<?php

	class proxyUIC
	{
	
		public $name = 'UIC';	
		public $domain = '.proxy.cc.uic.edu';
		var $login = 'ekocs';
		var $pass = 'Aniko813';
		var $config_file = 'uic.config.txt';
	
		public function proxyUIC()
		{
			$this->config_file = dirname(__FILE__).'/'.$this->config_file;
			$this->curl = new curlEx();
			$this->curl->sockets = true;
			$this->curl->sockets_exc[] = 'ness.uic.edu/bluestem/login.cgi';
			$this->curl->add_cookie_file($this->config_file);
		}
		
		public function ready()
		{
			$q = mysql_query("SELECT active FROM proxys WHERE proxy_name='".$this->name."'");
			$r = mysql_fetch_row($q);
			if ($r==false)
				return $false;
			return ($r[0]==1);
		}
		
		protected function disable()
		{
			mysql_query("UPDATE proxys SET active=0 WHERE proxy_name='".$this->name."'");
		}
		
		function fill_db($database)
		{
			set_time_limit(120);
			if (strpos($this->curl->check('http://proxy.cc.uic.edu/menu'),'Location: ') !== false)
			{
				$this->enter();
				$this->curl->flush_cookie();
				$this->curl->add_cookie_file($this->config_file);
			}
			curl_setopt($this->curl->curl, CURLOPT_TIMEOUT, 60);
			$menu = $this->curl->exec('http://proxy.cc.uic.edu/menu');
			$menu = $menu['data'];
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
		
		public function get_in($url)
		{
			$test = $this->curl->check($url);
			
			//log_event_forced($url,$test);
			
			if (strpos($test,'proxy.cc.uic.edu/login')!==false || strpos($test,'HTTP/1.1 303 See Other')!==false)
			{
				if (!$this->enter())
					return false;
				$this->curl->flush_cookie();
				$this->curl->add_cookie_file($this->config_file);
			}
			foreach ($this->curl->cookie as $cookie)
				if ($cookie['name']=='ezproxy')
					return $cookie['name'].'='.$cookie['value'];
		}
		
		function enter()
		{
			log_event_forced("entering proxy...");
		
			$this->curl->flush_cookie();
			
			$max_try = 3;
			while ($max_try>0)
			{
				$login_page = $this->curl->exec('http://proxy.cc.uic.edu/login?url=http://www.nature.com/');
				$login_page = $login_page['data'];
				if (strlen($login_page)==0)
				{
					$max_try--;
					if ($max_try==0)
					{
						$this->disable();
						log_event_forced("login failed for ".$this->name);
						notify($this->name." down");
						return false;
					}
					else
						usleep(rand(2000000,3000000));
				}
				else
					break;
				log_event_forced("trying again...");
			}
			
			$login_page = substr($login_page,strpos($login_page,'method="POST" action="/bluestem/login.cgi"'));
			$post = array();
			$i = 0;
			$pattern = 'value="';
			$i = strpos($login_page,$pattern,$i) + strlen($pattern);
			$post['BSVersionHash'] = substr($login_page,$i,strpos($login_page,'"',$i)-$i);
			$i = strpos($login_page,$pattern,$i) + strlen($pattern);
			$post['BSVersion'] = substr($login_page,$i,strpos($login_page,'"',$i)-$i);
			$i = strpos($login_page,$pattern,$i) + strlen($pattern);
			$post['cacheid'] = substr($login_page,$i,strpos($login_page,'"',$i)-$i);
			$i = strpos($login_page,$pattern,$i) + strlen($pattern);
			$post['msg'] = substr($login_page,$i,strpos($login_page,'"',$i)-$i);
			$i = strpos($login_page,$pattern,$i) + strlen($pattern);
			$post['prior'] = substr($login_page,$i,strpos($login_page,'"',$i)-$i);
			$i = strpos($login_page,$pattern,$i) + strlen($pattern);
			$post['RetrieveURL'] = substr($login_page,$i,strpos($login_page,'"',$i)-$i);
			$i = strpos($login_page,$pattern,$i) + strlen($pattern);
			$post['return'] = substr($login_page,$i,strpos($login_page,'"',$i)-$i);
			$i = strpos($login_page,$pattern,$i) + strlen($pattern);
			$post['proxy'] = substr($login_page,$i,strpos($login_page,'"',$i)-$i);
			$i = strpos($login_page,$pattern,$i) + strlen($pattern);
			$post['proxyrpc'] = substr($login_page,$i,strpos($login_page,'"',$i)-$i);
			$i = strpos($login_page,$pattern,$i) + strlen($pattern);
			$post['RPCURL'] = substr($login_page,$i,strpos($login_page,'"',$i)-$i);
			$i = strpos($login_page,$pattern,$i) + strlen($pattern);
			$post['RPCVersion'] = substr($login_page,$i,strpos($login_page,'"',$i)-$i);
			$post['UserID'] = $this->login;
			$post['password'] = $this->pass;
			
			$test_data = $this->curl->head('https://ness.uic.edu/bluestem/login.cgi', true, $post);
			
			if (!$this->curl->save_cookie_file($this->config_file,'ezproxy'))
			{
				$this->disable();
				log_event_forced("login failed for ".$this->name);
				notify($this->name." down");
				return false;
			}
			else
			{
				log_event_forced("logged in!");
				return true;
			}

		}
		
	}

?>
