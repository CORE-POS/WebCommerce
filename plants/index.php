<?php

$IS4C_PATH = isset($IS4C_PATH)?$IS4C_PATH:"";
if (empty($IS4C_PATH)){ while(!file_exists($IS4C_PATH."is4c.css")) $IS4C_PATH .= "../"; }

if (!class_exists('PhpAutoLoader')) {
    require(dirname(__FILE__) . '/../vendor-code/PhpAutoLoader/PhpAutoLoader.php');
}

class plantFAQ extends BasicPage
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
    <li>Go to your shopping cart and if everything looks right click the Paypal checkout button</li>
    <li>Enter payment information in Paypal</li>
    <li>Enter your phone number and desired pick-up date & time to finalize your order</li>
    <li>When you arrive let customer service or a cashier know you're here to pick up a plant order</li>
</ul>
</p>
<p>
Choice of pickup time is between 4 and 7pm. Earliest choice of pickup day is the next day - for example, if you're ordering
on a Monday the earliest pickup day to choose will be Tuesday.
</p>
<p>
Orders are subject to availability; refunds will be issued for any items that run out of stock.
</p>
<p>
Selection will change throughout the season. If you don't see what you're looking for today check again
in a week or two.
</p>
HTML;
    }

	function preprocess()
    {
        return true;
    }
}

if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
    new plantFAQ();
}

