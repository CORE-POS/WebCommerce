<?php
/*******************************************************************************

    Copyright 2001, 2004 Wedge Community Co-op

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
/*------------------------------------------------------------------------------
additem.php is called by the following files:

as include:
	login3.php
	authenticate3.php
	prehkeys.php
	upcscanned.php
	authenticate.php

additem.php is the bread and butter of IS4C. addItem inserts the information
stream for each item scanned, entered or transaction occurence into localtemptrans.
Each of the above follows the following structure for entry into localtemptrans:
	$strupc, 
	$strdescription, 
	$strtransType, 
	$strtranssubType, 
	$strtransstatus, 
	$intdepartment, 
	$dblquantity, 
	$dblunitPrice, 
	$dbltotal, 
	$dblregPrice, 
	$intscale, 
	$inttax, 
	$intfoodstamp, 
	$dbldiscount, 
	$dblmemDiscount, 
	$intdiscountable, 
	$intdiscounttype, 
	$dblItemQtty, 
	$intvolDiscType, 
	$intvolume, 
	$dblVolSpecial, 
	$intmixMatch, 
	$intmatched, 
	$intvoided

Additionally, additem.php inserts entries into the activity log when a cashier 
signs in
-------------------------------------------------------------------------------*/
$IS4C_PATH = isset($IS4C_PATH)?$IS4C_PATH:"";
if (empty($IS4C_PATH)){ while(!file_exists($IS4C_PATH."is4c.css")) $IS4C_PATH .= "../"; }

class TransRecord
{

//-------insert line into localtemptrans with standard insert string--------------
public static function addItem($strupc, $strdescription, $strtransType, $strtranssubType, $strtransstatus, $intdepartment, $dblquantity, $dblunitPrice, $dbltotal, $dblregPrice, $intscale, $inttax, $intfoodstamp, $dbldiscount, $dblmemDiscount, $intdiscountable, $intdiscounttype, $dblItemQtty, $intvolDiscType, $intvolume, $dblVolSpecial, $intmixMatch, $intmatched, $intvoided, $cost=0, $numflag=0, $charflag='') {
	global $IS4C_LOCAL;

	$dbltotal = str_replace(",", "", $dbltotal);		
	$dbltotal = number_format($dbltotal, 2, '.', '');
	$dblunitPrice = str_replace(",", "", $dblunitPrice);
	$dblunitPrice = number_format($dblunitPrice, 2, '.', '');

	$intregisterno = $IS4C_LOCAL->get("laneno");

    if (isset($_SESSION['emp_no'])) {
        $intempno = $_SESSION['emp_no'];
        $owner = 0;
    } else {
        $name = AuthLogin::checkLogin();
        if (!$name) return False;
        $intempno = AuthUtilities::getUID($name);
        if (!$intempno) return False;
        $owner = AuthUtilities::getOwner($name);
    }
	$memType = 0;
	$staff = 0;
	if ($owner !== False && $owner != 0){ 
		$memType=1;
	} else {
		$owner = $IS4C_LOCAL->get("defaultNonMem");
		if ($strtransType == 'I'){
			// this is handled fine in addUPC
			//$dblunitPrice += $memDiscount;
			//$dbltotal += ($quantity*$memDiscount);
		}
	}
	$strCardNo = $owner;

	$inttransno = Database::gettransno($intempno);

	$db = Database::tDataConnect();

	$datetimestamp = "";
	if ($IS4C_LOCAL->get("DBMS") == "mssql") {
		$datetimestamp = strftime("%m/%d/%y %H:%M:%S %p", time());
	} else {
		$datetimestamp = strftime("%Y-%m-%d %H:%M:%S", time());
	}

	$values = array(
		'datetime'	=> $datetimestamp,
		'register_no'	=> $intregisterno,
		'emp_no'	=> $intempno,
		'trans_no'	=> self::nullwrap($inttransno),
		'upc'		=> self::nullwrap($strupc),
		'description'	=> $strdescription,
		'trans_type'	=> self::nullwrap($strtransType),
		'trans_subtype'	=> self::nullwrap($strtranssubType),
		'trans_status'	=> self::nullwrap($strtransstatus),
		'department'	=> self::nullwrap($intdepartment),
		'quantity'	=> self::nullwrap($dblquantity),
		'cost'		=> self::nullwrap($cost),
		'unitPrice'	=> self::nullwrap($dblunitPrice),
		'total'		=> self::nullwrap($dbltotal),
		'regPrice'	=> self::nullwrap($dblregPrice),
		'scale'		=> self::nullwrap($intscale),
		'tax'		=> self::nullwrap($inttax),
		'foodstamp'	=> self::nullwrap($intfoodstamp),
		'discount'	=> self::nullwrap($dbldiscount),
		'memDiscount'	=> self::nullwrap($dblmemDiscount),
		'discountable'	=> self::nullwrap($intdiscountable),
		'discounttype'	=> self::nullwrap($intdiscounttype),
		'ItemQtty'	=> self::nullwrap($dblItemQtty),
		'volDiscType'	=> self::nullwrap($intvolDiscType),
		'volume'	=> self::nullwrap($intvolume),
		'VolSpecial'	=> self::nullwrap($dblVolSpecial),
		'mixMatch'	=> self::nullwrap($intmixMatch),
		'matched'	=> self::nullwrap($intmatched),
		'voided'	=> self::nullwrap($intvoided),
		'memType'	=> self::nullwrap($memType),
		'staff'		=> self::nullwrap($staff),
		'numflag'	=> self::nullwrap($numflag),
		'charflag'	=> $charflag,
		'card_no'	=> (string)$strCardNo
		);
	if ($IS4C_LOCAL->get("DBMS") == "mssql" && $IS4C_LOCAL->get("store") == "wfc"){
		unset($values["staff"]);
		$values["isStaff"] = self::nullwrap($staff);
	}

	// translate column/value array to build prepared statement
	$cols = '(';
	$vals = '(';
	$args = array();
	foreach($values as $col => $val){
		$cols .= $col.',';
		$vals .= '?,';
		$args[] = $val;
	}
	$cols = substr($cols,0,strlen($cols)-1).')';
	$vals = substr($vals,0,strlen($vals)-1).')';
	$query = "INSERT INTO localtemptrans $cols VALUES $vals";
	$prep = $db->prepare_statement($query);
	$db->exec_statement($prep, $args);

	$IS4C_LOCAL->set("toggletax",0);
	$IS4C_LOCAL->set("togglefoodstamp",0);

	return True;
}

public static function addDTrans($emp_no, $reg_no, $trans_no, $trans_id, $cols)
{
    $dbc = Database::pDataConnect();
    $vals = array(
        'datetime' => date('Y-m-d H:i:s'),
        'register_no' => $reg_no,
        'emp_no' => $emp_no,
        'trans_no' => $trans_no,
        'upc' => '',
        'description' => '',
        'trans_type' => 'I',
        'trans_subtype' => '',
        'trans_status' => '',
        'department' => 0,
        'quantity' => 0,
        'scale' => 0,
        'cost' => 0,
        'unitPrice' => 0,
        'total' => 0,
        'regPrice' => 0,
        'tax' => 0,
        'foodstamp' => 0,
        'discount' => 0,
        'memDiscount' => 0,
        'discountable' => 0,
        'discounttype' => 0,
        'voided' => 0,
        'percentDiscount' => 0,
        'ItemQtty' => 0,
        'volDiscType' => 0,
        'volume' => 0,
        'VolSpecial' => 0,
        'mixMatch' => '',
        'matched' => 0,
        'memType' => 0,
        'staff' => 0,
        'numflag' => 0,
        'charflag' => '',
        'card_no' => 0,
        'trans_id' => $trans_id,
        'pos_row_id' => 0,
    );
    $maxR = $dbc->query('SELECT MAX(pos_row_id) FROM dtransactions');
    while ($max = $dbc->fetchRow($maxR)) {
        $vals['pos_row_id'] = $max[0] + 1;
    }
    foreach (array_keys($vals) as $k) {
        if (isset($cols[$k])) {
            $vals[$k] = $cols[$k];
        }
    }
    $insQ = 'INSERT INTO dtransactions (';
    foreach (array_keys($vals) as $col) {
        $insQ .= $col . ',';
    }
    $insQ = substr($insQ, 0, strlen($insQ)-1) . ')';
    $insQ .= ' VALUES (';
    foreach ($vals as $v) {
        $insQ .= '?,';
    }
    $insQ = substr($insQ, 0, strlen($insQ)-1) . ')';
    $insP = $dbc->prepare($insQ);
    $insR = $dbc->execute($insP, array_values($vals));
    return $insR ? true : false;
}

//________________________________end addItem()


// add item by upc
// essentially an extremely pared-down version of upcscanned
public static function addUPC($upc,$quantity=1.0)
{
	global $IS4C_LOCAL;

	$db = Database::pDataConnect();
	$lookP = $db->prepare_statement("SELECT description, department, normal_price, special_price, pricemethod, specialpricemethod,
		tax, foodstamp, scale, discount, discounttype, cost, local FROM products
		WHERE upc=?");
	$result = $db->exec_statement($lookP,array($upc));
	if ($db->num_rows($result) == 0) return False;

	$row = $db->fetch_row($result);
	
	// keep to simple sales
	if ($row['discounttype'] == 0 && $row['pricemethod'] != 0)
		return False;
	elseif($row['discounttype'] != 0 && $row['specialpricemethod'] != 0)
		return False;

	$regPrice = $row['normal_price'];
	$unitPrice = $row['normal_price'];
	$discount = 0;
	$memDiscount = 0;
	switch($row['discounttype']){
	case 1:
		$discount = $row['normal_price'] - $row['special_price'];
		$unitPrice -= $discount;
		break;
	case 2:
		if (AuthUtilities::getOwner(AuthLogin::checkLogin()))
			$memDiscount = $row['normal_price'] - $row['special_price'];
		$unitPrice -= $memDiscount;
		break;
	}

	return self::addItem($upc, $row['description'], 'I', '', '', $row['department'], $quantity, 
			$unitPrice, $unitPrice*$quantity, $regPrice, $row['scale'], $row['tax'], 
			$row['foodstamp'], $discount, $memDiscount, $row['discount'], 
			$row['discounttype'], $quantity, 0, 0, 0.00, 0, 0, 0, $row['cost'],
			$row['local'], ''); 
}

public static function addOpenRing($amount, $dept_no, $dept_name, $tax=0, $fs=0)
{
	return self::addItem($amount . 'DP' . $dept_no, $dept_name, 'D', '', '', $dept_no, 1.0, 
			$amount, $amount, $amount, 0, $tax, 
			$fs, 0, 0, 0,
			0, 1.0, 0, 0, 0.00, 0, 0, 0, 0,
			0, ''); 
}

//---------------------------------- insert tax line item --------------------------------------

public static function addtax($amt) 
{
	self::addItem("TAX", "Tax", "A", "", "", 0, 0, 0, $amt, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0);
}

//________________________________end addtax()


//---------------------------------- insert tender line item -----------------------------------

public static function addtender($strtenderdesc, $strtendercode, $dbltendered) 
{
	self::addItem("", $strtenderdesc, "T", $strtendercode, "", 0, 0, 0, $dbltendered, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0);
}

// -----------nullwrap($num /numeric)----------
//
// Given $num, if it is empty or of length less than one, nullwrap becomes "0".
// If the argument is a non-numeric, generate a fatal error.
// Else nullwrap becomes the number.
public static function nullwrap($num) 
{
	if ( !$num ) {
		 return 0;
	} else if (!is_numeric($num) && strlen($num) < 1) {
		return " ";
	} else {
		return $num;
	}
}

//_______________________________end addtender()
}

?>
