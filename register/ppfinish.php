<?php
$IS4C_PATH="";
if (!class_exists('PhpAutoLoader')) {
    require(dirname(__FILE__) . '/vendor-code/PhpAutoLoader/PhpAutoLoader.php');
}
include('ini.php');

class PaypalResultPage extends NoMenuPage {

	function preprocess(){
		global $IS4C_LOCAL;
		if (isset($_REQUEST['token'])){
			$dbc = Database::pDataConnect();
			$fetchQ = $dbc->prepare_statement("SELECT card_no FROM tokenCache
				WHERE token=?");
			$fetchR = $dbc->exec_statement($fetchQ, array($_REQUEST['token']));
			if ($dbc->num_rows($fetchR) > 0){
				$fetchW = $dbc->fetch_row($fetchR);
                $cn = $fetchW['card_no'];
				$IS4C_LOCAL->set("memberID",$cn);

				$clearQ = $dbc->prepare_statement("DELETE FROM tokenCache
					WHERE token=?");
				$clearR = $dbc->exec_statement($clearQ, array($_REQUEST['token']));
			}
			
			$pp1 = PayPal::GetExpressCheckoutDetails($_REQUEST['token']);
			$pp2 = PayPal::DoExpressCheckoutPayment($pp1['TOKEN'],
				$pp1['PAYERID'],
				$pp1['PAYMENTREQUEST_0_AMT']);

			if ($pp2['ACK'] == 'Success') {
				$q = $dbc->prepare_statement("UPDATE registrations SET paid=1
                                        WHERE card_no=?");
				$r = $dbc->exec_statement($q, array($IS4C_LOCAL->get('memberID')));
				header("Location: done.php");
				return False;
			}
		}
		return True;
	}

	function main_content(){
		?>
		There was an error processing your payment.
		<ul>
		<li><a href="confirm.php">Try Again</a></li>
		<li><a href="cancel.php">Cancel</a></li>
		</ul>
		<?php
	}
}

new PaypalResultPage('Annual Meeting Registration');

?>
