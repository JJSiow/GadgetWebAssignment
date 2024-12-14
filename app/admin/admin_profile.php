<?php
require '../_base.php';

$id = req('admin_id');
$id = "A02";

if (is_get()) {
    $stm = $_db->prepare('SELECT * FROM admin WHERE admin_id = ?');
    $stm->execute([$id]);
    $admin = $stm->fetch();

    if (!$admin) {
        temp('info', 'Admin not found.');
        redirect('admin_list.php');
    }

    extract((array)$admin);
    $_SESSION['admin_profile_pic'] = $admin->admin_profile_pic;
}

if (is_post()) {
    $admin_id = req('admin_id');
    $admin_name = req('admin_name');
    $admin_phone_no = req('admin_phone_no');
    $admin_email = req('admin_email');
    $photo = get_file('admin_profile_pic');
    $admin_profile_pic = $_SESSION['admin_profile_pic'];

    $admin = $_db->query('SELECT * FROM admin WHERE admin_id = ' . $_db->quote($id))->fetch(PDO::FETCH_OBJ);

    // Validate name
    if ($admin_name == '') {
        $_err['admin_name'] = 'Required';
    }
    else if (strlen($admin_name) > 100) {
        $_err['admin_name'] = 'Maximum length 100';
    }

    // Validate phone number
    if ($admin_phone_no == '') {
        $_err['admin_phone_no'] = 'Required';
    }
    else if (strlen($admin_phone_no) > 11 || strlen($admin_phone_no) < 10) {
        $_err['admin_phone_no'] = 'Should be 10-11 digits';
    }
    else if (!ctype_digit($admin_phone_no)) {
        $_err['admin_phone_no'] = 'Invalid phone number';
    }
    else if (is_exists($admin_phone_no, 'admin', 'admin_phone_no') && $admin->admin_phone_no != $admin_phone_no) {
        $_err['admin_phone_no'] = 'Phone number already exists';
    }

    // Validate email
    if ($admin_email == '') {
        $_err['admin_email'] = 'Required';
    }
    else if (!filter_var($admin_email, FILTER_VALIDATE_EMAIL)) {
        $_err['admin_email'] = 'Invalid email address';
    }
    else if (is_exists($admin_email, 'admin', 'admin_email') && $admin->admin_email != $admin_email) {
        $_err['admin_email'] = 'Email already exists';
    }

    if ($photo) {
        if (!str_starts_with($photo->type, 'image/')) {
            $_err['admin_profile_pic'] = 'Invalid file type';
        }
        else if ($photo->size > 8 * 1024 * 1024) {
            $_err['admin_profile_pic'] = 'Maximum 8MB';
        }
    }

    if (count($_err) == 0) {
        if ($photo) {
            unlink("../photos/$admin_profile_pic");
            $admin_profile_pic = save_photo($photo, '../photos');
        }

        $stm = $_db->prepare('UPDATE admin SET admin_name = ?, admin_phone_no = ?, admin_email = ?, admin_profile_pic = ? WHERE admin_id = ?');
        $stm->execute([$admin_name, $admin_phone_no, $admin_email, $admin_profile_pic, $id]);

        temp('info', 'Admin profile updated.');
        redirect('../index.php');
    }
    else {
        temp('info', 'Please check the error(s).');
    }
}

$_title = 'Admin | Edit Admin Profile';
include '../_head.php';
?>

<form method="post" class="form" enctype="multipart/form-data">
    <label for="admin_id">Admin ID</label>
    <b><?= $admin_id ?></b>
    <?= err('admin_id') ?>

    <label for="admin_name">Name</label>
    <?= html_text('admin_name', 'maxlength="100"') ?>
    <?= err('admin_name') ?>

    <label for="admin_phone_no">Phone No</label>
    <?= html_text('admin_phone_no', 'maxlength="11"') ?>
    <?= err('admin_phone_no') ?>

    <label for="admin_email">Email</label>
    <?= html_text('admin_email', 'maxlength="100"') ?>
    <?= err('admin_email') ?>

    <label for="admin_profile_pic">Profile Picture</label>
    <div class="drop-zone upload" tabindex="0">
        <p>Drag and drop a file here or click to select a file</p>
        <?= html_file('admin_profile_pic', 'image/*') ?>
        <img class="preview" src="../photos/<?= $admin_profile_pic ?>">
    </div>
    <?= err('admin_profile_pic') ?>

    <section>
        <button type="submit">Save</button>
    </section>
</form>