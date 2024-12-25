<?php
require '../_base.php';
// ----------------------------------------------------------------------------
$_title = 'Gadget Store';
include '../_head.php';

// ----------------------------------------------------------------------------

auth_member();
$member_id = $_member->member_id; 

// Database connection
$conn = new mysqli("localhost", "root", "", "gadgetwebdb");

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $gadget_id = $_POST['gadget_id'];
    $quantity = $_POST['quantity'];

    // Validate gadget_id and quantity
    if (!empty($gadget_id) && !empty($quantity) && $quantity > 0) {
        // Check if the item already exists in the order_cart for this member
        $checkQuery = "SELECT quantity FROM order_cart WHERE gadget_id = ? AND member_id = ?";
        $stmt = $conn->prepare($checkQuery);
        $stmt->bind_param("ss", $gadget_id, $member_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            // Item already exists, update the quantity
            $row = $result->fetch_assoc();
            $newQuantity = $row['quantity'] + $quantity;

            // Update the quantity in the order_cart table
            $updateQuery = "UPDATE order_cart SET quantity = ? WHERE gadget_id = ? AND member_id = ?";
            $updateStmt = $conn->prepare($updateQuery);
            $updateStmt->bind_param("iss", $newQuantity, $gadget_id, $member_id);
            $updateStmt->execute();

            temp('info', 'Quantity updated in your cart.');
        } else {
            // Item does not exist in the cart, insert it as a new entry
            $insertQuery = "INSERT INTO order_cart (gadget_id, quantity, member_id) VALUES (?, ?, ?)";
            $insertStmt = $conn->prepare($insertQuery);
            $insertStmt->bind_param("sis", $gadget_id, $quantity, $member_id);
            $insertStmt->execute();

            temp('info', 'Added to your cart.');
        }

        // Redirect to avoid duplicate form submissions
        header("Location: gadget.php");
        exit();
    } else {
        echo "Invalid input for gadget_id or quantity.";
    }
}

// Initialize variables for search, category filter, brand filter, and price range filter
$search = '';
$category_filter = '';
$brand_filter = '';
$price_filter = '';

// Check if the form is submitted (via GET)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $search = $_GET['search'] ?? '';
    $category_filter = $_GET['category'] ?? '';
    $brand_filter = $_GET['brand'] ?? '';
    $price_filter = $_GET['price'] ?? '';
}

// Build the query with optional filters
$query = "
    SELECT DISTINCT 
        g.gadget_id, g.gadget_name, g.gadget_price, g.gadget_stock, g.gadget_description, 
        c.category_name, 
        b.brand_name,
        (SELECT photo_path FROM gallery ga WHERE ga.gadget_id = g.gadget_id LIMIT 1) AS photo_path -- Get one photo
    FROM gadget g
    LEFT JOIN category c ON g.category_id = c.category_id
    LEFT JOIN brand b ON g.brand_id = b.brand_id
    WHERE g.gadget_status != 'Unactive' -- Exclude gadgets with status 'Unactive'
";

// Apply the search filter (if exists)
if (!empty($search)) {
    $query .= " AND g.gadget_name LIKE ?";
}

// Apply the category filter (if exists)
if (!empty($category_filter)) {
    $query .= " AND c.category_name = ?";
}

// Apply the brand filter (if exists)
if (!empty($brand_filter)) {
    $query .= " AND b.brand_name = ?";
}

// Apply the price range filter (if exists)
if (!empty($price_filter)) {
    $price_ranges = [
        '1-100' => 'g.gadget_price BETWEEN 1 AND 100',
        '100-1000' => 'g.gadget_price BETWEEN 100 AND 1000',
        '1000-2000' => 'g.gadget_price BETWEEN 1000 AND 2000',
        '2000-4000' => 'g.gadget_price BETWEEN 2000 AND 4000',
        '4000-6000' => 'g.gadget_price BETWEEN 4000 AND 6000',
        '6000-10000' => 'g.gadget_price BETWEEN 6000 AND 10000',
        '>10000' => 'g.gadget_price >= 10000'
    ];

    if (isset($price_ranges[$price_filter])) {
        $query .= " AND " . $price_ranges[$price_filter];
    }
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
    <script src="/js/product.js" defer></script>
</head>

<body>
    <div class="store-container">
        <!-- Left Sidebar -->
        <div class="sidebar">
            <form action="gadget.php" method="GET" id="filterForm" class="filter-form">
                <!-- Search -->
                <input type="text" name="search" placeholder="Search gadgets..." value="<?= $search ?>">

                <!-- Search Button -->
                <button type="submit" name="search_button" class="search-btn">Search</button>

                <h4>Category</h4>
                <div>
                    <?php
                    $category_query = "SELECT DISTINCT category_name FROM category";
                    $category_result = $conn->query($category_query);
                    while ($category = $category_result->fetch_assoc()) {
                        $checked = ($category['category_name'] == $category_filter) ? 'checked' : '';
                        echo "<label><input type='radio' name='category' value='" . $category['category_name'] . "' onclick='submitForm()' $checked>" . $category['category_name'] . "</label><br>";
                    }
                    ?>
                </div>

                <h4>Brand</h4>
                <div>
                    <?php
                    $brand_query = "SELECT DISTINCT brand_name FROM brand";
                    $brand_result = $conn->query($brand_query);
                    while ($brand = $brand_result->fetch_assoc()) {
                        $checked = ($brand['brand_name'] == $brand_filter) ? 'checked' : '';
                        echo "<label><input type='radio' name='brand' value='" . $brand['brand_name'] . "' onclick='submitForm()' $checked>" . $brand['brand_name'] . "</label><br>";
                    }
                    ?>
                </div>

                <h4>Price Range</h4>
                <div>
                    <?php
                    $price_options = [
                        '1-100' => 'RM 1 - RM 100',
                        '100-1000' => 'RM 100 - RM 1000',
                        '1000-2000' => 'RM 1000 - RM 2000',
                        '2000-4000' => 'RM 2000 - RM 4000',
                        '4000-6000' => 'RM 4000 - RM 6000',
                        '6000-10000' => 'RM 6000 - RM 10000',
                        '>10000' => '> RM 10000'
                    ];

                    foreach ($price_options as $value => $label) {
                        $checked = ($value == $price_filter) ? 'checked' : '';
                        echo "<label><input type='radio' name='price' value='$value' onclick='submitForm()' $checked> $label</label><br>";
                    }
                    ?>
                </div>
            </form>
        </div>

        <!-- Product Grid -->
        <div class="product-grid">
            <?php while ($gadget = $result->fetch_assoc()): ?>
                <div class="product-card">
                    <a href="/page/gadget_details.php?gadget_id=<?= $gadget['gadget_id'] ?>">
                        <img src="/images/<?= $gadget['photo_path'] ?>" alt="<?= $gadget['gadget_name'] ?>"
                            class="product-image">
                    </a>
                    <div class="product-info">
                        <h3 class="product-name"><?= $gadget['gadget_name'] ?></h3>
                        <p class="product-description"><?= substr($gadget['gadget_description'], 0, 100) ?>...</p>
                        <p class="product-price">RM <?= $gadget['gadget_price'] ?></p>
                        <p class="product-category"><?= $gadget['category_name'] ?></p>
                        <p class="product-brand"><?= $gadget['brand_name'] ?></p>
                        <p class="product-stock"><?= $gadget['gadget_stock'] ?> in stock</p>

                        <button class="view-btn"
                            onclick="window.location.href='/page/gadget_details.php?gadget_id=<?= $gadget['gadget_id'] ?>'">View</button>

                        <!-- Add to Cart Form -->
                        <form action="gadget.php" method="POST">
                            <input type="hidden" name="gadget_id" value="<?= $gadget['gadget_id'] ?>">
                            <input type="number" name="quantity" value="1" min="1" max="<?= $gadget['gadget_stock'] ?>"
                                required>
                            <button type="submit" name="add_to_cart" class="add-to-cart-btn">Add to Cart</button>
                        </form>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </div>

    <script>
        // Submit the form automatically when a filter is selected
        function submitForm() {
            document.getElementById('filterForm').submit();
        }
    </script>
</body>

<?php
// Close the database connection
$conn->close();
?>
