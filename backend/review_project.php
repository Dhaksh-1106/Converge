<?php
header('Content-Type: application/json');
require "auth_check.php";
require "db_connect.php";

// 1. MUST decode JSON before running any security checks!
$json_payload = json_decode(file_get_contents('php://input'), true);
if (is_array($json_payload)) {
    $_POST = $json_payload;
}

// 2. Strict Security Gates
check_access(["faculty"]);
verify_csrf(); 

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    try {
        // 3. Validate ID presence before converting
        if (empty($_POST["project_id"])) {
            throw new Exception("Project ID is missing from the payload.");
        }
        $project_id = new MongoDB\BSON\ObjectId($_POST["project_id"]);
        
        // 4. Bulletproof the action tense mapping
        $raw_action = strtolower($_POST["action"] ?? '');
        $action = ($raw_action === 'approve' || $raw_action === 'approved') ? 'approved' : 'rejected';

        // 5. Build Update Payload
        $updatePayload = [
            "status" => $action,
            "updated_at" => date("c")
        ];

        if ($action === 'approved') {
            $updatePayload["approved_by"] = $_SESSION["user_email"];
        }

        // Soft Delete Logic
        if ($action === 'rejected') {
            $updatePayload["deleted"] = true;
        }

        // 6. Execute Database Update
        $result = $db->projects->updateOne(
            ["_id" => $project_id],
            ['$set' => $updatePayload]
        );

        // 7. Audit Log Insertion
        $auditPayload = [
            "project_id" => (string)$project_id,
            "faculty_email" => $_SESSION["user_email"],
            "action" => $action,
            "timestamp" => date("c")
        ];
        $db->audit_log->insertOne($auditPayload);

        echo json_encode(["status" => "success", "message" => "Project status updated!"]);
        exit();
        
    } catch (Exception $e) {
        http_response_code(400);
        // FIX: Now the UI toast will show the exact PHP crash reason!
        echo json_encode(["status" => "error", "message" => "Server Error: " . $e->getMessage()]);
        exit();
    }
}

echo json_encode(["status" => "error", "message" => "Not a POST request"]);
?>