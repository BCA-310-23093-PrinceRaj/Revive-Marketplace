<?php
session_start();
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['user_id'])) {
        header("Location: ../login.php");
        exit();
    }

    // CSRF Protection
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        header("Location: ../index.php?error=security_mismatch");
        exit();
    }

    $user_id = $_SESSION['user_id'];
    $product_id = (int)$_POST['product_id'];
    $rating = (int)$_POST['rating'];
    $comment = mysqli_real_escape_string($conn, $_POST['comment']);

    // Validation: Check if user actually bought this product
    $bought_check = $conn->query("SELECT id FROM orders WHERE buyer_id = $user_id AND product_id = $product_id AND status IN ('delivered', 'completed')");
    if ($bought_check->num_rows === 0) {
        header("Location: ../product_details.php?id=$product_id&error=not_purchased");
        exit();
    }

    $sql = "INSERT INTO reviews (product_id, user_id, rating, comment) VALUES ($product_id, $user_id, $rating, '$comment')";
    
    if ($conn->query($sql)) {
        // Notify Seller
        $prod = $conn->query("SELECT seller_id, title FROM products WHERE id = $product_id")->fetch_assoc();
        $seller_id = $prod['seller_id'];
        $buyer_name = $_SESSION['user_name'];
        $msg = "$buyer_name left a review on your product '" . $prod['title'] . "'.";
        $msg_escaped = mysqli_real_escape_string($conn, $msg);
        $conn->query("INSERT INTO notifications (user_id, message, type) VALUES ($seller_id, '$msg_escaped', 'info')");

        header("Location: ../product_details.php?id=$product_id&success=review_added");
    } else {
        header("Location: ../product_details.php?id=$product_id&error=failed");
    }
}
?>
