<?php
if (basename(__FILE__) != basename($_SERVER['PHP_SELF'])) {
    return;
}
?>
<html>
<head></head>
<body>
<?php
include('../ini.php');
if (!class_exists('PhpAutoLoader')) {
    require(dirname(__FILE__) . '/../vendor-code/PhpAutoLoader/PhpAutoLoader.php');
}

$query = "UPDATE products AS p INNER JOIN productExpires AS e
	ON p.upc=e.upc 
	SET p.inUse=0
	WHERE datediff(now(),e.expires) >= 0";
$db = Database::pDataConnect();
$r = $db->query($query);

?>
</body></html>
