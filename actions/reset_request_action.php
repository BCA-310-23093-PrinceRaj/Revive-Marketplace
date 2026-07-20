<?php
session_start();
require_once '../config/db.php';
require_once '../includes/otp.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../forgot_password.php');
    exit();
}

if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
    header('Location: ../forgot_password.php?error=security_mismatch');
    exit();
}

$email = strtolower(trim($_POST['email'] ?? ''));
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: ../forgot_password.php?error=invalid_email');
    exit();
}

$stmt = $conn->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
$stmt->bind_param('s', $email);
$stmt->execute();
if (!$stmt->get_result()->fetch_assoc()) {
    header('Location: ../forgot_password.php?error=email_not_found');
    exit();
}

$otp_result = create_and_send_otp($conn, $email, 'reset');
if ($otp_result === 'sent') {
    unset($_SESSION['reset_verified_email']);
    header('Location: ../verify_otp.php?purpose=reset');
} elseif ($otp_result === 'wait') {
    header('Location: ../verify_otp.php?purpose=reset&error=resend_wait');
} else {
    header('Location: ../forgot_password.php?error=otp_send_failed');
}
exit();
