<?php

$IS4C_PATH = isset($IS4C_PATH)?$IS4C_PATH:"";
if (empty($IS4C_PATH)){ while(!file_exists($IS4C_PATH."is4c.css")) $IS4C_PATH .= "../"; }

if (!class_exists('PhpAutoLoader')) {
    require(dirname(__FILE__) . '/../../vendor-code/PhpAutoLoader/PhpAutoLoader.php');
}

class pickupFAQ extends BasicPage
{
	function main_content()
    {
        echo <<<HTML
<p>
Orders for pick up are currently available at our <strong>Denfeld location only</strong> 
(4426 Grand Ave).
</p>
<p>
How to order:
<ul>
    <li>Create an account if you don't have one already (this is separate from your owner account with the co-op).</li>
    <li>Browse or search items and add them to your cart.</li>
    <li>Go to your shopping cart and if everything looks right click Arrange Pickup.</li>
    <li>Call between 7am and 10am to activate your order and let us know what time you'll pick up your order</li>
    <li>The co-op will call you with a final total and take credit card payment over the phone.</li>
    <li>Call when you've arrived for pickup and we'll take the order out to your car.</li>
</ul>
</p>
<p>
WFC will make every effort to have accurate pricing on the website but your final total may differ from what's shown
online.
</p>
HTML;
    }

	function preprocess()
    {
        return true;
    }
}

if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
    new pickupFAQ();
}

