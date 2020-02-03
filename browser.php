<?php

	include('config.php');
	include('geo.class.php');
	include('curlEx.class.php');
	include('logger.class.php');
	require 'PHProxy.class.php';

	session_start();
	
	foreach ($GLOBALS['banned_browsers'] as $browser)
		if (strpos($_SERVER['HTTP_USER_AGENT'], $browser)!==false)
		{
			$_SESSION['msg'] = $browser;
			header('Location: /unsupported.php');
			exit();
		}
	
	$ip = get_user_ip();
	if (!isset($_SESSION['client_ip']))
		$_SESSION['client_ip'] = $ip;
	if (in_array($ip,$GLOBALS['banned_ip']))
		$_SESSION['banned'] = true;
	if (!isset($_SESSION['banned']) || $_SESSION['client_ip']!=$ip)
	{
		$geo = new geo();
		$location = $geo->get_data($ip);
		if ($location['country']!='*')
			$_SESSION['banned'] = (in_array($location['country'],$GLOBALS['banned_countries']));
		else
			unset($_SESSION['banned']);
		$_SESSION['client_ip'] = $ip;
	}
	if ($_SESSION['banned'] && $GLOBALS['ban_ip'])
	{
		header('Location: /wait.html');
		exit();
	}
	
	foreach ($_POST as $k => $v)
		$_POST[$k] = stripslashes($v);
	foreach ($_GET as $k => $v)
		$_GET[$k] = stripslashes($v);
	$_SERVER['QUERY_STRING'] = stripslashes($_SERVER['QUERY_STRING']);
	
	$PHProxy = new PHProxy($config);
	
	if (isset($_GET[$PHProxy->config['get_form_name']]))
	{
		$url = decode_url($_GET[$PHProxy->config['get_form_name']]);
		$qstr = preg_match('#\?#', $url) ? (strpos($url, '?') === strlen($url) ? '' : '&') : '?';
		$arr = explode('&', $_SERVER['QUERY_STRING']);
		if (preg_match('#^'.$PHProxy->config['get_form_name'].'#', $arr[0]))
		{
			array_shift($arr);
		}
		$url .= $qstr . implode('&', $arr);
		$url = encode_url($url);
	}
	else
	if (isset($_GET[$PHProxy->config['url_var_name']]))
		$url = $_GET[$PHProxy->config['url_var_name']];
	
	if (isset($url))
	{
		$PHProxy->start_transfer($url);
		if ($PHProxy->unsupported_host)
		{
			header('Location: '.$PHProxy->url);
			exit();
		}
		if ($PHProxy->proxy_dead)
		{
			header('Location: /wait.html');
			exit();
		}
		if ($PHProxy->attack)
		{
			header('Location: /attack.php');
			exit();
		}
		$_SESSION['url'] = $PHProxy->url;
		$_SESSION['proxy'] = $PHProxy->current_proxy->name;
		
		ob_start("ob_gzhandler");
		echo $PHProxy->return_response();
		ob_end_flush();
		
		exit();
	}

?>
