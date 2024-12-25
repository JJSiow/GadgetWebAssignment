<?php
require '../_base.php';  // This should include session_start()

$_title = 'Gadget Store - Product Details';
include '../_head.php';

if (isset($_GET['gadget_id'])) {
    $gadget_id = $_GET['gadget_id'];
} else {
    header("Location: gadget.php");
    exit();
}

if (!isset($_SESSION['member_id'])) {
    header("Location: login.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "gadgetwebdb");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$query = "
    SELECT 
        g.gadget_id, g.gadget_name, g.gadget_price, g.gadget_stock, g.gadget_description, 
        c.category_name, 
        b.brand_name, 
        ga.photo_path
    FROM gadget g
    LEFT JOIN category c ON g.category_id = c.category_id
    LEFT JOIN brand b ON g.brand_id = b.brand_id
    LEFT JOIN gallery ga ON g.gadget_id = ga.gadget_id
    WHERE g.gadget_id = ?
";

$stmt = $conn->prepare($query);
$stmt->bind_param("s", $gadget_id);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: gadget.php");
    exit();
}

$gadget = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Details - <?= $gadget['gadget_name'] ?></title>
    <link rel="stylesheet" href="styles.css">
</head>

<body>
    <div class="product-details">
        <div class="product-info">
            
            <img src="/images/<?= $gadget['photo_path'] ?>" alt="<?= $gadget['gadget_name'] ?>" class="product-image">
            <h2 class="product-name"><?= $gadget['gadget_name'] ?></h2>
            <p class="product-description"><?= $gadget['gadget_description'] ?></p>
            <p class="product-price">RM <?= number_format($gadget['gadget_price'], 2) ?></p>
            <p class="product-category">Category: <?= $gadget['category_name'] ?></p>
            <p class="product-brand">Brand: <?= $gadget['brand_name'] ?></p>
            <p class="product-stock"><?= $gadget['gadget_stock'] ?> in stock</p>

            <form action="gadget.php" method="POST">
                <input type="hidden" name="gadget_id" value="<?= $gadget['gadget_id'] ?>">
                <input type="number" name="quantity" value="1" min="1" max="<?= $gadget['gadget_stock'] ?>" required>
                <button type="submit" name="add_to_cart" class="add-to-cart-btn">Add to Cart</button>
            </form>
        </div>
    </div>
    <a href="gadget.php" class="back-to-products">Back to Products</a>
</body>

</html>

<?php
$conn->close();
include '../_foot.php';
?>
