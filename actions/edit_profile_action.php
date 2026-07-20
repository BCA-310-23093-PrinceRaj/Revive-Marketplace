<?php
session_start();
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check login FIRST before anything else
    if (!isset($_SESSION['user_id'])) {
        header("Location: ../login.php");
        exit();
    }

    // CSRF Protection
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        header("Location: ../profile.php?error=security_mismatch");
        exit();
    }

    $user_id = $_SESSION['user_id'];
    $name_input = trim($_POST['name'] ?? '');
    $email_input = trim($_POST['email'] ?? '');
    $password = $_POST['password'];

    if ($name_input === '' || !preg_match("/^[\p{L}][\p{L}\s.'-]*$/u", $name_input)) {
        header("Location: ../profile.php?error=invalid_name");
        exit();
    }

    if (!filter_var($email_input, FILTER_VALIDATE_EMAIL)) {
        header("Location: ../profile.php?error=invalid_email");
        exit();
    }

    $name = $conn->real_escape_string($name_input);
    $email = $conn->real_escape_string($email_input);

    // Handle Image Upload
    $image_query = "";
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION));
        $allowed_exts = ['jpg', 'jpeg', 'png', 'webp'];
        if (!in_array($ext, $allowed_exts)) {
            header("Location: ../profile.php?error=invalid_image_type");
            exit();
        }

        $new_filename = uniqid('user_') . '.' . $ext;
        $upload_dir = '../assets/img/users/';
        
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        
        if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $upload_dir . $new_filename)) {
            $image_query = ", profile_image = '$new_filename'";
        }
    }

    // Handle Password Update
    $password_query = "";
    if (!empty($password)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $password_query = ", password = '$hashed_password'";
    }

    // Check if email exists
    $check_email = $conn->query("SELECT id FROM users WHERE email = '$email' AND id != $user_id");
    if ($check_email->num_rows > 0) {
        header("Location: ../profile.php?error=email_exists");
        exit();
    }

    $sql = "UPDATE users SET name = '$name', email = '$email' $image_query $password_query WHERE id = $user_id";

    if ($conn->query($sql)) {
        $_SESSION['user_name'] = $name; // Update session name
        header("Location: ../profile.php?success=1");
    } else {
        header("Location: ../profile.php?error=update_failed");
    }
}
?>
