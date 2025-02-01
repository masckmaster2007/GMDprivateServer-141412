<?php
@header('Content-Type: text/html; charset=utf-8');
require __DIR__."/../../config/connection.php";
require __DIR__."/../../config/security.php";
require __DIR__."/../../config/dashboard.php";

if(function_exists('ini_set')) {
	ini_set('display_errors', ($debugMode ? 'On' : 'Off'));
}
error_reporting(E_ALL & ~E_WARNING & ~E_DEPRECATED);

if(!isset($db)) global $db;
if(empty($db)) {
	try {
		$db = new PDO("mysql:host=".$servername.";port=".$port.";dbname=".$dbname, $username, $password, array(PDO::ATTR_PERSISTENT => true));
		$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		if(!$installed) require __DIR__."/migrate.php";
	} catch (PDOException $e) {
		echo "<h1>Connection to MySQL failed!</h1>";
		echo "Error: ".$e->getMessage();
	}
}
?>