<?php
header('Content-Type: application/json');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit();
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Method not allowed. Use POST."]);
    exit();
}

$autoloadPath = __DIR__ . '/vendor/autoload.php';
if (!file_exists($autoloadPath)) {
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Missing backend dependencies. Install Composer and run: composer install in backend directory."
    ]);
    exit();
}

require $autoloadPath;

if (!class_exists('Dotenv\\Dotenv')) {
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "phpdotenv package missing. Run: composer install in backend directory."
    ]);
    exit();
}

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

require __DIR__ . '/auth_check.php';

check_access(['student', 'faculty']);

$rawBody = file_get_contents('php://input');
$body = json_decode($rawBody, true) ?: [];
if (!empty($body)) {
    $_POST = array_merge($_POST, $body);
}

verify_csrf();

$query = trim($body['query'] ?? $_POST['query'] ?? '');
if ($query === '') {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Please provide a question or prompt for the assistant."]);
    exit();
}

$hfKey = $_ENV['HF_API_KEY'] ?? getenv('HF_API_KEY');
$openaiKey = $_ENV['OPENAI_API_KEY'] ?? getenv('OPENAI_API_KEY');
$hfModel = $_ENV['HF_MODEL'] ?? getenv('HF_MODEL') ?? 'google/flan-t5-small';
$openaiModel = $_ENV['OPENAI_MODEL'] ?? getenv('OPENAI_MODEL') ?? 'gpt-3.5-turbo';
$role = $_SESSION['role'] ?? 'student';
$systemPrompt = 'You are Converge AI assistant. Respond concisely and help students and faculty with research project ideas, collaboration, and using the Converge platform.';

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
    $prefix = $role === 'faculty'
        ? 'Faculty mode: '
        : 'Student mode: ';

    if (strpos($q, 'idea') !== false || strpos($q, 'project') !== false) {
        return $prefix . 'Try framing your proposal as Problem, Method, Data, and Impact. Example topics: low-cost campus energy monitoring, attendance analytics with privacy controls, or AI-assisted peer mentoring workflows.';
    }

    if (strpos($q, 'team') !== false || strpos($q, 'collab') !== false) {
        return $prefix . 'Build teams with one domain expert, one implementation lead, and one testing/documentation lead. Set weekly milestones and a shared demo checklist.';
    }

    if (strpos($q, 'converge') !== false || strpos($q, 'platform') !== false || strpos($q, 'submit') !== false) {
        return $prefix . 'Use Submit Proposal with clear tags, then monitor status. Students can join approved projects; faculty can review pending items from the moderation panel.';
    }

    return $prefix . 'I can help with project ideas, proposal structure, team collaboration, and Converge workflow questions. Ask with your domain, constraints, and timeline for a better answer.';
}

if ($hfKey) {
    $url = "https://api-inference.huggingface.co/models/{$hfModel}";
    $prompt = "{$systemPrompt}\nRole: {$role}\nUser: {$query}";
    $payload = [
        'inputs' => $prompt,
        'parameters' => [
            'temperature' => 0.75,
            'max_new_tokens' => 250,
            'return_full_text' => false,
        ],
    ];

    $curl = curl_init($url);
    if ($curl === false) {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "Unable to initialize Hugging Face request."]);
        exit();
    }

    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $hfKey,
    ]);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($curl, CURLOPT_TIMEOUT, 35);

    $response = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $curlError = curl_error($curl);
    curl_close($curl);

    if ($response === false) {
<<<<<<< HEAD
        $isDnsIssue = stripos($curlError, 'could not resolve host') !== false || stripos($curlError, 'resolve host') !== false;
        if ($isDnsIssue && $openaiKey) {
            // fall through to OpenAI below
        } else {
            http_response_code(502);
            echo json_encode(["status" => "error", "message" => "Hugging Face request failed: {$curlError}"]);
            exit();
        }
    } else {
        $data = json_decode($response, true);
        if ($httpCode !== 200 || !$data) {
            http_response_code(502);
            echo json_encode(["status" => "error", "message" => "Invalid response from Hugging Face.", "details" => $data]);
            exit();
        }
        if (isset($data['error'])) {
            http_response_code(502);
            echo json_encode(["status" => "error", "message" => "Hugging Face error: " . $data['error']]);
            exit();
        }

        $assistant = '';
        if (isset($data[0]['generated_text'])) {
            $assistant = trim($data[0]['generated_text']);
        } elseif (isset($data['generated_text'])) {
            $assistant = trim($data['generated_text']);
        }
        if ($assistant === '') {
            http_response_code(502);
            echo json_encode(["status" => "error", "message" => "Unable to parse Hugging Face response.", "details" => $data]);
            exit();
        }

        echo json_encode(["status" => "success", "assistant" => $assistant]);
        exit();
    }
=======
        respond_error(502, "Hugging Face request failed: {$curlError}");
    }

    $data = json_decode($response, true) ?: [];

    // Retry once with wait_for_model when HF returns model loading state (usually 503).
    if ($httpCode === 503 && isset($data['error'])) {
        $payload['options'] = ['wait_for_model' => true];

        $retryCurl = curl_init($url);
        if ($retryCurl !== false) {
            curl_setopt($retryCurl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($retryCurl, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $hfKey,
            ]);
            curl_setopt($retryCurl, CURLOPT_POST, true);
            curl_setopt($retryCurl, CURLOPT_POSTFIELDS, json_encode($payload));
            curl_setopt($retryCurl, CURLOPT_CONNECTTIMEOUT, 10);
            curl_setopt($retryCurl, CURLOPT_TIMEOUT, 45);

            $retryResponse = curl_exec($retryCurl);
            $retryCode = curl_getinfo($retryCurl, CURLINFO_HTTP_CODE);
            curl_close($retryCurl);

            if ($retryResponse !== false) {
                $response = $retryResponse;
                $httpCode = $retryCode;
                $data = json_decode($response, true) ?: [];
            }
        }
    }

    if (isset($data['error'])) {
        respond_error(502, "Hugging Face error: " . $data['error'], $data);
    }

    $assistant = '';
    if (isset($data[0]['generated_text'])) {
        $assistant = trim($data[0]['generated_text']);
    } elseif (isset($data['generated_text'])) {
        $assistant = trim($data['generated_text']);
    } elseif (isset($data[0]['summary_text'])) {
        $assistant = trim($data[0]['summary_text']);
    } elseif (isset($data[0]['translation_text'])) {
        $assistant = trim($data[0]['translation_text']);
    }

    if ($assistant === '') {
        if ($httpCode === 200) {
            respond_success(local_assistant_fallback($query, $role), 'local-fallback');
        }
        respond_error(502, "Unable to parse Hugging Face response.", $data);
    }

    respond_success($assistant, 'huggingface');
>>>>>>> d8bf75d7f2624a65aedb2c682f625ff31ca8ca49
}

if ($openaiKey) {
    $payload = [
        'model' => $openaiModel,
        'messages' => [
            [
                'role' => 'system',
                'content' => $systemPrompt,
            ],
            [
                'role' => 'user',
                'content' => "Role: {$role}. User query: {$query}",
            ],
        ],
        'temperature' => 0.75,
        'max_tokens' => 500,
    ];

    $curl = curl_init('https://api.openai.com/v1/chat/completions');
    if ($curl === false) {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "Unable to initialize OpenAI request."]);
        exit();
    }

    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $openaiKey,
    ]);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($curl, CURLOPT_TIMEOUT, 35);

    $response = curl_exec($curl);
    $curlError = curl_error($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    if ($response === false) {
        respond_error(502, "OpenAI request failed: {$curlError}");
    }

    $data = json_decode($response, true) ?: [];
    if (isset($data['error']['message'])) {
        respond_error(502, "OpenAI error: " . $data['error']['message'], $data);
    }

    if (!$data || !isset($data['choices'][0]['message']['content'])) {
        respond_error(502, "Invalid response from OpenAI.", ['http_code' => $httpCode, 'raw' => $data]);
    }

    $assistant = trim($data['choices'][0]['message']['content']);
    respond_success($assistant, 'openai');
}

respond_success(local_assistant_fallback($query, $role), 'local-fallback');
