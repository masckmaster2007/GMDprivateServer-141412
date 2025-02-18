<?php
require_once __DIR__."/../incl/lib/mainLib.php";
require_once __DIR__."/../incl/lib/security.php";
require_once __DIR__."/../incl/lib/exploitPatch.php";
require_once __DIR__."/../incl/lib/enums.php";
$sec = new Security();

$saveData = $_POST['saveData'];
if(empty($saveData)) exit(BackupError::SomethingWentWrong);

$person = $sec->loginPlayer();
if(!$person["success"]) {
	Library::logAction($person, Action::FailedAccountBackup, strlen($saveData));
	exit(CommonError::InvalidRequest);
}

$accountID = $person["accountID"];
$userName = $person['userName'];
$account = Library::getAccountByID($accountID);

if(!empty($account['salt'])) {
	$salt = $account['salt'];
	$fileEncrypted = $sec->encryptData($saveData, $salt);
	file_put_contents(__DIR__."/../data/accounts/".$accountID, $fileEncrypted);
} else {
	file_put_contents(__DIR__."/../data/accounts/".$accountID, $saveData);
}

Library::logAction($person, Action::SuccessfulAccountBackup, $userName, filesize(__DIR__."/../data/accounts/".$accountID), 0, 0);

exit(CommonError::Success);
?>