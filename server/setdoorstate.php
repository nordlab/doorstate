<?php
require_once 'config.inc.php';

function random($len = 40)
{
	$chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789-_';
	$result = '';

	for ($i = 0; $i < $len; $i++)
	{
		// Pick random ASCII char between and including 33-126
		$result .= $chars[rand(0, strlen($chars) - 1)];
	}

	return $result;
}

// Iterate over challenges in file while having open a copy for changes
function challenges($func)
{
	// No fucking clue why this is necessary
	global $config;

	if (!file_exists($config['challengeDB']))
		touch($config['challengeDB']);

	$handleOrig = fopen($config['challengeDB'], 'r');
	$challengeDBTmp = $config['challengeDB'] . random(5);
	$handleTemp = fopen($challengeDBTmp, 'a+');

	if ($handleOrig && $handleTemp)
	{
		while (($line = fgets($handleOrig)) !== false) {
			$func($handleOrig, $handleTemp, $line);
		}

		fclose($handleOrig);
		fclose($handleTemp);
		rename($challengeDBTmp, $config['challengeDB']);
	}
}

function updateStatus($status)
{
	// No fucking clue why this is necessary
	global $config;

	$handle = fopen($config['statusFile'], 'w+');

	if ($status === 'geschlossen')
		fwrite($handle, 'geschlossen');
	elseif ($status === 'offen')
		fwrite($handle, 'offen');

	fclose($handle);
}

function removeOldChallenges($orig, $new, $line)
{
	$tokens = explode(" ", $line);
	$timestamp = intval($tokens[0]);

	if (time() - $timestamp < 60)
	{
		fwrite($new, $line);
	}
}

function checkResponse($orig, $new, $line)
{
	// No fucking clue why this is necessary
	global $config;
	global $challenge;
	global $response;
	global $status;

	$tokens = explode(' ', $line);
	$knownChallenge = trim($tokens[1]);

	if ($challenge == $knownChallenge && hash('sha256', $challenge . $config['preSharedKey']) == $response)
	{
		updateStatus($status);
	}
	else
	{
		fwrite($new, $line);
	}
}

// Remove challenges older than 60 seconds
challenges('removeOldChallenges');

if (!isset($_GET['status']) && !isset($_GET['challenge']) && !isset($_GET['response']))
{
	$handle = fopen($config['challengeDB'], 'a+');
	$challenge = random();

	fwrite($handle, time() . ' ' . $challenge . "\n");
	fclose($handle);
	echo $challenge;
}
else
{
	global $challenge;
	global $response;
	global $status;

	$status = $_GET['status'];
	$challenge = $_GET['challenge'];
	$response = $_GET['response'];

	challenges("checkResponse");
}
?>
