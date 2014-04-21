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

class Notices
{

    const STORE_EMAIL = 'orders@wholefoods.coop';
    const REPLY_EMAIL = 'andy@wholefoods.coop';
    const ADMIN_EMAIL = 'andy@wholefoods.coop';

public static function sendEmail($to,$subject,$msg)
{
	$headers = 'From: '.self::STORE_EMAIL."\r\n";
	$headers .= 'Reply-To: '.self::REPLY_EMAIL."\r\n";

	mail($to,$subject,$msg,$headers);
}

public static function customerConfirmation($uid,$email,$total)
{
	$msg = "Thank you for ordering from Whole Foods Co-op\n\n";
	$msg .= "Order Summary:\n";
	$cart = self::getcart($uid);
	$msg .= $cart."\n";
	$msg .= sprintf("Order Total: \$%.2f\n",$total);

	self::sendEmail($email,"WFC Order Confirmation",$msg);

	return $cart;
}

public static function adminNotification($uid,$email,$ph,$total,$cart="")
{
	$msg = "New online order\n\n";
	$msg .= AuthUtilities::getRealName($email)." (".$email.")\n";
	$msg .= "Phone # provided: ".$ph."\n\n";
	$msg .= sprintf("Order Total: \$%.2f\n",$total);

	$msg .= "\nOrder Summary:\n";
	$msg .= $cart;
	
	self::sendEmail(self::ADMIN_EMAIL,"New Online Order",$msg);
}

public static function mgrNotification($addresses,$email,$ph,$total,$notes="",$cart="")
{
	$msg = "New online order\n\n";
	$msg .= AuthUtilities::getRealName($email)." (".$email.")\n";
	$msg .= "Phone # provided: ".$ph."\n\n";
	$msg .= sprintf("Order Total: \$%.2f\n",$total);

	$msg .= "\nOrder Summary:\n";
	$msg .= $cart;

	$msg .= "\n:Additional attendees\n";
	$msg .= (!empty($notes) ? $notes : 'none listed');
	
	$addr = "";
	foreach($addresses as $a)
		$addr .= $a.",";
	$addr = rtrim($addr,",");
	self::sendEmail($addr,"New Online Order",$msg);
}

public static function getcart($empno)
{
	$db = Database::tDataConnect();
	$q = $db->prepare_statement("SELECT description,quantity,total FROM
		cart WHERE emp_no=?");
	$r = $db->exec_statement($q, array($empno));
	$ret = "";
	while($w = $db->fetch_row($r)){
		$ret .= $w['description']."\t\tx";
		$ret .= $w['quantity']."\t\$";
		$ret .= sprintf("%.2f",$w['total'])."\n";
	}

	$ret .= "\n";

	$taxP = $db->prepare_statement("SELECT taxes FROM taxTTL WHERE emp_no=?");
	$taxR = $db->exec_statement($taxP, array($empno));
    $taxW = $db->fetch_row($taxR);
    $taxes = round($taxW['taxes'], 2);
	$ret .= sprintf("Sales tax: \$%.2f\n",$taxes);

	return $ret;
}

}

?>
