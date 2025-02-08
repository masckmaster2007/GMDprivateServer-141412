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

$itemID = Escape::multiple_ids($_POST['itemID']) ?: Escape::number($_POST['levelID']);
$type = Escape::number($_POST['type']) ?: 1;
$isLike = Escape::number($_POST['like']);

if(!$itemID) exit(CommonError::InvalidRequest);

$rateItem = Library::rateItem($accountID, $itemID, $type, $isLike);
if(!$rateItem) exit(CommonError::InvalidRequest);

exit(CommonError::Success);
?>