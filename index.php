<?php
	$url_browser = 'welcome.html';
	if (isset($_GET['url']))
	{
		$url_browser = '/browser.php?q='.$_GET['url'];
		$url_nav = str_replace('&amp;', '&', base64_decode(rawurldecode($_GET['url'])));
	}
?>
<html>
	<head>
	<title></title>
	<script type="text/javascript">
		function complete() {nav.document.getElementById('wait').style.display = 'none';}
		function setnav() {<?php if (isset($url_nav)) {echo "nav.document.getElementById('url').value=\"".$url_nav.'"';} ?>}
	</script>
	</head>
	<frameset rows="50px,3px,*,25px" frameborder="0" border="0" framespacing="0">
		<frame src="nav.php" name="nav" id="nav" noresize="noresize" scrolling="no" onload="setnav()" />
		<frame src="" style="background-color:#ccc" noresize="noresize" scrolling="no" />
		<frame name="browser" id="browser" src="<?php echo $url_browser; ?>" onload="complete()" />
		<frame src="banner.php" noresize="noresize" scrolling="no" />
	</frameset>
</html>
