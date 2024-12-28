<?php
require '../_base.php';

// Summary Query
$querySummary = "
    SELECT 
        COUNT(DISTINCT CASE WHEN order_count = 1 THEN member_id END) AS new_customers,
        COUNT(DISTINCT CASE WHEN order_count > 1 THEN member_id END) AS returning_customers
    FROM (
        SELECT member_id, COUNT(order_id) AS order_count
        FROM `order`
        GROUP BY member_id
    ) AS customer_orders;
";

$resultSummary = $_db->query($querySummary)->fetch(PDO::FETCH_ASSOC);

// Fetch monthly breakdown for new customers
$queryNewCustomers = "
    SELECT 
        DATE_FORMAT(o.order_date, '%d %b %Y') AS date, -- Fix the date format
        COUNT(DISTINCT o.member_id) AS new_customers
    FROM `order` o
    JOIN (
        SELECT member_id
        FROM `order`
        GROUP BY member_id
        HAVING COUNT(order_id) = 1
    ) AS new_members ON o.member_id = new_members.member_id
    GROUP BY DATE_FORMAT(o.order_date, '%Y-%m-%d') -- Group by day
";

$newCustomersData = $_db->query($queryNewCustomers)->fetchAll(PDO::FETCH_ASSOC);

// Fetch monthly breakdown for returning customers
$queryReturningCustomers = "
    SELECT 
        DATE_FORMAT(o.order_date, '%d %b %Y') AS date, -- Fix the date format
        COUNT(DISTINCT o.member_id) AS returning_customers
    FROM `order` o
    JOIN (
        SELECT member_id
        FROM `order`
        GROUP BY member_id
        HAVING COUNT(order_id) > 1
    ) AS returning_members ON o.member_id = returning_members.member_id
    GROUP BY DATE_FORMAT(o.order_date, '%Y-%m-%d') -- Group by day
";

$returningCustomersData = $_db->query($queryReturningCustomers)->fetchAll(PDO::FETCH_ASSOC);

// Prepare data to send to the frontend
$response = [
    "summary" => $resultSummary,
    "new_customers" => $newCustomersData,
    "returning_customers" => $returningCustomersData,
];

header('Content-Type: application/json');
echo json_encode($response);
