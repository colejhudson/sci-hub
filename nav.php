<html>
	<head>
	
		<style type="text/css">
			body {margin:0;padding:0;overflow:hidden;height:50px;overflow:hidden;background:url('logo.png') no-repeat;background-position: 15px 7px;background-color:#f0f0f0}
			#nav_form {margin-top:15px;margin-left:145px;font-size:11px;font-family:Tahoma;font-weight:bold}
			#nav_form input {margin:0px 10px 0px 10px;width:450px;font-size:10px}
			#nav_form .button {display:inline;border:solid 1px #ccc;padding:5px 10px 5px 10px;font-weight:normal;background-color:#fafafa;font-family:Arial;border-radius:5px;-moz-border-radius:5px;cursor:pointer}
			#nav_form #wait {display:none;margin-left:20px;font-weight:normal;font-size:13px;color:#666}
			#nav_form #feedback {margin:0;padding:0;display:inline;float:right;margin-right:25px}
			#nav_form #feedback a {color:#999}
		</style>
		
		<script src="javascript.js" type="text/javascript"></script>
		
		<script type="text/javascript">
			var enabled = true;
			function enter(i,e) {var keycode;if (window.event) keycode = window.event.keyCode;else if (e) keycode = e.which;else return true;if (keycode == 13) {submit();return false;} else return true;}
			function submit()
			{
				if(!enabled && document.getElementById('wait').style.display=='inline') return; enabled=false;
				document.getElementById('wait').style.display='inline';
				url = document.getElementById('url').value;
				if (url.substr(0,7)!='http://')
					if (url.substr(0,3)=='10.' && url.split('/').length==2)
						url = 'http://dx.doi.org/' + url;
					else
					if (url.split('/').length+url.split('.').length<=url.split(' ').length)
						url = 'http://scholar.google.com/scholar?q='+encodeURIComponent(url);
				parent.browser.location.href='browser.php?q='+base64_encode(url);
				setTimeout("enable()",25000)
			}
			function enable() {enabled=true;}
		</script>
	
	</head>

	<body>
	
		<div id="nav_form">
			<!--<span id="logo">Sci-Hub<sup><font style="font-size:10px">beta</font></sup></span>-->
			<span style="float:left">
			Ссылка: <input type="text" id="url" onkeypress="enter(this,event)"><span class="button" onclick="submit()">Открыть</span>
			<span id="wait"><img src="wait.gif">&nbsp;&nbsp;&nbsp;идет загрузка...</span>
			</span>
			<span id="feedback"><a href=# onclick="window.open('http://ringo-ring.info/support/sci-hub/feedback.php','feedback','width=430,height=455,location=no,resizable=no,menubar=no')">Обратная связь <img border="0" style="margin-left:3px" src="feedback.png"></a></span>
		</div>
	
	</body>

</html>
<?php exit(0); ?>
