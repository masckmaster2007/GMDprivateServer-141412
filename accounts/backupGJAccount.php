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
$saveData = $_POST['saveData'];

if(!$loginType) exit(LoginError::GenericError);

$loginToAccount = $sec->loginToAccountWithID($accountID, $loginType["key"], $loginType["type"]);
if(!$loginToAccount['success']) exit(BackupError::WrongCredentials);

$account = Library::getAccountByID($accountID);

if(!empty($account['salt'])) {
	$salt = $account['salt'];
	$sec->encryptData(__DIR__."/../data/accounts/".$accountID, $saveData, $salt);
} else {
	file_put_contents(__DIR__."/../data/accounts/".$accountID, $saveData);
}
exit(BackupError::Success);
?>