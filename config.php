<?php

/* ==================================================

	Global Options

===================================================== */
	
	// Set timezone https://www.php.net/manual/en/timezones.php
	date_default_timezone_set('America/Chicago');

	// The maximum number of announced streams per twitch category on each 5 minute check
	// Alexa only allows 5 notifications every 5 minutes so best to keep this set to 1
	define('MAX_NOTIFICATIONS_PER_TWITCH_CATEGORY', 1);

	// Empty log file where we keep track of stream IDs that have already been sent to the notifier
	define('STREAM_LOG_FILE', 'streams_log');

	// useful for debugging. set to 'false' to send notifications
	define('DISABLE_NOTIFICATIONS', false);

	// Will not announce the same stream again unless amount of time has passed
	define('TIME_BUFFER', '+12 hours');

/* ==================================================

	Twitch Categories Options

	Twitch categories can be a video game title, or a Twitch streaming category like, "Just Chatting".

	The intention for this program is to use categories that have little to no activity with streamers streaming to these categories,
	and to be alerted when this occurs.

	Note that Amazon Alexa has a limit of 5 notifications every 5 minutes.

	If you have defined more than 5 twitch categories, and each category has a new stream within that 5 minute span
	the remaining notifications over the 5 will not be sent. See config option MAX_NOTIFICATIONS_PER_TWITCH_CATEGORY

===================================================== */
	
	// Array of Twitch categories you want to follow for notifications
	// The category ID can be obtained from the Twitch API, or by viewing the source of a JSON response on a Twitch category page
	const TWITCH_CATEGORIES = [
		// 1234	=> 'Game Name',		// example
		// 9876	=> 'Game Name 2',	// example
	];



/* ==================================================

	Twitch API Options to obtain access token for API requests
	https://dev.twitch.tv/docs/authentication#registration

===================================================== */
	
	// defined to get oauth2 token
	define('TWITCH_CLIENT_ID', ''); // your registered twitch client
	define('TWITCH_CLIENT_SECRET_TOKEN', ''); // do not make this public anywhere!

	// api
	define('TWITCH_API_URL', 'https://api.twitch.tv');
	define('TWITCH_STREAMS_RESOURCE', '/helix/streams');

	

/* ==================================================

	Alexa Skill, "Notify Me" options
	https://www.thomptronics.com/about/notify-me
	https://www.amazon.com/Thomptronics-Notify-Me/dp/B07BB2FYFS

	Note: Alexa will only allow 5 notifications every 5 minutes

===================================================== */

	define('NOTIFY_ME_ACCESS_CODE', '');
	define('NOTIFY_ME_API_URL', 'https://api.notifymyecho.com');
	define('NOTIFY_ME_RESOURCE', '/v1/NotifyMe');
	define('NOTIFY_ME_NOTIFICATION', '%1s is now streaming %2s at %3s');
	define('NOTIFY_ME_TITLE', 'Twitch Stream is now Live!');



/* ==================================================

	Cron Setup

	Set up a cron on your server (or 3rd party service) with the paths changed to where your program is located on server
	
	In this example, this task runs every 5 minutes. Checking for new streams within a category.

	Note, do not include the PHP "//" comment notation

===================================================== */

//		*/5 * * * * cd /your/server/path/to/twitch-unpopular-game-notify && php index.php >/dev/null 2>&1;