<?php
require_once __DIR__."/../incl/lib/mainLib.php";
require_once __DIR__."/../incl/lib/security.php";
require_once __DIR__."/../incl/lib/exploitPatch.php";
require_once __DIR__."/../incl/lib/enums.php";
$sec = new Security();

$person = $sec->loginPlayer();
if(!$person["success"]) exit(CommonError::InvalidRequest);
$accountID = $person["accountID"];

$account = Library::getAccountByID($accountID);

if(!file_exists(__DIR__."/../data/accounts/".$accountID)) exit(BackupError::GenericError);

if(!empty($account['salt'])) {
	$salt = $account['salt'];
	$saveData = $sec->decryptFile(__DIR__."/../data/accounts/".$accountID, $salt);
} else {
	$saveData = file_get_contents(__DIR__."/../data/accounts/".$accountID);
}
exit($saveData.";21;30;a;a");
?>