<?php
session_start();
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST['email'] = 'webtester+1@example.com';
$_POST['password'] = 'Password123!';

ob_start();
require __DIR__ . '/login_process.php';
$out = ob_get_clean();
echo "Login output:\n";
echo $out . PHP_EOL;
?>
