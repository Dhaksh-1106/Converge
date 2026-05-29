<?php
session_start();
header('Content-Type: application/json');
require "db_connect.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    
    // Rate Limiting Logic (5 attempts, 5 minute lockout)
    $max_attempts = 5;
    $lockout_duration = 300; 

    if (isset($_SESSION['lockout_time']) && time() < $_SESSION['lockout_time']) {
        $remaining = $_SESSION['lockout_time'] - time();
        http_response_code(429);
        echo json_encode(["status" => "error", "message" => "Account locked. Try again in $remaining seconds."]);
        exit();
    }

    $email = $_POST["email"] ?? '';
    $password = $_POST["password"] ?? '';

    if (!$email || !$password) {
        echo json_encode(["status" => "error", "message" => "Fields left empty."]);
        exit();
    }

    $user = $db->users->findOne(["email" => $email]);

    if (!$user || !password_verify($password, $user["hashed_password"])) {
        // Record failed attempt
        $_SESSION['login_attempts'] = ($_SESSION['login_attempts'] ?? 0) + 1;
        if ($_SESSION['login_attempts'] >= $max_attempts) {
            $_SESSION['lockout_time'] = time() + $lockout_duration;
        }
        
        http_response_code(401);
        echo json_encode(["status" => "error", "message" => "Invalid email or password."]);
        exit();
    }

    // Success: Reset rate limiting and set session
    unset($_SESSION['login_attempts']);
    unset($_SESSION['lockout_time']);
    
    $_SESSION["user_email"] = $user["email"];
    $_SESSION["role"] = $user["role"];
    $_SESSION["user_id"] = (string)$user["_id"];
    
    // Generate fresh CSRF token on login
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

    echo json_encode([
        "status" => "success", 
        "message" => "Login Successful", 
        "role" => $user["role"],
        "csrf_token" => $_SESSION['csrf_token']
    ]);
    exit();
}

echo json_encode(["status" => "error", "message" => "Not a POST request"]);
?>