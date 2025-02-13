<?php
require_once __DIR__."/../lib/mainLib.php";
require_once __DIR__."/../lib/exploitPatch.php";
require_once __DIR__."/../lib/security.php";
require_once __DIR__."/../lib/enums.php";
$sec = new Security();

$person = $sec->loginPlayer();
if(!$person["success"]) exit(CommonError::InvalidRequest);

$targetAccountID = Escape::latin_no_spaces($_POST['targetAccountID']);

$removeFriend = Library::removeFriend($person, $targetAccountID);
if(!$removeFriend) exit(CommonError::InvalidRequest);

exit(CommonError::Success);
?>