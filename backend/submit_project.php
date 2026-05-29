<?php
header('Content-Type: application/json');
require "auth_check.php";
require "db_connect.php";

// Intercept JSON payloads and magically convert them into standard $_POST variables
$json_payload = json_decode(file_get_contents('php://input'), true);
if (is_array($json_payload)) {
    $_POST = $json_payload;
}

check_access(["student", "faculty"]);
verify_csrf(); // CSRF Protection

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $title = trim($_POST["title"] ?? '');
    $description = trim($_POST["description"] ?? '');
    $tags = array_map('trim', explode(",", $_POST["tags"] ?? ''));
    $email = $_SESSION["user_email"];
    $owner_id = $_SESSION["user_id"];

    if (!$title || !$description) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Fields left empty"]);
        exit();
    }

    $payload = [
        "title" => $title,
        "description" => $description,
        "tags" => $tags,
        "creator" => $email,
        "owner_id" => $owner_id,
        "collaborators" => [],
        "status" => "pending",
        "deleted" => false,
        "created_at" => date("c"),
        "updated_at" => date("c")
    ];

    $db->projects->insertOne($payload);
    
    echo json_encode(["status" => "success", "message" => "Project added successfully"]);
    exit();
}

echo json_encode(["status" => "error", "message" => "Not a POST request"]);
?>