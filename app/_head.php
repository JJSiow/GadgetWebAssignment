<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $_title ?? 'Untitled' ?></title>
    <link rel="shortcut icon" href="/images/favicon.png">
    <link rel="stylesheet" href="/css/app.css"> 
    <link rel="stylesheet" href="/css/admin.css">
    <link rel="stylesheet" href="/css/header.css">
    <!-- <link rel="stylesheet" href="/css/sidebar.css"> -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="/js/app.js"></script>
    <script src="/js/admin.js"></script>
    <script src="/js/multiple_photo.js"></script>
    <script src="/js/view_gadget.js"></script>
</head>

<body>
    <!-- Flash message -->
    <div id="info"><?= temp('info') ?></div>
    
    <div class="menu">
        <nav>
        <a href="/page/gadget.php"><img src="/images/logo2.png" class="logo"></a>
            <ul>
                <li><a href="/admin/adminHome.php">Admin Home</a></li>
                <li><a href="/admin/adminLogin.php">Admin Login</a></li>
                <li><a href="/admin/adminLogout.php">Admin Logout</a></li>
                <li><a href="/admin/member_list.php">Member</a></li>
                <li><a href="/admin/admin_list.php">Admin List</a></li>
                <li><a href="/admin/admin_profile.php">Admin Profile</a></li>
                <li><a href="/page/gadget.php">Product</a></li>
            </ul>
            <?php if ($_member): ?>
                <img src="/photos/<?= $_member->member_profile_pic?>" class="user_pic" onclick="tonggleMenu()">
                <a href="/page/order_cart.php"><img src="/images/cart.jpg" class="user_pic" onclick=""></a>
                <div class="sub_menu_profile" id="subMenu">
                    <div class="sub_menu">
                        <div class="user_info">

                        <img src="/photos/<?= $_member->member_profile_pic ?>">
                        <h3><?= $_member->member_name ?></h3>
                        </div>
                        <hr>

                        <a href="/member/member_profile.php" class="sub_menu_link">
                            <p>Member Profile</p>
                            <span>></span>
                        </a>

                        <a href="/member/member_password.php" class="sub_menu_link">
                            <p>Member Password</p>
                            <span>></span>
                        </a>

                        <a href="/member/address_book.php" class="sub_menu_link">
                            <p>Shipping Address</p>
                            <span>></span>
                        </a>

                        <a href="/page/order_item.php" class="sub_menu_link">
                            <p>Order Item</p>
                            <span>></span>
                        </a>

                        <a href="/logout.php" class="sub_menu_link">
                            <p>Logout</p>
                            <span>></span>
                        </a>
                    </div>
                </div>
                <?php else: ?>
                    <img src="/images/default_user.jpg" class="user_pic" onclick="tonggleMenu()">
                <a href="/login.php" ><img src="/images/cart.jpg" class="user_pic" onclick="returnLogin()"></a>
                <div class="sub_menu_profile" id="subMenu">
                    <div class="sub_menu">
                        <div class="user_info">

                            <img src="/images/default_user.jpg">
                            <h3>Unknown</h3>
                        </div>
                        <hr>

                        <a href="/login.php" class="sub_menu_link">
                            <p>Log In</p>
                            <span>></span>
                        </a>

                        <a href="/member/register.php" class="sub_menu_link">
                            <p>Sign Up</p>
                            <span>></span>
                        </a>
                    </div>
                </div>

                    <?php endif ?> 
        </nav>

    </div>

    <script>
        let subMenu = document.getElementById("subMenu");

        function tonggleMenu() {
            subMenu.classList.toggle("open-menu");
        }

        function returnLogin(){
            temp('info', 'You need to login');
        }
    </script>
</body>