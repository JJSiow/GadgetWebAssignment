<?php
include '../_base.php';

// ----------------------------------------------------------------------------


if (is_post()) {
    $email = req('email');

    // Validate: email
    if ($email == '') {
        $_err['email'] = 'Required';
    } else if (!is_email($email)) {
        $_err['email'] = 'Invalid email';
    } else if (!is_exists($email, 'member', 'member_email')) {
        $_err['email'] = 'Not exists';
    }

    // Send reset token (if valid)
    if (!$_err) {
        // TODO: (1) Select user
        $stm = $_db->prepare('SELECT * FROM member WHERE member_email = ?');
        $stm->execute([$email]);
        $u = $stm->fetch();

        // TODO: (2) Generate token id
        $id = sha1(uniqid() . rand());

        // TODO: (3) Delete old and insert new token
        $stm = $_db->prepare('
            DELETE FROM token WHERE user_id = ? AND token_type = ?;

            INSERT INTO token (id, expire, user_id,token_type)
            VALUES (?, ADDTIME(NOW(), "00:05"), ?,?);
        ');
        $stm->execute([$u->member_id, 'ForgotPassword', $id, $u->member_id, 'ForgotPassword']);

        // TODO: (4) Generate token url
        $url = base("token.php?id=$id&role=member");

        // TODO: (5) Send email
        $m = get_mail();
        $m->addAddress($u->member_email, $u->member_name);
        $m->isHTML(true);
        $m->Subject = 'Reset Password';
        $m->Body = "
            <img src='cid:photo'
                 style='width: 200px; height: 200px;
                        border: 1px solid #333'>
            <p>Dear $u->member_name,<p>
            <h1 style='color: red'>Reset Password</h1>
            <p>
                Please click <a href='$url'>here</a>
                to reset your password.
            </p>
            <p>From, 😺 Admin</p>
        ";
        $m->send();

        temp('info', 'Email sent');
        redirect('../login.php');
    }
}

// ----------------------------------------------------------------------------

$_title = 'User | Reset Password';
include '../_head.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/css/loginReg.css">
</head>

<body>
    <div class="forgot-ps-container">
        <div class="forgot-ps-form">
            <form method="post" class="form">
                
            <h1>Forgot <span style="color:rgb(0, 109, 0);">Password</span></h1>
            <p>You will receive en <span style="color:rgb(0, 109, 0);">email</span> to change your password</p>
                <label for="email">Email</label>
                <?= html_text('email', 'maxlength="100"') ?>
                <?= err('email') ?>

                <section>
                    <button>Submit</button>
                    <button type="reset">Reset</button>
                </section>
                <a href="/login.php">Login</a>
            </form>
        </div>
    </div>
</body>


<?php
include '../_foot.php';
