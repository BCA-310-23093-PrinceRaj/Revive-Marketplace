<?php
session_start();
require_once '../config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Login required']);
    exit();
}

$user_id = $_SESSION['user_id'];
$action = $_POST['action'] ?? $_GET['action'] ?? '';

// CSRF check on state-changing requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        echo json_encode(['error' => 'Security token mismatch']);
        exit();
    }
}

if ($action === 'toggle') {
    $product_id = (int)$_POST['product_id'];
    
    // Check if already in wishlist
    $check = $conn->query("SELECT id FROM wishlist WHERE user_id = $user_id AND product_id = $product_id");
    
    if ($check->num_rows > 0) {
        // Remove
        $conn->query("DELETE FROM wishlist WHERE user_id = $user_id AND product_id = $product_id");
        echo json_encode(['success' => true, 'status' => 'removed']);
    } else {
        // Add
        $conn->query("INSERT INTO wishlist (user_id, product_id) VALUES ($user_id, $product_id)");
        echo json_encode(['success' => true, 'status' => 'added']);
    }
} 

elseif ($action === 'get_count') {
    $count = $conn->query("SELECT COUNT(*) as count FROM wishlist WHERE user_id = $user_id")->fetch_assoc()['count'];
    echo json_encode(['count' => $count]);
}
?>
