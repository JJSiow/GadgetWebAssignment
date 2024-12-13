<?php
require '../_base.php';

// Get member ID from URL parameter
$id = req('id');
// $id = "M00002";

if (is_get()) {
    $member_id = req('member_id');

    $stm = $_db->prepare('SELECT * FROM member WHERE member_id = ?');
    $stm->execute([$id]);
    $member = $stm->fetch();

    if (!$member) {
        temp('info', 'Member not found.');
        redirect('member.php');
    }

    extract((array)$member);
    $_SESSION['member_profile_pic'] = $member->member_profile_pic;
    // echo "<pre>";
    // print_r($member);
    // echo "</pre>";
    // die();
}

if (is_post()) {
    $member_id = req('member_id');
    $member_name = req('member_name');
    $member_phone_no = req('member_phone_no');
    $member_gender = req('member_gender');
    $member_email = req('member_email');
    $shipping_address = req('shipping_address');
    $photo = get_file('member_profile_pic');
    $member_profile_pic = $_SESSION['member_profile_pic'];

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
    else if (strlen($member_phone_no) > 11) {
        $_err['member_phone_no'] = 'Maximum length 11';
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

    // Validate shipping address
    if ($shipping_address == '') {
        $_err['shipping_address'] = 'Required';
    }

    //$f = get_file('member_profile_pic');
    if ($photo) {
        if (!str_starts_with($photo->type, 'image/')) {
            $_err['member_profile_pic'] = 'Must be image';
        }
        else if ($photo->size > 8 * 1024 * 1024) {
            $_err['member_profile_pic'] = 'Maximum 8MB';
        }
    }


    if (count($_err) == 0) {
        if ($photo) {
            unlink("../photos/$member_profile_pic");
            $member_profile_pic = save_photo($photo, '../photos');
        }

        $stm = $_db->prepare('UPDATE member SET member_name = ?, member_phone_no = ?, member_gender = ?, member_email = ?, shipping_address = ?, member_profile_pic = ? WHERE member_id = ?');
        $stm->execute([$member_name, $member_phone_no, $member_gender, $member_email, $shipping_address, $member_profile_pic, $id]);

        temp('info', 'Member information updated.');
        redirect('member.php');
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
    <b><?= $id ?></b>
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

    <label for="shipping_address">Shipping Address</label>
    <?= html_text('shipping_address', 'width=500px') ?>
    <?= err('shipping_address') ?>

    <label for="member_profile_pic">Profile Picture</label>
    <label class="upload" tabindex="0">
        <?= html_file('member_profile_pic', 'image/*', 'hidden') ?>
        <img src="../photos/<?= $member_profile_pic ?>">
    </label>
    <?= err('member_profile_pic') ?>

    <section>
        <button type="submit">Save</button>
        <a href="member.php">Cancel</a>
    </section>
</form>


<pre>
    <?php
    // print_r($_SESSION);
    // print_r($_FILES);
    ?>