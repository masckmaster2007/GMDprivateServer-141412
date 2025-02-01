<?php
require_once __DIR__."/../lib/mainLib.php";
require_once __DIR__."/../lib/exploitPatch.php";
require_once __DIR__."/../lib/enums.php";

$accountID = Escape::number($_POST['accountID']);
$userID = Library::getUserID($accountID);
if(!$userID) exit(CommonError::InvalidRequest);

$binaryVersion = isset($_POST['binaryVersion']) ? Escape::number($_POST["binaryVersion"]) : 0;
$gameVersion = isset($_POST['gameVersion']) ? Escape::number($_POST["gameVersion"]) : 0;
$sortMode = $_POST["mode"] ? "likes" : "commentID";
$count = (isset($_POST["count"]) AND is_numeric($_POST["count"])) ? Escape::number($_POST["count"]) : 10;
$page = isset($_POST["page"]) ? Escape::number($_POST["page"]) : 0;

$commentsPage = $page * $count;

switch(true) {
	case isset($_POST['levelID']):
		
}
?>