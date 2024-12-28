<?php
require '../_base.php';

auth_member();

// Get member ID from URL parameter
$member_id = $_member->member_id;
// $member_id = "M00001";

if (is_get()) {
    $stm = $_db->prepare('SELECT * FROM member WHERE member_id = ?');
    $stm->execute([$member_id]);
    $member = $stm->fetch();

    extract((array)$member);
    $_SESSION['member_profile_pic'] = $member->member_profile_pic;
}

if (is_post()) {
    $member_name = req('member_name');
    $member_phone_no = req('member_phone_no');
    $member_gender = req('member_gender');
    $member_email = req('member_email');
    $shipping_address = req('shipping_address');
    $photo = get_file('member_profile_pic');
    $member_profile_pic = $_SESSION['member_profile_pic'];

    $member = $_db->query('SELECT * FROM member WHERE member_id = ' . $_db->quote($member_id))->fetch(PDO::FETCH_OBJ);

    // Validate name
    if ($member_name == '') {
        $_err['member_name'] = 'Required';
    }
    else if (strlen($member_name) > 100) {
        $_err['member_name'] = 'Maximum length 100';
    }

    // Validate phone number
    if ($member_phone_no == '') {
        $_err['member_phone_no'] = 'Required';
    }
    else if (strlen($member_phone_no) > 11 || strlen($member_phone_no) < 10) {
        $_err['member_phone_no'] = 'Should be 10-11 digits';
    }
    else if (!ctype_digit($member_phone_no)) {
        $_err['member_phone_no'] = 'Invalid phone number';
    }
    else if (is_exists($member_phone_no, 'member', 'member_phone_no') && $member->member_phone_no != $member_phone_no) {
        $_err['member_phone_no'] = 'Phone number already exists';
    }

    // Validate gender
    if ($member_gender == '') {
        $_err['member_gender'] = 'Required';
    }
    else if (!array_key_exists($member_gender, $_genders)) {
        $_err['member_gender'] = 'Invalid value';
    }

    // Validate email
    if ($member_email == '') {
        $_err['member_email'] = 'Required';
    }
    else if (strlen($member_email) > 100) {
        $_err['member_email'] = 'Maximum length 100';
    }
    else if (!filter_var($member_email, FILTER_VALIDATE_EMAIL)) {
        $_err['member_email'] = 'Invalid email format';
    }
    else if (is_exists($member_email, 'member', 'member_email') && $member->member_email != $member_email) {
        $_err['member_email'] = 'Email already exists';
    }

    // Validate shipping address
    if ($shipping_address == '') {
        $_err['shipping_address'] = 'Required';
    }

    //$f = get_file('member_profile_pic');
    if ($photo) {
        if (!str_starts_with($photo->type, 'image/')) {
            $_err['member_profile_pic'] = 'Invalid file type';
        }
        else if ($photo->size > 8 * 1024 * 1024) {
            $_err['member_profile_pic'] = 'Maximum 8MB';
        }
    }


    if (count($_err) == 0) {
        // $member_profile_pic = $_SESSION['member_profile_pic'];
        if ($photo) {
            unlink("../photos/$member_profile_pic");
            $member_profile_pic = save_photo($photo, '../photos');
        }

        $stm = $_db->prepare('UPDATE member SET member_name = ?, member_phone_no = ?, member_gender = ?, member_email = ?, member_profile_pic = ? WHERE member_id = ?');
        $stm->execute([$member_name, $member_phone_no, $member_gender, $member_email, $member_profile_pic, $member_id]);

        // Refresh session data with updated member information
        $stm = $_db->prepare('SELECT * FROM member WHERE member_id = ?');
        $stm->execute([$member_id]);
        $updated_member = $stm->fetch(PDO::FETCH_OBJ);

        $_SESSION['member'] = $updated_member;
        // $_member = $updated_member;

        temp('info', 'Member information updated.');
        redirect('../index.php');
    }
    else {
        temp('info', 'Please check the error(s).');
    }

}

// ----------------------------------------------------------------------------
$_title = 'Member | Edit Member Profile';
include '../_head.php';
?>

<form method="post" class="form" enctype="multipart/form-data">
    <label for="member_id">Member ID</label>
    <b><?= $member_id ?></b>
    <?= err('member_id') ?>

    <label for="member_name">Name</label>
    <?= html_text('member_name', 'maxlength="100"') ?>
    <?= err('member_name') ?>

    <label for="member_phone_no">Phone No</label>
    <?= html_text('member_phone_no', 'maxlength="11"') ?>
    <?= err('member_phone_no') ?>

    <label for="member_gender">Gender</label>
    <?= html_radios('member_gender', $_genders) ?>
    <?= err('member_gender') ?>

    <label for="member_email">Email</label>
    <?= html_text('member_email', 'maxlength="100"') ?>
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
        <button type="button" data-post="delete_member_account.php?member_id=<?= $member_id ?>" data-confirm="Are you sure you want to delete your account permanently?">Delete Account</button>
    </section>
</form>