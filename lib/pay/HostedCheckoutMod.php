<?php
/*******************************************************************************

    Copyright 2014 Whole Foods Co-op

    This file is part of IS4C.

    IS4C is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    IS4C is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IS4C; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/
 // session_start();
 
$IS4C_PATH = isset($IS4C_PATH)?$IS4C_PATH:"";
if (empty($IS4C_PATH)){ while(!file_exists($IS4C_PATH."is4c.css")) $IS4C_PATH .= "../"; }

global $PAYMENT_URL_SUCCESS, $PAYMENT_URL_FAILURE;
include_once(dirname(__FILE__) . "/../pp-api-credentials.php");

class HostedCheckoutMod extends RemoteProcessor
{

    const TEST_GATEWAY = 'https://hc.mercurydev.net/hcws/HCService.asmx?WSDL';
    const LIVE_GATEWAY = 'https://hc.mercurypay.com/hcws/HCService.asmx?WSDL';
    const TEST_CHECKOUT = 'https://hc.mercurydev.net/Checkout.aspx';
    const LIVE_CHECKOUT = 'https://hc.mercurypay.com/Checkout.aspx';
    const LIVE_MOD_GATEWAY = 'https://hc.mercurypay.com/tws/transactionservice.asmx';
    const TEST_MOD_GATEWAY = 'https://hc.mercurydev.net/tws/transactionservice.asmx';

    public $tender_name = 'Credit Card';
    public $tender_code = 'CC';
    public $postback_field_name = 'PaymentID';

    public $cancelable = false;

    /**
      Start payment process. Usually involves a request to the
      processor entity.
      @param $amount [numeric] total payment amount
      @param $tax [numeric] tax amount (optional)
      @param $email [string] user email address (optional)
      @return [string] payment identifier or [boolean] false
    */
    public function initializePayment($amount, $tax=0, $email="")
    {
        global $PAYMENT_URL_SUCCESS, $PAYMENT_URL_FAILURE;
        $uid = AuthUtilities::getUID(AuthLogin::checkLogin());
        $invoice = str_pad($uid, 6, '0', STR_PAD_LEFT) . time();
        $param = array(
            'MerchantID' => RemoteProcessor::LIVE_MODE ?  HOSTED_CHECKOUT_LIVE_MERCH_ID : HOSTED_CHECKOUT_TEST_MERCH_ID,
            'LaneID' => '01',
            'Password' => RemoteProcessor::LIVE_MODE ? HOSTED_CHECKOUT_LIVE_PASSWORD : HOSTED_CHECKOUT_TEST_PASSWORD,
            'TotalAmount' => sprintf('%.2f', $amount),
            'TaxAmount' => sprintf('%.2f', $tax),
            'TranType' => 'Sale',
            'Frequency' => 'OneTime',
            'Memo' => 'CORE Web',
            'Invoice' => $invoice,
            'ProcessCompleteUrl' => $PAYMENT_URL_SUCCESS,
            'ReturnUrl' => $PAYMENT_URL_FAILURE,
            'PartialAuth' => 'off',
            'AVSFields' => 'Both',
        );
        $gateway = RemoteProcessor::LIVE_MODE ? self::LIVE_GATEWAY : self::TEST_GATEWAY;

        $client = new SoapClient($gateway);
        try {
            $resp = $client->InitializePayment(array('request' => $param));
            if ($resp->InitializePaymentResult->ResponseCode == '0') {
                return $resp->InitializePaymentResult->PaymentID;
            } else {
                return false;
            }
        } catch (Exception $ex){
            return false;
        }

        return false;
    }

    /**
      Send user to the payment processor's site
      @param $identifier [string] payment identifier
    */
    public function redirectToProcess($identifier)
    {
        echo '
            <html>
                <head></head>
                <body onload="document.hcForm.submit();">
                <form name="hcForm" method="post"
                    action="' . (RemoteProcessor::LIVE_MODE ? self::LIVE_CHECKOUT : self::TEST_CHECKOUT) . '">
                <input type="hidden" name="PaymentID" value="' . $identifier . '" />
                <input type="hidden" name="ReturnMethod" value="GET" />
                </form>
                </body>
            </html>';
    }

    /**
      Undo the payment
      @param $identifier [string] payment identifier
      @return [boolean] success or failure

      Reverse a previous transaction.  
    */
    public function undoPayment($identifier)
    {
        $param = array(
            'MerchantID' => RemoteProcessor::LIVE_MODE ?  HOSTED_CHECKOUT_LIVE_MERCH_ID : HOSTED_CHECKOUT_TEST_MERCH_ID,
            'PaymentID' => $identifier,
            'Password' => RemoteProcessor::LIVE_MODE ? HOSTED_CHECKOUT_LIVE_PASSWORD : HOSTED_CHECKOUT_TEST_PASSWORD,
        );
        
        $gateway = RemoteProcessor::LIVE_MODE ? self::LIVE_GATEWAY : self::TEST_GATEWAY;

        $client = new SoapClient($gateway);
        try {
            $resp = $client->VerifyPayment(array('request' => $param));
            if ($resp->VerifyPaymentResult->ResponseCode == '0') {
                $password = $param['Password'];
                unset($param['Password']);
                unset($param['PaymentID']);
                $param['Frequency'] = 'OneTime';
                $param['Memo'] = 'CORE Web';
                $uid = AuthUtilities::getUID(AuthLogin::checkLogin());
                $invoice = str_pad($uid, 6, '0', STR_PAD_LEFT) . time();
                $param['Invoice'] = $invoice;
                $param['AuthCode'] = $resp->VerifyPaymentResult->AuthCode;
                $param['PurchaseAmount'] = $resp->VerifyPaymentResult->Amount;
                $param['RefNo'] = $resp->VerifyPaymentResult->RefNo;
                $param['Token'] = $resp->VerifyPaymentResult->Token;
                $param['AcqRefData'] = $resp->VerifyPaymentResult->AcqRefData;
                $param['ProcessData'] = $resp->VerifyPaymentResult->ProcessData;

                $client = new SoapClient(RemoteProcessor::LIVE ? self::LIVE_MOD_GATEWAY : self::TEST_MOD_GATEWAY);
                $resp = $client->CreditReversalToken(array('request'=>$param, 'password'=>$password));
                if ($resp->CreditReversalTokenResult->Status == 'Approved') {
                    return true;
                } else {
                    return false;
                }

            } else {
                // transaction didn't approve. 
                // no need to reverse it
                return true;
            }
        } catch (Exception $ex) {
            return false;
        }

        return false;
    }

    /**
      Finalize the payment
      @param $identifier [string] payment identifier
      @return [boolean] success or failure
    */
    public function finalizePayment($identifier) 
    {
        $param = array(
            'MerchantID' => RemoteProcessor::LIVE_MODE ?  HOSTED_CHECKOUT_LIVE_MERCH_ID : HOSTED_CHECKOUT_TEST_MERCH_ID,
            'PaymentID' => $identifier,
            'Password' => RemoteProcessor::LIVE_MODE ? HOSTED_CHECKOUT_LIVE_PASSWORD : HOSTED_CHECKOUT_TEST_PASSWORD,
        );
        $gateway = RemoteProcessor::LIVE_MODE ? self::LIVE_GATEWAY : self::TEST_GATEWAY;

        $client = new SoapClient($gateway);
        try {
            $resp = $client->VerifyPayment(array('request' => $param));
            if ($resp->VerifyPaymentResult->ResponseCode == '0') {
                return true;
            } else {
                return false;
            }
        } catch (Exception $ex) {
            return false;
        }

        return false;
    }

    public function checkoutButton() 
    {
		return '<button type="submit" name="checkoutButton">
                    Checkout
                </button>';
    }
}

