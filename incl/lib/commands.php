<?php
class Commands {
	public static function processLevelCommand($comment, $levelID, $accountID) {
		require_once __DIR__.'/mainLib.php';
		require_once __DIR__.'/exploitPatch.php';
		if(substr($comment, 0, 1) != '!') return false;
		
		$level = Library::getLevelByID($levelID);
		if(!$level) return false;
		$commentSplit = explode(' ', $comment);
		$increaseSplit = 0;
		switch($commentSplit[0]) {
			case '!rate':
			case '!r':
				$difficulty = Escape::latin($commentSplit[1]);
				if(!is_numeric($commentSplit[2])) {
					$increaseSplit++;
					$difficulty .= " ".Escape::latin($commentSplit[1 + $increaseSplit]);
				}
				$stars = Escape::number($commentSplit[2 + $increaseSplit]);
				$verifyCoins = Escape::number($commentSplit[3 + $increaseSplit]);
				$featured = Escape::number($commentSplit[4 + $increaseSplit]);
				
				if(!$stars) return "To unrate level please use !unrate.";
				
				if(!$difficulty || !is_numeric($stars) || !is_numeric($verifyCoins) || !is_numeric($featured)) return "Incorrect usage!".PHP_EOL."!rate *difficulty* *stars* *are coins verified* *featured/epic/legendary/mythic*".PHP_EOL."Example: !rate harder 7 1 4";
				
				$rateLevel = Library::rateLevel($levelID, $accountID, $difficulty, $stars, $verifyCoins, $featured);
				
				return "You successfully rated ".$level['levelName'].' as '.$rateLevel.', '.$stars .' star'.($stars > 1 ? 's!' : '!');
			case '!unrate':
			case '!unr':
				Library::rateLevel($levelID, $accountID, Library::prepareDifficultyForRating(($level['starDifficulty'] / $level['difficultyDenominator']), $level['starAuto'], $level['starDemon'], $level['starDemonDiff']), 0, 0, 0);
				
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
			case '!epi':
			case '!leg':
			case '!myt':
			case '!unfea':
			case '!unepi':
			case '!unleg':
			case '!unmyt':
				$starArray = [
					'!feature' => 1, '!fea' => 1,
					'!epic' => 2, '!epi' => 2,
					'!legendary' => 3, '!leg' => 3,
					'!mythic' => 4, '!myt' => 4,
					'!unfeature' => 0, '!unfea' => 0,
					'!unepic' => 0, '!unepi' => 0,
					'!unlegendary' => 0, '!unleg' => 0,
					'!unmythic' => 0, '!unmyt' => 0
				];
				$returnTextArray = ['unfeatured level %1$s!', 'featured level %1$s!', 'set level %1$s as epic!', 'set level %1$s as legendary!', 'set level %1$s as mythic!'];
				$featured = $starArray[$commentSplit[0]];
				
				Library::rateLevel($levelID, $accountID, Library::prepareDifficultyForRating(($level['starDifficulty'] / $level['difficultyDenominator']), $level['starAuto'], $level['starDemon'], $level['starDemonDiff']), $level['starStars'], $level['starCoins'], $featured);
				
				return "You successfully ".sprintf($returnTextArray[$featured], $level['levelName']);
		}
		
		return "Command ".$commentSplit[0]." was not found.";
	}
}
?>