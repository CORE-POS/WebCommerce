<?php
// pull content into index for prettier URL
include('GiftPage.php');
if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
    new GiftPage();
}
