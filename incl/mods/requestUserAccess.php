<?php
require_once __DIR__."/../lib/mainLib.php";
require_once __DIR__."/../lib/exploitPatch.php";
require_once __DIR__."/../lib/security.php";
require_once __DIR__."/../lib/ip.php";
require_once __DIR__."/../lib/enums.php";
$sec = new Security();

$player = $sec->loginPlayer();
if(!$player["success"]) exit(CommonError::InvalidRequest);
$IP = IP::getIP();
$accountID = $player["accountID"];
$userID = $player["userID"];
$userName = $player["userName"];
$person = [
	'accountID' => $accountID,
	'userID' => $userID,
	'IP' => $IP
];

$appearance = Library::getPersonCommentAppearance($person);

$badge = min(2, $appearance['modBadgeLevel']);

exit((string)$badge);
?>