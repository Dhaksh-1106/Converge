<?php
/**
 * Test MongoDB Connection
 * Run this after installing the MongoDB extension
 */

header('Content-Type: application/json');

echo json_encode([
    "php_version" => phpversion(),
    "mongodb_installed" => extension_loaded('mongodb') ? "✓ Yes" : "✗ No",
    "next_step" => extension_loaded('mongodb') 
        ? "Install the MongoDB extension, then restart PHP server"
        : "MongoDB extension is ready!"
], JSON_PRETTY_PRINT);

if (extension_loaded('mongodb')) {
    try {
        $uri = $_ENV["DB_URI"] ?? getenv("DB_URI") ?: 'mongodb+srv://Harshith:geAxkoXqWQeEiy9j@cluster0.cwd1iq7.mongodb.net/?retryWrites=true&w=majority';
        $client = new \MongoDB\Client($uri, [
            'serverSelectionTimeoutMS' => 5000,
            'connectTimeoutMS' => 10000
        ]);
        
        // Test connection
        $admin = $client->selectDatabase('admin');
        $admin->command(['ping' => 1]);
        
        // Get list of databases
        $databases = $client->listDatabases();
        
        echo "\n✓ MongoDB Connection Successful!\n";
        echo "Connected to: " . $client->getManager()->getServers()[0] . "\n";
        echo "Available databases: ";
        foreach ($databases as $db) {
            echo $db['name'] . " ";
        }
        echo "\n";
        
    } catch (\MongoDB\Driver\Exception\Exception $e) {
        echo "\n✗ Connection Error: " . $e->getMessage() . "\n";
    }
}
?>
