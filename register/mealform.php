<?php
$IS4C_PATH="";
if (!class_exists('PhpAutoLoader')) {
    require(dirname(__FILE__) . '/vendor-code/PhpAutoLoader/PhpAutoLoader.php');
}
include('ini.php');

class MealForm extends NoMenuPage {

	var $errors;
	var $data;
	var $choices;
	var $kchoices;

	function preprocess(){
		global $IS4C_LOCAL;

    /*
		if ($IS4C_LOCAL->get("memberID") == ''){
			header("Location: index.php");
			return False;
		}
*/

		$this->errors = "";
		$this->choices = array(
            1=>"King Salmon (Gluten-free)",
            2=>"Chicken Breast",
            3=>"Tempeh Stir Fry (Vegan & Gluten-free)",
        );
		$this->kchoices = array(1=>"Pasta");
		$this->data = array(
		'fnln'=>'',
		'email'=>'',
		'ph'=>'',
		'ng'=>0,
		'nc'=>0,
		'mc0'=>''
		);
		if (isset($_REQUEST['btn'])){
			$name = isset($_REQUEST['fnln'])?$_REQUEST['fnln']:'';
			$this->data['fnln'] = stripslashes($name);
			$email = isset($_REQUEST['email'])?$_REQUEST['email']:'';
			$this->data['email'] = stripslashes($email);
			$phone = isset($_REQUEST['ph'])?$_REQUEST['ph']:'';
			$this->data['ph'] = $phone;
			$guests = isset($_REQUEST['ng'])?((int)$_REQUEST['ng']):0;	
			if ($guests < 0) $guests=0;
			$this->data['ng'] = $guests;
			$kids = isset($_REQUEST['nc'])?((int)$_REQUEST['nc']):0;	
			if ($kids < 0) $kids=0;
			$this->data['nc'] = $kids;
			$meals = array();
			for($i=0;$i<count($_REQUEST['mc']);$i++){
				$choice = $_REQUEST['mc'][$i] != ""?((int)$_REQUEST['mc'][$i]):'';
				$meals[] = $choice;
				$this->data['mc'.$i] = $choice;
			}
			$kmeals = array();
			for ($i=0;$i<count($_REQUEST['kc']);$i++){
				$choice = $_REQUEST['kc'][$i] != ""?((int)$_REQUEST['kc'][$i]):'';
				$kmeals[] = $choice;
				$this->data['kc'.$i] = $choice;
			}
			for($i=1;$i<=$guests;$i++){
				if (!isset($meals[$i])) $meals[$i]='';
				if (!isset($this->data['mc'.$i])) $this->data['mc'.$i]='';
			}

			if (empty($name))
				$this->errors .= '<li>Full name is required</li>';
			if (empty($email))
				$this->errors .= '<li>Email address is required</li>';
			if (empty($phone))
				$this->errors .= '<li>Phone is required</li>';
			$mflag = False;
			foreach($meals as $m){
				if (empty($m)){
					$this->errors .= '<li>Meal choices cannot be blank</li>';
					$mflag = True;
					break;
				}
			}
			if (!$mflag){
				foreach($kmeals as $m){
					if (empty($m)){
						$this->errors .= '<li>Meal choices cannot be blank</li>';
						break;
					}
				}
			}
			if (empty($this->errors)) {
				$dbc = Database::pDataConnect();
				$regQ = $dbc->prepare_statement("INSERT INTO registrations (tdate,card_no,name,email,
					phone,guest_count,child_count,paid) VALUES (".$dbc->now().", ?, ?,
					?, ?, ?, ?,0)");
				$dbc->exec_statement($regQ, array($IS4C_LOCAL->get('memberID'), $name, $email,
								$phone, $guests, $kids));
				$mealsQ = $dbc->prepare_statement('INSERT INTO regMeals (card_no,type,subtype) VALUES (?,?,?)');
				$dbc->exec_statement($mealsQ, array($IS4C_LOCAL->get('memberID'),'OWNER',$meals[0]));
				for($i=1;$i<count($meals);$i++){
					$dbc->exec_statement($mealsQ, array($IS4C_LOCAL->get('memberID'),'GUEST',$meals[$i]));
				}
				for ($i=0;$i<$kids;$i++){
					$dbc->exec_statement($mealsQ, array($IS4C_LOCAL->get('memberID'),'CHILD',0));
				}
				header("Location: confirm.php");
				return False;
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
		?>
		<div id="grouper">

		<div id="formdiv" class="col-sm-6">
		<form action="mealform.php" method="get">
		<div class="form-group">
            Owner #<?php echo $IS4C_LOCAL->get("memberID"); ?>
        </div>
		<div class="form-group">
		    <label>First &amp; Last Name</label>
            <input type="text" name="fnln" value="<?php echo $this->data['fnln']; ?>" />
        </div>
		<div class="form-group">
            <label>Email Address</label>
            <input type="text" name="email" value="<?php echo $this->data['email']; ?>" />
        </div>
		<div class="form-group">
		    <label>Phone #</label>
            <input type="text" name="ph" value="<?php echo $this->data['ph']; ?>" />
        </div>
		<div class="form-group">
		    <label>Owner Meal Choice</label>
            <select name="mc[]"><option></option>
		<?php foreach($this->choices as $i=>$choice){
			printf('<option %s value="%d">%s</option>',
				($i==$this->data['mc0']?'selected':''),
				$i,$choice);
		}?></select>
        </div>
		<div class="form-group">
		    <label style="width:100%;">Additional Adults Attending</label>
            <input type="text" name="ng" size="3" value="<?php echo $this->data['ng']; ?>" />
        </div>
		<?php for($i=0;$i<$this->data['ng'];$i++){ 
            echo '<div class="form-group">';
			echo '<label>Guest Meal #'.($i+1).'</label>';
			echo '<td><select name="mc[]"><option></option>';	
			foreach($this->choices as $j=>$choice){
				printf('<option %s value="%d">%s</option>',
				($j==$this->data['mc'.($i+1)]?'selected':''),
				$j,$choice);
			}
			echo '</select></div>';
		}?>
		<div class="form-group">
		    <label>Children (12 and younger) Attending</label>
            <input type="text" name="nc" size="3" value="<?php echo $this->data['nc']; ?>" />
        </div>
		<?php for($i=0;$i<$this->data['nc'];$i++){ 
            echo '<div class="form-group">';
			echo '<label>Children\'s Meal #'.($i+1).'</label>';
			echo '<select name="kc[]"><option></option>';	
			foreach($this->kchoices as $j=>$choice){
				printf('<option %s value="%d">%s</option>',
				($j==$this->data['kc'.$i]?'selected':''),
				$j,$choice);
			}
			echo '</select></div>';
		}?>
		<?php printf('<div class="form-group">Amount due: $%.2f</div>',
			(20 + ($this->data['ng']*20) + $this->data['nc']*5));?>
		<div class="form-group">
            <input type="submit" name="btn" value="Continue" />
		</div>
		</form>	
		</div>

		<div id="menudiv" class="col-sm-5 text-center">
		<h2>Dinner Menu</h2>
A plated dinner including locally sourced foods with vegan, vegetarian, and gluten-free, options catered by the DECC. Beverage options include LOCAL beer and organic wine. <em>Choose one of the following</em><br />
		<img src="src/images/greyleaf.gif" alt="leaf" /><br />
Oven Broiled King Salmon topped with Béarnaise Sauce, served with Frisée and Apple Salad and herb roasted red potatoes (Entrée is Gluten Free).
		<br />
		<i>or</i>
		<br />
Boneless Breast of Chicken with a stuffing of Minnesota Wild Rice and Country Sausage topped
		<br />
		<i>or</i>
		<br />
Stir fried Tempeh and Fresh Vegetables over Saffron Rice (Entrée is Vegan and Gluten Free)
		<br />
		<br />
		<i>Children's Option</i>
		<br />
Pasta with Meatless Marinara Sauce served with garden vegetables. Gluten free noodles available upon request. <i>Ages 12 and under, please</i>.
		<br />
		<img src="src/images/greyleaf.gif" alt="leaf" /><br />
All meals served with locally baked dinner rolls (Gluten Free dinner rolls may be requested from your server)
        <br />
		<img src="src/images/greyleaf.gif" alt="leaf" /><br />
Dessert -  Death by Chocolate (vegan dessert option will be available upon request)
        <br />
		<img src="src/images/greyleaf.gif" alt="leaf" /><br />
Coffee & Water served, Milk or Tea upon request 
		<br /><br />
		If you have any additional questions about the menu, please contact <a href="mailto:awade@wholefoods.coop">awade@wholefoods.coop</a>.
		<br /><br />
		<div style="font-size:85%;font-style:italic">
		Owner and Guest meals $20/each.<br />
		Children's Plates $5/each.
		</div>
		</div>

		</div>
		<?php
	}
}

new MealForm('Annual Meeting Registration');

?>
