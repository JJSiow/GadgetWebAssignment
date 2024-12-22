<?php
require '../_base.php';

// Define fields for table headers
$fields = [
    'order_id' => 'Order ID',
    'member_id' => 'Member ID',
    'gadget_name_price' => 'Gadget Name and Price',
    'total_order_price' => 'Order Price',
    'order_status' => 'Order Status',
    'action' => 'Action',
];

// Fetch data from the database as an associative array
$orders = $_db->query(
    "SELECT o.order_id, o.order_status, 
            i.member_id, 
            g.gadget_name, 
            i.order_price, 
            o.total_order_price
     FROM `order` o
     JOIN `order_item` i ON o.order_id = i.order_id
     JOIN `gadget` g ON i.gadget_id = g.gadget_id
     WHERE o.order_status = 'pending'
     ORDER BY o.order_id"
)->fetchAll(PDO::FETCH_ASSOC);

// Group orders by Order ID
$groupedOrders = [];
foreach ($orders as $order) {
    $orderId = $order['order_id'];
    if (!isset($groupedOrders[$orderId])) {
        $groupedOrders[$orderId] = [
            'order_id' => $order['order_id'],
            'member_id' => $order['member_id'],
            'order_status' => $order['order_status'],
            'total_order_price' => $order['total_order_price'], // Use pre-calculated total price
            'gadget_details' => []
        ];
    }
    $groupedOrders[$orderId]['gadget_details'][] = [
        'gadget_name' => $order['gadget_name'],
        'order_price' => $order['order_price']
    ];
}

if (is_post()) {
    $orderId = req('order_id');

    $stm = $_db->prepare("UPDATE `order` SET order_status = 'delivered' WHERE order_id = ?");

    $stm->execute([$orderId]);

    temp('info', "Order ID : $orderId updated successfuly");
    redirect('/page/admin_order.php');
}
$_title = 'Order';
include '../_head.php';
?>

<div>
    <button>Pending</button>
    <button>Delivered</button>
    <button>Received</button>
</div>

<table class="table">
    <thead>
        <tr>
            <?= table_headers($fields) ?>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($orders)): ?>
            <tr>
                <td colspan="8">No order pending records found...</td>
            </tr>
        <?php else: ?>
            <?php foreach ($groupedOrders as $group): ?>
                <tr>
                    <!-- Order Details -->
                    <td rowspan="<?= count($group['gadget_details']) ?>"><?= htmlspecialchars($group['order_id']) ?></td>
                    <td rowspan="<?= count($group['gadget_details']) ?>"><?= htmlspecialchars($group['member_id']) ?></td>

                    <!-- First Gadget Details -->
                    <?php $firstGadget = array_shift($group['gadget_details']); ?>
                    <td><?= htmlspecialchars($firstGadget['gadget_name']) ?> - RM <?= htmlspecialchars($firstGadget['order_price']) ?></td>
                    <td rowspan="<?= count($group['gadget_details']) + 1 ?>">RM <?= htmlspecialchars($group['total_order_price']) ?></td>
                    <td rowspan="<?= count($group['gadget_details']) + 1 ?>"><?= htmlspecialchars($group['order_status']) ?></td>
                    <td rowspan="<?= count($group['gadget_details']) + 1 ?>">
                        <form method="POST">
                            <input type="hidden" name="order_id" value="<?= htmlspecialchars($group['order_id']) ?>">
                            <button type="submit" class="btn btn-primary">Mark as Delivered</button>
                        </form>
                    </td>
                </tr>
                <!-- Additional Gadget Details -->
                <?php foreach ($group['gadget_details'] as $gadget): ?>
                    <tr>
                        <td><?= htmlspecialchars($gadget['gadget_name']) ?> - <?= htmlspecialchars($gadget['order_price']) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>

<?php include '../_foot.php'; ?>