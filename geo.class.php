<?php

class geo
{

	function geo()
	{
		$this->curl = curl_init();
		curl_setopt($this->curl, CURLOPT_HEADER, 0);
		curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($this->curl, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
		curl_setopt($this->curl, CURLOPT_SSL_VERIFYPEER, false);
	}
	
	function __destruct()
	{
		curl_close($this->curl);
	}
	
	public function get_data($ip_address, $strict = false)
	{
		$this->ip = $ip_address;
		$data = $this->geo_service_2();
		if ($data==false || $data['country']=='*' || ($data['city']=='*' && $strict))
		{
			$odd = rand(3,7);
			if ($odd==7)
				if (($tmp = $this->geo_service_3()) !== false)
					$data = $tmp;
		}
		if ($data==false || $data['country']=='*' || ($data['city']=='*' && $strict))
			if (($tmp = $this->geo_service_1()) !== false)
				$data = $tmp;
		if ($data==false || $data['country']=='*' || ($data['city']=='*' && $strict))
			if (($tmp = $this->geo_service_3()) !== false)
				$data = $tmp;
		if ($data==false)
			$data = array('*','*');
		if (strpos($data['country'],'russian')!==false)
			$data['country'] = 'russia';
		return $data;
	}
	
	protected function geo_service_1()
	{
		$url = 'http://api.quova.com/v1/ipinfo/'.$this->ip.'?apikey=100.uufya9bjvynm5ypkgbrc&format=json&sig='.md5('100.uufya9bjvynm5ypkgbrcj58Kuaxd'.gmdate('U'));
		curl_setopt($this->curl, CURLOPT_URL, $url);
		$data = curl_exec($this->curl);
		$headers = curl_getinfo($this->curl);
		if ($headers['http_code']!='200')
			return false;
		usleep(500000);
		$data = json_decode($data);
		return array(
						'country'	=> $data->ipinfo->Location->CountryData->country,
						'city'		=> $data->ipinfo->Location->CityData->city
					);
	}
	
	protected function geo_service_2()
	{
		curl_setopt($this->curl, CURLOPT_URL, "http://api.hostip.info/get_html.php?ip=".$this->ip);
		$data = curl_exec($this->curl);
		if ($data==false)
			return false;
		$data = explode("\n",$data);
		$data[0] = explode(": ",$data[0]);
		$data[0] = explode(" ",$data[0][1]);
		array_pop($data[0]);
		$data[0] = implode(" ",$data[0]);
		$data[1] = explode(": ",$data[1]);
		if (strpos($data[1][1],'Unknown')!==false)
			$data[1][1] = '*';
		if (strpos($data[0],'Unknown')!==false)
			$data[0] = '*';
		return array(
						'country'	=> strtolower(trim($data[0])),
						'city'		=> strtolower(trim($data[1][1]))
					);
	}
	
	public function geo_service_3()
	{
		curl_setopt($this->curl, CURLOPT_URL, "http://whatismyipaddress.com/ip/".$this->ip);
		$data = curl_exec($this->curl);
		if ($data==false)
			return false;
		$i = strpos($data,'Geolocation Information</h2>');
		if ($i===false) return false;
		$i = strpos($data, 'Country', $i + 28);
		if ($i===false) return false;
		$i +=  17;
		$country = strtolower(trim(substr($data,$i, strpos($data,'<',$i) - $i)));
		if (strlen($country)==0)
			$country = '*';
		$i = strpos($data, 'City', $i);
		if ($i===false)
			$city = '';
		else
		{
			$i += 14;
			$city = strtolower(trim(substr($data,$i, strpos($data,'<',$i) - $i)));
		}
		if (strlen($city)==0)
			$city = '*';
		usleep(500000);
		return array(
						'country'	=> $country,
						'city'		=> $city
					);
	}
	
}
