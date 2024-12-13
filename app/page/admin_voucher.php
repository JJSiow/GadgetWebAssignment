<?php
require '../_base.php';
// ----------------------------------------------------------------------------


// ----------------------------------------------------------------------------
$_title = 'Page | Demo 2 | Ordered List';
include '../_head.php';
?>

<p><?= count($states) ?> state(s)</p>

<ol>
    <?php
    foreach ($states as $k => $v) {
        echo "<li>$k - $v</li>";
    }
    ?>
</ol>

<?php
include '../_foot.php';