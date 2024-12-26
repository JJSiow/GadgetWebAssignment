<?php
require '../_base.php';

// Define fields for table headers
$fields = [
    'order_id' => 'Order ID',
    'member_id' => 'Member ID',
    'gadget_name_price' => 'Gadget Purchased',
    'quantity' => 'Qty',
    'total_order_price' => 'Order Price',
    'action' => 'Action',
];

// Initialize search parameters from session or defaults
$searchParams = $_SESSION['order_search_params'] ?? [
    'soid' => '',
    'smid' => '',
    'sgadget' => '',
    'sstock' => '',
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
        'sstock' => '',
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
        'sstock' => req('sstock', ''),
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
            GROUP_CONCAT(i.item_quantity) as itemQtys,
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

    if (!empty($searchParams['sstock'])) {
        $conditions[] = "o.order_id IN (
            SELECT oi.order_id 
            FROM order_item oi 
            WHERE oi.item_quantity = ?
        )";
        $params[] = $searchParams['sstock'];
    }

    if ($searchParams['sprice']) {
        $conditions[] = "ROUND(o.total_order_price, 2) = ROUND(?, 2)";
        $params[] = $searchParams['sprice'];
    }

    $baseQuery .= " WHERE " . implode(" AND ", $conditions);
    $baseQuery .= " GROUP BY o.order_id) as subquery";

    $sortField = $searchParams['sort'];
    if ($sortField === 'gadget_name_price') {
        $sortField = 'gadget_names';
    } elseif ($sortField === 'quantity') {
        $sortField = 'itemQtys';
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

if (is_post() && isset($_POST['checkboxName'])) {
    $orderIds = explode(',', req('checkboxName', ''));

    if (!empty($orderIds)) {
        $placeholders = implode(',', array_fill(0, count($orderIds), '?'));
        $stm = $_db->prepare("UPDATE `order` SET order_status = 'delivered' WHERE order_id IN ($placeholders)");

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
    $itemQtys = explode(',', $order->itemQtys ?? '');
    $gadgetNames = explode(',', $order->gadget_names);
    $orderPrices = explode(',', $order->order_prices);

    $gadgetDetails = [];
    for ($i = 0; $i < count($gadgetNames); $i++) {
        $gadgetDetails[] = [
            'gadget_name' => $gadgetNames[$i],
            'order_price' => $orderPrices[$i] ?? 0,
            'item_quantity' => $itemQtys[$i] ?? ''
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
include '../admin/_adminHead.php';
?>

<div>
    <button class="active">Pending</button>
    <button>Delivered</button>
    <button>Received</button>
</div>

<p>
    <?= $p->count ?> of <?= $p->item_count ?> record(s) |
    Page <?= $p->page ?> of <?= $p->page_count ?>
</p>

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
                <td><?= html_number('sstock', $searchParams['sstock'] ?? '', ['min' => '0', 'max' => '1000', 'step' => '1']); ?></td>
                <td><?= html_number('sprice', $searchParams['sprice'] ?? '', ['min' => '0.01', 'max' => '10000.00', 'step' => '0.01'], 'RM '); ?></td>
                <td>
                    <button type="submit">Search</button>
                    <a href="?clear_search=1" class="clear-search-btn">Clear Search</a>
                </td>
            </tr>
        </form>
        <?php if (empty($processedOrders)): ?>
            <tr>
                <td colspan="7">No pending orders found...</td>
            </tr>
        <?php else: ?>
            <?php foreach ($processedOrders as $order): ?>
                <?php
                $gadgetCount = count($order['gadget_details']);
                $firstGadget = $order['gadget_details'][0];
                ?>
                <tr>
                    <td rowspan="<?= $gadgetCount ?>">
                        <input type="checkbox"
                            name="id[]"
                            value="<?= htmlspecialchars($order['order_id']) ?>"
                            class="checkbox">
                    </td>
                    <td rowspan="<?= $gadgetCount ?>"><?= htmlspecialchars($order['order_id']) ?></td>
                    <td rowspan="<?= $gadgetCount ?>"><?= htmlspecialchars($order['member_id']) ?></td>
                    <td><?= htmlspecialchars($firstGadget['gadget_name']) ?> - RM <?= number_format($firstGadget['order_price'], 2) ?></td>
                    <td><?= htmlspecialchars($firstGadget['item_quantity']) ?></td>
                    <td rowspan="<?= $gadgetCount ?>">RM <?= number_format($order['total_order_price'], 2) ?></td>
                    <td rowspan="<?= $gadgetCount ?>">
                        <form method="POST">
                            <input type="hidden" name="checkboxName" value="<?= htmlspecialchars($order['order_id']) ?>">
                            <button type="submit" class="btn btn-primary" data-confirm="Are you sure this order is delivered?">Mark as Delivered</button>
                        </form>
                    </td>
                </tr>
                <?php foreach (array_slice($order['gadget_details'], 1) as $gadget): ?>
                    <tr>
                        <td><?= htmlspecialchars($gadget['gadget_name']) ?> - RM <?= number_format($gadget['order_price'], 2) ?></td>
                        <td><?= htmlspecialchars($gadget['item_quantity']) ?></td>
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