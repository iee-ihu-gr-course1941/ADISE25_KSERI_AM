<?php

$server_name = "localhost";
$username  = "root";
$password  = "";
$db_name = "kseri_db";

// create connection
$mysqli = mysqli_connect($server_name, $username, $password, $db_name);

// check connection

if ($mysqli -> connect_error) {
    die("Connection failed: " . $mysqli -> connect_error);
}

// echo ("Connected successfully!");

?>