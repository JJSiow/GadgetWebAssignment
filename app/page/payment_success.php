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
    $shipping_address = $checkout_data['shipped_address'];

    // Insert into the `order` table
    $order_query = "INSERT INTO `order` (member_id, order_date, order_status, voucher_id, total_order_price, shipped_address) VALUES (?, NOW(), 'PENDING', ?, ?, ?)";
    $order_stmt = $conn->prepare($order_query);
    $order_stmt->bind_param("ssds", $member_id, $voucher_id, $final_price, $shipping_address);
    $order_stmt->execute();

    $order_id = $order_stmt->insert_id; // Get the generated order ID

    // Prepare order items for email
    $ordered_items = '';

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
    }

    $order_id = $order_stmt->insert_id; // Get the generated order ID

    // Prepare order items for email
    $ordered_items = '';

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

        // Add item to email body
        $ordered_items .= "
            <tr>
                <td>{$item['gadget_name']}</td>
                <td>{$quantity}</td>
                <td>RM " . number_format($item['gadget_price'], 2) . "</td>
                <td>RM " . number_format($order_item_price, 2) . "</td>
            </tr>";
    }

    // Remove items from the cart
    $delete_query = "DELETE FROM order_cart WHERE cart_id IN (" . implode(',', array_map('intval', $selected_items)) . ")";
    $conn->query($delete_query);

    // Send email
    $m = get_mail();
    $m->addAddress($_member->member_email, $_member->member_name);
    $m->isHTML(true);
    $m->Subject = 'SEO Gadget (E-Receipt)';
    $m->Body = "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: auto; border: 1px solid #ddd; padding: 20px;'>
            <h1 style='color: #007bff;'>SEO Gadget</h1>
            <p>Dear {$_member->member_name},</p>
            <p>Thank you for your purchase! Below are the details of your order:</p>
            <table style='width: 100%; border-collapse: collapse;'>
                <thead>
                    <tr>
                        <th style='border: 1px solid #ddd; padding: 8px;'>Item</th>
                        <th style='border: 1px solid #ddd; padding: 8px;'>Quantity</th>
                        <th style='border: 1px solid #ddd; padding: 8px;'>Price</th>
                        <th style='border: 1px solid #ddd; padding: 8px;'>Total</th>
                    </tr>
                </thead>
                <tbody>
                    $ordered_items
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan='3' style='border: 1px solid #ddd; padding: 8px; text-align: right;'><strong>Final Price:</strong></td>
                        <td style='border: 1px solid #ddd; padding: 8px;'><strong>RM " . number_format($final_price, 2) . "</strong></td>
                    </tr>
                </tfoot>
            </table>
            <p>Payment Method: $payment_method</p>
            <p>If you have any questions, feel free to contact us at support@seogadget.com.</p>
            <p>Thank you for choosing SEO Gadget!</p>
            <p>Best regards,</p>
            <p>The SEO Gadget Team</p>
        </div>
    ";
    $m->send();

    unset($_SESSION['checkout_data']);
    temp('info', 'Payment has been successfully made.');

    // Redirect to order cart page
    header("Location: order_item.php");
    exit();
}
