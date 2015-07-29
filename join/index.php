<?php
// pull content into index for prettier URL
include('JoinPage.php');
if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
    new JoinPage();
}
