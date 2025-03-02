<?php
require_once __DIR__."/../incl/lib/mainLib.php";
require_once __DIR__."/../incl/lib/security.php";
$sec = new Security();

$person = $sec->loginPlayer();
if(!$person['success']) exit($person['error']);
$accountID = $person['accountID'];
$userID = $person['userID'];

Library::logAction($person, Action::SuccessfulLogin);

exit($accountID.','.$userID);
?>