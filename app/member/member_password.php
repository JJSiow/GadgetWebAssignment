<?php
require '../_base.php';

// Get member ID from URL parameter
// $id = req('member_id');
$id = "M00002";

if (is_post()) {
    $member_current_password = req('member_current_password');
    $member_new_password = req('member_new_password');
    $member_confirm_password = req('member_confirm_password');

    $stm = $_db->prepare('SELECT member_password FROM member WHERE member_id = ?');
    $stm->execute([$id]);
    $member = $stm->fetch();

    if (!$member) {
        $_err['member_password'] = 'Member not found.';
    } else {
        $member_password = $member->member_password;
    }

    // Validate current password
    if ($member_current_password == '') {
        $_err['member_current_password'] = 'Required';
    }
    else if ($member_current_password != $member_password) {
        $_err['member_current_password'] = 'Incorrect password';
    }

    // Validate new password
    if ($member_new_password == '') {
        $_err['member_new_password'] = 'Required';
    }
    else if (strlen($member_new_password) > 100) {
        $_err['member_new_password'] = 'Maximum length 100';
    }
    else if (strlen($member_new_password) < 8) {
        $_err['member_new_password'] = 'Minimum length 8';
    }
    else if ($member_new_password == $member_current_password) {
        $_err['member_new_password'] = 'New password must be different';
    }

    // Validate confirm password
    if ($member_confirm_password == '') {
        $_err['member_confirm_password'] = 'Required';
    }
    else if ($member_new_password != $member_confirm_password) {
        $_err['member_confirm_password'] = 'Password does not match';
    }

    if (count($_err) == 0) {
        $stm = $_db->prepare('UPDATE member SET member_password = ? WHERE member_id = ?');
        $stm->execute([$member_new_password, $id]);

        temp('info', 'Member password updated.');
        redirect('member.php');
    }
}

$_title = 'Member | Update Member Password';
include '../_head.php';

?>



<form method="post" class="form">
    <label for="member_current_password">Current Password</label>
    <?= html_password('member_current_password', 'maxlength="100"') ?>
    <?= err('member_current_password') ?>
    
    <label for="member_new_password">New Password</label>
    <?= html_password('member_new_password', 'maxlength="100"') ?>
    <?= err('member_new_password') ?>

    <label for="member_confirm_password">Confirm Password</label>
    <?= html_password('member_confirm_password', 'maxlength="100"') ?>
    <?= err('member_confirm_password') ?>

    <section>
        <button type="submit">Save</button>
        <a href="member.php">Cancel</a>
    </section>
</form>

