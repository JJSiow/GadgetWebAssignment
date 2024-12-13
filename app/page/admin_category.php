<?php
require_once '../_base.php';

$category_error = isset($_SESSION['category_error']) ? $_SESSION['category_error'] : null;

unset($_SESSION['category_error']);
// (1) Sorting
$fields = [
    'category_id' => 'Category ID',
    'category_name' => 'Category Name',
    'category_status' => 'Category Status',
    'action' => 'Action'
];
$sort = req('sort');
key_exists($sort, $fields) || $sort = 'category_id';

$dir = req('dir');
in_array($dir, ['asc', 'desc']) || $dir = 'asc';

$arr = $_db->query("SELECT * FROM category ORDER BY $sort $dir")->fetchAll();

if (is_post()) {
    $sid = req('sid');
    $sname = req('sname');
    $sstatus = req('sstatus');

    $stm = $_db->prepare('SELECT * FROM category
    WHERE category_name LIKE ?
    AND (category_id = ? OR ?)
    AND (category_status = ? OR ?)');

    $stm->execute(["%$sname%", $sid, $sid == null, $sstatus, $sstatus == null]);

    $arr = $stm->fetchAll();
}

$_title = 'Gadget Category';
include '../_head.php';
?>

<form action="add_category.php" method="post">
    <input type="text" id="ctg_name" name="ctg_name" placeholder="Add new category">
    <button class="button addProdButton">Add</button>
    <?php if ($category_error): ?>
        <p class="error"><?= htmlspecialchars($category_error) ?></p>
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
            <p>No category records found...</p>
        </tr>
    <?php else: ?>
        <?php foreach ($arr as $category): ?>
            <tr>
                <td><?= $category->category_id ?></td>
                <td class="edit" data-id="<?= $category->category_id ?>" data-update-url="update_category.php"><?= $category->category_name ?></td>
                <td><?= $category->category_status ?></td>
                <td>
                    <?php if ($category->category_status == 'Active'): ?>
                        <a data-post="delete_category.php?action=Unactive&id=<?= $category->category_id ?>" data-confirm='Are you sure you want to unactivate this category?'>Unactivate</a>
                    <?php else: ?>
                        <a data-post="delete_category.php?action=Active&id=<?= $category->category_id ?>" data-confirm='Are you sure you want to activate this category?'>Activate</a>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach ?>
    <?php endif; ?>
</table>

<!-- <?= $p->html("sort=$sort&dir=$dir") ?> -->

<?php
include '../_foot.php'; ?>