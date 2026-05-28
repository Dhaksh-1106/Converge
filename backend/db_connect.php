<?php

require "vendor/autoload.php";

$uri = "mongodb+srv://Dhaksh:dg7ybICYSjmPg0HL@cluster0.cwd1iq7.mongodb.net/?appName=Cluster0";
try{
    $connection = new \MongoDB\Client($uri);
    $db=$connection -> db_1;
}
catch(Exception $e){
    echo "Connection error" . $e->getMessage();
}


?>