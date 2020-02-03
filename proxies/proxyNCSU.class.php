<?php

	class proxyNCSU
	{
	
		public $name = 'NCSU';	
		public $domain = '.prox.lib.ncsu.edu';
		var $login = '000756005';
		var $pass = '6005';
		var $config_file = 'ncsu.config.txt';
	
		public function proxyNCSU()
		{
			$this->config_file = dirname(__FILE__).'/'.$this->config_file;
			$this->curl = new curlEx();
			$this->curl->add_cookie_file($this->config_file);
		}
		
		public function ready()
		{
			//return false;
		
		
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
			$domains = explode("\r\n",file_get_contents(dirname(__FILE__).'/ncsu_hosts.txt'));
			$database->fill_proxy($this->name,$domains);
		}
		
		public function get_in($url)
		{
			$test = $this->curl->check($url);
			if (strpos($test,'prox.lib.ncsu.edu/login?qurl=') !== false || strpos($test,'prox.lib.ncsu.edu/login/up?url=') !== false || strpos($test,'prox.lib.ncsu.edu/login?url=') !== false)
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
			log_event("entering proxy...");
		
			$this->curl->flush_cookie();
			
			$this->curl->head('https://shib.ncsu.edu/ds/ncsu/WAYF?entityID=https%3a%2f%2fprox.lib.ncsu.edu%2fezproxy%2fshibboleth&return=https%3a%2f%2fprox.lib.ncsu.edu%2fShibboleth.sso%2fDS%3fSAMLDS%3d1%26target%3dezp.2bWVudQ--');
			$gate_page = $this->curl->exec('https://shib.ncsu.edu/ds/ncsu/WAYF.php?entityID=https%3a%2f%2fprox.lib.ncsu.edu%2fezproxy%2fshibboleth&return=https%3a%2f%2fprox.lib.ncsu.edu%2fShibboleth.sso%2fDS%3fSAMLDS%3d1%26target%3dezp.2bWVudQ--',true,array('Select'=>'Select','user_idp'=>'https://shib2.lib.ncsu.edu/idp/shibboleth'));
			
			$post_gate = array();
			$i = strpos($gate_page['data'],'value="') + 7;
			$post_gate['RelayState'] = trim(substr($gate_page['data'], $i, strpos($gate_page['data'],'"', $i) - $i));
			$i = strpos($gate_page['data'],'value="',$i) + 7;
			$post_gate['SAMLRequest'] = trim(substr($gate_page['data'], $i, strpos($gate_page['data'],'"', $i) - $i));
			$this->curl->exec('https://shib2.lib.ncsu.edu/idp/profile/SAML2/POST/SSO',true,$post_gate);
			
			$gate_page = $this->curl->exec('https://shib2.lib.ncsu.edu/idp/Authn/UserPassword',true,array('j_password'=>$this->pass,'j_username'=>$this->login));
			
			$post_gate = array();
			$i = strpos($gate_page['data'],'value="') + 7;
			$post_gate['RelayState'] = trim(substr($gate_page['data'], $i, strpos($gate_page['data'],'"', $i) - $i));
			$i = strpos($gate_page['data'],'value="',$i) + 7;
			$post_gate['SAMLResponse'] = trim(substr($gate_page['data'], $i, strpos($gate_page['data'],'"', $i) - $i));
			$this->curl->exec('https://prox.lib.ncsu.edu/Shibboleth.sso/SAML2/POST',true,$post_gate);
			
			if (!$this->curl->save_cookie_file($this->config_file,'ezproxy'))
			{
				log_event("login failed for ".$this->name);
				$this->disable();
				notify($this->name." down");
				return false;
			}
			else
			{
				log_event("logged in!");
				return true;
			}

		}
		
	}

?>
