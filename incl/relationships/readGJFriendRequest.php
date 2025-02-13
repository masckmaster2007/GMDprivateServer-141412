<?php
require_once __DIR__."/../lib/mainLib.php";
require_once __DIR__."/../lib/exploitPatch.php";
require_once __DIR__."/../lib/security.php";
require_once __DIR__."/../lib/enums.php";
$sec = new Security();

$person = $sec->loginPlayer();
if(!$person["success"]) exit(CommonError::InvalidRequest);

$requestID = Escape::number($_POST['requestID']);

$readFriendRequest = Library::readFriendRequest($person, $requestID);
if(!$readFriendRequest) exit(CommonError::InvalidRequest);

exit(CommonError::Success);
?>