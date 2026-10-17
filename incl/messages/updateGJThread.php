<?php
chdir(dirname(__FILE__));
require "../lib/connection.php";
require_once "../lib/GJPCheck.php";
require_once "../lib/exploitPatch.php";
require_once "../lib/mainLib.php";
$gs = new mainLib();

$accountID = GJPCheck::getAccountIDOrDie();
$mode = ExploitPatch::number($_POST["mode"]) ?: 0;
$threadID = ExploitPatch::number($_POST["threadID"]) ?: 0;
$value = ExploitPatch::rucharclean(ExploitPatch::url_base64_decode($_POST["value"])) ?: "";

if($mode != 3) {
	$thread = $gs->getThreadByID($threadID);
	if(!$thread) exit("-1");
}

switch($mode) {
	//case 
	default:
		exit("-1");
}
?>