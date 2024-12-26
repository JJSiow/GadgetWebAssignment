<?php
require_once '../_base.php';

//-----------------------------------------------------------------------------
if (is_post()) {
    $ctg_name = req('ctg_name');

    $existingCategory = $_db->prepare('SELECT COUNT(*) FROM category WHERE category_name = ?');
    $existingCategory->execute([$ctg_name]);
    $count = $existingCategory->fetchColumn();

    if (empty($ctg_name)) {
        $_err['ctg_name'] = 'Category Name is required';
    } elseif (strlen($ctg_name) > 15) {
        $_err['ctg_name'] = 'Maximum length for category name is 15 characters';
    } elseif ($count > 0) {
        $_err['ctg_name'] = 'Category with this name already exists';
    }

    if ($_err) {
        $_SESSION['category_error'] = $_err['ctg_name'];
        redirect('/page/admin_category.php');
    }else {
        $newCategoryId = auto_id('category_id', 'category', 'CA_');
        $ctg_name = strtoupper($ctg_name);
        $stm = $_db->prepare('INSERT INTO category
        (category_id, category_name, category_status)
        VALUES(?, ?, ?)');
        $stm->execute([$newCategoryId, $ctg_name, 'Active']);

        temp('info', 'Category added successfully');
        redirect('/page/admin_category.php');
    }
}
// ----------------------------------------------------------------------------
