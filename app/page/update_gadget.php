<?php
require_once '../_base.php';
//-----------------------------------------------------------------------------
$categories = $_db->query("SELECT category_name FROM category WHERE category_status = 'Active'")->fetchAll();
$brands = $_db->query("SELECT brand_name from brand WHERE brand_status = 'Active'")->fetchAll();

if (is_get()) {
    $id = req('id');

    $stm = $_db->prepare(
        'SELECT g.*, c.category_name, b.brand_name 
         FROM gadget g 
         JOIN category c ON g.category_id = c.category_id
         JOIN brand b ON g.brand_id = b.brand_id
         WHERE gadget_id = ?'
    );
    $stm->execute([$id]);
    $s = $stm->fetch();

    if (!$s) {
        redirect('admin_products.php');
    }

    $gname = $s->gadget_name;
    $gcategory = $s->category_name;
    $gbrand = $s->brand_name;
    $gdescribe = $s->gadget_description;
    $gprice = $s->gadget_price;
    $gstock = $s->gadget_stock;
    $_SESSION['photo'] = $s->gadget_photo;
}

if (is_post()) {
    $gid = req('id');
    $gname       = req('gname');
    $gcategory   = req('gcategory');
    $gbrand      = req('gbrand');
    $gdescribe   = req('gdescribe');
    $gprice      = req('gprice');
    $gstock      = req('gstock');
    $f     = get_file('photo');
    $photo =  $_SESSION['photo'];  

    if (empty($gname)) {
        $_err['gname'] = 'Gadget Name is required';
    } elseif (strlen($gname) > 25) {
        $_err['gname'] = 'Maximum length for Gadget Name is 25 characters';
    }

    if (empty($gcategory)) {
        $_err['gcategory'] = 'Gadget Category is required';
    }

    if (empty($gbrand)) {
        $_err['gbrand'] = 'Gadget Brand is required';
    }

    if (empty($gdescribe)) {
        $_err['gdescribe'] = 'Gadget Description is required';
    } elseif (strlen($gdescribe) > 10000) {
        $_err['gdescribe'] = 'Maximum length for Gadget Description is 10000 characters';
    }

    if ($gprice == ''||$gprice == 0) {
        $_err['gprice'] = 'Gadget Price is required';
    } elseif (!is_money($gprice)) {
        $_err['gprice'] = 'Gadget Price must in money format (Exp: RM XX.XX)';
    }

    if ($gstock == '') {
        $_err['gstock'] = 'Gadget Stock is required';
    } elseif (!is_numeric($gstock) || $gstock < 0 || $gstock > 1000) {
        $_err['gstock'] = 'Gadget Stock must ranged between 0 and 1000';
    }

    if ($f) {
        if (!str_starts_with($f->type, 'image/')) {
            $_err['photo'] = 'Must be image';
        }
        else if ($f->size > 1 * 1024 * 1024) {
            $_err['photo'] = 'Maximum 1MB';
        }
    }

    if (!$_err) {
        $gname = strtoupper($gname);

        if($f){
            unlink("../images/$photo");
            $photo = save_photo($f,'../images');
        }

        $stm = $_db->prepare('
            UPDATE gadget 
            SET 
                admin_id = ?, 
                gadget_name = ?, 
                gadget_price = ?, 
                category_id = (SELECT category_id FROM category WHERE category_name = ?), 
                gadget_description = ?, 
                brand_id = (SELECT brand_id FROM brand WHERE brand_name = ?), 
                gadget_stock = ?, 
                gadget_photo = ? 
            WHERE gadget_id = ?');

        $stm->execute(['AD_00002', $gname, $gprice, $gcategory, $gdescribe, $gbrand, $gstock, $photo, $gid]);

        temp('info', "Gadget ID : $gid updated successfuly");
        redirect('/page/admin_products.php');
    }
}
// ----------------------------------------------------------------------------
?>

<div class="form-container">
    <form method="post" id="form" enctype="multipart/form-data" novalidate>
        <div class="gadgetInfo">
            <span class="close">&times;</span>

            <label for="photo" class="upload" tabindex="0">
                <?= html_file('photo', 'image/*', 'hidden') ?>
                <img src="/images/<?= htmlspecialchars($photo) ?>" alt="Gadget Photo">
            </label>
            <?= err('photo') ?>

            <label for="gname">Gadget Name:</label>
            <?= html_text('gname', 'maxlength="50"') ?><br>
            <?= err('gname') ?>

            <label for="gcategory">Gadget Category:</label>
            <?php
            $category_names = array_map(fn($category) => $category->category_name, $categories);
            html_select('gcategory', $category_names);
            ?><br>
            <?= err('gcategory') ?>

            <label for="gbrand">Gadget Brand:</label>
            <?php
            $brand_names = array_map(fn($brand) => $brand->brand_name, $brands);
            html_select('gbrand', $brand_names);
            ?><br>
            <?= err('gbrand') ?>

            <label for="gdescribe">Gadget Description:</label>
            <textarea name="gdescribe"><?= htmlspecialchars($gdescribe ?? '') ?></textarea><br>
            <?= err('gdescribe') ?>

            <label for="gprice">Gadget Price:</label>
            <?= html_number('gprice', '10.00', ['min' => '0.01', 'max' => '10000.00', 'step' => '0.01']); ?><br>
            <?= err('gprice') ?>

            <label for="gstock">Gadget Stock:</label>
            <?= html_number('gstock', '0', ['min' => '0', 'max' => '1000', 'step' => '1']); ?><br>
            <?= err('gstock') ?>

            <section>
                <button id="resetModalBtn">Reset</button>
                <button data-confirm="Are you sure to modify this gadget info ?">Update</button>
            </section>
        </div>
    </form>
</div>
<?php
include '../page/admin_products.php';?>