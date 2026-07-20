<?php
session_start();
require_once '../config/db.php';

// Security check: Only admins allowed
if(!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    exit("Unauthorized");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF Protection
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        header("Location: ../admin_dashboard.php?error=security_mismatch");
        exit();
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'approve_product') {
        $product_id = (int)$_POST['product_id'];
        $sql = "UPDATE products SET status = 'available' WHERE id = $product_id";
        if ($conn->query($sql)) {
            // Notify Seller
            $prod = $conn->query("SELECT seller_id, title FROM products WHERE id = $product_id")->fetch_assoc();
            $seller_id = $prod['seller_id'];
            $msg = "Your product '" . $prod['title'] . "' has been approved and is now live!";
            $stmt_notif = $conn->prepare("INSERT INTO notifications (user_id, message, type) VALUES (?, ?, 'success')");
            $stmt_notif->bind_param("is", $seller_id, $msg);
            $stmt_notif->execute();
            
            header("Location: ../admin_dashboard.php?success=approved");
        } else {
            header("Location: ../admin_dashboard.php?error=failed");
        }
    } 
    
    elseif ($action === 'reject_product' || $action === 'delete_product') {
        $product_id = (int)$_POST['product_id'];

        // Fetch seller info BEFORE deleting (so we can notify them)
        $prod_info = $conn->query("SELECT seller_id, title, status FROM products WHERE id = $product_id")->fetch_assoc();

        // Delete images from disk to prevent storage leak
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
            // Notify seller only if it was a pending product rejection
            if ($prod_info && $prod_info['status'] === 'pending') {
                $seller_id = $prod_info['seller_id'];
                $prod_title = $prod_info['title'];
                $msg = "❌ Your product '$prod_title' was reviewed and unfortunately rejected by our team. Please check our guidelines and try re-listing.";
                $stmt_notif = $conn->prepare("INSERT INTO notifications (user_id, message, type) VALUES (?, ?, 'info')");
                $stmt_notif->bind_param("is", $seller_id, $msg);
                $stmt_notif->execute();
            }
            header("Location: ../admin_dashboard.php?success=removed");
        } else {
            header("Location: ../admin_dashboard.php?error=failed");
        }
    }

    elseif ($action === 'ban_user') {
        $target_user_id = (int)$_POST['user_id'];
        // Check current status
        $res = $conn->query("SELECT status FROM users WHERE id = $target_user_id")->fetch_assoc();
        $new_status = ($res['status'] === 'banned') ? 'active' : 'banned';
        
        $sql = "UPDATE users SET status = '$new_status' WHERE id = $target_user_id";
        if ($conn->query($sql)) {
            header("Location: ../admin_dashboard.php?success=status_updated");
        } else {
            header("Location: ../admin_dashboard.php?error=failed");
        }
    }

    elseif ($action === 'delete_review') {
        $review_id = (int)$_POST['review_id'];
        $sql = "DELETE FROM reviews WHERE id = $review_id";
        if ($conn->query($sql)) {
            header("Location: ../admin_dashboard.php?section=dashboard&success=review_deleted");
        } else {
            header("Location: ../admin_dashboard.php?section=dashboard&error=failed");
        }
    }

    elseif ($action === 'add_category') {
        $name = $conn->real_escape_string($_POST['name']);
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name), '-'));
        $sql = "INSERT INTO categories (name, slug) VALUES ('$name', '$slug')";
        if ($conn->query($sql)) {
            header("Location: ../admin_dashboard.php?section=categories&success=category_added");
        } else {
            header("Location: ../admin_dashboard.php?section=categories&error=failed");
        }
    }

    elseif ($action === 'delete_category') {
        $id = (int)$_POST['category_id'];
        $sql = "DELETE FROM categories WHERE id = $id";
        if ($conn->query($sql)) {
            header("Location: ../admin_dashboard.php?section=categories&success=category_removed");
        } else {
            header("Location: ../admin_dashboard.php?section=categories&error=failed");
        }
    }

    elseif ($action === 'delete_message') {
        $id = (int)$_POST['message_id'];
        $sql = "DELETE FROM messages WHERE id = $id";
        if ($conn->query($sql)) {
            header("Location: ../admin_dashboard.php?section=messages&success=message_removed");
        } else {
            header("Location: ../admin_dashboard.php?section=messages&error=failed");
        }
    }

    elseif ($action === 'update_order_status') {
        $id = (int)$_POST['order_id'];
        $status = $conn->real_escape_string($_POST['status']);
        $sql = "UPDATE orders SET status = '$status' WHERE id = $id";
        if ($conn->query($sql)) {
            // Notify Buyer about status change
            $order = $conn->query("SELECT o.buyer_id, p.title FROM orders o JOIN products p ON o.product_id = p.id WHERE o.id = $id")->fetch_assoc();
            $buyer_id = $order['buyer_id'];
            $title = $order['title'];
            $msg = "Your order for '$title' has been updated to: " . strtoupper($status);
            $stmt_notif = $conn->prepare("INSERT INTO notifications (user_id, message, type) VALUES (?, ?, 'info')");
            $stmt_notif->bind_param("is", $buyer_id, $msg);
            $stmt_notif->execute();

            header("Location: ../admin_dashboard.php?section=orders&success=status_updated");
        } else {
            header("Location: ../admin_dashboard.php?section=orders&error=failed");
        }
    }



    elseif ($action === 'update_shipping') {
        $order_id = (int)($_POST['order_id'] ?? 0);
        $carrier = mysqli_real_escape_string($conn, trim($_POST['carrier'] ?? ''));
        $tracking_number = mysqli_real_escape_string($conn, trim($_POST['tracking_number'] ?? ''));

        if (empty($carrier) || empty($tracking_number)) {
            header("Location: ../admin_dashboard.php?section=orders&error=empty_shipping_fields");
            exit();
        }

        // Update shipping details
        $sql = "UPDATE orders SET carrier = '$carrier', tracking_number = '$tracking_number' WHERE id = $order_id";
        if ($conn->query($sql)) {
            header("Location: ../admin_dashboard.php?section=orders&success=shipping_updated");
        } else {
            header("Location: ../admin_dashboard.php?section=orders&error=failed");
        }
    }
}
?>
