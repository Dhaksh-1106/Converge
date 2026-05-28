<?php

session_start();
require "auth_check.php";
require "db_connect.php";

check_access(["student","faculty"]);

if($_SERVER["REQUEST_METHOD"] === "POST"){
    $project_id = new MongoDB\BSON\ObjectId($_POST["project_id"]);
    $collaborator_email = $_SESSION["user_email"];
    $db -> projects -> updateOne(["_id" => $project_id],['$push' => ["collaborators" => $collaborator_email]]);
    echo "Successfully joined the research team!";
}

else{
    echo "Not a POST request";
}


?>
