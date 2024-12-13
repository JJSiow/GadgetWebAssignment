<?php
require_once '../_base.php';

$brand_error = isset($_SESSION['brand_error']) ? $_SESSION['brand_error'] : null;

unset($_SESSION['brand_error']);
// (1) Sorting
$fields = [
    'brand_id' => 'Brand ID',
    'brand_name' => 'Brand Name',
    'brand_status' => 'Brand Status',
    'action' => 'Action'
];
$sort = req('sort');
key_exists($sort, $fields) || $sort = 'brand_id';

$dir = req('dir');
in_array($dir, ['asc', 'desc']) || $dir = 'asc';

$arr = $_db->query("SELECT * FROM brand ORDER BY $sort $dir")->fetchAll();

if (is_post()) {
    $sid = req('sid');
    $sname = req('sname');
    $sstatus = req('sstatus');

    $stm = $_db->prepare('SELECT * FROM brand
    WHERE brand_name LIKE ?
    AND (brand_id = ? OR ?)
    AND (brand_status = ? OR ?)');

    $stm->execute(["%$sname%", $sid, $sid == null, $sstatus, $sstatus == null]);

    $arr = $stm->fetchAll();
}

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

<!-- <p>
    <?= $p->count ?> of <?= $p->item_count ?> record(s) |
    Page <?= $p->page ?> of <?= $p->page_count ?>
</p> -->


<table class="table">
    <tr>
        <!-- <?= table_headers($fields, $sort, $dir, "page=$page") ?> -->
        <?= table_headers($fields, $sort, $dir) ?>
    </tr>

    <form method="post">
        <tr>
            <td><?= html_search('sid') ?></td>
            <td><?= html_search('sname') ?></td>
            <td><?= html_select('sstatus', $_status, 'All') ?></td>
            <td><button type="submit">Search</button></td>
        </tr>
    </form>

    <?php if (empty($arr)): ?>
        <tr>
            <p>No brand records found...</p>
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

<!-- <?= $p->html("sort=$sort&dir=$dir") ?> -->

<?php
include '../_foot.php'; ?>