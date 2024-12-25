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

    $images = $_db->prepare('SELECT gallery_id, photo_path FROM gallery WHERE gadget_id = ?');
    $images->execute([$id]);
    $gadget_images = $images->fetchAll();

    // Initialize merged_files as an array
    $_SESSION['merged_files'] = []; 

    foreach ($gadget_images as $image) {
        // Add existing images to the merged array
        $_SESSION['merged_files'][] = (object)[
            'type' => 'image/*',
            'size' => filesize("../images/{$image->photo_path}"),
            'path' => "../images/{$image->photo_path}"
        ];
    }
}

if (is_post()) {
    $gid = req('id');
    $gname       = req('gname');
    $gcategory   = req('gcategory');
    $gbrand      = req('gbrand');
    $gdescribe   = req('gdescribe');
    $gprice      = req('gprice');
    $gstock      = req('gstock');
    $files = get_files('photos');
    $merged_files = array_merge($_SESSION['merged_files'] ?? [], $files ?? []); 

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

    if ($gprice == '' || $gprice == 0) {
        $_err['gprice'] = 'Gadget Price is required';
    } elseif (!is_money($gprice)) {
        $_err['gprice'] = 'Gadget Price must in money format (Exp: RM XX.XX)';
    }

    if ($gstock == '') {
        $_err['gstock'] = 'Gadget Stock is required';
    } elseif (!is_numeric($gstock) || $gstock < 0 || $gstock > 1000) {
        $_err['gstock'] = 'Gadget Stock must ranged between 0 and 1000';
    }

    if (count($merged_files) === 0) {
        $_err['photos'] = 'At least one photo is required';
    } else {
        foreach ($merged_files as $file) {
            if (isset($file->type) && !str_starts_with($file->type, 'image/')) {
                $_err['photos'] = 'Invalid format, all files must be images';
                break;
            } elseif ($file->size > 2 * 1024 * 1024) {
                $_err['photos'] = 'Each image must not exceed 2 MB';
                break;
            }
        }
    }

    if (!$_err) {
        $gname = strtoupper($gname);
        $photos = save_photos($files, '../images');

        $stm = $_db->prepare('
        UPDATE gadget 
        SET 
            admin_id = ?, 
            gadget_name = ?, 
            gadget_price = ?, 
            category_id = (SELECT category_id FROM category WHERE category_name = ? AND category_status = "Active"), 
            gadget_description = ?, 
            brand_id = (SELECT brand_id FROM brand WHERE brand_name = ? AND brand_status = "Active"), 
            gadget_stock = ?
        WHERE gadget_id = ?');

        $stm->execute(['AD_00002', $gname, $gprice, $gcategory, $gdescribe, $gbrand, $gstock, $gid]);

        $stm = $_db->prepare('INSERT INTO gallery (gallery_id, photo_path, gadget_id) VALUES (?, ?, ?)');
        foreach ($photos as $path) {
            $newGalleryId = auto_id('gallery_id', 'gallery', 'GA_', '/GA_(\d{5})/', 5);
            $stm->execute([$newGalleryId, $path, $gid]);
        }

        temp('info', "Gadget ID : $gid updated successfuly");
        redirect('/page/admin_products.php');
    }

    if (isset($_POST['action']) && $_POST['action'] === 'delete_image') {
        $gallery_id = $_POST['gallery_id'];
        $photo_path = $_POST['photo_path'];

        try {
            $stm = $_db->prepare('DELETE FROM gallery WHERE gallery_id = ?');
            $stm->execute([$gallery_id]);

            if (file_exists("../images/$photo_path")) {
                unlink("../images/$photo_path");
            }
            echo json_encode(['success' => true]);
            exit;
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            exit;
        }
    }
}
// ----------------------------------------------------------------------------
?>

<div class="form-container">
    <form method="post" id="form" enctype="multipart/form-data" novalidate>
        <div class="gadgetInfo">
            <span class="close">&times;</span>

            <div class="image-carousel">
                <button type="button" id="prevPhoto" class="carousel-btn" style="display: none;">&lt;</button>

                <div class="image-preview-container">
                    <label for="photos[]" class="upload_gadget" tabindex="0">
                        <?= html_file('photos[]', 'image/*', 'multiple hidden') ?>
                        <img id="defaultPreview" class="main-preview" alt="Gadget Preview">
                    </label>

                    <!-- Hidden container for existing images -->
                    <div class="existing-images" style="display: none;">
                        <?php foreach ($gadget_images as $image): ?>
                            <img
                                class="existing-image"
                                src="<?= htmlspecialchars('../images/' . $image->photo_path) ?>"
                                data-id="<?= htmlspecialchars($image->gallery_id) ?>"
                                alt="Gadget Preview">
                        <?php endforeach; ?>
                    </div>

                    <!-- Control buttons -->
                    <div class="control-buttons" style="margin-top: 10px; text-align: center;">
                        <button type="button" id="deletePhotos" class="control-btn delete-btn" style="display: none; margin: 0 5px;">
                            Delete
                        </button>
                        <button type="button" id="addMorePhotos" class="control-btn add-btn" style="display: none; margin: 0 5px;">
                            Add More
                        </button>
                    </div>

                    <!-- Image counter -->
                    <div id="imageCounter" class="image-counter" style="text-align: center; margin-top: 5px; display: none;"></div>
                </div>

                <button type="button" id="nextPhoto" class="carousel-btn" style="display: none;">&gt;</button>
            </div>
            <?= err('photos') ?>

            <label for="gname">Gadget Name:</label>
            <?= html_text('gname', 'maxlength="50"') ?><br>
            <?= err('gname') ?>

            <label for="gcategory">Gadget Category:</label>
            <?php
            $category_names = array_map(fn($category) => $category->category_name, $categories);
            html_select2('gcategory', $category_names, '- Select One -', $gcategory ?? '',);
            ?><br>
            <?= err('gcategory') ?>

            <label for="gbrand">Gadget Brand:</label>
            <?php
            $brand_names = array_map(fn($brand) => $brand->brand_name, $brands);
            html_select2('gbrand', $brand_names, '- Select One -', $gbrand ?? '');
            ?><br>
            <?= err('gbrand') ?>

            <label for="gprice">Gadget Price:</label>
            <?= html_number('gprice', $gprice, ['min' => '0.01', 'max' => '10000.00', 'step' => '0.01'], 'RM '); ?><br>
            <?= err('gprice') ?>

            <label for="gstock">Gadget Stock:</label>
            <?= html_number('gstock', $gstock, ['min' => '0', 'max' => '1000', 'step' => '1']); ?><br>
            <?= err('gstock') ?>

            <label for="gdescribe">Gadget Description:</label>
            <?php html_textarea('gdescribe'); ?><br>
            <?= err('gdescribe') ?>

            <section>
                <button id="resetModalBtn" type="button">Reset</button>
                <button id="updateBtn" data-confirm="Are you sure to modify this gadget info ?">Update</button>
            </section>
        </div>
    </form>
</div>
<?php
include '../page/admin_products.php'; ?>