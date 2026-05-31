<?php
require "vendor/autoload.php";

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

$uri = $_ENV["DB_URI"] ?? getenv("DB_URI");
$dbName = $_ENV["DB_NAME"] ?? "Converge";

try {
    // Check if MongoDB extension is loaded
    if (!extension_loaded('mongodb')) {
        header('Content-Type: application/json');
        http_response_code(503);
        echo json_encode([
            "status" => "error",
            "message" => "MongoDB PHP extension not installed. Run: php ext_mongodb_setup.php"
        ]);
        exit();
    }

    $client = new \MongoDB\Client($uri, [
        "serverSelectionTimeoutMS" => 5000,
        "connectTimeoutMS" => 10000
    ]);
    
    // Test connection
    $client->selectDatabase('admin')->command(['ping' => 1]);
    
    // Get database
    $db = $client->selectDatabase($dbName);
    
    // Ensure indexes exist
    $db->projects->createIndex(['title' => 'text', 'tags' => 'text']);
    $db->users->createIndex(['email' => 1], ['unique' => true]);
    
} catch (\MongoDB\Driver\Exception\ConnectionException $e) {
    header('Content-Type: application/json');
    http_response_code(503);
    echo json_encode([
        "status" => "error",
        "message" => "Cannot connect to MongoDB. Check your connection string and network: " . $e->getMessage()
    ]);
    exit();
} catch (\MongoDB\Driver\Exception\AuthenticationException $e) {
    header('Content-Type: application/json');
    http_response_code(401);
    echo json_encode([
        "status" => "error",
        "message" => "MongoDB authentication failed. Check credentials in .env"
    ]);
    exit();
} catch (Exception $e) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Database error: " . $e->getMessage()
    ]);
    exit();
}
?>