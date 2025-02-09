<?php
class Cron {
	public static function autoban($accountID, $checkForTime) {
		require __DIR__."/connection.php";
		require_once __DIR__."/mainLib.php";
		require_once __DIR__."/ip.php";
		
		$IP = IP::getIP();
		
		if($checkForTime) {
			$check = $db->prepare("SELECT count(*) FROM actions WHERE type = 39 AND timestamp >= :timestamp");
			$check->execute([':timestamp' => time() - 30]);
			$check = $check->fetchColumn();
			if($check) return false;
		}
		
		$levelStats = $db->prepare("SELECT
			212 + IFNULL(stars.stars, 0) + IFNULL(dailyStars.stars, 0) + IFNULL(eventStars.stars, 0) + IFNULL(gauntletStars.stars, 0) + IFNULL(mappackStars.stars, 0) AS stars,
			10 + IFNULL(coins.coins, 0) + IFNULL(dailyCoins.coins, 0) + IFNULL(eventCoins.coins, 0) + IFNULL(gauntletCoins.coins, 0) AS coins,
			3 + IFNULL(demons.demons, 0) + IFNULL(dailyDemons.demons, 0) + IFNULL(eventDemons.demons, 0) + IFNULL(gauntletDemons.demons, 0) AS demons,
			25 + IFNULL(moons.moons, 0) + IFNULL(dailyMoons.moons, 0) + IFNULL(eventMoons.moons, 0) + IFNULL(gauntletMoons.moons, 0) AS moons 
			FROM (
				-- Levels table
				(SELECT SUM(starStars) AS stars FROM levels WHERE levelLength != 5) stars
				JOIN (SELECT SUM(coins) AS coins FROM levels WHERE starCoins > 0) coins
				JOIN (SELECT SUM(starDemon) AS demons FROM levels WHERE starStars > 0) demons
				JOIN (SELECT SUM(starStars) AS moons FROM levels WHERE levelLength = 5) moons
				
				-- Daily/Weekly levels
				JOIN (SELECT SUM(starStars) as stars FROM dailyfeatures INNER JOIN levels ON levels.levelID = dailyfeatures.levelID WHERE levelLength != 5) dailyStars
				JOIN (SELECT SUM(coins) as coins FROM dailyfeatures INNER JOIN levels ON levels.levelID = dailyfeatures.levelID WHERE starCoins > 0) dailyCoins
				JOIN (SELECT SUM(starDemon) as demons FROM dailyfeatures INNER JOIN levels ON levels.levelID = dailyfeatures.levelID WHERE starStars > 0) dailyDemons
				JOIN (SELECT SUM(starStars) as moons FROM dailyfeatures INNER JOIN levels ON levels.levelID = dailyfeatures.levelID WHERE levelLength = 5) dailyMoons
				
				-- Event levels
				JOIN (SELECT SUM(starStars) as stars FROM events INNER JOIN levels ON levels.levelID = events.levelID WHERE levelLength != 5) eventStars
				JOIN (SELECT SUM(coins) as coins FROM events INNER JOIN levels ON levels.levelID = events.levelID WHERE starCoins > 0) eventCoins
				JOIN (SELECT SUM(starDemon) as demons FROM events INNER JOIN levels ON levels.levelID = events.levelID WHERE starStars > 0) eventDemons
				JOIN (SELECT SUM(starStars) as moons FROM events INNER JOIN levels ON levels.levelID = events.levelID WHERE levelLength = 5) eventMoons
				
				-- Map Packs
				JOIN (SELECT SUM(stars) as stars FROM mappacks) mappackStars
				
				-- Gauntlets
				JOIN (SELECT SUM(starStars) as stars FROM levels INNER JOIN gauntlets ON levels.levelID IN (gauntlets.level1, gauntlets.level2, gauntlets.level3, gauntlets.level4, gauntlets.level5) WHERE levelLength != 5) gauntletStars
				JOIN (SELECT SUM(coins) as coins FROM levels INNER JOIN gauntlets ON levels.levelID IN (gauntlets.level1, gauntlets.level2, gauntlets.level3, gauntlets.level4, gauntlets.level5) WHERE starCoins > 0) gauntletCoins
				JOIN (SELECT SUM(starDemon) as demons FROM levels INNER JOIN gauntlets ON levels.levelID IN (gauntlets.level1, gauntlets.level2, gauntlets.level3, gauntlets.level4, gauntlets.level5) WHERE starStars > 0) gauntletDemons
				JOIN (SELECT SUM(starStars) as moons FROM levels INNER JOIN gauntlets ON levels.levelID IN (gauntlets.level1, gauntlets.level2, gauntlets.level3, gauntlets.level4, gauntlets.level5) WHERE levelLength = 5) gauntletMoons
			)");
		$levelStats->execute();
		$levelStats = $levelStats->fetch();
		
		$stars = $levelStats['stars'];
		$coins = $levelStats['coins'];
		$demons = $levelStats['demons'];
		$moons = $levelStats['moons']; 
		
		$getCheaters = $db->prepare("SELECT userID FROM users WHERE stars > :stars OR demons > :demons OR userCoins > :coins OR moons > :moons OR stars < 0 OR demons < 0 OR coins < 0 OR userCoins < 0 OR diamonds < 0 OR moons < 0");
		$getCheaters->execute([':stars' => $stars, ':demons' => $demons, ':coins' => $coins, ':moons' => $moons]);
		$getCheaters = $getCheaters->fetchAll();
		
		foreach($getCheaters AS &$ban) {
			$getUser = Library::getUserByID();
			$maxText = 'MAX: ⭐'.$stars.' • 🌙'.$moons.' • 👿'.$demons.' • 🪙'.$coins.' | USER: ⭐'.$getUser['stars'].' • 🌙'.$getUser['moons'].' • 👿'.$getUser['demons'].' • 🪙'.$getUser['userCoins'];
			
			Library::banPerson(0, $ban['userID'], $maxText, 0, 1, 2147483647);
		}
		
		Library::logAction($accountID, $IP, 39, $stars, $coins, $demons, $moons, count($getCheaters));
		return true;
	}
	public static function updateCreatorPoints($accountID, $checkForTime) {
		require __DIR__."/connection.php";
		require_once __DIR__."/mainLib.php";
		require_once __DIR__."/ip.php";
		
		$IP = IP::getIP();
		
		if($checkForTime) {
			$check = $db->prepare("SELECT count(*) FROM actions WHERE type = 40 AND timestamp >= :timestamp");
			$check->execute([':timestamp' => time() - 30]);
			$check = $check->fetchColumn();
			if($check) return false;
		}
		
		$people = [];
		
		/*
			Creator Points for rated levels
		*/
		
		$rateCreatorPoints = $db->prepare("UPDATE users
			LEFT JOIN
			(
				SELECT usersTable.userID, (IFNULL(starredTable.starred, 0) + IFNULL(featuredTable.featured, 0) + (IFNULL(epicTable.epic,0))) as CP FROM (
					SELECT userID FROM users
				) AS usersTable
				LEFT JOIN
				(
					SELECT count(*) as starred, userID FROM levels WHERE starStars != 0 AND isCPShared = 0 GROUP BY(userID) 
				) AS starredTable ON usersTable.userID = starredTable.userID
				LEFT JOIN
				(
					SELECT count(*) as featured, userID FROM levels WHERE starFeatured != 0 AND isCPShared = 0 GROUP BY(userID) 
				) AS featuredTable ON usersTable.userID = featuredTable.userID
				LEFT JOIN
				(
					SELECT starEpic as epic, userID FROM levels WHERE starEpic != 0 AND isCPShared = 0 GROUP BY(userID) 
				) AS epicTable ON usersTable.userID = epicTable.userID
			) calculated
			ON users.userID = calculated.userID
			SET users.creatorPoints = IFNULL(calculated.CP, 0)");
		$rateCreatorPoints->execute();
		
		/*
			Creator Points sharing
		*/
		
		$shareCreatorPoints = $db->prepare("SELECT levelID, userID, starStars, starFeatured, starEpic FROM levels WHERE isCPShared != 0");
		$shareCreatorPoints->execute();
		$shareCreatorPoints = $shareCreatorPoints->fetchAll();
		
		foreach($shareCreatorPoints AS &$level) {
			$deservedcp = 0;
			
			if($level["starStars"]) $deservedcp++;
			if($level["starFeatured"]) $deservedcp++;
			if($level["starEpic"]) $deservedcp += $level["starEpic"];
			
			$shares = $db->prepare("SELECT userID FROM cpshares WHERE levelID = :levelID");
			$shares->execute([':levelID' => $level["levelID"]]);
			$shares = $shares->fetchAll();
			$shareCount = $shares->rowCount() + 1;
			
			$addCreatorPoints = $deservedcp / $shareCount;
			
			foreach($shares as &$share) $people[$share["userID"]] += $addCreatorPoints;
			$people[$level["userID"]] += $addCreatorPoints;
		}
		
		/*
			Creator Points for levels in Map Packs
		*/
		
		$mapPacksCreatorPoints = $db->prepare("SELECT levels FROM mappacks");
		$mapPacksCreatorPoints->execute();
		$mapPacksCreatorPoints = $mapPacksCreatorPoints->fetchAll();
		
		foreach($mapPacksCreatorPoints AS &$pack) {
			$levels = $db->prepare("SELECT userID FROM levels WHERE levelID IN (".$pack['levels'].")");
			$levels->execute();
			$levels = $levels->fetch();
			
			foreach($levels AS &$level) $people[$level["userID"]] += 1;
		}
		
		/*
			Creator Points for levels in Gauntlets
		*/
		
		$gauntletsCreatorPoints = $db->prepare("SELECT level1, level2, level3, level4, level5 FROM gauntlets");
		$gauntletsCreatorPoints->execute();
		$gauntletsCreatorPoints = $gauntletsCreatorPoints->fetchAll();
		
		foreach($gauntletsCreatorPoints AS &$gauntlet) {
			for($x = 1; $x < 6; $x++) {
				$gauntletCreatorPoints = $db->prepare("SELECT userID FROM levels WHERE levelID = :levelID");
				$gauntletCreatorPoints->execute([':levelID' => $gauntlet["level".$x]]);
				$gauntletCreatorPoints = $gauntletCreatorPoints->fetch();
				
				if($gauntletCreatorPoints) $people[$result["userID"]] += 1;
			}
		}
		
		/*
			Creator Points for Daily/Weekly levels
		*/
		
		$dailyCreatorPoints = $db->prepare("SELECT levelID FROM dailyfeatures WHERE timestamp < :time");
		$dailyCreatorPoints->execute([':time' => time()]);
		$dailyCreatorPoints = $dailyCreatorPoints->fetchAll();
		
		foreach($dailyCreatorPoints AS &$daily) {
			$dailyCreatorPoint = $db->prepare("SELECT userID, levelID FROM levels WHERE levelID = :levelID");
			$dailyCreatorPoint->execute([':levelID' => $daily["levelID"]]);
			$dailyCreatorPoint = $dailyCreatorPoint->fetch();
			
			if($dailyCreatorPoint) $people[$dailyCreatorPoint["userID"]] += 1;
		}
		
		/*
			Creator Points for Event levels
		*/
		
		$eventsCreatorPoints = $db->prepare("SELECT levelID FROM events WHERE timestamp < :time");
		$eventsCreatorPoints->execute([':time' => time()]);
		$eventsCreatorPoints = $eventsCreatorPoints->fetchAll();
		
		foreach($eventsCreatorPoints AS &$event) {
			$eventCreatorPoints = $db->prepare("SELECT userID, levelID FROM levels WHERE levelID = :levelID");
			$eventCreatorPoints->execute([':levelID' => $event["levelID"]]);
			$eventCreatorPoints = $eventCreatorPoints->fetch();
			
			if($eventCreatorPoints) $people[$eventCreatorPoints["userID"]] += 1;
		}
		
		/*
			Done
		*/
		
		foreach($people AS $user => $cp) {
			$updateCreatorPoints = $db->prepare("UPDATE users SET creatorPoints = (creatorpoints + :creatorpoints) WHERE userID = :userID");
			$updateCreatorPoints->execute([':userID' => $user, ':creatorpoints' => $cp]);
		}
		
		Library::logAction($accountID, $IP, 40);
		return true;
	}
	public static function fixUsernames($accountID, $checkForTime) {
		require __DIR__."/connection.php";
		require_once __DIR__."/mainLib.php";
		require_once __DIR__."/ip.php";
		
		$IP = IP::getIP();
		
		if($checkForTime) {
			$check = $db->prepare("SELECT count(*) FROM actions WHERE type = 41 AND timestamp >= :timestamp");
			$check->execute([':timestamp' => time() - 30]);
			$check = $check->fetchColumn();
			if($check) return false;
		}
		
		$fixUsernames = $db->prepare("UPDATE users
			INNER JOIN accounts ON accounts.accountID = users.extID
			SET users.userName = accounts.userName
			WHERE users.extID REGEXP '^-?[0-9]+$'
			AND LENGTH(accounts.userName) <= 69");
		$fixUsernames->execute();
		
		Library::logAction($accountID, $IP, 41);
		return true;
	}
	public static function updateFriendsCount($accountID, $checkForTime) {
		require __DIR__."/connection.php";
		require_once __DIR__."/mainLib.php";
		require_once __DIR__."/ip.php";
		
		$IP = IP::getIP();
		
		if($checkForTime) {
			$check = $db->prepare("SELECT count(*) FROM actions WHERE type = 42 AND timestamp >= :timestamp");
			$check->execute([':timestamp' => time() - 30]);
			$check = $check->fetchColumn();
			if($check) return false;
		}
		
		$updateFriendsCount = $db->prepare("UPDATE accounts
			LEFT JOIN
			(
				SELECT a.person, (IFNULL(a.friends, 0) + IFNULL(b.friends, 0)) AS friends FROM (
					SELECT count(*) as friends, person1 AS person FROM friendships GROUP BY(person1) 
				) AS a
				JOIN
				(
					SELECT count(*) as friends, person2 AS person FROM friendships GROUP BY(person2) 
				) AS b ON a.person = b.person
			) calculated
			ON accounts.accountID = calculated.person
			SET accounts.friendsCount = IFNULL(calculated.friends, 0)");
		$updateFriendsCount->execute();
		
		Library::logAction($accountID, $IP, 42);
		return true;
	}
	public static function miscFixes($accountID, $checkForTime) {
		require __DIR__."/connection.php";
		require_once __DIR__."/mainLib.php";
		require_once __DIR__."/ip.php";
		
		$IP = IP::getIP();
		
		if($checkForTime) {
			$check = $db->prepare("SELECT count(*) FROM actions WHERE type = 43 AND timestamp >= :timestamp");
			$check->execute([':timestamp' => time() - 30]);
			$check = $check->fetchColumn();
			if($check) return false;
		}
		
		/*
			Unbanning everyone who has expired ban
		*/
		
		$bans = $db->prepare('UPDATE bans SET isActive = 0 WHERE expires < :time');
		$bans->execute([':time' => time()]);
		
		/*
			Unbanning IPs
		*/
		
		$getIPBans = $db->prepare("SELECT person FROM bans WHERE personType = 2 AND banType = 4 AND isActive = 0");
		$getIPBans->execute();
		$getIPBans = $getIPBans->fetchAll();
		$IPBans = [];
		
		foreach($getIPBans AS &$ban) $IPBans[] = Library::IPForBan($ban['person'], true);
		
		$bannedIPsString = implode("|", $IPBans);
		$unbanIPs = $db->prepare('DELETE FROM bannedips WHERE IP REGEXP "'.$bannedIPsString.'"');
		$unbanIPs->execute();
		
		Library::logAction($accountID, $IP, 43);
		return true;
	}
	public static function updateSongsUsage($accountID, $checkForTime) {
		require __DIR__."/connection.php";
		require_once __DIR__."/mainLib.php";
		require_once __DIR__."/ip.php";
		
		$IP = IP::getIP();
		
		if($checkForTime) {
			$check = $db->prepare("SELECT count(*) FROM actions WHERE type = 44 AND timestamp >= :timestamp");
			$check->execute([':timestamp' => time() - 30]);
			$check = $check->fetchColumn();
			if($check) return false;
		}
		
		$levels = $db->prepare("SELECT songID, songIDs, sfxIDs FROM levels");
		$levels->execute();
		$levels = $levels->fetchAll();
		
		$songsUsage = $sfxsUsage = [];
		
		/*
			Count songs and SFXs usage
		*/
		
		$songsLibrary = json_decode(file_get_contents(__DIR__.'/../../music/ids.json'), true) ?: [];
		$sfxsLibrary = json_decode(file_get_contents(__DIR__.'/../../sfx/ids.json'), true) ?: [];
		
		foreach($levels AS &$level) {
			$mainSong = Library::getSongByID($level['songID'], "*", $songsLibrary);
			if($mainSong && $mainSong['isLocalSong']) $songsUsage[$mainSong['ID']]++;
			
			$extraSongs = explode(',', $level['songIDs']);
			foreach($extraSongs AS &$song) {
				if(empty($song)) continue;
				$extraSong = Library::getSongByID($song, "*", $songsLibrary);
				if($extraSong && $extraSong['isLocalSong']) $songsUsage[$extraSong['ID']]++;
			}
			
			$extraSFXs = explode(',', $level['sfxIDs']);
			foreach($extraSFXs AS &$sfx) {
				if(empty($sfx)) continue;
				$extraSFX = Library::getLibrarySongInfo($sfx, 'sfx', $sfxsLibrary);
				if($extraSFX && $extraSFX['isLocalSFX']) $sfxsUsage[$extraSFX['originalID']]++;
			}
		}
		
		/*
			Add this info to SQL
		*/
		
		$db->query("UPDATE songs SET levelsCount = 0");
		$db->query("UPDATE sfxs SET levelsCount = 0");
		
		foreach($songsUsage AS $song => $usage) {
			$addInfo = $db->prepare("UPDATE songs SET levelsCount = :usage WHERE ID = :songID");
			$addInfo->execute([':usage' => $usage, ':songID' => $song]);
		}
		
		foreach($sfxsUsage AS $sfx => $usage) {
			$addInfo = $db->prepare("UPDATE sfxs SET levelsCount = :usage WHERE ID = :sfxID");
			$addInfo->execute([':usage' => $usage, ':sfxID' => $sfx]);
		}
		
		Library::logAction($accountID, $IP, 44, count($songsUsage), count($sfxsUsage));
		return true;
	}
	public static function doEverything($accountID, $checkForTime) {
		if(
			!self::autoban($accountID, $checkForTime) ||
			!self::updateCreatorPoints($accountID, $checkForTime) ||
			!self::fixUsernames($accountID, $checkForTime) ||
			!self::updateFriendsCount($accountID, $checkForTime) ||
			!self::miscFixes($accountID, $checkForTime) ||
			!self::updateSongsUsage($accountID, $checkForTime)
		) return false;
		return true;
	}
}
?>
