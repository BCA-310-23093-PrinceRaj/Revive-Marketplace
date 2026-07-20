<?php
session_start();
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../forgot_password.php');
    exit();
}

if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
    header('Location: ../reset_password.php?error=security_mismatch');
    exit();
}

$email = $_SESSION['reset_verified_email'] ?? '';
$verified_at = (int)($_SESSION['reset_verified_at'] ?? 0);
if (!$email || $verified_at < time() - 900) {
    unset($_SESSION['reset_verified_email'], $_SESSION['reset_verified_at']);
    header('Location: ../forgot_password.php?error=verification_expired');
    exit();
}

$password = $_POST['password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';
if (strlen($password) < 8) {
    header('Location: ../reset_password.php?error=weak_password');
    exit();
}
if ($password !== $confirm_password) {
    header('Location: ../reset_password.php?error=passwords_do_not_match');
    exit();
}

$hashed_password = password_hash($password, PASSWORD_DEFAULT);
$stmt = $conn->prepare('UPDATE users SET password = ?, reset_token = NULL, reset_token_expiry = NULL WHERE email = ?');
$stmt->bind_param('ss', $hashed_password, $email);
$stmt->execute();

unset($_SESSION['reset_verified_email'], $_SESSION['reset_verified_at']);
header('Location: ../login.php?success=password_reset');
exit();

