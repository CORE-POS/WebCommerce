<?php
$IS4C_PATH="";
if (!class_exists('PhpAutoLoader')) {
    require(dirname(__FILE__) . '/vendor-code/PhpAutoLoader/PhpAutoLoader.php');
}
include('ini.php');

class LoginPage extends NoMenuPage {

	var $errors;
	var $data;

	function preprocess(){
		global $IS4C_LOCAL;
		
		/* disabled 
		return True;
        */

		$this->data = array('ln'=>'','cardno'=>'');
		$this->errors = "";
		if (isset($_REQUEST['btn'])){
			$name = isset($_REQUEST['ln'])?$_REQUEST['ln']:'';
			$num = isset($_REQUEST['cardno'])?$_REQUEST['cardno']:0;

			$this->data['ln'] = $name;
			if ($num != 0) $this->data['cardno'] = $num;	

			$num = str_replace(' ','',$num);
			if (strlen($num)>=10){ // likely a card
				if ($num[0] == '2')  // add lead digit
					$num = '4'.$num;
				if (strlen($num) >= 12) // remove check digit
					$num = substr($num,0,11);
				$num = str_pad($num,13,'0',STR_PAD_LEFT);
			}

			if (empty($name))
				$this->errors .= '<li>Name is required</li>';
			if (empty($num))
				$this->errors .= '<li>Owner number or Owner Card number is required</li>';
			if (!empty($this->errors)) return True;

			$dbc = Database::pDataConnect();

			$query = $dbc->prepare_statement("
                SELECT c.CardNo FROM custdata AS c
				LEFT JOIN membercards AS m ON c.CardNo=m.card_no
				WHERE c.LastName=? AND 
				(c.CardNo=? OR m.upc=?) AND
				c.personNum=1
            ");

			$result = $dbc->exec_statement($query, array($name, $num, $num));
			if ($dbc->num_rows($result)==0)
				$this->errors .= "<li>Account not found</li>";			
			else if ($dbc->num_rows($result) > 1)
				$this->errors .= "<li>Account error</li>";			
			else if ($dbc->num_rows($result)==1){
                $w = $dbc->fetch_row($result);
                $cn = $w[0];
				$IS4C_LOCAL->set("memberID",$cn);

				$q2 = sprintf("SELECT paid FROM registrations
					WHERE card_no=%d",$cn);
				$r2 = $dbc->query($q2);
				if ($dbc->num_rows($r2)==0){
					header("Location: mealform.php");
					return False;
				}
				else {
					$w2 = $dbc->fetch_row($r2);
					if ($w2['paid']==1){
						$this->errors .= '<li>Account already registered for the meeting</li>';
					}
					else {
						// registration never completed; start over
						$cQ = $dbc->prepare_statement("DELETE FROM registrations WHERE card_no=?");
						$dbc->exec_statement($cQ, array($cn));
						$cQ = $dbc->prepare_statement("DELETE FROM regMeals WHERE card_no=?");
						$dbc->exec_statement($cQ, array($cn));
						header("Location: mealform.php");
						return False;
					}
				}
			}
		}
		return True;
	}

	function main_content()
    {
        echo '<h2>Registration has closed</h2>
            <br />
            <a href="faq/">Annual Meeting FAQ</a><br />
            <a href="parkingpass.pdf">Print Parking Pass</a>';
        return true;
		if (!empty($this->errors)){
			echo "<blockquote><i>";
			echo $this->errors;
			echo "</i></blockquote>";
		}
		?>
		<form action="index.php" method="get">
		<div class="form-group">
		    <label>Owner # or Owner Card #</label>
		    <input type="text" name="cardno" value="<?php echo $this->data['cardno']; ?>" />
        </div>
		<div class="form-group">
		    <label>Last Name</label>
		    <input type="text" name="ln" value="<?php echo stripslashes($this->data['ln']); ?>" />
        </div>
		<div class="form-group">
            <input type="submit" name="btn" value="Register for Annual Meeting" />
		</div>
		</form>	
		<p />
		<a href="faq/">Annual Meeting FAQ</a><br />
		<a href="parkingpass.pdf">Print Parking Pass</a>
		<?php
	}
}

new LoginPage('Annual Meeting Registration');

