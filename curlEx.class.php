<?php
	class curlEx
	{
	
		var $url;
		var $url_segments;
		var $cookie = array();
		var $curl;
		
		var $post = false;
		var $sockets = false;
		var $sockets_exc = array();
	
		public function curlEx()
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

		function __destruct()
		{
			curl_close($this->curl);
		}
		
		public function exec($url, $post = false, $post_data = '')
		{
			$this->prepare_url($url);
			if (is_array($post_data))
				$post_data = http_build_query($post_data);
			if (strlen($post_data)>0)
				curl_setopt($this->curl, CURLOPT_POSTFIELDS, $post_data);
			$this->post = $post;
			if ($post) curl_setopt($this->curl, CURLOPT_POST, 1);
			$data = $this->cookie_aware_exec();
			if ($post) curl_setopt($this->curl, CURLOPT_POST, 0);
			while (stripos($data,'Location: ') !== false)
			{
				$i = stripos($data,'Location: ') + 10;
				$url = trim(substr($data, $i, strpos($data,"\n", $i) - $i));
				if ($url[0]=='/')
					$url = $this->url_segments['scheme'].'://'.$this->url_segments['host'].$url;
				else
					if (substr($url,0,7)!='http://' && substr($url,0,8)!='https://')
						$url = $this->url_segments['scheme'].'://'.$this->url_segments['host'].$this->url_segments['path'].$url;
				$this->prepare_url($url);
				$data = $this->cookie_aware_exec();
			}
			$result = array();
			$result['url'] = $url;
			$result['header'] = substr($data, 0, curl_getinfo($this->curl,CURLINFO_HEADER_SIZE));
			$result['data'] = substr($data, curl_getinfo($this->curl,CURLINFO_HEADER_SIZE));
			return $result;
		}
		
		public function head($url, $post = false, $post_data = '')
		{
			curl_setopt($this->curl, CURLOPT_NOBODY, 1);
			$data = $this->exec($url, $post, $post_data);
			curl_setopt($this->curl, CURLOPT_NOBODY, 0);
			return $data;
		}
		
		public function check($url)
		{
			$this->prepare_url($url);
			curl_setopt($this->curl, CURLOPT_NOBODY, 1);
			$data = $this->cookie_aware_exec();
			curl_setopt($this->curl, CURLOPT_NOBODY, 0);
			return $data;
		}
		
		public function add_cookie_str($cookie_str)
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
		
		public function add_cookie_file($cookie_file)
		{
			if (!file_exists($cookie_file))
				return;
			$cookie_data = explode("\n",file_get_contents($cookie_file));
			foreach ($cookie_data as $cookie_str)
				$this->add_cookie_str($cookie_str);
			return;
		}
		
		public function save_cookie_file($cookie_file, $cookies = array())
		{
			$cookie_file_content = '';
			if (!is_array($cookies))
				$cookies = array($cookies);
			foreach ($this->cookie as $cookie)
				if (in_array($cookie['name'],$cookies) || count($cookies)==0)
				{
					$expires = $cookie['expires']===false ? 0 : $cookie['expires'];
					$cookie_file_content .= $cookie['domain']."\tTRUE\t".$cookie['path']."\tFALSE\t".$expires."\t".$cookie['name']."\t".$cookie['value'];
				}
			if (strlen($cookie_file_content)==0)
				return false;
			file_put_contents($cookie_file, $cookie_file_content);
			return true;
		}
		
		public function flush_cookie()
		{
			$this->cookie = array();
		}
		
		protected function prepare_url($url)
		{
			$this->url = $url;
			$this->url_segments = parse_url($url);
			if ($this->url_segments['path'][strlen($this->url_segments['path'])-1]!='/')
				$this->url_segments['path'] .= '/';
		}
		
		protected function cookie_aware_exec()
		{
			$cookies = array();
			if (isset($this->cookie))
				foreach ($this->cookie as $cookie)
					if ((!$cookie['expires'] || time()-$cookie['expires']<0) &&
						preg_match("#".$cookie['domain']."$#i", $this->url_segments['host']) &&
						preg_match("#^".$cookie['path']."#i", $this->url_segments['path']))
							$cookies[] = $cookie['name'].'='.$cookie['value'];
			if (count($cookies)>0)
				curl_setopt($this->curl, CURLOPT_COOKIE, implode(";",$cookies));
			curl_setopt($this->curl, CURLOPT_URL, $this->url);
			
			$data = curl_exec($this->curl);
			
			foreach ($this->sockets_exc as $exc)
				if (strpos($this->url,$exc)!==false)
					if ($this->sockets && !$this->post)
					{
						$data = '';
						$fp = fsockopen($this->url_segments['host'], 80);
						$query = substr($this->url,strpos($this->url,'/',strpos($this->url,$this->url_segments['host'])));
						$out = "GET ".$query." HTTP/1.1\r\n";
						$out .= "Host: ".$this->url_segments['host']."\r\n";
						$out .= "Cookie: ".implode(";",$cookies)."\r\n";
						$out .= "Connection: Close\r\n\r\n";
						fwrite($fp, $out);
						while (!feof($fp))
							$data .= fgets($fp, 128);
						fclose($fp);
					}
			
			$header = substr($data, 0, curl_getinfo($this->curl,CURLINFO_HEADER_SIZE));
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
			return $data;
		}
		
	}

?>
