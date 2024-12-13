<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $_title ?? 'Untitled' ?></title>
    <link rel="stylesheet" href="/css/admin.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="/js/admin.js"></script>
</head>
<body>
    <!-- Flash message -->
    <div id="info"><?= temp('info') ?></div>

    <header>
        <h1><a href="/">X</a></h1>
    </header>

    <nav>
        <a href="/">Index</a>
        <a href="/member/member_list.php">Member</a>
        <a href="/member/member_profile.php">Member Profile</a>
        <a href="/member/member_password.php">Member Password</a>
        <a href="/admin/admin.php">Admin</a>
    </nav>

    <main>
        <h1><?= $_title ?? 'Untitled' ?></h1>