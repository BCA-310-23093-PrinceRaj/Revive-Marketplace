<?php
/**
 * Database Configuration for Revive
 */
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$host = "localhost";
$username = "root";
$password = "";
$dbname = "revive_db";

try {
    $conn = new mysqli($host, $username, $password, $dbname);
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
} catch (Exception $e) {
    // For development, we'll just log the error. 
    // In production, we would show a user-friendly message.
    error_log($e->getMessage());
}
?>
