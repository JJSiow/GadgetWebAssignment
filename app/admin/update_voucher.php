<?php
require_once '../_base.php';
auth_admin();
//-----------------------------------------------------------------------------
if (isset($_POST['updates'])) {
    $updates = json_decode($_POST['updates'], true);

    if ($updates !== null) {
        try {
            foreach ($updates as $update) {
                $voucherId = $update['id'];
                $voucherAmount = $update['name'];

                $stmt = $_db->prepare("UPDATE voucher SET voucher_amount = ? WHERE voucher_id = ?");
                $result = $stmt->execute([$voucherAmount, $voucherId]);

                if (!$result) {
                    throw new Exception("Failed to update voucher $voucherId");
                }
            }
            echo json_encode(['status' => 'success', 'message' => 'Voucher updated successfully']);
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid JSON']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'No updates sent']);
}
// ----------------------------------------------------------------------------
