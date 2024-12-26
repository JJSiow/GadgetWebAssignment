<?php
require '_base.php';
//-----------------------------------------------------------------------------

// if ($_member == null) {
//     temp('info', 'Please login as member');
//     redirect('/');
// }

auth_member();

// ----------------------------------------------------------------------------
$_title = 'Home';
include '_head.php';
?>


<img src="/photos/<?= $_member->member_profile_pic ?>">
<p>ID : <?= $_member->member_id ?></p>
<p>Name :<?= $_member->member_name ?></p>
<p>Gender :<?= $_genders[$_member->member_gender] ?></p>
<p>Email :<?= $_member->member_email ?></p>
<p>Address :<?= $_member->shipping_address ?></p>


<?php
include '_foot.php';
