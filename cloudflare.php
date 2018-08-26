<?php

class CloudflareSolver {

	private $html;
	private $url;

	function __construct($url, $html) {
		// $url must contain scheme for parse_url
		if(preg_match('/^(?:[a-zA-Z0-9+\-\.]+?:)?\/\//', $url, $matches) === 0) {
			$url = 'http://' . $url;
		}

		$this->url = parse_url($url);
		$this->html = $html;
	}

	function getTimeout() {
		return (preg_match('/}, (\d+)\);/', $this->html, $matches) === 1) ? $matches[1] : 4000;
	}

	function getSolvedUrl() {
		$query = [];

		// Read challenge values from challenge-form
		if(preg_match('/name="jschl_vc" value="([^"]+)"/', $this->html, $matches) !== 1) {
			return false;
		}
		$query['jschl_vc'] = $matches[1];
		if(preg_match('/name="pass" value="([^"]+)"/', $this->html, $matches) !== 1) {
			return false;
		}
		$query['pass'] = $matches[1];

		// Solve the challenge
		$query['jschl_answer'] = $this->solveJsChallenge();

		return $query['jschl_answer']
		     ? $this->url['scheme'] . '://' . $this->url['host'] . '/cdn-cgi/l/chk_jschl?' . http_build_query($query)
		     : false;
	}

	function isValid() {
		// Check if page is likely to be a Cloudflare challenge
		return strpos($this->html, '/cdn-cgi/l/chk_jschl') !== false
		    && strpos($this->html, 'challenge-form') !== false
		    && strpos($this->html, 'jschl_vc') !== false
		    && strpos($this->html, 'jschl_answer') !== false;
	}

	function solveJsChallenge() {
		// Find the initial value
		if(preg_match('/{"\w+":([^}]+)};/', $this->html, $matches) !== 1) {
			return false;
		}
		$challenge = self::decodeJsInt($matches[1]);

		// Find all instructions to apply
		if(preg_match_all('/([+\-*\/%])=([^ .;]+);/', $this->html, $matches, PREG_SET_ORDER) === FALSE) {
			return false;
		}

		foreach($matches as $match) {
			// Decode each expression and apply the instruction
			$op = $match[1];
			$number = self::decodeJsInt($match[2]);
			$challenge = self::jsMath($op, $challenge, $number);
		}

		// Limit to 10 decimals
		$challenge = round($challenge, 10);

		// Return challenge response and length of the domain
		return $challenge + strlen($this->url['host']);
	}

	private static function decodeJsInt($jsInt) {
		if(preg_match_all("/([\/])?\+\(((?:\([^);\/]+\)\+?)+)\)/", $jsInt, $matches) > 0) {
			// Found outer layer, may contain multiple ops
			$opCount = count($matches[0]);
			$preOps = [];
			$ints = [];
			for($i = 0; $i < $opCount; $i++) {
				$preOps[] = $matches[1][$i];
				$ints[] = self::decodeJsInt($matches[2][$i]);
			}

			$finalNum = '0';
			for($i = 0; $i < $opCount; $i++) {
				if(preg_match('/[+\-*\/%]/', $preOps[$i]) === 1) {
					$finalNum = self::jsMath($preOps[$i], $ints[$i - 1], $ints[$i]);
				}
			}
			return $finalNum;
		}
		else if(preg_match_all('/\([^);]+\)\+?/', $jsInt, $matches) !== FALSE) {
			// Found numbers to concatenate

			// Make sure we consume everything we match
			$matchLen = 0;
			foreach($matches[0] as $match)
				$matchLen += strlen($match);
			self::checkValid(strlen($jsInt) === $matchLen);

			// Concatenate numbers as a string
			$finalNum = '';
			$opCount = count($matches[0]);
			for($i = 0; $i < $opCount; $i++) {
				// Only count the expressions that equals '1'
				$finalNum .= substr_count($matches[0][$i], '+!![]') + substr_count($matches[0][$i], '!+[]');
			}
			return $finalNum;
		} else {
			// ???
			self::checkValid(false);
		}
	}

	private static function jsMath($op, $a, $b) {
		// Use same precision as JS
		$oldPrecision = ini_set('precision', 17);
		$a = (float)$a;
		$b = (float)$b;
		switch($op) {
			case '+': $a += $b; break;
			case '-': $a -= $b; break;
			case '*': $a *= $b; break;
			case '/': $a /= $b; break;
			case '%': $a %= $b; break;
			default: self::checkValid(false);
		}
		$a = (string)$a;
		ini_set('precision', $oldPrecision);
		return $a;
	}

	private static function checkValid($expression) {
		if(!$expression) {
			echo 'Assertion failed.' . PHP_EOL;
			debug_print_backtrace();
			die();
		}
	}
}
