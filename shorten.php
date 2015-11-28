<?php
/*
 * First authored by Brian Cray
 * License: http://creativecommons.org/licenses/by/3.0/
 * Contact the author at http://briancray.com/
 */
 
ini_set('display_errors', 0);

$url_to_shorten = get_magic_quotes_gpc() ? stripslashes(trim($_REQUEST['longurl'])) : trim($_REQUEST['longurl']);

if(!empty($url_to_shorten) && preg_match('|^https?://|', $url_to_shorten))
{
	require('config.php');

	// check if the client IP is allowed to shorten
	if($_SERVER['REMOTE_ADDR'] != LIMIT_TO_IP)
	{
		die('You are not allowed to shorten URLs with this service.');
	}
	
	// check if the URL is valid
	if(CHECK_URL)
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url_to_shorten);
		curl_setopt($ch,  CURLOPT_RETURNTRANSFER, TRUE);
		$response = curl_exec($ch);
		$response_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);
		if($response_status == '404')
		{
			die('Not a valid URL');
		}
		
	}

	$stmt = $dbh->prepare('SELECT id FROM '.DB_TABLE.' WHERE long_url=?');
	
	// check if the URL has already been shortened
	$stmt->execute(array($url_to_shorten));
	$tmp = $stmt->fetch(PDO::FETCH_NUM);
	$already_shortened = $tmp[0];
	if(!empty($already_shortened))
	{
		// URL has already been shortened
		$shortened_url = getShortenedURLFromID($already_shortened);
	}
	else
	{
		// URL not in database, insert
		try {
			$dbh->beginTransaction();
			$stmt2 = $dbh->prepare('INSERT INTO '.DB_TABLE." (long_url, created, creator) VALUES (?,?,?)");
			$stmt2->execute(array($url_to_shorten, time(), $_SERVER['REMOTE_ADDR']));
			$stmt->execute(array($url_to_shorten));
			$tmp = $stmt->fetch(PDO::FETCH_NUM);
			print_r($tmp);
			$shortened_url = getShortenedURLFromID($tmp[0]);
			if(empty($shortend_url)) {
				die('Insertion ratee');
			}
			$dbh->commit();
		} catch (Exception $e) {
			$dbh->rollBack();
			echo "Failed: " . $e->getMessage();
		}
	}
	echo BASE_HREF . $shortened_url;
}

function getShortenedURLFromID ($integer, $base = ALLOWED_CHARS)
{
	$integer += 65536;
	$length = strlen($base);
	while($integer > $length - 1)
	{
		$out = $base[fmod($integer, $length)] . $out;
		$integer = floor( $integer / $length );
	}
	return $base[$integer] . $out;
}
