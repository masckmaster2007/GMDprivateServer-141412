<?php
/*
	Submissions by unregistered accounts
	
	Should unregistered accounts be able to upload levels, post comments, etc. Required for <1.9 GDPSs:
		True — unregistered accounts can interact with GDPS
		False — only registered accounts can interact with GDPS
*/
$unregisteredSubmissions = false;

/*
	Preactivate accounts
	
	Should new accounts already be registered:
		True — all new accounts are automatically registered
		False — new accounts must be activated through activate page (dashboard/login/activate.php) or email message
*/
$preactivateAccounts = true;

/*
	Debug mode

	Show errors on page if hosting supports it
		True — show errors
		False — disable errors reporting (recommended for production)
*/
$debugMode = true;

/*
	Filters for various places
	
	$filterUsernames — method of filtering usernames:
		0 — disabled
		1 — checks if username is the word
		2 — checks if username contains the word
	$bannedUsernames — list of banned words in usernames
	
	$filterClanNames — method of filtering clan names:
		0 — disabled
		1 — checks if clan name is the word
		2 — checks if clan name contains the word
	$bannedClanNames — list of banned words in clan names
	
	$filterClanTags — method of filtering clan tags:
		0 — disabled
		1 — checks if clan tag is the word
		2 — checks if clan tag contains the word
	$bannedClanTags — list of banned words in clan tags
*/
$filterUsernames = 2;
$bannedClanTags = [ // Add words to ban if it is a username/if it is in a username
	'RobTop',
	'nig',
	'fag'
];

$filterClanNames = 2; // 0 = Disabled, 1 = Checks if the clan name is word, 2 = Checks if the clan name contains word
$bannedClanNames = [ // Add words to ban if it is a clan name/if it is in a clan name
	'Support',
	'Administration',
	'Moderation',
	'nig',
	'fag'
];

$filterClanTags = 2; // 0 = Disabled, 1 = Checks if the clan tag is word, 2 = Checks if the clan tag contains word
$bannedClanTags = [ // Add words to ban if it is a clan tag/if it is in a clan tag
	'ADMIN',
	'MOD',
	'nig',
	'fag'
];

/*
	Captcha settings
	
	$enableCaptcha — should captcha be enabled:
		True — captcha is enabled, you must configure next three variables
		False — captcha is disabled
	$captchaType — captcha provider:
		1  — hCaptcha: https://www.hcaptcha.com/
		2 — reCaptcha: https://www.google.com/recaptcha/
		3 — Cloudflare Turnstile: https://www.cloudflare.com/products/turnstile/
	$CaptchaKey — public captcha key
	$CaptchaSecret — private captcha key, must not be shared with anyone
*/

$enableCaptcha = false;
$captchaType = 1;
$CaptchaKey = "";
$CaptchaSecret = "";

/*
	Block access from free proxies and common VPNs

	Below are URLs for proxies and VPSs
	Should only return list of IPs without any other HTML code

	Syntax: $proxies['NAME OF IPs'] = 'LINK';
*/

$blockFreeProxies = true; // true = check if person uses free proxy
$blockCommonVPNs = true; // true = check if person uses a common VPN
// URLs for IPs of proxies
$proxies['http'] = 'https://fhgdps.com/proxies/http.txt';
$proxies['https'] = 'https://fhgdps.com/proxies/https.txt';
$proxies['socks4'] = 'https://fhgdps.com/proxies/socks4.txt';
$proxies['socks5'] = 'https://fhgdps.com/proxies/socks5.txt';
$proxies['unknown'] = 'https://fhgdps.com/proxies/unknown.txt';
// URLs for IP ranges of VPNs
$vpns['vpn'] = 'https://raw.githubusercontent.com/X4BNet/lists_vpn/main/output/vpn/ipv4.txt';

/*
	GDPS automod config

	$warningsPeriod — period of time in seconds, when new warnings of same type won't show to prevent warn spamming

	$levelsCountModifier — modifier to yesterday levels count to avoid small levels increase warning
		if(Levels today > Levels yesterday * Levels modifier) WARNING;
	$levelsCheckPeriod — what period of time in seconds to check

	$accountsCountModifier — modifier to yesterday accounts count to avoid small accounts increase warning
		if(Accounts today > Accounts yesterday * Accounts modifier) WARNING;
	$accountsCheckPeriod — what period of time in seconds to check

	$commentsCheckPeriod — comments posted in this period of time in seconds will be checked
		600 is 10 minutes, so comments posted in last 10 minutes would be checked

	$globalLevelsUploadDelay — if last level was uploaded X seconds ago, new one can't be uploaded.
	$perUserLevelsUploadDelay — if last level by some user was uploaded X seconds ago, new one can't be uploaded.
*/

$warningsPeriod = 86400;

$levelsCountModifier = 1.3;
$levelsCheckPeriod = 86400;

$accountsCountModifier = 1.3;
$accountsCheckPeriod = 86400;

$commentsCheckPeriod = 600;

$globalLevelsUploadDelay = 2;
$perUserLevelsUploadDelay = 5;
?>
