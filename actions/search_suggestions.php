<?php
require_once '../config/db.php';

$query = trim($_GET['q'] ?? '');
$suggestions = [];

if (strlen($query) >= 2) {
    // Secure query using prepared statement
    $stmt = $conn->prepare("SELECT id, title, price, images, brand FROM products 
                            WHERE (title LIKE ? OR brand LIKE ?) 
                            AND status = 'available' 
                            LIMIT 5");
    $searchTerm = '%' . $query . '%';
    $stmt->bind_param("ss", $searchTerm, $searchTerm);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $suggestions[] = $row;
    }
}

echo json_encode($suggestions);
?>
