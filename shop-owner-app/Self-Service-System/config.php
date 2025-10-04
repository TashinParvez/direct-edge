<?php
// MySQL database connection
$host = 'localhost';  // Update if your MySQL server is elsewhere
$dbname = 'shopno';
$username = 'root';   // Replace with your MySQL username
$password = '';       // Replace with your MySQL password

try {
    $db = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
