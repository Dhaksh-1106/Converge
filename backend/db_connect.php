<?php
require "vendor/autoload.php";

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

$uri = $_ENV["DB_URI"] ?? getenv("DB_URI");

try {
    $connection = new \MongoDB\Client($uri);
    $db = $connection->db_1;
    
    // Ensure text index exists for searching (runs silently if already exists)
    $db->projects->createIndex(['title' => 'text', 'tags' => 'text']);
    
} catch(Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(["status" => "error", "message" => "Database connection error."]);
    exit();
}
?>