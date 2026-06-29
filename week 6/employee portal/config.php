<?php
session_start();
$host = 'localhost';
$user = 'root';
$password = '';
$database = 'employee_portal_db';

$conn = new mysqli($host, $user, $password, $database);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Helper functions
function isLoggedIn() {
    return isset($_SESSION['employee_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit();
    }
}

function redirect($url) {
    header("Location: $url");
    exit();
}
?>