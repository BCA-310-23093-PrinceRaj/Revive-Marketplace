<?php

function smtp_read_response($socket)
{
    $response = '';
    while (($line = fgets($socket, 515)) !== false) {
        $response .= $line;
        if (strlen($line) < 4 || $line[3] === ' ') {
            break;
        }
    }
    return $response;
}

function smtp_command($socket, $command, $expected_codes)
{
    if ($command !== null) {
        fwrite($socket, $command . "\r\n");
    }
    $response = smtp_read_response($socket);
    $code = (int)substr($response, 0, 3);
    if (!in_array($code, (array)$expected_codes, true)) {
        throw new RuntimeException("SMTP command failed ($code): $response");
    }
    return $response;
}

function send_otp_email($recipient, $otp, $purpose)
{
    $config = require __DIR__ . '/../config/mail.php';
    $purpose_label = $purpose === 'register' ? 'email verification' : 'password reset';

    if (empty($config['enabled'])) {
        if (!empty($config['dev_show_otp'])) {
            $_SESSION['dev_otp'][$purpose] = $otp;
            return true;
        }
        return false;
    }

    $host = $config['host'];
    $port = (int)$config['port'];
    $transport = ($config['encryption'] ?? '') === 'ssl' ? "ssl://$host" : $host;
    $socket = stream_socket_client(
        "$transport:$port",
        $error_number,
        $error_message,
        20,
        STREAM_CLIENT_CONNECT
    );

    if (!$socket) {
        error_log("SMTP connection failed: $error_number $error_message");
        return false;
    }

    stream_set_timeout($socket, 20);

    try {
        smtp_command($socket, null, 220);
        smtp_command($socket, 'EHLO localhost', 250);

        if (($config['encryption'] ?? '') === 'tls') {
            smtp_command($socket, 'STARTTLS', 220);
            if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                throw new RuntimeException('Unable to enable SMTP TLS encryption.');
            }
            smtp_command($socket, 'EHLO localhost', 250);
        }

        smtp_command($socket, 'AUTH LOGIN', 334);
        smtp_command($socket, base64_encode($config['username']), 334);
        smtp_command($socket, base64_encode($config['password']), 235);
        smtp_command($socket, 'MAIL FROM:<' . $config['from_email'] . '>', 250);
        smtp_command($socket, 'RCPT TO:<' . $recipient . '>', [250, 251]);
        smtp_command($socket, 'DATA', 354);

        $subject = 'Revive ' . ucwords($purpose_label) . ' OTP';
        $body = '<div style="font-family:Arial,sans-serif;max-width:560px;margin:auto;padding:32px;background:#0a0a0a;color:#fff;border-radius:20px">'
            . '<h1 style="margin:0 0 16px;color:#bcff00">REVIVE.</h1>'
            . '<p>Use this one-time password for your ' . htmlspecialchars($purpose_label) . ':</p>'
            . '<p style="font-size:36px;letter-spacing:8px;font-weight:bold;color:#bcff00">' . htmlspecialchars($otp) . '</p>'
            . '<p style="color:#aaa">This OTP expires in 10 minutes. Never share it with anyone.</p></div>';

        $headers = [
            'Date: ' . date(DATE_RFC2822),
            'From: ' . $config['from_name'] . ' <' . $config['from_email'] . '>',
            'To: <' . $recipient . '>',
            'Subject: ' . $subject,
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
            'Content-Transfer-Encoding: 8bit',
        ];
        $message = implode("\r\n", $headers) . "\r\n\r\n" . $body;
        $message = preg_replace('/^\./m', '..', $message);
        fwrite($socket, $message . "\r\n.\r\n");
        smtp_command($socket, null, 250);
        smtp_command($socket, 'QUIT', 221);
        fclose($socket);
        return true;
    } catch (Throwable $e) {
        error_log($e->getMessage());
        fclose($socket);
        return false;
    }
}

