Cloud Flare DNS update script
==========

Cloud Flare DNS update script can be used as Linux cron, so if you setup it on every 5 minutes, it will fetch your current public IP address, and compare it with your DNS records IP. If they are not the same, DNS will be updated.


How-to
==========

Fetch latest version of cloudflare.php
Modify that file and update this fields 

	private $cf_user       = 'email@gmail.com';
	private $cf_api_key    = '123456';
	private $domain        = 'domain.info';
	private $service_mode  = 1;
	private $ttl           = 1;
	private $cf_dns_id     = array('*.domain.info', 'pi.domain.info');
	private $my_current_ip = '';

Save file for example in your linux home directory

In your Linux terminal type command crontab -e
and add following line 

*/5 * * * * php /path_to_your_dir/cloudflare.php >/dev/null 2>&1

If everything is ok on every 5 minutes your DNS records will be updated with your current public IP.


Comment
==========
I am using Raspberry Pi to host some of my public available web sites. This script help me to inform cloudflare when my ISP change my current IP address.
