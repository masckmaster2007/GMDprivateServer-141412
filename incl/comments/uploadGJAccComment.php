<?php
require_once __DIR__."/../lib/mainLib.php";
require_once __DIR__."/../lib/security.php";
require_once __DIR__."/../lib/exploitPatch.php";
require_once __DIR__."/../lib/enums.php";
$sec = new Security();

$player = $sec->loginPlayer();
if(!$player["success"]) exit(CommonError::InvalidRequest);
$accountID = $player["accountID"];
$userID = $player["userID"];
$userName = $player["userName"];

$gameVersion = Escape::number($_POST['gameVersion']);
$comment = Escape::text($_POST['comment']);

if(empty($comment)) exit(CommonError::InvalidRequest);

if($gameVersion >= 20) $comment = Escape::url_base64_decode($comment);
Library::uploadAccountComment($accountID, $userID, $userName, $comment);
exit(CommonError::Success); 
?>