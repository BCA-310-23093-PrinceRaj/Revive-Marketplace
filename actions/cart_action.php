<?php
session_start();
require_once '../config/db.php';

if(!isset($_SESSION['user_id'])) exit(json_encode(['error' => 'Login required']));

// CSRF check on state-changing requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        exit(json_encode(['error' => 'Security token mismatch']));
    }
}

$user_id = $_SESSION['user_id'];
$action = $_REQUEST['action'] ?? '';

if ($action === 'add') {
    $product_id = (int)$_POST['product_id'];
    
    // Safety: Don't allow buying own products
    $product_check = $conn->query("SELECT seller_id FROM products WHERE id = $product_id")->fetch_assoc();
    if($product_check['seller_id'] == $user_id) {
        exit(json_encode(['error' => 'You cannot buy your own product']));
    }
    
    // Check if already in cart
    $check = $conn->query("SELECT id FROM cart WHERE user_id = $user_id AND product_id = $product_id");
    if($check->num_rows > 0) {
        exit(json_encode(['error' => 'This item is already in your bag!']));
    } else {
        $conn->query("INSERT INTO cart (user_id, product_id) VALUES ($user_id, $product_id)");
    }
    echo json_encode(['success' => true]);
} 

elseif ($action === 'get') {
    $result = $conn->query("SELECT c.id, c.quantity, p.title, p.price, p.images, p.status, p.id as product_id FROM cart c JOIN products p ON c.product_id = p.id WHERE c.user_id = $user_id");
    $items = [];
    $unavailable = [];
    $total = 0;
    while($row = $result->fetch_assoc()) {
        if ($row['status'] === 'available') {
            $items[] = $row;
            $total += $row['price'] * $row['quantity'];
        } else {
            // Auto-remove sold/unavailable items from cart silently
            $conn->query("DELETE FROM cart WHERE id = {$row['id']} AND user_id = $user_id");
            $unavailable[] = $row['title'];
        }
    }
    echo json_encode(['items' => $items, 'total' => $total, 'count' => count($items), 'removed' => $unavailable]);
}

elseif ($action === 'remove') {
    $cart_id = (int)$_POST['cart_id'];
    $conn->query("DELETE FROM cart WHERE id = $cart_id AND user_id = $user_id");
    echo json_encode(['success' => true]);
}
?>
