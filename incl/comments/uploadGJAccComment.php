<?php
require_once __DIR__."/../lib/mainLib.php";
require_once __DIR__."/../lib/security.php";
require_once __DIR__."/../lib/exploitPatch.php";
require_once __DIR__."/../lib/commands.php";
require_once __DIR__."/../lib/enums.php";
$sec = new Security();

$person = $sec->loginPlayer();
if(!$person["success"]) exit(CommonError::InvalidRequest);
$accountID = $person['accountID'];

$gameVersion = Escape::number($_POST['gameVersion']);
$comment = Escape::text($_POST['comment']);

if(empty($comment)) exit(CommonError::InvalidRequest);

if($gameVersion >= 20) $comment = Escape::url_base64_decode($comment);

$account = Library::getAccountByID($accountID);

$command = Commands::processProfileCommand($comment, $account, $person);
if($command) exit(Library::showCommentsBanScreen($command, 0));

Library::uploadAccountComment($person, $comment);
exit(CommonError::Success); 
?>