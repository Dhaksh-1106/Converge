<?php
header('Content-Type: application/json');
require "auth_check.php";
require "db_connect.php";

// Strict Role Enforcement
check_access(["faculty"]);

if ($_SERVER["REQUEST_METHOD"] === "GET") {
    $filter = [
        "status" => "pending",
        "deleted" => ['$ne' => true]
    ];

    $results = $db->projects->find($filter)->toArray();

    echo json_encode([
        "status" => "success",
        "data" => $results
    ]);
    exit();
}
?>