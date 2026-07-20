<?php

require_once __DIR__ . '/mailer.php';

const OTP_EXPIRY_MINUTES = 10;
const OTP_RESEND_SECONDS = 60;
const OTP_MAX_ATTEMPTS = 5;

function create_and_send_otp($conn, $email, $purpose, $payload = null)
{
    $email = strtolower(trim($email));
    $existing = get_otp_request($conn, $email, $purpose);
    if ($existing && strtotime($existing['resend_available_at']) > time()) {
        return 'wait';
    }

    $otp = (string)random_int(100000, 999999);
    $otp_hash = password_hash($otp, PASSWORD_DEFAULT);
    $payload_json = $payload === null ? null : json_encode($payload);
    $expires_at = date('Y-m-d H:i:s', strtotime('+' . OTP_EXPIRY_MINUTES . ' minutes'));
    $resend_at = date('Y-m-d H:i:s', strtotime('+' . OTP_RESEND_SECONDS . ' seconds'));

    $stmt = $conn->prepare("INSERT INTO otp_requests
        (email, purpose, otp_hash, payload, expires_at, resend_available_at, attempts)
        VALUES (?, ?, ?, ?, ?, ?, 0)
        ON DUPLICATE KEY UPDATE otp_hash = VALUES(otp_hash), payload = VALUES(payload),
        expires_at = VALUES(expires_at), resend_available_at = VALUES(resend_available_at), attempts = 0");
    $stmt->bind_param('ssssss', $email, $purpose, $otp_hash, $payload_json, $expires_at, $resend_at);

    if (!$stmt->execute()) {
        return 'failed';
    }

    if (!send_otp_email($email, $otp, $purpose)) {
        $delete = $conn->prepare('DELETE FROM otp_requests WHERE email = ? AND purpose = ?');
        $delete->bind_param('ss', $email, $purpose);
        $delete->execute();
        return 'failed';
    }

    $_SESSION['otp_email'][$purpose] = $email;
    return 'sent';
}

function get_otp_request($conn, $email, $purpose)
{
    $stmt = $conn->prepare('SELECT * FROM otp_requests WHERE email = ? AND purpose = ? LIMIT 1');
    $stmt->bind_param('ss', $email, $purpose);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

function verify_otp_request($conn, $email, $purpose, $otp)
{
    $request = get_otp_request($conn, $email, $purpose);
    if (!$request) {
        return ['ok' => false, 'error' => 'otp_not_found'];
    }
    if ((int)$request['attempts'] >= OTP_MAX_ATTEMPTS) {
        return ['ok' => false, 'error' => 'too_many_attempts'];
    }
    if (strtotime($request['expires_at']) < time()) {
        return ['ok' => false, 'error' => 'otp_expired'];
    }
    if (!preg_match('/^[0-9]{6}$/', $otp) || !password_verify($otp, $request['otp_hash'])) {
        $stmt = $conn->prepare('UPDATE otp_requests SET attempts = attempts + 1 WHERE email = ? AND purpose = ?');
        $stmt->bind_param('ss', $email, $purpose);
        $stmt->execute();
        return ['ok' => false, 'error' => 'invalid_otp'];
    }
    return ['ok' => true, 'request' => $request];
}

function delete_otp_request($conn, $email, $purpose)
{
    $stmt = $conn->prepare('DELETE FROM otp_requests WHERE email = ? AND purpose = ?');
    $stmt->bind_param('ss', $email, $purpose);
    $stmt->execute();
    unset($_SESSION['otp_email'][$purpose], $_SESSION['dev_otp'][$purpose]);
}
