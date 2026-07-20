<?php
session_start();
require_once 'config/db.php';
require_once 'includes/financial_report.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    http_response_code(403);
    exit('Access denied');
}

$report = get_financial_report($conn, $_GET['from'] ?? null, $_GET['to'] ?? null);
$summary = $report['summary'];

function pdf_text($text)
{
    $text = iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', (string)$text);
    return str_replace(['\\', '(', ')'], ['\\\\', '\(', '\)'], $text);
}

function pdf_money($value)
{
    return 'INR ' . number_format((float)$value, 2);
}

function wrap_pdf_line($text, $width = 96)
{
    return explode("\n", wordwrap((string)$text, $width, "\n", true));
}

$lines = [
    ['REVIVE FINANCIAL REPORT', 18],
    ['Period: ' . $report['from'] . ' to ' . $report['to'], 11],
    ['Generated: ' . date('Y-m-d H:i:s'), 9],
    ['', 9],
    ['SUMMARY', 13],
    ['Completed revenue: ' . pdf_money($summary['revenue']), 10],
    ['Gross completed sales: ' . pdf_money($summary['gross_sales']), 10],
    ['Discounts: ' . pdf_money($summary['discounts']), 10],
    ['Loss value: ' . pdf_money($summary['losses']), 10],
    ['Estimated profit/loss: ' . pdf_money($summary['net_result']), 10],
    ['Completed orders: ' . $summary['completed_orders'] . ' | Loss orders: ' . $summary['loss_orders'] . ' | All orders: ' . $summary['total_orders'], 10],
    ['Average completed order: ' . pdf_money($summary['average_order']), 10],
    ['', 9],
    ['DEFINITIONS', 13],
    ['Revenue is completed order amount minus discounts. Loss value is cancelled orders plus orders with resolved refunded disputes. Estimated profit/loss is revenue minus loss value; product costs and operating expenses are not stored by the system.', 9],
    ['', 9],
    ['ORDER STATUS BREAKDOWN', 13],
];

foreach ($report['statuses'] as $row) {
    $lines[] = [ucfirst($row['status']) . ': ' . $row['order_count'] . ' orders | ' . pdf_money($row['total_value']), 9];
}

$lines[] = ['', 9];
$lines[] = ['TOP SELLERS', 13];
foreach ($report['sellers'] as $row) {
    $lines[] = [$row['seller_name'] . ': ' . $row['completed_orders'] . ' completed | ' . pdf_money($row['revenue']), 9];
}

$lines[] = ['', 9];
$lines[] = ['TRANSACTIONS', 13];
foreach ($report['transactions'] as $row) {
    $net = (float)$row['amount'] - (float)$row['discount_applied'];
    $loss_label = $row['is_loss'] ? ' | LOSS' : '';
    $lines[] = [
        '#' . $row['id'] . ' | ' . date('Y-m-d', strtotime($row['created_at'])) . ' | ' .
        strtoupper($row['status']) . $loss_label . ' | ' . pdf_money($net) . ' | ' .
        $row['product_title'] . ' | Buyer: ' . $row['buyer_name'] . ' | Seller: ' . $row['seller_name'],
        8
    ];
}

$pages = [];
$current = [];
$used_height = 0;
foreach ($lines as [$line, $size]) {
    foreach (wrap_pdf_line($line, $size >= 13 ? 75 : 102) as $wrapped) {
        $height = $size + 5;
        if ($used_height + $height > 730) {
            $pages[] = $current;
            $current = [];
            $used_height = 0;
        }
        $current[] = [$wrapped, $size];
        $used_height += $height;
    }
}
if ($current) {
    $pages[] = $current;
}

$objects = [];
$objects[1] = '<< /Type /Catalog /Pages 2 0 R >>';
$page_ids = [];
$next_id = 4;
foreach ($pages as $index => $page_lines) {
    $page_id = $next_id++;
    $content_id = $next_id++;
    $page_ids[] = $page_id . ' 0 R';

    $content = "BT\n/F1 10 Tf\n50 790 Td\n";
    foreach ($page_lines as [$line, $size]) {
        $content .= "/F1 $size Tf\n(" . pdf_text($line) . ") Tj\n0 -" . ($size + 5) . " Td\n";
    }
    $content .= "ET";

    $objects[$page_id] = "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 842] /Resources << /Font << /F1 3 0 R >> >> /Contents $content_id 0 R >>";
    $objects[$content_id] = "<< /Length " . strlen($content) . " >>\nstream\n$content\nendstream";
}
$objects[2] = '<< /Type /Pages /Kids [' . implode(' ', $page_ids) . '] /Count ' . count($page_ids) . ' >>';
$objects[3] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>';
ksort($objects);

$pdf = "%PDF-1.4\n";
$offsets = [0];
foreach ($objects as $id => $object) {
    $offsets[$id] = strlen($pdf);
    $pdf .= "$id 0 obj\n$object\nendobj\n";
}
$xref = strlen($pdf);
$max_id = max(array_keys($objects));
$pdf .= "xref\n0 " . ($max_id + 1) . "\n0000000000 65535 f \n";
for ($id = 1; $id <= $max_id; $id++) {
    $pdf .= sprintf("%010d 00000 n \n", $offsets[$id]);
}
$pdf .= "trailer\n<< /Size " . ($max_id + 1) . " /Root 1 0 R >>\nstartxref\n$xref\n%%EOF";

header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="revive-financial-report-' . $report['from'] . '-to-' . $report['to'] . '.pdf"');
header('Content-Length: ' . strlen($pdf));
echo $pdf;
