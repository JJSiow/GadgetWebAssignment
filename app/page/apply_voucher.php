<?php
header('Content-Type: application/json');

$conn = new mysqli("localhost", "root", "", "gadgetwebdb");

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $voucher_id = $_POST['voucher_id'];
    $total_price = floatval($_POST['total_price']);

    if (!empty($voucher_id)) {
        $voucher_query = "SELECT voucher_amount, voucher_status FROM voucher WHERE voucher_id = ?";
        $stmt = $conn->prepare($voucher_query);
        $stmt->bind_param("s", $voucher_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $voucher = $result->fetch_assoc();
            if ($voucher['voucher_status'] === 'Active') {
                $discount = $voucher['voucher_amount'];
                $final_price = max(0, $total_price - $discount);

                echo json_encode([
                    'success' => true,
                    'final_price' => number_format($final_price, 2),
                    'message' => "Voucher applied successfully! Discount: RM " . number_format($discount, 2)
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Voucher is not active.']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid voucher code.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Please enter a voucher code.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
}

$conn->close();
?>
