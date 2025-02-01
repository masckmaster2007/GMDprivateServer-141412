<?php
require_once __DIR__."/../incl/lib/mainLib.php";
require_once __DIR__."/../incl/lib/security.php";
require_once __DIR__."/../incl/lib/exploitPatch.php";
require_once __DIR__."/../incl/lib/enums.php";
require_once __DIR__."/../incl/lib/ip.php";
$sec = new Security();

$IP = IP::getIP();
$saveData = $_POST['saveData'];
if(empty($saveData)) exit(BackupError::SomethingWentWrong);

$player = $sec->loginPlayer();
$accountID = $player["accountID"];
if(!$player["success"]) {
	Library::logAction($accountID, $IP, 7, strlen($saveData));
	exit(CommonError::InvalidRequest);
}

$account = Library::getAccountByID($accountID);

if(!empty($account['salt'])) {
	$salt = $account['salt'];
	$sec->encryptData(__DIR__."/../data/accounts/".$accountID, $saveData, $salt);
} else {
	file_put_contents(__DIR__."/../data/accounts/".$accountID, $saveData);
}
$userName = $player['userName'];
Library::logAction($accountID, $IP, 5, $userName, filesize(__DIR__."/../data/accounts/".$accountID), 0, 0);
exit(BackupError::Success);
?>