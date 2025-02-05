<?php 

/*
	From https://github.com/sathoro/php-xor-cipher
*/

class XORCipher {

	public static function cipher($plaintext, $key) {
		$key = self::text2ascii($key);
		$plaintext = self::text2ascii($plaintext);

		$keysize = count($key);
		$input_size = count($plaintext);

		$cipher = "";
		
		for ($i = 0; $i < $input_size; $i++)
			$cipher .= chr($plaintext[$i] ^ $key[$i % $keysize]);

		return $cipher;
	}

	public static function crack($cipher, $keysize) {
		$cipher = $this->text2ascii($cipher);
		$occurences = $key = array();
		$input_size = count($cipher);

		for ($i = 0; $i < $input_size; $i++) {
			$j = $i % $keysize;
			if (++$occurences[$j][$cipher[$i]] > $occurences[$j][$key[$j]])
				$key[$j] = $cipher[$i];
		}

		return $this->ascii2text(array_map(function($v) { return $v ^ 32; }, $key));
	}

	public static function plaintext($cipher, $key) {
		$key = $this->text2ascii($key);
		$cipher = $this->text2ascii($cipher);
		$keysize = count($key);
		$input_size = count($cipher);
		$plaintext = "";
		
		for ($i = 0; $i < $input_size; $i++)
			$plaintext .= chr($cipher[$i] ^ $key[$i % $keysize]);

		return $plaintext;
	}

	private static function text2ascii($text) {
		return array_map('ord', str_split($text));
	}

	private static function ascii2text($ascii) {
		$text = "";

		foreach($ascii as $char)
			$text .= chr($char);

		return $text;
	}
}