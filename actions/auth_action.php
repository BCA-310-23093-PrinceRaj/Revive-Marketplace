<?php
session_start();
require_once '../config/db.php';
require_once '../includes/otp.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF Protection
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        header("Location: ../login.php?error=security_mismatch");
        exit();
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'register') {
        $name_input = trim($_POST['name']);
        $email_input = trim($_POST['email']);
        $password_input = $_POST['password'];

        // Validation 1: Person names cannot contain numbers or unsupported symbols.
        if ($name_input === '' || !preg_match("/^[\p{L}][\p{L}\s.'-]*$/u", $name_input)) {
            header("Location: ../register.php?error=invalid_name");
            exit();
        }

        // Validation 2: Email Format
        if (!filter_var($email_input, FILTER_VALIDATE_EMAIL)) {
            header("Location: ../register.php?error=invalid_email");
            exit();
        }

        // Validation 3: Password Length/Strength
        if (strlen($password_input) < 8) {
            header("Location: ../register.php?error=weak_password");
            exit();
        }

        $email_input = strtolower($email_input);
        $email = mysqli_real_escape_string($conn, $email_input);
        $password = password_hash($password_input, PASSWORD_DEFAULT);

        // Check if email already exists
        $check = $conn->query("SELECT id FROM users WHERE email = '$email'");
        if ($check->num_rows > 0) {
            header("Location: ../register.php?error=email_exists");
            exit();
        }

        $payload = ['name' => $name_input, 'password' => $password];
        $otp_result = create_and_send_otp($conn, $email_input, 'register', $payload);
        if ($otp_result === 'sent') {
            header("Location: ../verify_otp.php?purpose=register");
        } elseif ($otp_result === 'wait') {
            header("Location: ../verify_otp.php?purpose=register&error=resend_wait");
        } else {
            header("Location: ../register.php?error=otp_send_failed");
        }
    } 
    
    elseif ($action === 'login' || $action === 'admin_login') {
        // Rate Limiting: max 5 failed attempts per IP per 15 minutes
        $ip = $_SERVER['REMOTE_ADDR'];
        $rate_key = 'login_attempts_' . md5($ip);
        $time_key = 'login_attempt_time_' . md5($ip);

        if (!isset($_SESSION[$rate_key])) $_SESSION[$rate_key] = 0;
        if (!isset($_SESSION[$time_key])) $_SESSION[$time_key] = time();

        // Reset counter after 15 minutes
        if (time() - $_SESSION[$time_key] > 900) {
            $_SESSION[$rate_key] = 0;
            $_SESSION[$time_key] = time();
        }

        if ($_SESSION[$rate_key] >= 5) {
            $wait = ceil((900 - (time() - $_SESSION[$time_key])) / 60);
            $loc = ($action === 'admin_login') ? "../admin_login.php" : "../login.php";
            header("Location: $loc?error=too_many_attempts&wait=$wait");
            exit();
        }

        $email = mysqli_real_escape_string($conn, $_POST['email']);
        $password = $_POST['password'];

        $result = $conn->query("SELECT * FROM users WHERE email = '$email'");
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                // Reset rate limit on successful login
                $_SESSION[$rate_key] = 0;

                if (isset($user['email_verified']) && !$user['email_verified']) {
                    header("Location: ../login.php?error=email_not_verified");
                    exit();
                }
                if($user['status'] === 'banned') {
                    $loc = ($action === 'admin_login') ? "../admin_login.php" : "../login.php";
                    header("Location: $loc?error=banned");
                    exit();
                }
                
                // If it's an admin login, check the role BEFORE session
                if ($action === 'admin_login') {
                    if ($user['role'] !== 'admin') {
                        header("Location: ../admin_login.php?error=unauthorized");
                        exit();
                    }
                    // ✅ Admin credentials valid — now send OTP (2-Step)
                    $_SESSION[$rate_key] = 0;
                    $otp_result = create_and_send_otp($conn, $user['email'], 'admin_login', ['user_id' => $user['id'], 'name' => $user['name']]);
                    if ($otp_result === 'sent' || $otp_result === 'wait') {
                        header("Location: ../admin_verify_otp.php" . ($otp_result === 'wait' ? '?error=resend_wait' : ''));
                    } else {
                        header("Location: ../admin_login.php?error=otp_send_failed");
                    }
                    exit();
                }

                // Regular user login — set session directly
                $_SESSION[$rate_key] = 0;
                $_SESSION['user_id']   = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_role'] = $user['role'];
                
                $redirect = ($user['role'] === 'admin') ? "../admin_dashboard.php" : "../index.php?success=logged_in";
                header("Location: $redirect");
            } else {
                // Increment failed attempt counter
                $_SESSION[$rate_key]++;
                $loc = ($action === 'admin_login') ? "../admin_login.php" : "../login.php";
                header("Location: $loc?error=invalid_credentials");
            }
        } else {
            $_SESSION[$rate_key]++;
            $loc = ($action === 'admin_login') ? "../admin_login.php" : "../login.php";
            header("Location: $loc?error=user_not_found");
        }
    }
}
?>
