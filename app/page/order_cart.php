<?php
require '../_base.php';
$_title = 'Your Cart';
include '../_head.php';

// Database connection
$conn = new mysqli("localhost", "root", "", "gadgetwebdb");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

auth_member();
$member_id = $_member->member_id;

// Handle item deletion
if (isset($_GET['delete_cart_id'])) {
    $cart_id = $_GET['delete_cart_id'];

    $deleteQuery = "DELETE FROM order_cart WHERE cart_id = ? AND member_id = ?";
    $stmt = $conn->prepare($deleteQuery);
    $stmt->bind_param("ii", $cart_id, $member_id);

    if ($stmt->execute()) {
        temp('info', 'Item removed from cart.');
        header("Location: order_cart.php");
        exit();
    } else {
        echo "Error removing item: " . $conn->error;
    }
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_quantity'])) {
    $cart_id = intval($_POST['cart_id']);
    $quantity = intval($_POST['quantity']);
    $member_id = $_member->member_id;

    if ($cart_id > 0 && $quantity > 0) {
        $updateQuery = "UPDATE order_cart SET quantity = ? WHERE cart_id = ? AND member_id = ?";
        $stmt = $conn->prepare($updateQuery);
        $stmt->bind_param("iii", $quantity, $cart_id, $member_id);

        if ($stmt->execute()) {
            http_response_code(200);
            echo json_encode(["status" => "success", "message" => "Quantity updated"]);
        } else {
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => "Failed to update quantity"]);
        }
        exit();
    } else {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Invalid cart ID or quantity"]);
        exit();
    }
}

// Fetch cart items for the user (only the first photo for each product)
$query = "
    SELECT 
        oc.cart_id, 
        oc.quantity, 
        g.gadget_id, 
        g.gadget_name, 
        g.gadget_price, 
        (SELECT photo_path FROM gallery ga WHERE ga.gadget_id = g.gadget_id LIMIT 1) AS photo_path
    FROM order_cart oc
    JOIN gadget g ON oc.gadget_id = g.gadget_id
    WHERE oc.member_id = ?
";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $member_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Cart</title>
    <link rel="stylesheet" href="/css/order_cart.css">
    <script src="/js/product.js" defer></script>
</head>

<body>
    <form action="checkout.php" method="POST">
        <table>
            <thead>
                <tr>
                    <th>Select</th>
                    <th>Image</th>
                    <th>Gadget Name</th>
                    <th>Price</th>
                    <th>Quantity</th>
                    <th>Total</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($item = $result->fetch_assoc()): ?>
                    <tr class="cart-row">
                        <td>
                            <input type="checkbox" class="item-select" name="selected_items[]"
                                value="<?= $item['cart_id'] ?>">
                        </td>
                        <td>
                            <a href="/page/gadget_details.php?gadget_id=<?= $gadget['gadget_id'] ?>">
                                <img src="/images/<?= $item['photo_path'] ?>" alt="<?= $item['gadget_name'] ?>"
                                    class="cart-gadget-img" style="width: 100px; height: auto;">
                            </a>
                            
                        </td>
                        <td>
                            <?= $item['gadget_name'] ?>
                        </td>
                        <td class="item-price" data-price="<?= $item['gadget_price'] ?>">RM
                            <?= number_format($item['gadget_price'], 2) ?>
                        </td>
                        <td>
                            <input type="number" class="item-quantity" data-cart-id="<?= $item['cart_id'] ?>"
                                name="quantity[<?= $item['cart_id'] ?>]" value="<?= $item['quantity'] ?>" min="1" max="99"
                                required>
                        </td>
                        <td class="item-total">RM <?= number_format($item['gadget_price'] * $item['quantity'], 2) ?></td>
                        <td>
                            <a href="order_cart.php?delete_cart_id=<?= $item['cart_id'] ?>" class="delete-item">Delete</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <div class="form-group">
            <label>
                <input type="checkbox" id="select-all" class="form-control"> Select All
            </label>
        </div>

        <p>Total Price: <span id="total-price">RM 0.00</span></p>
        <button type="submit">Proceed to Checkout</button>
    </form>
    <a href="gadget.php" class="back-to-products">Back to Products</a>
</body>

</html>

<?php
include '../_foot.php';
// Close the database connection
$conn->close();
?>