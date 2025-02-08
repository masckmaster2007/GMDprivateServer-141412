<?php
$installed = true; // Like i said, it changed!

/*
	Main dashboard settings
	
	$gdps — GDPS name, shows in title and various dashboard and Discord webhooks
	$lrEnabled — are level reupload tools enabled or not
		True — level reupload tools are enabled
		False — level reupload tools are disabled
	$msgEnabled — is messenger page on dashboard enabled or not
		True — messenger page is enabled
		False — messenger page is disabled
	$clansEnabled — are clans enabled or not
		True — clans are enabled
		False — clans are disabled
	$songEnabled — are song upload tools enabled or not
		Add 1 — upload songs with file is enabled
		Add 2 — upload songs via URL is enabled
	$sfxEnabled — is SFX upload tool enabled
		True — SFX upload tool is enabled
		False — SFX upload tool is disabled
	$convertEnabled — is converting new SFXs enabled
		True — converting new SFXs is enabled
		False — converting new SFXs is disabled
	$songSize — maximum song size user can upload via file
	$sfxSize — maximum SFX size user can upload via file
	$timeType — how time shows in-game
		0 — default time, as in Cvolton's core (06/02/2024 22.18)
		1 — default dashboard time (depends on time since, today — 22;18, in this year — 02.06, older — 02.06.2024)
		2 — time as in real Geometry Dash (2 weeks ago)
	$dashboardIcon — icon in dashboard's navbar, can be link
	$dashboardFavicon — icon in browser's title, can be link
	$preenableSongs — should core autoenable songs when uploading new one?
		True — enable new songs
		False — disable new songs (requires moderator input to enable)
	$preenableSongs — should core autoenable SFXs when uploading new one?
		True — enable new SFXs
		False — disable new SFXs (requires moderator input to enable)
*/
$gdps = "GDPS";
$lrEnabled = true;
$msgEnabled = true;
$clansEnabled = true;
$songEnabled = 12;
$sfxEnabled = true;
$convertEnabled = true;
$songSize = 8;
$sfxSize = 4.5;
$timeType = 1;
$dashboardIcon = '/dashboard/icon.png';
$dashboardFavicon = '/dashboard/icon.png';
$preenableSongs = true;
$preenableSFXs = true;

/*
	Download links
	
	$pc — link to download GDPS for Windows
	$android — link to download GDPS for Android
	$mac — link to download GDPS for Mac OS
	$ios — link to download GDPS for iOS
*/
$pc = 'download/'.$gdps.'.zip';
$android = 'download/'.$gdps.'.apk';
$mac = 'download/'.$gdps.'.dmg';
$ios = 'download/'.$gdps.'.ipa';

/*
	Footer social links
	
	$footer[] = ['title', 'link to social', 'icon url'];
*/

// $footer[] = ['', '', ''];

/*
	Third-party resources
	
	$thirdParty[] = ['icon url', 'username', 'social media link', 'what person did'];
*/
$thirdParty[] = ['https://yt3.googleusercontent.com/EZ149IVvU5JX2Fi6yH7R95NQmKdNsea_gggEvJXA0MIZQ397E_WHLLNCgBjL45npnMZNUkpq=s88-c-k-c0x00ffffff-no-rj', 'RobTop', 'https://store.steampowered.com/app/322170/Geometry_Dash/', 'For Geometry Dash'];
$thirdParty[] = ['https://avatars.githubusercontent.com/u/5721187', 'Cvolton', 'https://github.com/Cvolton', 'For GDPS code'];
$thirdParty[] = ['https://avatars.githubusercontent.com/u/52624723', 'Foxodever', 'https://github.com/foxodever/BetterCvoltonGDPS/blob/main/tools/songs/upload.php', 'For file upload script'];

/*
	Custom music and SFX libraries
	
	$customLibrary[] = ['library ID (number, must be unique)', 'library name', 'library link', 'library type'];
	Library types:
		0 — only SFX library
		1 — only music library
		1 — both
*/
$customLibrary[] = array(1, 'Geometry Dash', 'https://geometrydashfiles.b-cdn.net', 2); 
$customLibrary[] = array(2, 'GDPSFH', 'https://sfx.fhgdps.com', 0); 
$customLibrary[] = array(3, $gdps, null, 2); // Your GDPS's library, don't remove it
$customLibrary[] = array(4, 'Song File Hub', 'https://api.songfilehub.com', 1);

/*
	SFX converter API's
	https://github.com/MegaSa1nt/GDPS-ConvertSFX
	
	$convertSFXAPI[] = "link to converter";
*/
$convertSFXAPI[] = "https://niko.gcs.icu";
$convertSFXAPI[] = "https://lamb.gcs.icu";
$convertSFXAPI[] = "https://omori.gcs.icu"; // You're welcome
$convertSFXAPI[] = "https://im.gcs.icu";
$convertSFXAPI[] = "https://hat.gcs.icu";
$convertSFXAPI[] = "https://converter.m336.dev";

/*
	Level reupload tool
	
	These confing will allow you to customize level reupload tool
	
	$requireAccountForReuploading — if user must enter their account credentials to reupload level
		True — require logging in
		False — don't require to login
	$disallowReuploadingNotUserLevels — if user should be allowed to reupload only their levels
		True — allow reuploading only their levels
		False — allow reuploading any levels
*/
$requireAccountForReuploading = false;
$disallowReuploadingNotUserLevels = false;

/*
	Cobalt API
	
	Use Cobalt API to be able to reupload songs with YouTube links and etc.
	Requires file upload to be enabled!
	
	$useCobalt — Should server use Cobalt to reupload songs by links
		True — use Cobalt
		False — don't use Cobalt

	$cobaltAPI[] — links to Cobalt's APIs
		Server will randomly pick one of Cobalt APIs when reuploading song
		
	Turnstile-protected APIs are currently not supported, sorry
*/
$useCobalt = true;
$cobaltAPI[] = 'https://cobalt.gcs.icu';

/*
	Geometry Dash icons renderer Server
	
	Dashboard shows icons of players, therefore it requires some server to get icons
	
	$iconsRendererServer — what server to use
	
	If gdicon.oat.zone doesn't work for you for some reason, you can use icons.gcs.icu
*/
$iconsRendererServer = 'https://gdicon.oat.zone';
?>
