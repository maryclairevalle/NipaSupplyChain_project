<?php
$host = "localhost";
$user = "root";      // Change if your MySQL user differs
$pass = "";          // Change if your MySQL password differs
$dbname = "nipa_db"; // Your database name

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
