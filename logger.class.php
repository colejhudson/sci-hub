<?php

class logger
{

	function logger()
	{
		$this->service = 'http://ringo-ring.info/support/sci-hub/logger/remember.php';
		$this->data_key = 'jw39ns23gf';
		$this->data_sep = '#####';
		$this->curl = curl_init();
		curl_setopt($this->curl, CURLOPT_HEADER, 0);
		curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($this->curl, CURLOPT_CONNECTTIMEOUT, 3);
		curl_setopt($this->curl, CURLOPT_TIMEOUT, 5);
	}
	
	function __destruct()
	{
		curl_close($this->curl);
	}
	
	public function add($data)
	{
		$data = implode($this->data_sep,array_values($data));
		$data = base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, md5($this->data_key), $data, MCRYPT_MODE_CBC, md5(md5($this->data_key))));
		curl_setopt($this->curl, CURLOPT_URL, $this->service);
		curl_setopt($this->curl, CURLOPT_POST, true);
		curl_setopt($this->curl, CURLOPT_POSTFIELDS, "data=".urlencode($data));
		curl_exec($this->curl);
	}

}

?>
