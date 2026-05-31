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

$apiKey = $_ENV['OPENAI_API_KEY'] ?? getenv('OPENAI_API_KEY');
if (!$apiKey) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "OPENAI_API_KEY is not configured. Please add it to backend/.env."]);
    exit();
}

$model = $_ENV['OPENAI_MODEL'] ?? getenv('OPENAI_MODEL') ?? 'gpt-3.5-turbo';
$role = $_SESSION['role'] ?? 'student';

$payload = [
    'model' => $model,
    'messages' => [
        [
            'role' => 'system',
            'content' => 'You are Converge AI assistant. Respond concisely and help students and faculty with research project ideas, collaboration, and using the Converge platform.'
        ],
        [
            'role' => 'user',
            'content' => "Role: {$role}. User query: {$query}"
        ]
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
    'Authorization: Bearer ' . $apiKey,
]);
curl_setopt($curl, CURLOPT_POST, true);
curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($payload));

$response = curl_exec($curl);
$httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
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
