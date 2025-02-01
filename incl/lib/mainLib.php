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
		$userID = $this->createUser($userName, $accountID, $IP);
		
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
	
	public function createUser($userName, $accountID, $IP) {
		require __DIR__."/connection.php";
		
		$isRegistered = is_numeric($accountID) ? 1 : 0;
		
		$createUser = $db->prepare("INSERT INTO users (isRegistered, extID, userName, IP)
			VALUES (:isRegistered, :extID, :userName, :IP)");
		$createUser->execute([':isRegistered' => $isRegistered, ':extID' => $accountID, ':userName' => $userName, ':IP' => $IP]);
		
		return $db->lastInsertId();
	}
	
	public static function getUserID($accountID) {
		require __DIR__."/connection.php";
		
		$userID = $db->prepare("SELECT userID FROM users WHERE extID = :extID");
		$userID->execute([':extID' => $accountID]);
		$userID = $userID->fetchColumn();
		
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
}
?>