<style type="text/css">
	a {font-size:12px;color:green;text-decoration:none}
	div {font-family:Verdana;font-size:12px}
</style>

<?php

	include('config.php');
	include('database.class.php');

	new database();

	if (isset($_GET['activate']))
	{
		mysql_query("UPDATE proxys SET active=1 WHERE proxy_name='".$_GET['activate']."'");
		header("Location: /status.php");
	}
	
	$proxy = array();
	$q = mysql_query("SELECT proxy_name,active FROM proxys ORDER BY proxy_name");
	
	echo mysql_error();
	
	while ($r = mysql_fetch_row($q))
		$proxy[$r[0]] = $r[1];
	
?>

<div style="margin-top:15px;margin-left:5px">

<?php

	foreach ($proxy as $p=>$a)
	{
		echo '<div style="float:left;width:70px">';
		if ($a==1)
			echo '<font color="green">active</font>';
		else
		if ($a==2)
			echo '<font color="blue">waiting</font>';
		else
			echo '<font color="red">stopped</font><sup><a href="/status.php?activate='.urlencode($p).'">*</a></sup>';
		echo '</div>'.$p.'<div style="clear:both;padding-top:5px"></div>';
	}

?>

</div>

<?php exit(); ?>
