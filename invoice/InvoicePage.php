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

class InvoicePage extends BasicPage 
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
        $webID = isset($_GET['webID']) ? $_GET['webID'] : '';
        if (empty($webID)) {
            echo '<p>Error: no invoice specified</p>';
            return;
        }
        $dbc = Database::pDataConnect();
        $prep = $dbc->prepare("SELECT * FROM B2BInvoices WHERE uuid=?");
        $res = $dbc->execute($prep, array($webID));
        if ($res == false || $dbc->numRows($res) == 0) {
            echo '<p>Error: invoice not found</p>';
            return;
        }
        $row = $dbc->fetchRow($res);
        $row['amount'] = sprintf('%.2f', $row['amount']);
        $row['customerNotes'] = nl2br($row['customerNotes']);
        $row['description'] = nl2br($row['description']);
        
        echo <<<HTML
        <style type="text/css">
        input.medium {
            width: 12em;
        }
        </style>
		<div id="loginTitle">
            <table class="table table-bordered">
                <tr>
                    <th>Invoice #</th><td>{$row['b2bInvoiceID']}</td>
                </tr>
                <tr>
                    <th>Account #</th><td>{$row['cardNo']}</td>
                </tr>
                <tr>
                    <th>Item</th><td>{$row['description']}</td>
                </tr>
                <tr>
                    <th>Amount</th><td>\${$row['amount']}</td>
                </tr>
                <tr>
                    <th>Date</th><td>{$row['createdDate']}</td>
                </tr>
                <tr>
                    <th>Notes</th><td>{$row['customerNotes']}</td>
                </tr>
            </table>
HTML;
        if ($row['isPaid']) {
            printf('<p>This invoice was paid %s</p>', $row['paidDate']);
        } else {
            printf('<form method="post" action="InvoicePage.php">
                <input type="hidden" name="webID" value="%s" />
                <input type="hidden" name="submit" value="1" />
                Pay Invoice Online<br />
                <input type="image" src="https://www.paypal.com/en_US/i/btn/btn_xpressCheckout.gif" />
                </form>', $webID);
        }
	}

    public function confirm_content()
    {
		global $IS4C_PATH;
        ?>
		<div id="loginTitle">
		<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
        <input type="hidden" name="_token" value="<?php echo $this->token; ?>" />
        <input type="hidden" name="webID" value="<?php echo $this->webID; ?>" />
        <p>
        Please click <i>Finalize Payment</i> to
        confirm your PayPal payment.
        </p>
        <label>Email</label>
        <input type="email" name="_email" placeholder="Optional; enter an address for a payment confirmation" />
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
        $dbc = Database::pDataConnect();
        $prep = $dbc->prepare("SELECT * FROM B2BInvoices WHERE uuid=?");
        $res = $dbc->execute($prep, array($this->webID));
        $row = $dbc->fetchRow($res);
        echo <<<HTML
		<div id="loginTitle">
            <p>
                Thank you. Print this for your records or refer to the
                emailed confirmation message.
            </p>
            <table class="table table-bordered">
                <tr>
                    <th>Invoice #</th><td>{$row['b2bInvoiceID']}</td>
                </tr>
                <tr>
                    <th>Account #</th><td>{$row['cardNo']}</td>
                </tr>
                <tr>
                    <th>Item</th><td>{$row['description']}</td>
                </tr>
                <tr>
                    <th>Amount</th><td>\${$row['amount']}</td>
                </tr>
                <tr>
                    <th>Date</th><td>{$row['createdDate']}</td>
                </tr>
                <tr>
                    <th>Notes</th><td>{$row['customerNotes']}</td>
                </tr>
                <tr>
                    <th>Paid</th><td>Yes</td>
                </tr>
                <tr>
                    <th>Payment Date</th><td>{$row['paidDate']}</td>
                </tr>
                <tr>
                    <th>Payment Method</th><td>PayPal</td>
                </tr>
            </table>
        </div>
HTML;
    }

	function preprocess()
    {
        global $IS4C_PATH,$PAYMENT_URL_SUCCESS, $PAYMENT_URL_FAILURE;
        if (session_id() === '') {
            session_start();
        }

		$dbc = Database::pDataConnect();
        $pay_class = RemoteProcessor::CURRENT_PROCESSOR;
        $proc = new $pay_class();
        if (isset($_REQUEST[$proc->postback_field_name])) {
            if (!isset($_SESSION['invoice'])) {
                $this->msgs = '<div class="errorMsg">Sorry, your session has expired</div>';
                return true;
            }
            $this->webID = $_SESSION['invoice'];
            $this->mode = 'confirm';
            $this->token = $_REQUEST[$proc->postback_field_name];
            return true;
        } elseif (isset($_REQUEST['_token']) && isset($_REQUEST['finalize'])) {
            if (!isset($_SESSION['invoice'])) {
                $this->msgs = '<div class="errorMsg">Sorry, your session has expired</div>';
                return true;
            }
            $this->webID = $_SESSION['invoice'];
            $this->token = $_REQUEST['_token'];
            $emailAddr = $_REQUEST['_email'];
            $dbc = Database::pDataConnect();
            $prep = $dbc->prepare("SELECT * FROM B2BInvoices WHERE uuid=?");
            $res = $dbc->execute($prep, array($this->webID));
            if ($res == false || $dbc->numRows($res) == 0) {
                $this->msgs = 'Error: invoice not found';
                return true;
            }
            $row = $dbc->fetchRow($res);
            $done = $proc->finalizePayment($this->token);

            $tNo = Database::getDTransNo(1001);
            TransRecord::addDTrans(1001, 30, $tNo, 1, array(
                'upc' => $row['amount'] . 'DP703',
                'description' => substr($row['description'], 0, 30),
                'trans_type' => 'D',
                'department' => 703,
                'quantity' => 1,
                'regPrice' => $row['amount'],
                'total' => $row['amount'],
                'unitPrice' => $row['amount'],
                'ItemQtty' => 1,
                'card_no' => $row['cardNo'],
                'charflag' => 'B2',
                'numflag' => $row['b2bInvoiceID'],
            ));
            TransRecord::addDTrans(1001, 30, $tNo, 2, array(
                'description' => 'Pay Pal',
                'trans_type' => 'T',
                'trans_subtype' => 'PP',
                'total' => -1*$row['amount'],
                'card_no' => $row['cardNo'],
            ));

            $paidP = $dbc->prepare("
                UPDATE B2BInvoices SET isPaid=1,
                    paidDate=NOW()
                WHERE b2bInvoiceID=?");
            $dbc->execute($paidP, array($row['b2bInvoiceID']));

            if ($emailAddr && filter_var($emailAddr, FILTER_VALIDATE_EMAIL)) {
                Notices::invoiceNotification($emailAddr, $row);
            }
            $this->mode = 'receipt';

		} elseif (isset($_POST['submit'])) {
			// validate
			$errors = false;
            if (!isset($_POST['webID'])) {
                $this->msgs = 'Internal error. Sorry';
                return true;
            }
            $webID = $_POST['webID'];
            $prep = $dbc->prepare("SELECT * FROM B2BInvoices WHERE uuid=?");
            $res = $dbc->execute($prep, array($webID));
            if ($res == false || $dbc->numRows($res) == 0) {
                $this->msgs = 'Error: invoice not found';
                return true;
            }
            $row = $dbc->fetchRow($res);
            $_SESSION['invoice'] = $webID;

            $amount = $row['amount'];
            $PAYMENT_URL_SUCCESS = 'http://store.wholefoods.coop/invoice/InvoicePage.php';
            $PAYMENT_URL_FAILURE = 'http://store.wholefoods.coop/invoice/cancel/';
            $init = $proc->initializePayment($amount, '0.00', '');
            if ($init === false) {
                $this->msgs .= 'Error: cannot process payment at this time.';
                return true;
            } else {
                $proc->redirectToProcess($init);
                return false;
            }
        }

		return true;
	}
}

if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
    new InvoicePage();
}

