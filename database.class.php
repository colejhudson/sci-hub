<?php

class database
{

	public function database()
	{
		$this->connect_db();
		$this->get_input();
	}
	
	public function domain_check($domain)
	{
		if (substr($domain,0,4)=='www.')
			$domain = substr($domain,4);
			
		$query = "SELECT DISTINCT proxy_name FROM proxys AS p JOIN proxy_domain AS pd JOIN domains AS d ON ('".$domain."' LIKE CONCAT('%.',`domain_name`) OR `domain_name` LIKE '".$domain."') AND d.domain_id=pd.domain_id AND p.proxy_id=pd.proxy_id";
		$cache_i = md5($query);
		
		if (isset($_SESSION['cache'][$cache_i]))
			return $_SESSION['cache'][$cache_i];
		
		$q = mysql_query($query);
		$good = array();
		while ($r = mysql_fetch_row($q))
			$good[] = $r[0];
		
		if (!isset($_SESSION['cache'])) $_SESSION['cache'] = array();
		$_SESSION['cache'][$cache_i] = $good;
		
		return $good;
	}
	
	public function fill_proxy($proxy,$domains)
	{
		$q = mysql_query("INSERT INTO proxys(proxy_name) VALUES ('".$proxy."') ON DUPLICATE KEY UPDATE proxy_id = LAST_INSERT_ID(proxy_id)");
		$proxy_id = mysql_insert_id();
		foreach ($domains as $domain)
		{
			$q = mysql_query("INSERT INTO domains(domain_name) VALUES ('".$domain."') ON DUPLICATE KEY UPDATE domain_id = LAST_INSERT_ID(domain_id)");
			$domain_id = mysql_insert_id();
			mysql_query("INSERT IGNORE INTO proxy_domain(proxy_id,domain_id) VALUES (".$proxy_id.",".$domain_id.")");
		}
	}
	
	private function connect_db()
	{
		if (!mysql_connect($GLOBALS['db_server'], $GLOBALS['db_login'], $GLOBALS['db_pass']))
			return false;
		if (!mysql_select_db($GLOBALS['db_name']))
			return false;
		mysql_query("SET SESSION character_set_results = utf8;");
		mysql_query("SET SESSION Character_set_client = utf8;");
		mysql_query("SET SESSION Character_set_results = utf8;");
		mysql_query("SET SESSION Collation_connection = utf8_unicode_ci;");
		mysql_query("SET SESSION Character_set_connection = utf8;");
	}
	
	private function get_input()
	{
		$this->input = array();
		foreach ($_POST as $k => $v)
			$this->input[$k] = mysql_real_escape_string($v);
		foreach ($_GET as $k => $v)
			$this->input[$k] = mysql_real_escape_string($v);
	}
	
}

?>
