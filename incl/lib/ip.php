<?php
class IP {
	public function ipv4inrange($ip, $range) {
		require_once __DIR__ . "/ip_in_range.php";
		return ipInRange::ipv4_in_range($ip, $range);
	}
	
	public static function ip2long6($ip) {
		require_once __DIR__ . "/ip_in_range.php";
		return ipInRange::ip2long6($ip, $range);
	}
	
	public static function ipv6_in_range($ip, $range_ip) {
		require_once __DIR__ . "/ip_in_range.php";
		return ipInRange::ipv6_in_range($ip, $range_ip);
	}
	
	public function isCloudFlareIP($ip) {
		$cf_ipv4s = array(
			'173.245.48.0/20',
			'103.21.244.0/22',
			'103.22.200.0/22',
			'103.31.4.0/22',
			'141.101.64.0/18',
			'108.162.192.0/18',
			'190.93.240.0/20',
			'188.114.96.0/20',
			'197.234.240.0/22',
			'198.41.128.0/17',
			'162.158.0.0/15',
			'104.16.0.0/13',
			'104.24.0.0/14',
			'172.64.0.0/13',
			'131.0.72.0/22'
		);
		$cf_ipv6s = array(
			'2400:cb00::/32',
			'2606:4700::/32',
			'2803:f800::/32',
			'2405:b500::/32',
			'2405:8100::/32',
			'2a06:98c0::/29',
			'2c0f:f248::/32'
		);
		foreach($cf_ipv4s as $cf_ip) {
			if($this->ipv4inrange($ip, $cf_ip)) {
				return true;
			}
		}
		foreach($cf_ipv6s as $cf_ip) {
			if($this->ipv6_in_range($ip, $cf_ip)) {
				return true;
			}
		}
		return false;
	}
	
	public static function getIP() {
		/*
			Cloudflare reverse proxy
		*/
		if(isset($_SERVER['HTTP_CF_CONNECTING_IP']) && $this->isCloudFlareIP($_SERVER['REMOTE_ADDR'])) return $_SERVER['HTTP_CF_CONNECTING_IP'];
		
		/*
			Localhost reverse proxy (mostly 7m.pl)
		*/
		if(isset($_SERVER['HTTP_X_FORWARDED_FOR']) && $this->ipv4inrange($_SERVER['REMOTE_ADDR'], '127.0.0.0/8')) return $_SERVER['HTTP_X_FORWARDED_FOR'];
		
		/*
			141412.xyz support
		*/
		if(isset($_SERVER['HTTP_X_FORWARDED_FOR']) && $_SERVER['REMOTE_ADDR'] == "10.0.1.10") return explode(",", $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
		
		/*
			Direct access to server
		*/
		return $_SERVER['REMOTE_ADDR'];
	}
}
?>