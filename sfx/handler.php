<?php
require __DIR__."/../config/dashboard.php";
require __DIR__."/../config/proxy.php";
require_once __DIR__."/../incl/lib/connection.php";
require_once __DIR__."/../incl/lib/mainLib.php";
$file = trim($_GET['request']);
switch($file) {
	case 'sfxlibrary.dat':
		$datFile = isset($_GET['dashboard']) ? 'standalone.dat' : 'gdps.dat';
		if(!file_exists($datFile)) {
			$time = Library::lastSFXTime();
			Library::updateLibraries($_GET['token'], $_GET['expires'], $time, 0);
		}
		
		exit(file_get_contents($datFile));
	case 'sfxlibrary_version.txt': 
		$time = Library::lastSFXTime();
		
		Library::updateLibraries($_GET['token'], $_GET['expires'], $time, 0);
		
		$times = [];
		foreach($customLibrary AS $library) if($library[2] !== null) $times[] = explode(', ', file_get_contents(__DIR__.'s'.$library[0].'.txt'))[1];
		$times[] = $time;
		rsort($times);
		
		exit((string)$times[0]);
	default:
		$servers = [];
		foreach($customLibrary AS $library) {
			$servers[$library[0]] = $library[2];
		}
		
		$sfxID = explode('.', substr($file, 1, strlen($file)))[0];
		
		if(!file_exists('ids.json')) {
			$time = Library::lastSFXTime();
			Library::updateLibraries($_GET['token'], $_GET['expires'], $time, 0);
		}
		
		$song = Library::getLibrarySongInfo($sfxID, 'sfx');
		
		$url = urldecode($song['download']);
		header("Location: ".$url);
}
?>