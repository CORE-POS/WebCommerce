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

class JoinPage extends BasicPage 
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
        <h2>Join the Co-op</h2>
        <p class="text-left">
        Become an <a href="http://wholefoods.coop/ownership/becoming-an-owner/">Owner</a> of Whole Foods Co-op today.
        Ownership is a $100 investment, not an annual fee. You may pay the full amount immediately or pay $20 now 
        and an additional $20 each month for the next four months.
        <!--and the remaining $80 at any time in any increment(s) over the next year. -->
        After 48 hours, you can activate your ownership by bringing 
        your picture ID to the Co-op location of your choice and picking up your Owner card and other materials.
        </p>
        <p class="text-left">
        Through the
        <a href="http://wholefoods.coop/cms/wp-content/uploads/2017/04/Fran-Skinner-Brochure-2016.pdf">
        Fran Skinner Memorial Matching Fund</a>.
        eligible shoppers can join for just $20. Please visit our Customer Service Counter to sign up for this program.
        </p>
		<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
		<table class="table" cellspacing="4" cellpadding="4">
		<tr>
            <th>First Name</th>
            <td><input class="medium" type="text" required name="fn" value="<?php echo $this->entries['fn']; ?>" /></td>
            <th>Last Name</th>
            <td colspan="3"><input type="text" required name="ln" value="<?php echo $this->entries['ln']; ?>" /></td>
		</tr>
		<tr>
            <th>Street Address</th>
            <td><input type="text" class="medium" required name="addr1" value="<?php echo $this->entries['addr1']; ?>" /></td>
            <th>Apt. #</th>
            <td colspan="3"><input type="text" name="addr2" placeholder="Optional" value="<?php echo $this->entries['addr2']; ?>" /></td>
		</tr>
		<tr>
            <th>City</th>
            <td><input type="text" required class="medium" name="city" value="<?php echo $this->entries['city']; ?>" /></td>
            <th>State</th>
            <td><input type="text" required name="state" maxlength="2" value="<?php echo $this->entries['state']; ?>" /></td>
            <th>Zip Code</th>
            <td><input type="text" required name="zip" value="<?php echo $this->entries['zip']; ?>" /></td>
		</tr>
		<tr>
            <th>Phone Number</th>
            <td><input type="tel" class="medium" required name="ph" value="<?php echo $this->entries['ph']; ?>" /></td>
            <th>E-mail address</th>
            <td colspan="3"><input type="text" required name="email" value="<?php echo $this->entries['email']; ?>" /></td>
		</tr>
        <tr>
            <th>Password</th>
            <td><input type="password" class="medium form-control" required name="passwd" value="<?php echo $this->entries['passwd']; ?>" /></td>
            <th>Re-Type Password</th>
            <td colspan=3"><input type="password" class="form-control" required name="passwd2" value="<?php echo $this->entries['passwd']; ?>" /></td>
		</tr>
        <tr>
            <th>Payment Options</th>
            <td align="left" colspan="5">
                <label><input type="radio" name="plan" value=2" checked />
                Full $100 today</label>
                <br />
                <!--<label><input type="radio" name="plan" value="1" />
                $20 today; remaining $80 due by 
                <?php echo date('F j, Y', strtotime('+1 year')); ?></label> -->
                <br /><label><input type="radio" name="plan" value="3" />$20 today; $20 automatically billed monthly for the next 4 months</label>
            </td>
        </tr>
        <tr>
            <th>I want to pick up my Owner ID card at:</th>
            <td colspan="5">
                <select name="store" required>
                    <option value="">Choose a store</option>
                    <option value="1">Hillside (610 E 4th St, Duluth, MN 55805)</option>
                    <option value="2">Denfeld (4426 Grand Ave, Duluth, MN 55807)</option>
                </select>
            </td>
        </tr>
        <tr>
            <th class="text-center" style="text-align: center;" colspan="6">Include up to three additional members of your household</th>
        <tr>
            <th>First Name</th>
            <td colspan="1"><input type="text" class="medium" name="hhf[]" value="<?php echo $this->entries['houseHold'][0][0]; ?>" /></td>
            <th>Last Name</th>
            <td colspan="3"><input type="text" name="hhl[]" value="<?php echo $this->entries['houseHold'][0][1]; ?>" /></td>
        </tr>
        <tr>
            <th>First Name</th>
            <td colspan="1"><input type="text" class="medium" name="hhf[]" value="<?php echo $this->entries['houseHold'][1][0]; ?>" /></td>
            <th>Last Name</th>
            <td colspan="3"><input type="text" name="hhl[]" value="<?php echo $this->entries['houseHold'][1][1]; ?>" /></td>
        </tr>
        <tr>
            <th>First Name</th>
            <td colspan="1"><input type="text" class="medium" name="hhf[]" value="<?php echo $this->entries['houseHold'][2][0]; ?>" /></td>
            <th>Last Name</th>
            <td colspan="3"><input type="text" name="hhl[]" value="<?php echo $this->entries['houseHold'][2][1]; ?>" /></td>
        </tr>
        <tr>
            <th>
            <input type="image" src="https://www.paypal.com/en_US/i/btn/btn_xpressCheckout.gif" align="left" style="margin-right:7px;">
            <input type="hidden" name="submit" value="submit" />
            </th>
            <td>&nbsp;</td>
		</tr>
		</table>
		</form>
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
        Your new account is almost ready. Please click <i>Finalize Payment</i> to
        confirm your $<?php echo $this->entries['plan'] == 2 ? '100.00': '20.00'; ?>
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
        Thanks for joining. Your owner number is <?php echo $this->entries['card_no']; ?>.
        Your membership ID card and other information will be available for pickup
        within one business day.
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
			'fn'=>(isset($_REQUEST['fn'])?$_REQUEST['fn']:''),
			'ln'=>(isset($_REQUEST['ln'])?$_REQUEST['ln']:''),
			'addr1'=>(isset($_REQUEST['addr1'])?$_REQUEST['addr1']:''),
			'addr2'=>(isset($_REQUEST['addr2'])?$_REQUEST['addr2']:''),
			'city'=>(isset($_REQUEST['city'])?$_REQUEST['city']:''),
			'state'=>(isset($_REQUEST['state'])?$_REQUEST['state']:''),
			'zip'=>(isset($_REQUEST['zip'])?$_REQUEST['zip']:''),
			'ph'=>(isset($_REQUEST['ph'])?$_REQUEST['ph']:''),
			'plan'=>(isset($_REQUEST['plan'])?$_REQUEST['plan']:''),
			'store'=>(isset($_REQUEST['store'])?$_REQUEST['store']:''),
			'email'=>(isset($_REQUEST['email'])?$_REQUEST['email']:''),
			'passwd'=>(isset($_REQUEST['passwd'])?$_REQUEST['passwd']:''),
            'houseHold' => array(),
		);
        if (isset($_REQUEST['hhf']) && isset($_REQUEST['hhl'])) {
            $hhf = $_REQUEST['hhf'];
            $hhl = $_REQUEST['hhl'];
            for ($i=0; $i<count($hhf) && $i<3; $i++) {
                $this->entries['houseHold'][] = array($hhf[$i], $hhl[$i]);
            }
        }
        for ($i=0; $i<3; $i++) {
            if (!isset($this->entries['houseHold'][$i])) {
                $this->entries['houseHold'][$i] = array('', '');
            }
        }

		$dbc = Database::pDataConnect();
        $pay_class = RemoteProcessor::CURRENT_PROCESSOR;
        $proc = new $pay_class();
        if (isset($_REQUEST[$proc->postback_field_name])) {
            if (!isset($_SESSION['userInfo']) || !is_array($_SESSION['userInfo'])) {
                $this->msgs = '<div class="errorMsg">Sorry, your session has expired</div>';
                return true;
            }
            $this->entries = $_SESSION['userInfo'];
            $this->mode = 'confirm';
            $this->token = $_REQUEST[$proc->postback_field_name];
            return true;
        } elseif (isset($_REQUEST['_token']) && isset($_REQUEST['finalize'])) {
            if (!isset($_SESSION['userInfo']) || !is_array($_SESSION['userInfo'])) {
                $this->msgs = '<div class="errorMsg">Sorry, your session has expired</div>';
                return true;
            }
            $this->entries = $_SESSION['userInfo'];
            $this->token = $_REQUEST['_token'];
            $db = Database::pDataConnect();
            if ($this->entries['plan'] == 3) {
                $profileID = $proc->finalizeRecurringPayment($this->token, 'WFC Equity Payment Plan', 20);
            }
            $done = $proc->finalizePayment($this->token);

            if ($this->entries['plan'] == 3) {
                $prep = $db->prepare_statement('INSERT INTO PaymentProfiles (profileID, cardNo, email) VALUES (?, ?, ?)');
                $db->exec_statement($prep, array($profileID, $this->entries['card_no'], $this->entries['email']));
                $this->mode = 'receipt';
                return true;
            }

            unset($_SESSION['userInfo']);
            $this->mode = 'done';
            $prep = $db->prepare_statement('
                UPDATE custdata
                SET FirstName=?,
                    LastName=?
                WHERE CardNo=?');
            $db->exec_statement($prep, array($this->entries['fn'], $this->entries['ln'], $this->entries['card_no']));

            $created = AuthLogin::createLogin($this->entries['email'],
                $this->entries['passwd'],
                $this->entries['fn'] . ' ' . $this->entries['ln'],
                $this->entries['card_no']);
            AuthUtilities::doLogin($this->entries['email']);
            $empno = AuthUtilities::getUID($this->entries['email']);
            $final_amount = $this->entries['plan'] == 2 ? 100.00 : 20.00;
            TransRecord::addOpenRing(20.00, 992, 'Class A Equity');
            if ($final_amount == 100.00) {
                TransRecord::addOpenRing(80.00, 991, 'Class B Equity');
            }
            TransRecord::addtender($proc->tender_name, $proc->tender_code, -1*$final_amount);

            if (!RemoteProcessor::LIVE_MODE) {
                $testP = $db->prepare_statement('UPDATE localtemptrans SET register_no=99 WHERE emp_no=?');
                $testR = $db->exec_statement($testP, array($empno));
            }
            // rotate data
            $endP = $db->prepare_statement("INSERT INTO localtrans SELECT l.* FROM
                localtemptrans AS l WHERE emp_no=?");
            $endR = $db->exec_statement($endP,array($empno));

            $pendingCols = Database::localMatchingColumns($db, 'localtemptrans', 'pendingtrans');
            $endP = $db->prepare_statement("INSERT INTO pendingtrans ($pendingCols) 
                                            SELECT $pendingCols 
                                            FROM localtemptrans AS l 
                                            WHERE l.emp_no=?");

            $endR = $db->exec_statement($endP,array($empno));
            if ($endR !== false) {
                $clearP = $db->prepare_statement("DELETE FROM localtemptrans WHERE emp_no=?");
                $db->exec_statement($clearP,array($empno));
            }

            /**
              Send email notifications to customer, store staff.
              Latter should include all signup info, possibly in a
              click-to-apply JSON encoding.
            */
            Notices::joinNotification($this->entries);
            Notices::joinAdminNotification($this->entries);
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

			if ($_REQUEST['passwd'] !== $_REQUEST['passwd2']){
				$this->msgs .= '<div class="errorMsg">';
				$this->msgs .= 'Passwords do not match';
				$this->msgs .= '</div>';
				$this->entries['passwd'] = '';
				$errors = true;
			}

			if (empty($_REQUEST['passwd'])) {
				$this->msgs .= '<div class="errorMsg">';
				$this->msgs .= 'Password is required';
				$this->msgs .= '</div>';
				$this->entries['passwd'] = '';
				$errors = true;
			}

			if (empty($this->entries['fn'])) {
				$this->msgs .= '<div class="errorMsg">';
				$this->msgs .= 'First name is required';
				$this->msgs .= '</div>';
				$this->entries['fn'] = '';
				$errors = True;
			}

			if (empty($this->entries['ln'])) {
				$this->msgs .= '<div class="errorMsg">';
				$this->msgs .= 'Last name is required';
				$this->msgs .= '</div>';
				$this->entries['ln'] = '';
				$errors = True;
			}

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

			if ($this->entries['plan'] != 1 && $this->entries['plan'] != 2 && $this->entries['plan'] != 3) {
				$this->msgs .= '<div class="errorMsg">';
				$this->msgs .= 'Invalid payment choice.';
				$this->msgs .= '</div>';
				$errors = true;
			}

			if (!$errors) {

                $card_no = false;
                /**
                  We won't see traffic requiring this any time soon,
                  but this loop searches for an available member# with
                  some extra checks to prevent concurrent users from
                  claiming the same member#.
                */
                while ($card_no === false) {
                    $query = '
                        SELECT CardNo
                        FROM custdata
                        WHERE LastName=\'NEW WEB MEMBER\'
                            AND FirstName=\'\'';
                    $result = $dbc->query($query);
                    if ($dbc->numRows($result) == 0) {
                        // no accounts available
                        $this->msgs .= '<div class="errorMsg">';
                        $this->msgs .= 'The online sign-up system is down. Sorry for the inconvenience.';
                        $this->msgs .= '</div>';
                        return true;
                    }
                    $row = $dbc->fetchRow($result);
                    $reservation = uniqid('', true);
                    $query = '
                        UPDATE custdata
                        SET FirstName=?
                        WHERE CardNo=?';
                    $prep = $dbc->prepare_statement($query);
                    $res = $dbc->exec_statement($prep, array($reservation, $row['CardNo']));

                    $query = '
                        SELECT CardNo
                        FROM custdata
                        WHERE FirstName=?';
                    $prep = $dbc->prepare_statement($query);
                    $res = $dbc->exec_statement($prep, array($reservation));
                    if ($dbc->numRows($res) == 0) {
                        // failed to reserve that cardno. should be safe to try again
                        continue;
                    }

                    $row2 = $dbc->fetchRow($res);
                    if ($row['CardNo'] == $row2['CardNo']) {
                        // successfully claimed a card number
                        $card_no = $row['CardNo'];
                        $_SESSION['UUID'] = $reservation;
                        break;
                    } else {
                        // a concurrent user generated the same UUID?
                        // should be insanely rare. just try again.
                        continue;
                    }
                }
                $this->entries['card_no'] = $card_no;
                $_SESSION['userInfo'] = $this->entries;

                $amount = ($this->entries['plan'] == 2) ? '100.00' : '20.00';
                $PAYMENT_URL_SUCCESS = 'http://' . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];
                $PAYMENT_URL_FAILURE = 'http://' . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];
                if (substr($PAYMENT_URL_FAILURE, -9) == 'index.php') {
                    $PAYMENT_URL_FAILURE = substr($PAYMENT_URL_FAILURE, 0, strlen($PAYMENT_URL_FAILURE)-9);
                }
                $PAYMENT_URL_FAILURE .= 'cancel/';
                if ($this->entries['plan'] == 3) {
                    $init = $proc->startRecurringPayment(20, 'WFC Equity Payment Plan', '0.00', $this->entries['email']);
                } else {
                    $init = $proc->initializePayment($amount, '0.00', $this->entries['email']);
                }
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
    new JoinPage('Join the Co-op');
}

?>
