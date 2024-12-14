<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $_title ?? 'Untitled' ?></title>
    <link rel="shortcut icon" href="/images/favicon.png">
    <link rel="stylesheet" href="/css/app.css">
    <link rel="stylesheet" href="/css/admin.css">
    <!-- <link rel="stylesheet" href="/css/sidebar.css"> -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="/js/app.js"></script>
    <script src="/js/admin.js"></script>
</head>

<header>
    <img class="logo" src="images/sd.jpg" alt="logo">
    <nav class="user_menu_bar">
        <ul>
            <li><a href="/">Dashboard</a></li>
            <li><a href="../member/login.php">Login</a></li>
            <li><a href="../member/logout.php">Logout</a></li>
            <li><a href="../admin/adminHome.php">Admin Home</a></li>
            <li><a href="../admin/adminLogin.php">Admin Login</a></li>
            <li><a href="../admin/adminLogout.php">Admin Logout</a></li>
            <li><a href="../admin/member_list.php">Member</a></li>
            <li><a href="/member/member_profile.php">Member Profile</a></li>
            <li><a href="/member/member_password.php">Member Password</a></li>
            <li><a href="/admin/admin_list.php">Admin List</a></li>
            <li><a href="/admin/admin_profile.php">Admin Profile</a></li>
        </ul>
    </nav>
    <a class="logout" href="../member/register.php"><img class="logo" src="images/sda.jpg" alt="logo"></a>
    <a class="logout" href="../member/register.php"><img class="logo" src="images/sad.jpg" alt="logo"></a>
</header>

<body>
    <!-- Flash message -->
    <div id="info"><?= temp('info') ?></div>

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