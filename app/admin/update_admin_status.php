<?php
require '../_base.php';
auth_super_admin();
if(is_post()) {
    $admin_id = req('admin_id');

    $stm = $_db->prepare('SELECT admin_status FROM admin WHERE admin_id = ?');
    $stm->execute([$admin_id]);
    $current_status = $stm->fetchColumn();

    $new_status = ($current_status == 'Active') ? 'Disabled' : 'Active';

    $_db->prepare('UPDATE admin SET admin_status = ? WHERE admin_id = ?')->execute([$new_status, $admin_id]);

    temp('info', 'Status updated');
    redirect("admin_list.php");
}
?>