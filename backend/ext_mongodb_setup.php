<?php
/**
 * MongoDB PHP Extension Installer Guide
 * For PHP 8.5.6 ZTS x64 on Windows
 */

echo "=== MongoDB PHP Extension Setup ===\n\n";

// Check current PHP info
echo "Current PHP Configuration:\n";
echo "- Version: " . phpversion() . "\n";
echo "- Thread Safety: " . (extension_loaded('pthreads') || defined('PHP_THREAD_SAFE') ? 'ZTS' : 'NTS') . "\n";
$ext_dir = ini_get('extension_dir');
echo "- Extension Directory: $ext_dir\n";
echo "- PHP INI: " . get_cfg_var('cfg_file_path') . "\n\n";

$has_mongodb = extension_loaded('mongodb');
echo "MongoDB Extension Status: " . ($has_mongodb ? "✓ INSTALLED" : "✗ NOT INSTALLED") . "\n\n";

if (!$has_mongodb) {
    echo "INSTALLATION STEPS:\n";
    echo "==================\n\n";
    
    echo "1. Download MongoDB Driver (PHP 8.5 ZTS x64):\n";
    echo "   From: https://windows.php.net/downloads/pecl/releases/mongodb/\n";
    echo "   Download: php_mongodb-1.17.x-8.5-TS-x64.zip\n\n";
    
    echo "2. Extract the ZIP file:\n";
    echo "   - Extract php_mongodb.dll to: C:\\php\\ext\\\n\n";
    
    echo "3. Enable the extension in php.ini:\n";
    echo "   - Open: C:\\php\\php.ini\n";
    echo "   - Add or uncomment: extension=php_mongodb.dll\n";
    echo "   - Save the file\n\n";
    
    echo "4. Restart PHP:\n";
    echo "   - Stop the dev server (Ctrl+C)\n";
    echo "   - Close any PHP processes\n";
    echo "   - Restart: php -S localhost:8000\n\n";
    
    echo "5. Verify installation:\n";
    echo "   - Run: php -m | findstr mongodb\n";
    echo "   - Or visit: http://localhost:8000/backend/ext_mongodb_setup.php\n\n";
    
    echo "DIRECT DOWNLOAD LINK:\n";
    echo "====================\n";
    echo "PHP 8.5.6 ZTS x64: https://windows.php.net/downloads/pecl/releases/mongodb/\n";
    echo "(Look for the latest 8.5-TS-x64.zip file)\n\n";
    
} else {
    echo "✓ MongoDB extension is installed and ready!\n";
    echo "\nTesting connection to MongoDB Atlas...\n";
    
    try {
        $uri = getenv('DB_URI') ?: 'mongodb+srv://Harshith:geAxkoXqWQeEiy9j@cluster0.cwd1iq7.mongodb.net/?retryWrites=true&w=majority';
        $client = new \MongoDB\Client($uri, ['serverSelectionTimeoutMS' => 3000]);
        $client->selectDatabase('admin')->command(['ping' => 1]);
        echo "✓ Connected to MongoDB successfully!\n";
        echo "✓ Database: " . (getenv('DB_NAME') ?: 'Converge') . "\n";
    } catch (Exception $e) {
        echo "✗ Connection failed: " . $e->getMessage() . "\n";
        echo "Check your connection string and network connectivity.\n";
    }
}

echo "\n";
?>
