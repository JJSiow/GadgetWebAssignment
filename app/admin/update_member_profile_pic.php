<?php

require '../_base.php';

$member_id = req('member_id');

if (is_post()) {
    $photo = get_file('member_profile_pic');
    $member_profile_pic = $_SESSION['member_profile_pic'];

    if (count($_err) == 0) {
        if ($photo) {
            unlink("../photos/$member_profile_pic");
            $member_profile_pic = save_photo($photo, '../photos');
        }

        $stm = $_db->prepare('UPDATE member SET member_profile_pic = ? WHERE member_id = ?');
        $stm->execute([$member_profile_pic, $member_id]);

        temp('info', 'Member profile picture updated.');
        redirect('member_list.php');
    }
    else {
        temp('info', 'Please check the error(s).');
    }

}

?>