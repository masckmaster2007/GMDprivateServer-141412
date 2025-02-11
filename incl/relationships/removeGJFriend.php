<?php
require_once __DIR__."/../lib/mainLib.php";
require_once __DIR__."/../lib/exploitPatch.php";
require_once __DIR__."/../lib/security.php";
require_once __DIR__."/../lib/enums.php";
$sec = new Security();

$player = $sec->loginPlayer();
if(!$player["success"]) exit(CommonError::InvalidRequest);
$accountID = $player["accountID"];
$userID = $player["userID"];
$userName = $player["userName"];

$targetAccountID = Escape::number($_POST['targetAccountID']);

$removeFriend = Library::removeFriend($accountID, $targetAccountID);
if(!$removeFriend) exit(CommonError::InvalidRequest);

exit(CommonError::Success);
?>