<?php

/*
* Created 07-15-2014
* CloudFlare DNS Updated by Nenad Milosavljevic lazarevac@gmail.com
* This script is executed by cron and it detect change of public IP
* If IP is changed it contact CloudFlare via API and update DNS records
* Ensure that you have curl installed ( sudo apt-get install php5-curl )
* API docs: https://www.cloudflare.com/docs/client-api.html
*/

$cf = new cloudFlare();
$result = $cf->setDNSRecords();
if(!empty($result)) {
	$result = trim($result, ", ");
	echo "Updated: {$result}";
}
else
{
	echo "Nothing updated";
}

class cloudFlare {

	private $cf_url        = 'https://www.cloudflare.com/api_json.html';
	private $cf_user       = 'email@gmail.com';
	private $cf_api_key    = 'api_key';
	private $domain        = 'domain.com';
	private $service_mode  = 1;
	private $ttl           = 1;
	private $cf_dns_id     = array('*.nesha.info', 'pi.nesha.info');
	private $my_current_ip = '';

	function __construct()
	{
		$this->my_current_ip = $this->curl('http://myip.dnsomatic.com/');
	}

	public function setDNSRecords() {
		// first get list of dns records and check is update needed
		// echo $this->my_current_ip;

		$dns_records = $this->getDNSRecords();

		$updated = '';
		foreach ($dns_records as $k => $v) {

			if(isset($v['content']) && $v['content'] != $this->my_current_ip) {
				//dns ip is different than current ip, update needed...
				if($this->updateDNS($v)) {
					$updated .= "{$v['name']}, ";
				}
			}
		}

		return $updated;
	}

	private function updateDNS($data = array() )
	{
		$params                 = array();
		$params['a']            = 'rec_edit';
		$params['tkn']          = $this->cf_api_key;
		$params['id']           = $data['rec_id'];
		$params['email']        = $this->cf_user;
		$params['z']            = $this->domain;
		$params['type']         = 'A';
		$params['name']         = $data['name'];
		$params['content']      = $this->my_current_ip;
		$params['service_mode'] = $this->service_mode;
		$params['ttl']          = $this->ttl;

		$res = $this->curl($this->cf_url, $params);
		$res = json_decode($res, true);

		return isset($res['result']) && $res['result'] == 'success' ? true : false;
	}

	// return ID's of DNS records specified in $this->cf_dns_id
	private function getDNSRecords() {

		$params          = array();
		$params['a']     = 'rec_load_all';
		$params['tkn']   = $this->cf_api_key;
		$params['email'] = $this->cf_user;
		$params['z']     = $this->domain;

		$data = $this->curl($this->cf_url, $params);
		$data = json_decode($data, true);

		if(!isset($data['response']['recs']['objs']))
			return false;

		$data = $data['response']['recs']['objs'];
		$result = array();

		foreach ($data as $k => $v) {

			if(!in_array($v['name'], $this->cf_dns_id))
				continue;

			$result[] = $v;
		}

		return $result;
	}

	private function curl($url = '', $params = array()) {
		if($url == '')
			return false;

		$ch = curl_init();
		 
		curl_setopt_array($ch, array(
		    CURLOPT_URL => $url,
		    CURLOPT_RETURNTRANSFER => true,
		    CURLOPT_POST => true,
		    CURLOPT_POSTFIELDS => $params,
		    CURLOPT_FOLLOWLOCATION => true
		));
		 
		$data = curl_exec($ch);
		 
		curl_close($ch);

		return $data;
	}
}


?>
