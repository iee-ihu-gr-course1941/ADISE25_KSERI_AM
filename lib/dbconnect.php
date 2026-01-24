<?php
$servername = null;  
$username = "iee2021075";       
$password = "1234";      
$dbname   = "kseri_db"; 
$socket   = "/home/student/iee/2021/iee2021075/mysql/run/mysql.sock"; // path from step 1

$conn = mysqli_connect($servername, $username, $password, $dbname, null, $socket);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
?>