<link rel="stylesheet" href="/css/modalBox.css">
<?php
require_once '../_base.php';
auth_admin();
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
    } elseif (strlen($gname) > 100) {
        $_err['gname'] = 'Maximum length for Gadget Name is 100 characters';
    }

    if (empty($gcategory)) {
        $_err['gcategory'] = 'Gadget Category is required';
    }

    if (empty($gbrand)) {
        $_err['gbrand'] = 'Gadget Brand is required';
    }

    if (empty($gdescribe)) {
        $_err['gdescribe'] = 'Gadget Description is required';
    } elseif (strlen($gdescribe) > 100000) {
        $_err['gdescribe'] = 'Maximum length for Gadget Description is 100,000 characters';
    }

    if ($gprice == '' || $gprice == 0) {
        $_err['gprice'] = 'Gadget Price is required';
    } elseif (!is_money($gprice)) {
        $_err['gprice'] = 'Gadget Price must in money format (Exp: RM XX.XX)';
    } elseif ($gprice <= 0.01 || $gprice > 10000.00) {
        $_err['gprice'] = 'Gadget Price must between RM 0.01 and RM 10000.00';
    }

    if ($gstock == '') {
        $_err['gstock'] = 'Gadget Stock is required';
    } elseif ($gstock < 0 || $gstock > 1000) {
        $_err['gstock'] = 'Gadget Stock must ranged between 0 and 1000';
    } else if (!is_numeric($gstock)) {
        $_err['gstock'] = 'Gadget Stock must be number';
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

        $stm->execute(['A01', $gname, $gprice, $gcategory, $gdescribe, $gbrand, $gstock, $gid]);

        $stm = $_db->prepare('INSERT INTO gallery (gallery_id, photo_path, gadget_id) VALUES (?, ?, ?)');
        foreach ($photos as $path) {
            $newGalleryId = auto_id('gallery_id', 'gallery', 'GA_', '/GA_(\d{5})/', 5);
            $stm->execute([$newGalleryId, $path, $gid]);
        }

        temp('info', "Gadget ID : $gid updated successfuly");
        redirect('/admin/admin_products.php');
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
            if (isset($_SESSION['merged_files'])) {
                foreach ($_SESSION['merged_files'] as $index => $file) {
                    if (isset($file->path) && $file->path === "../images/$photo_path") {
                        unset($_SESSION['merged_files'][$index]);
                        break;
                    }
                }
                $_SESSION['merged_files'] = array_values($_SESSION['merged_files']);
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
                </div>
                <button type="button" id="nextPhoto" class="carousel-btn" style="display: none;">&gt;</button>
            </div>

            <div class="error-container">
                <?= err('photos') ?>
            </div>

            <!-- Control buttons -->
            <div class="control-buttons">
                <button type="button" id="deletePhotos" class="control-btn delete-btn" style="display: none; margin: 0 5px;">
                    Delete
                </button>
                <button type="button" id="addMorePhotos" class="control-btn add-btn" style="display: none; margin: 0 5px;">
                    Add More
                </button>
            </div>

            <div class="input-container">
                <div class="input-field">
                    <label for="gname">Gadget Name:</label>
                    <?= html_text('gname', 'maxlength="50"') ?>
                    <?= err('gname') ?>
                </div>

                <div class="input-field">
                    <label for="gprice">Gadget Price (RM):</label>
                    <?= html_number('gprice', $gprice, ['min' => '0.01', 'max' => '10000.00', 'step' => '0.01']); ?>
                    <?= err('gprice') ?>
                </div>

                <div class="input-field">
                    <label for="gbrand">Gadget Brand:</label>
                    <?php
                    $brand_names = array_map(fn($brand) => $brand->brand_name, $brands);
                    html_select2('gbrand', $brand_names, '- Select One -', $gbrand ?? '');
                    ?>
                    <?= err('gbrand') ?>
                </div>

                <div class="input-field">
                    <label for="gcategory">Gadget Category:</label>
                    <?php
                    $category_names = array_map(fn($category) => $category->category_name, $categories);
                    html_select2('gcategory', $category_names, '- Select One -', $gcategory ?? '',);
                    ?>
                    <?= err('gcategory') ?>
                </div>

                <div class="input-field centered-field">
                    <label for="gstock">Gadget Stock:</label>
                    <?= html_number('gstock', $gstock, ['min' => '0', 'max' => '1000', 'step' => '1']); ?>
                    <?= err('gstock') ?>
                </div>

                <div class="input-field full-width">
                    <label for="gdescribe">Gadget Description:</label>
                    <?php html_textarea('gdescribe'); ?>
                    <?= err('gdescribe') ?>
                </div>
            </div>
            <section class="button-container">
                <button id="resetModalBtn" type="button">Reset</button>
                <button id="updateBtn" data-confirm="Are you sure to modify this gadget info ?">Update</button>
            </section>
        </div>
    </form>
</div>
<?php
include '../admin/admin_products.php'; ?>