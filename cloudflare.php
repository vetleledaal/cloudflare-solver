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
		if(preg_match_all('/([+\-*])=([^;]+);/', $this->html, $matches, PREG_SET_ORDER) === FALSE) {
			return false;
		}

		foreach($matches as $match) {
			// Decode each expression and apply the instruction
			$op = $match[1];
			$number = self::decodeJsInt($match[2]);
			if(!self::mathExec($challenge, $number, $op)) {
				return false;
			}
		}

		// Return challenge result and length of the domain
		return $challenge + strlen($this->url['host']);
	}

	private static function decodeJsInt($jsInt) {
		if(preg_match('/^\+\(\(?([^);]+)\)\+\(([^);]+)\)\)$/', $jsInt, $matches) === 1) {
			// Got two sets separated into tens and singles
			return self::decodeJsInt($matches[1]) * 10 + self::decodeJsInt($matches[2]);
		} else {
			// Count the expressions that equals '1'
			return substr_count($jsInt, '!![]') + substr_count($jsInt, '!+[]');
		}
	}

	private static function mathExec(&$a, $b, $op) {
		// Only +, - and * have been observed
		switch($op) {
			case '+': $a += $b; return true;
			case '-': $a -= $b; return true;
			case '*': $a *= $b; return true;
			case '/': $a /= $b; return true;
			case '%': $a %= $b; return true;
		}
		return false;
	}
}