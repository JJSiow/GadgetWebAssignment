<?php
require '_base.php';
// ----------------------------------------------------------------------------
if ($_member) {
     redirect('/page/gadget.php');
}

auth_member(false);

$inc = $_db->prepare('SELECT * FROM token WHERE expire < NOW() AND token_type= ?');
$inc->execute(['Activate']);
$inactive  = $inc->fetchAll();

if ($inactive) {
    $stm2 = $_db->prepare('DELETE FROM token WHERE user_id = ? AND token_type = ?');
    $stm3 = $_db->prepare('DELETE FROM member WHERE member_id = ?');
    
    foreach ($inactive as $token) {
        $stm2->execute([$token->user_id, 'Activate']); 
        $stm3->execute([$token->user_id]); 
    }
}

if (is_post()) {

    // Input
    //$member_id         = req('member_id');
    $member_password     = req('member_password');
    $member_email     = req('member_email');
    $remember_me     = req('remember_me');

    // Output
    if (!$_err) {
        $stm = $_db->prepare('SELECT * FROM member WHERE member_email = ?');
        $stm->execute([$member_email]);
        $member = $stm->fetch();

        if ($member) {



            $member_EncrptPassword = sha1($member_password);
            // Verify password
            if ($member_EncrptPassword === $member->member_password) {
                if ($member->member_status == 'Disabled') {
                    $_err['member_email'] = 'Account blocked';
                }
                else if ($member->member_status == 'Deleted') {
                    $_err['member_email'] = 'Account deleted';
                }
                else if($member->member_status == 'Inactive'){
                $_err['member_email'] = 'Account Inactive';
                }
                else{
                    // Login successful
                    temp('info', 'Login successful');
                    $_SESSION['member'] = $member;
                    
                      // Reset login attempts on successful login
                    $_db->prepare('UPDATE member SET login_attempt = 0 WHERE member_id = ?')->execute([$member->member_id]);


                    if ($remember_me) {
                        cookies_setting($member->member_id);
                    } else {
                        unCookies_setting();
                    }

                    // Redirect to gadget page
                    redirect('../page/gadget.php');
                    login($member);
                
                }
            } else {
                $_err['member_password'] = 'Incorrect password';
                
                // Increment login_attempts
                $_db->prepare('UPDATE member SET login_attempt = login_attempt + 1 WHERE member_id = ?')->execute([$member->member_id]);

                // Fetch login_attempts
                $stm = $_db->prepare('SELECT login_attempt FROM member WHERE member_id = ?');
                $stm->execute([$member->member_id]);
                $login_attempts = $stm->fetchColumn();

                // Check if max attempts reached
                if ($login_attempts >= 3) {
                    // Disable account
                    $_db->prepare('UPDATE member SET member_status = ? WHERE member_id = ?')->execute(['Disabled', $member->member_id]);
                    $_err['member_email'] = 'Account blocked';
                }
                else {
                    $remaining_attempts = 3 - $login_attempts;
                    $_err['member_email'] = 'You have ' . $remaining_attempts . ' login attempt(s) remaining.';
                }
            }
        } else {
            $_err['member_email'] = 'Email not registered';
        }
    }
}

// ----------------------------------------------------------------------------

$_title = 'Login';
include '_head.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<form method="post" class="form">

    <label for="member_email">Email</label>
    <?= html_text('member_email', 'maxlength="40"') ?>
    <?= err('member_email') ?>

    <label for="member_password">Password</label>
    <?= html_password('member_password', 'maxlength="100"') ?>
    <?= err('member_password') ?>

    <div>
        <input type="checkbox" name="remember_me" id="remember_me" value="1">
        <label for="remember_me">Remember Me</label>
    </div>

    <section>
        <button>Submit</button>
        <button type="reset">Reset</button>
    </section>
</form>
<a href="member/register.php">Register</a>
<br>
<a href="member/forgot_password.php?role=member">Forgot password?</a>

<?php
include '_foot.php';
