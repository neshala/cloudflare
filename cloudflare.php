<?php

/*
* Created 07-15-2014
* Updated 08-19-2016
* CloudFlare DNS Tool Created by Nenad Milosavljevic lazarevac@gmail.com
* This script is executed by cron and it detect change of public IP
* If IP is changed it contact CloudFlare via API and update DNS records
* Ensure that you have curl installed ( sudo apt-get install php5-curl )
* API docs: https://api.cloudflare.com
*/

$config = [];
$config['email'] = 'email@gmail.com';
$config['api_key'] = '4645645645';
$config['domain'] = 'domain.com';
$config['dns_records'] = ['home.domain.com'];

$dns = new CloudFlare($config);
echo $dns->run();

class CloudFlare
{
    private $api_url = 'https://api.cloudflare.com/client/v4/';
    private $email = '';
    private $api_key = '';
    private $domain = '';
    private $dns_records = [];
    private $my_current_ip = '';

    public function __construct($config = [])
    {
        if (!isset($config['email'])) {
            throw new Exception('Please provide email.');
        }
        if (!isset($config['api_key'])) {
            throw new Exception('Please provide api_key.');
        }
        if (!isset($config['domain'])) {
            throw new Exception('Please provide domain.');
        }
        if (!isset($config['dns_records'])) {
            throw new Exception('Please provide DNS Records.');
        }
        $this->email = $config['email'];
        $this->api_key = $config['api_key'];
        $this->domain = $config['domain'];
        $this->dns_records = $config['dns_records'];
        $this->my_current_ip = $this->curl('http://myip.dnsomatic.com/', [], 'GET');
    }

    private function curl($url = '', $params = [], $method = 'GET', $headers = [], $json = false)
    {
        if (empty($url)) {
            throw new Exception('Invalid URL.');
        }

        $methods_allowed = ['GET', 'PUT', 'POST', 'DELETE', 'PATCH'];
        if (!in_array($method, $methods_allowed)) {
            throw new Exception('Invalid Method.');
        }

        $curl_options = [
            CURLOPT_VERBOSE => false,
            CURLOPT_FORBID_REUSE => true,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_HEADER => false,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_FOLLOWLOCATION => true,
        ];

        $ch = curl_init();
        curl_setopt_array($ch, $curl_options);

        $json_data = '';
        if ($json) {
            $headers[] = 'Content-type: application/json';
            $json_data = json_encode($params);
        }

        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
        } elseif ($method === 'PUT') {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        } elseif ($method === 'DELETE') {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        } elseif ($method === 'PATCH') {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
        } else {
            $url .= '?' . http_build_query($params);
        }

        curl_setopt($ch, CURLOPT_URL, $url);

        $data = curl_exec($ch);
        $error = curl_error($ch);

        curl_close($ch);

        if (!empty($error)) {
            throw new Exception('Curl Error.');
        }

        if ($json) {
            $data = json_decode($data, true);
        }

        return $data;
    }

    /**
     * Check if specified DNS records needs to be update, and update them on Cloud Flare
     * @return string
     * @throws Exception
     */
    public function run()
    {
        $dns_records = $this->getDNSRecords();

        $updated = [];
        foreach ($dns_records as $k => $v) {
            if (isset($v['content']) && $v['content'] != $this->my_current_ip) {
                if ($this->updateDNS($v)) {
                    $updated[] = $v['name'];
                }
            }
        }

        $res = !empty($updated) ? "Updated with IP " . $this->my_current_ip . " => " . join(',', $updated) . '.' : "Nothing updated.";

        return $res;
    }

    /**
     * Fetch ID's of DNS Zone and records specified
     * @return array
     * @throws Exception
     */
    private function getDNSRecords()
    {
        $zone_id = $this->getZoneID();

        $url = $this->api_url . 'zones/' . $zone_id . '/dns_records/';
        $data = $this->curl($url, [], 'GET', $this->getHeaders(), true);

        $result = [];

        if (!isset($data['result'])) {
            return $result;
        }

        foreach ($data['result'] as $record) {
            if (!in_array($record['name'], $this->dns_records)) {
                continue;
            }

            $result[] = $record;
        }

        return $result;
    }

    /**
     * Get zone ID from Cloud Flare for specified domain
     * @return string
     * @throws Exception
     */
    private function getZoneID()
    {
        $params = [];
        $params['name'] = $this->domain;

        $url = $this->api_url . 'zones/';
        $data = $this->curl($url, $params, 'GET', $this->getHeaders(), true);
        $zone_id = '';
        if (isset($data['result']) && is_array($data['result'])) {
            foreach ($data['result'] as $record) {
                if ($record['name'] == $this->domain) {
                    $zone_id = $record['id'];
                    break;
                }
            }
        }

        if (empty($zone_id)) {
            throw new Exception('Invalid Domain specified. Unable to find zone ID');
        }

        return $zone_id;
    }

    private function getHeaders()
    {
        if (!isset($this->email) || !isset($this->api_key) || false === filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Invalid Authentication data.');
        }

        $headers = ["X-Auth-Email: {$this->email}", "X-Auth-Key: {$this->api_key}"];

        return $headers;
    }

    // fetch remote data via curl

    /**
     * Update single DNS record
     * @param array $record DNS record info
     * @return bool|mixed
     * @throws Exception
     */
    private function updateDNS($record = [])
    {
        $params = [];
        $params['name'] = $record['name'];
        $params['type'] = $record['type'];
        $params['content'] = $this->my_current_ip;

        $url = $this->api_url . 'zones/' . $record['zone_id'] . '/dns_records/' . $record['id'];
        $res = $this->curl($url, $params, 'PUT', $this->getHeaders(), true);
        $res = isset($res['success']) && $res['success'] ? true : false;

        return $res;
    }
}
