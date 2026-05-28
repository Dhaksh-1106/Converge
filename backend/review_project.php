<?php

session_start();
require "auth_check.php";
require "db_connect.php";

check_access(["faculty"]);

if($_SERVER["REQUEST_METHOD"] === "POST"){
    $project_id = new MongoDB\BSON\ObjectId($_POST["project_id"]);
    $action = $_POST["action"];

    $db -> projects -> updateOne(["_id" => $project_id],['$set' => ["status" => $action]]);
    echo "Project status has been updated";
}

else{
    echo "Not a POST request";
}

?>

