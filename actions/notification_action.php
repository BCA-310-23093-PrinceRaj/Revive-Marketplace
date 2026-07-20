<?php
session_start();
require_once '../config/db.php';

$action = $_GET['action'] ?? '';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['notifications' => [], 'unread' => 0]);
    exit();
}

$user_id = $_SESSION['user_id'];

if ($action === 'get') {
    $res = $conn->query("SELECT *, created_at FROM notifications WHERE user_id = $user_id ORDER BY created_at DESC LIMIT 10");
    $notifications = [];
    $unread = 0;

    while ($row = $res->fetch_assoc()) {
        if ($row['is_read'] == 0) $unread++;
        
        // Simple time ago
        $time = strtotime($row['created_at']);
        $diff = time() - $time;
        if ($diff < 60) $ago = "Just now";
        elseif ($diff < 3600) $ago = floor($diff/60) . "m ago";
        elseif ($diff < 86400) $ago = floor($diff/3600) . "h ago";
        else $ago = date('M d', $time);

        $row['time_ago'] = $ago;
        $notifications[] = $row;
    }

    echo json_encode(['notifications' => $notifications, 'unread' => $unread]);
}

elseif ($action === 'mark_read') {
    $conn->query("UPDATE notifications SET is_read = 1 WHERE user_id = $user_id");
    echo json_encode(['success' => true]);
}

elseif ($action === 'clear_all') {
    $conn->query("DELETE FROM notifications WHERE user_id = $user_id");
    echo json_encode(['success' => true]);
}
?>
