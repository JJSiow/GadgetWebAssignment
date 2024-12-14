<?php
require '../_base.php';
// ----------------------------------------------------------------------------

if (is_post()) {
    // Input
    $member_name       = req('member_name');
    $member_gender     = req('member_gender');
    $member_password     = req('member_password');
    $member_address     = req('member_address');
    $member_email     = req('member_email');
    $member_phoneNum     = req('member_phoneNum');


    // validate email
    if (!is_unique($member_email, 'member', 'member_email')) {
        $_err['member_email'] = 'Email had been registered';
    }
else if(!filter_var($member_email, FILTER_VALIDATE_EMAIL)){
    $_err['member_email'] = 'Invalid email format';
}

    
    // Validate name
    if ($member_name == '') {
        $_err['member_name'] = 'Required';
    }
    else if (strlen($member_name) > 100) {
        $_err['member_name'] = 'Maximum length 100';
    }

    // Validate gender
    if ($member_gender == '') {
        $_err['member_gender'] = 'Required';
    }
    else if (!array_key_exists($member_gender, $_genders)) {
        $_err['member_name'] = 'Invalid value';
    }

    // Output
    if (!$_err) {
        // TODO
        if (!$_err) {
            do{
            $code = rand(10000,99999);
            $member_id = "M".$code;
            }while(!is_unique($member_id, 'member', 'member_id'));
            $member_EncrptPassword = sha1($member_password);
            $stm = $_db->prepare('INSERT INTO member
                                  (member_id, member_name, member_gender,member_phone_no,member_email,shipping_address,member_password,member_profile_pic)
                                  VALUES(?, ?, ?, ?, ?, ?, ?,?)');
            $stm->execute([$member_id, $member_name, $member_gender,$member_phoneNum,$member_email,$member_address,$member_EncrptPassword,'default_user.jpg']);
            
            temp('info', 'Record inserted');
            redirect('/');
        }
    }
}
// ----------------------------------------------------------------------------
$_title = 'Register';
include '../_head.php';
?>

<form method="post" class="form">


    <label for="member_name">Name</label>
    <?= html_text('member_name', 'maxlength="100"') ?>
    <?= err('member_name') ?>

    <label for="member_phoneNum">Phone Number</label>
    <?= html_text('member_phoneNum', 'maxlength="11"') ?>
    <?= err('member_phoneNum') ?>

    <label>Gender</label>
    <?= html_radios('member_gender', $_genders) ?>
    <?= err('member_gender') ?>

    <label for="member_email">Email</label>
    <?= html_text('member_email', 'maxlength="40"') ?>
    <?= err('member_email') ?>

    <label for="member_address">Shipping Address</label>
    <?= html_text('member_address', 'maxlength="100"') ?>
    <?= err('member_address') ?>

    <label for="member_password">Password</label>
    <?= html_password('member_password', 'maxlength="100"') ?>
    <?= err('member_password') ?>

    <section>
        <button>Submit</button>
        <button type="reset">Reset</button>
    </section>
</form>

<a href="/index.php">Login</a>
<?php
include '../_foot.php';