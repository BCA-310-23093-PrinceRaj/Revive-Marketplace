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
        header("Location: ../seller_dashboard.php?error=security_mismatch");
        exit();
    }

    $action = $_POST['action'] ?? '';
    $user_id = $_SESSION['user_id'];

    // Note: 'update_order_status' action has been removed to enforce the new workflow
    // Sellers must now use 'update_shipping' to advance an order's status.

    if ($action === 'delete_product') {
        $product_id = (int)$_POST['product_id'];
        
        // Verify seller owns this product
        $check = $conn->query("SELECT id FROM products WHERE id = $product_id AND seller_id = $user_id");
        
        if ($check->num_rows > 0) {
            // Delete images from disk
            $img_res = $conn->query("SELECT image_path FROM product_images WHERE product_id = $product_id");
            if ($img_res) {
                while ($img_row = $img_res->fetch_assoc()) {
                    $file_path = '../assets/img/products/' . $img_row['image_path'];
                    if (file_exists($file_path)) {
                        unlink($file_path);
                    }
                }
            }

            $sql = "DELETE FROM products WHERE id = $product_id";
            if ($conn->query($sql)) {
                header("Location: ../seller_dashboard.php?success=deleted");
            } else {
                header("Location: ../seller_dashboard.php?error=failed");
            }
        } else {
            header("Location: ../seller_dashboard.php?error=unauthorized");
        }
    }
    
    elseif ($action === 'approve_return') {
        $order_id = (int)$_POST['order_id'];
        
        // Verify seller owns this order and it has a pending return request
        $check = $conn->query("SELECT o.id, o.buyer_id, p.title FROM orders o JOIN products p ON o.product_id = p.id WHERE o.id = $order_id AND p.seller_id = $user_id AND o.return_status = 'requested'");
        if ($check->num_rows > 0) {
            $order_info = $check->fetch_assoc();
            
            $sql = "UPDATE orders SET return_status = 'approved', status = 'cancelled' WHERE id = $order_id";
            if ($conn->query($sql)) {
                // Notify Buyer
                $buyer_id = $order_info['buyer_id'];
                $msg = "Good news! Your return request for '" . $order_info['title'] . "' has been APPROVED. Please check your email for refund details.";
                $stmt_notif = $conn->prepare("INSERT INTO notifications (user_id, message, type) VALUES (?, ?, 'success')");
                $stmt_notif->bind_param("is", $buyer_id, $msg);
                $stmt_notif->execute();

                header("Location: ../seller_dashboard.php?success=return_approved");
            } else {
                header("Location: ../seller_dashboard.php?error=failed");
            }
        } else {
            header("Location: ../seller_dashboard.php?error=unauthorized");
        }
    }

    elseif ($action === 'reject_return') {
        $order_id = (int)$_POST['order_id'];
        
        // Verify seller owns this order and it has a pending return request
        $check = $conn->query("SELECT o.id, o.buyer_id, p.title FROM orders o JOIN products p ON o.product_id = p.id WHERE o.id = $order_id AND p.seller_id = $user_id AND o.return_status = 'requested'");
        if ($check->num_rows > 0) {
            $order_info = $check->fetch_assoc();
            
            $sql = "UPDATE orders SET return_status = 'rejected' WHERE id = $order_id";
            if ($conn->query($sql)) {
                // Notify Buyer
                $buyer_id = $order_info['buyer_id'];
                $msg = "Your return request for '" . $order_info['title'] . "' has been REJECTED by the seller.";
                $stmt_notif = $conn->prepare("INSERT INTO notifications (user_id, message, type) VALUES (?, ?, 'error')");
                $stmt_notif->bind_param("is", $buyer_id, $msg);
                $stmt_notif->execute();

                header("Location: ../seller_dashboard.php?success=return_rejected");
            } else {
                header("Location: ../seller_dashboard.php?error=failed");
            }
        } else {
            header("Location: ../seller_dashboard.php?error=unauthorized");
        }
    }

    elseif ($action === 'update_shipping') {
        $order_id = (int)$_POST['order_id'];
        $carrier = mysqli_real_escape_string($conn, trim($_POST['carrier'] ?? ''));
        $tracking_number = mysqli_real_escape_string($conn, trim($_POST['tracking_number'] ?? ''));

        if (empty($carrier) || empty($tracking_number)) {
            header("Location: ../seller_dashboard.php?error=empty_shipping_fields");
            exit();
        }

        // Verify seller owns this order/product
        $check = $conn->query("SELECT o.buyer_id, p.title FROM orders o JOIN products p ON o.product_id = p.id WHERE o.id = $order_id AND p.seller_id = $user_id");
        
        if ($check->num_rows > 0) {
            $order_info = $check->fetch_assoc();
            $buyer_id = $order_info['buyer_id'];
            $prod_title = $order_info['title'];

            // Update shipping details and advance status to 'shipped'
            $sql = "UPDATE orders SET status = 'shipped', carrier = '$carrier', tracking_number = '$tracking_number' WHERE id = $order_id";
            if ($conn->query($sql)) {
                // Notify Buyer
                $msg = "Good news! Your order for '$prod_title' has been shipped via $carrier. Tracking Number: $tracking_number.";
                $stmt_notif = $conn->prepare("INSERT INTO notifications (user_id, message, type) VALUES (?, ?, 'info')");
                $stmt_notif->bind_param("is", $buyer_id, $msg);
                $stmt_notif->execute();

                header("Location: ../seller_dashboard.php?success=shipping_updated");
            } else {
                header("Location: ../seller_dashboard.php?error=failed");
            }
        } else {
            header("Location: ../seller_dashboard.php?error=unauthorized");
        }
    }
    elseif ($action === 'edit_product') {
        header('Content-Type: application/json');
        $product_id = (int)($_POST['product_id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $price = (float)($_POST['price'] ?? 0);
        $description = trim($_POST['description'] ?? '');

        if (empty($title) || $price <= 0 || $price > 100000 || empty($description)) {
            echo json_encode(['error' => 'Invalid data. Check title, price, and description.']);
            exit();
        }

        // Verify seller owns this product
        $check = $conn->query("SELECT id FROM products WHERE id = $product_id AND seller_id = $user_id");
        if ($check->num_rows === 0) {
            echo json_encode(['error' => 'Unauthorized or product not found.']);
            exit();
        }

        $title_esc = mysqli_real_escape_string($conn, $title);
        $desc_esc = mysqli_real_escape_string($conn, $description);

        $sql = "UPDATE products SET title = '$title_esc', price = $price, description = '$desc_esc' WHERE id = $product_id AND seller_id = $user_id";
        if ($conn->query($sql)) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['error' => 'Database update failed.']);
        }
        exit();
    }
}
?>
