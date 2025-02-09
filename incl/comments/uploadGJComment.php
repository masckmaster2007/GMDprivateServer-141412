<?php
require_once __DIR__."/../lib/mainLib.php";
require_once __DIR__."/../lib/security.php";
require_once __DIR__."/../lib/commands.php";
require_once __DIR__."/../lib/exploitPatch.php";
require_once __DIR__."/../lib/enums.php";
require_once __DIR__."/../lib/ip.php";
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
	'IP' => $IP,
];

$levelID = Escape::multiple_ids($_POST['levelID']);
$gameVersion = Escape::number($_POST['gameVersion']);
$comment = Escape::text($_POST['comment']);
$percent = Escape::number($_POST['percent']) ?: 0;

if(empty($comment)) exit(CommonError::InvalidRequest);

if($gameVersion >= 20) $comment = Escape::url_base64_decode($comment);

$level = Library::getLevelByID($levelID);
if(!$level) exit(CommonError::InvalidRequest);

$command = Commands::processLevelCommand($comment, $level, $person);
if($command) exit(Library::showCommentsBanScreen($command, 0));

$ableToComment = Library::isAbleToComment($levelID, $accountID, $userID, $IP);
if(!$ableToComment['success']) {
	switch($ableToComment['error']) {
		case CommonError::Banned:
			if($gameVersion < 21) exit(CommonError::Banned);
		
			exit(Library::showCommentsBanScreen(Escape::translit(Escape::url_base64_decode($ableToComment['info']['reason'])), $ableToComment['info']['expires']));
		default:
			exit(Library::showCommentsBanScreen("Commenting on this level is currently disabled!", 0));
	}
}

Library::uploadComment($accountID, $userID, $levelID, $userName, $comment, $percent);

exit(CommonError::Success); 
?>