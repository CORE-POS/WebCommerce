<?php
$IS4C_PATH="";
if (!class_exists('PhpAutoLoader')) {
    require(dirname(__FILE__) . '/vendor-code/PhpAutoLoader/PhpAutoLoader.php');
}
include('ini.php');

class ConfirmPage extends NoMenuPage {

	var $errors;
	var $data;
	var $choices;

	function preprocess(){
		global $IS4C_LOCAL,$IS4C_PATH,$PAYPAL_URL_SUCCESS,$PAYPAL_URL_FAILURE;

		if ($IS4C_LOCAL->get("memberID") == ''){
			header("Location: index.php");
			return False;
		}

		$this->errors = "";
		$this->choices = array(
            0=>"Children's Plate",
            1=>"King Salmon (Gluten-free)",
            2=>"Chicken Breast",
            3=>"Tempeh Stir Fry (Vegan & Gluten-free)",
        );
		$this->data = array(
		'fnln'=>'',
		'email'=>'',
		'ph'=>'',
		'ng'=>0,
		'nc'=>0,
		'mc0'=>''
		);
		$dbc = Database::pDataConnect();
		$args = array($IS4C_LOCAL->get('memberID'));
		if (isset($_REQUEST['backbtn'])){
			$clearQ = $dbc->prepare_statement("DELETE FROM registrations WHERE
				card_no=?");
			$dbc->exec_statement($clearQ, $args);
			$clearQ = $dbc->prepare_statement("DELETE FROM regMeals WHERE
				card_no=?");
			$dbc->exec_statement($clearQ, $args);
			header("Location: mealform.php");
			return False;
		}
		else if (isset($_REQUEST['gobtn'])){
			$amtQ = sprintf("SELECT guest_count,child_count,email FROM
				registrations WHERE card_no=%d",
				$IS4C_LOCAL->get("memberID"));
			$amtQ = $dbc->prepare_statement("SELECT guest_count,child_count,email FROM
				registrations WHERE card_no=?");
			$amtR = $dbc->exec_statement($amtQ, $args);
			$amtW = $dbc->fetch_row($amtR);
			$amt = 20 + (20*$amtW['guest_count'])+(5*$amtW['child_count']);
			if ($amt == 0){
				$q = $dbc->prepare_statement("UPDATE registrations SET paid=1
                                        WHERE card_no=?");
				$r = $dbc->exec_statement($q, $args);
				header("Location: done.php");
				return False;
			}
			else {
				
				$PAYPAL_URL_SUCCESS = "http://store.wholefoods.coop/register/ppfinish.php";
				$PAYPAL_URL_FAILURE = "http://store.wholefoods.coop/register/cancel.php";
				$ppinit = PayPalMod::SetExpressCheckout($amt,0,$amtW['email']);
				if ($ppinit) {
					// cache member ID by paypal token in case
					// session expires
					$q = $dbc->prepare_statement("INSERT INTO tokenCache (card_no,token,tdate)
						VALUES (?,?,".$dbc->now().")");
					$r = $dbc->exec_statement($q, array($IS4C_LOCAL->get('memberID'), $ppinit));
					return False;
				}
				else
					$this->errors .= '<li>Paypal is not responding right now. Cannot complete registration.</li>';
			}
		}
		return True;
	}

	function main_content(){
		global $IS4C_LOCAL;
		if (!empty($this->errors)){
			echo "<blockquote><i>";
			echo $this->errors;
			echo "</i></blockquote>";
		}
		$dbc = Database::pDataConnect();
		$regQ = $dbc->prepare_statement("SELECT name,email,phone,guest_count,child_count FROM
			registrations WHERE card_no=?");
		$args = array($IS4C_LOCAL->get("memberID"));
		$regR = $dbc->exec_statement($regQ, $args);
		$regW = $dbc->fetch_row($regR);
		$mealQ = $dbc->prepare_statement("SELECT subtype,count(*) FROM regMeals WHERE 
			card_no=? GROUP BY subtype");
		$mealR = $dbc->exec_statement($mealQ, $args);
		$meals = array();
		while($mealW = $dbc->fetch_row($mealR))
			$meals[$this->choices[$mealW['subtype']]] = $mealW[1];
		$due = 20 + ($regW['guest_count']*20) + ($regW['child_count']*5);
		$btn = ($due > 0) ? "Confirm &amp; Pay" : "Confirm";
		?>
		<form action="confirm.php" method="get">
		<table class="table table-bordered">
		<tr><th colspan="2" align="right">Owner #<?php echo $IS4C_LOCAL->get("memberID"); ?></th>
		<tr><th align="right">Full Name</th>
		<td><?php echo $regW['name']; ?></td></tr>
		<tr><th align="right">Email Address</th>
		<td><?php echo $regW['email']; ?></td></tr>
		<tr><th align="right">Phone #</th>
		<td><?php echo $regW['phone']; ?></td></tr>
		<tr><th align="center" colspan="2">Meals</th></tr>
		<?php foreach($meals as $type=>$count){
			printf('<tr><th align="right">%s</th><td>%d</td></tr>',
				$type,$count);
		}?>
		<tr><th align="right" colspan="2">
		<?php printf('Amount due: $%.2f',$due); ?></th></tr>
		<tr><td align="left">
		<input type="submit" name="backbtn" value="Go Back" />
		</td><td align="right">
		<input type="submit" name="gobtn" value="<?php echo $btn; ?>" /></td></tr>
		</table>
		</form>	
		<?php
	}
}

new ConfirmPage('Annual Meeting Registration');

?>
