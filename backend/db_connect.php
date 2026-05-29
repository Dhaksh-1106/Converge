<?php

require "vendor/autoload.php";

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$uri = "mongodb+srv://Dhaksh:".getenv("user_password")."@cluster0.cwd1iq7.mongodb.net/?appName=Cluster0";
try{
    $connection = new \MongoDB\Client($uri);
    $db=$connection -> db_1;
}
catch(Exception $e){
    echo "Connection error" . $e->getMessage();
}


?>