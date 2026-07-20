<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

// CSRF Protection
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    echo json_encode(['error' => 'Security token mismatch']);
    exit();
}

$user_id = $_SESSION['user_id'];
$order_id = (int)($_POST['order_id'] ?? 0);
$role = $_POST['role'] ?? 'buyer'; // 'buyer' or 'seller'

if ($order_id <= 0) {
    echo json_encode(['error' => 'Invalid order ID']);
    exit();
}

// Fetch order details
if ($role === 'seller') {
    // Seller can cancel orders for their own products
    $order = $conn->query("SELECT o.*, p.title, p.seller_id FROM orders o JOIN products p ON o.product_id = p.id WHERE o.id = $order_id AND p.seller_id = $user_id")->fetch_assoc();
} else {
    // Buyer can cancel their own orders
    $order = $conn->query("SELECT o.*, p.title, p.seller_id FROM orders o JOIN products p ON o.product_id = p.id WHERE o.id = $order_id AND o.buyer_id = $user_id")->fetch_assoc();
}

if (!$order) {
    echo json_encode(['error' => 'Order not found or unauthorized']);
    exit();
}

// Only 'pending' orders can be cancelled
if ($order['status'] !== 'pending') {
    echo json_encode(['error' => 'Only pending orders can be cancelled. This order is already: ' . strtoupper($order['status'])]);
    exit();
}

// Cancel the order and free up the product
$conn->begin_transaction();
try {
    // Update order status to cancelled
    $conn->query("UPDATE orders SET status = 'cancelled' WHERE id = $order_id");

    // Make product available again
    $conn->query("UPDATE products SET status = 'available' WHERE id = " . (int)$order['product_id']);

    $prod_name = $order['title'];
    $seller_id = $order['seller_id'];
    $buyer_id = $order['buyer_id'];

    if ($role === 'buyer') {
        // Notify Seller about cancellation
        $msg_seller = "❌ Order for '$prod_name' has been cancelled by the buyer. The item is now listed again.";
        $stmt = $conn->prepare("INSERT INTO notifications (user_id, message, type) VALUES (?, ?, 'info')");
        $stmt->bind_param("is", $seller_id, $msg_seller);
        $stmt->execute();
    } else {
        // Notify Buyer about seller cancellation
        $msg_buyer = "⚠️ Your order for '$prod_name' was cancelled by the seller. If payment was made, it will be refunded.";
        $stmt = $conn->prepare("INSERT INTO notifications (user_id, message, type) VALUES (?, ?, 'info')");
        $stmt->bind_param("is", $buyer_id, $msg_buyer);
        $stmt->execute();
    }

    $conn->commit();
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['error' => 'Cancellation failed: ' . $e->getMessage()]);
}
?>
