<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Session Expiry (30 minutes)
$timeout = 1800; 
if (isset($_SESSION['last_active']) && (time() - $_SESSION['last_active']) > $timeout) {
    session_unset();
    session_destroy();
    header('Content-Type: application/json');
    echo json_encode(["status" => "error", "message" => "Session expired. Please log in again."]);
    exit();
}
$_SESSION['last_active'] = time();

// 2. CSRF Token Generation
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function check_access($accepted_roles) {
    if (!isset($_SESSION["user_email"])) {
        header('Content-Type: application/json');
        http_response_code(401);
        echo json_encode(["status" => "error", "message" => "Access Denied. Please log in."]);
        exit();
    }
    if (!in_array($_SESSION["role"], $accepted_roles)) {
        header('Content-Type: application/json');
        http_response_code(403);
        echo json_encode(["status" => "error", "message" => "Unauthorized access."]);
        exit();
    }
}

// 3. CSRF Verification Function
function verify_csrf() {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        header('Content-Type: application/json');
        http_response_code(403);
        echo json_encode(["status" => "error", "message" => "Invalid CSRF token. Request rejected."]);
        exit();
    }
}
?>