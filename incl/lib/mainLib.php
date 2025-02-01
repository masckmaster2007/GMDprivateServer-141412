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
	
	/*
		Utils
	*/
	
	public static function logAction($accountID, $IP, $type, $value1 = '', $value2 = '', $value3 = '', $value4 = '', $value5 = '', $value6 = '') {
		require __DIR__."/connection.php";
		
		$insertAction = $db->prepare('INSERT INTO actions (account, type, timestamp, value, value2, value3, value4, value5, value6, IP) VALUES (:account, :type, :timestamp, :value, :value2, :value3, :value4, :value5, :value6, :IP)');
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
	
	public static function makeTime($time) {
		require __DIR__ . "/../../config/dashboard.php";
		if(!isset($timeType)) $timeType = 0;
		switch($timeType) {
			case 1:
				if(date("d.m.Y", $time) == date("d.m.Y", time())) return date("G;i", $time);
				elseif(date("Y", $time) == date("Y", time())) return date("d.m", $time);
				else return date("d.m.Y", $time);
				break;
			case 2:
				// taken from https://stackoverflow.com/a/36297417
				$time = time() - $time;
				$time = ($time < 1) ? 1 : $time;
				$tokens = array (31536000 => 'year', 2592000 => 'month', 604800 => 'week', 86400 => 'day', 3600 => 'hour', 60 => 'minute', 1 => 'second');
				foreach($tokens as $unit => $text) {
					if($time < $unit) continue;
					$numberOfUnits = floor($time / $unit);
					return $numberOfUnits . ' ' . $text . (($numberOfUnits > 1) ? 's' : '');
				}
				break;
			default:
				return date("d/m/Y G.i", $time);
				break;
		}
	}
}
?>