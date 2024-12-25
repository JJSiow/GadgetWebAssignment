<?php
require '../_base.php';
$_title = 'Checkout';
include '../_head.php';

// Database connection
$conn = new mysqli("localhost", "root", "", "gadgetwebdb");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (!isset($_SESSION['member_id'])) {
    // Redirect to login page if not logged in
    header("Location: ../member/login.php");
    exit();
}

$member_id = $_SESSION['member_id']; // Get the member_id from the session

$selected_items = isset($_POST['selected_items']) ? $_POST['selected_items'] : [];
$total_price = 0;

// If no items are selected, redirect back to the cart
if (empty($selected_items)) {
    header("Location: order_cart.php");
    exit();
}

// Fetch selected items from the cart
$items_query = "
    SELECT oc.cart_id, oc.quantity, g.gadget_id, g.gadget_name, g.gadget_price
    FROM order_cart oc
    JOIN gadget g ON oc.gadget_id = g.gadget_id
    WHERE oc.cart_id IN (" . implode(',', array_map('intval', $selected_items)) . ") AND oc.member_id = ?
";
$stmt = $conn->prepare($items_query);
$stmt->bind_param("s", $member_id); // Use 's' for string type (varchar)
$stmt->execute();
$items_result = $stmt->get_result();

$cart_items = [];
while ($item = $items_result->fetch_assoc()) {
    $cart_items[] = $item;
    $total_price += $item['gadget_price'] * $item['quantity']; // Calculate total price for the cart
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['checkout'])) {
    $voucher_id = !empty($_POST['voucher_id']) ? $_POST['voucher_id'] : NULL;  // Handle voucher as NULL if empty
    $payment_method = $_POST['payment_method'];

    // Insert into the `order` table first
    $order_query = "INSERT INTO `order` (member_id, order_date, order_status, voucher_id) VALUES (?, NOW(), 'pending', ?)";
    $order_stmt = $conn->prepare($order_query);
    $order_stmt->bind_param("ss", $member_id, $voucher_id); // Both are strings (varchar)
    $order_stmt->execute();
    
    $order_id = $order_stmt->insert_id;  // Get the order ID

    // Now insert into the `order_item` table and update gadget stock
    foreach ($cart_items as $item) {
        // Calculate the order price (total price for the item)
        $order_item_price = $item['gadget_price'] * $item['quantity'];

        // Insert into `order_item` table
        $order_item_query = "INSERT INTO order_item (gadget_id, order_id, order_price, member_id) VALUES (?, ?, ?, ?)";
        $order_item_stmt = $conn->prepare($order_item_query);
        $order_item_stmt->bind_param("ssss", $item['gadget_id'], $order_id, $order_item_price, $member_id);  // All are strings (varchar)
        $order_item_stmt->execute();

        // Update gadget stock in the `gadget` table
        $update_stock_query = "UPDATE gadget SET gadget_stock = gadget_stock - ? WHERE gadget_id = ?";
        $update_stock_stmt = $conn->prepare($update_stock_query);
        $update_stock_stmt->bind_param("is", $item['quantity'], $item['gadget_id']); // Bind quantity and gadget_id
        $update_stock_stmt->execute();
    }

    // Remove items from the cart
    $delete_query = "DELETE FROM order_cart WHERE cart_id IN (" . implode(',', array_map('intval', $selected_items)) . ")";
    $conn->query($delete_query);

    // Redirect to the order summary page
    header("Location: order_item.php?order_id=" . $order_id);
    exit();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <form action="checkout.php" method="POST">
        <table>
            <thead>
                <tr>
                    <th>Gadget</th>
                    <th>Price</th>
                    <th>Quantity</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($cart_items as $item): ?>
                <tr class="cart-row">
                    <td><?= $item['gadget_name'] ?></td>
                    <td class="item-price">RM <?= number_format($item['gadget_price'], 2) ?></td>
                    <td><?= $item['quantity'] ?></td>
                    <td>RM <?= number_format($item['gadget_price'] * $item['quantity'], 2) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <p><strong>Total Price: RM <?= number_format($total_price, 2) ?></strong></p>

        <label for="voucher_id">Voucher Code (optional):</label>
        <input type="text" name="voucher_id" id="voucher_id" placeholder="Enter voucher code">

        <br><br>

        <label>Payment Method:</label><br>
        <input type="radio" name="payment_method" value="TnG" required> TnG<br>
        <input type="radio" name="payment_method" value="Online Banking" required> Online Banking<br>

        <?php foreach ($selected_items as $selected_item): ?>
            <input type="hidden" name="selected_items[]" value="<?= $selected_item ?>">
        <?php endforeach; ?>

        <button type="submit" name="checkout">Confirm Checkout</button>
    </form>
</body>
</html>

<?php
include '../_foot.php';
$conn->close();
?>
