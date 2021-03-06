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

if (!isset($_REQUEST['upc'])){
	echo "0";
} else {
    $qty = 1;
    if (isset($_REQUEST['qty'])) {
        $qty = $_REQUEST['qty'];
    }
	$success = TransRecord::addUPC($_REQUEST['upc'], $qty);
	if ($success)
		echo "1";
	else
		echo "0";
}

?>
