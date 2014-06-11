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

$db = Database::tDataConnect();

$db->query("LOCK TABLES pendingtrans WRITE, dtransactions WRITE, 
	localtrans_today WRITE");

// get upcs & quantities from pending
$data = array();
$result = $db->query("SELECT upc,sum(quantity) as qty FROM pendingtrans
		WHERE trans_type='I'");
while($row = $db->fetch_row($result)){
	$data[$row['upc']] = $row['qty'];
}

// shuffle contents to final trans tables
$pendingCols = Database::localMatchingColumns($db, 'pendingtrans', 'dtransactions');
$db->query("INSERT INTO dtransactions ($pendingCols)
            SELECT $pendingCols FROM pendingtrans");
$pendingCols = Database::localMatchingColumns($db, 'pendingtrans', 'localtrans_today');
$db->query("INSERT INTO localtrans_today ($pendingCols)
            SELECT $pendingCols FROM pendingtrans");

// clear pending
$db->query("DELETE FROM pendingtrans");

$db->query("UNLOCK TABLES");

// update limits based on amounts sold
$db2 = Database::pDataConnect();
foreach($data as $upc=>$qty){
	$q = sprintf("UPDATE productOrderLimits 
		SET available=available-%d
		WHERE upc='%s'",$qty,$upc);
	$r = $db2->query($q);
}

$endQ = "UPDATE products AS p INNER JOIN
	productOrderLimits AS l ON p.upc=l.upc
	SET p.inUse=0
	WHERE l.available <= 0";
$db2->query($endQ);

?>
</body>
</html>
