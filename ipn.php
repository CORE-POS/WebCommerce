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
    $config = json_decode(file_get_contents(__DIR__ . '/join/config.json'), true);
    $plans = $config['paymentOptions']['recurring'];
    $planInfo = $plans[0]; // todo - save in payment profiles
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
    $tID = 1;
    $total = 0;
    foreach ($planInfo['recurRings'] as $r) {
        TransRecord::addDTrans(1001, 50, $tNo, $tID, array(
            'upc' => $r['amount'] . 'DP' . $r['deptID'],
            'description' => $r['name'],
            'trans_type' => 'D',
            'department' => $r['deptID'],
            'quantity' => 1,
            'regPrice' => $r['amount'],
            'total' => $r['amount'],
            'unitPrice' => $r['amount'],
            'ItemQtty' => 1,
            'card_no' => $card_no,
        ));
        $tID++;
        $total += $r['amount'];
    }
    TransRecord::addDTrans(1001, 50, $tNo, $tID, array(
        'description' => 'Pay Pal',
        'trans_type' => 'T',
        'trans_subtype' => 'PP',
        'total' => -1*$total,
        'card_no' => $card_no,
    ));
    fwrite($fp, "Finished payment for {$profileID}");
    fclose($fp);
}

