<?php
require __DIR__."/../config/dashboard.php";
require __DIR__."/../config/proxy.php";
require_once __DIR__."/../incl/lib/connection.php";
require_once __DIR__."/../incl/lib/mainLib.php";
$file = trim($_GET['request']);
switch($file) {
	case 'musiclibrary.dat': 
	case 'musiclibrary_02.dat': 
		$datFile = isset($_GET['dashboard']) ? 'standalone.dat' : 'gdps.dat';
		if(!file_exists($datFile)) {
			$time = Library::lastSongTime();
			Library::updateLibraries($_GET['token'], $_GET['expires'], $time, 1);
		}
		
		exit(file_get_contents($datFile));
	case 'musiclibrary_version.txt': 
	case 'musiclibrary_version_02.txt': 
		$time = Library::lastSongTime();
		
		Library::updateLibraries($_GET['token'], $_GET['expires'], $time, 1);
		
		$times = [];
		foreach($customLibrary AS $library) if($library[2] !== null) $times[] = explode(', ', file_get_contents(__DIR__.'/s'.$library[0].'.txt'))[1];
		$times[] = $time;
		rsort($times);
		
		exit((string)$times[0]);
	default:
		$servers = [];
		foreach($customLibrary AS $library) {
			$servers[$library[0]] = $library[2];
		}
		
		$musicID = explode('.', $file)[0];
		
		if(!file_exists('ids.json')) {
			$time = Library::lastSongTime();
			Library::updateLibraries($_GET['token'], $_GET['expires'], $time, 1);
		}
		
		$song = Library::getLibrarySongInfo($musicID, true);
		
		$url = $song ? urldecode($song['download']) : urldecode(Library::getSongByID($musicID, 'download'));
		
		if(empty($url)) $url = "https://www.newgrounds.com/audio/listen/".$musicID;
		header("Location: ".$url);
}
?>
