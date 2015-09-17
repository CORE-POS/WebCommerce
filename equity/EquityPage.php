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

class EquityPage extends BasicPage 
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

        $email = AuthLogin::checkLogin();
        $errors = false;
        if ($email == false) {
            $this->msgs .= '<div class="errorMsg">';
            $this->msgs .= 'Please <a href="../gui-modules/LoginPage.php">Log in</a> to make a payment.';
            $this->msgs .= '</div>';
            $errors = true;
        }

        $card_no = AuthUtilities::getOwner($email);
        if ($email && ($owner === false || $owner == 0 || $owner == 9)) {
            $this->msgs .= '<div class="errorMsg">';
            $this->msgs .= 'Your online account is not currently linked to a WFC ownership.
                <a href="../gui-modules/manageAccount.php">Click Here</a> to fix this.';
            $this->msgs .= '</div>';
            $errors = true;
        }
		echo $this->msgs;
        if ($errors) { // do not display form if account has errors.
            return true;
        }
		?>
		<div id="loginTitle">
        <h2>Make an Equity Payment</h2>
        <p class="text-left">
        </p>
		<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
        <label>Choose payment amount</label><br />
        <label>
            <input type="radio" name="amt" value="5"> $5.00
        </label><br />
        <label>
            <input type="radio" name="amt" value="20"> $20.00
        </label><br />
        <label>
            <input type="radio" name="amt" value="80"> $80.00
        </label><br />
        <label class="form-inline">
            <input type="radio" name="amt" value="other"> Other:
            <div class="input-group">
                <span class="input-group-addon">$</span>
                <input type="number" name="other" min="5" max="80" step="1" class="form-control" />
            </div>
        </label><br />
        <button type="submit" name="submit" value="1">Make Payment</button>
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
        Your transaction is almost complete. Please click <i>Finalize Payment</i> to
        confirm your $<?php echo $_SESSION['equityAmount']; ?>
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
        Thanks! 
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
			'amt'=>(isset($_REQUEST['passwd'])?$_REQUEST['passwd']:''),
		);

		$dbc = Database::pDataConnect();
        $pay_class = RemoteProcessor::CURRENT_PROCESSOR;
        $proc = new $pay_class();
        if (isset($_REQUEST[$proc->postback_field_name])) {
            $this->mode = 'confirm';
            $this->token = $_REQUEST[$proc->postback_field_name];
            return true;
        } elseif (isset($_REQUEST['_token']) && isset($_REQUEST['finalize'])) {
            $done = $proc->finalizePayment($_REQUEST['_token']);
            $this->mode = 'done';
            $db = Database::pDataConnect();

            $email = AuthLogin::checkLogin();
            $empno = AuthUtilities::getUID($email);
            $final_amount = $_SESSION['equityAmount'];
            TransRecord::addOpenRing($final_amount, 991, 'Class B Equity');
            TransRecord::addtender($proc->tender_name, $proc->tender_code, -1*$final_amount);
            unset($_SESSION['equityAmount']);

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
            Notices::joinNotification($this->entries);
            Notices::joinAdminNotification($this->entries);
            */
            $this->mode = 'receipt';

		} elseif (isset($_REQUEST['submit'])) {
			// validate
			$errors = false;

            $amt = $_REQUEST['amt'];
            if (!is_numeric($amt) && $amt != 'other') {
				$this->msgs .= '<div class="errorMsg">';
				$this->msgs .= 'Invalid amount. Minimum payment is $5; maximum is $80.';
				$this->msgs .= '</div>';
				$errors = true;
            } elseif ($amt == 'other') {
                $amt = $_REQUEST['other'];
            }

            if ($amt < 5 || $amt > 80) {
				$this->msgs .= '<div class="errorMsg">';
				$this->msgs .= 'Invalid amount. Minimum payment is $5; maximum is $80.';
				$this->msgs .= '</div>';
				$errors = true;
            }

			if (!$errors) {

                $email = AuthLogin::checkLogin();
                $amount = sprintf('%.2f', $amt);
                $PAYMENT_URL_SUCCESS = 'http://' . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];
                $PAYMENT_URL_FAILURE = 'http://' . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];
                if (substr($PAYMENT_URL_FAILURE, -9) == 'index.php') {
                    $PAYMENT_URL_FAILURE = substr($PAYMENT_URL_FAILURE, 0, strlen($PAYMENT_URL_FAILURE)-9);
                }
                $PAYMENT_URL_FAILURE .= 'cancel/';
                $_SESSION['equityAmount'] = $amount;
                $init = $proc->initializePayment($amount, '0.00', $email);
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
    new EquityPage();
}

?>
