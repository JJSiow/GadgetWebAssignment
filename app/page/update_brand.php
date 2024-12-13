<?php
require_once '../_base.php';

//-----------------------------------------------------------------------------
if (isset($_POST['updates'])) {
    $updates = json_decode($_POST['updates'], true);

    if ($updates !== null) {
        try {
            foreach ($updates as $update) {
                $brandId = $update['id'];
                $brandName = $update['name'];

                $stmt = $_db->prepare("UPDATE brand SET brand_name = ? WHERE brand_id = ?");
                $result = $stmt->execute([$brandName, $brandId]);

                if (!$result) {
                    throw new Exception("Failed to update brand $brandId");
                }
            }
            echo json_encode(['status' => 'success', 'message' => 'Brand updated successfully']);
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
