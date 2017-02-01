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
    const REPLY_EMAIL = 'it@wholefoods.coop';
    const ADMIN_EMAIL = 'it@wholefoods.coop';
    const PLUGIN_EMAIL = 'pik@wholefoods.coop';
    const JOIN_EMAIL = 'andy@wholefoods.coop,csc@wholefoods.coop,finance@wholefoods.coop,dcsc@wholefoods.coop';

public static function sendEmail($to,$subject,$msg)
{
	$headers = 'From: '.self::STORE_EMAIL."\r\n";
	$headers .= 'Reply-To: '.self::REPLY_EMAIL."\r\n";

    if (class_exists('PHPMailer')) {
        $mail = new PHPMailer();
        $mail->isMail();
        $mail->From = self::STORE_EMAIL;
        $mail->FromName = 'Whole Foods Co-op';
        $mail->addReplyTo(self::REPLY_EMAIL);
        if (strstr($to, ',')) {
            foreach (explode(',', $to) as $address) {
                $mail->addAddress(trim($address));
            }
        } else {
            $mail->addAddress($to);
        }
        $mail->Subject = $subject;
        $html = file_get_contents(dirname(__FILE__) . '/../src/html-mail/header.html')
            . str_replace("\n", '<br>', $msg)
            . file_get_contents(dirname(__FILE__) . '/../src/html-mail/footer.html');
        $mail->isHTML(true);
        $mail->Body = $html;
        $mail->AltBody = $msg;
        $mail->send();
    } else {
        mail($to,$subject,$msg,$headers);
    }
}

public static function customerConfirmation($uid,$email,$total,$notes)
{
	$msg = "Thank you for ordering from Whole Foods Co-op\n\n";
	$msg .= "Order Summary:\n";
	$cart = self::getcart($uid);
	$msg .= $cart."\n";
	$msg .= sprintf("Order Total: \$%.2f\n",$total);
    $msg .= $notes . "\n";

    $class_info = "\nCLASS INSTRUCTIONS:\n"
        . wordwrap("Please be courteous of the instructor and your classmates and be on time. Anyone arriving more than 10 minutes late will not be admitted into the class. The classroom is open at least 20 minutes prior to each class time to allow you to get settled in.")
        . "\n"
        . wordwrap("Please park in the 4th Street (upper) lot. People parking in the staff (lower) lot will be asked to move to the upper lot. We will no longer have anyone monitoring the back entrance.")
        . "\n"
        . wordwrap("Students should check in at Customer Service. We will escort you in groups to the classroom.")
        . "\n"
        . wordwrap("Please be on time! Late arrivals are disruptive to the instructor, and students. The classroom is open at least 30 minutes before each class, anyone arriving more than 10 minutes late will not be allowed into the class. A refund will not be given.")
        . "\n\n"
        . "THE FINE PRINT READ ME PLEASE!\n"
        . wordwrap("Classes and lectures must have a minimum of 6 students signed up for the class to take place. If a student cancels prior to 48 hours before the class, the refund will be applied to a future class or refunded in full in the tender with which it was paid. No refunds will be given for cancellations received after the 48-hour deadline or for no-shows. If WFC cancels the class, a full refund will be given or the refund may be applied to a future class, whichever the student prefers.");

    //$msg .= $class_info;

	self::sendEmail($email,"WFC Order Confirmation",$msg);

	return $cart;
}

public static function adminNotification($uid,$email,$ph,$owner,$total,$cart="",$triage=false)
{
	$msg = "New online order\n\n";
	$msg .= AuthUtilities::getRealName($email)." (".$email.")\n";
	$msg .= "Phone # provided: ".$ph."\n\n";
	$msg .= sprintf("Order Total: \$%.2f\n",$total);

	$msg .= "\nOrder Summary:\n";
	$msg .= $cart;

    $subject = 'New Online Order';
    if ($triage) {
        $subject = 'NO ONE GOT THIS ONLINE ORDER';
    }
	
	self::sendEmail(self::ADMIN_EMAIL,$subject,$msg);
}

public static function mgrNotification($addresses,$email,$ph,$owner,$total,$notes="",$cart="")
{
	$msg = "New online order\n\n";
	$msg .= AuthUtilities::getRealName($email)." (".$email.")\n";
	$msg .= "Phone # provided: ".$ph."\n\n";
    if ($owner) {
        $msg .= 'Owner #: ' . $owner . "\n\n";
    } else {
        $msg .= 'Non-owner' . "\n\n";
    }
	$msg .= sprintf("Order Total: \$%.2f\n",$total);

	$msg .= "\nOrder Summary:\n";
	$msg .= $cart;

	$msg .= (!empty($notes) ? $notes : '');
	
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

public static function joinNotification($json)
{
	$msg = "Thank you for joining Whole Foods Co-op\n\n";
    $msg .= 'Your owner number is ' . $json['card_no'] . "\n\n";
    if ($json['store'] == 1) {
        $msg .= 'Your owner ID cards and other materials will be available for pickup on '
            . date('F j, Y', strtotime('+1 day')) . ' at the ';
        $msg .= 'Hillside store:' . "\n";
        $msg .= '610 E 4th St.' . "\n";
        $msg .= 'Duluth, MN 55805' . "\n";
        $msg .= '218-728-0884' . "\n";
    } elseif ($json['store'] == 2) {
        $msg .= 'Your owner ID cards and other materials will be available for pickup on '
            . date('F j, Y', strtotime('+1 day')) . ' at the ';
        $msg .= 'Denfeld store:' . "\n";
        $msg .= '4426 Grand Ave.' . "\n";
        $msg .= 'Duluth, MN 55807' . "\n";
        $msg .= '218-728-0884' . "\n";
    }

	self::sendEmail($json['email'], "Joined Whole Foods Co-op", $msg);
}

public static function joinAdminNotification($json)
{
    $msg = 'New member joined via the website' . "\n\n";
    $msg .= 'Account #: ' . $json['card_no'] . "\n";
    $msg .= 'Name: ' . $json['fn'] . ' ' . $json['ln'] . "\n";
    $msg .= 'Address: ' . $json['addr1'] . "\n";
    if (!empty($json['addr2'])) {
        $msg .= $json['addr2'] . "\n";
    }
    $msg .= 'City: ' . $json['city'] . "\n";
    $msg .= 'State: ' . $json['state'] . "\n";
    $msg .= 'Zip: ' . $json['zip'] . "\n";
    $msg .= 'Phone: ' . $json['ph'] . "\n";
    $msg .= 'E-mail: ' . $json['email'] . "\n";
    if ($json['store'] == 1) {
        $msg .= 'Owner packet will be collected at Hillside.' . "\n";
    } elseif ($json['store'] == 2) {
        $msg .= 'Owner packet will be collected at Denfeld.' . "\n";
    }

    $msg .= "\n";
    $msg .= 'Update membership:' . "\n";
    $msg .= '<a href="http://key/git/fannie/modules/plugins2.0/PIKiller/PIApply.php?json=';
    $msg .= base64_encode(json_encode($json)) . "\">Click Here</a>\n";

	self::sendEmail(self::JOIN_EMAIL, "New Online Ownership", $msg);

    if (class_exists('PHPMailer')) {
        $mail = new PHPMailer();
        $mail->isMail();
        $mail->From = self::STORE_EMAIL;
        $mail->FromName = 'Whole Foods Co-op';
        $mail->addReplyTo(self::REPLY_EMAIL);
        $mail->addAddress(self::PLUGIN_EMAIL);
        $mail->addStringAttachment(json_encode($json), 'data.json', 'base64', 'application/json');
        $mail->isHTML(false);
        $mail->Body = 'blank';
        $mail->send();
    }
}

public static function giftNotification($json)
{
	$msg = sprintf("Thank you for purchasing a \$%.2f Whole Foods Co-op gift card\n\n", $json['amt']);
    if ($json['store'] == 1) {
        $msg .= 'Your card will be available for pickup on '
            . date('F j, Y', strtotime('+1 day')) . ' at the ';
        $msg .= 'Hillside store:' . "\n";
        $msg .= '610 E 4th St.' . "\n";
        $msg .= 'Duluth, MN 55805' . "\n";
        $msg .= '218-728-0884' . "\n";
    } elseif ($json['store'] == 2) {
        $msg .= 'Your card will be available for pickup on '
            . date('F j, Y', strtotime('+1 day')) . ' at the ';
        $msg .= 'Denfeld store:' . "\n";
        $msg .= '4426 Grand Ave' . "\n";
        $msg .= 'Duluth, MN 55807' . "\n";
        $msg .= '218-728-0884' . "\n";
    } elseif ($json['store'] == 99) {
        $msg .= 'Your card will be mailed to:' . "\n";
        $msg .= $json['sname'] . "\n";
        $msg .= $json['addr1'] . "\n";
        $msg .= $json['addr2'] . "\n";
        $msg .= $json['city'] . ', ' . $json['state'] . ' ' . $json['zip'] . "\n";
    }

	self::sendEmail($json['email'], "Whole Foods Co-op Gift Card", $msg);
}

public static function giftAdminNotification($json)
{
	$msg = sprintf("New \$%.2f Whole Foods Co-op gift card purchase\n\n", $json['amt']);
    $msg .= 'Name: ' . $json['name'] . "\n";
    $msg .= 'Phone: ' . $json['ph'] . "\n";
    $msg .= 'E-mail: ' . $json['email'] . "\n";
    if ($json['store'] == 1) {
        $msg .= "The customer will pick up the card at Hillside";
    } elseif ($json['store'] == 99) {
        $msg .= 'The card should be mailed to:' . "\n";
        $msg .= $json['sname'] . "\n";
        $msg .= $json['addr1'] . "\n";
        $msg .= $json['addr2'] . "\n";
        $msg .= $json['city'] . ', ' . $json['state'] . ' ' . $json['zip'] . "\n";
    }

	self::sendEmail($json['email'], "Online Gift Card", $msg);
}

}

