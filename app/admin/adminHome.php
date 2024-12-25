<?php
require '../_base.php';
//-----------------------------------------------------------------------------

if (!empty($_SESSION["admin"])) {
	// $admin_id = $_SESSION["admin_id"];

	//  $stm = $_db->prepare('SELECT * FROM admin WHERE admin_id = ?');
	//  $stm->execute([$admin_id]);
	//  $admin = $stm->fetch(PDO::FETCH_ASSOC);
} else {
	temp('info', 'Please login');
	redirect('adminLogin.php');
}
// if ($_admin == null) {
//     temp('info', 'Please login as admin');
//     redirect('/');
// }

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
// ----------------------------------------------------------------------------
$_title = '';
include '../admin/_adminHead.php';
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
		<div class="chart-title">Top 5 Best Selling Gadget</div>
		<div class="chart-box-content">
			<div class="bar-chart">
				<div class="bar" style="height: 50%;" data-info="January: $5000">
					<span class="bar-value">$5000</span>
					<div class="bar-label">Jan</div>
				</div>
				<div class="bar" style="height: 70%;" data-info="February: $7000">
					<span class="bar-value">$7000</span>
					<div class="bar-label">Feb</div>
				</div>
				<div class="bar" style="height: 80%;" data-info="March: $8000">
					<span class="bar-value">$8000</span>
					<div class="bar-label">Mar</div>
				</div>
				<div class="bar" style="height: 60%;" data-info="April: $6000">
					<span class="bar-value">$6000</span>
					<div class="bar-label">Apr</div>
				</div>
				<div class="bar" style="height: 90%;" data-info="May: $9000">
					<span class="bar-value">$9000</span>
					<div class="bar-label">May</div>
				</div>
			</div>
		</div>
	</div>

	<div class="chart-box">
		<div class="chart-title">Product Categories</div>
		<div class="chart-box-content">
			<div class="pie-chart"></div>
			<div class="pie-chart-labels">
				<div class="legend"><span class="legend-color electronics"></span>Electronics - 30%</div>
				<div class="legend"><span class="legend-color clothing"></span>Clothing - 40%</div>
				<div class="legend"><span class="legend-color groceries"></span>Groceries - 30%</div>
			</div>
		</div>
	</div>
</div>

<div class="table-container">
	<div class="table-header">
		<h3>Low Stock Gadget</h3>
		<button class="show-more">Show More</button>
	</div>
	<table>
		<thead>
			<tr>
				<th>ID</th>
				<th>Name</th>
				<th>Category</th>
				<th>Status</th>
			</tr>
		</thead>
		<tbody>
			<tr>
				<td>1</td>
				<td>Product A</td>
				<td>Electronics</td>
				<td>Available</td>
			</tr>
			<tr>
				<td>2</td>
				<td>Product B</td>
				<td>Clothing</td>
				<td>Out of Stock</td>
			</tr>
			<tr>
				<td>3</td>
				<td>Product C</td>
				<td>Groceries</td>
				<td>Available</td>
			</tr>
			<tr>
				<td>4</td>
				<td>Product D</td>
				<td>Electronics</td>
				<td>Available</td>
			</tr>
			<tr>
				<td>5</td>
				<td>Product E</td>
				<td>Clothing</td>
				<td>Out of Stock</td>
			</tr>
		</tbody>
	</table>
</div>

<img src="/photos/<?= $_admin->admin_profile_pic ?>">
<p>ID : <?= $_admin->admin_id ?></p>
<p>Name :<?= $_admin->admin_name ?></p>
<p>Phone Number :<?= $_admin->admin_phone_no ?></p>
<p>Email :<?= $_admin->admin_email ?></p>

<?php
// include '../_foot.php';
