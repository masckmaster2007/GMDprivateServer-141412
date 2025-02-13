<?php
require_once __DIR__."/../lib/mainLib.php";
require_once __DIR__."/../lib/security.php";
require_once __DIR__."/../lib/exploitPatch.php";
require_once __DIR__."/../lib/enums.php";
$sec = new Security();

$person = $sec->loginPlayer();
if(!$person["success"]) exit(CommonError::InvalidRequest);

$commentID = Escape::number($_POST["commentID"]);

$deleteComment = Library::deleteComment($person, $commentID);
if(!$deleteComment) exit(CommonError::InvalidRequest);

exit(CommonError::Success);
?>