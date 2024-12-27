<?php
require '../_base.php';

// Fetch member's addresses
$member_id = $_member->member_id;

$query = $_db->prepare('SELECT * FROM address where member_id = ?');
$query->execute([$member_id]);
$addresses = $query->fetchAll();

// ----------------------------------------------------------------------------
$_title = 'Member | List of Address';
include '../_head.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="/css/address.css">
  <script type="text/javascript" src="https://maps.googleapis.com/maps/api/js?key=AIzaSyACghnaJhcfHdkqZuD1Pp9dOb6dh7KyMS8"></script>
  <script src="/js/display_address.js" defer></script>
</head>

<body>

  <div class="container">
    <h1>Address Book</h1>

    <a href="gmap.php"><div class="btn-primary">Add Address</div></a>

    <div class="address-list">
      <?php foreach ($addresses as $index => $address): ?>
        <div class="address-card">
          <div class="address-details">
            <h2><?= htmlspecialchars($address->address_label) ?></h2>
            <p class="address-detail"><?= htmlspecialchars($address->address_detail) ?></p>
            <div id="google-map-<?= $index ?>" class="google-map" style="height: 200px; width: 100%;"></div>
            <div class="address-info-container">
              <p class="address-info"><b>Building Name :</b> <?= htmlspecialchars($address->address_building) ?></p>
              <p class="address-info"><b>Floor/Unit &nbsp; &nbsp; &nbsp; &nbsp; :</b> <?= htmlspecialchars($address->address_floor_unit) ?></p>
              <p class="address-info"><b>Instruction &nbsp; &nbsp; &nbsp; :</b> <?= htmlspecialchars($address->address_instruction) ?></p>
            </div>
          </div>    
          <div class="address-actions">
            <button class="btn btn-secondary" data-get="/member/address_update.php?id=<?= $address->address_id ?>">Edit</button>
            <button class="btn btn-danger" data-post="address_delete.php?id=<?= $address->address_id ?>" data-confirm="Are you sure you want to delete this address?">Delete</button>
          </div>
        </div>
        <script>
          document.addEventListener('DOMContentLoaded', function() {
              initializeMap('google-map-<?= $index ?>', '<?= addslashes($address->address_detail) ?>');
          });
        </script>
      <?php endforeach ?>
    </div>

  </div>

<?php
include '../_foot.php';
?>

</body>
</html>
