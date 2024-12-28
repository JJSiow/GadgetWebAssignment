<link rel="stylesheet" href="/css/brand.css">
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

$_title = '';
include '../admin/_admin_head.php';
?>

<div class="title-container">
    <h1 class="title">Brand Management</h1>
    <form action="add_brand.php" method="post">
        <input type="text" id="brd_name" name="brd_name" placeholder="Add new brand">
        <button class="button addProdButton">Add</button>
        <?php if ($brand_error): ?>
            <p class="error"><?= htmlspecialchars($brand_error) ?></p>
        <?php endif; ?>
    </form>
</div>

<form id="mark-all-form" action="delete_brand.php" method="post">
    <div class="hidden-button">
        <button id="submit-mark-unactive" class="btn btn-primary" data-confirm='Are you sure to deactivate all selected brand?'>Deactivate All</button>
        <button id="submit-mark-active" class="btn btn-primary" data-confirm='Are you sure to activate all selected brand?'>Activate All</button>
    </div>
</form>

<p>
    <?= $p->count ?> of <?= $p->item_count ?> record(s) |
    Page <?= $p->page ?> of <?= $p->page_count ?>
</p>

<table class="table">
    <tr>
        <th></th>
        <?= table_headers2($fields, $sort, $dir, "page=$page") ?>
    </tr>

    <form method="post">
        <tr>
            <td><label class="checkbox-wrapper"><input type="checkbox" id="check-all"><span>All</span></label></td>
            <td><?= html_search2('sid', $searchParams['sid']) ?></td>
            <td><?= html_search2('sname', $searchParams['sname']) ?></td>
            <td><?= html_select2('sstatus', $_status, 'All', $searchParams['sstatus']) ?></td>
            <td>
                <button type="submit">Search</button>
                <button data-get="?clear_search=1" class="clear-search-btn">Clear</button>
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
                <td>
                    <input type="checkbox"
                        name="id[]"
                        value="<?= htmlspecialchars($brand->brand_id) ?>"
                        class="checkbox">
                </td>
                <td><?= $brand->brand_id ?></td>
                <td class="edit" data-id="<?= $brand->brand_id ?>" data-update-url="update_brand.php"><?= $brand->brand_name ?></td>
                <td><span class="status-badge status-<?= $brand->brand_status ?>"><?= $brand->brand_status ?></span></td>
                <td>
                    <form id="mark-all-form" action="delete_brand.php" method="post">
                        <input type="hidden" name="checkboxName" value="<?= htmlspecialchars($brand->brand_id) ?>">
                        <?php if ($brand->brand_status == 'Active'): ?>
                            <a id="next_unactive" data-post="delete_brand.php?action=Unactive"
                                data-confirm='Are you sure you want to deactivate this brand?'>Deactivate</a>
                        <?php else: ?>
                            <a id="next_active" data-post="delete_brand.php?action=Active"
                                data-confirm='Are you sure you want to activate this brand?'>Activate</a>
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