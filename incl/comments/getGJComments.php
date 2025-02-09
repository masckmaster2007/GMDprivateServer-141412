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
	$extraTextArray = [];
	if($comment['userID'] == $comment['levelUserID']) $extraTextArray[] = 'Creator';
	
	$comment['comment'] = Escape::translit(Escape::url_base64_decode($comment["comment"]));
	$showLevelID = $displayLevelID ? $comment["levelID"] : Library::getFirstMentionedLevel($comment['comment']);
	$commentText = $gameVersion < 20 ? Escape::gd($comment["comment"]) : Escape::url_base64_encode($comment["comment"]);
	
	$likes = $comment['likes'] - $comment['dislikes'];
	if($commentAutoLike && array_key_exists($comment["commentID"], $specialCommentLikes)) $likes = $likes * $specialCommentLikes[$comment["commentID"]]; // Multiply by the specified value
	if($likes < -2) $comment["isSpam"] = 1;
	
	$user = Library::getUserByID($comment['userID']);
	if($binaryVersion > 31) {
		$person = [
			'accountID' => $user['extID'],
			'userID' => $user['userID'],
			'IP' => $user['IP'],
		];
		
		$appearance = Library::getPersonCommentAppearance($person);
		if(!empty($appearance['commentsExtraText'])) $extraTextArray[] = $appearance['commentsExtraText'];
		
		$personString = "~11~".$appearance['modBadgeLevel'].'~12~'.$appearance['commentColor'].":1~".$user["userName"]."~7~1~9~".$user["icon"]."~10~".$user["color1"]."~11~".$user["color2"]."~14~".$user["iconType"]."~15~".$user["special"]."~16~".$user["extID"];
	} elseif(!isset($users[$user["userID"]])) {
		$users[$user["userID"]] = true;
		$usersString .=  $user["userID"] . ":" . $user["userName"] . ":" . $user["extID"] . "|";
	}
	$timestamp = Library::makeTime($comment['timestamp'], $extraTextArray);
	$commentsString .= ($showLevelID ? "1~".$showLevelID."~" : "")."2~".$commentText."~3~".$comment["userID"]."~4~".$likes."~5~0~7~".$comment["isSpam"]."~9~".$timestamp."~6~".$comment["commentID"]."~10~".$comment["percent"].$personString;
	$commentsString .= "|";
}

$commentsString = rtrim($commentsString, "|");
exit($commentsString.($binaryVersion < 32 ? "#".rtrim($usersString, "|") : '')."#".$comments["count"].":".$pageOffset.":".count($comments["comments"]));
?>