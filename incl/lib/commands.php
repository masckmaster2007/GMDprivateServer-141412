<?php
class Commands {
	public static function processLevelCommand($comment, $level, $person) {
		require __DIR__.'/../../config/misc.php';
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

				if(!$stars) return "Please use !unrate to unrate level.";
				
				if($dontRateYourOwnLevels && $person['userID'] == $level['userID']) return "You can't rate your own level.";
				
				$rateLevel = Library::rateLevel($levelID, $person, $difficulty, $stars, $verifyCoins, $featured);
				
				return "You successfully rated ".$level['levelName'].' as '.$rateLevel.', '.$stars .' star'.($stars > 1 ? 's!' : '!');
			case '!unrate':
			case '!unr':
				if(!Library::checkPermission($person, 'commandRate')) return "You don't have permissions to use command ".$command."!";
				
				if($dontRateYourOwnLevels && $person['userID'] == $level['userID']) return "You can't unrate your own level.";
				
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
			case '!unf':
			case '!unepi':
			case '!unleg':
			case '!unmyt':
				$commandArray = [
					'!feature' => 1, '!fea' => 1, '!f' => 1,
					'!epic' => 2, '!epi' => 2,
					'!legendary' => 3, '!leg' => 3,
					'!mythic' => 4, '!myt' => 4,
					'!unfeature' => 0, '!unfea' => 0, '!unf' => 0,
					'!unepic' => 0, '!unepi' => 0,
					'!unlegendary' => 0, '!unleg' => 0,
					'!unmythic' => 0, '!unmyt' => 0
				];
				$returnTextArray = ['unfeatured %1$s!', 'featured %1$s!', 'set %1$s as epic!', 'set %1$s as legendary!', 'set %1$s as mythic!'];
				$featured = $commandArray[$command];
				
				$featurePermission = $featured < 2 && $level['starEpic'] == 0 ? 'Feature' : 'Epic';
				if(!Library::checkPermission($person, 'command'.$featurePermission)) return "You don't have permissions to use command ".$command."!";
				
				if($dontRateYourOwnLevels && $person['userID'] == $level['userID']) return "You can't ".(!$featured ? 'un' : '')."feature your own level.";
				
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
			
				$returnTextArray = ['unverified coins in %1$s!', 'verified coins in %1$s!'];
				$verifyCoins = $commandArray[$command];
				
				if($dontRateYourOwnLevels && $person['userID'] == $level['userID']) return "You can't ".(!$verifyCoins ? 'un' : '')."verify coins on your own level.";
				
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
				if(!$setDaily) return $level['levelName']." is already ".($type ? 'weekly' : 'daily')."!";
				
				return "You successfully set ".$level['levelName']." as ".($type ? 'weekly' : 'daily')."!".PHP_EOL
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
				if(!$removeDaily) return $level['levelName']." is not ".($type ? 'weekly' : 'daily')." level!";
				
				return "You successfully removed ".$level['levelName']." from ".($type ? 'weekly' : 'daily')." levels!";
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
				if(!$setEvent) return $level['levelName']." is already event level!";
				
				return "You successfully set ".$level['levelName']." as event level!".PHP_EOL
					."It will appear ".Library::makeTime($setEvent).'.';
			case "!unevent":
			case "!unev":
				if(!Library::checkPermission($person, 'commandEvent')) return "You don't have permissions to use command ".$command."!";

				$removeEvent = Library::removeEventLevel($levelID, $person);
				if(!$removeEvent) return $level['levelName']." is not event level!";
				
				return "You successfully removed ".$level['levelName']." from event levels!";
			case '!send':
			case '!suggest':
			case '!sug':
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
				
				if($dontRateYourOwnLevels && $person['userID'] == $level['userID']) return "You can't suggest your own level.";
				
				$sendLevel = Library::sendLevel($levelID, $person, $difficulty, $stars, $featured);
				if(!$sendLevel) return "You already suggested ".$level['levelName']."!";
				
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
				
				return "You successfully moved ".$level['levelName']." to user ".$player['userName']."!";
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
				if($level['updateLocked'] == $lockUpdating) return $level['levelName']." is already ".(!$lockUpdating ? 'un' : '')."locked!";
				
				Library::lockUpdatingLevel($levelID, $person, $lockUpdating);
				
				return "You successfully ".(!$lockUpdating ? 'un' : '')."locked ".$level['levelName']."!";
			case "!rename":
			case "!re":
				if(!Library::checkPermission($person, 'commandRename') && $person['userID'] != $level['userID']) return "You don't have permissions to use command ".$command."!";
			
				unset($commentSplit[0]);
				$newLevelName = trim(Escape::latin(implode(' ', $commentSplit)));
				if(!$newLevelName) {
					return "Incorrect usage!".PHP_EOL
						."!rename *level name*".PHP_EOL
						."Example: !rename My cool level";
				}
				
				if($level['levelName'] == $newLevelName) return $level['levelName']." already has this name!";
				
				Library::renameLevel($levelID, $person, $newLevelName);
				
				return "You successfully renamed ".$level['levelName']." to ".$newLevelName."!";
			case "!password":
			case "!pass":
			case "!p":
				if(!Library::checkPermission($person, 'commandPass') && $person['userID'] != $level['userID']) return "You don't have permissions to use command ".$command."!";
				
				if(!$commentSplit[1] || !is_numeric($commentSplit[1]) || strlen($commentSplit[1]) > 6) {
					return "Incorrect usage!".PHP_EOL
						."!password *level password*".PHP_EOL
						."Example: !password 141412";
				}
				
				$newPassword = sprintf("%06d", Escape::number($commentSplit[1]));
				
				if($level['password'] == '1'.$newPassword || $level['password'].'000000' == '1'.$newPassword) return $level['levelName']." already has this password!";
				
				Library::changeLevelPassword($levelID, $person, $newPassword);
				
				return "You successfully changed password of ".$level['levelName'].' to '.$newPassword."!";
			case "!song":
			case "!s":
				if(!Library::checkPermission($person, 'commandSong') && $person['userID'] != $level['userID']) return "You don't have permissions to use command ".$command."!";
			
				$songID = Escape::number($commentSplit[1]);
				if(!$songID) {
					return "Incorrect usage!".PHP_EOL
						."!song *song ID*".PHP_EOL
						."Example: !song 1967605";
				}
				
				if($level["songID"] == $songID) return $level['levelName']." already has this song!";
				
				$song = Library::getSongByID($songID);
				if(!$song) return "This song doesn't exist!";
				
				Library::changeLevelSong($levelID, $person, $songID);
				
				return "You successfully changed song of ".$level['levelName']." to ".Escape::translit($song['authorName'])." - ".Escape::translit($song['name'])."!";
			case "!description":
			case "!desc":
				if(!Library::checkPermission($person, 'commandDescription') && $person['userID'] != $level['userID']) return "You don't have permissions to use command ".$command."!";
			
				unset($commentSplit[0]);
				$newLevelDesc = Library::escapeDescriptionCrash(trim(Escape::text(implode(' ', $commentSplit))));
				if(!$newLevelDesc) {
					return "Incorrect usage!".PHP_EOL
						."!description *level description*".PHP_EOL
						."Example: !description This is my cool level i made in 3 hours. Please enjoy!";
				}
				
				if(Escape::url_base64_decode($level['levelDesc']) == $newLevelDesc) return $level['levelName']." already has this description!";
				
				Library::changeLevelDescription($levelID, $person, $newLevelDesc);
				
				return "You successfully changed description of ".$level['levelName']." to:".PHP_EOL
					.$newLevelDesc;
			case "!public":
			case "!unlist":
			case "!friends":
			case "!pub":
			case "!unl":
			case "!fr":
				if(!Library::checkPermission($person, 'commandPublic') && $person['userID'] != $level['userID']) return "You don't have permissions to use command ".$command."!";
			
				$privacyArray = [
					'!public' => 0, '!pub' => 0,
					'!friends' => 1, '!fr' => 1,
					'!unlist' => 2, '!unl' => 2,
				];
				$privacyText = ['public', 'only for friends', 'unlisted'];
				$privacy = $privacyArray[$command];
				
				if($level['unlisted'] == $privacy) return $level['levelName']." is already ".$privacyText[$privacy]."!";
				
				Library::changeLevelPrivacy($levelID, $person, $privacy);
				
				return "You successfully made ".$level['levelName']." ".$privacyText[$privacy]."!";
			case "!sharecp":
			case "!cp":
				if(!Library::checkPermission($person, 'commandSharecp')) return "You don't have permissions to use command ".$command."!";
			
				$player = Library::getUserFromSearch(Escape::latin($commentSplit[1]));
				if(!$player) return "This user was not found!";
				
				if($player['extID'] == $level['extID']) return "User ".$player['userName']." is creator of ".$level['levelName']."!";
				
				$shareCreatorPoints = Library::shareCreatorPoints($levelID, $person, $player['userID']);
				if(!$shareCreatorPoints) return "User ".$player['userName']." have already been shared Creator Points from ".$level['levelName']."!";
				
				return "You successfully shared Creator Points from ".$level['levelName']." with user ".$player['userName']."!";
			case '!lockComments':
			case '!unlockComments':
			case '!lc':
			case '!unlc':
				if(!Library::checkPermission($person, 'commandLockComments') && $person['userID'] != $level['userID']) return "You don't have permissions to use command ".$command."!";
				
				$lockCommentingArray = [
					'!lockComments' => 1, '!lc' => 1,
					'!unlockComments' => 0, '!unlc' => 0
				];
				$lockCommenting = $lockCommentingArray[$command];
				if($level['commentLocked'] == $lockCommenting) return "Comments on ".$level['levelName']." are already ".(!$lockCommenting ? 'un' : '')."locked!";
				
				Library::lockCommentingOnLevel($levelID, $person, $lockCommenting);
				
				return "You successfully ".(!$lockCommenting ? 'un' : '')."locked comments on ".$level['levelName']."!";
			case '!delete':
			case '!delet':
			case '!del':
			case '!d':
				if(!Library::checkPermission($person, 'commandDelete') && $person['userID'] != $level['userID']) return "You don't have permissions to use command ".$command."!";
				
				Library::deleteLevel($levelID, $person);
				
				return "You successfully deleted ".$level['levelName']."!";
		}
		
		return "Command ".$command." was not found.";
	}
	
	public static function processListCommand($comment, $list, $person) {
		require __DIR__.'/../../config/misc.php';
		require_once __DIR__.'/mainLib.php';
		require_once __DIR__.'/exploitPatch.php';
		
		if(substr($comment, 0, 1) != '!') return false;
		
		$listID = $list['listID'];
		
		$commentSplit = explode(' ', $comment);
		$increaseSplit = 0;
		$command = $commentSplit[0];
		
		switch($command) {
			case '!rate':
			case '!r':
				if(!Library::checkPermission($person, 'commandRate')) return "You don't have permissions to use command ".$command."!";

				$reward = Escape::number($commentSplit[1]);
				$difficulty = Escape::latin($commentSplit[2]);
				if(!is_numeric($commentSplit[3])) {
					$increaseSplit++;
					$difficulty .= " ".Escape::latin($commentSplit[2 + $increaseSplit]);
				}
				$featured = Escape::number($commentSplit[3 + $increaseSplit]);
				$levelsCount = Escape::number($commentSplit[4 + $increaseSplit]);
				
				if(empty($levelsCount)) $levelsCount = count(explode(',', $list['listlevels']));
				
				if(!is_numeric($reward) || !$difficulty || !is_numeric($featured)) {
					return "Incorrect usage!".PHP_EOL
						."!rate *reward amount* *difficulty* *is featured* *required levels amount to complete list*".PHP_EOL
						."Example: !rate 50 harder 1 7";
				}

				if(!$reward) return "Please use !unrate to unrate list.";
				
				if($dontRateYourOwnLevels && $person['accountID'] == $list['accountID']) return "You can't rate your own list.";
				
				$rateList = Library::rateList($listID, $person, $reward, $difficulty, $featured, $levelsCount);
				
				return "You successfully rated ".$list['listName'].' as '.$rateList.', '.$reward .' diamond'.($reward > 1 ? 's!' : '!');
			case '!unrate':
			case '!unr':
				if(!Library::checkPermission($person, 'commandRate')) return "You don't have permissions to use command ".$command."!";
				
				if($dontRateYourOwnLevels && $person['accountID'] == $list['accountID']) return "You can't unrate your own list.";
				
				Library::rateList($listID, $person, 0, $list['starDifficulty'], 0, 0);
				
				return "You successfully unrated ".$list['listName'].'!';
			case '!feature':
			case '!unfeature':
			case '!fea':
			case '!unfea':
			case '!f':
			case '!unf':
				if(!Library::checkPermission($person, 'commandFeature')) return "You don't have permissions to use command ".$command."!";
				
				$commandArray = [
					'!feature' => 1, '!fea' => 1, '!f' => 1,
					'!unfeature' => 0, '!unfea' => 0, '!unf' => 0,
				];
				$featuredValue = $commandArray[$command];
				
				if($dontRateYourOwnLevels && $person['accountID'] == $list['accountID']) return "You can't ".(!$featuredValue ? 'un' : '')."feature your own list.";
				
				Library::rateList($listID, $person, $list['starStars'], $list['starDifficulty'], $featuredValue, $list['countForReward']);
				
				return "You successfully ".(!$featuredValue ? 'un' : '')."featured ".$list['listName'].'!';
			case '!delete':
			case '!delet':
			case '!del':
			case '!d':
				if(!Library::checkPermission($person, 'commandDelete') && $person['accountID'] != $list['accountID']) return "You don't have permissions to use command ".$command."!";
				
				Library::deleteList($listID, $person);
				
				return "You successfully deleted ".$list['listName']."!";
			case "!public":
			case "!unlist":
			case "!friends":
			case "!pub":
			case "!unl":
			case "!fr":
				if(!Library::checkPermission($person, 'commandPublic') && $person['accountID'] != $list['accountID']) return "You don't have permissions to use command ".$command."!";
			
				$privacyArray = [
					'!public' => 0, '!pub' => 0,
					'!friends' => 1, '!fr' => 1,
					'!unlist' => 2, '!unl' => 2,
				];
				$privacyText = ['public', 'only for friends', 'unlisted'];
				$privacy = $privacyArray[$command];
				
				if($list['unlisted'] == $privacy) return $list['listName']." is already ".$privacyText[$privacy]."!";
				
				Library::changeListPrivacy($listID, $person, $privacy);
				
				return "You successfully made ".$list['listName']." ".$privacyText[$privacy]."!";
			case '!setacc':
			case '!account':
			case '!move':
			case '!sa':
			case '!acc':
			case '!m':
				if(!Library::checkPermission($person, 'commandSetacc')) return "You don't have permissions to use command ".$command."!";
			
				$player = Library::getUserFromSearch(Escape::latin($commentSplit[1]));
				if(!$player) return "This user was not found!";
				
				if($player['extID'] == $list['accountID']) return "User ".$player['userName']." already owns ".$list['listName']."!";
				
				Library::moveList($listID, $person, $player);
				
				return "You successfully moved ".$list['listName']." to user ".$player['userName']."!";
			case "!rename":
			case "!re":
				if(!Library::checkPermission($person, 'commandRename') && $person['accountID'] != $list['accountID']) return "You don't have permissions to use command ".$command."!";
			
				unset($commentSplit[0]);
				$newListName = trim(Escape::latin(implode(' ', $commentSplit)));
				if(!$newListName) {
					return "Incorrect usage!".PHP_EOL
						."!rename *list name*".PHP_EOL
						."Example: !rename My cool list";
				}
				
				if($list['listName'] == $newListName) return $list['listName']." already has this name!";
				
				Library::renameList($listID, $person, $newListName);
				
				return "You successfully renamed ".$list['listName']." to ".$newListName."!";
			case "!description":
			case "!desc":
				if(!Library::checkPermission($person, 'commandDescription') && $person['accountID'] != $list['accountID']) return "You don't have permissions to use command ".$command."!";
			
				unset($commentSplit[0]);
				$newListDesc = Library::escapeDescriptionCrash(trim(Escape::text(implode(' ', $commentSplit))));
				if(!$newListDesc) {
					return "Incorrect usage!".PHP_EOL
						."!description *list description*".PHP_EOL
						."Example: !description This is list with my favorite levels. Please enjoy!";
				}
				
				if(Escape::url_base64_decode($list['listDesc']) == $newListDesc) return $list['listName']." already has this description!";
				
				Library::changeListDescription($listID, $person, $newListDesc);
				
				return "You successfully changed description of ".$list['listName']." to:".PHP_EOL
					.$newListDesc;
			case '!lockComments':
			case '!unlockComments':
			case '!lc':
			case '!unlc':
				if(!Library::checkPermission($person, 'commandLockComments') && $person['accountID'] != $list['accountID']) return "You don't have permissions to use command ".$command."!";
				
				$lockCommentingArray = [
					'!lockComments' => 1, '!lc' => 1,
					'!unlockComments' => 0, '!unlc' => 0
				];
				$lockCommenting = $lockCommentingArray[$command];
				if($list['commentLocked'] == $lockCommenting) return "Comments on ".$list['listName']." are already ".(!$lockCommenting ? 'un' : '')."locked!";
				
				Library::lockCommentingOnList($listID, $person, $lockCommenting);
				
				return "You successfully ".(!$lockCommenting ? 'un' : '')."locked comments on ".$list['listName']."!";
			case '!send':
			case '!suggest':
			case '!sug':
				if(!Library::checkPermission($person, 'commandSuggest')) return "You don't have permissions to use command ".$command."!";
			
				$reward = Escape::number($commentSplit[1]);
				$difficulty = Escape::latin($commentSplit[2]);
				if(!is_numeric($commentSplit[3])) {
					$increaseSplit++;
					$difficulty .= " ".Escape::latin($commentSplit[2 + $increaseSplit]);
				}
				$featured = Escape::number($commentSplit[3 + $increaseSplit]);
				$levelsCount = Escape::number($commentSplit[4 + $increaseSplit]);
				
				if(empty($levelsCount)) $levelsCount = count(explode(',', $list['listlevels']));
				
				if(!is_numeric($reward) || !$difficulty || !is_numeric($featured)) {
					return "Incorrect usage!".PHP_EOL
						."!send *reward amount* *difficulty* *is featured* *required levels amount to complete list*".PHP_EOL
						."Example: !send 50 harder 1 7";
				}
				
				if($dontRateYourOwnLevels && $person['accountID'] == $list['accountID']) return "You can't suggest your own list.";
				
				$sendList = Library::sendList($listID, $person, $reward, $difficulty, $featured, $levelsCount);
				if(!$sendList) return "You already suggested ".$list['listName']."!";
				
				return "You successfully sent ".$list['listName'].' as '.$sendList.', '.$reward .' diamond'.($reward > 1 ? 's!' : '!');
		}
		
		return "Command ".$command." was not found.";
	}
	
	public static function processProfileCommand($comment, $account, $person) {
		require __DIR__.'/../../config/discord.php';
		require_once __DIR__.'/mainLib.php';
		require_once __DIR__.'/discord.php';
		require_once __DIR__.'/exploitPatch.php';
		
		if(substr($comment, 0, 1) != '!') return false;
		
		$accountID = $person['accountID'];
		
		$commentSplit = explode(' ', $comment);
		$command = $commentSplit[0];
		$subCommand = $commentSplit[1];
		
		if($command != '!discord') return "Command ".$command." was not found.";
		if(!$subCommand) return "Please specify subcommand to ".$command.".";
		
		if(!$discordEnabled) return "Linking account to Discord is disabled!";
		
		switch($subCommand) {
			case 'link':
			case 'l':
				$discordID = Escape::number($commentSplit[2]);
				if(!$discordID) {
					return "Incorrect usage!".PHP_EOL
						."!discord link *Discord account ID*".PHP_EOL
						."Example: !discord link 297295491417505793";
				}
				
				$link = Discord::getUserDiscord($accountID);
				if($link) return "You already linked your account with Discord ID ".$link."!";
				
				$discordAccount = Library::getAccountByDiscord($discordID);
				if($discordAccount) return "Discord ID ".$discordID." is already linked with account ".$discordAccount['userName']."!";
				
				$startLinking = Discord::startLinkingAccount($person, $discordID);
				if(!$startLinking) return "Something went wrong when trying to send code to Discord DMs or Discord ID ".$discordID." doesn't exist.";
				
				return "Verification code and next steps were sent to ".$startLinking." (".$discordID.")!";
			case 'accept':
			case 'verify':
			case 'a':
			case 'v':
				$code = Escape::number($commentSplit[2]);
				if(!$code) {
					return "Incorrect usage!".PHP_EOL
						."!discord accept *Verification code*".PHP_EOL
						."Example: !discord accept 7024";
				}
				
				$link = Discord::getUserDiscord($accountID);
				if($link) return "You already linked your account with Discord ID ".$link."!";
				
				$verifyLinking = Discord::verifyDiscordLinking($person, $code);
				if(!$verifyLinking) return "You didn't start linking your Discord account or code is wrong.";
				
				return "You successfully linked your account to Discord ID ".$verifyLinking."!";
			case 'unlink':
			case 'u':
				$unlink = Discord::unlinkDiscordAccount($person);
				if(!$unlink) return "Your account doesn't have connection with Discord!";
				
				return "You successfully unlinked your account from Discord!";
		}
		
		return "Command ".$command." ".$subCommand." was not found.";
	}
}
?>