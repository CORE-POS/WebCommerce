<?php
$IS4C_PATH="";
if (!class_exists('PhpAutoLoader')) {
    require(dirname(__FILE__) . '/vendor-code/PhpAutoLoader/PhpAutoLoader.php');
}
include('ini.php');

class DonePage extends NoMenuPage {

	var $choices;
	
	function main_content(){
		global $IS4C_LOCAL;
		$this->choices = array(
            0=>"Children's Plate",
            1=>"Pork",
            2=>"Ratatouille",
            3=>"Pork (gluten-free)",
            4=>"Ratatouille (gluten-free)",
        );
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
		?>
		Thank you for registering for the Annual Meeting.<br />
		<?php
		$msg = "Owner #".$IS4C_LOCAL->get("memberID")."\n";
		$msg .= $regW['name']."\n";
		$msg .= "Email: ".$regW['email']."\n";
		$msg .= "Phone: ".$regW['phone']."\n";
		$msg .= "Guests: ".$regW['guest_count']."\n";
		$msg .= "Children: ".$regW['child_count']."\n";
		$msg .= "\n";
		$msg .= "Meals selected\n";
		foreach ($meals as $type=>$count)
			$msg .= $count."x ".$type."\n";
		$msg .= "\n";
		$msg .= "Download a parking pass from\n";
		$msg .= "<a href=\"http://store.wholefoods.coop/register/parkingpass.pdf\">http://store.wholefoods.coop/register/parkingpass.pdf</a>\n";

		echo '<blockquote style="border:solid 1px black;padding:5px;margin:10px;">';
		echo str_replace("\n","<br />",$msg);
		echo "</blockquote>";

		echo "A copy of this confirmation will also be emailed. Please print either this page or the email and bring the confirmation to the meeting.";

		echo '<br /><br />';

		echo '<input type="submit" value="Print this Confirmation Page" onclick="window.print();return false;" />';

		$msg = "Thank you for registering for the Annual Meeting\n\nPlease print this message and bring to the meeting.\n\n".$msg;

		$to = $regW['email'];
		$subject = "WFC Registration Confirmation";
		$headers = "From: no-reply@wholefoods.coop\r\n";
		$headers .= "Reply-to: info@wholefoods.coop\r\n";
		$headers .= 'X-Mailer: PHP/' . phpversion()."\r\n";

		mail($to,$subject,$msg,$headers);
		mail("registrations@wholefoods.coop",$subject,$msg,$headers);
	}
}

new DonePage('Annual Meeting Registration');

?>
