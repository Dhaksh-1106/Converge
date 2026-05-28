<?php

session_start();

require "db_connect.php";

if($_SERVER["REQUEST_METHOD"] === "POST"){

    $email=$_POST["email"];
    $password=$_POST["password"];

    if(! $email || ! $password){
        echo "Fields left empty";
    }

    else{

        $user = $db -> users -> findOne(["email" => $email]);

        if(! $user){
            echo "Invalid email or password";
            exit();
        }

        if(! password_verify($password,$user["hashed_password"])){
            echo "Invaild email or password";
            exit();
        }
        else{

            $_SESSION["user_email"] = $user["email"];
            $_SESSION["role"] = $user["role"];
            echo "Login Successful";

        }

    }

}

else
    echo "Not a POST request";


?>

