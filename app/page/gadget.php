<?php
require '../_base.php';
// ----------------------------------------------------------------------------
$_title = 'Gadget Store';
include '../_head.php';

// ----------------------------------------------------------------------------

if (!isset($_SESSION['member'])) {
    // Redirect to login page if not logged in
    header("Location: ../member/login.php");
    exit();
}


$member_id = $_member->member_id; // Get the logged-in user's member_id
// Database connection
$conn = new mysqli("localhost", "root", "", "gadgetwebdb");

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (is_post()) {
    $gadget_id = $_POST['gadget_id'];
    $quantity = $_POST['quantity'];

    // Validate gadget_id and quantity
    if (isset($gadget_id) && isset($quantity) && $quantity > 0) {
        // Check if the item already exists in the order_cart for this member
        $checkQuery = "SELECT quantity FROM order_cart WHERE gadget_id = ? AND member_id = ?";

        $stmt = $conn->prepare($checkQuery);
        $stmt->bind_param("ss", $gadget_id, $member_id);  // Corrected bind_param types (string for both gadget_id and member_id)
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            // Item already exists, update the quantity
            $row = $result->fetch_assoc();
            $newQuantity = $row['quantity'] + $quantity;

            // Update the quantity in the order_cart table
            $updateQuery = "UPDATE order_cart SET quantity = ? WHERE gadget_id = ? AND member_id = ?";
            $updateStmt = $conn->prepare($updateQuery);
            $updateStmt->bind_param("iss", $newQuantity, $gadget_id, $member_id);  // Corrected bind_param types
            $updateStmt->execute();

            temp('info', 'Quantity updated in your cart.');
        } else {
            // Item does not exist in the cart, insert it as a new entry
            $insertQuery = "INSERT INTO order_cart (gadget_id, quantity, member_id) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($insertQuery);
            $stmt->bind_param("sis", $gadget_id, $quantity, $member_id);  // Corrected bind_param types
            $stmt->execute();

            temp('info', 'Added to your cart.');
        }
    } else {
        echo "Invalid input for gadget_id or quantity.";
    }

    redirect();
}

// Initialize variables for search, category filter, and brand filter
$search = '';
$category_filter = '';
$brand_filter = '';

// Check if the form is submitted (via GET)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $search = $_GET['search'] ?? '';  // Get the search term
    $category_filter = $_GET['category'] ?? '';  // Get the category filter
    $brand_filter = $_GET['brand'] ?? '';  // Get the brand filter
}

// Build the query with optional filters
$query = "
    SELECT g.gadget_id, g.gadget_name, g.gadget_price, g.gadget_stock, g.gadget_photo, g.gadget_description, 
           c.category_name, 
           b.brand_name
    FROM gadget g
    LEFT JOIN category c ON g.category_id = c.category_id
    LEFT JOIN brand b ON g.brand_id = b.brand_id
    WHERE 1=1
";

// Apply the search filter (if exists)
if (!empty($search)) {
    $query .= " AND g.gadget_name LIKE ?";  // Match the search term in the gadget name
}

// Apply the category filter (if exists)
if (!empty($category_filter)) {
    $query .= " AND c.category_name = ?";  // Match the selected category
}

// Apply the brand filter (if exists)
if (!empty($brand_filter)) {
    $query .= " AND b.brand_name = ?";  // Match the selected brand
}

// Prepare the query statement
$stmt = $conn->prepare($query);

// Bind parameters for search, category, and brand filters
$bindTypes = '';
$params = [];

if (!empty($search)) {
    $search_term = "%" . $search . "%";
    $bindTypes .= 's';
    $params[] = &$search_term;
}
if (!empty($category_filter)) {
    $bindTypes .= 's';
    $params[] = &$category_filter;
}
if (!empty($brand_filter)) {
    $bindTypes .= 's';
    $params[] = &$brand_filter;
}

if (!empty($bindTypes)) {
    $stmt->bind_param($bindTypes, ...$params);
}

// Execute the query
$stmt->execute();
$result = $stmt->get_result();

// Check if the query was successful
if (!$result) {
    die("Error fetching gadgets: " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gadget Store</title>
    <link rel="stylesheet" href="/css/gadget.css">
    <script>
        // Function to submit the form when a radio button is clicked
        function submitForm() {
            document.getElementById("filterForm").submit();
        }
    </script>
</head>
<body>
    <div class="store-container">
        <!-- Left Sidebar -->
        <div class="sidebar">
            <form action="gadget.php" method="GET" id="filterForm" class="filter-form">
                <!-- Search -->
                <input type="text" name="search" placeholder="Search gadgets..." value="<?= htmlspecialchars($search) ?>">
                
                <!-- Search Button -->
                <button type="submit" name="search_button" class="search-btn">Search</button>
                
                <h4>Category</h4>
                <div>
                    <?php
                    // Fetch categories to populate the radio buttons
                    $category_query = "SELECT DISTINCT category_name FROM category";
                    $category_result = $conn->query($category_query);
                    while ($category = $category_result->fetch_assoc()) {
                        $checked = ($category['category_name'] == $category_filter) ? 'checked' : '';
                        echo "<label><input type='radio' name='category' value='" . htmlspecialchars($category['category_name']) . "' onclick='submitForm()' $checked>" . htmlspecialchars($category['category_name']) . "</label><br>";
                    }
                    ?>
                </div>

                <h4>Brand</h4>
                <div>
                    <?php
                    // Fetch brands to populate the radio buttons
                    $brand_query = "SELECT DISTINCT brand_name FROM brand";
                    $brand_result = $conn->query($brand_query);
                    while ($brand = $brand_result->fetch_assoc()) {
                        $checked = ($brand['brand_name'] == $brand_filter) ? 'checked' : '';
                        echo "<label><input type='radio' name='brand' value='" . htmlspecialchars($brand['brand_name']) . "' onclick='submitForm()' $checked>" . htmlspecialchars($brand['brand_name']) . "</label><br>";
                    }
                    ?>
                </div>
            </form>
        </div>

        <!-- Product Grid -->
        <div class="product-grid">
            <?php while ($gadget = $result->fetch_assoc()): ?>
                <div class="product-card">
                    <img src="<?= $gadget['gadget_photo'] ?>" alt="<?= $gadget['gadget_name'] ?>" class="product-image">
                    <div class="product-info">
                        <h3 class="product-name"><?= $gadget['gadget_name'] ?></h3>
                        <p class="product-description"><?= substr($gadget['gadget_description'], 0, 100) ?>...</p>
                        <p class="product-price">RM <?= $gadget['gadget_price'] ?></p>

                        <p class="product-category"><?= $gadget['category_name'] ?></p>
                        <p class="product-brand"><?= $gadget['brand_name'] ?></p>
                        <p class="product-stock"><?= $gadget['gadget_stock'] ?> in stock</p>

                        
                        <button class="view-btn" onclick="window.location.href='/page/gadget_details.php?gadget_id=<?= $gadget['gadget_id'] ?>'">View</button>

                        

                        <!-- Add to Cart Form -->
                        <form action="gadget.php" method="POST">
                            <input type="hidden" name="gadget_id" value="<?= $gadget['gadget_id'] ?>">
                            <input type="number" name="quantity" value="1" min="1" max="<?= $gadget['gadget_stock'] ?>" required>
                            <button type="submit" name="add_to_cart" class="add-to-cart-btn">Add to Cart</button>
                        </form>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </div>
</body>
</html>

<?php
// Close the database connection
$conn->close();
?>

<?php
include '../_foot.php';
?>
