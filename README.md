Cloud Flare DNS update tool
==========

Cloud Flare DNS update script can be used as Linux or OS X cron, so if you setup it on every 5 minutes, it will fetch your current public IP address, and compare it with your DNS records IP. If they are not the same, DNS will be updated.


How-to
==========

Fetch latest version of cloudflare.php
Modify that file and update this fields 

	$config = [];
    $config['email'] = 'email@gmail.com';
    $config['api_key'] = 'your_api_key';
    $config['domain'] = 'domain.com';
    $config['dns_records'] = ['home.domain.com'];
    
    $dns = new CloudFlare($config);
    echo $dns->run();

Save file for example in your linux home directory

In your Linux terminal type command crontab -e
and add following line 

*/5 * * * * php /path_to_your_dir/cloudflare.php >/dev/null 2>&1

If everything is ok on every 5 minutes your DNS records will be updated with your current public IP.


Comment
==========
I am using Raspberry Pi to host some of my public available web sites. I am using this tool to inform cloudflare when my ISP change my current IP address.
