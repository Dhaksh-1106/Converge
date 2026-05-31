<?php
// CLI helper to test register_process.php without HTTP server
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST['name'] = 'CLI Tester';
$_POST['email'] = 'clitester+' . rand(1000,9999) . '@example.com';
$_POST['password'] = 'Password123!';
$_POST['role'] = 'student';

// Capture output
ob_start();
require __DIR__ . '/register_process.php';
$out = ob_get_clean();
echo "Output from register_process.php:\n";
echo $out . PHP_EOL;
?>