<?php
session_start();
require_once '../config/db.php';

if(!isset($_SESSION['user_id'])) exit(json_encode(['error' => 'Unauthorized']));

// CSRF check on state-changing requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        exit(json_encode(['error' => 'Security token mismatch']));
    }
}

$user_id = $_SESSION['user_id'];
$action = $_REQUEST['action'] ?? '';

if ($action === 'send_message') {
    $receiver_id = (int)$_POST['receiver_id'];
    $message = mysqli_real_escape_string($conn, $_POST['message']);

    // Prevent self-chatting
    if ($receiver_id === $user_id) {
        exit(json_encode(['error' => 'You cannot chat with yourself.']));
    }

    $image_path = "NULL";
    if (isset($_FILES['chat_image']) && $_FILES['chat_image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../assets/img/chats/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

        // File size limit: 5MB
        if ($_FILES['chat_image']['size'] > 5 * 1024 * 1024) {
            exit(json_encode(['error' => 'Image too large. Max 5MB allowed.']));
        }

        // Validate MIME type (not just extension)
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $_FILES['chat_image']['tmp_name']);
        finfo_close($finfo);
        $allowed_mimes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];

        if (!in_array($mime, $allowed_mimes)) {
            exit(json_encode(['error' => 'Invalid image type. Only JPG, PNG, WebP, GIF allowed.']));
        }

        // Generate a safe unique filename (no original filename used)
        $ext_map = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
        $ext = $ext_map[$mime];
        $file_name = 'chat_' . uniqid() . '.' . $ext;
        $target_path = $upload_dir . $file_name;

        if (move_uploaded_file($_FILES['chat_image']['tmp_name'], $target_path)) {
            $image_path = "'" . mysqli_real_escape_string($conn, $file_name) . "'";
        }
    }

    $product_id = isset($_POST['product_id']) && (int)$_POST['product_id'] > 0 ? (int)$_POST['product_id'] : "NULL";
    $conn->query("INSERT INTO chats (sender_id, receiver_id, product_id, message, image_path) VALUES ($user_id, $receiver_id, $product_id, '$message', $image_path)");
    
    // Add notification for receiver
    $sender_name_res = $conn->query("SELECT name FROM users WHERE id = $user_id")->fetch_assoc();
    $sender_name = $sender_name_res ? mysqli_real_escape_string($conn, $sender_name_res['name']) : 'A user';
    $notif_msg = "You have a new message from $sender_name.";
    $conn->query("INSERT INTO notifications (user_id, message, type) VALUES ($receiver_id, '$notif_msg', 'info')");

    echo json_encode(['success' => true]);
} 

elseif ($action === 'get_messages') {
    $receiver_id = (int)$_GET['receiver_id'];
    
    // Mark messages as read
    $conn->query("UPDATE chats SET is_read = 1 WHERE sender_id = $receiver_id AND receiver_id = $user_id AND is_read = 0");

    $result = $conn->query("
        SELECT c.*, p.title as p_title, p.price as p_price, p.images as p_images
        FROM chats c
        LEFT JOIN products p ON c.product_id = p.id
        WHERE (c.sender_id = $user_id AND c.receiver_id = $receiver_id) 
           OR (c.sender_id = $receiver_id AND c.receiver_id = $user_id) 
        ORDER BY c.created_at ASC
    ");
    
    $messages = [];
    while($row = $result->fetch_assoc()) {
        $row['time'] = date('H:i', strtotime($row['created_at']));
        $messages[] = $row;
    }
    echo json_encode($messages);
}

elseif ($action === 'get_contacts') {
    // Get distinct users the current user has chatted with
    $sql = "SELECT DISTINCT u.id, u.name, 
            (SELECT COUNT(*) FROM chats WHERE sender_id = u.id AND receiver_id = $user_id AND is_read = 0) as unread_count
            FROM users u 
            JOIN chats c ON (u.id = c.sender_id OR u.id = c.receiver_id)
            WHERE (c.sender_id = $user_id OR c.receiver_id = $user_id) AND u.id != $user_id";
    
    $result = $conn->query($sql);
    $contacts = [];
    while($row = $result->fetch_assoc()) {
        // Get last message for each contact
        $last_msg_res = $conn->query("SELECT message, image_path FROM chats WHERE (sender_id = $user_id AND receiver_id = {$row['id']}) OR (sender_id = {$row['id']} AND receiver_id = $user_id) ORDER BY created_at DESC LIMIT 1");
        if ($last_msg_res->num_rows > 0) {
            $last_msg = $last_msg_res->fetch_assoc();
            $row['last_message'] = $last_msg['message'] ? $last_msg['message'] : '📷 Image';
        } else {
            $row['last_message'] = '';
        }
        $contacts[] = $row;
    }
    echo json_encode($contacts);
}
?>
