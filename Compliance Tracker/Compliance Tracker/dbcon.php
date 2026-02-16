<?php
$host = '127.0.0.1'; // Database host 
$username = 'root';  // Database username (default for XAMPP)
$password = '';      // Database password (default is empty for XAMPP)
$database = 'Securecompliancetracker'; // Your database name

// Create a connection
$connection = new mysqli($host, $username, $password, $database);

// Check the connection
if ($connection->connect_error) {
    die("Connection failed: " . $connection->connect_error);
}
?>