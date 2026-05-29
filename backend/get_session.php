<?php
header('Content-Type: application/json');
require "auth_check.php";
require "db_connect.php";

if (!isset($_SESSION["user_email"])) {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Not logged in"]);
    exit();
}

// Fetch the user's name from the DB
$user = $db->users->findOne(["email" => $_SESSION["user_email"]]);

echo json_encode([
    "status" => "success",
    "data" => [
        "name" => $user["name"] ?? "User",
        "email" => $_SESSION["user_email"],
        "role" => $_SESSION["role"]
    ],

    "csrf_token" => $_SESSION['csrf_token']
]);
exit();
?>