<?php
require '../_base.php';
$_title = 'My Orders';
include '../_head.php';

// Database connection
$conn = new mysqli("localhost", "root", "", "gadgetwebdb");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

auth_member();
$member_id = $_member->member_id;

// Query to fetch all order items for the logged-in member (without quantity)
$order_query = "
    SELECT oi.order_item_id, oi.gadget_id, g.gadget_name, oi.order_price, o.order_id, o.order_date, o.order_status
    FROM order_item oi
    JOIN gadget g ON oi.gadget_id = g.gadget_id
    JOIN `order` o ON oi.order_id = o.order_id
    WHERE oi.member_id = ?
    ORDER BY o.order_date DESC
";

$stmt = $conn->prepare($order_query);
$stmt->bind_param("s", $member_id); // Bind member_id as a string
$stmt->execute();
$order_result = $stmt->get_result();

$order_items = [];
while ($item = $order_result->fetch_assoc()) {
    $order_items[] = $item;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <h1>My Order Items</h1>

    <?php if (count($order_items) > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Gadget</th>
                    <th>Price</th>
                    <th>Total</th>
                    <th>Order Date</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($order_items as $item): ?>
                    <tr>
                        <td><?= htmlspecialchars($item['order_id']) ?></td>
                        <td><?= htmlspecialchars($item['gadget_name']) ?></td>
                        <td>RM <?= number_format($item['order_price'], 2) ?></td>
                        <td>RM <?= number_format($item['order_price'], 2) ?></td>
                        <td><?= htmlspecialchars($item['order_date']) ?></td>
                        <td><?= htmlspecialchars($item['order_status']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>You have no orders yet.</p>
    <?php endif; ?>

    <a href="order_cart.php">Back to Cart</a>

</body>
</html>

<?php
include '../_foot.php';
$conn->close();
?>


<?php include '../_foot.php'; ?>
