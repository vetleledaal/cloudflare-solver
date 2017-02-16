<?php
include 'cloudflare.php';

$url = 'http://papers.gceguide.com/';

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/55.0.2883.87 Safari/537.36');

curl_setopt($ch, CURLOPT_ENCODING, 'gzip');
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

curl_setopt($ch, CURLOPT_COOKIEJAR, 'cookies.dat');
curl_setopt($ch, CURLOPT_COOKIEFILE, 'cookies.dat');
$result = curl_exec($ch);

$cf = new CloudflareSolver($url, $result);
if($cf->isValid()) {
	echo 'Waiting for ' . $cf->getTimeout()/1000 . ' seconds...' . "\r\n";
	usleep($cf->getTimeout() * 1000);
	curl_setopt($ch, CURLOPT_URL, $cf->getSolvedUrl());
	$result = curl_exec($ch);
}
curl_close($ch);

if(preg_match('/<title>(.*?)<\/title>/', $result, $matches) === 1)
	echo 'Title is "' . $matches[1] . "\"\r\n";