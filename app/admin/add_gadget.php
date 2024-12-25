<?php
require_once '../_base.php';

//-----------------------------------------------------------------------------
$categories = $_db->query("SELECT category_name FROM category WHERE category_status = 'Active'")->fetchAll();
$brands = $_db->query("SELECT brand_name from brand WHERE brand_status = 'Active'")->fetchAll();

if (is_post()) {
    $files = get_files('photos');
    $gname       = req('gname');
    $gcategory   = req('gcategory');
    $gbrand      = req('gbrand');
    $gdescribe   = req('gdescribe');
    $gprice      = req('gprice');
    $gstock      = req('gstock');

    $existingGadget = $_db->prepare('SELECT COUNT(*) FROM gadget WHERE gadget_name = ?');
    $existingGadget->execute([$gname]);
    $count = $existingGadget->fetchColumn();

    if (!$files || count($files) === 0) {
        $_err['photos'] = 'At least one photo is required';
    } else {
        foreach ($files as $file) {
            if (!str_starts_with($file->type, 'image/')) {
                $_err['photos'] = 'Invalid format, all files must be images';
                break;
            } elseif ($file->size > 2 * 1024 * 1024) {
                $_err['photos'] = 'Each image must not exceed 2 MB';
                break;
            }
        }
    }

    if (empty($gname)) {
        $_err['gname'] = 'Gadget Name is required';
    } elseif (strlen($gname) > 25) {
        $_err['gname'] = 'Maximum length for Gadget Name is 25 characters';
    } elseif ($count > 0) {
        $_err['gname'] = 'Gadget with this name already exists';
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

    if ($gprice == '') {
        $_err['gprice'] = 'Gadget Price is required';
    } elseif (!is_money($gprice)) {
        $_err['gprice'] = 'Gadget Price must in money format (Exp: RM XX.XX)';
    } elseif ($gprice <= 0.01 || $gprice > 10000.00) {
        $_err['gprice'] = 'Gadget Price must between RM 0.01 and RM 10000.00';
    }

    if ($gstock == '') {
        $_err['gstock'] = 'Gadget Price is required';
    } elseif (!is_numeric($gstock) || $gstock < 0 || $gstock > 1000) {
        $_err['gstock'] = 'Gadget Stock must ranged between 0 and 1000';
    }

    $_db->beginTransaction();
    if (!$_err) {
        $newGadgetId = auto_id('gadget_id', 'gadget', 'GD_', '/GD_(\d{5})/', 5);

        $photos = save_photos($files, '../images');
        $gname = strtoupper($gname);

        $fetchCategoryStmt = $_db->prepare('SELECT category_id FROM category WHERE category_name = ?');
        $fetchCategoryStmt->execute([$gcategory]);
        $categoryId = $fetchCategoryStmt->fetchColumn();

        $fetchBrandStmt = $_db->prepare('SELECT brand_id FROM brand WHERE brand_name = ?');
        $fetchBrandStmt->execute([$gbrand]);
        $brandId = $fetchBrandStmt->fetchColumn();

        $stm = $_db->prepare('INSERT INTO gadget
        (gadget_id, admin_id, gadget_name, gadget_price, category_id, gadget_description, brand_id, gadget_stock, gadget_status)
        VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $stm->execute([$newGadgetId, 'AD_00002', $gname, $gprice, $categoryId, $gdescribe, $brandId, $gstock, 'Active']);

        // Insert photos into gallery
        $stm = $_db->prepare('INSERT INTO gallery (gallery_id, photo_path, gadget_id) VALUES (?, ?, ?)');
        foreach ($photos as $path) {
            $newGalleryId = auto_id('gallery_id', 'gallery', 'GA_', '/GA_(\d{5})/', 5);
            $stm->execute([$newGalleryId, $path, $newGadgetId]);
        }
        $_db->commit();

        temp('info', 'Gadget added successfuly');
        redirect('/admin/admin_products.php');
    }
}

// ----------------------------------------------------------------------------
?>

<div class="form-container">
    <form method="post" id="form" enctype="multipart/form-data" novalidate>
        <div class="gadgetInfo">
            <span class="close">&times;</span>

            <div class="image-carousel">
                <button type="button" id="prevPhoto" class="carousel-btn">&lt;</button>
                <div class="image-preview-container">
                    <label for="photos[]" class="upload_gadget" tabindex="0">
                        <?= html_file('photos[]', 'image/*', 'multiple hidden') ?>
                        <img src="/images/defaultImage.png" id="defaultPreview">
                    </label>
                    <button type="button" id="deletePhoto" class="control-btn delete-btn" style="display: none;">
                        Delete
                    </button>
                    <button type="button" id="addMorePhotos" class="control-btn add-btn" style="display: none;">
                        Add More
                    </button>
                </div>
                <button type="button" id="nextPhoto" class="carousel-btn">&gt;</button>
            </div>
            <?= err('photos') ?>

            <label for="gname">Gadget Name:</label>
            <?= html_text('gname', 'maxlength="50"') ?><br>
            <?= err('gname') ?>

            <label for="gcategory">Gadget Category:</label>
            <?php
            $category_names = array_map(fn($category) => $category->category_name, $categories);
            html_select2('gcategory', $category_names, '- Select a Category -', $gcategory ?? '');
            ?><br>

            <?= err('gcategory') ?>

            <label for="gbrand">Gadget Brand:</label>
            <?php
            $brand_names = array_map(fn($brand) => $brand->brand_name, $brands);
            html_select2('gbrand', $brand_names, '- Select a Brand -', $gbrand ?? '');
            ?><br>
            <?= err('gbrand') ?>

            <label for="gprice">Gadget Price:</label>
            <input type="number" id="gprice" name="gprice" min="0.01" max="10000.00" step="0.01" value="<?php echo $gprice; ?>" required><br>
            <?= err('gprice') ?>

            <label for="gstock">Gadget Stock:</label>
            <input type="number" id="gstock" name="gstock" min="0" max="1000" step="1" value="<?php echo $gstock; ?>" required><br>
            <?= err('gstock') ?>

            <label for="gdescribe">Gadget Description:</label>
            <?= html_textarea('gdescribe', 'maxlength=100000') ?><br>
            <?= err('gdescribe') ?>

            <section>
                <button id="resetModalBtn" type="button">Reset</button>
                <button>Submit</button>
            </section>
        </div>
    </form>
</div>
<?php
include '../admin/admin_products.php'; ?>