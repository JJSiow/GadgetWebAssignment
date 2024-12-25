<link rel="stylesheet" href="../css/adminLoginReg.css">
<?php
include '_base.php';

// ----------------------------------------------------------------------------

// TODO: (1) Delete expired tokens

$id = req('id');

// TODO: (2) Is token id valid?
if (!is_exists($id, 'token', 'id')) {
    temp('info', 'Invalid token. Try again');
    redirect('/');
}

if (is_post()) {
    // DB operation
    if (!$_err) {

        $stm = $_db->prepare('
        UPDATE member
        SET member_status = ?
        WHERE member_id = (SELECT user_id FROM token WHERE id = ?);

        DELETE FROM token WHERE id = ?;
    ');
        $stm->execute(["Active", $id, $id]);

        temp('info', 'You have activate your account successfully');
        redirect('/login.php');

    } 
}

// ----------------------------------------------------------------------------

$_title = 'Account Verification';
include '_head.php';
?>

<form method="post" class="form">
<p>
    If This account registration is not done by you.Please do not click the Activate button.
</p>

    <section>
        <button>Activate Account</button>
    </section>
</form>

<?php
include '_foot.php';
