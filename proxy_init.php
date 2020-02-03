<?php

	include('config.php');
	include('curlEx.class.php');
	include('database.class.php');
	
	include('proxies/proxyNCSU.class.php');

	$o = new proxyNCSU();

	$db = new database();
	$o->fill_db($db);

	print_r($db->domain_check('sciencemag.org'));

?>
