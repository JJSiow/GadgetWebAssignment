<?php
require_once '../_base.php';

$category_error = isset($_SESSION['category_error']) ? $_SESSION['category_error'] : null;
unset($_SESSION['category_error']);

// Define fields for sorting
$fields = [
    'category_id' => 'Category ID',
    'category_name' => 'Category Name',
    'category_status' => 'Category Status',
    'action' => 'Action'
];

// Initialize search parameters from session or defaults
$searchParams = $_SESSION['category_search_params'] ?? [
    'sid' => '',
    'sname' => '',
    'sstatus' => '',
    'sort' => 'category_id',
    'dir' => 'asc',
    'page' => 1
];

// Clear search if requested
if (isset($_GET['clear_search'])) {
    unset($_SESSION['category_search_params']);
    $searchParams = [
        'sid' => '',
        'sname' => '',
        'sstatus' => '',
        'sort' => 'category_id',
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

// Save to session
$_SESSION['category_search_params'] = $searchParams;

// Build the query
function buildCategoryQuery($searchParams)
{
    $conditions = [];
    $params = [];

    $baseQuery = "SELECT * FROM category WHERE 1=1";

    if ($searchParams['sid']) {
        $conditions[] = "category_id LIKE ?";
        $params[] = "%{$searchParams['sid']}%";
    }

    if ($searchParams['sname']) {
        $conditions[] = "category_name LIKE ?";
        $params[] = "%{$searchParams['sname']}%";
    }

    if ($searchParams['sstatus'] && $searchParams['sstatus'] !== 'All') {
        $conditions[] = "category_status = ?";
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

$_title = 'Gadget Category';
include '../admin/_adminHead.php';
?>

<form action="add_category.php" method="post">
    <input type="text" id="ctg_name" name="ctg_name" placeholder="Add new category" required>
    <button class="button addProdButton">Add</button>
    <?php if ($category_error): ?>
        <p class="error"><?= htmlspecialchars($category_error) ?></p>
    <?php endif; ?>
</form>

<form id="mark-all-form" action="delete_category.php" method="post">
    <button id="submit-mark-unactive" class="btn btn-primary" data-post="delete_category.php?action=Unactive" style="display: none;" data-confirm='Are you sure to unactivate all selected category?'>Unactivate All</button>
    <button id="submit-mark-active" class="btn btn-primary" data-post="delete_category.php?action=Active" style="display: none;" data-confirm='Are you sure to activate all selected category?'>Activate All</button>
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
            <td><?= html_search2('sname', $searchParams['sname']) ?></td>
            <td><?= html_select2('sstatus', $_status, 'All', $searchParams['sstatus']) ?></td>
            <td>
                <button type="submit">Search</button>
                <a href="?clear_search=1" class="clear-search-btn">Clear Search</a>
            </td>
        </tr>
    </form>

    <?php if (empty($arr)): ?>
        <tr>
            <td colspan="5">No category records found...</td>
        </tr>
    <?php else: ?>
        <?php foreach ($arr as $category): ?>
            <tr>
                <td>
                    <input type="checkbox"
                        name="id[]"
                        value="<?= htmlspecialchars($category->category_id) ?>"
                        class="checkbox">
                </td>
                <td><?= htmlspecialchars($category->category_id) ?></td>
                <td class="edit" data-id="<?= htmlspecialchars($category->category_id) ?>"
                    data-update-url="update_category.php">
                    <?= htmlspecialchars($category->category_name) ?>
                </td>
                <td><?= htmlspecialchars($category->category_status) ?></td>
                <td>
                    <form id="mark-all-form" action="delete_category.php" method="post">
                        <input type="hidden" name="checkboxName" value="<?= htmlspecialchars($category->category_id) ?>">
                        <?php if ($category->category_status == 'Active'): ?>
                            <a id="next_unactive" data-post="delete_category.php?action=Unactive"
                                data-confirm='Are you sure you want to unactivate this category?'>Unactivate</a>
                        <?php else: ?>
                            <a id="next_active" data-post="delete_category.php?action=Active"
                                data-confirm='Are you sure you want to activate this category?'>Activate</a>
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

<!-- <?php include '../_foot.php'; ?> -->