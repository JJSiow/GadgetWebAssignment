<?php
require_once '../_base.php';

if (is_post()) {
    if (!empty($_POST['checkboxName'])) {
        print_r($_POST['checkboxName']);
        $ids = explode(',', $_POST['checkboxName']);
        if (!empty($ids)) {
            $action = isset($_POST['action']) ? $_POST['action'] : '';
            $status = ($action === 'Unactive') ? 'Unactive' : 'Active';

            $stmUpdateStatus = $_db->prepare('UPDATE brand SET brand_status = ? WHERE brand_id = ?');
            foreach ($ids as $id) {
                $stmUpdateStatus->execute([$status, $id]);
            }

            temp('info', count($ids) . " brand status(s) updated to {$status}.");
        } else {
            temp('error', 'Invalid brand IDs.');
        }
    } else {
        temp('error', 'No brands selected.');
    }
} else {
    temp('error', 'Invalid request method.');
}

redirect('admin_brand.php');
