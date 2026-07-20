<?php
session_start();
require_once '../config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Login required']);
    exit();
}

$user_id = (int)$_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF Protection
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        echo json_encode(['success' => false, 'error' => 'Security token mismatch']);
        exit();
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'create_dispute') {
        $order_id = (int)$_POST['order_id'];
        $reason = $conn->real_escape_string($_POST['reason'] ?? '');
        $details = $conn->real_escape_string($_POST['details'] ?? '');

        if (empty($reason) || empty($details)) {
            echo json_encode(['success' => false, 'error' => 'Reason and explanation details are required']);
            exit();
        }

        // Verify order exists, belongs to user, and is eligible
        $order_query = $conn->query("SELECT created_at, status FROM orders WHERE id = $order_id AND buyer_id = $user_id");
        if ($order_query->num_rows === 0) {
            echo json_encode(['success' => false, 'error' => 'Order not found or access denied']);
            exit();
        }

        $order = $order_query->fetch_assoc();

        // Only allow dispute on completed/delivered orders
        if (!in_array($order['status'], ['completed', 'delivered'])) {
            echo json_encode(['success' => false, 'error' => 'Disputes can only be filed on delivered or completed orders.']);
            exit();
        }

        // Dispute check
        $dispute_check = $conn->query("SELECT id FROM disputes WHERE order_id = $order_id");
        if ($dispute_check->num_rows > 0) {
            echo json_encode(['success' => false, 'error' => 'Dispute already filed for this order']);
            exit();
        }

        // Check 48-hour limit from DELIVERY time (updated_at), not order creation time
        $delivery_time = strtotime($order['updated_at'] ?? $order['created_at']);
        if ($delivery_time < strtotime('-48 hours')) {
            echo json_encode(['success' => false, 'error' => 'Dispute window (48 hours from delivery) has expired for this order.']);
            exit();
        }

        // Insert dispute
        $sql = "INSERT INTO disputes (order_id, buyer_id, reason, details) VALUES ($order_id, $user_id, '$reason', '$details')";
        if ($conn->query($sql)) {
            // Notify Seller
            $prod_info = $conn->query("SELECT p.seller_id, p.title FROM orders o JOIN products p ON o.product_id = p.id WHERE o.id = $order_id")->fetch_assoc();
            $seller_id = $prod_info['seller_id'];
            $prod_title = $prod_info['title'];
            $seller_msg = "Buyer has filed a dispute on your product '$prod_title'. Admins are reviewing it.";
            $stmt_seller = $conn->prepare("INSERT INTO notifications (user_id, message, type) VALUES (?, ?, 'info')");
            $stmt_seller->bind_param("is", $seller_id, $seller_msg);
            $stmt_seller->execute();

            // Notify Buyer
            $buyer_msg = "Your dispute on order #REV-$order_id has been submitted and is under admin review.";
            $stmt_buyer = $conn->prepare("INSERT INTO notifications (user_id, message, type) VALUES (?, ?, 'info')");
            $stmt_buyer->bind_param("is", $user_id, $buyer_msg);
            $stmt_buyer->execute();

            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Database error. Failed to file dispute']);
        }
        exit();
    }

    elseif ($action === 'update_dispute') {
        // Enforce Admin access
        if ($_SESSION['user_role'] !== 'admin') {
            header("Location: ../index.php");
            exit();
        }

        $dispute_id = (int)$_POST['dispute_id'];
        $status = $conn->real_escape_string($_POST['status'] ?? 'open');

        $valid_statuses = ['open', 'under_review', 'resolved_refunded', 'rejected'];
        if (!in_array($status, $valid_statuses)) {
            header("Location: ../admin_dashboard.php?section=disputes&error=invalid_status");
            exit();
        }

        $sql = "UPDATE disputes SET status = '$status' WHERE id = $dispute_id";
        if ($conn->query($sql)) {
            // Fetch dispute order information
            $disp_query = $conn->query("SELECT buyer_id, order_id FROM disputes WHERE id = $dispute_id");
            if ($disp_query->num_rows > 0) {
                $disp = $disp_query->fetch_assoc();
                $buyer_id = $disp['buyer_id'];
                $order_id = $disp['order_id'];

                // Notify Buyer
                $buyer_msg = "The status of your dispute on order #REV-$order_id has been updated to: " . strtoupper(str_replace('_', ' ', $status));
                $stmt_buyer = $conn->prepare("INSERT INTO notifications (user_id, message, type) VALUES (?, ?, 'info')");
                $stmt_buyer->bind_param("is", $buyer_id, $buyer_msg);
                $stmt_buyer->execute();

                // Notify Seller
                $prod_info = $conn->query("SELECT p.seller_id, p.title FROM orders o JOIN products p ON o.product_id = p.id WHERE o.id = $order_id")->fetch_assoc();
                $seller_id = $prod_info['seller_id'];
                $prod_title = $prod_info['title'];
                $seller_msg = "The status of the dispute on product '$prod_title' has been updated to: " . strtoupper(str_replace('_', ' ', $status));
                $stmt_seller = $conn->prepare("INSERT INTO notifications (user_id, message, type) VALUES (?, ?, 'info')");
                $stmt_seller->bind_param("is", $seller_id, $seller_msg);
                $stmt_seller->execute();
            }

            header("Location: ../admin_dashboard.php?section=disputes&success=status_updated");
        } else {
            header("Location: ../admin_dashboard.php?section=disputes&error=update_failed");
        }
        exit();
    }
}
?>
