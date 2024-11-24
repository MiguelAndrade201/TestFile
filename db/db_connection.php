<?php
// db_connection.php

$host = 'localhost';
$dbname = 'cakeaway_db';
$username = 'root';
$password = 'root';

// Create a connection
$conn = new mysqli($host, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
