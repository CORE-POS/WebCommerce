<?php

@session_start();
$_SESSION['storeID'] = $_GET['id'];
header('Location: https://store.wholefoods.coop/items/', true, 302);
exit;
