<?php
require_once '../_base.php';

//-----------------------------------------------------------------------------
if (isset($_POST['updates'])) {
    $updates = json_decode($_POST['updates'], true);

    if ($updates !== null) {
        try {
            foreach ($updates as $update) {
                $categoryId = $update['id'];
                $categoryName = $update['name'];

                $stmt = $_db->prepare("UPDATE category SET category_name = ? WHERE category_id = ?");
                $result = $stmt->execute([$categoryName, $categoryId]);

                if (!$result) {
                    throw new Exception("Failed to update category $categoryId");
                }
            }
            echo json_encode(['status' => 'success', 'message' => 'Categories updated successfully']);
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
