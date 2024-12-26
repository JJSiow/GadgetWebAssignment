<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $_title ?? 'Untitled' ?></title>
    <link rel="shortcut icon" href="/images/favicon.png">
    <link rel="stylesheet" href="/css/footer_nav.css">
    <link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
</head>
<?php
include '_footer_nav.php';
?>
    <div class="footer">
    <footer>
        
        Developed by <b>SEO GADGET</b> &middot;
        Copyrighted &copy; <?= date('Y') ?>
    </footer>
    </div>
</body>
</html>

<style>



        .footer {
            bottom: 0;
            left: 0;
            width: 100%;
            text-align: center;
            background-color:rgb(24, 24, 24);
            color: #fff;
            padding: 10px 0;
        }

        .footer b {
            color:rgb(129, 214, 236);
        }
    </style>