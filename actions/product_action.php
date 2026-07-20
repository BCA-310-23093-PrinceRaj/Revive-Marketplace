<?php
session_start();
require_once '../config/db.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF Protection
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['old_product_data'] = $_POST;
        header("Location: ../add_product.php?error=security_mismatch");
        exit();
    }

    $user_id = $_SESSION['user_id'];
    
    // Sanitize and trim
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = (float)($_POST['price'] ?? 0);
    $category_id = (int)($_POST['category_id'] ?? 0);
    $condition = trim($_POST['condition'] ?? '');
    $size = trim($_POST['size'] ?? '');
    $brand = trim($_POST['brand'] ?? '');
    $brand = preg_replace('/\s+/', ' ', $brand); // remove extra spaces
    $usage_info = trim($_POST['usage_info'] ?? '');

    // Define Error Redirect Function
    function redirectWithError($error_msg) {
        $_SESSION['old_product_data'] = $_POST;
        header("Location: ../add_product.php?error=" . urlencode($error_msg));
        exit();
    }

    // 1. Title Validation
    if (strlen($title) < 5 || strlen($title) > 100) {
        redirectWithError("Title must be between 5 and 100 characters");
    }
    if (!preg_match('/^[a-zA-Z0-9\s]+$/', $title)) {
        redirectWithError("Title can only contain letters, numbers, and spaces");
    }

    // 2. Description Validation
    if (strlen($description) < 20 || strlen($description) > 500) {
        redirectWithError("Description must be between 20 and 500 characters");
    }

    // Profanity Filter
    $bad_words = ['abuse1', 'abuse2', 'badword']; // Simplified for example, can be expanded
    $text_to_check = strtolower($title . ' ' . $description);
    foreach ($bad_words as $word) {
        if (strpos($text_to_check, $word) !== false) {
            redirectWithError("Inappropriate language detected. Please keep the community clean.");
        }
    }

    // 3. Price Validation
    if ($price <= 0 || $price > 100000) {
        redirectWithError("Price must be between ₹1 and ₹100,000");
    }

    // 4. Category Validation
    if ($category_id <= 0) {
        redirectWithError("Please select a valid category");
    }

    // 5. Condition Validation
    $allowed_conditions = ['New', 'Like New', 'Excellent', 'Good', 'Fair'];
    if (!in_array($condition, $allowed_conditions)) {
        redirectWithError("Invalid condition selected");
    }

    // 6. Size Validation
    $allowed_sizes = ['XS', 'S', 'M', 'L', 'XL', 'XXL'];
    if (!in_array($size, $allowed_sizes)) {
        redirectWithError("Invalid size selected");
    }

    // 7. Brand Validation
    if (strlen($brand) > 50) {
        redirectWithError("Brand name too long (max 50 chars)");
    }
    if (!empty($brand) && !preg_match('/^[a-zA-Z\s]+$/', $brand)) {
        redirectWithError("Brand name can only contain letters and spaces");
    }

    // 8. Usage History Validation
    if (strlen($usage_info) < 10 || strlen($usage_info) > 300) {
        redirectWithError("Usage details must be between 10 and 300 characters");
    }

    // 9. Images Validation
    if (empty($_FILES['product_images']['name'][0])) {
        redirectWithError("At least one image is required");
    }
    $files = $_FILES['product_images'];
    $file_count = count($files['name']);
    
    if ($file_count > 5) {
        redirectWithError("Maximum 5 images allowed");
    }

    // Secure string preparation for DB
    $safe_title = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
    $safe_desc = htmlspecialchars($description, ENT_QUOTES, 'UTF-8');
    $safe_brand = htmlspecialchars($brand, ENT_QUOTES, 'UTF-8');
    $safe_usage = htmlspecialchars($usage_info, ENT_QUOTES, 'UTF-8');
    
    $safe_title = mysqli_real_escape_string($conn, $safe_title);
    $safe_desc = mysqli_real_escape_string($conn, $safe_desc);
    $safe_brand = mysqli_real_escape_string($conn, $safe_brand);
    $safe_usage = mysqli_real_escape_string($conn, $safe_usage);

    $sql = "INSERT INTO products (seller_id, category_id, title, description, price, product_condition, size, brand, usage_info) 
            VALUES ($user_id, $category_id, '$safe_title', '$safe_desc', $price, '$condition', '$size', '$safe_brand', '$safe_usage')";

    if ($conn->query($sql)) {
        $product_id = $conn->insert_id;
        $upload_dir = '../assets/img/products/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
        
        $primary_image_set = false;
        
        for ($i = 0; $i < $file_count; $i++) {
            $tmp_name = $files['tmp_name'][$i];
            $file_size = $files['size'][$i];
            
            // 5MB Limit
            if ($file_size > 5242880) continue; 
            
            // MIME Type validation
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $tmp_name);
            finfo_close($finfo);
            
            $allowed_mimes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
            if (!array_key_exists($mime, $allowed_mimes)) continue;
            
            $ext = $allowed_mimes[$mime];
            $file_name = time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            $target_path = $upload_dir . $file_name;

            // Auto-Compression using GD (if available), fallback to direct copy
            $uploaded = false;
            if (extension_loaded('gd')) {
                if ($mime === 'image/jpeg') {
                    $img = imagecreatefromjpeg($tmp_name);
                    imagejpeg($img, $target_path, 75);
                    imagedestroy($img);
                    $uploaded = true;
                } elseif ($mime === 'image/png') {
                    $img = imagecreatefrompng($tmp_name);
                    imagepng($img, $target_path, 6);
                    imagedestroy($img);
                    $uploaded = true;
                } elseif ($mime === 'image/webp') {
                    $img = imagecreatefromwebp($tmp_name);
                    imagewebp($img, $target_path, 80);
                    imagedestroy($img);
                    $uploaded = true;
                }
            }
            // Fallback: if GD not available or image type unhandled, just move the file
            if (!$uploaded) {
                move_uploaded_file($tmp_name, $target_path);
            }

            if (file_exists($target_path)) {
                $is_primary = (!$primary_image_set) ? 1 : 0;
                $conn->query("INSERT INTO product_images (product_id, image_path, is_primary) VALUES ($product_id, '$file_name', $is_primary)");
                
                if($is_primary) {
                    $conn->query("UPDATE products SET images = '$file_name' WHERE id = $product_id");
                    $primary_image_set = true;
                }
            }
        }

        // Update user role to seller if they were just a buyer
        $conn->query("UPDATE users SET role = 'seller' WHERE id = $user_id AND role = 'buyer'");
        $_SESSION['user_role'] = 'seller';
        
        // Clear old data on success
        unset($_SESSION['old_product_data']);
        header("Location: ../add_product.php?success=listed");
    } else {
        redirectWithError("Database insertion failed");
    }
}
?>
