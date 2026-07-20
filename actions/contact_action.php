<?php
session_start();
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF Protection
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        header("Location: ../contact.php?error=security_mismatch");
        exit();
    }

    $name_input = trim($_POST['name'] ?? '');
    $email_input = trim($_POST['email'] ?? '');
    $message_input = trim($_POST['message'] ?? '');

    if ($name_input === '' || $email_input === '' || $message_input === '') {
        header("Location: ../contact.php?error=empty_fields");
        exit();
    }

    if (!preg_match("/^[\p{L}][\p{L}\s.'-]*$/u", $name_input)) {
        header("Location: ../contact.php?error=invalid_name");
        exit();
    }

    if (!filter_var($email_input, FILTER_VALIDATE_EMAIL)) {
        header("Location: ../contact.php?error=invalid_email");
        exit();
    }

    $name = $conn->real_escape_string($name_input);
    $email = $conn->real_escape_string($email_input);
    $message = $conn->real_escape_string($message_input);

    $sql = "INSERT INTO messages (name, email, message) VALUES ('$name', '$email', '$message')";
    if ($conn->query($sql)) {
        header("Location: ../contact.php?success=sent");
    } else {
        header("Location: ../contact.php?error=failed");
    }
}
?>
