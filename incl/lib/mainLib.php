<?php
require_once __DIR__."/enums.php";
class Library {
	/*
		Account-related functions
	*/
	
	public function createAccount($userName, $password, $repeatPassword, $email, $repeatEmail) {
		require __DIR__."/connection.php";
		require __DIR__."/../../config/mail.php";
		require_once __DIR__."/security.php";
		require_once __DIR__."/ip.php";
		
		$IP = IP::getIP();
		$salt = self::randomString(32);
		
		if(strlen($userName) > 20 || is_numeric($userName) || strpos($userName, " ") !== false) return ["success" => false, "error" => RegisterError::InvalidUserName];
		if(strlen($userName) < 3) return ["success" => false, "error" => RegisterError::UserNameIsTooShort];
		if(strlen($password) < 6) return ["success" => false, "error" => RegisterError::PasswordIsTooShort];
		if($password !== $repeatPassword) return ["success" => false, "error" => RegisterError::PasswordsDoNotMatch];
		if($email !== $repeatEmail) return ["success" => false, "error" => RegisterError::EmailsDoNotMatch];
		if(!filter_var($email, FILTER_VALIDATE_EMAIL)) return ["success" => false, "error" => RegisterError::InvalidEmail];
		
		$userNameExists = self::getAccountIDWithUserName($userName);
		if($userNameExists) return ["success" => false, "error" => RegisterError::AccountExists];
		
		if($mailEnabled) {
			$emailExists = self::getAccountByEmail($email);
			if($emailExists) return ["success" => false, "error" => RegisterError::EmailIsInUse];
		}
		
		$gjp2 = Security::GJP2FromPassword($password);
		$createAccount = $db->prepare("INSERT INTO accounts (userName, password, email, registerDate, isActive, gjp2, salt)
			VALUES (:userName, :password, :email, :registerDate, :isActive, :gjp2, :salt)");
		$createAccount->execute([':userName' => $userName, ':password' => Security::hashPassword($password), ':email' => $email, ':registerDate' => time(), ':isActive' => $preactivateAccounts ? 1 : 0, ':gjp2' => Security::hashPassword($gjp2), ':salt' => $salt]);
		
		$accountID = $db->lastInsertId();
		$userID = self::createUser($userName, $accountID, $IP);
		
		self::logAction($accountID, $IP, 1, $userName, $email, $userID);

		// TO-DO: Readd email verification
		
		return ["success" => true, "accountID" => $accountID, "userID" => $userID];
	}
	
	public static function getAccountByUserName($userName) {
		require __DIR__."/connection.php";
		
		$account = $db->prepare("SELECT * FROM accounts WHERE userName LIKE :userName LIMIT 1");
		$account->execute([':userName' => $userName]);
		$account = $account->fetch();
		
		return $account;
	}
	
	public static function getAccountByID($accountID) {
		require __DIR__."/connection.php";
		
		$account = $db->prepare("SELECT * FROM accounts WHERE accountID = :accountID");
		$account->execute([':accountID' => $accountID]);
		$account = $account->fetch();
		
		return $account;
	}
	
	public static function getAccountByEmail($email) {
		require __DIR__."/connection.php";
		
		$account = $db->prepare("SELECT * FROM accounts WHERE email LIKE :email ORDER BY registerDate ASC LIMIT 1");
		$account->execute([':email' => $email]);
		$account = $account->fetch();
		
		return $account;
	}
	
	public static function createUser($userName, $accountID, $IP) {
		require __DIR__."/connection.php";
		
		$isRegistered = is_numeric($accountID) ? 1 : 0;
		
		$createUser = $db->prepare("INSERT INTO users (isRegistered, extID, userName, IP)
			VALUES (:isRegistered, :extID, :userName, :IP)");
		$createUser->execute([':isRegistered' => $isRegistered, ':extID' => $accountID, ':userName' => $userName, ':IP' => $IP]);
		
		return $db->lastInsertId();
	}
	
	public static function getUserID($accountID) {
		require __DIR__."/connection.php";
		require_once __DIR__."/ip.php";
		
		$userID = $db->prepare("SELECT userID FROM users WHERE extID = :extID");
		$userID->execute([':extID' => $accountID]);
		$userID = $userID->fetchColumn();
		
		if(!$userID) {
			$account = self::getAccountByID($accountID);
			if(!$account) return false;
			
			$IP = IP::getIP();
			$userName = $account['userName'];
			$userID = self::createUser($userName, $accountID, $IP);
		}
		
		return $userID;
	}
	
	public static function getAccountID($userID) {
		require __DIR__."/connection.php";
		
		$accountID = $db->prepare("SELECT extID FROM users WHERE userID = :userID");
		$accountID->execute([':userID' => $userID]);
		$accountID = $accountID->fetchColumn();
		
		return $accountID;
	}
	
	public static function getAccountIDWithUserName($userName) {
		require __DIR__."/connection.php";
		
		$accountID = $db->prepare("SELECT accountID FROM accounts WHERE userName LIKE :userName");
		$accountID->execute([':userName' => $userName]);
		$accountID = $accountID->fetchColumn();
		
		return $accountID;
	}
	
	public static function getUserByID($userID) {
		require __DIR__."/connection.php";
		
		$user = $db->prepare("SELECT * FROM users WHERE userID = :userID");
		$user->execute([':userID' => $userID]);
		$user = $user->fetch();
		
		return $user;
	}
	
	public static function getUserByUserName($userName) {
		require __DIR__."/connection.php";
		
		$user = $db->prepare("SELECT * FROM users WHERE userName LIKE :userName ORDER BY isRegistered DESC LIMIT 1");
		$user->execute([':userName' => $userName]);
		$user = $user->fetch();
		
		return $user;
	}
	
	public static function getUserFromSearch($player) {
		switch(true) {
			case is_numeric($player):
				$userID = self::getUserID($player);
				$player = self::getUserByID($userID);
				break;
			case substr($player, 0, 1) == 'u':
				$userID = substr($player, 1);
				if(is_numeric($userID)) {
					$player = self::getUserByID($userID);
					break;
				}
			default:
				$player = self::getUserByUserName($player);
				break;
		}
		
		return $player;
	}
	
	public static function getFriendRequest($accountID, $targetAccountID) {
		require __DIR__."/connection.php";
		
		$friendRequest = $db->prepare("SELECT * FROM friendreqs WHERE accountID = :accountID AND toAccountID = :targetAccountID");
		$friendRequest->execute([':accountID' => $accountID, ':targetAccountID' => $targetAccountID]);
		$friendRequest = $friendRequest->fetch();
		
		return $friendRequest;
	}
	
	public static function isFriends($accountID, $targetAccountID) {
		require __DIR__."/connection.php";

		$isFriends = $db->prepare("SELECT count(*) FROM friendships WHERE (person1 = :accountID AND person2 = :targetAccountID) OR (person1 = :targetAccountID AND person2 = :accountID)");
		$isFriends->execute([':accountID' => $accountID, ':targetAccountID' => $targetAccountID]);
		
		return $isFriends->fetchColumn() > 0;
	}
	
	public static function getAccountComments($userID, $commentsPage) {
		require __DIR__."/connection.php";

		$accountComments = $db->prepare("SELECT * FROM acccomments WHERE userID = :userID ORDER BY timestamp DESC LIMIT 10 OFFSET ".$commentsPage);
		$accountComments->execute([':userID' => $userID]);
		$accountComments = $accountComments->fetchAll();
		
		$accountCommentsCount = $db->prepare("SELECT count(*) FROM acccomments WHERE userID = :userID");
		$accountCommentsCount->execute([':userID' => $userID]);
		$accountCommentsCount = $accountCommentsCount->fetchColumn();
		
		return ["comments" => $accountComments, "count" => $accountCommentsCount];
	}
	
	public static function uploadAccountComment($accountID, $userID, $userName, $comment) {
		require __DIR__."/connection.php";
		require_once __DIR__."/exploitPatch.php";
		require_once __DIR__."/ip.php";
		
		$IP = IP::getIP();
		$comment = Escape::url_base64_encode($comment);
		
		$uploadAccountComment = $db->prepare("INSERT INTO acccomments (userID, comment, timestamp)
			VALUES (:userID, :comment, :timestamp)");
		$uploadAccountComment->execute([':userID' => $userID, ':comment' => $comment, ':timestamp' => time()]);
		$commentID = $db->lastInsertId();

		self::logAction($accountID, $IP, 14, $userName, $comment, $commentID);
		
		return $commentID;
	}
	
	public static function updateAccountSettings($accountID, $messagesState, $friendRequestsState, $commentsState, $socialsYouTube, $socialsTwitter, $socialsTwitch) {
		require __DIR__."/connection.php";
		require_once __DIR__."/ip.php";
		
		$IP = IP::getIP();
		
		$updateAccountSettings = $db->prepare("UPDATE accounts SET mS = :messagesState, frS = :friendRequestsState, cS = :commentsState, youtubeurl = :socialsYouTube, twitter = :socialsTwitter, twitch = :socialsTwitch WHERE accountID = :accountID");
		$updateAccountSettings->execute([':accountID' => $accountID, ':messagesState' => $messagesState, ':friendRequestsState' => $friendRequestsState, ':commentsState' => $commentsState, ':socialsYouTube' => $socialsYouTube, ':socialsTwitter' => $socialsTwitter, ':socialsTwitch' => $socialsTwitch]);
		
		self::logAction($accountID, $IP, 27, $messagesState, $friendRequestsState, $commentsState, $socialsYouTube, $socialsTwitter, $socialsTwitch);
	}
	
	public static function getFriends($accountID) {
		require __DIR__."/connection.php";
		
		$friendsArray = [];
		
		$getFriends = $db->prepare("SELECT person1, person2 FROM friendships WHERE person1 = :accountID OR person2 = :accountID");
		$getFriends->execute([':accountID' => $accountID]);
		$getFriends = $getFriends->fetchAll();
		
		foreach($getFriends as &$friendship) $friendsArray[] = $friendship["person2"] == $accountID ? $friendship["person1"] : $friendship["person2"];
		
		return $friendsArray;
	}
	
	public static function getUserString($user) {
		//$user['userName'] = Library::makeClanUsername($user);
		return $user['userID'].':'.$user["userName"].':'.(is_numeric($user['extID']) ? $user['extID'] : 0);
	}
	
	public static function isAccountAdmininstrator($accountID) {
		$account = self::getAccountByID($accountID);
		return $account['isAdmin'] != 0;
	}
	
	public static function getCommentsOfUser($userID, $sortMode, $pageOffset) {
		require __DIR__."/connection.php";
		
		$comments = $db->prepare("SELECT * FROM levels INNER JOIN comments ON comments.levelID = levels.levelID WHERE comments.userID = :userID AND levels.unlisted = 0 AND levels.unlisted2 = 0 ORDER BY ".$sortMode." DESC LIMIT 10 OFFSET ".$pageOffset);
		$comments->execute([':userID' => $userID]);
		$comments = $comments->fetchAll();
		
		$commentsCount = $db->prepare("SELECT count(*) FROM levels INNER JOIN comments ON comments.levelID = levels.levelID WHERE comments.userID = :userID AND levels.unlisted = 0 AND levels.unlisted2 = 0");
		$commentsCount->execute([':userID' => $userID]);
		$commentsCount = $commentsCount->fetchColumn();
		
		return ["comments" => $comments, "count" => $commentsCount];
	}
	
	public static function deleteAccountComment($userID, $commentID) {
		require __DIR__."/connection.php";
		
		$getComment = $db->prepare("SELECT count(*) FROM acccomments WHERE userID = :userID AND commentID = :commentID");
		$getComment->execute([':userID' => $userID, ':commentID' => $commentID]);
		$getComment = $getComment->fetchColumn();
		if(!$getComment) return false;
		
		$deleteComment = $db->prepare("DELETE FROM acccomments WHERE commentID = :commentID");
		$deleteComment->execute([':commentID' => $commentID]);
		
		return true;
	}
	
	/*
		Levels-related functions
	*/
	
	public static function escapeDescriptionCrash($rawDesc) {
		if(strpos($rawDesc, '<c') !== false) {
			$tagsStart = substr_count($rawDesc, '<c');
			$tagsEnd = substr_count($rawDesc, '</c>');
			if($tagsStart > $tagsEnd) {
				$tags = $tagsStart - $tagsEnd;
				for($i = 0; $i < $tags; $i++) $rawDesc .= '</c>';
			}
		}
		return $rawDesc;
	}
	
	public static function isAbleToUploadLevel($accountID, $userID, $IP) {
		require __DIR__."/connection.php";
		require __DIR__."/../../config/security.php";
		
		$lastUploadedLevel = $db->prepare('SELECT count(*) FROM levels WHERE uploadDate >= :time');
		$lastUploadedLevel->execute([':time' => time() - $globalLevelsUploadDelay]);
		$lastUploadedLevel = $lastUploadedLevel->fetchColumn();
		if($lastUploadedLevel) return ["success" => false, "error" => LevelUploadError::TooFast];
		
		$lastUploadedLevelByUser = $db->prepare('SELECT count(*) FROM levels WHERE uploadDate >= :time AND (userID = :userID OR hostname = :IP)');
		$lastUploadedLevelByUser->execute([':time' => time() - $perUserLevelsUploadDelay, ':userID' => $userID, ':IP' => $IP]);
		$lastUploadedLevelByUser = $lastUploadedLevelByUser->fetchColumn();
		if($lastUploadedLevelByUser) return ["success" => false, "error" => LevelUploadError::TooFast];
		
		return ["success" => true];
	}
	
	public function uploadLevel($accountID, $userID, $levelID, $levelName, $levelString, $levelDetails) {
		require __DIR__."/connection.php";
		require_once __DIR__."/ip.php";
		
		$IP = IP::getIP();
		
		$checkLevelExistenceByID = $db->prepare("SELECT updateLocked FROM levels WHERE levelID = :levelID AND userID = :userID");
		$checkLevelExistenceByID->execute([':levelID' => $levelID, ':userID' => $userID]);
		$checkLevelExistenceByID = $checkLevelExistenceByID->fetch();
		if($checkLevelExistenceByID) {
			if($checkLevelExistenceByID['updateLocked']) return ['success' => false, 'error' => LevelUploadError::UploadingDisabled];
			
			$writeFile = file_put_contents(__DIR__.'/../../data/levels/'.$levelID, $levelString);
			if(!$writeFile) return ['success' => false, 'error' => LevelUploadError::FailedToWriteLevel];
			
			$updateLevel = $db->prepare('UPDATE levels SET userName = :userName, gameVersion = :gameVersion, binaryVersion = :binaryVersion, levelDesc = :levelDesc, levelVersion = levelVersion + 1, levelLength = :levelLength, audioTrack = :audioTrack, auto = :auto, original = :original, twoPlayer = :twoPlayer, songID = :songID, objects = :objects, coins = :coins, requestedStars = :requestedStars, extraString = :extraString, levelString = "", levelInfo = :levelInfo, unlisted = :unlisted, hostname = :IP, isLDM = :isLDM, wt = :wt, wt2 = :wt2, unlisted2 = :unlisted, settingsString = :settingsString, songIDs = :songIDs, sfxIDs = :sfxIDs, ts = :ts, password = :password, updateDate = :timestamp WHERE levelID = :levelID');
			$updateLevel->execute([':levelID' => $levelID, ':userName' => $levelDetails['userName'], ':gameVersion' => $levelDetails['gameVersion'], ':binaryVersion' => $levelDetails['binaryVersion'], ':levelDesc' => $levelDetails['levelDesc'], ':levelLength' => $levelDetails['levelLength'], ':audioTrack' => $levelDetails['audioTrack'], ':auto' => $levelDetails['auto'], ':original' => $levelDetails['original'], ':twoPlayer' => $levelDetails['twoPlayer'], ':songID' => $levelDetails['songID'], ':objects' => $levelDetails['objects'], ':coins' => $levelDetails['coins'], ':requestedStars' => $levelDetails['requestedStars'], ':extraString' => $levelDetails['extraString'], ':levelInfo' => $levelDetails['levelInfo'], ':unlisted' => $levelDetails['unlisted'], ':isLDM' => $levelDetails['isLDM'], ':wt' => $levelDetails['wt'], ':wt2' => $levelDetails['wt2'], ':settingsString' => $levelDetails['settingsString'], ':songIDs' => $levelDetails['songIDs'], ':sfxIDs' => $levelDetails['sfxIDs'], ':ts' => $levelDetails['ts'], ':password' => $levelDetails['password'], ':timestamp' => time(), ':IP' => $IP]);
			
			self::logAction($accountID, $IP, 23, $levelName, $levelDetails['levelDesc'], $levelID);
			return ["success" => true, "levelID" => (string)$levelID];
		}
		
		$checkLevelExistenceByName = $db->prepare("SELECT levelID, updateLocked FROM levels WHERE levelName LIKE :levelName AND userID = :userID ORDER BY levelID DESC LIMIT 1");
		$checkLevelExistenceByName->execute([':levelName' => $levelName, ':userID' => $userID]);
		$checkLevelExistenceByName = $checkLevelExistenceByName->fetchColumn();
		if($checkLevelExistenceByName) {
			if($checkLevelExistenceByID['updateLocked']) return ['success' => false, 'error' => LevelUploadError::UploadingDisabled];
			
			$writeFile = file_put_contents(__DIR__.'/../../data/levels/'.$checkLevelExistenceByName, $levelString);
			if(!$writeFile) return ['success' => false, 'error' => LevelUploadError::FailedToWriteLevel];
			
			$updateLevel = $db->prepare('UPDATE levels SET userName = :userName, gameVersion = :gameVersion, binaryVersion = :binaryVersion, levelDesc = :levelDesc, levelVersion = levelVersion + 1, levelLength = :levelLength, audioTrack = :audioTrack, auto = :auto, original = :original, twoPlayer = :twoPlayer, songID = :songID, objects = :objects, coins = :coins, requestedStars = :requestedStars, extraString = :extraString, levelString = "", levelInfo = :levelInfo, unlisted = :unlisted, hostname = :IP, isLDM = :isLDM, wt = :wt, wt2 = :wt2, unlisted2 = :unlisted, settingsString = :settingsString, songIDs = :songIDs, sfxIDs = :sfxIDs, ts = :ts, password = :password, updateDate = :timestamp WHERE levelID = :levelID');
			$updateLevel->execute([':levelID' => $checkLevelExistenceByName, ':userName' => $levelDetails['userName'], ':gameVersion' => $levelDetails['gameVersion'], ':binaryVersion' => $levelDetails['binaryVersion'], ':levelDesc' => $levelDetails['levelDesc'], ':levelLength' => $levelDetails['levelLength'], ':audioTrack' => $levelDetails['audioTrack'], ':auto' => $levelDetails['auto'], ':original' => $levelDetails['original'], ':twoPlayer' => $levelDetails['twoPlayer'], ':songID' => $levelDetails['songID'], ':objects' => $levelDetails['objects'], ':coins' => $levelDetails['coins'], ':requestedStars' => $levelDetails['requestedStars'], ':extraString' => $levelDetails['extraString'], ':levelInfo' => $levelDetails['levelInfo'], ':unlisted' => $levelDetails['unlisted'], ':isLDM' => $levelDetails['isLDM'], ':wt' => $levelDetails['wt'], ':wt2' => $levelDetails['wt2'], ':settingsString' => $levelDetails['settingsString'], ':songIDs' => $levelDetails['songIDs'], ':sfxIDs' => $levelDetails['sfxIDs'], ':ts' => $levelDetails['ts'], ':password' => $levelDetails['password'], ':timestamp' => time(), ':IP' => $IP]);
			
			self::logAction($accountID, $IP, 23, $levelName, $levelDetails['levelDesc'], $levelID);
			return ["success" => true, "levelID" => (string)$checkLevelExistenceByName];
		}
		
		$timestamp = time();
		$writeFile = file_put_contents(__DIR__.'/../../data/levels/'.$userID.'_'.$timestamp, $levelString);
		if(!$writeFile) return ['success' => false, 'error' => LevelUploadError::FailedToWriteLevel];
		
		$uploadLevel = $db->prepare("INSERT INTO levels (userID, extID, userName, gameVersion, binaryVersion, levelName, levelDesc, levelVersion, levelLength, audioTrack, auto, original, twoPlayer, songID, objects, coins, requestedStars, extraString, levelString, levelInfo, unlisted, unlisted2, hostname, isLDM, wt, wt2, settingsString, songIDs, sfxIDs, ts, password, uploadDate, updateDate)
			VALUES (:userID, :accountID, :userName, :gameVersion, :binaryVersion, :levelName, :levelDesc, 1, :levelLength, :audioTrack, :auto, :original, :twoPlayer, :songID, :objects, :coins, :requestedStars, :extraString, '', :levelInfo, :unlisted, :unlisted, :IP, :isLDM, :wt, :wt2, :settingsString, :songIDs, :sfxIDs, :ts, :password, :timestamp, 0)");
		$uploadLevel->execute([':userID' => $userID, ':accountID' => $accountID, ':userName' => $levelDetails['userName'], ':gameVersion' => $levelDetails['gameVersion'], ':binaryVersion' => $levelDetails['binaryVersion'], ':levelName' => $levelName, ':levelDesc' => $levelDetails['levelDesc'], ':levelLength' => $levelDetails['levelLength'], ':audioTrack' => $levelDetails['audioTrack'], ':auto' => $levelDetails['auto'], ':original' => $levelDetails['original'], ':twoPlayer' => $levelDetails['twoPlayer'], ':songID' => $levelDetails['songID'], ':objects' => $levelDetails['objects'], ':coins' => $levelDetails['coins'], ':requestedStars' => $levelDetails['requestedStars'], ':extraString' => $levelDetails['extraString'], ':levelInfo' => $levelDetails['levelInfo'], ':unlisted' => $levelDetails['unlisted'], ':isLDM' => $levelDetails['isLDM'], ':wt' => $levelDetails['wt'], ':wt2' => $levelDetails['wt2'], ':settingsString' => $levelDetails['settingsString'], ':songIDs' => $levelDetails['songIDs'], ':sfxIDs' => $levelDetails['sfxIDs'], ':ts' => $levelDetails['ts'], ':password' => $levelDetails['password'], ':timestamp' => $timestamp, ':IP' => $IP]);
		
		$levelID = $db->lastInsertId();
		rename(__DIR__.'/../../data/levels/'.$userID.'_'.$timestamp, __DIR__.'/../../data/levels/'.$levelID);
		self::logAction($accountID, $IP, 22, $levelName, $levelDetails['levelDesc'], $levelID);
		return ["success" => true, "levelID" => (string)$levelID];
	}
	
	public static function getLevels($filters, $order, $orderSorting, $queryJoin, $pageOffset) {
		require __DIR__."/connection.php";
		
		$levels = $db->prepare("SELECT * FROM levels ".$queryJoin." WHERE (".implode(") AND (", $filters).") ORDER BY ".$order." ".$orderSorting." LIMIT 10 OFFSET ".$pageOffset);
		$levels->execute();
		$levels = $levels->fetchAll();
		
		$levelsCount = $db->prepare("SELECT count(*) FROM levels ".$queryJoin." WHERE (".implode(" ) AND ( ", $filters).")");
		$levelsCount->execute();
		$levelsCount = $levelsCount->fetchColumn();
		
		return ["levels" => $levels, "count" => $levelsCount];
	}
	
	public static function getGauntletByID($gauntletID) {
		require __DIR__."/connection.php";
		
		$gauntlet = $db->prepare("SELECT * FROM gauntlets WHERE ID = :gauntletID");
		$gauntlet->execute([':gauntletID' => $gauntletID]);
		$gauntlet = $gauntlet->fetch();
		
		return $gauntlet;
	}
	
	public static function canAccountPlayLevel($accountID, $level) {
		require __DIR__."/../../config/misc.php";
		
		if($unlistedLevelsForAdmins && self::isAccountAdmininstrator($accountID)) return true;
		
		return !($level['unlisted'] > 0 && ($level['unlisted'] == 1 && (self::isFriends($accountID, $level['extID']) || $accountID == $level['extID'])));
	}
	
	public static function getDailyLevelID($type) {
		require __DIR__."/connection.php";
		
		switch($type) {
			case -1: // Daily level
				$dailyLevelID = $db->prepare("SELECT feaID, levelID FROM dailyfeatures WHERE timestamp < :time AND type = 0 ORDER BY timestamp DESC LIMIT 1");
				$dailyLevelID->execute([':time' => time()]);
				$dailyLevelID = $dailyLevelID->fetch();
				$levelID = $dailyLevelID["levelID"];
				$feaID = $dailyLevelID["feaID"];
				break;
			case -2: // Weekly level
				$weeklyLevelID = $db->prepare("SELECT feaID, levelID FROM dailyfeatures WHERE timestamp < :time AND type = 1 ORDER BY timestamp DESC LIMIT 1");
				$weeklyLevelID->execute([':time' => time()]);
				$weeklyLevelID = $weeklyLevelID->fetch();
				$levelID = $weeklyLevelID["levelID"];
				$feaID = $weeklyLevelID["feaID"] + 100000;
				break;
			case -3: // Event level
				$eventLevelID = $db->prepare("SELECT feaID, levelID FROM events WHERE timestamp < :time AND duration >= :time ORDER BY timestamp DESC LIMIT 1");
				$eventLevelID->execute([':time' => time()]);
				$eventLevelID = $eventLevelID->fetch();
				$levelID = $eventLevelID["levelID"];
				$feaID = $eventLevelID["feaID"] + 200000;
				break;
		}
		
		if(!$levelID) return false;
		
		return ["levelID" => $levelID, "feaID" => $feaID];
	}
	
	public static function getLevelByID($levelID) {
		require __DIR__."/connection.php";
		
		$level = $db->prepare('SELECT * FROM levels WHERE levelID = :levelID');
		$level->execute([':levelID' => $levelID]);
		$level = $level->fetch();
		
		return $level;
	}
	
	public static function addDownloadToLevel($accountID, $IP, $levelID) {
		require __DIR__."/connection.php";
		
		$getDownloads = $db->prepare("SELECT count(*) FROM actions_downloads WHERE levelID = :levelID AND (ip = INET6_ATON(:IP) OR accountID = :accountID)");
		$getDownloads->execute([':levelID' => $levelID, ':IP' => $IP, ':accountID' => $accountID]);
		$getDownloads = $getDownloads->fetchColumn();
		if($getDownloads) return false;
		
		$addDownload = $db->prepare("UPDATE levels SET downloads = downloads + 1 WHERE levelID = :levelID");
		$addDownload->execute([':levelID' => $levelID]);
		$insertAction = $db->prepare("INSERT INTO actions_downloads (levelID, ip, accountID)
			VALUES (:levelID, INET6_ATON(:IP), :accountID)");
		$insertAction->execute([':levelID' => $levelID, ':IP' => $IP, ':accountID' => $accountID]);
		
		return true;
	}
	
	public static function showCommentsBanScreen($text, $time) {
		$time = $time - time();
		if($time < 0) $time = 0;
		return $_POST['gameVersion'] > 20 ? 'temp_'.$time.'_'.$text : '-1';
	}
	
	public static function getCommentsOfLevel($levelID, $sortMode, $pageOffset) {
		require __DIR__."/connection.php";
		
		$comments = $db->prepare("SELECT *, levels.userID AS levelUserID FROM levels INNER JOIN comments ON comments.levelID = levels.levelID WHERE levels.levelID = :levelID ORDER BY ".$sortMode." DESC LIMIT 10 OFFSET ".$pageOffset);
		$comments->execute([':levelID' => $levelID]);
		$comments = $comments->fetchAll();
		
		$commentsCount = $db->prepare("SELECT count(*) FROM levels INNER JOIN comments ON comments.levelID = levels.levelID WHERE levels.levelID = :levelID");
		$commentsCount->execute([':levelID' => $levelID]);
		$commentsCount = $commentsCount->fetchColumn();
		
		return ["comments" => $comments, "count" => $commentsCount];
	}
	
	public static function uploadComment($accountID, $userID, $levelID, $userName, $comment, $percent) {
		require __DIR__."/connection.php";
		require_once __DIR__."/exploitPatch.php";
		require_once __DIR__."/ip.php";
		
		$IP = IP::getIP();
		$comment = Escape::url_base64_encode($comment);
		
		$uploadAccountComment = $db->prepare("INSERT INTO comments (userID, levelID, percent, comment, timestamp)
			VALUES (:userID, :levelID, :percent, :comment, :timestamp)");
		$uploadAccountComment->execute([':userID' => $userID, ':levelID' => $levelID, ':percent' => $percent, ':comment' => $comment, ':timestamp' => time()]);
		$commentID = $db->lastInsertId();

		self::logAction($accountID, $IP, 15, $userName, $comment, $commentID, $levelID);
		
		return $commentID;
	}
	
	public static function getFirstMentionedLevel($text) {
		require __DIR__."/../../config/misc.php";
		if(!$mentionLevelsInComments) return false;
		
		$textArray = explode(' ', $text);
		foreach($textArray AS &$element) {
			if(substr($element, 0, 1) != "#") continue;
			
			$element = substr($element, 1);
			if(!is_numeric($element)) continue;
			
			return $element;
		}
		return false;
	}
	
	public static function getLevelDifficulty($difficulty) {
		switch(strtolower($difficulty)) {
			case 1:
			case "auto":
				return ["name" => "Auto", "difficulty" => 50, "auto" => 1, "demon" => 0];
			case 2:
			case "easy":
				return ["name" => "Easy", "difficulty" => 10, "auto" => 0, "demon" => 0];
			case 3:
			case "normal":
				return ["name" => "Normal", "difficulty" => 20, "auto" => 0, "demon" => 0];
			case 4:
			case 5:
			case "hard":
				return ["name" => "Hard", "difficulty" => 30, "auto" => 0, "demon" => 0];
			case 6:
			case 7:
			case "harder":
				return ["name" => "Harder", "difficulty" => 40, "auto" => 0, "demon" => 0];
			case 8:
			case 9:
			case "insane":
				return ["name" => "Insane", "difficulty" => 50, "auto" => 0, "demon" => 0];
			case 10:
			case "demon":
			case "harddemon":
			case "hard_demon":
			case "hard demon":
				return ["name" => "Hard Demon", "difficulty" => 50, "auto" => 0, "demon" => 1];
			case "easydemon":
			case "easy_demon":
			case "easy demon":
				return ["name" => "Easy Demon", "difficulty" => 50, "auto" => 0, "demon" => 3];
			case "mediumdemon":
			case "medium_demon":
			case "medium demon":
				return ["name" => "Medium Demon", "difficulty" => 50, "auto" => 0, "demon" => 4];
			case "insanedemon":
			case "insane_demon":
			case "insane demon":
				return ["name" => "Insane Demon", "difficulty" => 50, "auto" => 0, "demon" => 5];
			case "extremedemon":
			case "extreme_demon":
			case "extreme demon":
				return ["name" => "Extreme Demon", "difficulty" => 50, "auto" => 0, "demon" => 6];
			default:
				return ["name" => "N/A", "difficulty" => 0, "auto" => 0, "demon" => 0];
		}
	}
	
	public static function prepareDifficultyForRating($difficulty, $auto, $demon, $demonDiff) {
		if($auto) return "auto";
		if($demon) {
			switch($demonDiff) {
				case 3:
					return "easy demon";
				case 4:
					return "medium demon";
				case 5:
					return "insane demon";
				case 6:
					return "extreme demon";
				default:
					return "hard demon";
			}
		}
		switch(true) {
			case $difficulty >= 9.5:
				return "extreme demon";
			case $difficulty >= 8.5:
				return "insane demon";
			case $difficulty >= 7.5:
				return "medium demon";
			case $difficulty >= 6.5:
				return "easy demon";
			case $difficulty >= 5.5 && $difficulty < 6.5:
				return "hard demon";
			case $difficulty >= 4.5:
				return "insane";
			case $difficulty >= 3.5:
				return "harder";
			case $difficulty >= 2.5:
				return "hard";
			case $difficulty >= 1.5:
				return "normal";
			case $difficulty >= 0.5:
				return "easy";
			default:
				return "na";
		}
	}
	
	public static function rateLevel($levelID, $accountID, $difficulty, $stars, $verifyCoins, $featured) {
		require __DIR__."/connection.php";
		
		$realDifficulty = self::getLevelDifficulty($difficulty);
		if($featured) {
			$epic = $featured - 1;
			$featured = self::nextFeaturedID();
		} else $epic = $featured = 0;
		$starCoins = $verifyCoins != 0 ? 1 : 0;
		$starDemon = $realDifficulty['demon'] != 0 ? 1 : 0;
		$demonDiff = $realDifficulty['demon'];
		
		$rateLevel = $db->prepare("UPDATE levels SET starDifficulty = :starDifficulty, difficultyDenominator = 10, starStars = :starStars, starFeatured = :starFeatured, starEpic = :starEpic, starCoins = :starCoins, starDemon = :starDemon, starDemonDiff = :starDemonDiff, starAuto = :starAuto, rateDate = :rateDate WHERE levelID = :levelID");
		$rateLevel->execute([':starDifficulty' => $realDifficulty['difficulty'], ':starStars' => $stars, ':starFeatured' => $featured, ':starEpic' => $epic, ':starCoins' => $starCoins, ':starDemon' => $starDemon, ':starDemonDiff' => $demonDiff, ':starAuto' => $realDifficulty['auto'], ':rateDate' => time(), ':levelID' => $levelID]);
		
		return $realDifficulty['name'];
	}
	
	public static function nextFeaturedID() {
		require __DIR__."/connection.php";
		
		$featuredID = $db->prepare("SELECT starFeatured FROM levels ORDER BY starFeatured DESC LIMIT 1");
		$featuredID->execute();
		$featuredID = $featuredID->fetchColumn() + 1;
		
		return $featuredID;
	}
	
	public static function setLevelAsDaily($levelID, $accountID, $type) {
		require __DIR__."/connection.php";
		
		$isDaily = self::isLevelDaily($levelID, $type);
		if($isDaily) return false;
		
		$dailyTime = self::nextDailyTime($type);
		
		$setDaily = $db->prepare("INSERT INTO dailyfeatures (levelID, type, timestamp)
			VALUES (:levelID, :type, :timestamp)");
		$setDaily->execute([':levelID' => $levelID, ':type' => $type, ':timestamp' => $dailyTime]);
		
		return $dailyTime;
	}
	
	public static function isLevelDaily($levelID, $type) {
		require __DIR__."/connection.php";
		
		$isDaily = $db->prepare("SELECT feaID FROM dailyfeatures WHERE levelID = :levelID AND type = :type AND timestamp >= :time");
		$isDaily->execute([':levelID' => $levelID, ':type' => $type, ':time' => time() - ($type ? 604800 : 86400)]);
		$isDaily = $isDaily->fetchColumn();
		
		return $isDaily;
	}
	
	public static function nextDailyTime($type) {
		require __DIR__."/connection.php";
		
		$typeTime = $type ? 604800 : 86400;
		
		$dailyTime = $db->prepare("SELECT timestamp FROM dailyfeatures WHERE type = :type AND timestamp >= :time ORDER BY timestamp DESC LIMIT 1");
		$dailyTime->execute([':type' => $type, ':time' => time() - $typeTime]);
		$dailyTime = $dailyTime->fetchColumn();
		
		if(!$dailyTime) $dailyTime = time();
		$dailyTime = $type ? strtotime(date('d.m.Y', $dailyTime)." next monday") : strtotime(date('d.m.Y', $dailyTime)." tomorrow");
		
		return $dailyTime;
	}
	
	public static function setLevelAsEvent($levelID, $accountID, $duration, $rewards) {
		require __DIR__."/connection.php";
		
		$isEvent = self::isLevelEvent($levelID);
		if($isEvent) return false;
		
		$eventTime = self::nextEventTime($duration);
		
		$setEvent = $db->prepare("INSERT INTO events (levelID, timestamp, duration, rewards)
			VALUES (:levelID, :timestamp, :duration, :rewards)");
		$setEvent->execute([':levelID' => $levelID, ':timestamp' => $eventTime, ':duration' => $eventTime + $duration, ':rewards' => $rewards]);
		
		return $eventTime;
	}
	
	public static function isLevelEvent($levelID) {
		require __DIR__."/connection.php";
		
		$isEvent = $db->prepare("SELECT feaID FROM events WHERE levelID = :levelID AND duration >= :time");
		$isEvent->execute([':levelID' => $levelID, ':time' => time()]);
		$isEvent = $isEvent->fetchColumn();
		
		return $isEvent;
	}
	
	public static function nextEventTime($duration) {
		require __DIR__."/connection.php";
		
		$time = time();
		
		$eventTime = $db->prepare("SELECT duration FROM events WHERE timestamp < :time OR duration >= :duration ORDER BY duration DESC LIMIT 1");
		$eventTime->execute([':time' => $time, ':duration' => $time + $duration]);
		$eventTime = $eventTime->fetchColumn();
		
		if(!$eventTime) $eventTime = $time;
		
		return $eventTime;
	}
	
	public static function sendLevel($levelID, $accountID, $difficulty, $stars, $featured) {
		require __DIR__."/connection.php";
		
		$realDifficulty = self::getLevelDifficulty($difficulty);
		$starDemon = $realDifficulty['demon'] != 0 ? 1 : 0;
		$demonDiff = $realDifficulty['demon'];
		
		$isSent = self::isLevelSent($levelID, $accountID);
		if($isSent) return false;
		
		$sendLevel = $db->prepare("INSERT INTO suggest (suggestBy, suggestLevelId, suggestDifficulty, suggestStars, suggestFeatured, suggestAuto, suggestDemon, timestamp)
			VALUES (:accountID, :levelID, :starDifficulty, :starStars, :starFeatured, :starAuto, :starDemon, :timestamp)");
		$sendLevel->execute([':accountID' => $accountID, ':levelID' => $levelID, ':starDifficulty' => $realDifficulty['difficulty'], ':starStars' => $stars, ':starFeatured' => $featured, ':starAuto' => $realDifficulty['auto'], ':starDemon' => $realDifficulty['demon'], ':timestamp' => time()]);
		
		return $realDifficulty['name'];
	}
	
	public static function isLevelSent($levelID, $accountID) {
		require __DIR__."/connection.php";
		
		$isSent = $db->prepare("SELECT count(*) FROM suggest WHERE suggestLevelId = :levelID AND suggestBy = :accountID");
		$isSent->execute([':levelID' => $levelID, ':accountID' => $accountID]);
		$isSent = $isSent->fetchColumn();
		
		return $isSent > 0;
	}
	
	public static function removeDailyLevel($levelID, $accountID, $type) {
		require __DIR__."/connection.php";
		
		$isDaily = self::isLevelDaily($levelID, $type);
		if(!$isDaily) return false;
		
		$removeDaily = $db->prepare("UPDATE dailyfeatures SET timestamp = timestamp * -1 WHERE feaID = :feaID");
		$removeDaily->execute([':feaID' => $isDaily]);
		
		return true;
	}
	
	public static function removeEventLevel($levelID, $accountID) {
		require __DIR__."/connection.php";
		
		$isEvent = self::isLevelEvent($levelID);
		if(!$isEvent) return false;
		
		$removeEvent = $db->prepare("UPDATE events SET duration = duration * -1 WHERE feaID = :feaID");
		$removeEvent->execute([':feaID' => $isEvent]);
		
		return true;
	}
	
	public static function moveLevel($levelID, $accountID, $player) {
		require __DIR__."/connection.php";
		
		$setAccount = $db->prepare("UPDATE levels SET extID = :extID, userID = :userID, userName = :userName WHERE levelID = :levelID");
		$setAccount->execute([':extID' => $player['extID'], ':userID' => $player['userID'], ':userName' => $player['userName'], ':levelID' => $levelID]);
		
		return true;
	}
	
	public static function lockUpdatingLevel($levelID, $accountID, $lockUpdating) {
		require __DIR__."/connection.php";
		
		$lockLevel = $db->prepare("UPDATE levels SET updateLocked = :updateLocked WHERE levelID = :levelID");
		$lockLevel->execute([':updateLocked' => $lockUpdating, ':levelID' => $levelID]);
		
		return true;
	}
	
	public static function deleteComment($userID, $commentID) {
		require __DIR__."/connection.php";
		
		$getComment = $db->prepare("SELECT count(*) FROM comments WHERE userID = :userID AND commentID = :commentID");
		$getComment->execute([':userID' => $userID, ':commentID' => $commentID]);
		$getComment = $getComment->fetchColumn();
		if(!$getComment) return false;
		
		$deleteComment = $db->prepare("DELETE FROM comments WHERE commentID = :commentID");
		$deleteComment->execute([':commentID' => $commentID]);
		
		return true;
	}
	
	public static function renameLevel($levelID, $accountID, $levelName) {
		require __DIR__."/connection.php";
		
		$renameLevel = $db->prepare("UPDATE levels SET levelName = :levelName WHERE levelID = :levelID");
		$renameLevel->execute([':levelID' => $levelID, ':levelName' => $levelName]);
		
		return true;
	}
	
	public static function changeLevelPassword($levelID, $accountID, $newPassword) {
		require __DIR__."/connection.php";
		
		if($newPassword == '000000') $newPassword = '';
		
		$changeLevelPassword = $db->prepare("UPDATE levels SET password = :password WHERE levelID = :levelID");
		$changeLevelPassword->execute([':levelID' => $levelID, ':password' => "1".$newPassword]);
		
		return true;
	}
	
	public static function changeLevelSong($levelID, $accountID, $songID) {
		require __DIR__."/connection.php";
		
		$changeLevelSong = $db->prepare("UPDATE levels SET songID = :songID WHERE levelID = :levelID");
		$changeLevelSong->execute([':levelID' => $levelID, ':songID' => $songID]);
		
		return true;
	}
	
	public static function changeLevelDescription($levelID, $accountID, $description) {
		require __DIR__."/connection.php";
		require_once __DIR__."/exploitPatch.php";
		
		$changeLevelDescription = $db->prepare("UPDATE levels SET levelDesc = :levelDesc WHERE levelID = :levelID");
		$changeLevelDescription->execute([':levelID' => $levelID, ':levelDesc' => Escape::url_base64_encode($description)]);
		
		return true;
	}
	
	public static function changeLevelPrivacy($levelID, $accountID, $privacy) {
		require __DIR__."/connection.php";
		
		$changeLevelPrivacy = $db->prepare("UPDATE levels SET unlisted = :privacy, unlisted2 = :privacy WHERE levelID = :levelID");
		$changeLevelPrivacy->execute([':levelID' => $levelID, ':privacy' => $privacy]);
		
		return true;
	}
	
	public static function shareCreatorPoints($levelID, $accountID, $targetUserID) {
		require __DIR__."/connection.php";
		
		$changeLevel = $db->prepare("UPDATE levels SET isCPShared = 1 WHERE levelID = :levelID");
		$changeLevel->execute([':levelID' => $levelID]);
		
		$checkIfShared = $db->prepare("SELECT count(*) FROM cpshares WHERE levelID = :levelID AND userID = :userID");
		$checkIfShared->execute([':levelID' => $levelID, ':userID' => $targetUserID]);
		$checkIfShared = $checkIfShared->fetchColumn();
		if($checkIfShared) return false;
		
		$shareCreatorPoints = $db->prepare("INSERT INTO cpshares (levelID, userID)
			VALUES (:levelID, :userID)");
		$shareCreatorPoints->execute([':levelID' => $levelID, ':userID' => $targetUserID]);
		
		return true;
	}
	
	public static function lockCommentingOnLevel($levelID, $accountID, $lockCommenting) {
		require __DIR__."/connection.php";

		$lockLevel = $db->prepare("UPDATE levels SET commentLocked = :commentLocked WHERE levelID = :levelID");
		$lockLevel->execute([':commentLocked' => $lockCommenting, ':levelID' => $levelID]);
		
		return true;
	}
	
	/*
		Lists-related functions
	*/
	
	public static function getListLevels($listID) {
		require __DIR__."/connection.php";
		
		$listLevels = $db->prepare('SELECT listlevels FROM lists WHERE listID = :listID');
		$listLevels->execute([':listID' => $listID]);
		$listLevels = $listLevels->fetchColumn();
		
		return $listLevels;
	}
	
	/*
		Audio-related functions
	*/
	
	public static function getSongByID($songID, $column = "*", $library = false) {
		require __DIR__."/connection.php";
		
		$isLocalSong = true;
		
		$song = $db->prepare("SELECT $column FROM songs WHERE ID = :songID");
		$song->execute([':songID' => $songID]);
		$song = $song->fetch();
		
		if(empty($song)) {
			$song = self::getLibrarySongInfo($songID, 'music', $library);
			if(!$song) return false;
			$isLocalSong = false;
		}
		
		if($column != "*") return $song[$column];
		else return array("isLocalSong" => $isLocalSong, "ID" => $song["ID"], "name" => $song["name"], "authorName" => $song["authorName"], "size" => $song["size"], "duration" => $song["duration"], "download" => $song["download"], "reuploadTime" => $song["reuploadTime"], "reuploadID" => $song["reuploadID"]);
	}
	
	public static function getSFXByID($sfxID, $column = "*") {
		require __DIR__."/connection.php";
		
		$sfx = $db->prepare("SELECT $column FROM sfxs WHERE ID = :sfxID");
		$sfx->execute([':sfxID' => $sfxID]);
		$sfx = $sfx->fetch();
		if(empty($sfx)) return false;
		
		if($column != "*") return $sfx[$column];
		else return array("ID" => $sfx["ID"], "name" => $sfx["name"], "authorName" => $sfx["authorName"], "size" => $sfx["size"], "download" => $sfx["download"], "reuploadTime" => $sfx["reuploadTime"], "reuploadID" => $sfx["reuploadID"]);
	}
	
	public static function getSongString($songID) {
		require __DIR__."/connection.php";
		require_once __DIR__."/exploitPatch.php";
		$librarySong = false;
		$extraSongString = '';
		$song = self::getSongByID($songID);
		if(!$song) {
			$librarySong = true;
			$song = self::getLibrarySongInfo($song['songID']);
		}
		if(!$song || empty($song['ID']) || $song["isDisabled"] == 1) return false;
		$downloadLink = urlencode($song["download"]);
		if($librarySong) {
			$artistsNames = [];
			$artistsArray = explode('.', $song['artists']);
			if(count($artistsArray) > 0) {
				foreach($artistsArray AS &$artistID) {
					$artistData = self::getLibrarySongAuthorInfo($artistID);
					if(!$artistData) continue;
					$artistsNames[] = $artistID.','.$artistData['name'];
				}
			}
			$artistsNames = implode(',', $artistsNames);
			$extraSongString = '~|~9~|~'.$song['priorityOrder'].'~|~11~|~'.$song['ncs'].'~|~12~|~'.$song['artists'].'~|~13~|~'.($song['new'] ? 1 : 0).'~|~14~|~'.$song['new'].'~|~15~|~'.$artistsNames;
		}
		return "1~|~".$song["ID"]."~|~2~|~".Escape::translit(str_replace("#", "", $song["name"]))."~|~3~|~".$song["authorID"]."~|~4~|~".Escape::translit($song["authorName"])."~|~5~|~".$song["size"]."~|~6~|~~|~10~|~".$downloadLink."~|~7~|~~|~8~|~1".$extraSongString;
	}
	
	public static function getLibrarySongInfo($audioID, $type = 'music', $extraLibrary = false) {
		require __DIR__."/../../config/dashboard.php";
		if(!file_exists(__DIR__.'/../../'.$type.'/ids.json')) return false;
		$servers = $serverIDs = $serverNames = [];
		foreach($customLibrary AS $customLib) {
			$servers[$customLib[0]] = $customLib[2];
			$serverNames[$customLib[0]] = $customLib[1];
			$serverIDs[$customLib[2]] = $customLib[0];
		}
		
		$library = $extraLibrary ? $extraLibrary : json_decode(file_get_contents(__DIR__.'/../../'.$type.'/ids.json'), true);
		if(!isset($library['IDs'][$audioID]) || ($type == 'music' && $library['IDs'][$audioID]['type'] != 1)) return false;
		
		if($type == 'music') {
			$song = $library['IDs'][$audioID];
			$author = $library['IDs'][$song['authorID']];
			$token =self::randomString(22);
			$expires = time() + 3600;
			$link = $servers[$song['server']].'/music/'.$song['originalID'].'.ogg?token='.$token.'&expires='.$expires;
			return ['server' => $song['server'], 'ID' => $audioID, 'name' => $song['name'], 'authorID' => $song['authorID'], 'authorName' => $author['name'], 'size' => round($song['size'] / 1024 / 1024, 2), 'download' => $link, 'seconds' => $song['seconds'], 'tags' => $song['tags'], 'ncs' => $song['ncs'], 'artists' => $song['artists'], 'externalLink' => $song['externalLink'], 'new' => $song['new'], 'priorityOrder' => $song['priorityOrder']];
		} else {
			$SFX = $library['IDs'][$audioID];
			$token = self::randomString(22);
			$expires = time() + 3600;
			$link = $servers[$SFX['server']] != null ? $servers[$SFX['server']].'/sfx/s'.$SFX['ID'].'.ogg?token='.$token.'&expires='.$expires : self::getSFXByID($SFX['ID'], 'download');
			return ['isLocalSFX' => $servers[$SFX['server']] == null, 'server' => $SFX['server'], 'ID' => $audioID, 'name' => $song['name'], 'download' => $link, 'originalID' => $SFX['ID']];
		}
	}
	
	public static function getLibrarySongAuthorInfo($songID) {
		if(!file_exists(__DIR__.'/../../music/ids.json')) return false;
		
		$library = json_decode(file_get_contents(__DIR__.'/../../music/ids.json'), true);
		if(!isset($library['IDs'][$songID])) return false;
		
		return $library['IDs'][$songID];
	}
	
	public static function updateLibraries($token, $expires, $mainServerTime, $type = 0) {
		require __DIR__."/../../config/dashboard.php";
		require_once __DIR__."/exploitPatch.php";
		
		$servers = $times = [];
		
		$types = ['sfx', 'music'];
		if(!isset($customLibrary)) global $customLibrary;
		if(empty($customLibrary)) $customLibrary = [[1, 'Geometry Dash', 'https://geometrydashfiles.b-cdn.net'], [3, $gdps, null]];
		
		foreach($customLibrary AS $library) {
			if(($types[$type] == 'sfx' AND $library[3] === 1) OR ($types[$type] == 'music' AND $library[3] === 0)) continue;
			
			if($library[2] !== null) $servers['s'.$library[0]] = $library[2];
		}
		
		$updatedLib = false;
		foreach($servers AS $key => &$server) {
			$versionUrl = $server.'/'.$types[$type].'/'.$types[$type].'library_version'.($types[$type] == 'music' ? '_02' : '').'.txt';
			$dataUrl = $server.'/'.$types[$type].'/'.$types[$type].'library'.($types[$type] == 'music' ? '_02' : '').'.dat';

			$oldVersion = file_exists(__DIR__.'/../../'.$types[$type].'/'.$key.'.txt') ? file_get_contents(__DIR__.'/../../'.$types[$type].'/'.$key.'.txt') : [0, 0];
			$times[] = (int)$oldVersion[1];
			
			if((int)$oldVersion[1] + 600 > time()) continue; // Download library only once per 10 minutes
			
			$curl = curl_init($versionUrl.'?token='.$token.'&expires='.$expires);
			curl_setopt_array($curl, [
				CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
				CURLOPT_RETURNTRANSFER => 1,
				CURLOPT_FOLLOWLOCATION => 1
			]);
			$newVersion = (int)Escape::number(curl_exec($curl));
			curl_close($curl);
			
			if($newVersion > $oldVersion[0] || !$oldVersion[0]) {
				file_put_contents(__DIR__.'/../../'.$types[$type].'/'.$key.'.txt', $newVersion.', '.time());
				
				$download = curl_init($dataUrl.'?token='.$token.'&expires='.$expires.'&dashboard=1');
				curl_setopt_array($download, [
					CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
					CURLOPT_RETURNTRANSFER => 1,
					CURLOPT_FOLLOWLOCATION => 1
				]);
				$dat = curl_exec($download);
				$resultStatus = curl_getinfo($download, CURLINFO_HTTP_CODE);
				curl_close($download);
				
				if($resultStatus == 200) {
					file_put_contents(__DIR__.'/../../'.$types[$type].'/'.$key.'.dat', $dat);
					$updatedLib = true;
				}
			}
		}
		// Now this server's version check
		if(file_exists(__DIR__.'/../../'.$types[$type].'/gdps.txt')) $oldVersion = file_get_contents(__DIR__.'/../../'.$types[$type].'/gdps.txt');
		else {
			$oldVersion = 0;
			file_put_contents(__DIR__.'/../../'.$types[$type].'/gdps.txt', $mainServerTime);
		}
		
		$times[] = $mainServerTime;
		rsort($times);
		
		if($oldVersion < $mainServerTime || $updatedLib) self::generateDATFile($times[0], $type);
	}
	
	public static function generateDATFile($mainServerTime, $type = 0) {
		require __DIR__."/connection.php";
		require __DIR__."/../../config/dashboard.php";
		require_once __DIR__."/exploitPatch.php";
		
		$library = $servers = $serverIDs = $serverTypes = [];
		if(!isset($customLibrary)) global $customLibrary;
		if(empty($customLibrary)) $customLibrary = [[1, 'Geometry Dash', 'https://geometrydashfiles.b-cdn.net', 2], [3, $gdps, null, 2]]; 
		
		$types = ['sfx', 'music'];
		foreach($customLibrary AS $customLib) {
			if($customLib[2] !== null) {
				$servers['s'.$customLib[0]] = $customLib[0];
			}
			$serverIDs[$customLib[2]] = $customLib[0];
			if($types[$type] == 'sfx') {
				if($customLib[3] != 1) $library['folders'][($customLib[0] + 1)] = [
					'name' => Escape::dat($customLib[1]),
					'type' => 1,
					'parent' => 1
				];
			} else {
				if($customLib[3] != 0) $library['tags'][$customLib[0]] = [
					'ID' => $customLib[0],
					'name' => Escape::dat($customLib[1]),
				];
			}
		}
		
		$idsConverter = file_exists(__DIR__.'/../../'.$types[$type].'/ids.json') ? json_decode(file_get_contents(__DIR__.'/../../'.$types[$type].'/ids.json'), true) : ['count' => ($type == 0 ? count($customLibrary) + 2 : 8000000), 'IDs' => [], 'originalIDs' => []];
		$skipSFXIDs = file_exists(__DIR__.'/../../config/skipSFXIDs.json') ? json_decode(file_get_contents(__DIR__.'/../../config/skipSFXIDs.json'), true) : [];
		
		foreach($servers AS $key => $server) {
			if(!file_exists(__DIR__.'/../../'.$types[$type].'/'.$key.'.dat')) continue;
			$res = $bits = null;
			
			$res = file_get_contents(__DIR__.'/../../'.$types[$type].'/'.$key.'.dat');
			$res = mb_convert_encoding($res, 'UTF-8', 'UTF-8');
			try {
				$res = Escape::url_base64_decode($res);
				$res = zlib_decode($res);
			} catch(Exception $e) {
				unlink(__DIR__.'/../../'.$types[$type].'/'.$key.'.dat');
				continue;
			}
			
			$res = explode('|', $res);
			if(!$type) {
				// SFX library decoding was made by MigMatos, check their ObeyGDBot! https://obeybd.web.app/
				for($i = 0; $i < count($res); $i++) {
					$res[$i] = explode(';', $res[$i]);
					if($i === 0) {
						$library['version'] = $mainServerTime;
						$version = explode(',', $res[0][0]);
						$version[1] = $mainServerTime;
						$version = implode(',', $version);
					}
					for($j = 1; $j <= count($res[$i]); $j++) {
						$bits = explode(',', $res[$i][$j]);
						switch($i) {
							case 0: // File/Folder
								if(empty(trim($bits[1])) || empty($bits[0]) || !is_numeric($bits[0])) break;
								if(empty($idsConverter['originalIDs'][$server][$bits[0]])) {
									$idsConverter['count']++;
									while(in_array($idsConverter['count'], $skipSFXIDs)) $idsConverter['count']++;
									$idsConverter['IDs'][$idsConverter['count']] = ['server' => $server, 'ID' => $bits[0], 'name' => $bits[1], 'type' => $bits[2]];
									$idsConverter['originalIDs'][$server][$bits[0]] = $idsConverter['count'];
									$bits[0] = $idsConverter['count'];
								} else {
									$bits[0] = $idsConverter['originalIDs'][$server][$bits[0]];
									if(!isset($idsConverter['IDs'][$bits[0]]['name'])) $idsConverter['IDs'][$bits[0]] = ['server' => $server, 'ID' => $bits[0], 'name' => $bits[1], 'type' => $bits[2]];
								}
								if($bits[3] != 1) {
									if(empty($idsConverter['originalIDs'][$server][$bits[3]])) {
										$idsConverter['count']++;
										while(in_array($idsConverter['count'], $skipSFXIDs)) $idsConverter['count']++;
										$idsConverter['IDs'][$idsConverter['count']] = ['server' => $server, 'ID' => $bits[3], 'name' => $bits[1], 'type' => 1];
										$idsConverter['originalIDs'][$server][$bits[3]] = $idsConverter['count'];
										$bits[3] = $idsConverter['count'];
									} else $bits[3] = $idsConverter['originalIDs'][$server][$bits[3]];
								} else $bits[3] = $server + 1;
								if($bits[2]) {
									$library['folders'][$bits[0]] = [
										'name' => Escape::dat($bits[1]),
										'type' => (int)$bits[2],
										'parent' => (int)$bits[3]
									];
								} else {
									$library['files'][$bits[0]] = [
										'name' => Escape::dat($bits[1]),
										'type' => (int)$bits[2],
										'parent' => (int)$bits[3],
										'bytes' => (int)$bits[4],
										'milliseconds' => (int)$bits[5],
									];
								}
								break;
							case 1: // Credit
								if(empty(trim($bits[0])) || empty(trim($bits[1]))) continue 2;
								$library['credits'][Escape::dat($bits[0])] = [
									'name' => Escape::dat($bits[0]),
									'website' => Escape::dat($bits[1]),
								];
								break;
						}
					}
				}
				$sfxs = $db->prepare("SELECT sfxs.*, accounts.userName FROM sfxs JOIN accounts ON accounts.accountID = sfxs.reuploadID");
				$sfxs->execute();
				$sfxs = $sfxs->fetchAll();
				$folderID = $gdpsLibrary = [];
				$server = $serverIDs[null];
				foreach($sfxs AS &$customSFX) {
					if(!isset($folderID[$customSFX['reuploadID']])) {
						if(empty($idsConverter['originalIDs'][$server][$customSFX['reuploadID']])) {
							$idsConverter['count']++;
							$idsConverter['IDs'][$idsConverter['count']] = ['server' => $server, 'ID' => $customSFX['ID'], 'name' => $customSFX['userName'].'\'s SFXs', 'type' => 1];
							$idsConverter['originalIDs'][$server][$customSFX['reuploadID']] = $idsConverter['count'];
							$newID = $idsConverter['count'];
						} else $newID = $idsConverter['originalIDs'][$server][$customSFX['reuploadID']];
						$library['folders'][$newID] = [
							'name' => Escape::dat($customSFX['userName']).'\'s SFXs',
							'type' => 1,
							'parent' => (int)($server + 1)
						];
						$gdpsLibrary['folders'][$newID] = [
							'name' => Escape::dat($customSFX['userName']).'\'s SFXs',
							'type' => 1,
							'parent' => 1
						];
						$folderID[$customSFX['reuploadID']] = true;
					}
					if(empty($idsConverter['originalIDs'][$server][$customSFX['ID'] + 8000000])) {
						$idsConverter['count']++;
						$idsConverter['IDs'][$idsConverter['count']] = ['server' => $server, 'ID' => $customSFX['ID'], 'name' => $customSFX['name'], 'type' => 0];
						$idsConverter['originalIDs'][$server][$customSFX['ID'] + 8000000] = $idsConverter['count'];
						$customSFX['ID'] = $idsConverter['count'];
					} else $customSFX['ID'] = $idsConverter['originalIDs'][$server][$customSFX['ID'] + 8000000];
					$library['files'][$customSFX['ID']] = $gdpsLibrary['files'][$customSFX['ID']] = [
						'name' => Escape::dat($customSFX['name']),
						'type' => 0,
						'parent' => (int)$idsConverter['originalIDs'][$server][$customSFX['reuploadID']],
						'bytes' => (int)$customSFX['size'],
						'milliseconds' => (int)($customSFX['milliseconds'] / 10)
					];
				}
				
				$filesEncrypted = $creditsEncrypted = [];
				foreach($library['folders'] AS $id => &$folder) $filesEncrypted[] = implode(',', [$id, $folder['name'], 1, $folder['parent'], 0, 0]);
				foreach($library['files'] AS $id => &$file) $filesEncrypted[] = implode(',', [$id, $file['name'], 0, $file['parent'], $file['bytes'], $file['milliseconds']]);
				foreach($library['credits'] AS &$credit) $creditsEncrypted[] = implode(',', [$credit['name'], $credit['website']]);
				$encrypted = $version.";".implode(';', $filesEncrypted)."|" .implode(';', $creditsEncrypted).';';
				
				$filesEncrypted = $creditsEncrypted = [];
				foreach($gdpsLibrary['folders'] AS $id => &$folder) $filesEncrypted[] = implode(',', [$id, $folder['name'], 1, $folder['parent'], 0, 0]);
				foreach($gdpsLibrary['files'] AS $id => &$file) $filesEncrypted[] = implode(',', [$id, $file['name'], 0, $file['parent'], $file['bytes'], $file['milliseconds']]);
				$creditsEncrypted[] = implode(',', [$gdps, $_SERVER['SERVER_NAME']]);
				$gdpsEncrypted = $version.";".implode(';', $filesEncrypted)."|" .implode(';', $creditsEncrypted).';';
			} else {
				$version = $mainServerTime;
				array_shift($res);
				$x = 0;
				foreach($res AS &$data) {
					$data = rtrim($data, ';');
					$music = explode(';', $data);
					foreach($music AS &$songString) {
						$song = explode(',', $songString);
						$originalID = $song[0];
						if(empty($song[0]) || !is_numeric($song[0])) continue;
						if(empty($idsConverter['originalIDs'][$server][$song[0]])) {
							$idsConverter['count']++;
							$idsConverter['IDs'][$idsConverter['count']] = ['server' => $server, 'ID' => $song[0], 'type' => $x];
							$idsConverter['originalIDs'][$server][$song[0]] = $idsConverter['count'];
							$song[0] = $idsConverter['count'];
						} else $song[0] = $idsConverter['originalIDs'][$server][$song[0]];
						switch($x) {
							case 0:
								$idsConverter['IDs'][$song[0]] = $library['authors'][$song[0]] = [
									'server' => $server,
									'type' => $x,
									'originalID' => $originalID,
									'authorID' => $song[0],
									'name' => Escape::dat($song[1]),
									'link' => Escape::dat($song[2]),
									'yt' => Escape::dat($song[3])
								];
								break;
							case 1:
								if(empty($idsConverter['originalIDs'][$server][$song[2]])) {
									$idsConverter['count']++;
									$idsConverter['IDs'][$idsConverter['count']] = ['server' => $server, 'ID' => $song[2], 'type' => $x];
									$idsConverter['originalIDs'][$server][$song[2]] = $idsConverter['count'];
									$song[2] = $idsConverter['count'];
								} else $song[2] = $idsConverter['originalIDs'][$server][$song[2]];
								$tags = explode('.', $song[5]);
								$newTags = [];
								foreach($tags AS &$tag) {
									if(empty($tag)) continue;
									if(empty($idsConverter['originalIDs'][$server][$tag])) {
										$idsConverter['count']++;
										$idsConverter['IDs'][$idsConverter['count']] = ['server' => $server, 'ID' => $tag, 'type' => 2];
										$idsConverter['originalIDs'][$server][$tag] = $idsConverter['count'];
										$tag = $idsConverter['count'];
									} else $tag = $idsConverter['originalIDs'][$server][$tag];
									$newTags[] = $tag;
								}
								$newTags[] = $server;
								$tags = '.'.implode('.', $newTags).'.';
								$newArtists = [];
								$artists = explode('.', $song[7]);
								foreach($artists AS &$artist) {
									if(empty($artist)) continue;
									if(empty($idsConverter['originalIDs'][$server][$artist])) {
										$idsConverter['count']++;
										$idsConverter['IDs'][$idsConverter['count']] = ['server' => $server, 'ID' => $artist, 'type' => 0];
										$idsConverter['originalIDs'][$server][$artist] = $idsConverter['count'];
										$artist = $idsConverter['count'];
									} else $artist = $idsConverter['originalIDs'][$server][$artist];
									$newArtists[] = $artist;
								}
								$artists = implode('.', $newArtists);
								$idsConverter['IDs'][$song[0]] = $library['songs'][$song[0]] = [
									'server' => $server,
									'type' => $x,
									'originalID' => $originalID,
									'ID' => $song[0],
									'name' => Escape::dat($song[1]),
									'authorID' => $song[2],
									'size' => $song[3],
									'seconds' => $song[4],
									'tags' => $tags,
									'ncs' => $song[6] ?: 0,
									'artists' => $artists,
									'externalLink' => $song[8] ?: '',
									'new' => $song[9] ?: 0,
									'priorityOrder' => $song[10] ?: 0
								];
								break;
							case 2:
								$idsConverter['IDs'][$song[0]] = $library['tags'][$song[0]] = [
									'server' => $server,
									'type' => $x,
									'originalID' => $originalID,
									'ID' => $song[0],
									'name' => Escape::dat($song[1])
								];
								break;
						}
					}
					$x++;
				}
				$songs = $db->prepare("SELECT songs.*, accounts.userName FROM songs JOIN accounts ON accounts.accountID = songs.reuploadID WHERE isDisabled = 0");
				$songs->execute();
				$songs = $songs->fetchAll();
				$folderID = $accIDs = $gdpsLibrary = [];
				$c = 100;
				foreach($songs AS &$customSongs) {
					$c++;
					$authorName = trim(Escape::text(Escape::dat(Escape::translit($customSongs['authorName'])), 40));
					if(empty($authorName)) $authorName = 'Reupload';
					if(empty($folderID[$authorName])) {
						$folderID[$authorName] = $c;
						$library['authors'][$serverIDs[null]. 0 .$folderID[$authorName]] = $gdpsLibrary['authors'][$serverIDs[null]. 0 .$folderID[$authorName]] = [
							'authorID' => (int)($serverIDs[null]. 0 .$folderID[$authorName]),
							'name' => $authorName,
							'link' => ' ',
							'yt' => ' '
						];
					}
					if(empty($accIDs[$customSongs['reuploadID']])) {
						$c++;
						$accIDs[$customSongs['reuploadID']] = $c;
						$library['tags'][$serverIDs[null]. 0 .$accIDs[$customSongs['reuploadID']]] = $gdpsLibrary['tags'][$serverIDs[null]. 0 .$accIDs[$customSongs['reuploadID']]] = [
							'ID' => (int)($serverIDs[null]. 0 .$accIDs[$customSongs['reuploadID']]),
							'name' => Escape::text(Escape::dat($customSongs['userName']), 30),
						];
					}
					$customSongs['name'] = trim(Escape::text(Escape::dat(Escape::translit($customSongs['name'])), 40));
					$library['songs'][$customSongs['ID']] = $gdpsLibrary['songs'][$customSongs['ID']] = [
						'ID' => ($customSongs['ID']),
						'name' => !empty($customSongs['name']) ? $customSongs['name'] : 'Unnamed',
						'authorID' => (int)($serverIDs[null]. 0 .$folderID[$authorName]),
						'size' => $customSongs['size'] * 1024 * 1024,
						'seconds' => $customSongs['duration'],
						'tags' => '.'.$serverIDs[null].'.'.$serverIDs[null]. 0 .$accIDs[$customSongs['reuploadID']].'.',
						'ncs' => 0,
						'artists' => '',
						'externalLink' => urlencode($customSongs['download']),
						'new' => ($customSongs['reuploadTime'] > time() - 604800 ? 1 : 0),
						'priorityOrder' => 0
					];
				}
				$authorsEncrypted = $songsEncrypted = $tagsEncrypted = [];
				foreach($library['authors'] AS &$authorList) {
					unset($authorList['server'], $authorList['type'], $authorList['originalID']);
					$authorsEncrypted[] = implode(',', $authorList);
				}
				foreach($library['songs'] AS &$songsList) {
					unset($songsList['server'], $songsList['type'], $songsList['originalID']);
					$songsEncrypted[] = implode(',', $songsList);
				}
				foreach($library['tags'] AS &$tagsList) {
					unset($tagsList['server'], $tagsList['type'], $tagsList['originalID']);
					$tagsEncrypted[] = implode(',', $tagsList);
				}
				$encrypted = $version."|".implode(';', $authorsEncrypted).";|" .implode(';', $songsEncrypted).";|" .implode(';', $tagsEncrypted).';';
				
				$authorsEncrypted = $songsEncrypted = $tagsEncrypted = [];
				foreach($gdpsLibrary['authors'] AS &$authorList) {
					unset($authorList['server'], $authorList['type'], $authorList['originalID']);
					$authorsEncrypted[] = implode(',', $authorList);
				}
				foreach($gdpsLibrary['songs'] AS &$songsList) {
					unset($songsList['server'], $songsList['type'], $songsList['originalID']);
					$songsEncrypted[] = implode(',', $songsList);
				}
				foreach($gdpsLibrary['tags'] AS &$tagsList) {
					unset($tagsList['server'], $tagsList['type'], $tagsList['originalID']);
					$tagsEncrypted[] = implode(',', $tagsList);
				}
				$gdpsEncrypted = $version."|".implode(';', $authorsEncrypted).";|" .implode(';', $songsEncrypted).";|" .implode(';', $tagsEncrypted).';';
			}
		}

		file_put_contents(__DIR__.'/../../'.$types[$type].'/ids.json', json_encode($idsConverter, JSON_PRETTY_PRINT | JSON_INVALID_UTF8_IGNORE));
		$encrypted = zlib_encode($encrypted, ZLIB_ENCODING_DEFLATE);
		$encrypted = Escape::url_base64_encode($encrypted);
		file_put_contents(__DIR__.'/../../'.$types[$type].'/gdps.dat', $encrypted);
		
		$gdpsEncrypted = zlib_encode($gdpsEncrypted, ZLIB_ENCODING_DEFLATE);
		$gdpsEncrypted = Escape::url_base64_encode($gdpsEncrypted);
		file_put_contents(__DIR__.'/../../'.$types[$type].'/standalone.dat', $gdpsEncrypted);
	}
	
	public static function lastSongTime() {
		require __DIR__."/connection.php";
		
		$lastSongTime = $db->prepare('SELECT reuploadTime FROM songs WHERE reuploadTime > 0 ORDER BY reuploadTime DESC LIMIT 1');
		$lastSongTime->execute();
		$lastSongTime = $lastSongTime->fetchColumn();
		if(!$lastSongTime) $lastSongTime = 1;
		
		return $lastSongTime;
	}
	
	public static function lastSFXTime() {
		require __DIR__."/connection.php";
		
		$lastSongTime = $db->prepare('SELECT reuploadTime FROM sfxs WHERE reuploadTime > 0 ORDER BY reuploadTime DESC LIMIT 1');
		$lastSongTime->execute();
		$lastSongTime = $lastSongTime->fetchColumn();
		if(!$lastSongTime) $lastSongTime = 1;
		
		return $lastSongTime;
	}
	
	/*
		Utils
	*/
	
	public static function logAction($accountID, $IP, $type, $value1 = '', $value2 = '', $value3 = '', $value4 = '', $value5 = '', $value6 = '') {
		require __DIR__."/connection.php";
		
		$insertAction = $db->prepare('INSERT INTO actions (account, type, timestamp, value, value2, value3, value4, value5, value6, IP)
			VALUES (:account, :type, :timestamp, :value, :value2, :value3, :value4, :value5, :value6, :IP)');
		$insertAction->execute([':account' => $accountID, ':type' => $type, ':value' => $value1, ':value2' => $value2, ':value3' => $value3, ':value4' => $value4, ':value5' => $value5, ':value6' => $value6, ':timestamp' => time(), ':IP' => $IP]);
		
		return $db->lastInsertId();
	}
	
	public static function randomString($length = 6) {
		$randomString = openssl_random_pseudo_bytes(round($length / 2, 0, PHP_ROUND_HALF_UP));
		if($randomString == false) {
			$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
			$charactersLength = strlen($characters);
			$randomString = '';
			for ($i = 0; $i < $length; $i++) {
				$randomString .= $characters[rand(0, $charactersLength - 1)];
			}
			return $randomString;
		}
		$randomString = bin2hex($randomString);
		return $randomString;
	}
	
	public static function makeTime($time, $extraTextArray = []) {
		require __DIR__."/../../config/dashboard.php";
		if(!isset($timeType)) $timeType = 0;
		$extraText = !empty($extraTextArray) ? implode(", ", $extraTextArray).', ' : '';
		switch($timeType) {
			case 1:
				if(date("d.m.Y", $time) == date("d.m.Y", time())) return $extraText.date("G;i", $time);
				elseif(date("Y", $time) == date("Y", time())) return $extraText.date("d.m", $time);
				else return $extraText.date("d.m.Y", $time);
				break;
			case 2:
				// taken from https://stackoverflow.com/a/36297417
				$isFuture = false;
				$time = time() - $time;
				if($time < 0) {
					$time = abs($time);
					$isFuture = true;
				}
				$tokens = array (31536000 => 'year', 2592000 => 'month', 604800 => 'week', 86400 => 'day', 3600 => 'hour', 60 => 'minute', 1 => 'second');
				foreach($tokens as $unit => $text) {
					if($time < $unit) continue;
					$numberOfUnits = floor($time / $unit);
					return $extraText.($isFuture ? 'in ' : '').$numberOfUnits.' '.$text.(($numberOfUnits > 1) ? 's' : '');
				}
				break;
			default:
				return $extraText.date("d/m/Y G.i", $time);
				break;
		}
	}
	
	public static function rateItem($accountID, $itemID, $type, $isLike) {
		require __DIR__."/connection.php";
		require_once __DIR__."/ip.php";
		
		$IP = IP::getIP();
		
		$checkIfRated = $db->prepare("SELECT count(*) FROM actions_likes WHERE itemID = :itemID AND type = :type AND (ip = INET6_ATON(:IP) OR accountID = :accountID)");
		$checkIfRated->execute([':itemID' => $itemID, ':type' => $type, ':IP' => $IP, ':accountID' => $accountID]);
		$checkIfRated = $checkIfRated->fetchColumn();
		if($checkIfRated) return false;
		
		$rateItemAction = $db->prepare("INSERT INTO actions_likes (itemID, type, isLike, ip, accountID)
			VALUES (:itemID, :type, :isLike, INET6_ATON(:IP), :accountID)");
		$rateItemAction->execute([':itemID' => $itemID, ':type' => $type, ':isLike' => $isLike, ':IP' => $IP, ':accountID' => $accountID]);
		
		switch($type) {
			case 1:
				$table = "levels";
				$column = "levelID";
				break;
			case 2:
				$table = "comments";
				$column = "commentID";
				break;
			case 3:
				$table = "acccomments";
				$column = "commentID";
				break;
			case 4:
				$table = "lists";
				$column = "listID";
				break;
		}
		$rateColumn = $isLike ? 'likes' : 'dislikes';
		
		$rateItem = $db->prepare("UPDATE ".$table." SET ".$rateColumn." = ".$rateColumn." + 1 WHERE ".$column." = :itemID");
		$rateItem->execute([':itemID' => $itemID]);
		
		return true;
	}
}
?>