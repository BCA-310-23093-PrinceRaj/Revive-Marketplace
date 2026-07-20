<?php
session_start();
require_once '../config/db.php';

// Security check: Logged in users only
if(!isset($_SESSION['user_id'])) {
    exit("Unauthorized");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF Protection
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        header("Location: ../my_orders.php?error=security_mismatch");
        exit();
    }

    $action = $_POST['action'] ?? '';
    $user_id = $_SESSION['user_id'];

    if ($action === 'mark_completed') {
        $order_id = (int)$_POST['order_id'];

        // Verify buyer owns this order and it is currently shipped or delivered
        $check = $conn->query("SELECT o.id, p.seller_id, p.title FROM orders o JOIN products p ON o.product_id = p.id WHERE o.id = $order_id AND o.buyer_id = $user_id AND (o.status = 'shipped' OR o.status = 'delivered')");
        
        if ($check->num_rows > 0) {
            $order_info = $check->fetch_assoc();
            
            // Advance status to 'completed'
            $sql = "UPDATE orders SET status = 'completed' WHERE id = $order_id";
            if ($conn->query($sql)) {
                // Notify Seller that buyer received the order
                $seller_id = $order_info['seller_id'];
                $msg = "Buyer has received '" . $order_info['title'] . "' and the order is now COMPLETED.";
                $stmt_notif = $conn->prepare("INSERT INTO notifications (user_id, message, type) VALUES (?, ?, 'success')");
                $stmt_notif->bind_param("is", $seller_id, $msg);
                $stmt_notif->execute();

                header("Location: ../my_orders.php?success=order_completed");
            } else {
                header("Location: ../my_orders.php?error=failed");
            }
        } else {
            header("Location: ../my_orders.php?error=unauthorized_or_invalid_status");
        }
    }
    
    elseif ($action === 'request_return') {
        $order_id = (int)$_POST['order_id'];
        $reason = mysqli_real_escape_string($conn, trim($_POST['return_reason'] ?? ''));

        if (empty($reason)) {
            header("Location: ../my_orders.php?error=empty_reason");
            exit();
        }

        // Verify buyer owns this order, it's completed, and no return is currently active
        $check = $conn->query("SELECT o.id, p.seller_id, p.title FROM orders o JOIN products p ON o.product_id = p.id WHERE o.id = $order_id AND o.buyer_id = $user_id AND o.status = 'completed' AND o.return_status = 'none'");
        
        if ($check->num_rows > 0) {
            $order_info = $check->fetch_assoc();
            
            $sql = "UPDATE orders SET return_status = 'requested', return_reason = '$reason' WHERE id = $order_id";
            if ($conn->query($sql)) {
                // Notify Seller
                $seller_id = $order_info['seller_id'];
                $msg = "Buyer has requested a return for '" . $order_info['title'] . "'. Please review it in your dashboard.";
                $stmt_notif = $conn->prepare("INSERT INTO notifications (user_id, message, type) VALUES (?, ?, 'warning')");
                $stmt_notif->bind_param("is", $seller_id, $msg);
                $stmt_notif->execute();

                header("Location: ../my_orders.php?success=return_requested");
            } else {
                header("Location: ../my_orders.php?error=failed");
            }
        } else {
            header("Location: ../my_orders.php?error=unauthorized_or_invalid_status");
        }
    }
}
?>
