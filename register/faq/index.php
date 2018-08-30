<?php
$IS4C_PATH="";
if (!class_exists('PhpAutoLoader')) {
    require(dirname(__FILE__) . '/../vendor-code/PhpAutoLoader/PhpAutoLoader.php');
}
include(__DIR__ . '/../ini.php');

class FAQPage extends NoMenuPage 
{
    public function main_content()
    {
        echo <<<HTML
<h2>FAQ</h2>
<h3>When is the Annual Owners Meeting?</h3>
<p>The Annual Owners Meeting is Tuesday, October 16, 2018 from 5pm to 7:45pm.
Dinner will be served at 5:30pm.</p>
<h3>Where is the Annual Owners Meeting?</h3>
<p>The meeting will be held at the Harbor Side Room in the Duluth Entertainment & Convention Center (DECC).</p>
<h3>Who can attend the Annual Owners Meeting?</h3>
<p>Any owner may attend the meeting. However, owners that wish to attend <strong>must pre-register beforehand</strong>.
WFC needs to know who is attending to ensure an adequate amount of seating and meals. Owners
may bring additional guests or children as long as their registration indicates the total number of
people attending.
</p>
<h3>How do I register to attend the Annual Owners Meeting?</h3>
<p>You can register <a href="../index.php">online</a>, at the customer service desk in the store,
or by calling and speaking to customer service (218-728-0884, press 1). You must register by
<strong>October 1, 2018</strong> to attend this year's meeting.</p>
<h3>What does it cost to attend the Annual Owners Meeting?</h3>
<p>Registration for the meeting costs $20 per adult and $5 per child (12 and under). Owners will
receive a $20 gift card upon arrival to the meeting.
</p>
<h2>Agenda</h2>
<h3>Social Time</h3>
<p>5:00 - 5:30 PM</p>
<p>Owner opportunity to vote for Reduce/Reuse/Recycle and Abandoned Equity non-profit recipient for 2019 via “bean count”</p>
<h3>Dinner is Served</h3>
<p>5:30 PM</p>
<h3>Guest Speakers</h3>
<p>6:00 - 6:30 PM</p>
<p>
Steve Alves – Producer/Director of the film “Food For Change”
</p>
<h3>Business Meeting</h3>
<p>6:30 - 7:00 PM &nbsp;&nbsp;<i>child care available</i>
    <ul>
        <li>Welcome & Introduction</li>
        <li>Proof of notice of meeting</li>
        <li>Report on number of Owners present</li>
        <li>Reading or waiver of reading of Minutes of 2017 meeting/approval of 2017 Minutes</li>
        <li>State of the Co-op Reports from Management and Board</li>
        <li>Remarks for the good and welfare of the Co-op</li>
        <li>Announcement of Results of Board Election</li>
    </ul>
</p>
<h3>Door Prize Drawings</h3>
<p>7:15 PM</p>
<p>Must be present to win.</p>
<h3></h3>
<p></p>
HTML;
    }
}

new FAQPage('Annual Meeting Registration');

