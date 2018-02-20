<?php

use PayPal\IPN\PPIPNMessage;

if (!class_exists('PhpAutoLoader')) {
    require(__DIR__ . '/vendor-code/PhpAutoLoader/PhpAutoLoader.php');
}
$IS4C_PATH = __DIR__ . '/';
include(__DIR__ . '/ini.php');

$ipn = new PPIPNMessage(null, array('mode' => 'live'));
$fp = fopen(__DIR__ . '/ipn.log', 'a');
$payment = array(
    'recurring_payment_id' => '',
    'product_name' => '',
    'amount' => '',
    'payment_status' => '',
    'txn_type' => '',
);
if ($ipn->validate()) {
    fwrite($fp, date('r') . ": Valid IPN message\n"); 
    foreach ($ipn->getRawData() as $key => $val) {
        if (isset($payment[$key])) {
            $payment[$key] = $val;
        }
        fwrite($fp, "\t{$key}: {$val}\n");
    }
} else {
    fwrite($fp, date('r') . ': Invalid IPN message' . "\n");
}
fclose($fp);

if ($payment['txn_type'] == 'recurring_payment' && $payment['payment_status'] == 'Completed' && $payment['amount']) {
    addEquityPayment($payment['recurring_payment_id'], $payment['amount']);
}

/**
  Run an equity transaction for the notified payment
*/
function addEquityPayment($profileID, $amount)
{
    $fp = fopen(__DIR__ . '/ipn.log', 'a');
    fwrite($fp, "Adding payment for {$profileID}\n");
    $dbc = Database::pDataConnect();
    $prep = $dbc->prepare_statement('SELECT cardNo, email FROM PaymentProfiles WHERE profileID=?');
    $res = $dbc->exec_statement($prep, array($profileID));
    $row = $dbc->fetch_row($res);
    if (!$row) {
        fwrite($fp, "Could not find profile {$profileID}");
        fclose($fp);
        return false;
    }
    $card_no = $row['cardNo'];
    $tNo = Database::getDTransNo(1001);
    TransRecord::addDTrans(1001, 50, $tNo, 1, array(
        'upc' => $amount . 'DP991',
        'description' => 'Class B Equity',
        'trans_type' => 'D',
        'department' => 991,
        'quantity' => 1,
        'regPrice' => $amount,
        'total' => $amount,
        'unitPrice' => $amount,
        'ItemQtty' => 1,
        'card_no' => $card_no,
    ));
    TransRecord::addDTrans(1001, 50, $tNo, 2, array(
        'description' => 'Pay Pal',
        'trans_type' => 'T',
        'trans_subtype' => 'PP',
        'total' => -1*$amount,
        'card_no' => $card_no,
    ));
    fwrite($fp, "Finished payment for {$profileID}");
    fclose($fp);
}

