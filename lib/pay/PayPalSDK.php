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

use PayPal\CoreComponentTypes\BasicAmountType;
use PayPal\EBLBaseComponents\AddressType;
use PayPal\EBLBaseComponents\BillingAgreementDetailsType;
use PayPal\EBLBaseComponents\PaymentDetailsItemType;
use PayPal\EBLBaseComponents\PaymentDetailsType;
use PayPal\EBLBaseComponents\SetExpressCheckoutRequestDetailsType;
use PayPal\EBLBaseComponents\DoExpressCheckoutPaymentRequestDetailsType;
use PayPal\EBLBaseComponents\RecurringPaymentsProfileDetailsType;
use PayPal\EBLBaseComponents\ActivationDetailsType;
use PayPal\EBLBaseComponents\ScheduleDetailsType;
use PayPal\PayPalAPI\SetExpressCheckoutReq;
use PayPal\PayPalAPI\SetExpressCheckoutRequestType;
use PayPal\PayPalAPI\DoExpressCheckoutPaymentReq;
use PayPal\PayPalAPI\DoExpressCheckoutPaymentRequestType;
use PayPal\PayPalAPI\GetExpressCheckoutDetailsReq;
use PayPal\PayPalAPI\GetExpressCheckoutDetailsRequestType;
use PayPal\PayPalAPI\CreateRecurringPaymentsProfileReq;
use PayPal\PayPalAPI\CreateRecurringPaymentsProfileRequestType;
use PayPal\Service\PayPalAPIInterfaceServiceService;
 
$IS4C_PATH = isset($IS4C_PATH)?$IS4C_PATH:"";
if (empty($IS4C_PATH)){ while(!file_exists($IS4C_PATH."is4c.css")) $IS4C_PATH .= "../"; }

global $PAYMENT_URL_SUCCESS, $PAYMENT_URL_FAILURE;
include_once(dirname(__FILE__) . "/../pp-api-credentials.php");

class PayPalSDK extends RemoteProcessor
{
    public $tender_name = 'Pay Pal';
    public $tender_code = 'PP';
    public $postback_field_name = 'token';

    public $cancelable = true;

    private $PAYPAL_LIVE_URL       = 'https://api-3t.paypal.com/nvp';
    private $PAYPAL_LIVE_RD        = 'https://www.paypal.com/webscr';

    private function getConfig()
    {
        $live = RemoteProcessor::LIVE_MODE;
        $config = array(
            'mode' => $live ? 'live' : 'sandbox',
            'acct1.UserName' => $live ? PAYPAL_LIVE_UID : PAYPAL_TEST_UID,
            'acct1.Password' => $live ? PAYPAL_LIVE_PWD : PAYPAL_TEST_PWD,
            'acct1.Signature' => $live ? PAYPAL_LIVE_KEY : PAYPAL_TEST_KEY,
        );
    
        return $config;
    }

    public function recurringPayment($amount, $description, $tax=0, $email='')
    {
        $billingAgreement = new BillingAgreementDetailsType('RecurringPayments');
        $billingAgreement->BillingAgreementDescription = $description;
        $token = $this->initializePayment($amount, $tax, $email, $billingAgreement);
        if ($token === false) {
            return false;
        }

        // all of this may need to happen AFTER returning from
        // PayPal on the initial payment

        $currencyCode = 'USD';
        $RPProfileDetails = new RecurringPaymentsProfileDetailsType();
        $RPProfileDetails->BillingStartDate = date('Y-m-d');

        $activationDetails = new ActivationDetailsType();
        $activationDetails->InitialAmount = new BasicAmountType($currencyCode, $amount);
        $activationDetails->FailedInitialAmountAction = 'CancelOnFailure';

        $paymentBillingPeriod =  new BillingPeriodDetailsType();
        $paymentBillingPeriod->BillingFrequency = 4;
        $paymentBillingPeriod->BillingPeriod = 'Monthly';
        $paymentBillingPeriod->TotalBillingCycles = 1;
        $paymentBillingPeriod->Amount = new BasicAmountType($currencyCode, $amount);
        $paymentBillingPeriod->ShippingAmount = new BasicAmountType($currencyCode, 0);
        $paymentBillingPeriod->TaxAmount = new BasicAmountType($currencyCode, $tax);

        $scheduleDetails = new ScheduleDetailsType();
        $scheduleDetails->Description = $description;
        $scheduleDetails->ActivationDetails = $activationDetails;
        $scheduleDetails->PaymentPeriod = $paymentBillingPeriod;
        
        $createRPProfileRequestDetail = new CreateRecurringPaymentsProfileRequestDetailsType();
        $createRPProfileRequestDetail->Token = $token;
        $createRPProfileRequestDetail->ScheduleDetails = $scheduleDetails;
        $createRPProfileRequestDetail->RecurringPaymentsProfileDetails = $RPProfileDetails;
        $createRPProfileRequest = new CreateRecurringPaymentsProfileRequestType();
        $createRPProfileRequest->CreateRecurringPaymentsProfileRequestDetails = $createRPProfileRequestDetail;
        $createRPProfileReq =  new CreateRecurringPaymentsProfileReq();
        $createRPProfileReq->CreateRecurringPaymentsProfileRequest = $createRPProfileRequest;

        $paypalService = new PayPalAPIInterfaceServiceService($this->getConfig());
        try {
            /* wrap API method calls on the service object with a try catch */
            $createRPProfileResponse = $paypalService->CreateRecurringPaymentsProfile($createRPProfileReq);
            if ($createRPProfileResponse->Ack == 'Success') {
                return true;
            }
        } catch (Exception $ex) {
        }

        return false;
    }

    /**
      Start payment process. Usually involves a request to the
      processor entity.
      @param $amount [numeric] total payment amount
      @param $tax [numeric] tax amount (optional)
      @param $email [string] user email address (optional)
      @return [string] payment identifier or [boolean] false
    */
    public function initializePayment($amount, $tax=0, $email="", $billingAgreement=false)
    {
        global $PAYMENT_URL_SUCCESS, $PAYMENT_URL_FAILURE;
        $config = $this->getConfig();

        $currencyCode = 'USD';
        $paymentDetails = new PaymentDetailsType();
        $paymentDetails->ItemTotal = new BasicAmountType($currencyCode, $amount);
        $paymentDetails->TaxTotal = new BasicAmountType($currencyCode, $tax);
        $paymentDetails->OrderTotal = new BasicAmountType($currencyCode, $amount + $tax);
        $paymentDetails->PaymentAction = 'Sale';

        $setECReqDetails = new SetExpressCheckoutRequestDetailsType();
        $setECReqDetails->BuyerEmail = $email;
        $setECReqDetails->PaymentDetails[0] = $paymentDetails;
        $setECReqDetails->CancelURL = $PAYMENT_URL_FAILURE;
        $setECReqDetails->ReturnURL = $PAYMENT_URL_SUCCESS;
        $setECReqDetails->NoShipping = 1;
        $setECReqDetails->AddressOverride = 0;
        $setECReqDetails->ReqConfirmShipping = 0;
        if ($billingAgreement) {
            $setECReqDetails->BillingAgreementDetails = array($billingAgreement);
        }

        $setECReqType = new SetExpressCheckoutRequestType();
        $setECReqType->SetExpressCheckoutRequestDetails = $setECReqDetails;
        $setECReq = new SetExpressCheckoutReq();
        $setECReq->SetExpressCheckoutRequest = $setECReqType;

        $paypalService = new PayPalAPIInterfaceServiceService(Configuration::getAcctAndConfig());
        try {
            /* wrap API method calls on the service object with a try catch */
            $setECResponse = $paypalService->SetExpressCheckout($setECReq);
            if ($setECResponse->Ack =='Success') {
                return $setECResponse->Token;
            }
        } catch (Exception $ex) {
        }

        return false;
    }

    /**
      Send user to the payment processor's site
      @param $identifier [string] payment identifier
    */
    public function redirectToProcess($identifier)
    {
        header("Location: ".(RemoteProcessor::LIVE_MODE ? $this->PAYPAL_LIVE_RD : $this->PAYPAL_TEST_RD)."?cmd=_express-checkout&token=".$identifier);
    }

    /**
      Finalize the payment
      @param $identifier [string] payment identifier
      @return [boolean] success or failure
    */
    public function finalizePayment($identifier) 
    {
        $config = $this->getConfig();
        $getExpressCheckoutDetailsRequest = new GetExpressCheckoutDetailsRequestType($identifier);
        $getExpressCheckoutReq = new GetExpressCheckoutDetailsReq();
        $getExpressCheckoutReq->GetExpressCheckoutDetailsRequest = $getExpressCheckoutDetailsRequest;
        $paypalService = new PayPalAPIInterfaceServiceService(Configuration::getAcctAndConfig());
        try {
            /* wrap API method calls on the service object with a try catch */
            $getECResponse = $paypalService->GetExpressCheckoutDetails($getExpressCheckoutReq);

            $DoECRequestDetails = new DoExpressCheckoutPaymentRequestDetailsType();
            $DoECRequestDetails->PayerID = $getECResponse->PayerID;
            $DoECRequestDetails->Token = $identifier;
            $DoECRequestDetails->PaymentAction = 'Sale';
            $DoECRequestDetails->PaymentDetails[0] = $getECResponse->PaymentDetails[0];

            $DoECRequest = new DoExpressCheckoutPaymentRequestType();
            $DoECRequest->DoExpressCheckoutPaymentRequestDetails = $DoECRequestDetails;
            $DoECReq = new DoExpressCheckoutPaymentReq();
            $DoECReq->DoExpressCheckoutPaymentRequest = $DoECRequest;

            $DoECResponse = $paypalService->DoExpressCheckoutPayment($DoECReq);
            if ($DoECResponse->Ack == 'Success') {
                return true;
            }
        } catch (Exception $ex) {
        }

        return false;
    }

    public function checkoutButton() 
    {
		return '<button type="submit" name="checkoutButton">
                    <img height="30px;" 
                         style="vertical-align:bottom;" 
                         alt="Paypal Checkout Button"
                         src="https://www.paypal.com/en_US/i/btn/btn_xpressCheckout.gif" />
                </button>';
    }

}

?>
