<?php
require '../_base.php';

auth_admin();

$member_id = req('member_id');

if (is_post()) {
    $stm = $_db->prepare('SELECT * FROM member WHERE member_id = ?');
    $stm->execute([$member_id]);
    $member = $stm->fetch();

    extract((array)$member);
    $_SESSION['member_profile_pic'] = $member->member_profile_pic;

    if ($member->member_status == 'Deleted') {
        temp('info', 'Member deleted.');
        redirect('member_list.php');
    }
}

// ----------------------------------------------------------------------------
$_title = 'Admin | Edit Member Profile Picture';
include '../admin/_admin_head.php';
?>

<form method="post" class="profile" enctype="multipart/form-data" action="update_member_profile_pic.php">
    <label for="member_id">Member ID</label>
    <b><?= $member_id ?></b>
    <input type="hidden" name="member_id" value="<?= $member_id ?>">
    <?= err('member_id') ?>

    <label for="member_name">Name</label>
    <?= html_text('member_name', 'maxlength="100" disabled') ?>
    <?= err('member_name') ?>

    <label for="member_phone_no">Phone No</label>
    <?= html_text('member_phone_no', 'maxlength="11" disabled') ?>
    <?= err('member_phone_no') ?>

    <label for="member_gender">Gender</label>
    <?= html_radios('member_gender', $_genders, 'disabled') ?>
    <?= err('member_gender') ?>

    <label for="member_email">Email</label>
    <?= html_text('member_email', 'maxlength="100" disabled') ?>
    <?= err('member_email') ?>

    <label for="member_profile_pic">Profile Picture</label>
    <div class="drop-zone upload" tabindex="0">
        <p>Drag and drop a photo here or click to select a photo</p>
        <?= html_file('member_profile_pic', 'image/*') ?>
        <img class="preview" src="../photos/<?= $member_profile_pic ?>">
    </div>
    <?= err('member_profile_pic') ?>

    <section>
        <button type="submit">Save</button>
    </section>
</form>