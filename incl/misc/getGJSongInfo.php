<?php
require_once __DIR__."/../lib/mainLib.php";
require_once __DIR__."/../lib/security.php";
require_once __DIR__."/../lib/exploitPatch.php";
require_once __DIR__."/../lib/enums.php";
$sec = new Security();

$person = $sec->loginPlayer();
if(!$person["success"]) exit(CommonError::InvalidRequest);

$songID = Escape::number($_POST['songID']);

$song = Library::getSongByID($songID);
if(!$song) exit(CommonError::InvalidRequest);
if($song['isDisabled']) exit(CommonError::Disabled);

// To be added: Newgrounds support

$songString = Library::getSongString($songID);

exit($songString);
?>