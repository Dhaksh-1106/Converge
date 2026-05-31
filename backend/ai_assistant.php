<?php
header('Content-Type: application/json');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require __DIR__ . '/vendor/autoload.php';
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

    $response = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $curlError = curl_error($curl);
    curl_close($curl);

    if ($response === false) {
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

    $response = curl_exec($curl);
    $curlError = curl_error($curl);
    curl_close($curl);

    if ($response === false) {
        http_response_code(502);
        echo json_encode(["status" => "error", "message" => "OpenAI request failed: {$curlError}"]);
        exit();
    }

    $data = json_decode($response, true);
    if (!$data || !isset($data['choices'][0]['message']['content'])) {
        http_response_code(502);
        echo json_encode(["status" => "error", "message" => "Invalid response from OpenAI.", "details" => $data]);
        exit();
    }

    $assistant = trim($data['choices'][0]['message']['content']);
    echo json_encode(["status" => "success", "assistant" => $assistant]);
    exit();
}

http_response_code(500);
echo json_encode(["status" => "error", "message" => "No AI provider is configured. Set HF_API_KEY for free Hugging Face access or OPENAI_API_KEY for OpenAI fallback."]);
exit();
