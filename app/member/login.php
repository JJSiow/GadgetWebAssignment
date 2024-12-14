<?php
require '../_base.php';
// ----------------------------------------------------------------------------
if (!empty($_SESSION["member"])) {
    redirect('../home.php'); 
}
if (is_post()) {

    // Input
    $member_id         = req('member_id');
    $member_password     = req('member_password');
    $member_email     = req('member_email');

    // Output
    if (!$_err) {
        $stm = $_db->prepare('SELECT * FROM member WHERE member_email = ?');
        $stm->execute([$member_email]);
        $member = $stm->fetch();

        if ($member) {

            $member_EncrptPassword = sha1($member_password);
            // Verify password
            if ($member_EncrptPassword === $member->member_password) {
                // Login successful
                temp('info', 'Login successful');
              login($member);
            } else {
                $_err['member_password'] = 'Incorrect password';
            }
        } else {
            $_err['member_email'] = 'Email not registered';
        }
    }
}

// ----------------------------------------------------------------------------
$_title = 'Login';
include '../_head.php';
?>

<form method="post" class="form">

    <label for="member_email">Email</label>
    <?= html_text('member_email', 'maxlength="40"') ?>
    <?= err('member_email') ?>

    <label for="member_password">Password</label>
    <?= html_password('member_password', 'maxlength="100"') ?>
    <?= err('member_password') ?>

    <section>
        <button>Submit</button>
        <button type="reset">Reset</button>
    </section>
</form>
<a href="../member/register.php">Register</a>
<br>
<a href="../member/forgot_password.php?role=member">Forgot password?</a>

<?php
include '../_foot.php';