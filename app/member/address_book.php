<?php
require '../_base.php';
// ----------------------------------------------------------------------------

// TODO
$member_id = $_member->member_id;

$query = $_db->prepare('SELECT * FROM address where member_id = ?');
$query->execute([$member_id]);
$arr  = $query->fetchAll();


// ----------------------------------------------------------------------------
$_title = 'Member | List of Address';
include '../_head.php';
?>

<p><?= count($arr) ?> record(s)</p>

<a href="gmap.php">add address</a>

<table class="table">
    <tr>
        <th>Address</th>
        <th>Label</th>
        <th>Building Name</th>
        <th>Floor/Unit</th>
        <th>Instruction</th>
        <th></th>
    </tr>

    <?php foreach ($arr as $s): ?>
    <tr>
        <td><?= $s->address_detail ?></td>
        <td><?= $s->address_label ?></td>
        <td><?= $s->address_building ?></td>
        <td><?= $s->address_floor_unit ?></td>
        <td><?= $s->address_instruction ?></td>
        <td>
            <!-- TODO -->
            <button  data-get="/member/address_update.php?id=<?= $s->address_id ?>">Update</button>
            <button data-post="address_delete.php?id=<?= $s->address_id ?>" data-confirm='Are you sure you want to delete this address?'>Delete</button>
        </td>
    </tr>
    <?php endforeach ?>
</table>


<?php
include '../_foot.php';
?>
