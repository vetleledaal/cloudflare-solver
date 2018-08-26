<?php
include 'cloudflare.php';

if(!($url = !empty($argv[1]) ? $argv[1] : false)) {
	echo 'Usage: php ' . $argv[0] . ' example.com' . PHP_EOL;
	return;
}

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:61.0) Gecko/20100101 Firefox/61.0');

curl_setopt($ch, CURLOPT_ENCODING, 'gzip');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
// curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Insecure, 'fixes' cURL error 60

curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_MAXREDIRS, 21);

curl_setopt($ch, CURLOPT_COOKIEJAR, 'cookies.dat');
curl_setopt($ch, CURLOPT_COOKIEFILE, 'cookies.dat');
$result = curl_exec($ch);

if($result === false) {
	echo 'Fatal cURL error ' . curl_errno($ch) . ': ' . curl_error($ch) . PHP_EOL;
	return;
}

$cf = new CloudflareSolver($url, $result);
if($cf->isValid()) {
	echo 'Waiting for ' . $cf->getTimeout() / 1000 . ' seconds...' . PHP_EOL;
	usleep($cf->getTimeout() * 1000);
	curl_setopt($ch, CURLOPT_URL, $cf->getSolvedUrl());
	$result = curl_exec($ch);
	if($result === false) {
		echo 'Fatal cURL error ' . curl_errno($ch) . ': ' . curl_error($ch) . PHP_EOL;
		return;
	}
} else {
	echo 'No challenge found' . PHP_EOL;
}

curl_close($ch);

if(preg_match('/<title>([^<]*)<\/title>/', $result, $matches) === 1) {
	echo 'Title is "' . trim($matches[1]) . '"' . PHP_EOL;
} else {
	echo 'No title found, ' . strlen($result) . ' bytes were returned.' . PHP_EOL;
}
