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

$db->query("LOCK TABLES pendingtrans WRITE, dtransactions WRITE, 
	localtrans_today WRITE, productOrderLimits WRITE");

// get upcs & quantities from pending
$transR = $db->query("SELECT emp_no, register_no, trans_no FROM pendingtrans GROUP BY emp_no, register_no, trans_no");
while ($transW = $db->fetchRow($transR)) {
    $data = array();
    $result = $db->query(sprintf("SELECT upc,sum(quantity) as qty FROM pendingtrans
            WHERE trans_type='I' AND emp_no=%d AND register_no=%d AND trans_no=%d
            GROUP BY upc",
            $transW['emp_no'], $transW['register_no'], $transW['trans_no']));
    while($row = $db->fetch_row($result)){
        $data[$row['upc']] = $row['qty'];
    }
    $storeR = $db->query(sprintf("SELECT description FROM pendingtrans
        WHERE emp_no=%d AND register_no=%d AND trans_no=%d AND trans_type='C'
            AND trans_subtype='CM' AND description LIKE 'STORE%%'",
            $transW['emp_no'], $transW['register_no'], $transW['trans_no']));
    $storeID = null;
    while ($storeW =  $db->fetchRow($storeR)) {
        list($nothing, $storeID) = explode(' ', $storeW['description']);
    }
    foreach($data as $upc=>$qty){
        $q = sprintf("UPDATE productOrderLimits 
            SET available=available-%d
            WHERE upc='%s' AND storeID=%d",$qty,$upc,$storeID);
        $r = $db->query($q);
    }
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

$endQ = "UPDATE products AS p INNER JOIN
	productOrderLimits AS l ON p.upc=l.upc
	SET p.inUse=0
	WHERE l.available <= 0";
$db2->query($endQ);

?>
</body>
</html>
