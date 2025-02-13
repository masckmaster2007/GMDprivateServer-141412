<?php
class Security {
	public static function GJP2FromPassword($password) {
		return sha1($password."mI29fmAnxgTs");
	}
	
	public static function hashPassword($password) {
		return password_hash($password, PASSWORD_DEFAULT);
	}
	
	public function loginToAccountWithID($accountID, $key, $type) {
		require_once __DIR__."/mainLib.php";
		require_once __DIR__."/enums.php";
		require_once __DIR__."/ip.php";
		
		$IP = IP::getIP();
		
		$account = Library::getAccountByID($accountID);
		if(!$account) return ["success" => false, "error" => LoginError::WrongCredentials, "accountID" => (string)$accountID, "IP" => $IP];
		
		switch($type) {
			case 1:
				if(!password_verify($key, $account["password"])) return ["success" => false, "error" => LoginError::WrongCredentials, "accountID" => (string)$accountID, "IP" => $IP];
				break;
			case 2:
				if(!password_verify($key, $account["gjp2"])) return ["success" => false, "error" => LoginError::WrongCredentials, "accountID" => (string)$accountID, "IP" => $IP];
				break;
			case 3:
				if(empty(trim($key)) || $key != $account["auth"]) return ["success" => false, "error" => LoginError::WrongCredentials, "accountID" => (string)$accountID, "IP" => $IP];
				break;
		}		
		if($account["isActive"] == "0") return ["success" => false, "error" => LoginError::AccountIsNotActivated, "accountID" => (string)$accountID, "IP" => $IP];
		
		$userID = Library::getUserID($accountID);
		
		if(empty($account['salt']) && openssl_random_pseudo_bytes(2) !== false) {
			$salt = Library::randomString(32);
			self::assignSaltToAccount($accountID, $salt);
			if(file_exists(__DIR__.'/../../data/accounts/'.$accountID)) $this->encryptFile(__DIR__.'/../../data/accounts/'.$accountID, $salt);
		}
		
		$userName = $account["userName"];
		
		self::updateLastPlayed($userID);
		
		return ["success" => true, "accountID" => (string)$accountID, "userID" => (string)$userID, "userName" => (string)$userName, "IP" => $IP];
	}
	
	public function loginToAccountWithUserName($userName, $key, $type) {
		require_once __DIR__."/mainLib.php";
		require_once __DIR__."/enums.php";
		
		$accountID = Library::getAccountIDWithUserName($userName);
		if(!$accountID) return ["success" => false, "error" => LoginError::WrongCredentials, "accountID" => "0"];
		
		return $this->loginToAccountWithID($accountID, $key, $type);
	}
	
	public static function assignSaltToAccount($accountID, $salt) {
		require __DIR__."/connection.php";
		
		$assignSalt = $db->prepare("UPDATE accounts SET salt = :salt WHERE accountID = :accountID");
		return $assignSalt->execute([':accountID' => $accountID, ':salt' => $salt]);
	}
	
	public static function getMainCipherMethod() {
		$cipherMethods = openssl_get_cipher_methods();
		switch(true) {
			case in_array("chacha20", $cipherMethods):
				return "chacha20";
			case in_array("aes-128-cbc", $cipherMethods):
				return "aes-128-cbc";
			default:
				return $cipherMethods[0];
		}
	}
	
	public function encryptFile($filePath, $salt) {
		$file = file_get_contents($filePath);
		$fileEncrypted = self::encryptData($file, $salt);
		file_put_contents($filePath, $fileEncrypted);
	}
	
	public static function encryptData($data, $salt) {
		$cipherMethod = self::getMainCipherMethod();
		$fileEncrypted = openssl_encrypt($data, $cipherMethod, $salt);
		return $fileEncrypted;
	}
	
	public function decryptFile($filePath, $salt) {
		$cipherMethod = self::getMainCipherMethod();
		$file = file_get_contents($filePath);
		$fileDecrypted = openssl_decrypt($file, $cipherMethod, $salt);
		return $fileDecrypted;
	}
	
	public static function getLoginType() {
		switch(true) {
			case isset($_POST['gjp2']):
				$key = $_POST['gjp2'];
				$type = 2;
				break;
			case isset($_POST['password']):
				$key = $_POST['password'];
				$type = 1;
				break;
			case isset($_POST['auth']):
				$key = $_POST['auth'];
				$type = 3;
				break;
			default:
				return false;
		}
		return ["key" => $key, "type" => $type];
	}
	
	public function loginPlayer() {
		require_once __DIR__."/mainLib.php";
		require_once __DIR__."/exploitPatch.php";
		require_once __DIR__."/enums.php";
		
		switch(true) {
			case isset($_POST['userName']):
				$userName = Escape::latin($_POST['userName']);
				$accountID = Library::getAccountIDWithUserName($userName);
				break;
			case isset($_POST['uuid']):
				$userID = Escape::number($_POST['uuid']);
				$accountID = Library::getAccountID($userID);
				break;
			default:
				$accountID = Escape::number($_POST['accountID']);
				break;
		}
		
		$loginType = self::getLoginType();
		if(!$loginType) return ["success" => false, "error" => LoginError::GenericError, "accountID" => $accountID];

		$loginToAccount = $this->loginToAccountWithID($accountID, $loginType["key"], $loginType["type"]);
		if(!$loginToAccount['success']) return ["success" => false, "error" => $loginToAccount['error'], "accountID" => $accountID];
		return ["success" => true, "accountID" => $loginToAccount['accountID'], "userID" => $loginToAccount['userID'], "userName" => $loginToAccount["userName"], "IP" => $loginToAccount['IP']];
	}
	
	public static function updateLastPlayed($userID) {
		require_once __DIR__."/connection.php";

		if(!isset($db)) global $db;
		
		$updateLastPlayed = $db->prepare("UPDATE users SET lastPlayed = :lastPlayed WHERE userID = :userID");
		return $updateLastPlayed->execute([':lastPlayed' => time(), ':userID' => $userID]);
	}
	
	public static function generateLevelsHash($levelsStatsArray) {
		$hash = "";
		foreach($levelsStatsArray as $level) {
			$id = strval($level['levelID']);
			$hash = $hash.$id[0].$id[strlen($id)-1].$level["stars"].$level["coins"];
		}
		
		return sha1($hash."xI25fpAapCQg");
	}
	
	public static function generateFirstHash($levelString) {
		$len = strlen($levelString);
		if($len < 41) return sha1($levelString."xI25fpAapCQg");
		
		$hash = '????????????????????????????????????????xI25fpAapCQg';
		$m = intdiv($len, 40);
		$i = 40;
		
		while($i)$hash[--$i] = $levelString[$i*$m];
		
		return sha1($hash);
	}
	
	public static function generateSecondHash($levelString) {
		return sha1($levelString."xI25fpAapCQg");
	}
	
	public static function generateThirdHash($levelString) {
		return sha1($levelString."oC36fpYaPtdg");
	}
	
	public static function generateFourthHash($levelString){
		return sha1($levelString."pC26fpYaQCtg");
	}
}
?>