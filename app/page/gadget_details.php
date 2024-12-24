<?php
require '../_base.php';  // This should include session_start()

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

// Display Gadget ID (for debugging or testing)
echo "Gadget ID: " . htmlspecialchars($gadget_id);

// Check if the user is logged in (based on session)
if (!isset($_SESSION['member_id'])) {
    // Redirect to login page if not logged in
    header("Location: login.php");
    exit();
}

// Database connection
$conn = new mysqli("localhost", "root", "", "gadgetwebdb");

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch the gadget details from the database using the gadget_id
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

// Prepare the query and bind parameters
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $gadget_id);  // Bind the gadget_id as a string
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
            <!-- Display gadget photo -->
            <img src="/images/<?= htmlspecialchars($gadget['photo_path']) ?>" 
                 alt="<?= htmlspecialchars($gadget['gadget_name']) ?>" class="product-image">
            
            <!-- Display gadget name -->
            <h2 class="product-name"><?= htmlspecialchars($gadget['gadget_name']) ?></h2>
            
            <!-- Display gadget description -->
            <p class="product-description"><?= htmlspecialchars($gadget['gadget_description']) ?></p>
            
            <!-- Display gadget price -->
            <p class="product-price">RM <?= number_format($gadget['gadget_price'], 2) ?></p>
            
            <!-- Display category name -->
            <p class="product-category">Category: <?= htmlspecialchars($gadget['category_name']) ?></p>
            
            <!-- Display brand name -->
            <p class="product-brand">Brand: <?= htmlspecialchars($gadget['brand_name']) ?></p>
            
            <!-- Display stock availability -->
            <p class="product-stock"><?= $gadget['gadget_stock'] ?> in stock</p>

            <!-- Add to Cart Form -->
            <form action="gadget.php" method="POST">
                <input type="hidden" name="gadget_id" value="<?= htmlspecialchars($gadget['gadget_id']) ?>">
                <input type="number" name="quantity" value="1" min="1" max="<?= htmlspecialchars($gadget['gadget_stock']) ?>" required>
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
