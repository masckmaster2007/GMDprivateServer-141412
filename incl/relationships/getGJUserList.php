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

$usersString = '';
$type = Escape::number($_POST["type"]);

switch($type) {
	case 0:
		$isBlocks = false;
		$users = Library::getFriendships($accountID);
		break;
	case 1:
		$isBlocks = true;
		$users = Library::getBlocks($accountID);
		break;
	case 2:
		exit(CommonError::InvalidRequest);
	
}

if(empty($users)) exit(CommentsError::NothingFound);

foreach($users AS &$user) $usersString .= Library::returnFriendshipsString($person, $user, $isBlocks)."|";

exit(rtrim($usersString, "|"));
?>