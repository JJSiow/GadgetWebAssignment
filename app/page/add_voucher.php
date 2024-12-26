<?php
require_once '../_base.php';

//-----------------------------------------------------------------------------
if (is_post()) {
    $voc_amount = req('voc_amount');

    $existingBrand = $_db->prepare('SELECT COUNT(*) FROM voucher WHERE voucher_amount = ?');
    $existingBrand->execute([$voc_amount]);
    $count = $existingBrand->fetchColumn();

    if ($voc_amount == '') {
        $_err['voc_amount'] = 'Voucher Amount is required';
    } elseif (!is_money($voc_amount)) {
        $_err['voc_amount'] = 'Voucher Amount must in money format (Exp: RM XX.XX)';
    } elseif ($count > 0) {
        $_err['voc_amount'] = "Voucher Amount (RM $voc_amount) already existed";
    }

    if ($_err) {
        $_SESSION['voucher_error'] = $_err['voc_amount'];
        redirect('/page/admin_voucher.php');
    }else {
        $newVoucherId = auto_id('voucher_id', 'voucher', 'VOC_');
        $stm = $_db->prepare('INSERT INTO voucher
        (voucher_id, voucher_amount, voucher_status)
        VALUES(?, ?, ?)');
        $stm->execute([$newVoucherId, $voc_amount, 'Active']);

        temp('info', 'Voucher added successfully');
        redirect('/page/admin_voucher.php');
    }
}
// ----------------------------------------------------------------------------
