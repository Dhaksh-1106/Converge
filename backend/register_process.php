<?php
require "db_connect.php";

if($_SERVER["REQUEST_METHOD"] === "POST"){

    $email=$_POST["email"];
    $password=$_POST["password"];
    $role=$_POST["role"];

    $password_length=strlen($password);

    if ($password_length < 8){
        echo "Password Length too short";
    }


    else if($role != "student" && $role != "faculty"){
        echo "Unauthorized access";
    }
    
    else{

        $hashed_password=password_hash($password,PASSWORD_DEFAULT);
        $payload = [

            "email" => $email,
            "hashed_password" => $hashed_password,
            "role" => $role,
        ];
        $db -> users -> insertOne($payload);
        echo "Registration Successful. You can now log in";
    }
}

else{
    echo "Not a POST request";
}

?>