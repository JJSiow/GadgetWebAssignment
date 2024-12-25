<?php
require_once '../_base.php';

// (1) Sorting
$fields = [
    'gadget_id' => 'Gadget ID',
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
    'sstock_operator' => '=',
    'sstatus' => '',
    'sort' => 'gadget_id',
    'dir' => 'asc',
    'page' => 1
];

if (!isset($_SESSION['previous_stock_levels'])) {
    $_SESSION['previous_stock_levels'] = [];
    $stockQuery = "SELECT gadget_id, gadget_stock FROM gadget";
    $stocks = $_db->query($stockQuery)->fetchAll();
    foreach ($stocks as $item) {
        $_SESSION['previous_stock_levels'][$item->gadget_id] = $item->gadget_stock;
    }
}

// Check for new low stock items
$currentStocks = $_db->query("SELECT gadget_id, gadget_name, gadget_stock FROM gadget WHERE gadget_status = 'Active'")->fetchAll();
$newLowStockItems = [];

foreach ($currentStocks as $item) {
    $previousStock = $_SESSION['previous_stock_levels'][$item->gadget_id] ?? $item->gadget_stock;
    if ($item->gadget_stock < 10 && $previousStock >= 10) {
        $newLowStockItems[] = $item;
    }
    $_SESSION['previous_stock_levels'][$item->gadget_id] = $item->gadget_stock;
}

if (is_get()) {
    $stock = req('stock');
    $operator = req('operator');

    if ($stock !== '' && $operator !== '') {
        $searchParams['sstock'] = $stock;
        $searchParams['sstock_operator'] = $operator;
        $searchParams['sort'] = 'gadget_stock';
        $searchParams['dir'] = 'asc';
        $searchParams['page'] = 1;
    }

    $sort = req('sort');
    if ($sort && key_exists($sort, $fields)) {
        $searchParams['sort'] = $sort;
    }

    $dir = req('dir');
    if ($dir && in_array($dir, ['asc', 'desc'])) {
        $searchParams['dir'] = $dir;
    }

    $page = req('page');
    if ($page) {
        $searchParams['page'] = (int)$page;
    }

    $_SESSION['gadget_search_params'] = $searchParams;
}

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
        'sstock_operator' => '=', // Reset operator
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
$page = is_post() ? 1 : (int)req('page', $searchParams['page']);

// If it's a POST request (new search), update session parameters
if (is_post()) {
    $searchParams = [
        'sid' => req('sid', $searchParams['sid']),
        'sname' => req('sname', $searchParams['sname']),
        'sbrand' => req('sbrand', $searchParams['sbrand'] ?? ''),
        'scategory' => req('scategory', $searchParams['scategory'] ?? ''),
        'sprice' => req('sprice', $searchParams['sprice'] ?? ''),
        'sstock' => req('sstock', $searchParams['sstock']),
        'sstock_operator' => req('sstock_operator', $searchParams['sstock_operator']),
        'sstatus' => req('sstatus', $searchParams['sstatus']),
        'sort' => $sort,
        'dir' => $dir,
        'page' => 1
    ];
} else {
    // For GET requests (pagination/sorting), maintain search parameters but update page/sort
    $searchParams['sort'] = $sort;
    $searchParams['dir'] = $dir;
    $searchParams['page'] = $page;
}

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

if ($searchParams['sbrand'] ?? '') {
    $conditions[] = "b.brand_name = ?";
    $params[] = $searchParams['sbrand'];
}

if ($searchParams['scategory'] ?? '') {
    $conditions[] = "c.category_name = ?";
    $params[] = $searchParams['scategory'];
}

if ($searchParams['sprice'] ?? '') {
    $conditions[] = "ROUND(g.gadget_price, 2) = ROUND(?, 2)";
    $params[] = $searchParams['sprice'];
}

if ($searchParams['sstock'] !== '' && isset($searchParams['sstock_operator'])) {
    $operator = $_operators[$searchParams['sstock_operator']] ?? '=';
    $conditions[] = "g.gadget_stock {$operator} ?";
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
    10,
    $searchParams['page'],
    false
);

$arr = $p->result;

$gadget_images = $_db->query('SELECT gallery_id, photo_path, gadget_id FROM gallery')->fetchAll();
$_title = 'Gadget';
include '../admin/_adminHead.php';
?>

<?php if (!empty($newLowStockItems)): ?>
    <div id="stock-alert" class="stock-alert" style="background-color: #f8d7da; border: 1px solid #f5c6cb; padding: 10px; margin: 10px 0; border-radius: 4px; position: relative;">
        <button class="dismiss-alert" style="position: absolute; right: 10px; top: 10px; background: none; border: none; cursor: pointer; font-size: 18px;">&times;</button>
        <strong style="color: #721c24;">Warning: New Low Stock Detected!</strong>
        <?php foreach ($newLowStockItems as $item): ?>
            <p style="margin: 5px 0; color: #721c24;">
                <?= htmlspecialchars($item->gadget_name) ?> has dropped below 10 units (Current stock: <?= $item->gadget_stock ?>)
            </p>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<button class="button addProdButton" onclick="window.location.href='add_gadget.php'">Add New Gadget</button>

<form id="mark-all-form" action="delete_gadget.php" method="post">
    <button id="submit-mark-unactive" class="btn btn-primary" data-post="delete_gadget.php?action=Unactive" style="display: none;" data-confirm='Are you sure to unactivate all selected gadget?'>Unactivate All</button>
    <button id="submit-mark-active" class="btn btn-primary" data-post="delete_gadget.php?action=Active" style="display: none;" data-confirm='Are you sure to activate all selected gadget?'>Activate All</button>
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
            <td><?= html_select2('sbrand', $brands_name,  'All', $searchParams['sbrand'] ?? '') ?></td>
            <td><?= html_select2('scategory', $category_name,  'All', $searchParams['scategory'] ?? '') ?></td>
            <td><?= html_number('sprice', $searchParams['sprice'] ?? '', ['min' => '0.01', 'max' => '10000.00', 'step' => '0.01'], 'RM '); ?></td>
            <td>
                <?= html_select2('sstock_operator', $_operators,  '=', $searchParams['sstock_operator'] ?? '') ?>
                <?= html_number('sstock', $searchParams['sstock'] ?? '', ['min' => '0', 'max' => '1000', 'step' => '1']); ?>
            </td>
            <td><?= html_select2('sstatus', $_status, 'All', $searchParams['sstatus']) ?></td>
            <td>
                <button type="submit">Search</button>
                <a href="?clear_search=1" class="clear-search-btn">Clear Search</a>
            </td>
        </tr>
    </form>

    <?php if (empty($arr)): ?>
        <tr>
            <td colspan="9">No gadget records found...</td>
        </tr>
    <?php else: ?>
        <?php foreach ($arr as $gadget): ?>
            <?php
            $rowColor = '';
            $stock_status = '';
            if ($gadget->gadget_stock == 0) {
                $rowColor = 'background-color: #ffcdd2;';
                $stock_status = "Out Of Stock";
            } elseif ($gadget->gadget_stock <= 10) {
                $rowColor = 'background-color: #fff59d;';
                $stock_status = "Low Stock";
            } else {
                $stock_status = "Sufficient";
            }
            ?>
            <tr>
                <td style="<?= $rowColor ?>">
                    <input type="checkbox"
                        name="id[]"
                        value="<?= htmlspecialchars($gadget->gadget_id) ?>"
                        class="checkbox">
                </td>
                <td style="<?= $rowColor ?>"><?= $gadget->gadget_id ?></td>
                <td style="<?= $rowColor ?>">
                    <?= $gadget->gadget_name ?>
                    <div class="slideshow-container" data-gadget-id="<?= $gadget->gadget_id ?>">
                        <?php
                        $gadget_specific_images = array_values(
                            array_filter($gadget_images, function ($image) use ($gadget) {
                                return $image->gadget_id == $gadget->gadget_id; // Ensure strict matching
                            })
                        );
                        foreach ($gadget_specific_images as $index => $image): ?>
                            <img
                                class="gadget-image <?= $index === 0 ? 'active' : '' ?>"
                                src="<?= htmlspecialchars('../images/' . $image->photo_path) ?>"
                                alt="Gadget Preview">
                        <?php endforeach; ?>
                    </div>
                </td>
                <td style="<?= $rowColor ?>"><?= $gadget->brand_name ?></td>
                <td style="<?= $rowColor ?>"><?= $gadget->category_name ?></td>
                <td style="<?= $rowColor ?>">RM <?= number_format($gadget->gadget_price, 2) ?></td>
                <td style="<?= $rowColor ?>"><?= $gadget->gadget_stock  . " (" . $stock_status . ")" ?></td>
                <td style="<?= $rowColor ?>"><?= $gadget->gadget_status ?></td>
                <td style="<?= $rowColor ?>">
                    <a href="view_gadget.php?id=<?= $gadget->gadget_id ?>">View</a> |
                    <a data-get="update_gadget.php?id=<?= $gadget->gadget_id ?>">Edit</a> |
                    <form id="mark-all-form" action="delete_gadget.php" method="post">
                        <input type="hidden" name="checkboxName" value="<?= htmlspecialchars($gadget->gadget_id) ?>">
                        <?php if ($gadget->gadget_status == 'Active'): ?>
                            <a id="next_unactive" data-post="delete_gadget.php?action=Unactive"
                                data-confirm='Are you sure you want to unactivate this gadget?'>Unactivate</a>
                        <?php else: ?>
                            <a id="next_active" data-post="delete_gadget.php?action=Active"
                                data-confirm='Are you sure you want to activate this gadget?'>Activate</a>
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
])); ?>