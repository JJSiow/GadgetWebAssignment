<?php
require_once '../_base.php';

if (is_post()) {
    if (!empty($_POST['checkboxName'])) {
        print_r($_POST['checkboxName']);
        $ids = explode(',', $_POST['checkboxName']);
        if (!empty($ids)) {
            $action = isset($_POST['action']) ? $_POST['action'] : '';
            $status = ($action === 'Unactive') ? 'Unactive' : 'Active';

            $stmUpdateStatus = $_db->prepare('UPDATE gadget SET gadget_status = ? WHERE gadget_id = ?');
            foreach ($ids as $id) {
                $stmUpdateStatus->execute([$status, $id]);
            }

            temp('info', count($ids) . " gadget status(s) updated to {$status}.");
        } else {
            temp('error', 'Invalid gadget IDs.');
        }
    } else {
        temp('error', 'No gadget selected.');
    }
} else {
    temp('error', 'Invalid request method.');
}

redirect('admin_products.php');
