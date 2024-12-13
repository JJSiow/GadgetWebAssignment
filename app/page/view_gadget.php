<?php
require_once '../_base.php';
//-----------------------------------------------------------------------------

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

            <label for="photo" class="upload" tabindex="0">
                <?= html_file('photo', 'image/*', 'hidden') ?>
                <img src="<?= '/images/' . ($s->photo ?? 'defaultImage.jpg') ?>" id="default_pic" alt="Gadget Image">
            </label>

            <label for="gname">Gadget Name:</label>
            <input type="text" name="gname" id="gname" value="<?= $gadgetName ?>" readonly><br>

            <label for="gcategory">Gadget Category:</label>
            <input type="text" name="gcategory" id="gcategory" value="<?= $categoryName ?>" readonly><br>

            <label for="gbrand">Gadget Brand:</label>
            <input type="text" name="gbrand" id="gbrand" value="<?= $brandName ?>" readonly><br>

            <label for="gdescribe">Gadget Description:</label>
            <textarea name="gdescribe" id="gdescribe" readonly><?= $description ?></textarea><br>

            <label for="gprice">Gadget Price:</label>
            <input type="number" name="gprice" id="gprice" value="<?= $price ?>" readonly><br>

            <label for="gstock">Gadget Stock:</label>
            <input type="number" name="gstock" id="gstock" value="<?= $stock ?>" readonly><br>

            <section>
                <button data-get="update_gadget.php?id=<?= $gadgetid ?>">Edit Now</button>
            </section>
        </div>
    </form>
</div>
<?php
include '../page/admin_products.php';?>