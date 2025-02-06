<?php
require __DIR__."/../../config/misc.php";
require_once __DIR__."/../lib/mainLib.php";
require_once __DIR__."/../lib/exploitPatch.php";
require_once __DIR__."/../lib/enums.php";

$commentsString = $usersString = "";
$usersArray = [];

$binaryVersion = isset($_POST['binaryVersion']) ? Escape::number($_POST["binaryVersion"]) : 0;
$gameVersion = isset($_POST['gameVersion']) ? Escape::number($_POST["gameVersion"]) : 0;
$sortMode = $_POST["mode"] ? "comments.likes - comments.dislikes" : "comments.timestamp";
$count = isset($_POST["count"]) ? Escape::number($_POST["count"]) : 10;
$page = isset($_POST["page"]) ? Escape::number($_POST["page"]) : 0;

$pageOffset = $page * $count;

switch(true) {
	case isset($_POST['levelID']):
		$levelID = Escape::multiple_ids($_POST['levelID']);
		$comments = Library::getCommentsOfLevel($levelID, $sortMode, $pageOffset);
		$displayLevelID = false;
		break;
	case isset($_POST['userID']):
		$userID = Escape::number($_POST['userID']);
		$comments = Library::getCommentsOfUser($userID, $sortMode, $pageOffset);
		$displayLevelID = true;
		break;
	default:
		exit(CommonError::InvalidRequest);
}

if(empty($comments['comments'])) exit(CommentsError::NothingFound);

foreach($comments['comments'] AS &$comment) {
	$timestamp = Library::makeTime($comment['timestamp']);
	
	$comment['comment'] = Escape::translit(Escape::url_base64_decode($comment["comment"]));
	$showLevelID = $displayLevelID ? $comment["levelID"] : Library::getFirstMentionedLevel($comment['comment']);
	$commentText = $gameVersion < 20 ? Escape::gd($comment["comment"]) : Escape::url_base64_encode($comment["comment"]);
	
	$likes = $comment['likes'] - $comment['dislikes'];
	if($commentAutoLike && array_key_exists($comment["commentID"], $specialCommentLikes)) $likes = $likes * $specialCommentLikes[$comment["commentID"]]; // Multiply by the specified value
	if($likes < -2) $comment["isSpam"] = 1;
	
	$commentsString .= ($showLevelID ? "1~".$showLevelID."~" : "")."2~".$commentText."~3~".$comment["userID"]."~4~".$likes."~5~0~7~".$comment["isSpam"]."~9~".$timestamp."~6~".$comment["commentID"]."~10~".$comment["percent"];
	$user = Library::getUserByID($comment['userID']);
	if($binaryVersion > 31) {
		//$badge = $gs->getMaxValuePermission($comment1["extID"], "modBadgeLevel");
		//$colorString = $badge > 0 ? "~12~".$gs->getAccountCommentColor($comment1["extID"]) : "";
		$commentsString .= "~11~0~12~255,255,255:1~".$user["userName"]."~7~1~9~".$user["icon"]."~10~".$user["color1"]."~11~".$user["color2"]."~14~".$user["iconType"]."~15~".$user["special"]."~16~".$user["extID"];
	} elseif(!isset($users[$user["userID"]])) {
		$users[$user["userID"]] = true;
		$usersString .=  $user["userID"] . ":" . $user["userName"] . ":" . $user["extID"] . "|";
	}
	$commentsString .= "|";
}

$commentsString = rtrim($commentsString, "|");
$usersString = rtrim($usersString, "|");
exit($commentsString.($binaryVersion < 32 ? "#".$usersString : '')."#".$comments["count"].":".$pageOffset.":".count($comments["comments"]));
?>