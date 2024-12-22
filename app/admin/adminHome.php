<?php
require '../_base.php';
//-----------------------------------------------------------------------------

if (!empty($_SESSION["admin"])) {
  // $admin_id = $_SESSION["admin_id"];

  //  $stm = $_db->prepare('SELECT * FROM admin WHERE admin_id = ?');
  //  $stm->execute([$admin_id]);
  //  $admin = $stm->fetch(PDO::FETCH_ASSOC);
} else {
  temp('info', 'Please login');
  redirect('adminLogin.php');
}

// ----------------------------------------------------------------------------
$_title = 'admin Home';
include '../admin/_adminHead.php';
?>

<img src="/photos/<?= $_admin->admin_profile_pic ?>">
<p>ID : <?= $_admin->admin_id ?></p>
<p>Name :<?= $_admin->admin_name ?></p>
<p>Phone Number :<?= $_admin->admin_phone_no ?></p>
<p>Email :<?= $_admin->admin_email ?></p>

<?php
include '../_foot.php';
