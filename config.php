<?php

	error_reporting(0);

	ini_set('session.bug_compat_warn', 0);
	ini_set('session.bug_compat_42', 0);

	$GLOBALS['db_server'] = 'mysql11.000webhost.com';
	$GLOBALS['db_login'] = 'a7524768_user';
	$GLOBALS['db_pass'] = '3dpRKKKX';
	$GLOBALS['db_name'] = 'a7524768_db';
	$GLOBALS['db_prefix'] = '';
	
	$GLOBALS['log_req_file'] = '/home/a7524768/public_html/requests.txt';
	$GLOBALS['log_file'] = '/home/a7524768/public_html/events.txt';
	$GLOBALS['exclude_log'] = array('resources.metapress.com/pdf-preview.axd?','www.springerlink.com/dynamic-file.axd?','ncbi.nlm.nih.gov/portal/js/portal.js','ncbi.nlm.nih.gov/portal/portal3rc.fcgi/rlib/js/InstrumentOmnitureBaseJS');
	$GLOBALS['mute'] = true;
	
	$GLOBALS['ban_ip'] = true;
	$GLOBALS['banned_countries'] = array('united states','france','united kingdom');
	$GLOBALS['banned_ip'] = array();
	
	$GLOBALS['noscript_hosts'] = array('hematologylibrary.org','sciencemag.org','jbc.org','bmj.com');
	$GLOBALS['script_hosts'] = array('rsc.org','ncbi.nlm.nih.gov','onlinelibrary.wiley.com');
	
	$GLOBALS['banned_browsers'] = array('Opera');
	
	$config = array
	(
		'url_var_name'             => 'q',
		'flags_var_name'           => 'hl',
		'get_form_name'            => '__script_get_form',
		'proxy_url_form_name'      => 'poxy_url_form',
		'proxy_settings_form_name' => 'poxy_settings_form',
		'max_file_size'            => -1
	);
	
	function log_event($message,$data='')
	{
		if ($GLOBALS['mute'])
			return;
		if (is_array($message))
		{
			ob_start();
			print_r($message);
			$message = ob_get_contents();
			ob_end_clean();
		}
		if (is_array($data))
		{
			ob_start();
			print_r($data);
			$data = ob_get_contents();
			ob_end_clean();
		}
		$f = fopen($GLOBALS['log_file'], 'a');
		fwrite($f, date('h:i:s')."\t".get_user_ip()."\t".$message."\r\n");
		if (strlen($data)>0)
			fwrite($f, "\r\n".$data."\r\n\r\n");
		fclose($f);
	}
	
	function log_event_forced($message,$data='')
	{
		$mute = $GLOBALS['mute'];
		$GLOBALS['mute'] = false;
		log_event($message,$data);
		$GLOBALS['mute'] = $mute;
	}
	
	function notify($msg)
	{
		file_get_contents("http://ringo-ring.info/support/sci-hub/notify.php?message=".urlencode($msg));
	}
	
	function get_user_ip()
	{
		if ( getenv('REMOTE_ADDR') ) $user_ip = getenv('REMOTE_ADDR');
		elseif ( getenv('HTTP_FORWARDED_FOR') ) $user_ip = getenv('HTTP_FORWARDED_FOR');
		elseif ( getenv('HTTP_X_FORWARDED_FOR') ) $user_ip = getenv('HTTP_X_FORWARDED_FOR');
		elseif ( getenv('HTTP_X_COMING_FROM') ) $user_ip = getenv('HTTP_X_COMING_FROM');
		elseif ( getenv('HTTP_VIA') ) $user_ip = getenv('HTTP_VIA');
		elseif ( getenv('HTTP_XROXY_CONNECTION') ) $user_ip = getenv('HTTP_XROXY_CONNECTION');
		elseif ( getenv('HTTP_CLIENT_IP') ) $user_ip = getenv('HTTP_CLIENT_IP');
		$user_ip = trim($user_ip);
		if ( !preg_match("/^([1-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])(\.([0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])){3}$/", $user_ip) ) return '';
		return $user_ip;
	}
	
?>
