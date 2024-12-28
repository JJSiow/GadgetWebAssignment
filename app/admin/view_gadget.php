<link rel="stylesheet" href="/css/modalBox.css">
<?php
require_once '../_base.php';
//----------------------------------------------------------------------------- 
auth_admin();
$id = req('id');
$stm = $_db->prepare(
    'SELECT g.*, c.category_name, b.brand_name 
     FROM gadget g 
     JOIN category c ON g.category_id = c.category_id
     JOIN brand b ON g.brand_id = b.brand_id
     WHERE gadget_id = ?'
);
$stm->execute([$id]);
$s = $stm->fetch(PDO::FETCH_OBJ);

$images = $_db->prepare('SELECT gallery_id, photo_path FROM gallery WHERE gadget_id = ?');
$images->execute([$id]);
$gadget_images = $images->fetchAll();

if (!$s) {
    redirect('/');
}

// Populate variables with fetched data
$gadgetid = htmlspecialchars($s->gadget_id);
$gadgetName = htmlspecialchars($s->gadget_name);
$categoryName = htmlspecialchars($s->category_name);
$brandName = htmlspecialchars($s->brand_name);
$description = htmlspecialchars($s->gadget_description);
$price = htmlspecialchars($s->gadget_price);
$stock = htmlspecialchars($s->gadget_stock);
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

            <div class="input-container">
                <div class="input-field">
                    <label for="gname">Gadget Name:</label>
                    <input type="text" name="gname" id="gname" value="<?= $gadgetName ?>" readonly>
                </div>

                <div class="input-field">
                    <label for="gprice">Gadget Price (RM):</label>
                    <input type="number" name="gprice" id="gprice" value="<?= $price ?>" readonly>
                </div>

                <div class="input-field">
                    <label for="gbrand">Gadget Brand:</label>
                    <input type="text" name="gbrand" id="gbrand" value="<?= $brandName ?>" readonly>
                </div>

                <div class="input-field">
                    <label for="gcategory">Gadget Category:</label>
                    <input type="text" name="gcategory" id="gcategory" value="<?= $categoryName ?>" readonly>
                </div>

                <div class="input-field centered-field">
                    <label for="gstock">Gadget Stock:</label>
                    <input type="number" name="gstock" id="gstock" value="<?= $stock ?>" readonly>
                </div>

                <div class="input-field full-width">
                    <label for="gdescribe">Gadget Description:</label>
                    <textarea name="gdescribe" id="gdescribe" readonly><?= $description ?></textarea>
                </div>
            </div>
            <section class="button-container">
                <button data-get="update_gadget.php?id=<?= $gadgetid ?>">Edit Now</button>
            </section>
        </div>
    </form>
</div>


<?php
include '../admin/admin_products.php';
?>