<?php
/*******************************************************************************

    Copyright 2007,2010 Whole Foods Co-op

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

class GiftPage extends BasicPage 
{
	private $entries;
	private $msgs = '';
    private $mode = 'form';
    private $token = '';

	function main_content()
    {
		global $IS4C_PATH;
        if ($this->mode == 'confirm') {
            return $this->confirm_content();
        } elseif ($this->mode == 'receipt') {
            return $this->receipt_content();
        }
		echo $this->msgs;
		?>
        <style type="text/css">
        input.medium {
            width: 12em;
        }
        </style>
		<div id="loginTitle">
        <h2>Purchase a Gift Card</h2>
        <p class="text-left">
        Gift cards may be purchased online in any amount between $5 and $500. Gift cards may be picked up in
        store or mailed to the designated address. <strong>There is a $1 fee for shipping on mailed cards</strong>.
        </p>
		<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
		<table class="table" cellspacing="4" cellpadding="4">
        <tr>
            <th>Amount</th>
            <td colspan="5"><input type="number" min="5" max="500" step="0.01" name="amt" required value="<?php echo $this->entries['amt']; ?>" /></td>
		</tr>
        <tr>
            <th>Your Name</th>
            <td colspan="5"><input type="text" required name="name" value="<?php echo $this->entries['name']; ?>" /></td>
		</tr>
		<tr>
            <th>Phone Number</th>
            <td><input type="tel" class="medium" required name="ph" value="<?php echo $this->entries['ph']; ?>" /></td>
            <th>E-mail address</th>
            <td colspan="3"><input type="email" required name="email" value="<?php echo $this->entries['email']; ?>" /></td>
		</tr>
        <tr>
            <th>Delivery</th>
            <td colspan="5">
                <select name="store" required>
                    <option value="99">Mail to the address below ($1 fee)</option>
                    <option value="1">Pick up at Hillside (610 E 4th St, Duluth, MN 55805)</option>
                </select>
            </td>
        </tr>
        <tr>
            <th>Shipping Name</th>
            <td colspan="5"><input type="text" name="sname" value="<?php echo $this->entries['sname']; ?>" /></td>
		</tr>
		<tr>
            <th>Street Address</th>
            <td><input type="text" class="medium" name="addr1" value="<?php echo $this->entries['addr1']; ?>" /></td>
            <th>Apt. #</th>
            <td colspan="3"><input type="text" name="addr2" placeholder="Optional" value="<?php echo $this->entries['addr2']; ?>" /></td>
		</tr>
		<tr>
            <th>City</th>
            <td><input type="text" class="medium" name="city" value="<?php echo $this->entries['city']; ?>" /></td>
            <th>State</th>
            <td><input type="text" name="state" value="<?php echo $this->entries['state']; ?>" /></td>
            <th>Zip Code</th>
            <td><input type="text" name="zip" value="<?php echo $this->entries['zip']; ?>" /></td>
		</tr>
        <tr>
            <th>Notes</th>
            <td colspan="5"><textarea name="notes"><?php echo $this->entries['notes']; ?></textarea></td>
        <tr>
            <th colspan="6" align="center" style="text-align:center;">
            <input type="image" src="https://www.paypal.com/en_US/i/btn/btn_xpressCheckout.gif"> 
            <input type="hidden" name="submit" value="submit" />
            </th>
		</tr>
		</table>
		</form>
		</div>
        <hr />
        <div style="font-size: 90%;">
        <h4>The Fine Print</h4>
        Mailed gift card are sent via USPS. We'll make every effort to mail them by the next business day but cannot guarantee any
        specific delivery date. With in store pickup cards should typically be available within a few minutes of ordering. Gift
        card purchases are not eligible for refunds or returns.
        </div>
		<?php
	}

    public function confirm_content()
    {
		global $IS4C_PATH;
        ?>
		<div id="loginTitle">
		<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
        <input type="hidden" name="_token" value="<?php echo $this->token; ?>" />
        <p>
        Your gift card purchase is almost complete. Please click <i>Finalize Payment</i> to
        confirm your <?php printf('$%.2f', $this->entries['amt'] + ($this->entries['store'] == 99 ? 1 : 0)); ?>
        payment. 
        </p>
        <p>
        <input type="submit" name="finalize" value="Finalize Payment" />
        </p>
        </form>
        </div>
        <?php
    }

    public function receipt_content()
    {
		global $IS4C_PATH;
        ?>
		<div id="loginTitle">
        Thanks for your purchase. Your <?php printf('$%.2f', $this->entries['amt']); ?> gift card will be mailed to:
        <br />
        <?php echo $this->entries['sname']; ?><br />
        <?php echo $this->entries['addr1']; ?><br />
        <?php echo $this->entries['addr2']; ?><br />
        <?php echo $this->entries['city'] . ', ' . $this->entries['state'] . ' ' . $this->entries['zip']; ?><br />
        </div>
        <?php
    }

	function preprocess()
    {
        global $IS4C_PATH,$PAYMENT_URL_SUCCESS, $PAYMENT_URL_FAILURE;
        if (session_id() === '') {
            session_start();
        }
		$this->entries = array(
			'name'=>(isset($_REQUEST['name'])?$_REQUEST['name']:''),
			'sname'=>(isset($_REQUEST['sname'])?$_REQUEST['sname']:''),
			'addr1'=>(isset($_REQUEST['addr1'])?$_REQUEST['addr1']:''),
			'addr2'=>(isset($_REQUEST['addr2'])?$_REQUEST['addr2']:''),
			'city'=>(isset($_REQUEST['city'])?$_REQUEST['city']:''),
			'state'=>(isset($_REQUEST['state'])?$_REQUEST['state']:''),
			'zip'=>(isset($_REQUEST['zip'])?$_REQUEST['zip']:''),
			'ph'=>(isset($_REQUEST['ph'])?$_REQUEST['ph']:''),
			'store'=>(isset($_REQUEST['store'])?$_REQUEST['store']:''),
			'email'=>(isset($_REQUEST['email'])?$_REQUEST['email']:''),
			'amt'=>(isset($_REQUEST['amt'])?$_REQUEST['amt']:''),
			'notes'=>(isset($_REQUEST['notes'])?$_REQUEST['notes']:''),
		);
        $shipping = $this->entries['store'] == 99 ? 1 : 0;

		$dbc = Database::pDataConnect();
        $pay_class = RemoteProcessor::CURRENT_PROCESSOR;
        $proc = new $pay_class();
        if (isset($_REQUEST[$proc->postback_field_name])) {
            $this->entries = $_SESSION['giftInfo'];
            $this->mode = 'confirm';
            $this->token = $_REQUEST[$proc->postback_field_name];
            return true;
        } elseif (isset($_REQUEST['_token']) && isset($_REQUEST['finalize'])) {
            $done = $proc->finalizePayment($_REQUEST['_token']);
            $this->mode = 'done';
            $this->entries = $_SESSION['giftInfo'];
            unset($_SESSION['giftInfo']);

            $final_amount = $this->entries['amt'];
            $_SESSION['emp_no'] = 1001;
            $db->query('LOCK TABLES localtemptrans WRITE');
            TransRecord::addOpenRing($final_amount, 903, 'Gift Card');
            if ($shipping == 1) {
                TransRecord::addOpenRing(1, 800, 'Shipping');
            }
            TransRecord::addtender($proc->tender_name, $proc->tender_code, -1*($final_amount+$shipping));
            unset($_SESSION['emp_no']);

            if (!RemoteProcessor::LIVE_MODE) {
                $testP = $db->prepare_statement('UPDATE localtemptrans SET register_no=99 WHERE emp_no=?');
                $testR = $db->exec_statement($testP, array(1001));
            }
            // rotate data
            $endP = $db->prepare_statement("INSERT INTO localtrans SELECT l.* FROM
                localtemptrans AS l WHERE emp_no=?");
            $endR = $db->exec_statement($endP,array(1001));

            $pendingCols = Database::localMatchingColumns($db, 'localtemptrans', 'pendingtrans');
            $endP = $db->prepare_statement("INSERT INTO pendingtrans ($pendingCols) 
                                            SELECT $pendingCols 
                                            FROM localtemptrans AS l 
                                            WHERE l.emp_no=?");

            $endR = $db->exec_statement($endP,array(1001));
            if ($endR !== false) {
                $clearP = $db->prepare_statement("DELETE FROM localtemptrans WHERE emp_no=?");
                $db->exec_statement($clearP,array(1001));
            }
            $db->query('UNLOCK TABLES');

            /**
              Send email notifications to customer, store staff.
              Latter should include all signup info, possibly in a
              click-to-apply JSON encoding.
            */
            Notices::giftNotification($this->entries);
            Notices::giftAdminNotification($this->entries);
            $this->mode = 'receipt';

		} elseif (isset($_REQUEST['submit'])) {
			// validate
			$errors = false;

			if (!AuthUtilities::isEmail($this->entries['email'])){
				$this->msgs .= '<div class="errorMsg">';
				$this->msgs .= 'Not a valid e-mail address: '.$this->entries['email'];
				$this->msgs .= '</div>';
				$this->entries['email'] = '';
				$errors = true;
			}
            if (empty($this->entries['name'])) {
                $this->msgs .= '<div class="errorMsg">';
                $this->msgs .= 'Name is required';
                $this->msgs .= '</div>';
                $this->entries['name'] = '';
                $errors = true;
            }

            if ($this->entries['store'] == 99) {

                if (empty($this->entries['addr1'])) {
                    $this->msgs .= '<div class="errorMsg">';
                    $this->msgs .= 'Address is required';
                    $this->msgs .= '</div>';
                    $this->entries['addr1'] = '';
                    $errors = True;
                }

                if (empty($this->entries['city'])) {
                    $this->msgs .= '<div class="errorMsg">';
                    $this->msgs .= 'City is required';
                    $this->msgs .= '</div>';
                    $this->entries['city'] = '';
                    $errors = true;
                }

                if (empty($this->entries['state'])) {
                    $this->msgs .= '<div class="errorMsg">';
                    $this->msgs .= 'State is required';
                    $this->msgs .= '</div>';
                    $this->entries['state'] = '';
                    $errors = true;
                }

                if (empty($this->entries['zip'])) {
                    $this->msgs .= '<div class="errorMsg">';
                    $this->msgs .= 'Zip code is required';
                    $this->msgs .= '</div>';
                    $this->entries['zip'] = '';
                    $errors = true;
                }
            }

			if (empty($this->entries['ph'])) {
				$this->msgs .= '<div class="errorMsg">';
				$this->msgs .= 'Phone number is required';
				$this->msgs .= '</div>';
				$this->entries['zip'] = '';
				$errors = true;
			}

			if (empty($this->entries['store'])) {
				$this->msgs .= '<div class="errorMsg">';
				$this->msgs .= 'Store where you\'ll collect ID card is required';
				$this->msgs .= '</div>';
				$errors = true;
			}

			if ($this->entries['amt'] != 1 && !is_numeric($this->entries['amt'])) {
				$this->msgs .= '<div class="errorMsg">';
				$this->msgs .= 'Invalid card amount choice.';
				$this->msgs .= '</div>';
				$errors = true;
			} elseif ($this->entries['amt'] < 5 || $this->entries['amt'] > 500) {
				$this->msgs .= '<div class="errorMsg">';
				$this->msgs .= 'Card amount must be between $5 and $500';
				$this->msgs .= '</div>';
				$errors = true;
            }

            if (empty($this->entries['sname'])) {
                $this->entries['sname'] = $this->entries['name'];
            }

			if (!$errors) {

                $_SESSION['giftInfo'] = $this->entries;

                $amount = $this->entries['amt'] + $shipping;
                $PAYMENT_URL_SUCCESS = 'http://' . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];
                $PAYMENT_URL_FAILURE = 'http://' . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];
                if (substr($PAYMENT_URL_FAILURE, -9) == 'index.php') {
                    $PAYMENT_URL_FAILURE = substr($PAYMENT_URL_FAILURE, 0, strlen($PAYMENT_URL_FAILURE)-9);
                }
                $PAYMENT_URL_FAILURE .= 'cancel/';
                $init = $proc->initializePayment($amount, '0.00', $this->entries['email']);
                if ($init === false) {
                    $this->msgs .= 'Error: cannot process payment at this time.';
                    return true;
                } else {
                    $proc->redirectToProcess($init);
                    return false;
                }
			}
		}

		return true;
	}
}

if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
    new GiftPage();
}

