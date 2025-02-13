<?php
require_once __DIR__."/../lib/mainLib.php";
require_once __DIR__."/../lib/security.php";
require_once __DIR__."/../lib/exploitPatch.php";
require_once __DIR__."/../lib/enums.php";
$sec = new Security();

$person = $sec->loginPlayer();
if(!$person["success"]) exit(CommonError::InvalidRequest);

$messagesState = Escape::number($_POST["mS"]);
$friendRequestsState = Escape::number($_POST["frS"]);
$commentsState = Escape::number($_POST["cS"]);
$socialsYouTube = Escape::text($_POST["yt"]);
$socialsTwitter = Escape::text($_POST["twitter"]);
$socialsTwitch = Escape::text($_POST["twitch"]);

Library::updateAccountSettings($person, $messagesState, $friendRequestsState, $commentsState, $socialsYouTube, $socialsTwitter, $socialsTwitch);
exit(CommonError::Success);
?>