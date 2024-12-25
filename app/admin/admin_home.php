<?php
require '../_base.php';
//-----------------------------------------------------------------------------

auth_admin();

// ----------------------------------------------------------------------------
$_title = 'admin Home';
include '../admin/_admin_head.php';
?>

<img src="/photos/<?= $_admin->admin_profile_pic ?>">
<p>ID : <?= $_admin->admin_id ?></p>
<p>Name :<?= $_admin->admin_name ?></p>
<p>Phone Number :<?= $_admin->admin_phone_no ?></p>
<p>Email :<?= $_admin->admin_email ?></p>

<?php
include '../_foot.php';
