<link rel="stylesheet" href="/css/order.css">
<?php
require '../_base.php';
auth_admin();
// Define fields for table headers
$fields = [
    'order_id' => 'Order ID',
    'member_id' => 'Member ID',
    'gadget_name_price' => 'Gadget Purchased',
    'quantity' => 'Qty',
    'total_order_price' => 'Subtotal',
    'action' => 'Status',
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
    $conditions = ["o.order_status = 'received'"];
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

$_title = '';
include '../admin/_admin_head.php';
?>

<div class="container">
    <div class="order_header">
        <h1 class="title">Order Management</h1>
    </div>

    <div class="status-buttons">
        <button data-get="admin_order.php">Pending</button>
        <button data-get="admin_delivered.php">Delivered</button>
        <button data-get="admin_received.php" class="active">Received</button>
        <button data-get="admin_cancelled.php">Cancelled</button>

        <p class="page-info">
            <?= $p->count ?> of <?= $p->item_count ?> record(s) |
            Page <?= $p->page ?> of <?= $p->page_count ?>
        </p>
    </div>

    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <?= table_headers2($fields, $searchParams['sort'], $searchParams['dir'], "page={$searchParams['page']}") ?>
                </tr>
            </thead>
            <tbody>
                <tr class="search-row">
                    <form method="post">
                        <td><?= html_search2('soid', $searchParams['soid']) ?></td>
                        <td><?= html_search2('smid', $searchParams['smid']) ?></td>
                        <td><?= html_search2('sgadget', $searchParams['sgadget']) ?></td>
                        <td>
                            <div class="input-wrapper">
                                <?= html_number('sstock', $searchParams['sstock'] ?? '', ['min' => '0', 'max' => '1000', 'step' => '1']); ?>
                            </div>
                        </td>
                        <td>
                            <div class="input-wrapper">
                                <?= html_number('sprice', $searchParams['sprice'] ?? '', ['min' => '0.01', 'max' => '10000.00', 'step' => '0.01']); ?>
                            </div>
                        </td>
                        <td class="action-column">
                            <button type="submit" class="btn btn-search">Search</button>
                            <a href="?clear_search=1" class="clear-search-btn">Clear</a>
                        </td>
                    </form>
                </tr>

                <?php if (empty($processedOrders)): ?>
                    <tr>
                        <td colspan="7" class="no-records">No received orders found...</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($processedOrders as $order): ?>
                        <?php
                        $gadgetCount = count($order['gadget_details']);
                        $firstGadget = $order['gadget_details'][0];
                        ?>
                        <tr class="order-row">
                            <td rowspan="<?= $gadgetCount ?>" class="order-id"><?= htmlspecialchars($order['order_id']) ?></td>
                            <td rowspan="<?= $gadgetCount ?>" class="member-id"><?= htmlspecialchars($order['member_id']) ?></td>
                            <td class="gadget-details">
                                <span class="gadget-name"><?= htmlspecialchars($firstGadget['gadget_name']) ?></span>
                                <span class="gadget-price">RM <?= number_format($firstGadget['order_price'], 2) ?></span>
                            </td>
                            <td class="quantity"><?= htmlspecialchars($firstGadget['item_quantity']) ?></td>
                            <td rowspan="<?= $gadgetCount ?>" class="total-price">RM <?= number_format($order['total_order_price'], 2) ?></td>
                            <td rowspan="<?= $gadgetCount ?>" class="action-column">
                                <span class="status-badge status-<?= htmlspecialchars($order['order_status']) ?>"><?= htmlspecialchars($order['order_status']) ?></span>
                            </td>
                        </tr>
                        <?php foreach (array_slice($order['gadget_details'], 1) as $gadget): ?>
                            <tr class="additional-gadget-row">
                                <td class="gadget-details">
                                    <span class="gadget-name"><?= htmlspecialchars($gadget['gadget_name']) ?></span>
                                    <span class="gadget-price">RM <?= number_format($gadget['order_price'], 2) ?></span>
                                </td>
                                <td class="quantity"><?= htmlspecialchars($gadget['item_quantity']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="pagination">
        <?= $p->html(http_build_query([
            'sort' => $searchParams['sort'],
            'dir' => $searchParams['dir']
        ])) ?>
    </div>
</div>