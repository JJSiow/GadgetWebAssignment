<?php
require '../_base.php';
// ----------------------------------------------------------------------------

if (is_post()) {
    // Input
    $member_name       = req('member_name');
    $member_gender     = req('member_gender');
    $member_password     = req('member_password');
    $shipping_address     = req('shipping_address');
    $member_email     = req('member_email');
    $member_phone_no     = req('member_phone_no');
    $member_passwordconfirm     = req('member_passwordconfirm');

    // Validate name
    if ($member_name == '') {
        $_err['member_name'] = 'Required';
    } else if (strlen($member_name) > 100) {
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
    else if (is_exists($member_phone_no, 'member', 'member_phone_no')) {
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
    else if (is_exists($member_email, 'member', 'member_email')) {
        $_err['member_email'] = 'Email already exists';
    }

    // // Validate shipping address
    // if ($shipping_address == '') {
    //     $_err['shipping_address'] = 'Required';
    // }

    // Validate password
    if ($member_password == '') {
        $_err['member_password'] = 'Required';
    }
    else if (strlen($member_password) > 100) {
        $_err['member_password'] = 'Maximum length 100';
    }
    else if (strlen($member_password) < 8) {
        $_err['member_password'] = 'Minimum length 8';
    }

    if ($member_password == '') {
        $_err['member_password'] = 'Required';
    }

    if ($member_passwordconfirm == '') {
        $_err['member_passwordconfirm'] = 'Required';
    } else if ($member_passwordconfirm != $member_password) {
        $_err['member_passwordconfirm'] = 'Not Match';
    }

    if($_POST['g-recaptcha-response'] == ""){
        $_err['g-recaptcha-response'] = 'google recaptcha does not response';
    }else{

    $secret = '6Lc4AqUqAAAAAD3xemwtZ433ZDXxMIak1Eds0r9U';
    $verifyResponse = file_get_contents('https://www.google.com/recaptcha/api/siteverify?secret=' . $secret . '&response=' . $_POST['g-recaptcha-response']);
    $responseData = json_decode($verifyResponse);
    if (!$responseData->success) {
        $_err['g-recaptcha-response'] = 'robot validation failed';
    }
}

    // Output

    // TODO
    if (!$_err) {
            $no_of_member = $_db->query('SELECT COUNT(*) FROM member')->fetchColumn();
            $member_id = sprintf('M%05d', $no_of_member + 1);
        $member_EncrptPassword = sha1($member_password);
        $stm = $_db->prepare('INSERT INTO member
                                  (member_id, member_name, member_gender,member_phone_no,member_email,shipping_address,member_password,member_profile_pic,member_status)
                                  VALUES(?, ?, ?, ?, ?, ?, ?,?,?)');
        $stm->execute([$member_id, $member_name, $member_gender, $member_phone_no, $member_email, $member_address, $member_EncrptPassword, 'default_user.jpg', 'Inactive']);

        // TODO: (2) Generate token id
        $id = sha1(uniqid() . rand());

        // TODO: (3) Delete old and insert new token
        $stm = $_db->prepare('
            DELETE FROM token WHERE user_id = ? AND token_type = ?;

            INSERT INTO token (id, expire, user_id,token_type)
            VALUES (?, ADDTIME(NOW(), "00:05"), ?,?);
        ');
        $stm->execute([$member_id,'Activate', $id, $member_id,'Activate']);

        // TODO: (4) Generate token url
        $url = base("account_verify.php?id=$id");

        // TODO: (5) Send email
        $m = get_mail();
        $m->addAddress($member_email, $member_name);
        $m->isHTML(true);
        $m->Subject = 'Activate Account';
        $m->Body = "
            <img src='cid:photo'
                 style='width: 200px; height: 200px;
                        border: 1px solid #333'>
            <p>Dear $member_name,<p>
            <h1 style='color: red'>Activate Account</h1>
            <p>
            If this action is not done by yourself. Please ignore the email.<br>
                Please click <a href='$url'>here</a>
                to activate your account.
            </p>
            <p>From, 😺 Admin</p>
        ";
        $m->send();

        temp('info', 'Activate Email sent');
        redirect('/');
    }
}
// ----------------------------------------------------------------------------
$_title = 'Register';
include '../_head.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
</head>

<form method="post" class="form">


    <label for="member_name">Name</label>
    <?= html_text('member_name', 'maxlength="100"') ?>
    <?= err('member_name') ?>

    <label for="member_phone_no">Phone Number</label>
    <?= html_text('member_phone_no', 'maxlength="11"') ?>
    <?= err('member_phone_no') ?>

    <label>Gender</label>
    <?= html_radios('member_gender', $_genders) ?>
    <?= err('member_gender') ?>

    <label for="member_email">Email</label>
    <?= html_text('member_email', 'maxlength="40"') ?>
    <?= err('member_email') ?>

    <label for="shipping_address">Shipping Address</label>
    <?= html_text('shipping_address', 'maxlength="100"') ?>
    <?= err('shipping_address') ?>

    <label for="member_password">Password</label>
    <?= html_password('member_password', 'maxlength="100"') ?>
    <?= err('member_password') ?>

    <label for="member_passwordconfirm">Confirm Password</label>
    <?= html_password('member_passwordconfirm', 'maxlength="100"') ?>
    <?= err('member_passwordconfirm') ?>

    <div class="g-recaptcha" data-sitekey="6Lc4AqUqAAAAAJXaWZC7V2bFfeasBlRlqdwOZLBq"></div>
    <?= err('g-recaptcha-response') ?>

    <section>
        <button>Submit</button>
        <button type="reset">Reset</button>
    </section>
</form>

<a href="/member/login.php">Login</a>
<?php
include '../_foot.php';
