<?php
session_start();

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];

    include __DIR__ . '/../include/connect-db.php'; // Database connection

    $stmt = $conn->prepare('SELECT role FROM users WHERE user_id = ? LIMIT 1');
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $stmt->bind_result($role);
    $stmt->fetch();
    $stmt->close();
}

// Unset all session variables
$_SESSION = array();

// Destroy the session
session_destroy();

if ($role == 'Admin')
    header("Location: ../Login-Signup/login.php");
else
    header("Location: ../Home/landing.php");
exit;
