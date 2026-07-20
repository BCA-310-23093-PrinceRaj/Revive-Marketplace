<?php
session_start();
require_once '../config/db.php';

// Security: Only admins can fetch analytics
if(!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Content-Type: application/json');
    exit(json_encode(['error' => 'Unauthorized']));
}

$action = $_GET['action'] ?? '';

if ($action === 'get_sales_chart') {
    // Last 7 days of sales
    $data = [];
    for ($i = 6; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $label = date('D', strtotime("-$i days"));
        
        $res = $conn->query("SELECT SUM(amount) as total FROM orders WHERE DATE(created_at) = '$date' AND status = 'completed'");
        $total = $res->fetch_assoc()['total'] ?? 0;
        
        $data['labels'][] = $label;
        $data['values'][] = (float)$total;
    }
    
    header('Content-Type: application/json');
    echo json_encode($data);
    exit();
}

if ($action === 'get_user_growth') {
    // New users per day for last 7 days
    $data = [];
    for ($i = 6; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $label = date('D', strtotime("-$i days"));
        
        $res = $conn->query("SELECT COUNT(*) as count FROM users WHERE DATE(created_at) = '$date'");
        $count = $res->fetch_assoc()['count'] ?? 0;
        
        $data['labels'][] = $label;
        $data['values'][] = (int)$count;
    }
    
    header('Content-Type: application/json');
    echo json_encode($data);
    exit();
}
?>
