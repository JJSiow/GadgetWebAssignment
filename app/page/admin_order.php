<?php
require '../_base.php';

// Define fields for table headers
$fields = [
    'order_id' => 'Order ID',
    'member_id' => 'Member ID',
    'gadget_name_price' => 'Gadget Purchased',
    'total_order_price' => 'Order Price',
    'action' => 'Action',
];

// Initialize search parameters from session or defaults
$searchParams = $_SESSION['order_search_params'] ?? [
    'soid' => '',
    'smid' => '',
    'sgadget' => '',
    'sprice' => '',
    'sort' => 'order_id',
    'dir' => 'asc',
    'page' => 1
];

// Clear search if requested
if (isset($_GET['clear_search'])) {
    unset($_SESSION['order_search_params']);
    $searchParams = [
        'soid' => '',
        'smid' => '',
        'sgadget' => '',
        'sprice' => '',
        'sort' => 'order_id',
        'dir' => 'asc',
        'page' => 1
    ];
}

// Handle sorting
$sort = req('sort');
$sort = key_exists($sort, $fields) ? $sort : $searchParams['sort'];

$dir = req('dir');
$dir = in_array($dir, ['asc', 'desc']) ? $dir : $searchParams['dir'];

// Handle pagination - Reset to page 1 for new searches
$page = is_post() ? 1 : (int)req('page', $searchParams['page']);

// Update search parameters
if (is_post()) {
    $searchParams = [
        'soid' => req('soid', ''),
        'smid' => req('smid', ''),
        'sgadget' => req('sgadget', ''),
        'sprice' => req('sprice', ''),
        'sort' => $sort,
        'dir' => $dir,
        'page' => 1
    ];
} else {
    $searchParams['sort'] = $sort;
    $searchParams['dir'] = $dir;
    $searchParams['page'] = $page;
}

// Save to session
$_SESSION['order_search_params'] = $searchParams;

function buildOrderQuery($searchParams)
{
    $conditions = ["o.order_status = 'pending'"];
    $params = [];

    // Use subquery to get correct count
    $baseQuery = "SELECT * FROM (
        SELECT DISTINCT o.order_id, o.order_status, 
            i.member_id, 
            GROUP_CONCAT(g.gadget_name) as gadget_names,
            GROUP_CONCAT(i.order_price) as order_prices,
            GROUP_CONCAT(i.gadget_id) as gadget_ids,
            o.total_order_price
        FROM `order` o
        JOIN `order_item` i ON o.order_id = i.order_id
        JOIN `gadget` g ON i.gadget_id = g.gadget_id";

    if ($searchParams['soid']) {
        $conditions[] = "o.order_id LIKE ?";
        $params[] = "%{$searchParams['soid']}%";
    }

    if ($searchParams['smid']) {
        $conditions[] = "i.member_id LIKE ?";
        $params[] = "%{$searchParams['smid']}%";
    }

    if ($searchParams['sgadget']) {
        $conditions[] = "o.order_id IN (
            SELECT DISTINCT oi.order_id 
            FROM order_item oi 
            JOIN gadget g2 ON oi.gadget_id = g2.gadget_id 
            WHERE g2.gadget_name LIKE ?
        )";
        $params[] = "%{$searchParams['sgadget']}%";
    }

    if ($searchParams['sprice']) {
        $conditions[] = "ROUND(o.total_order_price, 2) = ROUND(?, 2)";
        $params[] = $searchParams['sprice'];
    }

    $baseQuery .= " WHERE " . implode(" AND ", $conditions);
    $baseQuery .= " GROUP BY o.order_id) as subquery";

    // Handle sorting
    $sortField = $searchParams['sort'];
    if ($sortField === 'gadget_name_price') {
        $sortField = 'gadget_names';
    }
    $baseQuery .= " ORDER BY {$sortField} {$searchParams['dir']}";

    return [$baseQuery, $params];
}

// Get the query and parameters
[$query, $params] = buildOrderQuery($searchParams);

// Create SimplePager with the appropriate query
require_once '../lib/SimplePager2.php';
$p = new SimplePager2(
    $query,
    $params,
    10,
    $searchParams['page'],
    true
);

$orders = $p->result;

// if (is_post() && isset($_POST['checkboxName'])) {
//     $orderIds = explode(',', req('checkboxName', ''));
//     $gadgetIds = explode(',', req('gadgetID', ''));

//     // Start transaction
//     $_db->beginTransaction();

//     try {
//         // First update the order status
//         if (!empty($orderIds)) {
//             $placeholders = implode(',', array_fill(0, count($orderIds), '?'));
//             $stm = $_db->prepare("UPDATE `order` SET order_status = 'delivered' WHERE order_id IN ($placeholders)");

//             if (!$stm->execute($orderIds)) {
//                 throw new Exception("Failed to update orders.");
//             }

//             // Now deduct stock for each gadget
//             if (!empty($gadgetIds)) {
//                 // Get order quantities for each gadget from order_item
//                 $gadgetPlaceholders = implode(',', array_fill(0, count($gadgetIds), '?'));
//                 $quantityStm = $_db->prepare("
//                     SELECT gadget_id, SUM(quantity) as total_quantity 
//                     FROM order_item 
//                     WHERE order_id IN ($placeholders) AND gadget_id IN ($gadgetPlaceholders)
//                     GROUP BY gadget_id
//                 ");

//                 // Combine orderIds and gadgetIds for the query
//                 $params = array_merge($orderIds, $gadgetIds);
//                 $quantityStm->execute($params);
//                 $quantities = $quantityStm->fetchAll(PDO::FETCH_ASSOC);

//                 // Update stock for each gadget
//                 $updateStockStm = $_db->prepare("
//                     UPDATE gadget 
//                     SET gadget_stock = gadget_stock - ? 
//                     WHERE gadget_id = ?
//                 ");

//                 foreach ($quantities as $quantity) {
//                     if (!$updateStockStm->execute([$quantity['total_quantity'], $quantity['gadget_id']])) {
//                         throw new Exception("Failed to update stock for gadget ID: " . $quantity['gadget_id']);
//                     }
//                 }
//             }

//             // If everything succeeded, commit the transaction
//             $_db->commit();
//             temp('info', "Orders updated successfully and stock deducted.");
//             header('Location: ' . $_SERVER['PHP_SELF']);
//             exit;
//         }
//     } catch (Exception $e) {
//         // If anything fails, rollback the transaction
//         $_db->rollBack();
//         temp('error', $e->getMessage());
//     }
// } else {
//     temp('error', "No orders selected for update.");
// }

if (is_post() && isset($_POST['checkboxName'])) {
    $orderIds = explode(',', req('checkboxName', ''));
    $gadgetIds = explode(',', req('gadgetID', ''));
    echo '<pre>';
    print_r($gadgetIds);
    echo '</pre>';

    if (!empty($orderIds)) {
        $placeholders = implode(',', array_fill(0, count($orderIds), '?'));
        $stm = $_db->prepare("UPDATE order SET order_status = 'delivered' WHERE order_id IN ($placeholders)");

        if ($stm->execute($orderIds)) {
            temp('info', "Orders updated successfully.");
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        } else {
            temp('error', "Failed to update orders.");
        }
    } else {
        temp('error', "No orders selected for update.");
    }
}

// Process the results
$processedOrders = [];
foreach ($orders as $order) {
    $gadgetIDs = explode(',', $order->gadget_ids ?? '');
    $gadgetNames = explode(',', $order->gadget_names);
    $orderPrices = explode(',', $order->order_prices);

    $gadgetDetails = [];
    for ($i = 0; $i < count($gadgetNames); $i++) {
        $gadgetDetails[] = [
            'gadget_name' => $gadgetNames[$i],
            'order_price' => $orderPrices[$i] ?? 0,
            'gadget_id' => $gadgetIDs[$i] ?? ''
        ];
    }

    $processedOrders[] = [
        'order_id' => $order->order_id,
        'member_id' => $order->member_id,
        'total_order_price' => $order->total_order_price,
        'order_status' => $order->order_status,
        'gadget_details' => $gadgetDetails
    ];
}

$_title = 'Order';
include '../_head.php';
?>

<div>
    <button class="active">Pending</button>
    <button>Delivered</button>
    <button>Received</button>
</div>

<form id="mark-all-form" method="post">
    <button id="submit-mark-all" class="btn btn-primary" style="display: none;">Mark All Delivered</button>
</form>

<table class="table">
    <thead>
        <tr>
            <th></th>
            <?= table_headers2($fields, $searchParams['sort'], $searchParams['dir'], "page={$searchParams['page']}") ?>
        </tr>
    </thead>
    <tbody>
        <form method="post">
            <tr>
                <td><input type="checkbox" id="check-all">All</td>
                <td><?= html_search2('soid', $searchParams['soid']) ?></td>
                <td><?= html_search2('smid', $searchParams['smid']) ?></td>
                <td><?= html_search2('sgadget', $searchParams['sgadget']) ?></td>
                <td><?= html_number('sprice', $searchParams['sprice'] ?? '', ['min' => '0.01', 'max' => '10000.00', 'step' => '0.01'], 'RM '); ?></td>
                <td>
                    <button type="submit">Search</button>
                    <a href="?clear_search=1" class="clear-search-btn">Clear Search</a>
                </td>
            </tr>
        </form>
        <?php if (empty($processedOrders)): ?>
            <tr>
                <td colspan="6">No pending orders found...</td>
            </tr>
        <?php else: ?>
            <?php foreach ($processedOrders as $order): ?>
                <tr>
                    <td rowspan="<?= count($order['gadget_details']) ?>">
                        <input type="checkbox"
                            name="id[]"
                            value="<?= htmlspecialchars($order['order_id']) ?>"
                            class="checkbox"
                            data-gadget-ids="<?= htmlspecialchars(implode(',', array_column($order['gadget_details'], 'gadget_id'))) ?>">
                    </td>
                    <td rowspan="<?= count($order['gadget_details']) ?>"><?= htmlspecialchars($order['order_id']) ?></td>
                    <td rowspan="<?= count($order['gadget_details']) ?>"><?= htmlspecialchars($order['member_id']) ?></td>

                    <?php $firstGadget = array_shift($order['gadget_details']); ?>
                    <td><?= htmlspecialchars($firstGadget['gadget_name']) ?> - RM <?= number_format($firstGadget['order_price'], 2) ?></td>
                    <td rowspan="<?= count($order['gadget_details']) + 1 ?>">RM <?= number_format($order['total_order_price'], 2) ?></td>
                    <td rowspan="<?= count($order['gadget_details']) + 1 ?>">
                        <form method="POST">
                            <?php
                            $allGadgetIds = array_column($order['gadget_details'], 'gadget_id');
                            array_unshift($allGadgetIds, $firstGadget['gadget_id']);
                            ?>
                            <input type="hidden" name="gadgetID" value="<?= htmlspecialchars(implode(',', $allGadgetIds)) ?>">
                            <input type="hidden" name="checkboxName" value="<?= htmlspecialchars($order['order_id']) ?>">
                            <button type="submit" class="btn btn-primary" data-confirm="Are you sure this order is delivered?">Mark as Delivered</button>
                        </form>
                    </td>
                </tr>
                <?php foreach ($order['gadget_details'] as $gadget): ?>
                    <tr>
                        <td><?= htmlspecialchars($gadget['gadget_name']) ?> - RM <?= number_format($gadget['order_price'], 2) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>

<?= $p->html(http_build_query([
    'sort' => $searchParams['sort'],
    'dir' => $searchParams['dir']
])) ?>

<?php include '../_foot.php'; ?>