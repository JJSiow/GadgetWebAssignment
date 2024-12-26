<?php
require '../_base.php';

$conn = new mysqli("localhost", "root", "", "gadgetwebdb");

if ($conn->connect_error) {
    die(json_encode(['status' => 'error', 'message' => 'Database connection failed.']));
}

auth_member();
$member_id = $_member->member_id;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'], $_POST['status'])) {
    $order_id = intval($_POST['order_id']);
    $status = $_POST['status'];

    // Query to check the current status and member ownership
    $check_query = "SELECT order_status FROM `order` WHERE order_id = ? AND member_id = ?";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param("is", $order_id, $member_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode(['status' => 'error', 'message' => 'Order not found or access denied.']);
        exit;
    }

    $order = $result->fetch_assoc();

    // Check conditions for status update
    if ($status === 'CANCELLED' && $order['order_status'] === 'PENDING') {
        $conn->begin_transaction();
        try {
            // Update order status
            $update_query = "UPDATE `order` SET order_status = ? WHERE order_id = ? AND member_id = ?";
            $stmt = $conn->prepare($update_query);
            $stmt->bind_param("sis", $status, $order_id, $member_id);
            $stmt->execute();
    
            // Delete from order_item table
            $delete_query = "DELETE FROM order_item WHERE order_id = ?";
            $stmt = $conn->prepare($delete_query);
            $stmt->bind_param("i", $order_id);
            $stmt->execute();
    
            $conn->commit();
            echo json_encode([
                'status' => 'success',
                'message' => 'Your payment will be returned within 3 days. Contact (1212-2221-312) if not received after 3 days.'
            ]);
        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(['status' => 'error', 'message' => 'Failed to cancel the order.']);
        }
    } elseif ($status === 'RECEIVED' && $order['order_status'] === 'DELIVERED') {
        $update_query = "UPDATE `order` SET order_status = ? WHERE order_id = ? AND member_id = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("sis", $status, $order_id, $member_id);

        if ($stmt->execute() && $stmt->affected_rows > 0) {
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to update order status.']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid action or order cannot be updated.']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request.']);
}

$conn->close();