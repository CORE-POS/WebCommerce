<?php
if (basename(__FILE__) != basename($_SERVER['PHP_SELF'])) {
    return;
}
?>
<html>
<head></head>
<body>
<?php
if (!class_exists('PhpAutoLoader')) {
    require(dirname(__FILE__) . '/../vendor-code/PhpAutoLoader/PhpAutoLoader.php');
}
include('../ini.php');

$db = Database::tDataConnect();
/*
$oldCartsQ = "DELETE FROM localtemptrans WHERE datediff(curdate(),datetime) > 1";
$db->query($oldCartsQ);
*/

$clearQ = "DELETE FROM localtrans_today WHERE datediff(curdate(),datetime) <> 0";
$db->query($clearQ);

$pingQ = "INSERT INTO dtransactions (datetime, register_no, emp_no, trans_no, upc,
            description, trans_type, trans_subtype, trans_status, department,
            quantity, cost, unitPrice, total, regPrice, scale, tax, foodstamp,
            discount, memDiscount, discountable, discounttype, ItemQtty, volDiscType,
            volume, VolSpecial, mixMatch, matched, voided, memType, staff, numflag,
            charflag, card_no, trans_id) VALUES (" . $db->now() . ", 0, 0, 0, 'DAILYPING',
            'DAILYPING', 'L', 'OG', '', 0,
            0, 0, 0, 0, 0, 0, 0, 0,
            0, 0, 0, 0, 0, 0,
            0, 0, '', 0, 0, 0, 0, 0,
            '', 0, 1)";
$db->query($pingQ);

?>
</body>
</html>
