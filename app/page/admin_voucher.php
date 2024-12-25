<?php
require_once '../_base.php';

$voucher_error = isset($_SESSION['voucher_error']) ? $_SESSION['voucher_error'] : null;
unset($_SESSION['voucher_error']);

// Define fields for sorting
$fields = [
    'voucher_id' => 'Voucher ID',
    'voucher_amount' => 'Voucher Amount',
    'voucher_status' => 'Voucher Status',
    'action' => 'Action'
];

// Initialize search parameters from session or defaults
$searchParams = $_SESSION['voucher_search_params'] ?? [
    'sid' => '',
    'samount' => '',
    'sstatus' => '',
    'sort' => 'voucher_id',
    'dir' => 'asc',
    'page' => 1
];

// Clear search if requested
if (isset($_GET['clear_search'])) {
    unset($_SESSION['voucher_search_params']);
    $searchParams = [
        'sid' => '',
        'samount' => '',
        'sstatus' => '',
        'sort' => 'voucher_id',
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
        'sid' => req('sid', ''),
        'samount' => req('samount', ''),
        'sstatus' => req('sstatus', ''),
        'sort' => $sort,
        'dir' => $dir,
        'page' => 1
    ];
} else {
    // Update sort/pagination for GET requests
    $searchParams['sort'] = $sort;
    $searchParams['dir'] = $dir;
    $searchParams['page'] = $page;
}

// Save to session
$_SESSION['voucher_search_params'] = $searchParams;

// Build the query
function buildVoucherQuery($searchParams)
{
    $conditions = [];
    $params = [];

    $baseQuery = "SELECT * FROM voucher WHERE 1=1";

    if ($searchParams['sid']) {
        $conditions[] = "voucher_id LIKE ?";
        $params[] = "%{$searchParams['sid']}%";
    }

    if ($searchParams['samount']) {
        $conditions[] = "ROUND(voucher_amount, 2) = ROUND(?, 2)";
        $params[] = $searchParams['samount'];
    }

    if ($searchParams['sstatus'] && $searchParams['sstatus'] !== 'All') {
        $conditions[] = "voucher_status = ?";
        $params[] = $searchParams['sstatus'];
    }

    if (!empty($conditions)) {
        $baseQuery .= " AND " . implode(" AND ", $conditions);
    }

    $baseQuery .= " ORDER BY {$searchParams['sort']} {$searchParams['dir']}";

    return [$baseQuery, $params];
}

// Get the query and parameters
[$query, $params] = buildVoucherQuery($searchParams);

// Create SimplePager with the appropriate query
require_once '../lib/SimplePager2.php';
$p = new SimplePager2(
    $query,
    $params,
    10,
    $searchParams['page'],
    true
);

$arr = $p->result;

$_title = 'Voucher';
include '../_head.php';
?>

<form action="add_voucher.php" method="post">
    <label for="voc_amount">RM
        <input type="number" id="voc_amount" name="voc_amount" step="0.10" min="1.00" max="10000.00" placeholder="Enter voucher amount" required style="width: 175px;">
    </label>
    <button class="button addProdButton">Add</button>
    <?php if ($voucher_error): ?>
        <p class="error"><?= htmlspecialchars($voucher_error) ?></p>
    <?php endif; ?>
</form>

<form id="mark-all-form" action="delete_voucher.php" method="post">
    <button id="submit-mark-unactive" class="btn btn-primary" data-post="delete_voucher.php?action=Unactive" style="display: none;" data-confirm='Are you sure to unactivate all selected voucher?'>Unactivate All</button>
    <button id="submit-mark-active" class="btn btn-primary" data-post="delete_voucher.php?action=Active" style="display: none;" data-confirm='Are you sure to activate all selected voucher?'>Activate All</button>
</form>

<p>
    <?= $p->count ?> of <?= $p->item_count ?> record(s) |
    Page <?= $p->page ?> of <?= $p->page_count ?>
</p>

<table class="table">
    <tr>
        <th></th>
        <?= table_headers2($fields, $searchParams['sort'], $searchParams['dir'], "page={$searchParams['page']}") ?>
    </tr>

    <form method="post">
        <tr>
            <td><input type="checkbox" id="check-all">All</td>
            <td><?= html_search2('sid', $searchParams['sid']) ?></td>
            <td><?= html_number('samount', $searchParams['samount'] ?? '', ['min' => '0.01', 'max' => '10000.00', 'step' => '0.01'], 'RM ') ?></td>
            <td><?= html_select2('sstatus', $_status, 'All', $searchParams['sstatus']) ?></td>
            <td>
                <button type="submit">Search</button>
                <a href="?clear_search=1" class="clear-search-btn">Clear Search</a>
            </td>
        </tr>
    </form>

    <?php if (empty($arr)): ?>
        <tr>
            <td colspan="5">No voucher records found...</td>
        </tr>
    <?php else: ?>
        <?php foreach ($arr as $voucher): ?>
            <tr>
                <td>
                    <input type="checkbox"
                        name="id[]"
                        value="<?= htmlspecialchars($voucher->voucher_id) ?>"
                        class="checkbox">
                </td>
                <td><?= htmlspecialchars($voucher->voucher_id) ?></td>
                <td class="edit2" data-id="<?= htmlspecialchars($voucher->voucher_id) ?>"
                    data-update-url="update_voucher.php">
                    RM <?= number_format($voucher->voucher_amount, 2) ?>
                </td>
                <td><?= htmlspecialchars($voucher->voucher_status) ?></td>
                <td>
                    <form id="mark-all-form" action="delete_voucher.php" method="post">
                        <input type="hidden" name="checkboxName" value="<?= htmlspecialchars($voucher->voucher_id) ?>">
                        <?php if ($voucher->voucher_status == 'Active'): ?>
                            <a id="next_unactive" data-post="delete_voucher.php?action=Unactive"
                                data-confirm='Are you sure you want to unactivate this voucher?'>Unactivate</a>
                        <?php else: ?>
                            <a id="next_active" data-post="delete_voucher.php?action=Active"
                                data-confirm='Are you sure you want to activate this voucher?'>Activate</a>
                        <?php endif; ?>
                    </form>
                </td>
            </tr>
        <?php endforeach ?>
    <?php endif; ?>
</table>

<?= $p->html(http_build_query([
    'sort' => $searchParams['sort'],
    'dir' => $searchParams['dir']
])) ?>

<?php include '../_foot.php'; ?>