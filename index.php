<?php

define('APP_FOLDER_NAME', 'twitch-unpopular-game-notify');

require_once dirname(__DIR__).'/'.APP_FOLDER_NAME.'/vendor/autoload.php';
require_once dirname(__DIR__).'/'.APP_FOLDER_NAME.'/config.php';

// Check all user config options are defined in config.php
$requiredOptions = get_defined_constants(true);
foreach($requiredOptions['user'] as $key => $val) {
	if (empty($val)) {
		exit('Config option '.$key.' is required. Set this value in file config.php');
	}
}

/*******************************************************************************************
********************************************************************************************/

	// Twitch OAuth Client Credentials Flow
	// https://dev.twitch.tv/docs/authentication/getting-tokens-oauth/#oauth-client-credentials-flow
	$provider = new Vertisan\OAuth2\Client\Provider\TwitchHelix([
	    'clientId' => TWITCH_CLIENT_ID,
	   	'clientSecret' => TWITCH_CLIENT_SECRET_TOKEN
	]);

	// Access token using Client Credentials Grant
	$accessToken = $provider->getAccessToken('client_credentials');

	if (!is_file(STREAM_LOG_FILE)) {
		file_put_contents(STREAM_LOG_FILE, '');
		chmod(STREAM_LOG_FILE, 0777);
	}

	// Get previously logged stream contents from file
	$previouslyLoggedStreams = explode(',', file_get_contents(STREAM_LOG_FILE));

	foreach(TWITCH_CATEGORIES as $key => $val) {

		// Twitch API request
		try {
			$request = $provider->getAuthenticatedRequest('GET', TWITCH_API_URL.TWITCH_STREAMS_RESOURCE . '?game_id=' . rawurlencode($key), $accessToken);
		} catch (\League\OAuth2\Client\Provider\Exception\IdentityProviderException $e) {
			exit($e->getMessage());
		}
		
		// Twitch API response
		$client = new \GuzzleHttp\Client();
		$response = $client->send($request);
		$rawBody = json_decode($response->getBody()->getContents());

		// no data for category
		if (empty($rawBody->data)) {
			continue;
		}

		// Logging
		$streamsToLogToFile = array();
		$newStreamsToAnnounce = array();

		foreach($rawBody->data as $key => $val) {
			if (!in_array($val->id, $previouslyLoggedStreams)) {
				$newStreamsToAnnounce[$val->id] = array(
					'id' => $val->id,
					'title' => $val->title,
					'game' => TWITCH_CATEGORIES[$val->game_id],
					'user' => $val->user_name,
					'time' => date('h:iA', strtotime($val->started_at)),
					'unix_time' => strtotime($val->started_at)
				);
			}
		}

		// Twitch has no way to sort the return api result so we flip and sort desc by streams unix_time start
		usort($newStreamsToAnnounce, function($a, $b) {
			return $a['unix_time'] <=> $b['unix_time'];
		});

		// The number of items from the stack we use based on MAX_NOTIFICATIONS_PER_TWITCH_CATEGORY
		$newStreamsToAnnounce = array_slice(array_reverse($newStreamsToAnnounce), 0, MAX_NOTIFICATIONS_PER_TWITCH_CATEGORY);

		foreach($newStreamsToAnnounce as $stream) {
			$streamsToLogToFile[] = $stream['id'];
			$notificationMessage = sprintf(
				NOTIFY_ME_NOTIFICATION,
				$stream['user'],
				$stream['game'],
				$stream['time']
			);

			// Send notification
			echo $notificationMessage . PHP_EOL;

			if (DISABLE_NOTIFICATIONS == 'false') {
				try {
					$request = $provider->getAuthenticatedRequest('POST', NOTIFY_ME_API_URL.NOTIFY_ME_RESOURCE.'?&title='.NOTIFY_ME_TITLE.'&notification='.rawurlencode($notificationMessage).'&accessCode='.NOTIFY_ME_ACCESS_CODE, $accessToken);
					$response = $client->send($request);
				} catch(\League\OAuth2\Client\Provider\Exception\IdentityProviderException $e) {
					// "Notify Me" Failed to push notification
					exit($e->getMessage());
				}
			}

		}

		// Writing to log file so we don't send notifications that have already been sent previously
		if (!empty($streamsToLogToFile)) {

			$streamsToLogToFile = implode(',', array_diff($streamsToLogToFile, $previouslyLoggedStreams));

			// dealing with comma delimited when using file_put_contents with FILE_APPEND
			if (filesize(STREAM_LOG_FILE)) {
				$file_contents = ',' . $streamsToLogToFile;
			} else {
				$file_contents = $streamsToLogToFile;
			}

			file_put_contents(STREAM_LOG_FILE, $file_contents, FILE_APPEND);
		}
		
	}
	
