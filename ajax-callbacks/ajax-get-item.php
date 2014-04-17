<?php
/*******************************************************************************

    Copyright 2011 Whole Foods Co-op

    This file is part of IS4C.

    IS4C is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    IS4C is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IS4C; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/
 // session_start();
 
$IS4C_PATH = isset($IS4C_PATH)?$IS4C_PATH:"";
if (empty($IS4C_PATH)){ while(!file_exists($IS4C_PATH."is4c.css")) $IS4C_PATH .= "../"; }

if (!class_exists('PhpAutoLoader')) {
    require(dirname(__FILE__) . '/../vendor-code/PhpAutoLoader/PhpAutoLoader.php');
}

$upc = str_pad($_REQUEST['upc'], 13, '0', STR_PAD_LEFT);
$dbc = Database::pDataConnect();
$q = $dbc->prepare_statement("SELECT p.upc,p.normal_price,p.special_price,
    p.discounttype,u.description,u.brand,u.long_text,
    p.inUse,u.soldOut,1 AS found,
    CASE WHEN o.available IS NULL then 99 ELSE o.available END as available
    FROM products AS p INNER JOIN productUser AS u
    ON p.upc=u.upc LEFT JOIN productOrderLimits AS o
    ON p.upc=o.upc WHERE p.upc=?");
$r = $dbc->exec_statement($q,array($upc));

$item = array(
    'upc' => '',
    'description' => '',
    'brand' => '',
    'long_text' => '',
    'special_price' => 0,
    'normal_price' => 0,
    'discounttype' => 0,
    'inUse' => 1,
    'soldOut' => 0,
    'inCart' => 0,
    'found' => 0,
);

if ($dbc->num_rows($r) == 0) {
    $item['found'] = 0;
} else {
    $row = $dbc->fetch_row($r);
    foreach(array_keys($item) as $key) {
        if (isset($row[$key])) {
            $item[$key] = $row[$key];
        }
    }
    if ($row['available'] <= 0) {
        $item['soldOut'] = 1;
    }
}

if ($item['found']) {
    $empno = AuthUtilities::getUID(AuthLogin::checkLogin());
    if ($empno===false) $empno=-999;

    $chkP = $dbc->prepare_statement("SELECT upc FROM localtemptrans WHERE
        upc=? AND emp_no=?");
    $chkR = $dbc->exec_statement($chkP,array($upc,$empno));
    if ($dbc->num_rows($chkR) > 0) {
        $item['inCart'] = 1;
    }
}

echo json_encode($item);

