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
    try {
        $project_id = new MongoDB\BSON\ObjectId($_POST["project_id"]);
        $collaborator_email = $_SESSION["user_email"];
        
        $db->projects->updateOne(
            ["_id" => $project_id],
            ['$addToSet' => ["collaborators" => $collaborator_email]] // $addToSet prevents duplicates better than $push
        );
        
        echo json_encode(["status" => "success", "message" => "Successfully joined the research team!"]);
        exit();
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Invalid Project ID."]);
        exit();
    }
}

echo json_encode(["status" => "error", "message" => "Not a POST request"]);
?>