<?php
session_start();
require_once '../config/db.php';

header('Content-Type: application/json');

// Auth check
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

// CSRF check
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    echo json_encode(['error' => 'Security token mismatch']);
    exit();
}

$user_id = (int)$_SESSION['user_id'];
$password = $_POST['password'] ?? '';

if (empty($password)) {
    echo json_encode(['error' => 'Password is required to delete your account.']);
    exit();
}

// Verify password
$user = $conn->query("SELECT password FROM users WHERE id = $user_id")->fetch_assoc();
if (!$user || !password_verify($password, $user['password'])) {
    echo json_encode(['error' => 'Incorrect password. Account not deleted.']);
    exit();
}

// Delete all user data in correct order (foreign key safe)
$conn->begin_transaction();
try {
    // 1. Delete disputes linked to user's orders
    $conn->query("DELETE d FROM disputes d JOIN orders o ON d.order_id = o.id WHERE o.buyer_id = $user_id");

    // 2. Delete orders (as buyer)
    $conn->query("DELETE FROM orders WHERE buyer_id = $user_id");

    // 3. Delete product images for seller's products
    $prod_res = $conn->query("SELECT id FROM products WHERE seller_id = $user_id");
    if ($prod_res) {
        while ($prod = $prod_res->fetch_assoc()) {
            $pid = $prod['id'];
            // Delete image files from disk
            $img_res = $conn->query("SELECT image_path FROM product_images WHERE product_id = $pid");
            if ($img_res) {
                while ($img = $img_res->fetch_assoc()) {
                    $file_path = '../assets/img/products/' . $img['image_path'];
                    if (file_exists($file_path)) @unlink($file_path);
                }
            }
            $conn->query("DELETE FROM product_images WHERE product_id = $pid");
        }
    }

    // 4. Delete seller's products
    $conn->query("DELETE FROM products WHERE seller_id = $user_id");

    // 5. Delete wishlist
    $conn->query("DELETE FROM wishlist WHERE user_id = $user_id");

    // 6. Delete cart
    $conn->query("DELETE FROM cart WHERE user_id = $user_id");

    // 7. Delete chats
    $conn->query("DELETE FROM chats WHERE sender_id = $user_id OR receiver_id = $user_id");

    // 8. Delete notifications
    $conn->query("DELETE FROM notifications WHERE user_id = $user_id");

    // 9. Delete profile image from disk
    $user_full = $conn->query("SELECT profile_image FROM users WHERE id = $user_id")->fetch_assoc();
    if (!empty($user_full['profile_image'])) {
        $img_path = '../assets/img/users/' . $user_full['profile_image'];
        if (file_exists($img_path)) @unlink($img_path);
    }

    // 10. Delete OTP records
    $conn->query("DELETE FROM otps WHERE email = (SELECT email FROM users WHERE id = $user_id)");

    // 11. Finally delete the user
    $conn->query("DELETE FROM users WHERE id = $user_id");

    $conn->commit();

    // Destroy session
    session_unset();
    session_destroy();

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['error' => 'Deletion failed: ' . $e->getMessage()]);
}
?>
