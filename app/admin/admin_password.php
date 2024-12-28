<?php
require '../_base.php';

auth_admin();

$admin_id = $_admin->admin_id;

if (is_get()) {
    $stm = $_db->prepare('SELECT * FROM admin WHERE admin_id = ?');
    $stm->execute([$admin_id]);
    $admin = $stm->fetch();

    extract((array)$admin);
}

if (is_post()) {
    $admin_current_password = req('admin_current_password');
    $admin_new_password = req('admin_new_password');
    $admin_confirm_password = req('admin_confirm_password');

    $stm = $_db->prepare('SELECT admin_password FROM admin WHERE admin_id = ?');
    $stm->execute([$admin_id]);
    $admin = $stm->fetch();

    if (!$admin) {
        $_err['admin_password'] = 'Admin not found.';
    } else {
        $admin_password = $admin->admin_password;
    }

    // Validate current password
    if ($admin_current_password == '') {
        $_err['admin_current_password'] = 'Required';
    } else if (sha1($admin_current_password) != $admin_password) {
        $_err['admin_current_password'] = 'Incorrect password';
    }

    // Validate new password
    if ($admin_new_password == '') {
        $_err['admin_new_password'] = 'Required';
    } else if (strlen($admin_new_password) > 100) {
        $_err['admin_new_password'] = 'Maximum length 100';
    } else if (strlen($admin_new_password) < 8) {
        $_err['admin_new_password'] = 'Minimum length 8';
    } else if ($admin_new_password == $admin_current_password) {
        $_err['admin_new_password'] = 'New password must be different';
    }

    // Validate confirm password
    if ($admin_confirm_password == '') {
        $_err['admin_confirm_password'] = 'Required';
    } else if ($admin_new_password != $admin_confirm_password) {
        $_err['admin_confirm_password'] = 'Password does not match';
    }

    if (count($_err) == 0) {
        $stm = $_db->prepare('UPDATE admin SET admin_password = SHA1(?) WHERE admin_id = ?');
        $stm->execute([$admin_new_password, $admin_id]);

        temp('info', 'Password updated');
        redirect('admin_home.php');
    }
}

$_title = '';
include '_admin_head.php';
?>

<div class="profile">
    <h1>Change Password</h2>
    <form method="post">
        <label for="admin_current_password">Current Password</label>
        <?= html_password('admin_current_password', 'maxlength="100"') ?>
        <?= err('admin_current_password') ?>

        <label for="admin_new_password">New Password</label>
        <?= html_password('admin_new_password', 'maxlength="100"') ?>
        <?= err('admin_new_password') ?>

        <label for="admin_confirm_password">Confirm Password</label>
        <?= html_password('admin_confirm_password', 'maxlength="100"') ?>
        <?= err('admin_confirm_password') ?>
        <button type="submit">Update</button>
        <button type="reset">Reset</button>
    </form>
</div>