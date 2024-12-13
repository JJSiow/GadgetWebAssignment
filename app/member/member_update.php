<?php
require '../_base.php';

// Get member ID from URL parameter
// $id = req('member_id');
$id = "M00001";

// Fetch member data
$stm = $_db->prepare('SELECT * FROM member WHERE member_id = ?');
$stm->execute([$id]);
$member = $stm->fetch();

// If member not found, redirect back
if (!$member) {
    temp('info', 'Member not found.');
    redirect('member.php');
}

// Handle form submission
if (is_post()) {
    $selected_fields = post('fields_to_update', []);
    $updates = [];
    $params = [];

    if (in_array('member_name', $selected_fields)) {
        $name = post('member_name');
        if (empty($name)) {
            $_err['member_name'] = 'Name is required';
        } else {
            $updates[] = 'member_name = ?';
            $params[] = $name;
        }
    }

    if (in_array('member_phone_no', $selected_fields)) {
        $phone = post('member_phone_no');
        if (empty($phone)) {
            $_err['member_phone_no'] = 'Phone number is required';
        } else {
            $updates[] = 'member_phone_no = ?';
            $params[] = $phone;
        }
    }

    if (in_array('member_email', $selected_fields)) {
        $email = post('member_email');
        if (empty($email)) {
            $_err['member_email'] = 'Email is required';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_err['member_email'] = 'Invalid email format';
        } else {
            $updates[] = 'member_email = ?';
            $params[] = $email;
        }
    }

    if (in_array('shipping_address', $selected_fields)) {
        $address = post('shipping_address');
        $updates[] = 'shipping_address = ?';
        $params[] = $address;
    }

    if (in_array('member_gender', $selected_fields)) {
        $gender = post('member_gender');
        $updates[] = 'member_gender = ?';
        $params[] = $gender;
    }

    // Handle profile picture upload if provided
    if (in_array('member_profile_pic', $selected_fields) && $f = get_file('member_profile_pic')) {
        $photo = save_photo($f, '../photos');
        $updates[] = 'member_profile_pic = ?';
        $params[] = $photo;
    }

    // If no errors and there are updates, update the database
    if (empty($_err) && !empty($updates)) {
        $params[] = $id;
        $sql = 'UPDATE member SET ' . implode(', ', $updates) . ' WHERE member_id = ?';
        $stm = $_db->prepare($sql);
        $stm->execute($params);
        
        redirect('member.php');
    }
}

// Set form values
$member_name = post('member_name', $member->member_name);
$member_phone_no = post('member_phone_no', $member->member_phone_no);
$member_email = post('member_email', $member->member_email);
$shipping_address = post('shipping_address', $member->shipping_address);
$member_gender = post('member_gender', $member->member_gender);
$fields_to_update = post('fields_to_update', []);

// ----------------------------------------------------------------------------
$_title = 'Member | Edit Member Information';
include '../_head.php';
?>

<!DOCTYPE html>
<html>
<head>
    <title>Update Member</title>
    <link rel="stylesheet" href="../css/style.css">
    <!-- <script>
        function toggleFields() {
            const selectedFields = Array.from(document.querySelectorAll('select[name="fields_to_update[]"] option:checked')).map(el => el.value);
            document.querySelectorAll('.form-group').forEach(group => {
                const field = group.getAttribute('data-field');
                if (field && selectedFields.includes(field)) {
                    group.style.display = 'block';
                } else if (field) {
                    group.style.display = 'none';
                }
            });
        }
        document.addEventListener('DOMContentLoaded', toggleFields);
    </script> -->
</head>
<body>
    <form method="post" enctype="multipart/form-data">
        <div class="form-group">
            <label>Select Fields to Update:</label>
            <select name="fields_to_update[]" multiple onchange="toggleFields()">
                <option value="member_name" <?= in_array('member_name', $fields_to_update) ? 'selected' : '' ?>>Name</option>
                <option value="member_phone_no" <?= in_array('member_phone_no', $fields_to_update) ? 'selected' : '' ?>>Phone Number</option>
                <option value="member_email" <?= in_array('member_email', $fields_to_update) ? 'selected' : '' ?>>Email</option>
                <option value="shipping_address" <?= in_array('shipping_address', $fields_to_update) ? 'selected' : '' ?>>Shipping Address</option>
                <option value="member_gender" <?= in_array('member_gender', $fields_to_update) ? 'selected' : '' ?>>Gender</option>
                <option value="member_profile_pic" <?= in_array('member_profile_pic', $fields_to_update) ? 'selected' : '' ?>>Profile Picture</option>
            </select>
        </div>

        <div class="form-group" data-field="member_name">
            <label>Name:</label>
            <?php html_text('member_name', 'required') ?>
            <?php err('member_name') ?>
        </div>

        <div class="form-group" data-field="member_phone_no">
            <label>Phone Number:</label>
            <?php html_text('member_phone_no', 'required') ?>
            <?php err('member_phone_no') ?>
        </div>

        <div class="form-group" data-field="member_email">
            <label>Email:</label>
            <?php html_text('member_email', 'required') ?>
            <?php err('member_email') ?>
        </div>

        <div class="form-group" data-field="member_gender">
            <label>Gender:</label>
            <?php html_radios('member_gender', $_genders) ?>
            <?php err('member_gender') ?>
        </div>

        <div class="form-group" data-field="shipping_address">
            <label>Shipping Address:</label>
            <textarea name="shipping_address"><?= encode($shipping_address) ?></textarea>
            <?php err('shipping_address') ?>
        </div>

        <div class="form-group" data-field="member_profile_pic">
            <label>Profile Picture:</label>
            <?php if ($member->member_profile_pic): ?>
                <img src="../photos/<?= $member->member_profile_pic ?>" width="100">
            <?php endif ?>
            <?php html_file('member_profile_pic', 'image/*') ?>
            <?php err('member_profile_pic') ?>
        </div>

        <div class="form-buttons">
            <button type="submit">Update</button>
            <a href="member.php" class="button">Cancel</a>
        </div>
    </form>
</body>
</html>