<?php
header('Content-Type: application/json');
session_start();

// Destroy session data
$_SESSION = [];
session_unset();
session_destroy();

echo json_encode(["status" => "success", "message" => "Logged out successfully"]);
exit();
?>