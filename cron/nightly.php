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

?>
</body>
</html>
