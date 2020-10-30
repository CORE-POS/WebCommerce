<?php

$IS4C_PATH = isset($IS4C_PATH)?$IS4C_PATH:"";
if (empty($IS4C_PATH)){ while(!file_exists($IS4C_PATH."is4c.css")) $IS4C_PATH .= "../"; }

if (!class_exists('PhpAutoLoader')) {
    require(dirname(__FILE__) . '/../vendor-code/PhpAutoLoader/PhpAutoLoader.php');
}

$dt = isset($_REQUEST['dt']) ? $_REQUEST['dt'] : '';
$dateID = date('Ymd', strtotime($dt));
$store = isset($_REQUEST['s']) ? $_REQUEST['s'] : '';
$dbc = Database::pDataConnect();
$res = $dbc->query(sprintf('SELECT time FROM PickupSlots
            WHERE dateID=%d AND storeID=%d AND slots > 0 ORDER BY seq',
            $dateID, $store));
while ($row = $dbc->fetchRow($res)) {
    printf('<option>%s</option>', $row['time']);
}
