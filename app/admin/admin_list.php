<?php
require '../_base.php';

// $id = req('id');
// $id = "A01";

// $stm = $_db->prepare('SELECT * FROM admin WHERE admin_id = ?');
// $stm->execute([$id]);
// $admin = $stm->fetch();

$arr = $_db->query('SELECT * FROM admin')->fetchAll();

// ----------------------------------------------------------------------------
$_title = 'Admin | Admin Management';
include '../_head.php';
?>

<p><button data-get=create_new_admin.php>Create New Admin</button></p>

<p><?= count($arr) ?> record(s)</p>

<table class="table">
    <tr>
        <?php table_headers($_admin_attr) ?>
    </tr>

    <?php foreach ($arr as $a) : ?>
        <tr>
            <td><?= $a->admin_id ?></td>
            <td><?= $a->admin_name ?></td>
            <td><?= $a->admin_phone_no ?></td>
            <td><?= $a->admin_email ?></td>
            <td><img src="../photos/<?= $a->admin_profile_pic ?>" width="100"></td>
            <td><?= $a->admin_status ?></td>
            <td><button data-get="edit_admin_profile_pic.php?admin_id=<?= $a->admin_id ?>">Update</a></td>
            <td><button data-post="update_admin_status.php?admin_id=<?= $a->admin_id ?>" data-confirm="Are you sure you want to change the status of this member?">Change Status</button></td>
        </tr>
    <?php endforeach ?>
</table>