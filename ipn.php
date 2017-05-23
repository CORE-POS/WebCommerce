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
        fwrite($fp, "\t{$key}: {$value}\n");
    }
} else {
    fwrite($fp, date('r') . ': Invalid IPN message' . "\n");
}
fclose($fp);

