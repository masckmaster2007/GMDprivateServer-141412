<?php
require_once __DIR__."/../incl/lib/mainLib.php";
require_once __DIR__."/../incl/lib/security.php";
$sec = new Security();

$person = $sec->loginPlayer();
$accountID = $person['accountID'];
$userID = $person['userID'];

if(!$person['success']) {
	Library::logAction($person, 6);
	exit($person['error']);
}

Library::logAction($person, 2);
exit($accountID.','.$userID);
?>