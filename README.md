Cloud Flare DNS update script
==========

Cloud Flare DNS update script can be used as Linux cron, so if you setup it on every 5 minutes, it will fetch your current public IP address, and compare it with your DNS records IP. If they are not the same, DNS will be updated.


How-to
==========

Fetch latest version of cloudflare.php
Modify that file and update this fields 

	private $cf_user       = 'email@gmail.com'; // your CF login email
	private $cf_api_key    = '123456';
	private $domain        = 'domain.info'; // CF API Key, find it in Account section of CF site
	private $service_mode  = 1; // Status of CF Proxy, 1 = orange cloud, 0 = grey cloud
	private $ttl           = 1; // TTL of record in seconds. 1 = Automatic, otherwise, value must in between 120 and 86400 seconds.
	private $cf_dns_id     = array('*.domain.info', 'pi.domain.info'); // DNS records you have already available on CF site

Save file for example in your linux home directory

In your Linux terminal type command crontab -e
and add following line 

*/5 * * * * php /path_to_your_dir/cloudflare.php >/dev/null 2>&1

If everything is ok on every 5 minutes your DNS records will be updated with your current public IP.


Comment
==========
I am using Raspberry Pi to host some of my public available web sites. This script help me to inform cloudflare when my ISP change my current IP address.
