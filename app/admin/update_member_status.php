<?php
require '../_base.php';

if (is_post()) {
    $member_id = req('member_id');

    // // Fetch the current status of the member
    // $stm = $_db->prepare('SELECT member_status FROM member WHERE member_id = ?');
    // $stm->execute([$member_id]);
    // $current_status = $stm->fetchColumn();

    $stm = $_db->prepare('SELECT member_status, login_attempt FROM member WHERE member_id = ?');
    $stm->execute([$member_id]);
    $result = $stm->fetch(PDO::FETCH_ASSOC);
    $current_status = $result['member_status'];
    $login_attempts = $result['login_attempt'];

    // Toggle the status
    $new_status = ($current_status == 'Active') ? 'Disabled' : 'Active';

    if ($current_status == 'Disabled' && $login_attempts >= 3) {
        $_db->prepare('UPDATE member SET login_attempt = 0 WHERE member_id = ?')->execute([$member_id]);
    }
    
    // Update the status in the database
    $_db->prepare('UPDATE member SET member_status = ? WHERE member_id = ?')->execute([$new_status, $member_id]);

    // Redirect back to the member list with the current page number
    temp('info', 'Status updated');
    
    if (isset($_SERVER['HTTP_REFERER'])) {
        redirect($_SERVER['HTTP_REFERER']);
    } else {
        redirect("member_list.php");
    }
}
?>