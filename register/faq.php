<?php
$IS4C_PATH="";
if (!class_exists('PhpAutoLoader')) {
    require(dirname(__FILE__) . '/vendor-code/PhpAutoLoader/PhpAutoLoader.php');
}
include('ini.php');

class FAQPage extends NoMenuPage 
{
    public function main_content()
    {
        echo <<<HTML
<h3>When is the Annual Owners Meeting?</h3>
<p>The Annual Owners Meeting is Friday, October 21, 2016 from 5pm to 8pm.</p>
<h3>Where is the Annual Owners Meeting?</h3>
<p>The meeting will be held at the Harbor Side Room in the Duluth Entertainment & Convention Center (DECC).</p>
<h3>Who can attend the Annual Owners Meeting?</h3>
<p>Any owner may attend the meeting. However, owners that wish to attend <strong>must pre-register beforehand</strong>.
WFC needs to know who is attending to ensure an adequate amount of seating and meals. Owners
may bring additional guests or children as long as their registration indicates the total number of
people attending.
</p>
<h3>How do I register to attend the Annual Owners Meeting?</h3>
<p>You can register <a href="index.php">online</a>, at the customer service desk in the store,
or by calling and speaking to customer service (218-728-0884, press 1). You must register by
<strong>October 10, 2016</strong> to attend this year's meeting.</p>
<h3>What does it cost to attend the Annual Owners Meeting?</h3>
<p>Registration for the meeting costs $20 per adult and $5 per child (12 and under). Owners will
receive a $20 gift card upon arrival to the meeting.
</p>
HTML;
    }
}

new FAQPage('Annual Meeting Registration');

