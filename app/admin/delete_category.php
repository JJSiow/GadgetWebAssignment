<?php
require_once '../_base.php';
auth_admin();
if (is_post()) {
    if (!empty($_POST['checkboxName'])) {
        $ids = explode(',', $_POST['checkboxName']);
        if (!empty($ids)) {
            $action = isset($_POST['action']) ? $_POST['action'] : '';
            $status = ($action === 'Unactive') ? 'Unactive' : 'Active';

            $stmUpdateStatus = $_db->prepare('UPDATE category SET category_status = ? WHERE category_id = ?');
            foreach ($ids as $id) {
                $stmUpdateStatus->execute([$status, $id]);
            }

            temp('info', count($ids) . " category status(s) updated to {$status}.");
        } else {
            temp('error', 'Invalid category IDs.');
        }
    } else {
        temp('error', 'No categories selected.');
    }
} else {
    temp('error', 'Invalid request method.');
}
redirect('admin_category.php');
