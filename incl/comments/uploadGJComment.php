<?php
require_once __DIR__."/../lib/mainLib.php";
require_once __DIR__."/../lib/security.php";
require_once __DIR__."/../lib/commands.php";
require_once __DIR__."/../lib/exploitPatch.php";
require_once __DIR__."/../lib/enums.php";
$sec = new Security();

$player = $sec->loginPlayer();
if(!$player["success"]) exit(CommonError::InvalidRequest);
$accountID = $player["accountID"];
$userID = $player["userID"];
$userName = $player["userName"];

$levelID = Escape::multiple_ids($_POST['levelID']);
$gameVersion = Escape::number($_POST['gameVersion']);
$comment = Escape::text($_POST['comment']);
$percent = Escape::number($_POST['percent']) ?: 0;

if(empty($comment)) exit(CommonError::InvalidRequest);

if($gameVersion >= 20) $comment = Escape::url_base64_decode($comment);
$command = Commands::processLevelCommand($comment, $levelID, $accountID);
if($command) exit(Library::showCommentsBanScreen($command, 0));
Library::uploadComment($accountID, $userID, $levelID, $userName, $comment, $percent);
exit(CommonError::Success); 
?>