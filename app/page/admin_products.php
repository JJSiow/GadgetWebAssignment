<?php
require_once '../_base.php';

// (1) Sorting
$fields = [
    'gadget_id' => 'Gadget Id',
    'gadget_name' => 'Gadget Name',
    'brand_name' => 'Gadget Brand',
    'category_name' => 'Gadget Category',
    'gadget_price' => 'Gadget Price',
    'gadget_stock' => 'Available Stock',
    'gadget_status' => 'Gadget Status',
    'action' => 'Action'
];

// Initialize default search parameters
$searchParams = $_SESSION['gadget_search_params'] ?? [
    'sid' => '',
    'sname' => '',
    'sbrand' => '',
    'scategory' => '',
    'sprice' => '',
    'sstock' => '',
    'sstatus' => '',
    'sort' => 'gadget_id',
    'dir' => 'asc',
    'page' => 1
];

// Check for clear search request
if (isset($_GET['clear_search'])) {
    unset($_SESSION['gadget_search_params']);
    $searchParams = [
        'sid' => '',
        'sname' => '',
        'sbrand' => '',
        'scategory' => '',
        'sprice' => '',
        'sstock' => '',
        'sstatus' => '',
        'sort' => 'gadget_id',
        'dir' => 'asc',
        'page' => 1
    ];
}

// Handle sorting
$sort = req('sort');
$sort = key_exists($sort, $fields) ? $sort : $searchParams['sort'];

$dir = req('dir');
$dir = in_array($dir, ['asc', 'desc']) ? $dir : $searchParams['dir'];

// Handle pagination
$page = req('page', $searchParams['page']);

// If it's a POST request (new search), update session parameters
if (is_post()) {
    $searchParams = [
        'sid' => req('sid', $searchParams['sid']), 
        'sname' => req('sname', $searchParams['sname']),
        'sbrand' => req('sbrand', $searchParams['sbrand']),
        'scategory' => req('scategory', $searchParams['scategory']),
        'sprice' => req('sprice', $searchParams['sprice']),
        'sstock' => req('sstock', $searchParams['sstock']),
        'sstatus' => req('sstatus', $searchParams['sstatus']),
        'sort' => $sort,
        'dir' => $dir,
        'page' => $page
    ];

    // Save to session
    $_SESSION['gadget_search_params'] = $searchParams;
}
// Update sort and dir in session if changed
$searchParams['sort'] = $sort;
$searchParams['dir'] = $dir;
$searchParams['page'] = $page;
$_SESSION['gadget_search_params'] = $searchParams;

// Prepare base query
$baseQuery = "SELECT g.*, c.*, b.*
    FROM gadget g 
    JOIN category c ON g.category_id = c.category_id
    JOIN brand b ON g.brand_id = b.brand_id
    WHERE b.brand_status = 'Active' AND 
    c.category_status = 'Active'";

$categories = $_db->query("SELECT category_name FROM category WHERE category_status = 'Active'")->fetchAll();
$brands = $_db->query("SELECT brand_name from brand WHERE brand_status = 'Active'")->fetchAll();

$category_name = array_map(fn($category) => $category->category_name, $categories);
$brands_name = array_map(fn($brand) => $brand->brand_name, $brands);

// Build search conditions
$conditions = [];
$params = [];

if ($searchParams['sid']) {
    $conditions[] = "g.gadget_id LIKE ?";
    $params[] = "%{$searchParams['sid']}%";
}

if ($searchParams['sname']) {
    $conditions[] = "g.gadget_name LIKE ?";
    $params[] = "%{$searchParams['sname']}%";
}

if ($searchParams['sbrand']) {
    $conditions[] = "b.brand_name = ?";
    $params[] = $searchParams['sbrand'];
}

if ($searchParams['scategory']) {
    $conditions[] = "c.category_name = ?";
    $params[] = $searchParams['scategory'];
}

if ($searchParams['sprice']) {
    $conditions[] = "ROUND(g.gadget_price, 2) = ROUND(?, 2)";
    $params[] = $searchParams['sprice'];
}

if ($searchParams['sstock']) {
    $conditions[] = "g.gadget_stock = ?";
    $params[] = $searchParams['sstock'];
}

if ($searchParams['sstatus']) {
    $conditions[] = "g.gadget_status = ?";
    $params[] = $searchParams['sstatus'];
}

// Modify the query with search conditions
if (!empty($conditions)) {
    $searchQuery = $baseQuery . " AND " . implode(' AND ', $conditions);
} else {
    $searchQuery = $baseQuery;
}

// Add sorting
$searchQuery .= " ORDER BY {$searchParams['sort']} {$searchParams['dir']}";

// Use SimplePager with the appropriate query
require_once '../lib/SimplePager2.php';
$p = new SimplePager2(
    $searchQuery,
    $params,
    5,
    $searchParams['page']
);

$arr = $p->result;

$_title = 'Gadget';
include '../_head.php';
?>

<button class="button addProdButton" onclick="window.location.href='add_gadget.php'">Add New Gadget</button>

<p>
    <?= $p->count ?> of <?= $p->item_count ?> record(s) |
    Page <?= $p->page ?> of <?= $p->page_count ?>
</p>

<table class="table">
    <tr>
        <?= table_headers2($fields, $searchParams['sort'], $searchParams['dir'], "page={$searchParams['page']}") ?>
    </tr>

    <form method="post">
        <tr>
            <td><?= html_search('sid', $searchParams['sid']) ?></td>
            <td><?= html_search('sname', $searchParams['sname']) ?></td>
            <td><?= html_select2('sbrand', $brands_name,  'All',$searchParams['sbrand']) ?></td>
            <td><?= html_select2('scategory', $category_name,  'All',$searchParams['scategory']) ?></td>
            <td><?= html_number('sprice', $searchParams['sprice'], ['min' => '0.01', 'max' => '10000.00', 'step' => '0.01'],'RM '); ?></td>
            <td><?= html_number('sstock', $searchParams['sstock'], ['min' => '0', 'max' => '1000', 'step' => '1']); ?></td>
            <td><?= html_select2('sstatus', $_status, 'All',$searchParams['sstatus'] ) ?></td>
            <td>
                <button type="submit">Search</button>
                <a href="?clear_search=1" class="clear-search-btn">Clear Search</a>
            </td>
        </tr>
    </form>

    <?php if (empty($arr)): ?>
        <tr>
            <td colspan="8">No gadget records found...</td>
        </tr>
    <?php else: ?>
        <?php foreach ($arr as $gadget): ?>
            <tr>
                <td><?= $gadget->gadget_id ?></td>
                <td><?= $gadget->gadget_name ?></td>
                <td><?= $gadget->brand_name ?></td>
                <td><?= $gadget->category_name ?></td>
                <td>RM <?= number_format($gadget->gadget_price, 2) ?></td>
                <td><?= $gadget->gadget_stock ?></td>
                <td><?= $gadget->gadget_status ?></td>
                <td>
                    <a href="view_gadget.php?id=<?= $gadget->gadget_id ?>">View</a> |
                    <a data-get="update_gadget.php?id=<?= $gadget->gadget_id ?>">Edit</a> |
                    <?php if ($gadget->gadget_status == 'Active'): ?>
                        <a data-post="delete_gadget.php?action=Unactive&id=<?= $gadget->gadget_id ?>" data-confirm='Are you sure you want to unactivate this gadget?'>Unactivate</a>
                    <?php else: ?>
                        <a data-post="delete_gadget.php?action=Active&id=<?= $gadget->gadget_id ?>" data-confirm='Are you sure you want to activate this gadget?'>Activate</a>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach ?>
    <?php endif; ?>
</table>

<?= $p->html("sort={$searchParams['sort']}&dir={$searchParams['dir']}") ?>

<?php
include '../_foot.php'; ?>