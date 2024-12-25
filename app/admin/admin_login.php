<link rel="stylesheet" href="../css/adminLoginReg.css">

<?php
require '../_base.php';

auth_admin(false);

if (is_post()) {
    // Input
    $admin_id         = req('admin_id');
    $admin_password     = req('admin_password');
    $admin_email     = req('admin_email');

    // Output
    if (!$_err) {
        $stm = $_db->prepare('SELECT * FROM admin WHERE admin_email = ?');
        $stm->execute([$admin_email]);
        $admin = $stm->fetch();

        if ($admin) {

            //$admin_EncrptPassword = sha1($admin_password);
            // Verify password
            if (sha1($admin_password) === $admin->admin_password) {
                if ($admin->admin_status == 'Disabled') {
                    $_err['admin_email'] = 'Account blocked';
                }
                else {
                    // Login successful
                    temp('info', $admin->admin_id.' Login successful');
                    adminlogin($admin);
                }
            } else {
                $_err['admin_password'] = 'Incorrect password';
            }
        } else {
            $_err['admin_email'] = 'Email not registered';
        }
    }
}

// ----------------------------------------------------------------------------
$_title = 'Admin Login';
?>

<div class="admin_login_form">
    <h1>Login Now</h1>
    <form method="post" class="form">
        <label for="admin_email">Email</label>
        <?= html_text('admin_email', 'maxlength="40"') ?>
        <?= err('admin_email') ?>

        <label for="admin_password">Password</label>
        <?= html_password('admin_password', 'maxlength="100"') ?>
        <?= err('admin_password') ?>

        <button type="submit">Login</button>
        <button type="reset">Reset</button>
        <a href="../admin/admin_forgot_password.php">Forgot password?</a>
        <a href="../member/login.php">Member Login</a>
    </form>
</div>

<?php
include '../_foot.php';

