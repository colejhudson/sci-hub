<!-- <meta http-equiv="refresh" content="120"> -->

<style type="text/css">
	a {font-size:12px;color:green;text-decoration:none}
	td {padding:15px 10px 5px 10px;border-bottom:solid 1px #ccc;font-family:Verdana}
	.proxy {font-size:12px;color:#aaa}
	div {font-family:Verdana}
	.geo {font-weight:bold;font-size:10px}
	.mark {vertical-align:top;text-align:left;font-size:10px}
</style>

<table style="width:100%">

<tr>
	<td style="width:15%"></td>
	<td style="width:65%"></td>
	<td></td>
	<td style="width:10%"></td>
	<td style="width:10%"></td>
</td>

<?php

	include('config.php');
	include('database.class.php');
	include('geo.class.php');
	
	new database();
	
	$geo = new geo();
	
	$my_ip = get_user_ip();
	
	if (!isset($_SESSION['admin']) || $my_ip!=$_SESSION['admin_ip'])
	{
		$location = $geo->get_data($my_ip);
		if ($location['country']=='kazakhstan' || $location['country']=='*' || md5($_GET['p'])=='9cc6a2962537f146b6ac07082be925bc')
		{
			$_SESSION['admin'] = true;
			$_SESSION['admin_ip'] = $my_ip;
		}
		else
		{
			unset($_SESSION['admin']);
			exit;
		}
	}
	
	if (isset($_GET['activate']))
	{
		mysql_query("UPDATE proxys SET active=1 WHERE proxy_name='".$_GET['activate']."'");
		header("Location: /events.php");
	}
	
	if (isset($_GET['remove']))
	{
		mysql_query("DELETE FROM log WHERE client_ip='".$_GET['remove']."'");
		header("Location: /events.php");
	}
	
	if (isset($_GET['remove_id']))
	{
		mysql_query("DELETE FROM log WHERE id='".$_GET['remove_id']."'");
		header("Location: /events.php");
	}
	
	$count = isset($_GET['count'])?$_GET['count']:35;
	
	session_start();
	
	$proxy = array();
	$q = mysql_query("SELECT proxy_name,active FROM proxys ORDER BY proxy_name");
	
	echo mysql_error();
	
	while ($r = mysql_fetch_row($q))
		$proxy[$r[0]] = $r[1];
	
	if (isset($_SESSION['ip_data']))
		$ip_data = $_SESSION['ip_data'];
	else
		$ip_data = array();
	
	$q = mysql_query("SELECT client_ip, request, proxy, time + INTERVAL 10 HOUR, `type`, id, note FROM `log` WHERE request NOT LIKE 'http://scholar.google.com/scholar?%' ORDER BY `log`.`time` DESC LIMIT 0,".$count);

	$prev = '';
	
	$unique_ip = array();
	$unique_url = array();
	$pdf_count = 0;
	
	while ($r = mysql_fetch_row($q))
	{
	
		$time = explode(' ',$r[3]);
		$time = $time[1];
	
		if ($prev==md5($r[0].$r[1]))
		{
			echo '<tr><td colspan=5 style="font-size:10px;color:#ccc;border:none">+1 ['.$r[2].'] '.$time.'</td></tr>';
			continue;
		}
		
		$prev = md5($r[0].$r[1]);
	
		if (!isset($ip_data[$r[0]]))
		{
			$location = $geo->get_data($r[0],true);
			$data = implode(', ',$location);
			if ($location['country']!='*')
				$ip_data[$r[0]] = $data;
		}
		if (isset($ip_data[$r[0]]))
			$geo_i = $ip_data[$r[0]];
		else
			$geo_i = '';
		$del = '';
		if (strpos($geo_i,'kazakhstan')!==false || strpos($geo_i,'united states')!==false)
			$del = '<sup><a href="events.php?remove='.$r[0].'">x</a></sup>';
		$mark = '&nbsp;';
		if ($r[4]=='PDF')
			$mark = '<font style="color:gray">[<font color=red>PDF</font>]</font>';
		$url = $r[1];
		if ($url[0]=='*')
			$url = gzuncompress(base64_decode($url));
		$url = urldecode($url);
		$title = $url;
		if (strlen($title)>100)
			$title = substr($title,0,100).'...';
		echo "<tr><td title=\"{$r[6]}\">{$r[0]} $del<br><span class=\"geo\">$geo_i</span></td><td><a style=\"color:gray;font-size:8px\" href=\"events.php?remove_id={$r[5]}\">x </a><a href=\"$url\" target=\"_new\">$title</a></td><td class=\"mark\">$mark</td><td class=\"proxy\">{$r[2]}</td><td>$time</td></tr>";
		
		if (!in_array($r[0],$unique_ip))
			$unique_ip[] = $r[0];
		if (!in_array(md5($r[1]),$unique_url))
		{
			$unique_url[] = md5($r[1]);
			if ($r[4]=='PDF')
				$pdf_count++;
		}
	}
	
	$_SESSION['ip_data'] = $ip_data;
	
?>

</table>

<div style="padding:50px;padding-bottom:0px"><?php echo 'Total '.count($unique_ip).' unique visitors, '.count($unique_url).' requests, '.$pdf_count.' PDF downloaded'; ?></div>

<div style="padding:50px">
<p><b>Proxy Status</b></p>

<?php

	foreach ($proxy as $p=>$a)
	{
		echo '<div style="float:left;width:100px">';
		if ($a==1)
			echo '<font color="green">active</font>';
		else
		if ($a==2)
			echo '<font color="blue">waiting</font>';
		else
			echo '<font color="red">stopped</font><sup><a href="/events.php?activate='.urlencode($p).'">*</a></sup>';
		echo '</div>'.$p.'<div style="clear:both"></div>';
	}

?>

</div>

<?php

	exit();
	
?>
