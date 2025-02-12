<?php
require_once __DIR__."/../lib/mainLib.php";
require_once __DIR__."/../lib/exploitPatch.php";
require_once __DIR__."/../lib/security.php";
require_once __DIR__."/../lib/XOR.php";
require_once __DIR__."/../lib/enums.php";
$sec = new Security();

$player = $sec->loginPlayer();
if(!$player["success"]) exit(CommonError::InvalidRequest);
$accountID = $player["accountID"];
$userID = $player["userID"];
$userName = $player["userName"];

$rewardKey = Escape::latin($_POST["rewardKey"]);
$chk = XORCipher::cipher(Escape::url_base64_decode(substr(Escape::latin($_POST["chk"]), 5)), 59182);

$vaultCode = Library::getVaultCode($rewardKey);
if(!$vaultCode || $vaultCode['uses'] == 0 || ($vaultCode['duration'] != 0 && $vaultCode['duration'] <= time())) exit(CommonError::InvalidRequest);

$checkVaultCode = Library::isVaultCodeUsed($accountID, $vaultCode['rewardID']);
if($checkVaultCode) exit(CommonError::InvalidRequest);

Library::useVaultCode($accountID, $vaultCode, $rewardKey);

$string = Escape::url_base64_encode(XORCipher::cipher('Sa1nt:'.$chk.':'.$vaultCode['rewardID'].':1:'.$vaultCode['rewards'], 59182));
$hash = Security::generateFourthHash($string);
exit('Sa1nt'.$string.'|'.$hash);
?>