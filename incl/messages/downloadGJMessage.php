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

$messageID = Escape::number($_POST["messageID"]);
$isSender = Escape::number($_POST["isSender"]) ?: 0;

$message = Library::readMessage($accountID, $messageID, $isSender);
if(!$message) exit(CommonError::InvalidRequest);

$uploadDate = Library::makeTime($message["timestamp"]);

$message["userName"] = Library::makeClanUsername($message['extID']);

exit("6:".$message["userName"].":3:".$message["userID"].":2:".$message["extID"].":1:".$message["messageID"].":4:".$message["subject"].":8:".$message["isNew"].":9:".$isSender.":5:".$message["body"].":7:".$uploadDate);
?>