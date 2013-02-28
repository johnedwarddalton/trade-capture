<?php

/// url of download action
define ('url', 'http://orson/trade-capture/public/capture/download');
define ('interval', 9);     //interval (seconds) between attempted rss reads
define ('loop_max', 9000);


function printLine($string_message = '') {
	return isset($_SERVER['SERVER_PROTOCOL']) ? print "$string_message<br />" . PHP_EOL:
	print $string_message . PHP_EOL;
}

for ($i=0; $i<loop_max; $i++){
	$curl_handle = curl_init();
	curl_setopt($curl_handle, CURLOPT_URL, url);
	curl_setopt($curl_handle, CURLOPT_CONNECTTIMEOUT, 5);
	curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, 1);

	$buffer = trim(curl_exec($curl_handle)); 
	curl_close($curl_handle);
	$timestamp = date('m/d h:i:s');   
	$output = "$i: $timestamp $buffer";
	printLine( $output );
	sleep(interval);
}



