<?php
$IS4C_PATH="";
if (!class_exists('PhpAutoLoader')) {
    require(dirname(__FILE__) . '/vendor-code/PhpAutoLoader/PhpAutoLoader.php');
}
include('ini.php');

class CancelPage extends NoMenuPage {

	function main_content(){
		global $IS4C_LOCAL;
		$dbc = Database::pDataConnect();
		$args = array($IS4C_LOCAL->get('memberID'));
		$clearQ = $dbc->prepare_statement("DELETE FROM registrations WHERE
			card_no=?");
		$dbc->exec_statement($clearQ, $args);
		$clearQ = $dbc->prepare_statement("DELETE FROM regMeals WHERE
			card_no=?");
		$dbc->exec_statement($clearQ, $args);
		?>
		Your registration has been canceled.
		<a href="index.php">Click here</a> if you wish to
		start over.
		<?php
	}
}

new CancelPage('Annual Meeting Registration');

?>
