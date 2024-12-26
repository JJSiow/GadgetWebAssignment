<link rel="stylesheet" href="/css/adminHome.css">
<?php
require '../_base.php';
//-----------------------------------------------------------------------------

auth_admin();

$orderPrices = $_db->query("SELECT * FROM `order`")->fetchAll(PDO::FETCH_ASSOC);
$totalUser = $_db->query("SELECT COUNT(member_id) AS total FROM `member` WHERE member_status = 'Active'")->fetch(PDO::FETCH_ASSOC);
$totalGadget = $_db->query("SELECT COUNT(gadget_id) AS total FROM `gadget` WHERE gadget_status = 'Active'")->fetch(PDO::FETCH_ASSOC);
$totalSales = 0;

$totalUserCount = $totalUser['total'];
$totalGadgetCount = $totalGadget['total'];

foreach ($orderPrices as $orderPrice) {
	$totalSales += $orderPrice['total_order_price'];
}

// Query to get total sales of all gadgets
$totalResult = $_db->query("
SELECT SUM(oi.item_quantity) AS total_quantity_sold 
FROM order_item oi         
JOIN `order` o ON oi.order_id = o.order_id
WHERE o.order_status = 'DELIVERED'")->fetch(PDO::FETCH_ASSOC);
$totalQty = $totalResult['total_quantity_sold'];

// Query to get top 5 best-selling gadgets
$topSellingGadgets = $_db->query("
        SELECT g.gadget_id,g.gadget_name,SUM(oi.item_quantity) AS total_quantity_sold
        FROM order_item oi
        JOIN gadget g ON oi.gadget_id = g.gadget_id
        JOIN `order` o ON oi.order_id = o.order_id
        WHERE o.order_status = 'DELIVERED'
        GROUP BY oi.gadget_id
        ORDER BY total_quantity_sold DESC
        LIMIT 5;
    ")->fetchAll(PDO::FETCH_ASSOC);

$query = "SELECT g.gadget_id, g.gadget_name, c.category_name, g.gadget_stock 
	FROM gadget g
	JOIN category c ON g.category_id = c.category_id
	WHERE g.gadget_stock < 10 
	AND g.gadget_status = 'Active'
	ORDER BY g.gadget_stock ASC
	LIMIT 5";

$low_stock_gadgets = $_db->query($query)->fetchAll();

// Add stock function
if (is_post()) {
	$gadget_id = req('gadget_id');
	$add_stock = req('add_stock');

	if ($gadget_id && $add_stock > 0) {
		$update_query = "UPDATE gadget 
				  SET gadget_stock = gadget_stock + ? 
				  WHERE gadget_id = ?";
		$_db->prepare($update_query)->execute([$add_stock, $gadget_id]);
	}
}
// ----------------------------------------------------------------------------
$_title = 'admin Home';
include '../admin/_admin_head.php';
?>


<!-- <p>ID : <?= $_admin->admin_id ?></p>
<p>Name :<?= $_admin->admin_name ?></p>
<p>Password :<?= $_admin->admin_password ?></p>
<p>Phone Number :<?= $_admin->admin_phoneNo ?></p>
<p>Email :<?= $_admin->admin_email ?></p> -->

<div class="dashboard-container">
	<div class="dashboard-box">
		<div class="dashboard-title">Total Revenue</div>
		<div class="dashboard-value">RM <?= number_format($totalSales, 2) ?></div>
	</div>
	<div class="dashboard-box">
		<div class="dashboard-title">Total Active Member</div>
		<div class="dashboard-value"><?= $totalUserCount ?> pers</div>
	</div>
	<div class="dashboard-box">
		<div class="dashboard-title">Total Active Gadget</div>
		<div class="dashboard-value"><?= $totalGadgetCount ?> units</div>
	</div>
</div>

<div class="charts-container">
	<div class="chart-box">
		<div class="chart-title">Top 5 Best Selling Sold Gadgets</div>
		<?php if (empty($topSellingGadgets)): ?>
			<div>
				<p style="color: #777; font-size: 16px; text-align: center;">There are no gadgets placed.</p>
			</div>
		<?php else: ?>
			<div class="chart-box-content">
				<div class="bar-chart">
					<?php
					foreach ($topSellingGadgets as $index => $gadget) {
						$percentageValue = ($gadget['total_quantity_sold'] / $totalQty) * 100;

						echo "
						<div class='bar' style='height: {$percentageValue}%;' data-info='{$gadget['gadget_name']} - {$gadget['total_quantity_sold']} sold'>
							<span class='bar-value'>" . number_format($percentageValue, 2) . "%</span>
							<div class='bar-label'>{$gadget['gadget_name']}</div>
						</div>
					";
					}
					?>
				</div>
			</div>
		<?php endif; ?>
	</div>

	<div class="chart-box">
		<div class="chart-title">Product Categories</div>
		<div class="chart-box-content">
			<div class="pie-chart"></div>
			<div class="pie-chart-labels">
				<div class="legend"><span class="legend-color electronics"></span>Electronics - 30%</div>
				<div class="legend"><span class="legend-color clothing"></span>Clothing - 40%</div>
				<div class="legend"><span class="legend-color groceries"></span>Groceries - 30%</div>
				<div class="legend"><span class="legend-color tv"></span>Groceries - 30%</div>
			</div>
		</div>
	</div>
</div>

<div class="table-container">
	<div class="table-header">
		<h3>Low Gadget Stock</h3>
		<button data-get="../admin/admin_products.php?stock=10&operator=<">Show More</button>
	</div>
	<table class="table">
		<thead>
			<tr>
				<th>Gadget ID</th>
				<th>Name</th>
				<th>Category</th>
				<th>Stock Status</th>
				<th>Add Stock</th>
			</tr>
		</thead>
		<tbody>
			<?php if (empty($low_stock_gadgets)): ?>
				<tr>
					<td colspan="5">No low stock gadgets found.</td>
				</tr>
			<?php else: ?>
				<?php foreach ($low_stock_gadgets as $gadget): ?>
					<?php
					$row_class = $gadget->gadget_stock == 0 ? 'out-of-stock' : 'low-stock';
					$stock_status = $gadget->gadget_stock == 0 ? 'Out of Stock' : 'Low Stock';
					?>
					<tr class="<?= $row_class ?>">
						<td><?= $gadget->gadget_id ?></td>
						<td><?= $gadget->gadget_name ?></td>
						<td><?= $gadget->category_name ?></td>
						<td>
							<span class="stock-status">
								<?= $stock_status ?> (<?= $gadget->gadget_stock ?>)
							</span>
						</td>
						<td>
							<form method="post" class="add-stock-form">
								<input type="hidden" name="gadget_id" value="<?= $gadget->gadget_id ?>">
								<input type="number"
									name="add_stock"
									class="add-stock-input"
									min="1"
									max="100"
									required>
								<button type="submit" class="add-stock-btn">Add</button>
							</form>
						</td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
		</tbody>
	</table>
</div>

<!-- <img src="/photos/<?= $_admin->admin_profile_pic ?>">
<p>ID : <?= $_admin->admin_id ?></p>
<p>Name :<?= $_admin->admin_name ?></p>
<p>Phone Number :<?= $_admin->admin_phone_no ?></p>
<p>Email :<?= $_admin->admin_email ?></p> -->
