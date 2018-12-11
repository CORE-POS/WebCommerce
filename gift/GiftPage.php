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

    // suppress normal menu
	function top_menu()
    {
        return '';
    }

    function switchHeader()
    {
        if (date('Ymd') <= 20181130) {
            return 'Owners: Buy Gift Cards, get Co-op Cash';
        }

        return 'Purchase a Gift Card';
    }

    function switchBody()
    {
        if (date('Ymd') <= 20181130) {
            return '
                Gift giving just got easier! Now through November 30th, for every $100.00 in Whole Foods Co-op gift cards purchased you will receive $20.00 in Co-op Cash for yourself.
                This deal is just for co-op owners. Not an owner?
                <a href="../join/">Join Today</a>!<br /><br />
                Co-op Cash is redeemable from December 1, 2018 through December 31, 2018.
                Purchase amount can be placed on multiple separate gift cards, however there is a $500.00 limit.
                Gift cards do not expire.
                Gift cards can be picked up at either location.
            ';
        }

        return 'Gift cards purchased online can be picked up at either location or mailed for a $1 fee.';
    }

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
        #hero {
            background-image: url('/src/img/giftcard.jpg');
        }
        div.post-wrap {
            padding-top: 0px !important;
        }
        article#post-0 {
            padding-top: 15px !important;
        }
        </style>
		<div id="loginTitle">
        <h2><?php echo $this->switchHeader(); ?></h2>
        <p class="text-left">
        <!--
        Gift cards may be purchased online in any amount between $5 and $500. Gift cards may be picked up in
        store or mailed to the designated address. <strong>There is a $1 fee for shipping on mailed cards</strong>.-->
        <?php echo $this->switchBody(); ?>
        </p>
        <hr />
		<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
        <div class="text-left form-group">
            <label>Gift Card Amount</label><br />
            <div class="input-group">
                <span class="input-group-addon">$</span>
                <input type="number" name="amt" min="5" max="500" step="1" />
            </div>
        </div>
        <div class="text-left form-group">
            <label>Your Name</label>
            <input type="text" required name="name" value="<?php echo $this->entries['name']; ?>" />
        </div>
        <!--
        <div class="text-left form-group">
            <label>Owner Number</label>
            <input type="text" name="owner" placeholder="optional" value="<?php echo $this->entries['owner']; ?>" />
        </div>
        -->
        <div class="text-left form-group">
            <label>Phone Number</label><br />
            <input type="tel" class="medium" required name="ph" value="<?php echo $this->entries['ph']; ?>" />
        </div>
        <div class="text-left form-group">
            <label>E-mail address</label>
            <input type="email" required name="email" value="<?php echo $this->entries['email']; ?>" />
        </div>
        <div class="text-left form-group">
            <label>Store</label>
            <select name="store" required
                onchange="if (this.value==99) { $('.address-info').show(); } else { $('.address-info').hide(); }">
                <option value="">Select one...</option>
                <option value="99">Mail to the address below ($1 fee)</option>
                <option value="1">Pick up at Hillside (610 E 4th St, Duluth, MN 55805)</option>
                <option value="2">Pick up at Denfeld (4426 Grand Ave, Duluth, MN 55807)</option>
            </select>
        </div>
        <div class="text-left form-group address-info collapse">
            <label>Shipping Name</label>
            <input type="text" name="sname" placeholder="If different from above" value="<?php echo $this->entries['sname']; ?>" />
        </div>
        <div class="text-left form-group address-info collapse">
            <label>Street Address</label>
            <input type="text" name="addr1" value="<?php echo $this->entries['addr1']; ?>" />
        </div>
        <div class="text-left form-group address-info collapse">
            <label>City</label>
            <input type="text" name="city" value="<?php echo $this->entries['city']; ?>" />
        </div>
        <div class="text-left form-group address-info collapse">
            <label>State</label>
            <input type="text" name="state" value="<?php echo $this->entries['state']; ?>" />
        </div>
        <div class="text-left form-group address-info collapse">
            <label>Zip Code</label>
            <input type="text" name="zip" value="<?php echo $this->entries['zip']; ?>" />
        </div>
        <div class="text-left form-group">
            <label>Notes</label>
            <textarea name="notes" rows="5"
                placeholder="If you would like to split the amount across multiple gift cards, for example two $50 cards instead of one $100 card please specify that here."><?php echo $this->entries['notes']; ?></textarea>
        </div>
        <div class="text-left form-group">
            <input type="image" src="https://www.paypal.com/en_US/i/btn/btn_xpressCheckout.gif"> 
            <input type="hidden" name="submit" value="submit" />
        </div>
		</form>
		</div>
        <hr />
        <div style="font-size: 90%;">
        <h4>The Fine Print</h4>
        Mailed gift card are sent via USPS. We'll make every effort to mail them by the next business day but cannot guarantee any
        specific delivery date.
        With in store pickup cards should typically be available within a couple hours of ordering. Gift
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
        Thanks for your purchase. Your <?php printf('$%.2f', $this->entries['amt']); ?> gift card will be
        <?php
        if ($this->entries['store'] == 99) {
            echo " mailed to:<br />";
            echo $this->entries['sname']. '<br />';
            echo $this->entries['addr1']. '<br />';
            echo $this->entries['city'] . ', ' . $this->entries['state'] . ' ' . $this->entries['zip'] . '<br />';
        } else if ($this->entries['store'] == 1) {
            echo ' available for pick up at the Hillside store (610 E 4th St, Duluth, MN 55805)<br />';
        } else {
            echo ' available for pick up at the Denfeld store (4426 Grand Ave, Duluth, MN 55807)<br />';
        }
        echo '</div>';
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
			'city'=>(isset($_REQUEST['city'])?$_REQUEST['city']:''),
			'state'=>(isset($_REQUEST['state'])?$_REQUEST['state']:''),
			'zip'=>(isset($_REQUEST['zip'])?$_REQUEST['zip']:''),
			'ph'=>(isset($_REQUEST['ph'])?$_REQUEST['ph']:''),
			'store'=>(isset($_REQUEST['store'])?$_REQUEST['store']:''),
			'email'=>(isset($_REQUEST['email'])?$_REQUEST['email']:''),
			'amt'=>(isset($_REQUEST['amt'])?$_REQUEST['amt']:''),
			'notes'=>(isset($_REQUEST['notes'])?$_REQUEST['notes']:''),
			'owner'=>(isset($_REQUEST['owner'])?$_REQUEST['owner']:''),
		);
        $shipping = $this->entries['store'] == 99 ? 1 : 0;

		$dbc = Database::pDataConnect();
        $db = $dbc;
        $pay_class = RemoteProcessor::CURRENT_PROCESSOR;
        $proc = new $pay_class();
        if (isset($_REQUEST[$proc->postback_field_name])) {
            $this->entries = $_SESSION['giftInfo'];
            $this->mode = 'confirm';
            $this->token = $_REQUEST[$proc->postback_field_name];
            return true;
        } elseif (isset($_REQUEST['_token']) && isset($_REQUEST['finalize'])) {
            $done = $proc->finalizePayment($_REQUEST['_token']);
            if (!$done) {
                $this->mode = 'form';
				$this->msgs .= '<div class="errorMsg">';
                $this->msgs .= 'Sorry, there was an error processing your payment. Please try again in a few minutes';
                $this->msgs .= '</div>';

                return true;
            }
            $this->mode = 'done';
            $this->entries = $_SESSION['giftInfo'];
            unset($_SESSION['giftInfo']);

            $final_amount = $this->entries['amt'];
            $_SESSION['emp_no'] = 1001;
            TransRecord::addOpenRing($final_amount, 903, 'Gift Card');
            if ($shipping == 1) {
                TransRecord::addOpenRing(1, 800, 'Shipping');
            }
            TransRecord::addtender($proc->tender_name, $proc->tender_code, -1*($final_amount+$shipping));
            unset($_SESSION['emp_no']);

            if (!RemoteProcessor::LIVE_MODE) {
                $testP = $dbc->prepare_statement('UPDATE localtemptrans SET register_no=99 WHERE emp_no=?');
                $testR = $dbc->exec_statement($testP, array(1001));
            }
            // rotate data
            $endP = $dbc->prepare_statement("INSERT INTO localtrans SELECT l.* FROM
                localtemptrans AS l WHERE emp_no=?");
            $endR = $dbc->exec_statement($endP,array(1001));

            $pendingCols = Database::localMatchingColumns($dbc, 'localtemptrans', 'pendingtrans');
            $endP = $dbc->prepare_statement("INSERT INTO pendingtrans ($pendingCols) 
                                            SELECT $pendingCols 
                                            FROM localtemptrans AS l 
                                            WHERE l.emp_no=?");

            $endR = $dbc->exec_statement($endP,array(1001));
            if ($endR !== false) {
                $clearP = $dbc->prepare_statement("DELETE FROM localtemptrans WHERE emp_no=?");
                $dbc->exec_statement($clearP,array(1001));
            }

            /**
              Send email notifications to customer, store staff.
              Latter should include all signup info, possibly in a
              click-to-apply JSON encoding.
            */
            Notices::giftNotification($this->entries);
            Notices::giftAdminNotification($this->entries, 'it@wholefoods.coop');
            if ($this->entries['store'] == 1 || $this->entries['store'] == 99) {
                Notices::giftAdminNotification($this->entries, 'csc@wholefoods.coop');
            } elseif ($this->entries['store'] == 2) {
                Notices::giftAdminNotification($this->entries, 'dcsc@wholefoods.coop');
            } else {
                Notices::giftAdminNotification($this->entries, 'csc@wholefoods.coop');
                Notices::giftAdminNotification($this->entries, 'dcsc@wholefoods.coop');
            }
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
				$this->msgs .= 'Store where you\'ll pick up the card is required';
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

                $proto = 'http://';
                if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') {
                    $proto = 'https://';
                }

                $amount = $this->entries['amt'] + $shipping;
                $PAYMENT_URL_SUCCESS = $proto . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];
                $PAYMENT_URL_FAILURE = $proto. $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];
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

