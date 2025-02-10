<?php
require_once __DIR__."/../lib/mainLib.php";
require_once __DIR__."/../lib/security.php";
require_once __DIR__."/../lib/exploitPatch.php";
require_once __DIR__."/../lib/enums.php";
$sec = new Security();

$player = $sec->loginPlayer();
if(!$player["success"]) exit(CommonError::InvalidRequest);
$accountID = $player["accountID"];
$userID = $player["userID"];
$userName = $player["userName"];

$friendsState = $badge = 0;
$targetAccountID = Escape::number($_POST['targetAccountID']);
$targetUserID = Library::getUserID($targetAccountID);
if(!$targetUserID) exit(CommonError::InvalidRequest);

$user = Library::getUserByID($targetUserID);
$account = Library::getAccountByID($targetAccountID);

$queryText = Library::getBannedPeopleQuery(0, true);

$rank = Library::getUserRank($user['stars'], $user['moons']);
$user['creatorPoints'] = round($user["creatorPoints"], PHP_ROUND_HALF_DOWN);
$messagesState = $account['mS'];
$friendRequestsState = $account['frS'];
$commentsState = $account['cS'];

$incomingRequest = '';
if($accountID == $targetAccountID) {
	$requestsCount = $db->prepare("SELECT count(*) FROM friendreqs WHERE toAccountID = :accountID");
	$requestsCount->execute([':accountID' => $accountID]);
	$requestsCount = $requestsCount->fetchColumn();
	
	$newMessagesCount = $db->prepare("SELECT count(*) FROM messages WHERE toAccountID = :accountID AND isNew = 0");
	$newMessagesCount->execute([':accountID' => $accountID]);
	$newMessagesCount = $newMessagesCount->fetchColumn();
	
	$newFriendRequestsCount = $db->prepare("SELECT count(*) FROM friendships WHERE (person1 = :accountID AND isNew2 = '1') OR (person2 = :accountID AND isNew1 = '1')");
	$newFriendRequestsCount->execute([':accountID' => $accountID]);
	$newFriendRequestsCount = $newFriendRequestsCount->fetchColumn();
	
	$incomingRequestText = ":38:".$newMessagesCount.":39:".$requestsCount.":40:".$newFriendRequestsCount;
} else {
	$isFriends = Library::isFriends($accountID, $targetAccountID);
	if($isFriends) $friendsState = 1;
	else {
		$incomingFriendRequest = Library::getFriendRequest($targetAccountID, $accountID);
		if($incomingFriendRequest) {
			$incomingFriendRequestTime = Library::makeTime($incomingFriendRequest["uploadDate"]);
			$incomingRequestText = ":32:".$incomingFriendRequest["ID"].":35:".$incomingFriendRequest["comment"].":37:".$incomingFriendRequestTime;
			$friendsState = 3;
		} else {
			$outcomingFriendRequest = Library::getFriendRequest($accountID, $targetAccountID);
			if($outcomingFriendRequest) $friendsState = 4;
		}
	}
}
exit("1:".$user["userName"].":2:".$user["userID"].":13:".$user["coins"].":17:".$user["userCoins"].":10:".$user["color1"].":11:".$user["color2"].":51:".$user["color3"].":3:".$user["stars"].":46:".$user["diamonds"].":52:".$user["moons"].":4:".$user["demons"].":8:".$user['creatorPoints'].":18:".$messagesState.":19:".$friendRequestsState.":50:".$commentsState.":20:".$account["youtubeurl"].":21:".$user["accIcon"].":22:".$user["accShip"].":23:".$user["accBall"].":24:".$user["accBird"].":25:".$user["accDart"].":26:".$user["accRobot"].":28:".$user["accGlow"].":43:".$user["accSpider"].":48:".$user["accExplosion"].":53:".$user["accSwing"].":54:".$user["accJetpack"].":30:".$rank.":16:".$user["extID"].":31:".$friendsState.":44:".$account["twitter"].":45:".$account["twitch"].":49:".$badge.":55:".$user["dinfo"].":56:".$user["sinfo"].":57:".$user["pinfo"].$incomingRequest.":29:1");
?>