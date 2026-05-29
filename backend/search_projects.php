<?php
header('Content-Type: application/json');
require "auth_check.php";
require "db_connect.php";

check_access(["student", "faculty"]);

// Intercept JSON payloads and magically convert them into standard $_POST variables
$json_payload = json_decode(file_get_contents('php://input'), true);
if (is_array($json_payload)) {
    $_POST = $json_payload;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    
    // Changed from $_GET to $_POST
    $search_term = trim($_POST["query"] ?? "");
    
    // Base filter: only approved, not deleted
    $filter = [
        "status" => "approved",
        "deleted" => ['$ne' => true]
    ];

    // Utilize Text Search Index if query exists
    if ($search_term !== "") {
        $filter['$text'] = ['$search' => $search_term];
    }

    $results = $db->projects->find($filter)->toArray();

    echo json_encode([
        "status" => "success", 
        "data" => $results
    ]);
    exit();
}

// Updated error message to reflect the new expected method
echo json_encode(["status" => "error", "message" => "Not a POST request"]);
?>