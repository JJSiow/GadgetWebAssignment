<?php
require '../_base.php';

// Fetch total quantity sold
$totalResult = $_db->query("
    SELECT SUM(oi.item_quantity) AS total_quantity_sold 
    FROM order_item oi         
    JOIN `order` o ON oi.order_id = o.order_id
    WHERE o.order_status = 'DELIVERED'
")->fetch(PDO::FETCH_ASSOC);

$totalQty = $totalResult['total_quantity_sold'];

// Fetch top 5 best-selling gadgets
$topSellingGadgets = $_db->query("
    SELECT g.gadget_name, SUM(oi.item_quantity) AS total_quantity_sold
    FROM order_item oi
    JOIN gadget g ON oi.gadget_id = g.gadget_id
    JOIN `order` o ON oi.order_id = o.order_id
    WHERE o.order_status = 'DELIVERED'
    GROUP BY oi.gadget_id
    ORDER BY total_quantity_sold DESC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

// Prepare data points for the chart
$dataPoints = [];
foreach ($topSellingGadgets as $gadget) {
    $percentageValue = ($gadget['total_quantity_sold'] / $totalQty) * 100;
    $dataPoints[] = [
        "label" => $gadget['gadget_name'],
        "y" => $percentageValue,
        "quantitySold" => $gadget['total_quantity_sold'] // Include quantity sold
    ];
}

header('Content-Type: application/json');
echo json_encode($dataPoints, JSON_NUMERIC_CHECK);
