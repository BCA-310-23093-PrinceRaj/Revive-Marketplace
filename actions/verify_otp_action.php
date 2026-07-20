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
$otp = trim($_POST['otp'] ?? '');
$result = verify_otp_request($conn, $email, $purpose, $otp);
if (!$result['ok']) {
    header("Location: ../verify_otp.php?purpose=$purpose&error=" . urlencode($result['error']));
    exit();
}

if ($purpose === 'register') {
    $payload = json_decode($result['request']['payload'] ?? '', true);
    if (!$payload || empty($payload['name']) || empty($payload['password'])) {
        delete_otp_request($conn, $email, $purpose);
        header('Location: ../register.php?error=registration_expired');
        exit();
    }

    $stmt = $conn->prepare("INSERT INTO users (name, email, password, email_verified) VALUES (?, ?, ?, 1)");
    $stmt->bind_param('sss', $payload['name'], $email, $payload['password']);
    if (!$stmt->execute()) {
        delete_otp_request($conn, $email, $purpose);
        header('Location: ../register.php?error=email_exists');
        exit();
    }

    $_SESSION['user_id'] = $conn->insert_id;
    $_SESSION['user_name'] = $payload['name'];
    $_SESSION['user_role'] = 'buyer';
    delete_otp_request($conn, $email, $purpose);
    header('Location: ../index.php?success=registered');
    exit();
}

if ($purpose === 'admin_login') {
    $payload = json_decode($result['request']['payload'] ?? '', true);
    if (!$payload || empty($payload['user_id'])) {
        delete_otp_request($conn, $email, $purpose);
        header('Location: ../admin_login.php?error=session_expired');
        exit();
    }
    // Set admin session
    $_SESSION['user_id']   = $payload['user_id'];
    $_SESSION['user_name'] = $payload['name'];
    $_SESSION['user_role'] = 'admin';
    delete_otp_request($conn, $email, $purpose);
    header('Location: ../admin_dashboard.php');
    exit();
}

$_SESSION['reset_verified_email'] = $email;
$_SESSION['reset_verified_at'] = time();
delete_otp_request($conn, $email, $purpose);
header('Location: ../reset_password.php');
exit();
