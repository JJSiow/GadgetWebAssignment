<?php
require '../_base.php';
$_title = 'Your Cart';
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

$member_id = $_SESSION['member_id'];  // Get logged-in member ID

// Handle item deletion
if (isset($_GET['delete_cart_id'])) {
    $cart_id = $_GET['delete_cart_id'];

    $deleteQuery = "DELETE FROM order_cart WHERE cart_id = ? AND member_id = ?";
    $stmt = $conn->prepare($deleteQuery);
    $stmt->bind_param("ii", $cart_id, $member_id);

    if ($stmt->execute()) {
        temp('info', 'Item removed from cart.');
        redirect('order_cart.php');
    } else {
        echo "Error removing item: " . $conn->error;
    }
    exit;
}

// Handle AJAX quantity update
if (isset($_POST['update_quantity'])) {
    $cart_id = $_POST['cart_id'];
    $quantity = $_POST['quantity'];

    $updateQuery = "UPDATE order_cart SET quantity = ? WHERE cart_id = ? AND member_id = ?";
    $stmt = $conn->prepare($updateQuery);
    $stmt->bind_param("iii", $quantity, $cart_id, $member_id);

    if ($stmt->execute()) {
        echo "Quantity updated successfully.";
    } else {
        echo "Error updating quantity: " . $conn->error;
    }
    exit;
}

// Fetch cart items for the user
$query = "
    SELECT 
        oc.cart_id, 
        oc.quantity, 
        g.gadget_id, 
        g.gadget_name, 
        g.gadget_price, 
        ga.photo_path
    FROM order_cart oc
    JOIN gadget g ON oc.gadget_id = g.gadget_id
    LEFT JOIN gallery ga ON g.gadget_id = ga.gadget_id
    WHERE oc.member_id = ?
";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $member_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Cart</title>
    <link rel="stylesheet" href="app.css">
    <script src="/js/product.js" defer></script>
</head>

<body>
    <form action="checkout.php" method="POST">
        <table>
            <thead>
                <tr>
                    <th>Select</th>
                    <th>Gadget</th>
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
                            <img src="/uploads/<?= htmlspecialchars($item['photo_path']) ?>" alt="<?= $item['gadget_name'] ?>" class="cart-gadget-img">
                            <span><?= htmlspecialchars($item['gadget_name']) ?></span>
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

        <p>Total Price: <span id="total-price">RM 0.00</span></p>
        <button type="submit">Proceed to Checkout</button>
    </form>
    <a href="gadget.php" class="back-to-products">Back to Products</a>

    <script>
        // Calculate total price dynamically
        const itemRows = document.querySelectorAll('.cart-row');
        let totalPrice = 0;

        itemRows.forEach(row => {
            const price = parseFloat(row.querySelector('.item-price').dataset.price);
            const quantity = parseInt(row.querySelector('.item-quantity').value);
            totalPrice += price * quantity;
        });

        document.getElementById('total-price').innerText = `RM ${totalPrice.toFixed(2)}`;
        
        // Update total price when quantity changes
        const quantityInputs = document.querySelectorAll('.item-quantity');
        quantityInputs.forEach(input => {
            input.addEventListener('input', function () {
                const row = input.closest('.cart-row');
                const price = parseFloat(row.querySelector('.item-price').dataset.price);
                const quantity = parseInt(input.value);
                row.querySelector('.item-total').innerText = `RM ${ (price * quantity).toFixed(2) }`;

                let totalPrice = 0;
                document.querySelectorAll('.item-total').forEach(total => {
                    totalPrice += parseFloat(total.innerText.replace('RM ', '').replace(',', ''));
                });

                document.getElementById('total-price').innerText = `RM ${totalPrice.toFixed(2)}`;
            });
        });
    </script>
</body>

</html>

<?php
include '../_foot.php';
// Close the database connection
$conn->close();
?>
