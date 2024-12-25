<?php
require_once '../_base.php';

$brand_error = isset($_SESSION['brand_error']) ? $_SESSION['brand_error'] : null;
unset($_SESSION['brand_error']);

$fields = [
    'brand_id' => 'Brand ID',
    'brand_name' => 'Brand Name',
    'brand_status' => 'Brand Status',
    'action' => 'Action'
];

$searchParams = $_SESSION['brand_search_params'] ?? [
    'sid' => '',
    'sname' => '',
    'sstatus' => '',
    'sort' => 'brand_id',
    'dir' => 'asc',
    'page' => 1
];

// Clear search if requested
if (isset($_GET['clear_search'])) {
    unset($_SESSION['brand_search_params']);
    $searchParams = [
        'sid' => '',
        'sname' => '',
        'sstatus' => '',
        'sort' => 'brand_id',
        'dir' => 'asc',
        'page' => 1
    ];
}
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
        'sname' => req('sname', ''),
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

$_SESSION['brand_search_params'] = $searchParams;

// Build the query
function buildCategoryQuery($searchParams)
{
    $conditions = [];
    $params = [];

    $baseQuery = "SELECT * FROM brand WHERE 1=1";

    if ($searchParams['sid']) {
        $conditions[] = "brand_id LIKE ?";
        $params[] = "%{$searchParams['sid']}%";
    }

    if ($searchParams['sname']) {
        $conditions[] = "brand_name LIKE ?";
        $params[] = "%{$searchParams['sname']}%";
    }

    if ($searchParams['sstatus'] && $searchParams['sstatus'] !== 'All') {
        $conditions[] = "brand_status = ?";
        $params[] = $searchParams['sstatus'];
    }

    if (!empty($conditions)) {
        $baseQuery .= " AND " . implode(" AND ", $conditions);
    }

    $baseQuery .= " ORDER BY {$searchParams['sort']} {$searchParams['dir']}";

    return [$baseQuery, $params];
}

// Get the query and parameters
[$query, $params] = buildCategoryQuery($searchParams);

require_once '../lib/SimplePager2.php';
$p = new SimplePager2(
    $query,
    $params,
    10,
    $searchParams['page'],
    true
);

$arr = $p->result;

$_title = 'Gadget Brand';
include '../_head.php';
?>

<form action="add_brand.php" method="post">
    <input type="text" id="brd_name" name="brd_name" placeholder="Add new brand">
    <button class="button addProdButton">Add</button>
    <?php if ($brand_error): ?>
        <p class="error"><?= htmlspecialchars($brand_error) ?></p>
    <?php endif; ?>
</form>

<p>
    <?= $p->count ?> of <?= $p->item_count ?> record(s) |
    Page <?= $p->page ?> of <?= $p->page_count ?>
</p>


<table class="table">
    <tr>
        <?= table_headers2($fields, $sort, $dir, "page=$page") ?>
    </tr>

    <form method="post">
        <tr>
            <td><?= html_search2('sid', $searchParams['sid']) ?></td>
            <td><?= html_search2('sname', $searchParams['sname']) ?></td>
            <td><?= html_select2('sstatus', $_status, 'All', $searchParams['sstatus']) ?></td>
            <td><button type="submit">Search</button>
                <a href="?clear_search=1" class="clear-search-btn">Clear Search</a>
            </td>
        </tr>
    </form>

    <?php if (empty($arr)): ?>
        <tr>
            <td colspan="8">No brand records found...</td>
        </tr>
    <?php else: ?>
        <?php foreach ($arr as $brand): ?>
            <tr>
                <td><?= $brand->brand_id ?></td>
                <td class="edit" data-id="<?= $brand->brand_id ?>" data-update-url="update_brand.php"><?= $brand->brand_name ?></td>
                <td><?= $brand->brand_status ?></td>
                <td>
                    <?php if ($brand->brand_status == 'Active'): ?>
                        <a data-post="delete_brand.php?action=Unactive&id=<?= $brand->brand_id ?>" data-confirm='Are you sure you want to unactivate this brand?'>Unactivate</a>
                    <?php else: ?>
                        <a data-post="delete_brand.php?action=Active&id=<?= $brand->brand_id ?>" data-confirm='Are you sure you want to activate this brand?'>Activate</a>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach ?>
    <?php endif; ?>
</table>

<?= $p->html(http_build_query([
    'sort' => $searchParams['sort'],
    'dir' => $searchParams['dir']
])) ?>

<?php
include '../_foot.php'; ?>