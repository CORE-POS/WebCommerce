<?php
$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, true);
if (!class_exists('PhpAutoLoader')) {
    require(dirname(__FILE__) . '/../vendor-code/PhpAutoLoader/PhpAutoLoader.php');
}

$example = array(
    'upc' => '0000000004011',
    'check-digits' => false,
);
$input = array('upc' => '4011');

header('Content-type: application/json');
if (!isset($input['upc'])) {
    echo json_encode($example);
} else {
    $out = array();
    $dbc = Database::pDataConnect();
    $query = '
        SELECT p.upc,
            NULL AS brand1,
            u.brand AS brand2,
            p.description AS desc1,
            u.description AS desc2,
            p.size AS size1,
            u.sizing AS size2
        FROM products AS p
            LEFT JOIN productUser AS u ON p.upc=u.upc
        WHERE p.upc=?';
    $prep = $dbc->prepare($query);
    $res = $dbc->execute($prep, array($input['upc']));
    echo $dbc->error();

    while ($w = $dbc->fetchRow($res)) {
        $out['upc'] = $w['upc'];
        $out['brand'] = $w['brand2'] ? $w['brand2'] : $w['brand1'];
        $out['description'] = $w['desc2'] ? $w['desc2'] : $w['desc1'];
        $out['size'] = $w['size2'] ? $w['size2'] : $w['size1'];
    }

    echo json_encode($out);
}
