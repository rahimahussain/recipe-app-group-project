<?php
// Database Configuration
$servername = "localhost";
$username = "root";     // Default username for local servers
$password = "";         // Default password is empty
$dbname = "recipe_app_database"; // The name we chose in database.sql

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to UTF-8 to support all characters
$conn->set_charset("utf8");
?>
