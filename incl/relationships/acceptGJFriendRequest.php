<?php
require_once __DIR__."/../lib/mainLib.php";
require_once __DIR__."/../lib/exploitPatch.php";
require_once __DIR__."/../lib/security.php";
require_once __DIR__."/../lib/enums.php";
$sec = new Security();

$player = $sec->loginPlayer();
if(!$player["success"]) exit(CommonError::InvalidRequest);
$IP = IP::getIP();
$accountID = $player["accountID"];
$userID = $player["userID"];
$userName = $player["userName"];

$requestID = Escape::number($_POST['requestID']);

$acceptFriendRequest = Library::acceptFriendRequest($accountID, $requestID);
if(!$acceptFriendRequest) exit(CommonError::InvalidRequest);

exit(CommonError::Success);
?>