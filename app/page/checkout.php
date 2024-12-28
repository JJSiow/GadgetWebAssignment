<?php
require '../_base.php';
$_title = 'Checkout';
include '../_head.php';
require __DIR__ . "/vendor/autoload.php";

$stripe_secret_key = "sk_test_51QaDuGKBOa17We83oRWp6LLxA3lcYN58zhcLX6fJ4GX4CAPTn2SVxS0MC450qLiGDp42D0WTjlmphSMJ8S9UHg0U00pjWY0sqh";

\Stripe\Stripe::setApiKey($stripe_secret_key);

// Database connection
$conn = new mysqli("localhost", "root", "", "gadgetwebdb");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

auth_member();
$member_id = $_member->member_id;

$selected_items = isset($_POST['selected_items']) ? $_POST['selected_items'] : [];
$total_price = 0;

if (empty($selected_items)) {
    header("Location: order_cart.php");
    exit();
}

auth_member();
$member_id = $_member->member_id;

// Fetch member details
$member_query = "SELECT member_name, member_email FROM member WHERE member_id = ?";
$member_stmt = $conn->prepare($member_query);
$member_stmt->bind_param("s", $member_id);
$member_stmt->execute();
$member_result = $member_stmt->get_result();
$member = $member_result->fetch_assoc();
$member_name = $member['member_name'];
$member_email = $member['member_email'];

// Fetch selected items from the cart
$items_query = "
    SELECT oc.cart_id, oc.quantity, g.gadget_id, g.gadget_name, g.gadget_price
    FROM order_cart oc
    JOIN gadget g ON oc.gadget_id = g.gadget_id
    WHERE oc.cart_id IN (" . implode(',', array_map('intval', $selected_items)) . ") AND oc.member_id = ?
";
$stmt = $conn->prepare($items_query);
$stmt->bind_param("s", $member_id);
$stmt->execute();
$items_result = $stmt->get_result();

$cart_items = [];
while ($item = $items_result->fetch_assoc()) {
    $cart_items[] = $item;
    $total_price += $item['gadget_price'] * $item['quantity'];
}

// Handle form submission for checkout
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['checkout'])) {
    $voucher_id = !empty($_POST['voucher_id']) ? $_POST['voucher_id'] : NULL;  // Handle voucher as NULL if empty
    $payment_method = $_POST['payment_method'];
    $final_price = floatval($_POST['final_price']); // Get the final price



    // Validate stock before processing the checkout
    foreach ($cart_items as $item) {
        $gadget_id = $item['gadget_id'];
        $quantity = $item['quantity'];

        // Check the available stock
        $check_stock_query = "SELECT gadget_stock FROM gadget WHERE gadget_id = ?";
        $check_stock_stmt = $conn->prepare($check_stock_query);
        $check_stock_stmt->bind_param("s", $gadget_id);
        $check_stock_stmt->execute();
        $check_stock_result = $check_stock_stmt->get_result();

        if ($check_stock_result->num_rows > 0) {
            $stock_data = $check_stock_result->fetch_assoc();
            $available_stock = $stock_data['gadget_stock'];

            if ($quantity > $available_stock) {
                // Stock insufficient, temp a message and redirect back to the cart
                temp('info', 'Insufficient stock for ' . htmlspecialchars($item['gadget_name']) . '. Available stock: ' . $available_stock . '. Quantity updated in your cart.');
                header("Location: order_cart.php");
                exit();
            }
        } else {
            // If the gadget does not exist or stock information is missing
            temp('info', 'Error retrieving stock information for ' . htmlspecialchars($item['gadget_name']) . '. Please try again later.');
            header("Location: order_cart.php");
            exit();
        }
    }

    $_SESSION['checkout_data'] = [
        'member_id' => $member_id,
        'voucher_id' => $voucher_id,
        'payment_method' => $payment_method,
        'final_price' => $final_price,
        'selected_items' => $selected_items,
        'cart_items' => $cart_items
    ];

    try {
        $checkout_session = \Stripe\Checkout\Session::create([
            'payment_method_types' => ['card'],
            'mode' => 'payment',
            'success_url' => "http://localhost:8000/page/payment_success.php",
            'cancel_url' => "http://localhost:8000/page/payment_cancel.php",
            'line_items' => [
                [
                    "quantity" => 1,
                    "price_data" => [
                        "currency" => 'myr',
                        "unit_amount" => intval($final_price * 100),
                        "product_data" => [
                            "name" => "Order for SEO Gadget with $_member->member_name",
                        ]
                    ]
                ]
            ]
        ]);
        header("Location: " . $checkout_session->url);
        exit();
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage();
        exit();
    }

}



?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout</title>
    <link rel="stylesheet" href="/css/checkout.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="/js/product.js" defer></script>
</head>

<body>
    <div class="checkout-container">
        <h1 class="checkout-title">Checkout</h1>
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

        <div class="checkout-content">
            <!-- Left: Order Summary -->
            <div class="order-summary">
                <h2>Order Summary</h2>
                <table class="cart-table">
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
                        <tr>
                            <td><?= $item['gadget_name'] ?></td>
                            <td>RM <?= number_format($item['gadget_price'], 2) ?></td>
                            <td><?= $item['quantity'] ?></td>
                            <td>RM <?= number_format($item['gadget_price'] * $item['quantity'], 2) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <p><strong>Total Price: RM <span id="total-price"><?= number_format($total_price, 2) ?></span></strong></p>
            </div>

            <!-- Right: Payment Details -->
            <div class="payment-details">
                <h2>Payment Details</h2>
                <form action="checkout.php" method="POST">
                    <label for="voucher_id">Voucher Code (optional):</label>
                    <input type="text" name="voucher_id" id="voucher_id" placeholder="Enter voucher code">
                    <button type="button" id="apply-voucher-btn" class="btn">Apply Voucher</button>
                    <p id="voucher-message"></p>

                    <p><strong>Member Email:</strong> <?= $member_email ?></p>
                    <p><strong>Final Price: RM <span id="final-price"><?= number_format($total_price, 2) ?></span></strong></p>

                    <label>Payment Method:</label>
                    <div class="payment-options">
                        <input type="radio" name="payment_method" value="TnG" required> TnG
                        <input type="radio" name="payment_method" value="Online Banking" required> Online Banking
                    </div>

                    <?php foreach ($selected_items as $selected_item): ?>
                        <input type="hidden" name="selected_items[]" value="<?= $selected_item ?>">
                    <?php endforeach; ?>
                    <input type="hidden" name="final_price" id="hidden-final-price" value="<?= $total_price ?>">

                    <button type="submit" name="checkout" class="btn btn-primary">Confirm Checkout</button>
                </form>
            </div>
        </div>
    </div>
</body>

</html>


<?php
include '../_foot.php';
$conn->close();
?>