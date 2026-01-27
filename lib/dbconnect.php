<?php


$host     = 'localhost'; 
$username = "iee2021075";       
$password = "1234";
$dbname   = "kseri_db"; 
$socket   = "/home/student/iee/2021/iee2021075/mysql/run/mysql.sock";


$mysqli = new mysqli($host, $username, $password, $dbname, null, $socket);

if ($mysqli->connect_errno) {
    die("Connection failed: " . $mysqli->connect_error);
}

?>
