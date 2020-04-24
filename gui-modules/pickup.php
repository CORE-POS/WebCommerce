<?php

$IS4C_PATH = isset($IS4C_PATH)?$IS4C_PATH:"";
if (empty($IS4C_PATH)){ while(!file_exists($IS4C_PATH."is4c.css")) $IS4C_PATH .= "../"; }

if (!class_exists('PhpAutoLoader')) {
    require(dirname(__FILE__) . '/../vendor-code/PhpAutoLoader/PhpAutoLoader.php');
}

class pickup extends BasicPage
{
	function main_content()
    {
		$empno = AuthUtilities::getUID(AuthLogin::checkLogin());
        $transno = Database::gettransno($empno);
        $hour = date('G');
        if (true || ($hour >= 7 && $hour < 10)) {
            echo <<<HTML
<p>
<h3 style="text-align: center;">Your Order Number is:<br /> {$empno} - {$transno}</h3>
Please call 218-343-2643 to activate your order. Please have ready:
<ul>
    <li>Your name</li>
    <li>Phone number</li>
    <li>Order number (above)</li>
    <li>Make, model, and color of your vehicle</li>
</ul>
</p>
HTML;
        } else {
            echo <<<HTML
<p>
Orders are accepted between 7am and 10am. Please come back later to activate your order.
Feel free to close this page. Your items will remain saved with your account.
</p>        
HTML;

        }
    }

	function preprocess()
    {
        return true;
    }
}

if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
    new pickup();
}

