<link rel="stylesheet" href="../css/adminLoginReg.css">

<?php
include '../_base.php';

// ----------------------------------------------------------------------------


if (is_post()) {
    $email = req('email');

    // Validate: email
    if ($email == '') {
        $_err['email'] = 'Required';
    }
    else if (!is_email($email)) {
        $_err['email'] = 'Invalid email';
    }
    else if (!is_exists($email, 'admin', 'admin_email')) {
        $_err['email'] = 'Not exists';
    }

    // Send reset token (if valid)
    if (!$_err) {
        // TODO: (1) Select user
        $stm = $_db->prepare('SELECT * FROM admin WHERE admin_email = ?');
        $stm->execute([$email]);
        $u = $stm->fetch();

        // TODO: (2) Generate token id
        $id = sha1(uniqid() . rand());

        // TODO: (3) Delete old and insert new token
        $stm = $_db->prepare('
            DELETE FROM token WHERE user_id = ?;

            INSERT INTO token (id, expire, user_id)
            VALUES (?, ADDTIME(NOW(), "00:05"), ?);
        ');
        $stm->execute([$u->admin_id, $id, $u->admin_id]);

        // TODO: (4) Generate token url
        $url = base("token.php?id=$id&role=admin");

        // TODO: (5) Send email
        $m = get_mail();
        $m->addAddress($u->admin_email, $u->admin_name);
        $m->isHTML(true);
        $m->Subject = 'Reset Password';
        $m->Body = "
            <img src='cid:photo'
                 style='width: 200px; height: 200px;
                        border: 1px solid #333'>
            <p>Dear $u->name,<p>
            <h1 style='color: red'>Reset Password</h1>
            <p>
                Please click <a href='$url'>$url</a>
                to reset your password.
            </p>
            <p>From, 😺 Admin</p>
        ";
        $m->send();

        temp('info', $u->admin_id, $id,'Email sent');
        redirect('/');
    }
}

// ----------------------------------------------------------------------------
?>

<div class="admin_login_form">
    <h1>Reset Password</h1>
<form method="post" class="form">
    <label for="email">Email</label>
    <?= html_text('email', 'maxlength="100"') ?>
    <?= err('email') ?>

    <section>
        <button>Submit</button>
    </section>
    <a href="../admin/admin_login.php">Back to Login</a>
</form>
</div>

<?php
include '../_foot.php';