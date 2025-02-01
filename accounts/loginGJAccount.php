<?php
require_once __DIR__."/../incl/lib/mainLib.php";
require_once __DIR__."/../incl/lib/security.php";
require_once __DIR__."/../incl/lib/ip.php";
$sec = new Security();

$IP = IP::getIP();
$player = $sec->loginPlayer();

if($player['success']) {
	$accountID = $player['accountID'];
	$userID = $player['userID'];
	Library::logAction($accountID, $IP, 2);
	exit($accountID.','.$userID);
} else {
	$accountID = $player['accountID'];
	$userName = $player['userName'];
	Library::logAction($accountID, $IP, 6);
	exit($player['error']);
}
?>