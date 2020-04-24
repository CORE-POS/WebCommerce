<?php

if (!class_exists('PhpAutoLoader')) {
    require(dirname(__FILE__) . '/../vendor-code/PhpAutoLoader/PhpAutoLoader.php');
}

if (isset($_REQUEST['notes'])) {
    $userID = AuthUtilities::getUID(AuthLogin::checkLogin());
    if ($userID) {
		$dbc = Database::tDataConnect();
        $prep = $dbc->prepare_statement("SELECT userID FROM CurrentOrderNotes WHERE userID=?");
        $res = $dbc->exec_statement($prep, array($userID));
        if ($dbc->num_rows($res) == 0) {
            $insP = $dbc->prepare_statement("INSERT INTO CurrentOrderNotes VALUES (?, ?)");
            $dbc->exec_statement($insP, array($userID, $_REQUEST['notes']));
        } else {
            $upP = $dbc->prepare_statement("UPDATE CurrentOrderNotes SET notes=? WHERE userID=?");
            $dbc->exec_statement($upP, array($_REQUEST['notes'], $userID));
        }
    }
}
