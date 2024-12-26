<?php
require_once '../_base.php';

//-----------------------------------------------------------------------------
if (is_post()) {
    $brd_name = req('brd_name');

    $existingCategory = $_db->prepare('SELECT COUNT(*) FROM brand WHERE brand_name = ?');
    $existingCategory->execute([$brd_name]);
    $count = $existingCategory->fetchColumn();

    if (empty($brd_name)) {
        $_err['brd_name'] = 'Brand Name is required';
    } elseif (strlen($brd_name) > 15) {
        $_err['brd_name'] = 'Maximum length for brand name is 15 characters';
    } elseif ($count > 0) {
        $_err['brd_name'] = 'Brand with this name already exists';
    }

    if ($_err) {
        $_SESSION['brand_error'] = $_err['brd_name'];
        redirect('/page/admin_brand.php');
    } else {
        $newBrandId = auto_id('brand_id', 'brand', 'BD_');
        $brd_name = strtoupper($brd_name);
        $stm = $_db->prepare('INSERT INTO brand
        (brand_id, brand_name, brand_status)
        VALUES(?, ?, ?)');
        $stm->execute([$newBrandId, $brd_name, 'Active']);

        temp('info', 'Brand added successfully');
        redirect('/page/admin_brand.php');
    }
}
// ----------------------------------------------------------------------------
