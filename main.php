<?php

$html = <<<HTML
var s,t,o,p,b,r,e,a,k,i,n,g,f, SdgCLeG={"q":!+[]+!![]+!+[]+!![]+!![]+!![]};
        t = document.createElement('div');
        t.innerHTML="<a href='/'>x</a>";
        t = t.firstChild.href;r = t.match(/https?:\/\//)[0];
        t = t.substr(r.length); t = t.substr(0,t.length-1);
        a = document.getElementById('jschl-answer');
        f = document.getElementById('challenge-form');
        ;SdgCLeG.q+=+((!+[]+!![]+!![]+[])+(+[]));SdgCLeG.q*=+((!+[]+!+[]+!![]+!+[]+[])+(+!![]));SdgCLeG.q-=+((!+[]+!![]+!![]+[])+(+!![]));SdgCLeG.q*=+((+!![]+[])+(!+[]+!![]+!![]+!![]+!![]+!![]+!![]+!![]));SdgCLeG.q*=+((!+[]+!![]+!![]+!![]+[])+(+[]));a.value = parseInt(SdgCLeG.q, 10) + t.length; 
  <form id="challenge-form" action="/cdn-cgi/l/chk_jschl" method="get">
    <input type="hidden" name="jschl_vc" value="1a79a4d60de6718e8e5b326e338ae533"/>
    <input type="hidden" name="pass" value="1487112786.013-dEMzfIsOva"/>
    <input type="hidden" id="jschl-answer" name="jschl_answer"/>
  </form>
HTML;

echo solve_challenge($html, 'http://example.com');



function solve_challenge($html, $url) {
	$url = parse_url($url);
	$fields = array();
	
	preg_match('/name="jschl_vc" value="([^"]+)"/', $html, $matches);
	$fields['jschl_vc'] = $matches[1];
	
	preg_match('/name="pass" value="([^"]+)"/', $html, $matches);
	$fields['pass'] = $matches[1];
	
	$fields['jschl_answer'] = decode_cfchallenge($html, $url['host']);	
	return $url['scheme'] . '://' . $url['host'] . '/cdn-cgi/l/chk_jschl?'
		. http_build_query($fields);
}

function decode_cfchallenge($html, $domain) {
	preg_match('/{"\w":([^}]+)};/', $html, $matches); // Initial value
	$jschl = decode_cfint($matches[1]);
	
	preg_match_all('/([+\-*])=([^;]+);/', $html, $matches, PREG_SET_ORDER); // Operations
	foreach($matches as $match) {
		$op = $match[1];
		$number = decode_cfint($match[2]);
		do_math($jschl, $number, $op);
	}
	
	return $jschl + strlen($domain);
}

function decode_cfint($cfint) {
	if(preg_match('/^\+\(\(?([^);]+)\)\+\(([^);]+)\)\)$/', $cfint, $matches) !== 0) {
		return decode_cfint($matches[1])*10 + decode_cfint($matches[2]);
	} else {
		return substr_count($cfint, '!![]') + substr_count($cfint, '!+[]');
	}
}

function do_math(&$a, $b, $op) {
	switch($op) {
		case '+': $a += $b; return true;
		case '-': $a -= $b; return true;
		case '*': $a *= $b; return true;
	}
	echo "Unknown operator $op\n";
	return false;
}