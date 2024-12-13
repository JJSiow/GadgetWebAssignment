<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $_title ?? 'Untitled' ?></title>
    <link rel="shortcut icon" href="/images/favicon.png">
    <link rel="stylesheet" href="/css/app.css">
    <!-- <link rel="stylesheet" href="/css/sidebar.css"> -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="/js/app.js"></script>
</head>

<body>
    <!-- Flash message -->
    <div id="info"><?= temp('info') ?></div>

    <header>
        <h1><a href="/">Admin Panel</a></h1>
    </header>

    <nav>
        <a href="/">Dashboard</a>
        <a href="/page/admin_products.php">Product</a>
        <a href="/page/admin_voucher.php">Voucher</a>
        <a href="/page/admin_member.php">Member</a>
        <a href="/page/admin_category.php">Category</a>
        <a href="/page/admin_brand.php">Brand</a>
    </nav>

    <!-- <nav class="image-text">
        <span class="image">
            <img src="" alt="logo">
        </span>

        <div class="text header-text">
            <span class="name">CodingLab</span>
            <span class="profession">Web developer</span>
        </div>

        <span class="material-symbols-outlined">
            chevron_right
        </span>
    </nav> -->


    <main>
        <h1><?= $_title ?? 'Untitled' ?></h1>