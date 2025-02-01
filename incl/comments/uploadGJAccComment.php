<?php
require_once __DIR__."/../lib/mainLib.php";
require_once __DIR__."/../lib/security.php";
require_once __DIR__."/../lib/exploitPatch.php";
require_once __DIR__."/../lib/enums.php";
require_once __DIR__."/../lib/ip.php";
$sec = new Security();

$IP = IP::getIP();
$gameVersion = Escape::number($_POST['gameVersion']);
$comment = Escape::text($_POST['comment']);
$player = $sec->loginPlayer();
if(!$player["success"]) exit(CommonError::InvalidRequest);
$accountID = $player["accountID"];
$userID = $player["userID"];
$userName = $player["userName"];

if(!empty($comment)) {
	if($gameVersion >= 20) $comment = Escape::url_base64_decode($comment);
	Library::uploadAccountComment($accountID, $userID, $userName, $comment);
	exit(CommonError::Success);
} else exit(CommonError::InvalidRequest);
?>