<link rel="stylesheet" href="/css/modalBox.css">
<?php
require_once '../_base.php';

// Initialize session storage for uploaded photos if not exists
if (!isset($_SESSION['uploaded_photos'])) {
    $_SESSION['uploaded_photos'] = [];
}

// Fetch categories and brands
$categories = $_db->query("SELECT category_name FROM category WHERE category_status = 'Active'")->fetchAll();
$brands = $_db->query("SELECT brand_name from brand WHERE brand_status = 'Active'")->fetchAll();

// Handle photo deletion request
if (is_post() && isset($_POST['action']) && $_POST['action'] === 'clear_photos') {
    if (isset($_SESSION['uploaded_photos'])) {
        foreach ($_SESSION['uploaded_photos'] as $photoPath) {
            $fullPath = realpath($photoPath);
            if ($fullPath && file_exists($fullPath)) {
                unlink($fullPath);
            }
        }
        unset($_SESSION['uploaded_photos']);
    }
    exit(json_encode(['success' => true]));
}

// Handle single photo deletion
if (is_post() && isset($_POST['filePath'])) {
    $webPath = $_POST['filePath'];
    $baseDirectory = realpath('../images/temp');

    $filePath = realpath($baseDirectory . DIRECTORY_SEPARATOR . basename($webPath));

    if ($filePath && strpos($filePath, $baseDirectory . DIRECTORY_SEPARATOR) === 0 && file_exists($filePath)) {
        if (unlink($filePath)) {
            if (isset($_SESSION['uploaded_photos'])) {
                $_SESSION['uploaded_photos'] = array_filter(
                    $_SESSION['uploaded_photos'],
                    fn($photo) => realpath($photo) !== $filePath
                );
            }
            echo json_encode(['success' => true, 'updatedPhotos' => $_SESSION['uploaded_photos']]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to delete the file.']);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid file path or file does not exist.']);
    }
    exit;
}

// Handle form submission
if (is_post()) {
    $files = get_files('photos');
    $gname = req('gname');
    $gcategory = req('gcategory');
    $gbrand = req('gbrand');
    $gdescribe = req('gdescribe');
    $gprice = req('gprice');
    $gstock = req('gstock');

    // Validate existing gadget
    $existingGadget = $_db->prepare('SELECT COUNT(*) FROM gadget WHERE gadget_name = ?');
    $existingGadget->execute([$gname]);
    $count = $existingGadget->fetchColumn();

    // Validate form inputs
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

    // Handle file uploads
    $allFiles = [];

    // Process new uploads
    if ($files && !empty($files)) {
        foreach ($files as $file) {
            if (!str_starts_with($file->type, 'image/')) {
                $_err['photos'] = 'Invalid format, all files must be images';
                break;
            } elseif ($file->size > 2 * 1024 * 1024) {
                $_err['photos'] = 'Each image must not exceed 2 MB';
                break;
            }

            if (is_uploaded_file($file->tmp_name)) {
                $filename = uniqid() . '.' . pathinfo($file->name, PATHINFO_EXTENSION);
                $destination = '../images/temp/' . $filename;

                if (move_uploaded_file($file->tmp_name, $destination)) {
                    if (!in_array($destination, $_SESSION['uploaded_photos'])) {
                        $_SESSION['uploaded_photos'][] = $destination;
                    }
                }
            }
        }
    }

    $_SESSION['uploaded_photos'] = array_values(array_filter(
        $_SESSION['uploaded_photos'],
        'file_exists'
    ));

    // Add existing uploaded files
    foreach ($_SESSION['uploaded_photos'] as $storedPhoto) {
        if (file_exists($storedPhoto)) {
            $allFiles[] = (object)[
                'name' => basename($storedPhoto),
                'tmp_name' => $storedPhoto,
                'type' => mime_content_type($storedPhoto),
                'size' => filesize($storedPhoto)
            ];
        }
    }

    if (empty($allFiles)) {
        $_err['photos'] = 'At least one photo is required';
    }

    // Process form if no errors
    if (!$_err) {
        $_db->beginTransaction();
        try {
            // Generate new gadget ID
            $newGadgetId = auto_id('gadget_id', 'gadget', 'GD_', '/GD_(\d{5})/', 5);

            // Process and save photos
            $photos = save_photos($allFiles, '../images');

            // Clean up temporary files
            if (isset($_SESSION['uploaded_photos'])) {
                foreach ($_SESSION['uploaded_photos'] as $photoPath) {
                    $fullPath = realpath($photoPath);
                    if ($fullPath && file_exists($fullPath)) {
                        unlink($fullPath);
                    }
                }
                unset($_SESSION['uploaded_photos']);
            }

            // Get category and brand IDs
            $fetchCategoryStmt = $_db->prepare('SELECT category_id FROM category WHERE category_name = ?');
            $fetchCategoryStmt->execute([$gcategory]);
            $categoryId = $fetchCategoryStmt->fetchColumn();

            $fetchBrandStmt = $_db->prepare('SELECT brand_id FROM brand WHERE brand_name = ?');
            $fetchBrandStmt->execute([$gbrand]);
            $brandId = $fetchBrandStmt->fetchColumn();

            // Insert gadget
            $stm = $_db->prepare('INSERT INTO gadget
                (gadget_id, admin_id, gadget_name, gadget_price, category_id, 
                gadget_description, brand_id, gadget_stock, gadget_status)
                VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?)');
            $stm->execute([
                $newGadgetId,
                'AD_00002', // You might want to make this dynamic
                strtoupper($gname),
                $gprice,
                $categoryId,
                $gdescribe,
                $brandId,
                $gstock,
                'Active'
            ]);

            // Insert photos into gallery
            $stm = $_db->prepare('INSERT INTO gallery (gallery_id, photo_path, gadget_id) VALUES (?, ?, ?)');
            foreach ($photos as $path) {
                $newGalleryId = auto_id('gallery_id', 'gallery', 'GA_', '/GA_(\d{5})/', 5);
                $stm->execute([$newGalleryId, $path, $newGadgetId]);
            }

            $_db->commit();
            temp('info', 'Gadget added successfully');
            redirect('/admin/admin_products.php');
        } catch (Exception $e) {
            $_db->rollBack();
            error_log("Error adding gadget: " . $e->getMessage());
            temp('error', 'An error occurred while adding the gadget');
        }
    }
} else {
    $_SESSION['uploaded_photos'] = $_SESSION['uploaded_photos'] ?? [];
}

$uploadedPhotos = $_SESSION['uploaded_photos'];
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
                        <img src="/images/defaultImage.png" id="defaultPreview" alt="Image Preview">
                    </label>

                    <div class="existing-images" style="display: none;">
                        <?php foreach (array_unique($uploadedPhotos) as $photo): ?>
                            <img
                                class="existing-image"
                                src="<?= htmlspecialchars('/images/temp/' . basename($photo)) ?>"
                                alt="Gadget Preview">
                        <?php endforeach; ?>
                    </div>
                </div>
                <button type="button" id="nextPhoto" class="carousel-btn">&gt;</button>
            </div>
            <div class="error-container">
                <?= err('photos') ?>
            </div>
            <div class="control-buttons">
                <button type="button" id="deletePhotos" class="control-btn delete-btn" style="display: none;">
                    Delete
                </button>
                <button type="button" id="addMorePhotos" class="control-btn add-btn" style="display: none;">
                    Add More
                </button>
            </div>

            <div class="input-container">
                <!-- Left side -->
                <div class="input-field">
                    <label for="gname">Gadget Name:</label>
                    <?= html_text('gname', 'maxlength="50"') ?>
                    <?= err('gname') ?>
                </div>

                <div class="input-field">
                    <label for="gprice">Gadget Price (RM):</label>
                    <input type="number" id="gprice" name="gprice" min="0.01" max="10000.00" step="0.01" value="<?php echo $gprice ?? ''; ?>" required>
                    <?= err('gprice') ?>
                </div>

                <div class="input-field">
                    <label for="gbrand">Gadget Brand:</label>
                    <?php
                    $brand_names = array_map(fn($brand) => $brand->brand_name, $brands);
                    html_select2('gbrand', $brand_names, '- Select a Brand -', $gbrand ?? '');
                    ?>
                    <?= err('gbrand') ?>
                </div>

                <div class="input-field">
                    <label for="gcategory">Gadget Category:</label>
                    <?php
                    $category_names = array_map(fn($category) => $category->category_name, $categories);
                    html_select2('gcategory', $category_names, '- Select a Category -', $gcategory ?? '');
                    ?>
                    <?= err('gcategory') ?>
                </div>

                <div class="input-field centered-field">
                    <label for="gstock">Gadget Stock:</label>
                    <input type="number" id="gstock" name="gstock" min="0" max="1000" step="1" value="<?php echo $gstock ?? ''; ?>" required>
                    <?= err('gstock') ?>
                </div>

                <!-- Description Field -->
                <div class="input-field full-width">
                    <label for="gdescribe">Gadget Description:</label>
                    <?= html_textarea('gdescribe', 'maxlength=100000') ?>
                    <?= err('gdescribe') ?>
                </div>
            </div>
            <section class="button-container">
                <button id="resetModalBtn" type="button">Reset</button>
                <button id="addBtn" data-confirm="Are you sure to add this gadget ?">Submit</button>
            </section>
        </div>
    </form>
</div>
<?php
include '../admin/admin_products.php'; ?>