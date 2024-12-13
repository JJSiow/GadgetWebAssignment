<?php
require '../_base.php';

if (is_post()) {
    $member_id = req('member_id');
    $page = req('page', 1); // Default to page 1 if not provided

    // Fetch the current status of the member
    $stm = $_db->prepare('SELECT member_status FROM member WHERE member_id = ?');
    $stm->execute([$member_id]);
    $current_status = $stm->fetchColumn();

    // Toggle the status
    $new_status = ($current_status == 'Active') ? 'Disabled' : 'Active';

    // Update the status in the database
    $_db->prepare('UPDATE member SET member_status = ? WHERE member_id = ?')->execute([$new_status, $member_id]);

    // Redirect back to the member list with the current page number
    temp('info', 'Status updated');
    redirect("member.php?page=$page");
}
?>