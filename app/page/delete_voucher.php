<?php
require_once '../_base.php';

if (is_post()) {
    $id = req('id'); 
    $action = req('action'); 

    if (in_array($action, ['Active', 'Unactive'])) {
        $updateGadget = $_db->prepare('UPDATE voucher SET voucher_status = ? WHERE voucher_id = ?');
        $updateGadget->execute([$action, $id]);

        temp('info', "Voucher status updated to $action.");
    } else {
        temp('error', 'Invalid action.');
    }
}

redirect('admin_voucher.php');
