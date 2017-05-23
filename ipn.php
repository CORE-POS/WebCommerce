<?php

use PayPal\IPN\PPIPNMessage;

if (!class_exists('PhpAutoLoader')) {
    require(__DIR__ . '/vendor-code/PhpAutoLoader/PhpAutoLoader.php');
}

$ipn = new PPIPNMessage(null, array('mode' => 'live'));
$fp = fopen(__DIR__ . '/ipn.log', 'a');
if ($ipn->validate()) {
    fwrite($fp, date('r') . ": Valid IPN message\n"); 
    foreach ($ipn->getRawData() as $key => $val) {
        fwrite($fp, "\t{$key}: {$val}\n");
    }
} else {
    fwrite($fp, date('r') . ': Invalid IPN message' . "\n");
}
fclose($fp);

/**
  Run an equity transaction for the notified payment
*/
function addEquityPayment($profileID, $amount)
{
    $dbc = Database::pDataConnect();
    $prep = $dbc->prepare_statement('SELECT cardNo, email FROM PaymentProfiles WHERE profileID=?');
    $res = $dbc->exec_statement($prep, array($profileID));
    $row = $dbc->fetch_row($res);
    if (!$row) {
        return false;
    }
    $card_no = $row['cardNo'];
    $email = $row['email'];
    AuthUtilities::doLogin($email);
    $empno = AuthUtilities::getUID($email);
    TransRecord::addOpenRing($amount, 991, 'Class B Equity');
    $pay_class = RemoteProcessor::CURRENT_PROCESSOR;
    $proc = new $pay_class();
    TransRecord::addtender($proc->tender_name, $proc->tender_code, -1*$amount);

    $endP = $dbc->prepare_statement("INSERT INTO localtrans SELECT l.* FROM
        localtemptrans AS l WHERE emp_no=?");
    $endR = $dbc->exec_statement($endP,array($empno));

    $pendingCols = Database::localMatchingColumns($dbc, 'localtemptrans', 'pendingtrans');
    $endP = $dbc->prepare_statement("INSERT INTO pendingtrans ($pendingCols) 
                                    SELECT $pendingCols 
                                    FROM localtemptrans AS l 
                                    WHERE l.emp_no=?");

    $endR = $dbc->exec_statement($endP,array($empno));
    if ($endR !== false) {
        $clearP = $dbc->prepare_statement("DELETE FROM localtemptrans WHERE emp_no=?");
        $dbc->exec_statement($clearP,array($empno));
    }
}

