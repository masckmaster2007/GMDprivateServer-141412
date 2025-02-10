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

$messages = isset($_POST['messages']) ? Escape::multiple_ids($_POST['messages']) : Escape::number($_POST['messageID']);

$deleteMessages = Library::deleteMessages($accountID, $messages);
if(!$deleteMessages) exit(CommonError::InvalidRequest);

exit(CommonError::Success);
?>