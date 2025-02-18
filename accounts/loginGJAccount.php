<?php
require_once __DIR__."/../incl/lib/mainLib.php";
require_once __DIR__."/../incl/lib/security.php";
$sec = new Security();

$person = $sec->loginPlayer();
$accountID = $person['accountID'];
$userID = $person['userID'];

if(!$person['success']) {
	Library::logAction($person, Action::FailedLogin);
	exit($person['error']);
}

Library::logAction($person, Action::SuccessfulLogin);

exit($accountID.','.$userID);
?>