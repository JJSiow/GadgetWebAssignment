<?php
require '../_base.php';

auth_super_admin();

$arr = $_db->query('SELECT * FROM admin WHERE is_super_admin = "N"')->fetchAll();

// ----------------------------------------------------------------------------
$_title = 'Admin | Admin Management';
include '../admin/_admin_head.php';
?>

<br>
<p><button data-get=create_new_admin.php>Create New Admin</button></p>
<br>
<p><?= count($arr) ?> record(s)</p>

<table class="table">
    <tr>
        <?php table_headers($_admin_attr) ?>
        <th>Profile Pic</th>
    </tr>

    <?php foreach ($arr as $a) : ?>
        <tr>
            <?php foreach ($_admin_attr as $field => $label) : ?>
                <td class="status-<?= strtolower($a->admin_status) ?>"><?= $a->$field ?></td>
            <?php endforeach ?>
            <td class="status-<?= strtolower($a->admin_status) ?>"><img src="../photos/<?= $a->admin_profile_pic ?>" width="100"></td>
            <td class="status-<?= strtolower($a->admin_status) ?>"><button data-post="update_admin_status.php?admin_id=<?= $a->admin_id ?>" data-confirm="Are you sure you want to change the status of this member?">Change Status</button></td>
        </tr>
    <?php endforeach ?>
</table>