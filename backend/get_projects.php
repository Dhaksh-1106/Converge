<?php
header('Content-Type: application/json');
require "auth_check.php";
require "db_connect.php";

// Security Gate
check_access(["student", "faculty"]);

if ($_SERVER["REQUEST_METHOD"] === "GET") {
    try {
        // Only fetch approved projects that haven't been soft-deleted
        $filter = [
            "status" => "approved",
            "deleted" => ['$ne' => true]
        ];

        $results = $db->projects->find($filter)->toArray();

        echo json_encode([
            "status" => "success",
            "data" => $results
        ]);
        exit();
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "Server Error: " . $e->getMessage()]);
        exit();
    }
}

echo json_encode(["status" => "error", "message" => "Not a GET request"]);
?>