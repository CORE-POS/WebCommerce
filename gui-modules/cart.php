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

class cart extends BasicPage {

	var $notices;

	function js_content(){
		?>
		$(document).ready(function(){
			$('#searchbox').focus();
		});
        function saveNotes(text) {
            $.ajax({
                url: '/ajax-callbacks/ajax-save-notes.php',
                type: 'post',
                data: 'notes=' + encodeURIComponent(text)
            });
        }
		<?php
	}

	function main_content(){
		global $IS4C_PATH,$IS4C_LOCAL;
		$db = Database::tDataConnect();
		$empno = AuthUtilities::getUID(AuthLogin::checkLogin());

		$q = $db->prepare_statement("SELECT c.*, s.superID, p.scale
            FROM cart AS c
                LEFT JOIN products AS p ON c.upc=p.upc
                LEFT JOIN superdepts AS s ON p.department=s.dept_ID
            WHERE c.emp_no=?");
		$r = $db->exec_statement($q, array($empno));

		if (!RemoteProcessor::LIVE_MODE){
			echo '<h2>This store is in test mode; orders will not be processed</h2>';
		}

        $noteP = $db->prepare_statement("SELECT notes FROM CurrentOrderNotes WHERE userID=?");
        $noteR = $db->exec_statement($noteP, array($empno));
        $notes = '';
        if ($db->num_rows($noteR) > 0) {
            $noteW = $db->fetch_row($noteR);
            $notes = $noteW['notes'];
        }
		
		echo '<blockquote><em>'.$this->notices.'</em></blockquote>';
		echo '<form action="cart.php" method="post">';
		echo "<table class=\"table table-bordered\">";
		echo "<tr><th>&nbsp;</th><th>Item</th><th>Qty</th><th>Price</th>
			<th>Total</th><th>&nbsp;</th></tr>";
		$ttl = 0.0;
		while($w = $db->fetch_row($r)){
            $min = 0;
            $max = 6;
            $step = 1;
            if ($w['scale']) {
                $step = 0.25;
            }
            if ($w['superID'] == 6) {
                $max = 99;
            }
			printf('<tr>
				<td><input type="checkbox" name="selections[]" value="%s" /></td>
				<td>%s %s</td>
				<td><input type="hidden" name="upcs[]" value="%s" /><input type="number"
                min="%s" max="%s" step="%s"
				size="4" name="qtys[]" value="%.2f" /><input type="hidden" name="scales[]"
				value="%d" /><input type="hidden" name="orig[]" value="%.2f" /></td>
				<td>$%.2f</td><td>$%.2f</td><td>%s</td></tr>',
				$w['upc'],
				$w['brand'],$w['description'],
				$w['upc'], $min, $max, $step,
                $w['quantity'],$w['scale'],$w['quantity'],
				$w['unitPrice'],$w['total'],
				(empty($w['saleMsg'])?'&nbsp;':$w['saleMsg'])
			);
			$ttl += $w['total'];
		}
		printf('<tr><th colspan="4" align="right">Subtotal</th>
			<td>$%.2f</td><td>&nbsp;</td></tr>',$ttl);
		$taxP = $db->prepare_statement("SELECT taxes FROM taxTTL WHERE emp_no=?");
		$taxR = $db->exec_statement($taxP,array($empno));
		$taxes = 0;
		if ($db->num_rows($taxR) > 0) {
            $taxW = $db->fetch_row($taxR);
			$taxes = round($taxW['taxes'], 2);
        }
		printf('<tr><th colspan="4" align="right">Taxes</th>
			<td>$%.2f</td><td>&nbsp;</td></tr>',$taxes);
		printf('<tr><th colspan="4" align="right">Total</th>
			<td>$%.2f</td><td>&nbsp;</td></tr>',$taxes+$ttl);
        printf('<tr><th>Notes</th><td colspan="5">
            <textarea onchange="saveNotes(this.value);">%s</textarea></td></tr>', $notes);
		echo '<tr><td colspan="6" valign="top">';
		echo '<input type="submit" name="delbtn" style="" value="Delete Selected Items" />';
		echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
		echo '<input type="submit" name="qtybtn" value="Update Quantities" />';
		echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
		echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
		echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
		echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
        $pay_class = RemoteProcessor::CURRENT_PROCESSOR;
        $mod = new $pay_class();
        echo $mod->checkoutButton();
        /*
        echo '<a class="button btn-custom pull-right" href="/gui-modules/pickup.php">Arrange Pickup</a>';
        */
		echo "</td></tr>";
		echo "</table><br />";
	}

	function preprocess()
    {
		global $IS4C_LOCAL;
		$db = Database::tDataConnect();
		$empno = AuthUtilities::getUID(AuthLogin::checkLogin());

		if (isset($_REQUEST['qtybtn'])){
			for($i=0; $i<count($_REQUEST['qtys']);$i++){
				if (!is_numeric($_REQUEST['qtys'][$i])) continue;
				if ($_REQUEST['qtys'][$i] == $_REQUEST['orig'][$i]) continue;

				$upc = $_REQUEST['upcs'][$i];
				$qty = round($_REQUEST['qtys'][$i]);
				if ($_REQUEST['scales'][$i] == 1)
					$qty = number_format(round($_REQUEST['qtys'][$i]*4)/4,2);
				if ($qty == $_REQUEST['orig'][$i]) continue;

				//$availP = $db->prepare_statement("SELECT available FROM productOrderLimits WHERE upc=?");
				$limit = 6;
				$availP = $db->prepare_statement("SELECT superID FROM products as p 
                                left join superdepts AS s on p.department=s.dept_ID
                                where p.upc=?");
				$availR = $db->exec_statement($availP,array($upc));
				if ($db->num_rows($availR) > 0) {
                    $availW = $db->fetch_row($availR);
                    if ($availW['superID'] == 6) {
                        $limit = 99;
                    }
					//$limit = $availW['available'];
                }
				if ($qty > $limit && $qty > 0){
					$qty = $limit;
					if ($qty <= 0) $qty=1;
					$this->notices = "Due to limited availability, requested quantity
						cannot be provided";
				}

				$q1 = $db->prepare_statement("DELETE FROM localtemptrans WHERE
					upc=? AND emp_no=?");
				$db->exec_statement($q1,array($upc,$empno));
				if ($qty > 0) {
					TransRecord::addUPC($upc,$qty);
                }
			}
		}
		if (isset($_REQUEST['delbtn'])){
			if (isset($_REQUEST['selections'])){
				foreach($_REQUEST['selections'] as $upc){
					$q1 = $db->prepare_statement("DELETE FROM localtemptrans WHERE
						upc=? AND emp_no=?");
					$db->exec_statement($q1,array($upc,$empno));
				}
			}
		}
		if (isset($_REQUEST['checkoutButton'])){
			$dbc = Database::tDataConnect();
			$email = AuthLogin::checkLogin();
			$empno = AuthUtilities::getUID($email);
			$subP = $dbc->prepare_statement("SELECT sum(total) FROM cart WHERE emp_no=?");
			$sub = $dbc->exec_statement($subP,array($empno));
            $row = $dbc->fetch_row($sub);
            $sub = $row[0];
			$taxP = $dbc->prepare_statement("SELECT sum(taxes) FROM taxTTL WHERE emp_no=?");
			$tax = $dbc->exec_statement($taxP,array($empno));
            $row = $dbc->fetch_row($tax);
            $tax = $row[0];

            $ttl = round($sub + $tax, 2);
            if (floor($ttl*100) == 0) {
                header('Location: confirm.php');

                return false;
            } else {
                $pay_class = RemoteProcessor::CURRENT_PROCESSOR;
                $proc = new $pay_class();
                $init = $proc->initializePayment(round($sub, 2), round($tax, 2), $email);
                if ($init === false) {
                    echo 'Error: cannot process payment at this time.';
                } else {
                    $proc->redirectToProcess($init);
                }

                return false;
            }
		}

		return True;
	}
}

if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
    new cart();
}

?>
