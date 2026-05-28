<?php

session_start();
require "auth_check.php";
require "db_connect.php";

check_access(["student","faculty"]);

if($_SERVER["REQUEST_METHOD"] === "GET"){

$search_term = isset($_GET["query"]) ? $_GET["query"] : "" ;
$filter = $search_term === "" ? [] : [
    "\$or" => [
        ["title" => ['$regex' => $search_term,'$options' => "i" ]],
        ["tags" => $search_term],
    ],

    "status" => "approved",
];

$results = $db -> projects -> find($filter) -> toArray();

if (empty($results)){
    echo "No matching projects found";
}

else{
    print_r($results);
}

}

else{
    echo "Not a GET request";
}

?>

