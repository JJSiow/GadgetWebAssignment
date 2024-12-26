<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $_title ?? 'Untitled' ?></title>
    <link rel="shortcut icon" href="/images/favicon.png">
    <link rel="stylesheet" href="/css/adminNav.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="/js/app.js"></script>
    <script src="/js/multiple_photo.js"></script>
    <script src="/js/view_gadget.js"></script>
    <script src="/js/chart.js"></script>
    <link rel="stylesheet" href="/css/app.css">
</head>

<body>
    <!-- Flash message -->
    <div id="info"><?= temp('info') ?></div>
    
    <header>
        <img class="logo" src="images/default_user.jpg" alt="logo">
        <nav class="admin_menu_bar">
            <ul>
            <li><a href="../index.php">User Login</a></li>
            <!-- <li><a href="../admin/admin_logout.php">Admin Logout</a></li> -->
            <li><a href="/page/admin_products.php">Product</a></li>
            <li><a href="/page/admin_voucher.php">Voucher</a></li>
            <li><a href="/admin/member_list.php">Member List</a></li>
            <li><a href="/page/admin_order.php">Order</a></li>
            <li><a href="/page/admin_category.php">Category</a></li>
            <li><a href="/page/admin_brand.php">Brand</a></li>
            <?php if ($_admin->is_super_admin == 'Y'): ?>
            <li><a href="/admin/admin_list.php">Admin List</a></li>
            <?php endif ?>
            <li><a href="/admin/admin_profile.php">Admin Profile</a></li>

            </ul>
        </nav>
        <a class="logout" href="../admin/admin_logout.php"><button>Logout</button></a>
    </header>
    <main>
        <h1><?= $_title ?? 'Untitled' ?></h1>