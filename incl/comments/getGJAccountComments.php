<?php
require_once __DIR__."/../lib/mainLib.php";
require_once __DIR__."/../lib/exploitPatch.php";
require_once __DIR__."/../lib/enums.php";

$accountID = Escape::number($_POST['accountID']);
$userID = Library::getUserID($accountID);
if(!$userID) exit(CommonError::InvalidRequest);
$page = Escape::number($_POST["page"]) ?? 0;
$commentsPage = $page * 10;

$accountComments = Library::getAccountComments($userID, $commentsPage);
$echoString = '';
foreach($accountComments['comments'] AS &$accountComment) {
	$timestamp = Library::makeTime($accountComment['timestamp']);
	$likes = $accountComment['likes'] - $accountComment['dislikes'];
	$accountComment['comment'] = Escape::url_base64_encode(Escape::translit(Escape::url_base64_decode($accountComment['comment'])));
	$echoString .= "2~".$accountComment["comment"]."~3~".$accountComment["userID"]."~4~".$likes."~5~0~7~".$accountComment["isSpam"]."~9~".$timestamp."~6~".$accountComment["commentID"]."|";
}
$echoString = substr($echoString, 0, -1);
exit($echoString."#".$accountComments['count'].":".$commentsPage.":10");
?>