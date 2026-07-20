<?php

function normalize_report_date($value, $fallback)
{
    if (is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
        $date = DateTime::createFromFormat('Y-m-d', $value);
        if ($date && $date->format('Y-m-d') === $value) {
            return $value;
        }
    }

    return $fallback;
}

function get_financial_report($conn, $from = null, $to = null)
{
    $from = normalize_report_date($from, date('Y-01-01'));
    $to = normalize_report_date($to, date('Y-m-d'));

    if ($from > $to) {
        [$from, $to] = [$to, $from];
    }

    $from_sql = $conn->real_escape_string($from);
    $to_sql = $conn->real_escape_string($to);
    $date_filter = "DATE(o.created_at) BETWEEN '$from_sql' AND '$to_sql'";
    $refund_condition = "EXISTS (
        SELECT 1 FROM disputes d
        WHERE d.order_id = o.id AND d.status = 'resolved_refunded'
    )";
    $loss_condition = "(o.status = 'cancelled' OR $refund_condition)";

    $summary_sql = "SELECT
        COUNT(*) AS total_orders,
        SUM(CASE WHEN o.status = 'completed' THEN 1 ELSE 0 END) AS completed_orders,
        SUM(CASE WHEN $loss_condition THEN 1 ELSE 0 END) AS loss_orders,
        COALESCE(SUM(CASE WHEN o.status = 'completed' THEN o.amount ELSE 0 END), 0) AS gross_sales,
        COALESCE(SUM(CASE WHEN o.status = 'completed' THEN o.discount_applied ELSE 0 END), 0) AS discounts,
        COALESCE(SUM(CASE WHEN o.status = 'completed' THEN o.amount - o.discount_applied ELSE 0 END), 0) AS revenue,
        COALESCE(SUM(CASE WHEN $loss_condition THEN o.amount - o.discount_applied ELSE 0 END), 0) AS losses
        FROM orders o
        WHERE $date_filter";
    $summary = $conn->query($summary_sql)->fetch_assoc();

    foreach (['gross_sales', 'discounts', 'revenue', 'losses'] as $key) {
        $summary[$key] = (float)$summary[$key];
    }
    foreach (['total_orders', 'completed_orders', 'loss_orders'] as $key) {
        $summary[$key] = (int)$summary[$key];
    }
    $summary['net_result'] = $summary['revenue'] - $summary['losses'];
    $summary['average_order'] = $summary['completed_orders'] > 0
        ? $summary['revenue'] / $summary['completed_orders']
        : 0;

    $status_rows = [];
    $status_result = $conn->query("SELECT o.status, COUNT(*) AS order_count,
        COALESCE(SUM(o.amount - o.discount_applied), 0) AS total_value
        FROM orders o WHERE $date_filter GROUP BY o.status ORDER BY order_count DESC");
    while ($row = $status_result->fetch_assoc()) {
        $status_rows[] = $row;
    }

    $seller_rows = [];
    $seller_result = $conn->query("SELECT u.name AS seller_name,
        COUNT(*) AS completed_orders,
        COALESCE(SUM(o.amount - o.discount_applied), 0) AS revenue
        FROM orders o
        JOIN products p ON o.product_id = p.id
        JOIN users u ON p.seller_id = u.id
        WHERE $date_filter AND o.status = 'completed'
        GROUP BY u.id, u.name
        ORDER BY revenue DESC
        LIMIT 10");
    while ($row = $seller_result->fetch_assoc()) {
        $seller_rows[] = $row;
    }

    $transactions = [];
    $transaction_result = $conn->query("SELECT o.id, o.created_at, o.status, o.amount,
        o.discount_applied, u.name AS buyer_name, p.title AS product_title,
        seller.name AS seller_name,
        CASE WHEN $loss_condition THEN 1 ELSE 0 END AS is_loss
        FROM orders o
        JOIN users u ON o.buyer_id = u.id
        JOIN products p ON o.product_id = p.id
        JOIN users seller ON p.seller_id = seller.id
        WHERE $date_filter
        ORDER BY o.created_at DESC");
    while ($row = $transaction_result->fetch_assoc()) {
        $transactions[] = $row;
    }

    return [
        'from' => $from,
        'to' => $to,
        'summary' => $summary,
        'statuses' => $status_rows,
        'sellers' => $seller_rows,
        'transactions' => $transactions,
    ];
}

