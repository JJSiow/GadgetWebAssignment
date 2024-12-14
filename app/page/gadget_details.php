<?php
require '../_base.php';

// ----------------------------------------------------------------------------
$_title = 'Gadget Store - Product Details';
include '../_head.php';

// ----------------------------------------------------------------------------

// Check if 'gadget_id' is passed in the URL
if (isset($_GET['gadget_id'])) {
    $gadget_id = $_GET['gadget_id'];
} else {
    // Redirect to the main page if no gadget_id is provided
    header("Location: gadget.php");
    exit();
}

// Database connection
$conn = new mysqli("localhost", "root", "", "gadgetwebdb");  // Update to match your actual database

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch the gadget details from the database using the gadget_id
$query = "
    SELECT g.gadget_id, g.gadget_name, g.gadget_price, g.gadget_stock, g.gadget_photo, g.gadget_description, 
           c.category_name, 
           b.brand_name
    FROM gadget g
    LEFT JOIN category c ON g.category_id = c.category_id
    LEFT JOIN brand b ON g.brand_id = b.brand_id
    WHERE g.gadget_id = ?
";

// Prepare the query and bind parameters
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $gadget_id);  // Bind the gadget_id as an integer
$stmt->execute();

// Get the result
$result = $stmt->get_result();

// Check if the gadget was found
if ($result->num_rows === 0) {
    // Redirect to the main page if no gadget is found
    header("Location: gadget.php");
    exit();
}

// Fetch the gadget details
$gadget = $result->fetch_assoc();

// Check if the user is logged in (based on session)
session_start();
// if (!isset($_SESSION['member_id'])) {
//     // Redirect to login page if not logged in
//     header("Location: login.php");
//     exit();
// }

// Get member ID from session
$member_id = $_SESSION['member_id'];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Details - <?= htmlspecialchars($gadget['gadget_name']) ?></title>
    <link rel="stylesheet" href="styles.css">
</head>

<body>

    <h1>Product Details</h1>

    <!-- Display the gadget details -->
    <div class="product-details">
        <div class="product-info">
            <img src="<?= htmlspecialchars($gadget['gadget_photo']) ?>"
                alt="<?= htmlspecialchars($gadget['gadget_name']) ?>" class="product-image">
            <h2 class="product-name"><?= htmlspecialchars($gadget['gadget_name']) ?></h2>
            <p class="product-description"><?= htmlspecialchars($gadget['gadget_description']) ?></p>
            <p class="product-price">RM <?= htmlspecialchars($gadget['gadget_price']) ?></p>
            <p class="product-category"><?= htmlspecialchars($gadget['category_name']) ?></p>
            <p class="product-brand"><?= htmlspecialchars($gadget['brand_name']) ?></p>
            <p class="product-stock"><?= $gadget['gadget_stock'] ?> in stock</p>

            <!-- Add to Cart Form -->
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
// Close the database connection
$conn->close();
?>

<?php
include '../_foot.php';
?>
