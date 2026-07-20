<?php
session_start();
require_once '../config/db.php';

// Enable error reporting for debugging
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

if(!isset($_SESSION['user_id'])) exit(json_encode(['error' => 'Unauthorized']));

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF Protection
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        echo json_encode(['error' => 'Security token mismatch']);
        exit();
    }

    $address = mysqli_real_escape_string($conn, trim($_POST['address'] ?? ''));
    $phone_input = trim($_POST['phone'] ?? '');

    if (!preg_match('/^[0-9]{10}$/', $phone_input)) {
        echo json_encode(['error' => 'Please enter a valid 10-digit mobile number']);
        exit();
    }

    $phone = mysqli_real_escape_string($conn, $phone_input);
    
    // Get all items in cart and verify availability
    $cart_items = $conn->query("SELECT c.product_id, p.price, p.status FROM cart c JOIN products p ON c.product_id = p.id WHERE c.user_id = $user_id");
    
    $cart_items_list = [];
    if ($cart_items->num_rows > 0) {
        while ($item = $cart_items->fetch_assoc()) {
            if ($item['status'] !== 'available') {
                echo json_encode(['error' => 'Product is no longer available']);
                exit();
            }
            $cart_items_list[] = $item;
        }
    } else {
        echo json_encode(['error' => 'Cart is empty']);
        exit();
    }

    $conn->begin_transaction();
    try {
        $payment_method = $_POST['payment_method'] ?? 'cod';
        // Both COD and online start as 'pending' — seller must ship first
        $order_status = 'pending';
        $order_ids = [];
        
        $num_items = count($cart_items_list);

        for ($i = 0; $i < $num_items; $i++) {
            $item = $cart_items_list[$i];
            $pid = $item['product_id'];
            $amount = $item['price'];
            
            // Create Order
            $stmt_order = $conn->prepare("INSERT INTO orders (buyer_id, product_id, amount, shipping_address, phone, status) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt_order->bind_param("iidsss", $user_id, $pid, $amount, $address, $phone, $order_status);
            $stmt_order->execute();
            
            $order_ids[] = $conn->insert_id;

            // Mark product as SOLD immediately ONLY for COD
            // For online payment, product stays available until payment is confirmed
            if ($payment_method === 'cod') {
                $conn->query("UPDATE products SET status = 'sold' WHERE id = $pid");
            }

            // For ALL orders (COD + Online), notify both parties about new order
            $prod_info = $conn->query("SELECT seller_id, title FROM products WHERE id = $pid")->fetch_assoc();
            $seller_id = $prod_info['seller_id'];
            $prod_name = $prod_info['title'];

            // Notify Seller about new order
            $seller_msg = "🛍️ New order received for '$prod_name'! Please prepare and ship it. Check your dashboard.";
            $seller_msg_escaped = mysqli_real_escape_string($conn, $seller_msg);
            $conn->query("INSERT INTO notifications (user_id, message, type) VALUES ($seller_id, '$seller_msg_escaped', 'order')");

            // Notify Buyer about order confirmation
            $buyer_msg = "✅ Order confirmed! Your order for '$prod_name' has been placed. The seller will ship it soon.";
            $buyer_msg_escaped = mysqli_real_escape_string($conn, $buyer_msg);
            $conn->query("INSERT INTO notifications (user_id, message, type) VALUES ($user_id, '$buyer_msg_escaped', 'success')");
        }
        

        
        // Clear Cart
        $conn->query("DELETE FROM cart WHERE user_id = $user_id");
        
        $conn->commit();
        
        if ($payment_method === 'online') {
            $ids_str = implode(',', $order_ids);
            echo json_encode(['redirect' => "payment.php?order_ids=$ids_str"]);
        } else {
            echo json_encode(['success' => true]);
        }
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['error' => 'Order failed: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['error' => 'Invalid request method']);
}
?>
