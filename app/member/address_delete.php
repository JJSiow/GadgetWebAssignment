<?php
require_once '../_base.php';

if (is_post()) {
    $address_id = req('id');

    $add_delete = $_db->prepare('DELETE FROM address WHERE address_id = ?');
    $add_delete->execute([$address_id]);
    
} else {
    temp('error', 'Invalid action.');
}

redirect('address_book.php');
