<?php
session_start();
require_once '../config/db.php';
require_once '../includes/otp.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../login.php');
    exit();
}

$purpose = $_POST['purpose'] ?? '';
if (!in_array($purpose, ['register', 'reset', 'admin_login'], true)) {
    header('Location: ../login.php?error=invalid_request');
    exit();
}

if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
    header("Location: ../verify_otp.php?purpose=$purpose&error=security_mismatch");
    exit();
}

$email = $_SESSION['otp_email'][$purpose] ?? '';
$request = $email ? get_otp_request($conn, $email, $purpose) : null;

$verify_page = ($purpose === 'admin_login') ? '../admin_verify_otp.php' : "../verify_otp.php?purpose=$purpose";
$start_page  = ($purpose === 'register') ? '../register.php' : (($purpose === 'admin_login') ? '../admin_login.php' : '../forgot_password.php');

if (!$request) {
    header("Location: $start_page?error=otp_not_found");
    exit();
}
if (strtotime($request['resend_available_at']) > time()) {
    header("Location: $verify_page" . (str_contains($verify_page, '?') ? '&' : '?') . "error=resend_wait");
    exit();
}

$payload = $request['payload'] ? json_decode($request['payload'], true) : null;
$otp_result = create_and_send_otp($conn, $email, $purpose, $payload);
if ($otp_result === 'sent') {
    header("Location: $verify_page" . (str_contains($verify_page, '?') ? '&' : '?') . "success=otp_resent");
} elseif ($otp_result === 'wait') {
    header("Location: $verify_page" . (str_contains($verify_page, '?') ? '&' : '?') . "error=resend_wait");
} else {
    header("Location: $verify_page" . (str_contains($verify_page, '?') ? '&' : '?') . "error=otp_send_failed");
}
exit();

