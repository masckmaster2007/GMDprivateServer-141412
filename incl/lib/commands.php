<?php
class Commands {
	public static function processLevelCommand($comment, $level, $person) {
		require_once __DIR__.'/mainLib.php';
		require_once __DIR__.'/exploitPatch.php';
		
		if(substr($comment, 0, 1) != '!') return false;
		
		$levelID = $level['levelID'];
		
		$commentSplit = explode(' ', $comment);
		$increaseSplit = 0;
		$command = $commentSplit[0];
		
		switch($command) {
			case '!rate':
			case '!r':
				if(!Library::checkPermission($person, 'commandRate')) return "You don't have permissions to use command ".$command."!";

				$difficulty = Escape::latin($commentSplit[1]);
				if(!is_numeric($commentSplit[2])) {
					$increaseSplit++;
					$difficulty .= " ".Escape::latin($commentSplit[1 + $increaseSplit]);
				}
				$stars = Escape::number($commentSplit[2 + $increaseSplit]);
				$verifyCoins = Escape::number($commentSplit[3 + $increaseSplit]);
				$featured = Escape::number($commentSplit[4 + $increaseSplit]);
				
				if(!$difficulty || !is_numeric($stars) || !is_numeric($verifyCoins) || !is_numeric($featured)) {
					return "Incorrect usage!".PHP_EOL
						."!rate *difficulty* *stars* *are coins verified* *featured/epic/legendary/mythic*".PHP_EOL
						."Example: !rate harder 7 1 4";
				}

				if(!$stars) return "To unrate level please use !unrate.";
				
				$rateLevel = Library::rateLevel($levelID, $person, $difficulty, $stars, $verifyCoins, $featured);
				
				return "You successfully rated ".$level['levelName'].' as '.$rateLevel.', '.$stars .' star'.($stars > 1 ? 's!' : '!');
			case '!unrate':
			case '!unr':
				if(!Library::checkPermission($person, 'commandRate')) return "You don't have permissions to use command ".$command."!";
				
				Library::rateLevel($levelID, $person, Library::prepareDifficultyForRating(($level['starDifficulty'] / $level['difficultyDenominator']), $level['starAuto'], $level['starDemon'], $level['starDemonDiff']), 0, 0, 0);
				
				return "You successfully unrated ".$level['levelName'].'!';
			case '!feature':
			case '!epic':
			case '!legendary':
			case '!mythic':
			case '!unfeature':
			case '!unepic':
			case '!unlegendary':
			case '!unmythic':
			case '!fea':
			case '!f':
			case '!epi':
			case '!leg':
			case '!myt':
			case '!unfea':
			case '!unepi':
			case '!unleg':
			case '!unmyt':
			
				$commandArray = [
					'!feature' => 1, '!fea' => 1, '!f' => 1,
					'!epic' => 2, '!epi' => 2,
					'!legendary' => 3, '!leg' => 3,
					'!mythic' => 4, '!myt' => 4,
					'!unfeature' => 0, '!unfea' => 0,
					'!unepic' => 0, '!unepi' => 0,
					'!unlegendary' => 0, '!unleg' => 0,
					'!unmythic' => 0, '!unmyt' => 0
				];
				$returnTextArray = ['unfeatured level %1$s!', 'featured level %1$s!', 'set level %1$s as epic!', 'set level %1$s as legendary!', 'set level %1$s as mythic!'];
				$featured = $commandArray[$command];
				
				$featurePermission = $featured < 2 && $level['starEpic'] == 0 ? 'Feature' : 'Epic';
				if(!Library::checkPermission($person, 'command'.$featurePermission)) return "You don't have permissions to use command ".$command."!";
				
				Library::rateLevel($levelID, $person, Library::prepareDifficultyForRating(($level['starDifficulty'] / $level['difficultyDenominator']), $level['starAuto'], $level['starDemon'], $level['starDemonDiff']), $level['starStars'], $level['starCoins'], $featured);
				
				return "You successfully ".sprintf($returnTextArray[$featured], $level['levelName']);
			case '!verifycoins':
			case '!unverifycoins':
			case '!vc':
			case '!unvc':
				if(!Library::checkPermission($person, 'commandVerifycoins')) return "You don't have permissions to use command ".$command."!";
			
				$commandArray = [
					'!verifycoins' => 1, '!vc' => 1,
					'!unverifycoins' => 0, '!unvc' => 0
				];
				
				$returnTextArray = ['unverified coins in level %1$s!', 'verified coins in level %1$s!'];
				$verifyCoins = $commandArray[$command];
				
				$featured = $level['starEpic'] + ($level['starFeatured'] ? 1 : 0);
				
				Library::rateLevel($levelID, $person, Library::prepareDifficultyForRating(($level['starDifficulty'] / $level['difficultyDenominator']), $level['starAuto'], $level['starDemon'], $level['starDemonDiff']), $level['starStars'], $verifyCoins, $featured);
				
				return "You successfully ".sprintf($returnTextArray[$verifyCoins], $level['levelName']);
			case '!daily':
			case '!weekly':
			case '!da':
			case '!w':
				$typeArray = [
					'!daily' => 0, '!da' => 0,
					'!weekly' => 1, '!w' => 1
				];
				$type = $typeArray[$command];
				
				$dailyPermission = $type ? 'Weekly' : 'Daily';
				if(!Library::checkPermission($person, 'command'.$dailyPermission)) return "You don't have permissions to use command ".$command."!";
				
				$setDaily = Library::setLevelAsDaily($levelID, $person, $type);
				if(!$setDaily) return "Level ".$level['levelName']." is already ".($type ? 'weekly' : 'daily')."!";
				
				return "You successfully set level ".$level['levelName']." as ".($type ? 'weekly' : 'daily')."!".PHP_EOL
					."It will appear ".Library::makeTime($setDaily).'.';
			case '!undaily':
			case '!unda':
			case '!unweekly':
			case '!unw':
				$typeArray = [
					'!undaily' => 0, '!unda' => 0,
					'!unweekly' => 1, '!unw' => 1
				];
				$type = $typeArray[$command];
				
				$dailyPermission = $type ? 'Weekly' : 'Daily';
				if(!Library::checkPermission($person, 'command'.$dailyPermission)) return "You don't have permissions to use command ".$command."!";
				
				$removeDaily = Library::removeDailyLevel($levelID, $person, $type);
				if(!$removeDaily) return "Level ".$level['levelName']." is not ".($type ? 'weekly' : 'daily')." level!";
				
				return "You successfully removed level ".$level['levelName']." from ".($type ? 'weekly' : 'daily')." levels!";
			case '!event':
			case '!ev':
				if(!Library::checkPermission($person, 'commandEvent')) return "You don't have permissions to use command ".$command."!";
			
				if(!is_numeric($commentSplit[1])) {
					return "Incorrect usage!".PHP_EOL
						."!event *duration in minutes* *reward type* *reward amount*".PHP_EOL
						."Example: !event 60 7 1000 8 20 1001 379";
				}
				$duration = Escape::number($commentSplit[1]) * 60;
				unset($commentSplit[0], $commentSplit[1]);
				$rewards = implode(",", $commentSplit);
				
				if(!$duration || $duration < 0 || !$rewards || $rewards != Escape::multiple_ids($rewards)) {
					return "Incorrect usage!".PHP_EOL
						."!event *duration in minutes* *reward type* *reward amount*".PHP_EOL
						."Example: !event 60 7 1000 8 20 1001 379";
				}
				
				$setEvent = Library::setLevelAsEvent($levelID, $person, $duration, $rewards);
				if(!$setEvent) return "Level ".$level['levelName']." is already event level!";
				
				return "You successfully set level ".$level['levelName']." as event level!".PHP_EOL
					."It will appear ".Library::makeTime($setEvent).'.';
			case "!unevent":
			case "!unev":
				if(!Library::checkPermission($person, 'commandEvent')) return "You don't have permissions to use command ".$command."!";

				$removeEvent = Library::removeEventLevel($levelID, $person);
				if(!$removeEvent) return "Level ".$level['levelName']." is not event level!";
				
				return "You successfully removed level ".$level['levelName']." from event levels!";
			case '!send':
			case '!suggest':
				if(!Library::checkPermission($person, 'commandSuggest')) return "You don't have permissions to use command ".$command."!";
			
				$difficulty = Escape::latin($commentSplit[1]);
				if(!is_numeric($commentSplit[2])) {
					$increaseSplit++;
					$difficulty .= " ".Escape::latin($commentSplit[1 + $increaseSplit]);
				}
				$stars = Escape::number($commentSplit[2 + $increaseSplit]);
				$featured = Escape::number($commentSplit[3 + $increaseSplit]);
				
				if(!$difficulty || !$stars || !is_numeric($featured)) {
					return "Incorrect usage!".PHP_EOL
						."!send *difficulty* *stars* *featured/epic/legendary/mythic*".PHP_EOL
						."Example: !send harder 7 4";
				}
				
				$sendLevel = Library::sendLevel($levelID, $person, $difficulty, $stars, $featured);
				if(!$sendLevel) return "You already suggested level ".$level['levelName']."!";
				
				return "You successfully sent ".$level['levelName'].' as '.$sendLevel.', '.$stars .' star'.($stars > 1 ? 's!' : '!');
			case '!setacc':
			case '!account':
			case '!move':
			case '!sa':
			case '!acc':
			case '!m':
				if(!Library::checkPermission($person, 'commandSetacc')) return "You don't have permissions to use command ".$command."!";
			
				$player = Library::getUserFromSearch(Escape::latin($commentSplit[1]));
				if(!$player) return "This user was not found!";
				
				if($player['extID'] == $level['extID']) return "User ".$player['userName']." already owns level ".$level['levelName']."!";
				
				Library::moveLevel($levelID, $person, $player);
				
				return "You successfully moved level ".$level['levelName']." to user ".$player['userName']."!";
			case '!lockUpdating':
			case '!unlockUpdating':
			case '!lu':
			case '!unlu':
				if(!Library::checkPermission($person, 'commandLockUpdating')) return "You don't have permissions to use command ".$command."!";
			
				$lockUpdatingArray = [
					'!lockUpdating' => 1, '!lu' => 1,
					'!unlockUpdating' => 0, '!unlu' => 0
				];
				$lockUpdating = $lockUpdatingArray[$command];
				if($level['updateLocked'] == $lockUpdating) return "Level ".$level['levelName']." is already ".(!$lockUpdating ? 'un' : '')."locked!";
				
				Library::lockUpdatingLevel($levelID, $person, $lockUpdating);
				
				return "You successfully ".(!$lockUpdating ? 'un' : '')."locked level ".$level['levelName']."!";
			case "!rename":
			case "!re":
				if(!Library::checkPermission($person, 'commandRename')) return "You don't have permissions to use command ".$command."!";
			
				unset($commentSplit[0]);
				$newLevelName = trim(Escape::latin(implode(' ', $commentSplit)));
				if(!$newLevelName) {
					return "Incorrect usage!".PHP_EOL
						."!rename *level name*".PHP_EOL
						."Example: !rename My cool level";
				}
				
				if($level['levelName'] == $newLevelName) return "Level ".$level['levelName']." already has this name!";
				
				Library::renameLevel($levelID, $person, $newLevelName);
				
				return "You successfully renamed level ".$level['levelName']." to ".$newLevelName."!";
			case "!password":
			case "!pass":
			case "!p":
				if(!Library::checkPermission($person, 'commandPass')) return "You don't have permissions to use command ".$command."!";
				
				if(!$commentSplit[1] || !is_numeric($commentSplit[1]) || strlen($commentSplit[1]) > 6) {
					return "Incorrect usage!".PHP_EOL
						."!password *level password*".PHP_EOL
						."Example: !password 141412";
				}
				
				$newPassword = sprintf("%06d", Escape::number($commentSplit[1]));
				
				if($level['password'] == '1'.$newPassword || $level['password'].'000000' == '1'.$newPassword) return "Level ".$level['levelName']." already has this password!";
				
				Library::changeLevelPassword($levelID, $person, $newPassword);
				
				return "You successfully changed password of level ".$level['levelName'].' to '.$newPassword."!";
			case "!song":
			case "!s":
				if(!Library::checkPermission($person, 'commandSong')) return "You don't have permissions to use command ".$command."!";
			
				$songID = Escape::number($commentSplit[1]);
				if(!$songID) {
					return "Incorrect usage!".PHP_EOL
						."!song *song ID*".PHP_EOL
						."Example: !song 1967605";
				}
				
				if($level["songID"] == $songID) return "Level ".$level['levelName']." already has this song!";
				
				$song = Library::getSongByID($songID);
				if(!$song) return "This song doesn't exist!";
				
				Library::changeLevelSong($levelID, $person, $songID);
				
				return "You successfully changed song of level ".$level['levelName']." to ".Escape::translit($song['authorName'])." - ".Escape::translit($song['name'])."!";
			case "!description":
			case "!desc":
				if(!Library::checkPermission($person, 'commandDescription')) return "You don't have permissions to use command ".$command."!";
			
				unset($commentSplit[0]);
				$newLevelDesc = Library::escapeDescriptionCrash(trim(Escape::text(implode(' ', $commentSplit))));
				if(!$newLevelDesc) {
					return "Incorrect usage!".PHP_EOL
						."!description *level description*".PHP_EOL
						."Example: !description This is my cool level i made in 3 hours. Please enjoy!";
				}
				
				if($level['levelDesc'] == $newLevelDesc) return "Level ".$level['levelName']." already has this description!";
				
				Library::changeLevelDescription($levelID, $person, $newLevelDesc);
				
				return "You successfully changed description of level ".$level['levelName']." to:".PHP_EOL
					.$newLevelDesc;
			case "!public":
			case "!unlist":
			case "!friends":
			case "!pub":
			case "!unl":
			case "!fr":
				if(!Library::checkPermission($person, 'commandPublic')) return "You don't have permissions to use command ".$command."!";
			
				$privacyArray = [
					'!public' => 0, '!pub' => 0,
					'!friends' => 1, '!fr' => 1,
					'!unlist' => 2, '!unl' => 2,
				];
				$privacyText = ['public', 'only for friends', 'unlisted'];
				$privacy = $privacyArray[$command];
				
				if($level['unlisted'] == $privacy) return "Level ".$level['levelName']." already is ".$privacyText[$privacy]."!";
				
				Library::changeLevelPrivacy($levelID, $person, $privacy);
				
				return "You successfully made level ".$level['levelName']." ".$privacyText[$privacy]."!";
			case "!sharecp":
			case "!cp":
				if(!Library::checkPermission($person, 'commandSharecp')) return "You don't have permissions to use command ".$command."!";
			
				$player = Library::getUserFromSearch(Escape::latin($commentSplit[1]));
				if(!$player) return "This user was not found!";
				
				if($player['extID'] == $level['extID']) return "User ".$player['userName']." is creator of level ".$level['levelName']."!";
				
				$shareCreatorPoints = Library::shareCreatorPoints($levelID, $person, $player['userID']);
				if(!$shareCreatorPoints) return "User ".$player['userName']." have already been shared Creator Points from level ".$level['levelName']."!";
				
				return "You successfully shared Creator Points from level ".$level['levelName']." with user ".$player['userName']."!";
			case '!lockComments':
			case '!unlockComments':
			case '!lc':
			case '!unlc':
				if(!Library::checkPermission($person, 'commandLockComments')) return "You don't have permissions to use command ".$command."!";
				
				$lockCommentingArray = [
					'!lockComments' => 1, '!lc' => 1,
					'!unlockComments' => 0, '!unlc' => 0
				];
				$lockCommenting = $lockCommentingArray[$command];
				if($level['commentLocked'] == $lockCommenting) return "Comments on level ".$level['levelName']." are already ".(!$lockCommenting ? 'un' : '')."locked!";
				
				Library::lockCommentingOnLevel($levelID, $person, $lockCommenting);
				
				return "You successfully ".(!$lockCommenting ? 'un' : '')."locked comments on level ".$level['levelName']."!";
			case '!delete':
			case '!delet':
			case '!del':
			case '!d':
				if(!Library::checkPermission($person, 'commandDelete')) return "You don't have permissions to use command ".$command."!";
				
				Library::deleteLevel($levelID, $person);
				
				return "You successfully deleted level ".$level['levelName']."!";
		}
		
		return "Command ".$command." was not found.";
	}
}
?>