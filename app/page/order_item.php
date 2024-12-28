<?php
require '../_base.php';
include '../_head.php';
$conn = new mysqli("localhost", "root", "", "gadgetwebdb");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

auth_member();
$member_id = $_member->member_id;



// Fetch all orders and their items for the member
$order_query = "
    SELECT 
        o.order_id, 
        o.order_date, 
        o.order_status, 
        o.total_order_price, 
        oi.gadget_id, 
        g.gadget_name, 
        g.gadget_price, 
        oi.item_quantity, 
        oi.order_price
    FROM `order` o
    JOIN order_item oi ON o.order_id = oi.order_id
    JOIN gadget g ON oi.gadget_id = g.gadget_id
    WHERE o.member_id = ?
    ORDER BY o.order_date DESC
";

$stmt = $conn->prepare($order_query);
$stmt->bind_param("s", $member_id);
$stmt->execute();
$order_result = $stmt->get_result();

$orders = [];
while ($row = $order_result->fetch_assoc()) {
    $orders[$row['order_id']]['order_info'] = [
        'order_date' => $row['order_date'],
        'order_status' => $row['order_status'],
        'total_order_price' => $row['total_order_price'], // Fetch final price
    ];
    $orders[$row['order_id']]['items'][] = $row;
}





?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders</title>
    <link rel="stylesheet" href="/css/order_item.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="/js/product.js" defer></script>
</head>
<body>
    <div class="orders-container">
        <h1 class="page-title">My Orders</h1>

        <?php if (count($orders) > 0): ?>
            <?php foreach ($orders as $order_id => $order): ?>
                <div class="order-card">
                    <div class="order-header">
                        <h3>Order ID: <span><?= $order_id ?></span></h3>
                        <p>Date: <?= $order['order_info']['order_date'] ?></p>
                        <p>Status: <span class="status <?= strtolower($order['order_info']['order_status']) ?>" id="status-<?= $order_id ?>">
                            <?= $order['order_info']['order_status'] ?>
                        </span></p>
                        <p><strong>Final Price:</strong> RM <?= number_format($order['order_info']['total_order_price'], 2) ?></p>
                    </div>
                    <table class="order-table">
                        <thead>
                            <tr>
                                <th>Gadget</th>
                                <th>Price</th>
                                <th>Quantity</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($order['items'] as $item): ?>
                                <tr>
                                    <td><?= $item['gadget_name'] ?></td>
                                    <td>RM <?= number_format($item['gadget_price'], 2) ?></td>
                                    <td><?= $item['item_quantity'] ?></td>
                                    <td>RM <?= number_format($item['order_price'], 2) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <div class="order-actions">
                        <?php if ($order['order_info']['order_status'] === 'PENDING'): ?>
                            <button class="btn cancel-order" data-order-id="<?= $order_id ?>">Cancel Order</button>
                        <?php elseif ($order['order_info']['order_status'] === 'DELIVERED'): ?>
                            <button class="btn mark-received" data-order-id="<?= $order_id ?>">Mark as Received</button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="no-orders">You have no orders yet.</p>
        <?php endif; ?>

        <a href="order_cart.php" class="btn back-to-cart">Back to Cart</a>
    </div>
</body>
</html>


<?php
include '../_foot.php';
$conn->close();
?>
