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

// recursive array find needle
function in_array_r($needle, $haystack, $strict = false) {
	foreach ($haystack as $item) {
		if (($strict ? $item === $needle : $item == $needle) || (is_array($item) && in_array_r($needle, $item, $strict))) {
		    return true;
		}
	}
	return false;
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
	$previouslyLoggedStreams = json_decode(file_get_contents(STREAM_LOG_FILE), true);

	if (! is_array($previouslyLoggedStreams)) {
		$previouslyLoggedStreams = array();
	}

	// Clean up streams from STREAM_LOG_FILE where stream unix_time is greater than TIME_BUFFER
	foreach($previouslyLoggedStreams as $key => $stream) {
		$max_time = $stream['unix_time'] + (strtotime(TIME_BUFFER) - time());
		if (time() > $max_time) {
			unset($previouslyLoggedStreams[$key]);
		}
	}

	// Logging
	$streamsToLogToFile = array();
	$newStreamsToAnnounce = array();

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

		foreach($rawBody->data as $key => $val) {
			// not same stream ID OR not previously saved user
			if (!array_key_exists($val->id, $previouslyLoggedStreams) || !in_array_r($val->user_name, $previouslyLoggedStreams)) {
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

			$streamsToLogToFile[$stream['id']] = array(
				'user' => $stream['user'],
				'game' => $stream['game'],
				'unix_time' => $stream['unix_time']
			);

			$notificationMessage = sprintf(
				NOTIFY_ME_NOTIFICATION,
				$stream['user'],
				$stream['game'],
				$stream['time']
			);

			// Send notification
			echo $notificationMessage . '<br>';

			if (DISABLE_NOTIFICATIONS == false) {
				try {
					$request = $provider->getAuthenticatedRequest('POST', NOTIFY_ME_API_URL.NOTIFY_ME_RESOURCE.'?&title='.NOTIFY_ME_TITLE.'&notification='.rawurlencode($notificationMessage).'&accessCode='.NOTIFY_ME_ACCESS_CODE, $accessToken);
					$response = $client->send($request);
				} catch(\League\OAuth2\Client\Provider\Exception\IdentityProviderException $e) {
					// "Notify Me" Failed to push notification
					exit($e->getMessage());
				}
			}
		}

		file_put_contents(STREAM_LOG_FILE, json_encode($streamsToLogToFile + $previouslyLoggedStreams));

	}
