<link rel="stylesheet" href="/css/adminHome.css">
<?php
require '../_base.php';
//-----------------------------------------------------------------------------

auth_admin();

$orderPrices = $_db->query("SELECT * FROM `order` WHERE order_status = 'delivered'")->fetchAll(PDO::FETCH_ASSOC);
$totalUser = $_db->query("SELECT COUNT(member_id) AS total FROM `member` WHERE member_status = 'Active'")->fetch(PDO::FETCH_ASSOC);
$totalGadget = $_db->query("SELECT COUNT(gadget_id) AS total FROM `gadget` WHERE gadget_status = 'Active'")->fetch(PDO::FETCH_ASSOC);
$totalSales = 0;

$totalUserCount = $totalUser['total'];
$totalGadgetCount = $totalGadget['total'];

foreach ($orderPrices as $orderPrice) {
	$totalSales += $orderPrice['total_order_price'];
}

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
	redirect("admin_home.php");
}
// ----------------------------------------------------------------------------
$_title = '';
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
		<div id="barChartContainer" style="height: 370px; width: 100%;"></div>
		<script src="https://canvasjs.com/assets/script/jquery-1.11.1.min.js"></script>
		<script src="https://cdn.canvasjs.com/jquery.canvasjs.min.js"></script>
	</div>

	<div class="chart-box">
		<div id="doughnutChartContainer" style="height: 100%; width: 100%;"></div>
		<button class="btn invisible" id="backButton">
			Back</button>
		<script src="https://canvasjs.com/assets/script/jquery-1.11.1.min.js"></script>
		<script src="https://cdn.canvasjs.com/jquery.canvasjs.min.js"></script>
	</div>
</div>

<div class="table-container">
	<div class="table-header">
		<h3>Low Active Gadget Stock</h3>
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