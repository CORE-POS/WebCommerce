<?php
/*******************************************************************************

    Copyright 2007,2011 Whole Foods Co-op

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

$IS4C_PATH = isset($IS4C_PATH)?$IS4C_PATH:"";
if (empty($IS4C_PATH)){ while(!file_exists($IS4C_PATH."is4c.css")) $IS4C_PATH .= "../"; }

if (!class_exists('PhpAutoLoader')) {
    require(dirname(__FILE__) . '/../vendor-code/PhpAutoLoader/PhpAutoLoader.php');
}

class confirm extends BasicPage {

    private $mode;
    private $msgs;

    function main_content()
    {
        if ($this->mode == 0)
            $this->confirm_content(False);
        else
            $this->confirm_content(True);
    }

    function confirm_content($receiptMode=False)
    {
        global $IS4C_PATH,$IS4C_LOCAL;
        $db = Database::tDataConnect();
        $empno = AuthUtilities::getUID(AuthLogin::checkLogin());

        $q = $db->prepare_statement("SELECT * FROM cart WHERE emp_no=?");
        $r = $db->exec_statement($q, array($empno));
        
        if (!$receiptMode){
            echo '<form action="confirm.php" method="post">';
        }
        else {
            echo '<blockquote>Your order has been processed</blockquote>';
        }
        if (!empty($this->msgs)){
            echo '<blockquote>'.$this->msgs.'</blockquote>';
        }
        echo "<table id=\"carttable\" cellspacing='0' cellpadding='4' border='1'>";
        echo "<tr><th>Item</th><th>Qty</th><th>Price</th>
            <th>Total</th><th>&nbsp;</th></tr>";
        $ttl = 0.0;
        $pickupDate = true;
        $class = false;
        while($w = $db->fetch_row($r)){
            printf('<tr>
                <td>%s %s</td>
                <td><input type="hidden" name="upcs[]" value="%s" />%.2f
                <input type="hidden" name="scales[]"
                value="%d" /><input type="hidden" name="orig[]" value="%.2f" /></td>
                <td>$%.2f</td><td>$%.2f</td><td>%s</td></tr>',
                $w['brand'],$w['description'],
                $w['upc'],$w['quantity'],$w['scale'],$w['quantity'],
                $w['unitPrice'],$w['total'],
                (empty($w['saleMsg'])?'&nbsp;':$w['saleMsg'])
            );
            $ttl += $w['total'];
            if (stristr($w['description'], 'CLASS')) {
                $class = true;
            } elseif ($w['upc'] == '0000000001127') {
                $pickupDate = true;
            }
        }
        printf('<tr><th colspan="3" align="right">Subtotal</th>
            <td>$%.2f</td><td>&nbsp;</td></tr>',$ttl);
        $taxP = $db->prepare_statement("SELECT taxes FROM taxTTL WHERE emp_no=?");
        $taxR = $db->exec_statement($taxP,array($empno));
        $taxW = $db->fetch_row($taxR);
        $taxes = round($taxW['taxes'], 2);
        printf('<tr><th colspan="3" align="right">Taxes</th>
            <td>$%.2f</td><td>&nbsp;</td></tr>',$taxes);
        printf('<tr><th colspan="3" align="right">Total</th>
            <td>$%.2f</td><td>&nbsp;</td></tr>',$taxes+$ttl);
        echo "</table><br />";
        if (!$receiptMode) {
            if ($ttl > 0) {
                $pay_class = RemoteProcessor::CURRENT_PROCESSOR;
                $proc = new $pay_class();
                $ident = $_REQUEST[$proc->postback_field_name];
                printf('<input type="hidden" name="token" value="%s" />',$ident);
            }
            echo '<b>Phone Number (incl. area code)</b>: ';
            echo '<input type="text" name="ph_contact" required /><br />';
            /*
            echo '<blockquote>We require a phone number because some email providers
                have trouble handling .coop email addresses. A phone number ensures
                we can reach you if there are any questions about your order.</blockquote>';
            */
            if ($class) {
                echo '<b>Additional attendees</b>: ';
                echo '<input type="text" name="attendees" /><br />';
                echo '<blockquote>If you are purchasing a ticket for someone else, please
                    enter their name(s) so we know to put them on the list.</blockquote>';
            }
            if ($pickupDate) {
                $min = date('Y-m-d', strtotime('tomorrow'));
                $max = date('Y-m-d', strtotime('+3 days'));
                $btwn = date('n/j', strtotime($min)) . ' and ' . date('n/j', strtotime($max));
                echo '<b>Choose order pick-up date (between ' . $btwn . ') & time (between 4 and 7pm)</b>:<br />';
                echo '<input type="date" name="pickup_date" min="' . $min . '" max="' . $max . '" 
                        placeholder="YYYY-MM-DD"/>';
                echo '<input type="time" name="time" min="16:00" max="19:00" placeholder="HH:MM" /><br />';
                echo '<b>Vehicle make, model, & color</b>:<br />';
                echo '<input type="text" name="vehicle" required /><br />';
            }
            echo '<input type="submit" name="confbtn" value="Finalize Order" />';
            echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
            if ($ttl > 0 && $proc->cancelable) {
                echo '<input type="submit" name="backbtn" value="Go Back" />';
            }
        } else {
            /* refactor idea: clear in preprocess()
               and print receipt from a different script
            */
            
            /* REMOVE

            // normalize date in case items were added long before checkout
            $dateP = $db->prepare_statement('UPDATE localtemptrans SET datetime=' . $db->now() . '
                                        WHERE emp_no=?');
            $dateR = $db->exec_statement($dateP, array($empno));
            // mark test transactions as lane #99
            if (!RemoteProcessor::LIVE_MODE) {
                $testP = $db->prepare_statement('UPDATE localtemptrans SET register_no=99 WHERE emp_no=?');
                $testR = $db->exec_statement($testP, array($empno));
            }
            // rotate data
            $endP = $db->prepare_statement("INSERT INTO localtrans SELECT l.* FROM
                localtemptrans AS l WHERE emp_no=?");
            $endR = $db->exec_statement($endP,array($empno));

            $noteP = $db->prepare_statement("DELETE FROM CurrentOrderNotes WHERE userID=?");
            $noteR = $db->exec_statement($noteP, array($empno));

            $pendingCols = Database::localMatchingColumns($db, 'localtemptrans', 'pendingtrans');
            $endP = $db->prepare_statement("INSERT INTO pendingtrans ($pendingCols) 
                                            SELECT $pendingCols 
                                            FROM localtemptrans AS l 
                                            WHERE l.emp_no=?");

            $endR = $db->exec_statement($endP,array($empno));
            if ($endR !== False){
                $clearP = $db->prepare_statement("DELETE FROM localtemptrans WHERE emp_no=?");
                $db->exec_statement($clearP,array($empno));
            }

            REMOVE */
        }
    }

    function preprocess()
    {
        global $IS4C_LOCAL;
        $this->mode = 0;
        $this->msgs = "";

        if (isset($_REQUEST['backbtn'])){
            header("Location: cart.php");

            return false;
        } else if (isset($_REQUEST['confbtn'])) {
            /* confirm payment with paypal
               if it succeeds, add tax and tender
               shuffle order to pendingtrans table
               send order notifications
            */
            $ph = $_REQUEST['ph_contact'];
            $ph = preg_replace("/[^\d]/","",$ph);
            if (strlen($ph) != 10){
                $this->msgs = 'Phone number with area code is required';
                return True;
            }
            $attend = isset($_REQUEST['attendees']) ? $_REQUEST['attendees'] : '';
            $pickup = isset($_REQUEST['pickup_date']) ? $_REQUEST['pickup_date'] : '';
            $time = isset($_REQUEST['time']) ? $_REQUEST['time'] : '';
            $vehicle = isset($_REQUEST['vehicle']) ? $_REQUEST['vehicle'] : '';

            $notes = '';
            if ($attend) {
                $notes .= "Additional attendees: {$attend}\n";
            }
            if ($pickup) {
                $notes .= "Pickup date: {$pickup}\n";
                list($hour, $minute) = explode(':', $time, 2);
                $hour -= 12;
                $notes .= "Pickup time: {$hour}:{$minute}PM\n";
            }

            $db = Database::tDataConnect();
            $email = AuthLogin::checkLogin();
            $empno = AuthUtilities::getUID($email);
            $owner = AuthUtilities::getOwner($email);
            $subP = $db->prepare_statement("SELECT sum(total) FROM cart WHERE emp_no=?");
            $sub = $db->exec_statement($subP,array($empno));
            $subW = $db->fetch_row($sub);
            $sub = $subW[0];

            $final_amount = $sub;
            if (isset($_REQUEST['token']) && !empty($_REQUEST['token'])){
                $pay_class = RemoteProcessor::CURRENT_PROCESSOR;
                $proc = new $pay_class();
                $done = $proc->finalizePayment($_REQUEST['token']);

                if ($done) {
                    $this->mode=1;

                    /* get tax from db and add */
                    $taxP = $db->prepare_statement("SELECT taxes FROM taxTTL WHERE emp_no=?");
                    $taxR = $db->exec_statement($taxP,array($empno));
                    $taxW = $db->fetch_row($taxR);
                    $taxes = round($taxW['taxes'], 2);
                    TransRecord::addtax($taxes);
                    
                    /* add paypal tender */
                    TransRecord::addtender($proc->tender_name, $proc->tender_code, -1*$final_amount);
                }
            } else if (floor($sub * 100) == 0) {
                // items totalled $0. No paypal to process.
                $this->mode=1;
                $final_amount = '0.00';
            }

            // REMOVE
            if ($pickup) $this->mode = 1;

            if ($this->mode == 1) {
                /* purchase succeeded - send notices */
                $cart = Notices::customerConfirmation($empno,$email,$final_amount,$notes);

                if ($pickup) {
                    Notices::pickup($empno,$email,$pickup,$time,$vehicle,$ph);
                } else {
                    $addrP = $db->prepare_statement("SELECT e.email_address FROM localtemptrans
                        as l INNER JOIN superdepts AS s ON l.department=s.dept_ID
                        INNER JOIN superDeptEmails AS e ON s.superID=e.superID
                        WHERE l.emp_no=? GROUP BY e.email_address");
                    $addrR = $db->exec_statement($addrP,array($empno));
                    $addr = array();
                    while($addrW = $db->fetch_row($addrR))
                        $addr[] = $addrW[0];
                    if (count($addr) > 0 && RemoteProcessor::LIVE_MODE) {
                        Notices::mgrNotification($addr,$email,$ph,$owner,$final_amount,$notes,$cart);
                        Notices::adminNotification($empno,$email,$ph,$owner,$final_amount,$cart,false);
                    } else {
                        Notices::adminNotification($empno,$email,$ph,$owner,$final_amount,$cart,true);
                    }
                }
            }
        }

        return True;
    }
}

if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
    new confirm();
}

?>
