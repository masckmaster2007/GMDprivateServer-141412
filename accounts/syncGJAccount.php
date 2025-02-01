<?php
require_once __DIR__."/../incl/lib/mainLib.php";
require_once __DIR__."/../incl/lib/security.php";
require_once __DIR__."/../incl/lib/exploitPatch.php";
require_once __DIR__."/../incl/lib/enums.php";
require_once __DIR__."/../incl/lib/ip.php";
$sec = new Security();

$IP = IP::getIP();
$accountID = Escape::number($_POST['accountID']);
$loginType = Security::getLoginType();

if(!$loginType) exit(LoginError::GenericError);

$loginToAccount = $sec->loginToAccountWithID($accountID, $loginType["key"], $loginType["type"]);
if(!$loginToAccount['success']) exit(BackupError::WrongCredentials);

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