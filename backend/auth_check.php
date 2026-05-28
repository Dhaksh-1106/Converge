<?php

session_start();

function check_access($accepted_roles){

    if (! isset($_SESSION["user_email"])){
    echo "Access Denied. Please log in.";
    exit();
}

if(! in_array($_SESSION["role"],$accepted_roles) ){
    echo "Unauthorized : Faculty access only";
    exit();
}
}


?>
