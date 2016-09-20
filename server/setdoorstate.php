<?php
require_once 'config.inc.php';

// Credits:
// http://stackoverflow.com/a/14735386/1532986
// https://github.com/sarciszewski/oauth2-server/blob/7a63f42462ca098a5933f3e24b50cbf7964f1e41/src/Util/KeyAlgorithm/DefaultAlgorithm.php
function random($len = 40, $strong = true)
{
	$stripped = '';

	do
	{
		$bytes = openssl_random_pseudo_bytes($len, $strong);

		// We want to stop execution if the key fails because, well, that is bad.
		if ($bytes === false || $strong === false)
			throw new \Exception('Error generating key');

		$stripped .= str_replace(['/', '+', '='], '', base64_encode($bytes));
	} while (strlen($stripped) < $len);

	return $stripped;
}

// Iterate over challenges in file while having open a copy for changes
function challenges($func)
{
	// No fucking clue why this is necessary
	global $config;

	if (!file_exists($config['challengeDB']))
		touch($config['challengeDB']);

	$handleOrig = fopen($config['challengeDB'], 'r');
	$challengeDBTmp = $config['challengeDB'] . random(5, false);
	$handleTemp = fopen($challengeDBTmp, 'a+');

	if ($handleOrig && $handleTemp)
	{
		while (($line = fgets($handleOrig)) !== false) {
			call_user_func_array($func, array($handleOrig, $handleTemp, $line));
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

// Remove challenges older than 60 seconds
challenges(function($orig, $new, $line) {
	$timestamp = intval(explode(" ", $line)[0]);

	if (time() - $timestamp < 60)
	{
		fwrite($new, $line);
	}
});

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
	$status = $_GET['status'];
	$challenge = $_GET['challenge'];
	$response = $_GET['response'];

	challenges(function($orig, $new, $line) use ($config, $challenge, $response, $status) {
		$knownChallenge = trim(explode(' ', $line)[1]);

		if ($challenge == $knownChallenge && hash('sha256', $challenge . $config['preSharedKey']) == $response)
		{
			updateStatus($status);
		}
		else
		{
			fwrite($new, $line);
		}
	});
}
?>
