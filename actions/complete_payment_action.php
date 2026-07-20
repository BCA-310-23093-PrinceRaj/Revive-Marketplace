<?php
session_start();
require_once '../config/db.php';

// Enable error reporting for database debugging
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF Protection
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        header("Location: ../index.php?error=security_mismatch");
        exit();
    }

    $order_ids_str = $_POST['order_ids'] ?? '';
    if (empty($order_ids_str)) {
        header("Location: ../index.php?error=empty_orders");
        exit();
    }

    $order_ids = explode(',', $order_ids_str);

    // SECURITY CHECK: Verify all orders belong to this buyer and are currently pending
    foreach ($order_ids as $id) {
        $id = (int)$id;
        $stmt_check = $conn->prepare("SELECT buyer_id, status FROM orders WHERE id = ?");
        $stmt_check->bind_param("i", $id);
        $stmt_check->execute();
        $order_check = $stmt_check->get_result()->fetch_assoc();

        if (!$order_check) {
            header("Location: ../index.php?error=order_not_found");
            exit();
        }

        if ($order_check['buyer_id'] != $user_id) {
            header("Location: ../index.php?error=unauthorized_payment");
            exit();
        }

        if ($order_check['status'] !== 'pending') {
            header("Location: ../index.php?error=invalid_order_status");
            exit();
        }
    }

    // Begin Transaction to apply updates atomically
    $conn->begin_transaction();
    try {
        foreach ($order_ids as $id) {
            $id = (int)$id;
            
            // 1. Update Order Status to Completed
            $stmt_ord_update = $conn->prepare("UPDATE orders SET status = 'completed' WHERE id = ?");
            $stmt_ord_update->bind_param("i", $id);
            $stmt_ord_update->execute();
            
            // 2. Fetch product_id linked to the order
            $stmt_pid = $conn->prepare("SELECT product_id FROM orders WHERE id = ?");
            $stmt_pid->bind_param("i", $id);
            $stmt_pid->execute();
            $p_res = $stmt_pid->get_result()->fetch_assoc();
            
            if ($p_res) {
                $pid = $p_res['product_id'];
                
                // 3. Update Product Status to Sold
                $stmt_prod_update = $conn->prepare("UPDATE products SET status = 'sold' WHERE id = ?");
                $stmt_prod_update->bind_param("i", $pid);
                $stmt_prod_update->execute();
                
                // 4. Fetch details for notifications
                $stmt_info = $conn->prepare("SELECT seller_id, title FROM products WHERE id = ?");
                $stmt_info->bind_param("i", $pid);
                $stmt_info->execute();
                $prod_info = $stmt_info->get_result()->fetch_assoc();
                
                if ($prod_info) {
                    $seller_id = $prod_info['seller_id'];
                    $prod_name = $prod_info['title'];

                    // Notify Seller
                    $seller_msg = "Great news! Your item '$prod_name' has been purchased. Check your dashboard for details.";
                    $seller_msg_escaped = mysqli_real_escape_string($conn, $seller_msg);
                    $conn->query("INSERT INTO notifications (user_id, message, type) VALUES ($seller_id, '$seller_msg_escaped', 'order')");

                    // Notify Buyer
                    $buyer_msg = "Payment successful! You have purchased '$prod_name'.";
                    $buyer_msg_escaped = mysqli_real_escape_string($conn, $buyer_msg);
                    $conn->query("INSERT INTO notifications (user_id, message, type) VALUES ($user_id, '$buyer_msg_escaped', 'success')");
                }
            }
        }
        $conn->commit();
        header("Location: ../index.php?success=payment_completed");
    } catch (Exception $e) {
        $conn->rollback();
        header("Location: ../index.php?error=payment_failed");
    }
} else {
    header("Location: ../index.php");
}
?>
