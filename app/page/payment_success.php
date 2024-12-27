<?php
require '../_base.php';

// Database connection
$conn = new mysqli("localhost", "root", "", "gadgetwebdb");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (isset($_SESSION['checkout_data'])) {
    $cart_items = [];
    $checkout_data = $_SESSION['checkout_data'];

    $member_id = $checkout_data['member_id'];
    $voucher_id = $checkout_data['voucher_id'];
    $payment_method = $checkout_data['payment_method'];
    $final_price = $checkout_data['final_price'];
    $selected_items = $checkout_data['selected_items'];
    $cart_items = $checkout_data['cart_items'];


    // Process the checkout as needed

    // Insert into the `order` table
    $order_query = "INSERT INTO `order` (member_id, order_date, order_status, voucher_id, total_order_price) VALUES (?, NOW(), 'PENDING', ?, ?)";
    $order_stmt = $conn->prepare($order_query);
    $order_stmt->bind_param("ssd", $member_id, $voucher_id, $final_price);
    $order_stmt->execute();

    $order_id = $order_stmt->insert_id; // Get the generated order ID

    // Proceed with checkout if validation passes
    foreach ($cart_items as $item) {
        $gadget_id = $item['gadget_id'];
        $quantity = $item['quantity'];
        $order_item_price = $item['gadget_price'] * $quantity;

        // Insert into `order_item` table
        $order_item_query = "INSERT INTO order_item (gadget_id, order_id, order_price, item_quantity, member_id) VALUES (?, ?, ?, ?, ?)";
        $order_item_stmt = $conn->prepare($order_item_query);
        $order_item_stmt->bind_param("sdsis", $gadget_id, $order_id, $order_item_price, $quantity, $member_id);
        $order_item_stmt->execute();

        // Update gadget stock in the `gadget` table and set status to "Unactive" if stock <= 0
        $update_stock_query = "
    UPDATE gadget 
    SET gadget_stock = gadget_stock - ?, 
        gadget_status = CASE WHEN gadget_stock - ? <= 0 THEN 'Unactive' ELSE gadget_status END
    WHERE gadget_id = ?";
        $update_stock_stmt = $conn->prepare($update_stock_query);
        $update_stock_stmt->bind_param("iis", $quantity, $quantity, $gadget_id);
        $update_stock_stmt->execute();
    }

    // Remove items from the cart
    $delete_query = "DELETE FROM order_cart WHERE cart_id IN (" . implode(',', array_map('intval', $selected_items)) . ")";
    $conn->query($delete_query);

    
    $m = get_mail();
        $m->addAddress($_member->member_email, $u->member_name);
        $m->isHTML(true);
        $m->Subject = 'Reset Password';
        $m->Body = "
            <img src='cid:photo'
                 style='width: 200px; height: 200px;
                        border: 1px solid #333'>
            <p>Dear $u->member_name,<p>
            <h1 style='color: red'>Reset Password</h1>
            <p>
                Please click <a href='$url'>here</a>
                to reset your password.
            </p>
            <p>From, 😺 Admin</p>
        ";
        $m->send();

    unset($_SESSION['checkout_data']);
    temp('info', 'Payment has been make successfully');

    // Redirect to order cart page
    header("Location: order_item.php");
    exit();
}