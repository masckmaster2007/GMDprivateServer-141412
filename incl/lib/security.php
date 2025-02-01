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
		
		$account = Library::getAccountByID($accountID);
		if(!$account) return ["success" => false, "error" => LoginError::WrongCredentials];
		
		switch($type) {
			case 1:
				if(!password_verify($key, $account["password"])) return ["success" => false, "error" => LoginError::WrongCredentials];
				break;
			case 2:
				if(!password_verify($key, $account["gjp2"])) return ["success" => false, "error" => LoginError::WrongCredentials];
				break;
			case 3:
				if(empty(trim($key)) || $key != $account["auth"]) return ["success" => false, "error" => LoginError::WrongCredentials];
				break;
		}		
		if($account["isActive"] == "0") return ["success" => false, "error" => LoginError::AccountIsNotActivated];
		
		$userID = Library::getUserID($accountID);
		
		if(empty($account['salt']) && openssl_random_pseudo_bytes(2) !== false) {
			$salt = Library::randomString(32);
			self::assignSaltToAccount($accountID, $salt);
			if(file_exists(__DIR__.'/../../data/accounts/'.$accountID)) $this->encryptFile(__DIR__.'/../../data/accounts/'.$accountID, $salt);
		}
		
		return ["success" => true, "accountID" => $accountID, "userID" => $userID];
	}
	
	public function loginToAccountWithUserName($userName, $key, $type) {
		require_once __DIR__."/mainLib.php";
		require_once __DIR__."/enums.php";
		
		$accountID = Library::getAccountIDWithUserName($userName);
		if(!$accountID) return ["success" => false, "error" => LoginError::WrongCredentials];
		
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
		$fileEncrypted = self::encryptData($filePath, $file, $salt);
	}
	
	public static function encryptData($filePath, $data, $salt) {
		$cipherMethod = self::getMainCipherMethod();
		$fileEncrypted = openssl_encrypt($data, $cipherMethod, $salt);
		file_put_contents($filePath, $fileEncrypted);
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
			case isset($_POST['password']):
				$key = $_POST['password'];
				$type = 1;
				break;
			case isset($_POST['gjp2']):
				$key = $_POST['gjp2'];
				$type = 2;
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
}
?>