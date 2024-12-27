<link rel="stylesheet" href="../css/adminLoginReg.css">
<?php
include '_base.php';

// ----------------------------------------------------------------------------

// TODO: (1) Delete expired tokens
$_db->query('DELETE FROM token WHERE expire < NOW() AND token_type="ForgotPassword"');

$id = req('id');
$role = req('role');

// TODO: (2) Is token id valid?
if (!is_exists($id, 'token', 'id')) {
    temp('info', 'Invalid token. Try again');
    redirect('/');
}

if (is_post()) {
    $password = req('password');
    $confirm  = req('confirm');

    // Validate: password
    if ($password == '') {
        $_err['password'] = 'Required';
    } else if (strlen($password) < 5 || strlen($password) > 100) {
        $_err['password'] = 'Between 5-100 characters';
    }

    // Validate: confirm
    if ($confirm == '') {
        $_err['confirm'] = 'Required';
    } else if (strlen($confirm) < 5 || strlen($confirm) > 100) {
        $_err['confirm'] = 'Between 5-100 characters';
    } else if ($confirm != $password) {
        $_err['confirm'] = 'Not matched';
    }
var_dump($role);
    // DB operation
    if (!$_err) {
        if ($role == 'member') {
            // TODO: Update user (password) based on token id + delete token
            $stm = $_db->prepare('
            UPDATE member
            SET member_password = SHA1(?)
            WHERE member_id = (SELECT user_id FROM token WHERE id = ?);

            DELETE FROM token WHERE id = ?;
        ');
            $stm->execute([$password, $id, $id]);

            temp('info', 'Record updated');
            redirect('/index.php');
        }
        else if ($role === 'admin') {
            // TODO: Update admin (password) based on token id + delete token
            $stm = $_db->prepare('
          UPDATE admin
          SET admin_password = SHA1(?)
          WHERE admin_id = (SELECT user_id FROM token WHERE id = ?);
    
          DELETE FROM token WHERE id = ?;
      ');
            $stm->execute([$password, $id, $id]);
    
            temp('info', 'Record updated');
            redirect('../admin/admin_login.php');
        }

    } 
}

// ----------------------------------------------------------------------------

$_title = 'User | Reset Password';
// include '_head.php';
?>
<div class="token-container">
<form method="post" class="form">
<h1>Reset Password</h1>
<p>Please enter you new password</p>
    <label for="password">Password</label>
    <?= html_password('password', 'maxlength="100"') ?>
    <?= err('password') ?>

    <label for="confirm">Confirm</label>
    <?= html_password('confirm', 'maxlength="100"') ?>
    <?= err('confirm') ?>

    <section>
        <button>Submit</button>
        <button type="reset">Reset</button>
    </section>
</form>
</div>
<?php
// include '_foot.php';
