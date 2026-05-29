<?php
header('Content-Type: application/json');
require "db_connect.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = $_POST["email"] ?? '';
    $password = $_POST["password"] ?? '';
    $role = $_POST["role"] ?? '';

    if (strlen($password) < 8) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Password length too short"]);
        exit();
    }

    if ($role != "student" && $role != "faculty") {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Unauthorized access"]);
        exit();
    }

    // Check if user exists
    $existing = $db->users->findOne(["email" => $email]);
    if ($existing) {
        http_response_code(409);
        echo json_encode(["status" => "error", "message" => "Email already registered."]);
        exit();
    }

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $payload = [
        "email" => $email,
        "hashed_password" => $hashed_password,
        "role" => $role,
        "created_at" => date("c")
    ];
    
    $db->users->insertOne($payload);
    
    echo json_encode(["status" => "success", "message" => "Registration Successful. You can now log in."]);
    exit();
}

echo json_encode(["status" => "error", "message" => "Not a POST request"]);
?>