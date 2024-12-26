<?php
require_once '../_base.php';

if (is_post()) {
    $id = req('id'); 
    $action = req('action'); 

    if (in_array($action, ['Active', 'Unactive'])) {
        $updateGadget = $_db->prepare('UPDATE gadget SET gadget_status = ? WHERE gadget_id = ?');
        $updateGadget->execute([$action, $id]);

        temp('info', "Gadget status updated to $action.");
    } else {
        temp('error', 'Invalid action.');
    }
}

redirect('admin_products.php');
