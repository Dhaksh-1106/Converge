<?php

session_start();
require "auth_check.php";
require "db_connect.php";

check_access(["student","faculty"]);

if($_SERVER["REQUEST_METHOD"] === "POST"){

    $title=$_POST["title"];
    $description=$_POST["description"];
    $tags=explode(",",$_POST["tags"]);
    $email=$_SESSION["user_email"];

    if(! $title || ! $description){
        echo "Fields left empty";
        exit();
    }

    $payload=[

        "title" => $title,
        "description" => $description,
        "tags" => $tags,
        "creator"=> $email,
        "collaborators" => [],
        "created_at" => date("Y-m-d"),
        "status" => "pending",
    
        ];

    $db -> projects -> insertOne($payload);
    echo "Project added successfully";


}

else{
    echo "Not a POST request";
}

?>
