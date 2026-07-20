<?php
session_start();
require_once '../config/db.php';

header('Content-Type: application/json');

// CSRF Protection
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    echo json_encode(['success' => false, 'error' => 'Security token mismatch.']);
    exit();
}

$email = $_POST['email'] ?? '';

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'error' => 'Invalid email address.']);
    exit();
}

// Check if already subscribed
$stmt = $conn->prepare("SELECT id FROM subscribers WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows > 0) {
    echo json_encode(['success' => false, 'error' => 'You are already subscribed!']);
    exit();
}
$stmt->close();

// Insert new subscriber
$stmt = $conn->prepare("INSERT INTO subscribers (email) VALUES (?)");
$stmt->bind_param("s", $email);
if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to subscribe. Please try again.']);
}
$stmt->close();
?>
