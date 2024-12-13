<?php
require '../_base.php';
// ----------------------------------------------------------------------------

$sort = req('sort');
if (!key_exists($sort, $_member_attr)) {
    $sort = 'member_id'; // Default sort column
}

$dir = req('dir');
if (!in_array($dir, ['asc', 'desc'])) {
    $dir = 'asc'; // Default sort direction
}

// Add the sorting clause to the query
$order_clause = "ORDER BY $sort $dir";

$page = req('page', 1);

$search_by = req('search_by');
$allowed_fields = ['member_id', 'member_name', 'member_phone_no', 'member_gender', 'member_email', 'shipping_address', 'member_status'];
if (!in_array($search_by, $allowed_fields)) {
    $search_by = 'member_id';
}

$search_value = req('search_value');
$params = ["%$search_value%"];
$where_clause = "WHERE $search_by LIKE ?";

require_once '../lib/SimplePager.php';
$p = new SimplePager("SELECT * FROM member $where_clause $order_clause", $params, 10, $page);
$arr = $p->result;

// Capture existing GET parameters and remove 'page'
$query_params = $_GET;
unset($query_params['page']);
$query_string = http_build_query($query_params);

// ----------------------------------------------------------------------------
$_title = 'Member | List of Members';
include '../_head.php';
?>

<form>
    <?= html_select('search_by', $_member_attr) ?>
    <?= html_search('search_value') ?>
    <button>Search</button>
</form>

<p>
    <?= $p->count ?> of <?= $p->item_count ?> record(s) |
    Page <?= $p->page ?> of <?= $p->page_count ?>
</p>

<table class="table">
    <tr>
        <?php table_headers($_member_attr, $sort, $dir, "$query_string&page=$page") ?>
    </tr>

    <?php foreach ($arr as $s): ?>
    <tr>
        <td><?= $s->member_id ?></td>
        <td><?= $s->member_name ?></td>
        <td><?= $s->member_phone_no ?></td>
        <td><?= $s->member_gender ?></td>
        <td><?= $s->member_email ?></td>
        <td><?= $s->shipping_address ?></td>
        <td><?= $s->member_status ?></td>
        <td><img src="../photos/<?= $s->member_profile_pic ?>"  width="100"></td>
        <td><button data-post="member_status.php?member_id=<?= $s->member_id ?>&page=<?= $page ?>" data-confirm="Are you sure you want to change the status of this member?">Change Status</button></td>
        <td><button data-get="admin_edit_profile_pic.php?id=<?= $s->member_id ?>">Edit Profle Pic</a></button></td>

    </tr>
    <?php endforeach ?>
</table>

<br>

<?= $p->html("search_by=$search_by&search_value=$search_value") ?>
