<?php
require '../_base.php';
// ----------------------------------------------------------------------------
$_title = 'Gadget Store';
include '../_head.php';

if (!isset($_SESSION['member'])) {
    // Redirect to login page if not logged in
    header("Location: /login.php");
    exit();
}

if (is_get()) {
    $address_id = req('id');
    $stm = $_db->prepare('SELECT * FROM address WHERE address_id = ?');
    $stm->execute([$address_id]);
    $s = $stm->fetch();


    if (!$s) {
        redirect('member_profile.php');
    }
}

extract((array)$s);

if (is_post()) {
    $address_id = req('id');
    $address_detail       = req('address_detail');
    $address_latitude       = req('address_latitude');
    $address_longitude       = req('address_longitude');
    $address_label       = req('address_label');
    $address_building       = req('address_building');
    $address_floor_unit       = req('address_floor_unit');
    $address_instruction       = req('address_instruction');

    if ($address_detail == '') {
        $_err['address_detail'] = 'Required';
    } else if (strlen($address_detail) > 300) {
        $_err['address_detail'] = 'Maximum length 300';
    }

    if ($address_latitude == '') {
        $_err['address_latitude'] = 'Required';
    } else if (strlen($address_latitude) > 50) {
        $_err['address_latitude'] = 'Maximum length 50';
    }

    if ($address_longitude == '') {
        $_err['address_longitude'] = 'Required';
    } else if (strlen($address_longitude) > 50) {
        $_err['address_longitude'] = 'Maximum length 50';
    }


    if (strlen($address_label) > 50) {
        $_err['address_label'] = 'Maximum length 50';
    }

    if (strlen($address_building) > 50) {
        $_err['address_building'] = 'Maximum length 50';
    }

    if (strlen($address_floor_unit) > 30) {
        $_err['address_floor_unit'] = 'Maximum length 30';
    }

    if (strlen($address_instruction) > 200) {
        $_err['address_instruction'] = 'Maximum length 200';
    }

    if (!$_err) {

        $stm = $_db->prepare('
            UPDATE address SET address_detail = ?, address_label = ?,address_building = ?,address_floor_unit = ?,address_instruction = ?,address_latitude = ?,address_longitude = ? WHERE address_id = ?
            ');
        $stm->execute([$address_detail, $address_label, $address_building, $address_floor_unit, $address_instruction, $address_latitude, $address_longitude, $address_id]);

        temp('info', 'Your address had update successfully.');
        redirect('address_book.php');
        exit();
    }
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Address Create</title>
    <script type="text/javascript" src="https://maps.googleapis.com/maps/api/js?key=AIzaSyACghnaJhcfHdkqZuD1Pp9dOb6dh7KyMS8"></script>
</head>

<body>

    <div id="map" style="height: 500px; width: 100%;"></div>

    <div id="output">
        <p>Result will display here</p>
    </div>


    <form method="post" class="form">
        <label for="address">Address</label>
        <?= html_text('address_detail', 'placeholder="Enter Address"'); ?>
        <button id="search" type="button">Search Address</button>
        <?= err('address') ?>

        <label for="address_latitude">latitude</label>
        <?= html_text('address_latitude', 'placeholder="Latitude"'); ?>
        <?= err('address_latitude') ?>
        <label for="address_longitude">longitude</label>
        <?= html_text('address_longitude', 'placeholder="Longitude"'); ?>
        <?= err('address_longitude') ?>

        <label for="label">Label</label>
        <?= html_text('address_label', 'placeholder="Enter label"'); ?>
        <?= err('address_label') ?>

        <label for="building">building</label>
        <?= html_text('address_building', 'placeholder="(Optional) Building Name"'); ?>
        <?= err('address_building') ?>

        <label for="floor_unit">Floor/Unit</label>
        <?= html_text('address_floor_unit', 'placeholder="(Optional) floor_unit"'); ?>
        <?= err('address_floor_unit') ?>


        <label for="address_instruction">Delivery Instructions</label>
        <?= html_text('address_instruction', 'placeholder="(Optional) Please provide additional devliery instruction"'); ?>
        <?= err('address_instruction') ?>

        <section>
            <button>Submit</button>
            <button type="reset">Reset</button>
            <a href="address_book.php">return</a>
        </section>
    </form>



    <script type="text/javascript" src="/js/google_map.js">
        window.onload = initMap;
    </script>
</body>

</html>

<?php
include '../_foot.php';
?>