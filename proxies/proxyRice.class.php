<?php

	class proxyRice
	{
	
		public $name = 'Rice University';	
		public $domain = '.ezproxy.rice.edu';
		var $login = 'sk27';
		var $pass = 'z2c22hz7';
		var $config_file = 'rice.config.txt';
	
		public function proxyRice()
		{
			$this->config_file = dirname(__FILE__).'/'.$this->config_file;
			$this->init_curl();
			if (file_exists($this->config_file))
				$this->reset_cookie(file_get_contents($this->config_file));
			else
				$this->reset_cookie();
		}
		
		function __destruct()
		{
			curl_close($this->curl);
			if (isset($this->cookie_file))
				unlink($this->cookie_file);
		}
		
		public function ready()
		{
			$q = mysql_query("SELECT active FROM proxys WHERE proxy_name='".$this->name."'");
			$r = mysql_fetch_row($q);
			return ($r[0]==1);
		}
		
		protected function disable()
		{
			mysql_query("UPDATE proxys SET active=0 WHERE proxy_name='".$this->name."'");
		}
		
		function fill_db($database)
		{
			set_time_limit(120);
			curl_setopt($this->curl, CURLOPT_NOBODY, 1);
			$menu = $this->curl_cookie_aware_exec('https://login.ezproxy.rice.edu/menu');
			curl_setopt($this->curl, CURLOPT_NOBODY, 0);
			if (strpos($menu,'Location: ') !== false)
			{
				$this->enter();
				$this->reset_cookie(file_get_contents($this->config_file));
			}
			curl_setopt($this->curl, CURLOPT_TIMEOUT, 60);
			$menu = $this->curl_cookie_aware_exec('https://login.ezproxy.rice.edu/menu');
			//$menu = file_get_contents( dirname(__FILE__).'/menu.htm');
			$pattern = 'login?url=';
			$i = 0;
			$domains = array();
			while (($i = strpos($menu,$pattern,$i)) !== false)
			{
				$i += strlen($pattern);
				$i = strpos($menu,"//",$i) + 2;
				$domain = substr($menu,$i,strpos($menu,'"',$i)-$i) . '/';
				if (substr($domain,0,3)=='www')
					$domain = substr($domain,4);
				$domain = substr($domain,0,strpos($domain,'/'));
				$domains[] = $domain;
			}
			$database->fill_proxy($this->name,$domains);
		}
		
		
		
		public function get_in($u)
		{
			curl_setopt($this->curl, CURLOPT_NOBODY, 1);
			curl_setopt($this->curl, CURLOPT_URL, $u);
			
			$test = $this->curl_cookie_aware_exec($u);
			
			curl_setopt($this->curl, CURLOPT_NOBODY, 0);
			
		log_event($u,$test);
			
			if (strpos($test,'Location: http://ezproxy.rice.edu/') !== false || strpos($test,'login.ezproxy.rice.edu/login?url=') !== false)
			{
				log_event("entering proxy...");
			
				$this->reset_cookie();
				
				if (!$this->enter())
					return false;
				
				$this->reset_cookie(file_get_contents($this->config_file));
			}
			else
				log_event("logged in!");
			
			foreach ($this->cookie as $cookie)
				if ($cookie['name']=='ezproxy')
					return $cookie['name'].'='.$cookie['value'];
		}
		
		function enter()
		{
			$this->reset_cookie();
			
			curl_setopt($this->curl, CURLOPT_NOBODY, 1);
			$login_page = $this->get_page('https://login.ezproxy.rice.edu/login');
			curl_setopt($this->curl, CURLOPT_NOBODY, 0);
			
			curl_setopt($this->curl, CURLOPT_POSTFIELDS, 'user='.$this->login.'&pass='.$this->pass.'&url=^U');
			$success_page = $this->get_page('https://login.ezproxy.rice.edu/login', true);
			
			$config_content = '';
			
			foreach ($this->cookie as $cookie)
				if ($cookie['name']=='ezproxy')
				{
					$expires = $cookie['expires']===false ? 0 : $cookie['expires'];
					$config_content = $cookie['domain']."\tTRUE\t".$cookie['path']."\tFALSE\t".$expires."\t".$cookie['name']."\t".$cookie['value'];
					break;
				}
			if (strlen($config_content)>0)
			{
				file_put_contents($this->config_file,$config_content);
				return true;
			}
			else
			{
				log_event("login failed for ".$this->name);
				$this->disable();
				notify($this->name." down");
				return false;
			}
		}
		
		function init_curl()
		{
			$this->curl = curl_init();
			curl_setopt($this->curl, CURLOPT_HEADER, 1);
			curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($this->curl, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($this->curl, CURLOPT_SSL_VERIFYHOST, false);
			curl_setopt($this->curl, CURLOPT_AUTOREFERER, true);
			curl_setopt($this->curl, CURLOPT_CONNECTTIMEOUT, 5);
			curl_setopt($this->curl, CURLOPT_TIMEOUT, 10);
			curl_setopt($this->curl, CURLOPT_FOLLOWLOCATION, false);
		}
		
		function reset_cookie($cookies = "")
		{
			$this->cookie = array();
			$cookie_array = explode("\n",$cookies);
			foreach ($cookie_array as $cookie_str)
			{
				$cookie_str = explode("\t",trim($cookie_str));
				if ($cookie_str[4]==0) $expires = false; else $expires = strtotime($cookie_str[4]);
				if (count($cookie_str)==7)
					$this->cookie[] = array(
												'name'		=> $cookie_str[5],
												'value'		=> $cookie_str[6],
												'expires'	=> $expires,
												'path'		=> $cookie_str[2],
												'domain'	=> $cookie_str[0]
											);
			}
			return;
		}
		
		function get_page($url, $POST = false)
		{
			log_event("fetching ".$url);
			if ($POST) curl_setopt($this->curl, CURLOPT_POST, 1);
			$data = $this->curl_cookie_aware_exec($url);
			if ($POST) curl_setopt($this->curl, CURLOPT_POST, 0);
			while (strpos($data,'Location: ') !== false)
			{
				$i = strpos($data,'Location: ') + 10;
				$url = trim(substr($data, $i, strpos($data,"\n", $i) - $i));
				log_event('redirect - fetching '.$url);
				$data = $this->curl_cookie_aware_exec($url);
			}
			$result = array();
			$result['url'] = $url;
			$result['data'] = substr($data, curl_getinfo($this->curl,CURLINFO_HEADER_SIZE));
			return $result;
		}
		
		
		function curl_cookie_aware_exec($url)
		{
			$url_segments = parse_url($url);
			$cookies = array();
			if (isset($this->cookie))
				foreach ($this->cookie as $cookie)
					if ((!$cookie['expires'] || time()-$cookie['expires']<0) &&
						preg_match("#".$cookie['domain']."$#i", $url_segments['host']) &&
						preg_match("#^".$cookie['path']."#i", $url_segments['path']))
							$cookies[] = $cookie['name'].'='.$cookie['value'];
			if (count($cookies)>0)
			{
				curl_setopt($this->curl, CURLOPT_COOKIE, implode(";",$cookies));
				log_event('using '.implode(";",$cookies));
			}
			curl_setopt($this->curl, CURLOPT_URL, $url);
			$data = curl_exec($this->curl);
			$header = substr($data, 0, curl_getinfo($this->curl,CURLINFO_HEADER_SIZE));
			
			//log_event('got ',$header);
			
			if (preg_match_all("#set-cookie:([^\r\n]*)#i", $header, $matches))
			{
				if (!isset($this->cookie))
					$this->cookie = array();
				foreach ($matches[1] as $cookie_info)
				{
					preg_match('#^\s*([^=;,\s]*)=?([^;,\s]*)#', $cookie_info, $match)  && list(, $name, $value) = $match;
					preg_match('#;\s*expires\s*=([^;]*)#i', $cookie_info, $match)      && list(, $expires)      = $match;
					preg_match('#;\s*path\s*=\s*([^;,\s]*)#i', $cookie_info, $match)   && list(, $path)         = $match;
					preg_match('#;\s*domain\s*=\s*([^;,\s]*)#i', $cookie_info, $match) && list(, $domain)       = $match;
					preg_match('#;\s*(secure\b)#i', $cookie_info, $match)              && list(, $secure)       = $match;
					$expires = isset($expires) ? strtotime($expires) : false;
					$expires = (is_numeric($expires) && time()-$expires < 0) ? false : $expires;
					$path    = isset($path)    ? $path : $url_segments['path'];
					$domain  = isset($domain)  ? $domain : $url_segments['host'];
					$domain  = rtrim($domain, '.');
					
					$new_cookie = array(
											'name'		=> $name,
											'value'		=> $value,
											'expires'	=> $expires,
											'path'		=> $path,
											'domain'	=> $domain
										);
					
					$cookie_i = -1;
					for ($i=0; $i<count($cookie); $i++)
						if ($this->cookie[$i]['name']==$name)
						{
							$cookie_i = $i;
							break;
						}
					
					if ($cookie_i==-1)
						$this->cookie[] = $new_cookie;
					else
						$this->cookie[$cookie_i] = $new_cookie;

				}
			}
			
			/*ob_start();
			print_r($this->cookie);
			$cookie_tmp = ob_get_contents();
			ob_end_clean();
			log_event('got cookies',$cookie_tmp);*/
			
			return $data;
		}
		
	}

?>
