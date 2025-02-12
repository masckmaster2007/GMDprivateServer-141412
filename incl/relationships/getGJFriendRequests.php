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

$friendRequestsString = '';
$page = Escape::number($_POST['page']) ?: 0;
$getSent = Escape::number($_POST['getSent']) ?: 0;
$pageOffset = $page * 10;

$friendRequests = Library::getFriendRequests($accountID, $getSent, $pageOffset);

if(empty($friendRequests['requests'])) exit(CommentsError::NothingFound);

foreach($friendRequests['requests'] AS &$request) {
	$uploadTime = Library::makeTime($request["uploadDate"]);
	
	$request["userName"] = Library::makeClanUsername($request['extID']);
	$request["comment"] = Escape::url_base64_encode(Escape::translit(Escape::url_base64_decode($request["comment"])));
	
	$friendRequestsString .= Library::returnFriendRequestsString($person, $request)."|";
}

exit(rtrim($friendRequestsString, "|")."#".$friendRequests['count'].":".$pageOffset.":10");
?>