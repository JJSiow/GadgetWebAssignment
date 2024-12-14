<?php
require '../_base.php';

$no_of_admin = $_db->query('SELECT COUNT(*) FROM admin')->fetchColumn();

if (is_get()) {
    if ($no_of_admin >= 10) {
        temp('info', 'Maximum 10 admins allowed');
        redirect('admin_list.php');
    }
}

if (is_post()) {
    $admin_name = req('admin_name');
    $admin_phone_no = req('admin_phone_no');
    $admin_email = req('admin_email');
    $admin_password = req('admin_password');
    $confirm_password = req('confirm_password');
    $photo = get_file('admin_profile_pic');
    // $admin_profile_pic = $_SESSION['admin_profile_pic'];

    // Validate name
    if ($admin_name == '') {
        $_err['admin_name'] = 'Required';
    }
    else if (strlen($admin_name) > 100) {
        $_err['admin_name'] = 'Maximum length 100';
    }

    if ($admin_phone_no == '') {
        $_err['admin_phone_no'] = 'Required';
    }
    else if (!ctype_digit($admin_phone_no)) {
        $_err['admin_phone_no'] = 'Invalid phone number';
    }
    else if (strlen($admin_phone_no) > 11 || strlen($admin_phone_no) < 10) {
        $_err['admin_phone_no'] = 'Should be 10-11 digits';
    }
    else if (is_exists($admin_phone_no, 'admin', 'admin_phone_no')) {
        $_err['admin_phone_no'] = 'Phone number already exists';
    }

    if ($admin_email == '') {
        $_err['admin_email'] = 'Required';
    }
    else if (strlen($admin_email) > 100) {
        $_err['admin_email'] = 'Maximum length 100';
    }
    else if (!filter_var($admin_email, FILTER_VALIDATE_EMAIL)) {
        $_err['admin_email'] = 'Invalid email format';
    }
    else if (is_exists($admin_email, 'admin', 'admin_email')) {
        $_err['admin_email'] = 'Email already exists';
    }

    // Validate password
    if ($admin_password == '') {
        $_err['admin_password'] = 'Required';
    }
    else if (strlen($admin_password) > 100) {
        $_err['admin_password'] = 'Maximum length 100';
    }
    else if (strlen($admin_password) < 8) {
        $_err['admin_password'] = 'Minimum length 8';
    }

    if ($admin_password != $confirm_password) {
        $_err['confirm_password'] = 'Password does not match';
    }

    // Validate profile picture
    if ($photo) {
        if (!str_starts_with($photo->type, 'image/')) {
            $_err['admin_profile_pic'] = 'Invalid file type';
        }
        else if ($photo->size > 8 * 1024 * 1024) {
            $_err['admin_profile_pic'] = 'Maximum 8MB';
        }
    }
    else {
        $_err['admin_profile_pic'] = 'Required';
    }

    if (count($_err) == 0) {
        $admin_id = sprintf('A%02d', $no_of_admin + 1);

        $admin_profile_pic = save_photo($photo, '../photos');

        $stm = $_db->prepare('INSERT INTO admin (admin_id, admin_name, admin_phone_no, admin_email, admin_password, admin_profile_pic, admin_status, is_super_admin) VALUES (?, ?, ?, ?, SHA1(?), ?, ?, ?)');
        $stm->execute([$admin_id, $admin_name, $admin_phone_no, $admin_email, $admin_password, $admin_profile_pic, 'Active', 'N']);

        temp('info', 'Admin created');
        redirect('admin_list.php');
    }
}

// ----------------------------------------------------------------------------
$_title = 'Admin | Create New Admin';
include '../_head.php';
?>

<form method="post" class="form" enctype="multipart/form-data">
    <label for="admin_name">Name</label>
    <?= html_text('admin_name', 'maxlength="100"') ?>
    <?= err('admin_name') ?>

    <label for="admin_phone_no">Phone No</label>
    <?= html_text('admin_phone_no', 'maxlength="11"') ?>
    <?= err('admin_phone_no') ?>

    <label for="admin_email">Email</label>
    <?= html_text('admin_email', 'maxlength="100"') ?>
    <?= err('admin_email') ?>

    <label for="admin_password">Password</label>
    <?= html_password('admin_password', 'maxlength="100"') ?>
    <?= err('admin_password') ?>

    <label for="confirm_password">Confirm Password</label>
    <?= html_password('confirm_password', 'maxlength="100"') ?>
    <?= err('confirm_password') ?>

    <label for="admin_profile_pic">Profile Picture</label>
    <div class="drop-zone upload" tabindex="0">
        <p>Drag and drop a photo here or click to select a photo</p>
        <?= html_file('admin_profile_pic', 'image/*') ?>
        <img class="preview" src="../photos/<?= $admin_profile_pic ?>">
    </div>
    <?= err('admin_profile_pic') ?>

    <section>
        <button type="submit">Create</button>
        <button type="reset">Reset</button>
    </section>
</form>