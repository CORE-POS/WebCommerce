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
 
if (!class_exists('PhpAutoLoader')) {
    require(dirname(__FILE__) . '/../vendor-code/PhpAutoLoader/PhpAutoLoader.php');
}

$username = AuthLogin::checkLogin();
$empno = AuthUtilities::getUID($username);

$db = Database::tDataConnect();
$query = 'SELECT upc,
            brand,
            description,
            scale,
            quantity,
            unitPrice,
            total,
            saleMsg
          FROM cart
          WHERE emp_no=?';
$prep = $db->prepare_statement($query);
$result = $db->exec_statement($prep, array($empno));

$cart = array();
$subtotal = 0.0;
while($row = $db->fetch_row($result)) {
    $entry = array(
        'upc' => $row['upc'],
        'brand' => $row['brand'],
        'description' => $row['description'],
        'scale' => $row['scale'],
        'quantity' => $row['quantity'],
        'unitPrice' => $row['unitPrice'],
        'total' => $row['total'],
        'saleMsg' => $row['saleMsg'],
        'checked' => false,
    );
    $cart[] = $entry;
    $subtotal += $row['total'];
}

$taxP = $db->prepare_statement("SELECT taxes FROM taxTTL WHERE emp_no=?");
$taxR = $db->exec_statement($taxP,array($empno));
$taxTTL = 0.0;
if ($db->num_rows($taxR) > 0) {
    $taxW = $db->fetch_row($taxR);
    $taxTTL = $taxW['taxes'];
}
$total = $subtotal + $taxTTL;

$out = array(
    'cart' => $cart,
    'subtotal' => $subtotal,
    'tax' => $taxTTL,
    'total' => $total,
);

echo json_encode($out);
