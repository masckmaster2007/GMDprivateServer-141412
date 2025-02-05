<?php
require_once __DIR__."/../lib/mainLib.php";
require_once __DIR__."/../lib/security.php";
require_once __DIR__."/../lib/exploitPatch.php";
require_once __DIR__."/../lib/enums.php";
require_once __DIR__."/../lib/ip.php";
$sec = new Security();

$messagesState = Escape::number($_POST["mS"]);
$friendRequestsState = Escape::number($_POST["frS"]);
$commentsState = Escape::number($_POST["cS"]);
$socialsYouTube = Escape::text($_POST["yt"]);
$socialsTwitter = Escape::text($_POST["twitter"]);
$socialsTwitch = Escape::text($_POST["twitch"]);

$player = $sec->loginPlayer();
if(!$player["success"]) exit(CommonError::InvalidRequest);
$IP = IP::getIP();
$accountID = $player["accountID"];
$userID = $player["userID"];
$userName = $player["userName"];

Library::updateAccountSettings($accountID, $messagesState, $friendRequestsState, $commentsState, $socialsYouTube, $socialsTwitter, $socialsTwitch);
exit(CommonError::Success);
?>