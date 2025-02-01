<?php
require_once "../incl/lib/mainLib.php";
require_once "../incl/lib/security.php";
require_once "../incl/lib/exploitPatch.php";
require_once "../incl/lib/enums.php";
require_once "../incl/lib/ip.php";
$sec = new Security();

$IP = IP::getIP();
$userName = Escape::username($_POST['userName']);
$loginType = Security::getLoginType();

if(!$loginType) exit(LoginError::GenericError);

$loginToAccount = $sec->loginToAccountWithUserName($userName, $loginType["key"], $loginType["type"]);
if($loginToAccount['success']) {
	$accountID = $loginToAccount['accountID'];
	$userID = $loginToAccount['userID'];
	Library::logAction($accountID, $IP, 2);
	exit($accountID.','.$userID);
} else {
	$accountID = Library::getAccountIDWithUserName($userName) ?? 0;
	Library::logAction($accountID, $IP, 6);
	exit($loginToAccount['error']);
}
?>