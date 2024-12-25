<?php
require '../_base.php';

auth_super_admin();

$admin_id = req('admin_id');

if (is_get()) {
    $stm = $_db->prepare('SELECT * FROM admin WHERE admin_id = ?');
    $stm->execute([$admin_id]);
    $admin = $stm->fetch();

    extract((array)$admin);
    $_SESSION['admin_profile_pic'] = $admin->admin_profile_pic;
}

if (is_post()) {
    $photo = get_file('admin_profile_pic');
    $admin_profile_pic = $_SESSION['admin_profile_pic'];

    if (count($_err) == 0) {
        if ($photo) {
            unlink("../photos/$admin_profile_pic");
            $admin_profile_pic = save_photo($photo, '../photos');
        }

        $stm = $_db->prepare('UPDATE admin SET admin_profile_pic = ? WHERE admin_id = ?');
        $stm->execute([$admin_profile_pic, $admin_id]);

        temp('info', 'Admin profile picture updated.');
        redirect('admin_list.php');
    }
    else {
        temp('info', 'Please check the error(s).');
    }

}

// ----------------------------------------------------------------------------
$_title = 'Admin | Edit Admin Profile Picture';
include '../_head.php';
?>

<form method="post" class="form" enctype="multipart/form-data">
    <label for="admin_id">Admin ID</label>
    <b><?= $admin_id ?></b>
    <?= err('admin_id') ?>

    <label for="admin_name">Name</label>
    <?= html_text('admin_name', 'maxlength="100" disabled') ?>
    <?= err('admin_name') ?>

    <label for="admin_phone_no">Phone No</label>
    <?= html_text('admin_phone_no', 'maxlength="11" disabled') ?>
    <?= err('admin_phone_no') ?>

    <label for="admin_email">Email</label>
    <?= html_text('admin_email', 'maxlength="100" disabled') ?>
    <?= err('admin_email') ?>

    <label for="admin_profile_pic">Profile Picture</label>
    <div class="drop-zone upload" tabindex="0">
        <p>Drag and drop a photo here or click to select a photo</p>
        <?= html_file('admin_profile_pic', 'image/*') ?>
        <img class="preview" src="../photos/<?= $admin_profile_pic ?>">
    </div>
    <?= err('admin_profile_pic') ?>

    <section>
        <button>Update</button>
    </section>
</form>