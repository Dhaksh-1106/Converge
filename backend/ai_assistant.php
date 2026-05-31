<?php
header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Handle CORS Preflight Options Request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit();
}

// 2. Strict Method Gate
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Method not allowed. Use POST."]);
    exit();
}

// 3. Environment Config Mapping (Native .ini parsing used in your db_connect setup)
$envFilePath = __DIR__ . '/.env';
if (file_exists($envFilePath)) {
    $env = parse_ini_file($envFilePath);
    $hfKey = $env['HF_API_KEY'] ?? $env['HF_TOKEN'] ?? getenv('HF_API_KEY') ?? getenv('HF_TOKEN');
    $openaiKey = $env['OPENAI_API_KEY'] ?? getenv('OPENAI_API_KEY');
    $hfModel = $env['HF_MODEL'] ?? getenv('HF_MODEL') ?? 'meta-llama/Llama-3-8B-Instruct'; // Standard operational text-router model
    $openaiModel = $env['OPENAI_MODEL'] ?? getenv('OPENAI_MODEL') ?? 'gpt-3.5-turbo';
} else {
    $hfKey = getenv('HF_API_KEY') ?? getenv('HF_TOKEN');
    $openaiKey = getenv('OPENAI_API_KEY');
    $hfModel = getenv('HF_MODEL') ?? 'meta-llama/Llama-3-8B-Instruct';
    $openaiModel = getenv('OPENAI_MODEL') ?? 'gpt-3.5-turbo';
}

// 4. Incorporate Central Security Controls
require __DIR__ . '/auth_check.php';
check_access(['student', 'faculty']);

// 5. Intercept and Normalize Incoming Payload Streams
$rawBody = file_get_contents('php://input');
$body = json_decode($rawBody, true) ?: [];
if (!empty($body)) {
    $_POST = array_merge($_POST, $body);
}

verify_csrf();

// 6. Validate Input Presence
$query = trim($body['query'] ?? $_POST['query'] ?? '');
if ($query === '') {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Please provide a question or prompt for the assistant."]);
    exit();
}

$role = $_SESSION['role'] ?? 'student';
$systemPrompt = 'You are Converge AI assistant. Respond concisely in two or three sentences maximum and help students and faculty with research project ideas, collaboration, and using the Converge platform.';

// ============================================================
// SYSTEM HELPER RESPONDERS
// ============================================================
function respond_success(string $assistant, string $provider = 'unknown'): void {
    echo json_encode([
        "status" => "success",
        "assistant" => $assistant,
        "provider" => $provider,
    ]);
    exit();
}

function respond_error(int $code, string $message, array $details = []): void {
    http_response_code($code);
    $payload = ["status" => "error", "message" => $message];
    if (!empty($details)) {
        $payload["details"] = $details;
    }
    echo json_encode($payload);
    exit();
}

function local_assistant_fallback(string $query, string $role): string {
    $q = strtolower($query);
    $prefix = $role === 'faculty' ? 'Faculty mode: ' : 'Student mode: ';

    if (strpos($q, 'idea') !== false || strpos($q, 'project') !== false || strpos($q, 'research') !== false) {
        return $prefix . 'Try framing your proposal using Problem, Method, Data, and Impact bounds. Great starting concepts include campus energy consumption analytics, privacy-preserving attendance monitors, or AI-assisted peer-mentoring architectures.';
    }

    if (strpos($q, 'team') !== false || strpos($q, 'collab') !== false || strpos($q, 'member') !== false) {
        return $prefix . 'Build stable teams balanced across one domain architect, an implementation developer, and a testing/documentation lead. Maintain clear weekly checkpoints and shared milestone trackers.';
    }

    if (strpos($q, 'converge') !== false || strpos($q, 'platform') !== false || strpos($q, 'submit') !== false) {
        return $prefix . 'To post concepts, use the Submit Proposal module with comma-separated tag lines. Students can immediately join approved works from the public panel; faculty manage actions using the moderation panel.';
    }

    return $prefix . 'I can assist you with optimizing project proposals, defining team workflows, or handling your active Converge dashboard processes. Share your specific domain or timeline constraint for a tailored answer.';
}

// ============================================================
// BACKEND HUGGING FACE INFERENCE ROUTER PIPELINE
// ============================================================
if ($hfKey) {
    // 1. Maintain the global router endpoint
    $url = "https://router.huggingface.co/v1/chat/completions";
    
    // 2. CORRECTION: Append the fallback provider policy suffix to your model id
    $modelTarget = strpos($hfModel, ':') === false ? "{$hfModel}:fastest" : $hfModel;

    $payload = [
        'model' => $modelTarget,
        'messages' => [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => "Role: {$role}. User query: {$query}"]
        ],
        'temperature' => 0.75,
        'max_tokens' => 250
    ];

    $curl = curl_init($url);
    if ($curl === false) {
        respond_success(local_assistant_fallback($query, $role), 'local-fallback');
    }

    // 3. CORRECTION: Add User-Agent header to slip past Cloudflare blocking rules
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $hfKey,
        'User-Agent: ConvergeCampusPlatform/1.0 (PHP cURL Request)' 
    ]);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 30); 
    curl_setopt($curl, CURLOPT_TIMEOUT, 60);

    $response = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    // If the network drops completely, log the raw issue internally and fallback
    if ($response === false) {
        respond_success(local_assistant_fallback($query, $role), 'local-fallback');
    }

    $data = json_decode($response, true) ?: [];

    // Extract content matching OpenAI formatting schemas returned by global routers
    $assistant = '';
    if (isset($data['choices'][0]['message']['content'])) {
        $assistant = trim($data['choices'][0]['message']['content']);
    }

    if ($assistant !== '') {
        respond_success($assistant, 'huggingface');
    }
    // Add this temporarily right before the end of the HF block to debug:
if ($assistant === '') {
    respond_error($httpCode, "HF API Error Response", $data);
}
}

// ============================================================
// OPENAI COMPILATION FALLBACK ROUTINE
// ============================================================
if ($openaiKey) {
    $payload = [
        'model' => $openaiModel,
        'messages' => [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => "Role: {$role}. User query: {$query}"],
        ],
        'temperature' => 0.75,
        'max_tokens' => 500,
    ];

    $curl = curl_init('https://api.openai.com/v1/chat/completions');
    if ($curl !== false) {
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $openaiKey,
        ]);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 15);
        curl_setopt($curl, CURLOPT_TIMEOUT, 35);

        $response = curl_exec($curl);
        curl_close($curl);

        if ($response !== false) {
            $data = json_decode($response, true) ?: [];
            if (isset($data['choices'][0]['message']['content'])) {
                $assistant = trim($data['choices'][0]['message']['content']);
                respond_success($assistant, 'openai');
            }
        }
    }
}

// Default back to localized server rules if all external calls are network dropped
respond_success(local_assistant_fallback($query, $role), 'local-fallback');
?>