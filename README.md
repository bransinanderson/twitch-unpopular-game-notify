# Twitch Unpopular Game Notify

There are unpopular [Twitch](https://www.twitch.tv/) video game categories that can go for weeks or months without seeing a [live streamer](https://en.wikipedia.org/wiki/Online_streamer). This program sends a notification to your [Amazon Echo](https://en.wikipedia.org/wiki/Amazon_Echo) device when a [streamer](https://www.computerhope.com/jargon/s/streamer.htm) begins live streaming your favorite obscure video game.

The intention of this program is to handpick Twitch games that have little to no activity, and to be notified when a streamer begins to live stream that game.

## Requirements

- Server that runs PHP
- Cron Job
- A [registered Twitch app](https://dev.twitch.tv/docs/authentication#registration)
- Amazon Echo device
- [Notify Me](https://www.thomptronics.com/about/notify-me) Alexa Skill installed on Amazon Echo

## Install

1. Clone or download this repository to your system
2. From the command line, navigate to project directory `cd /path/to/dir/twitch-unpopular-game-notify`
3. Run `composer install`. If Composer is not installed on your system, you can [get Composer here](https://getcomposer.org/).
4. [Open **config.php** file and set your options and save](#configuration-options-in-configphp)
5. Upload the project directory 'twitch-unpopular-game-notify' to your server
6. [Cron Job Setup](#cron-job-setup)

## Configuration Options in config.php

#### Set Timezone
Set your timezone. Timezone parameters can be found [here](https://www.php.net/manual/en/timezones.php).
`date_default_timezone_set('America/Chicago');`

#### Set Games/Categories
Set the Array of Twitch categories you want to follow for notifications.
The category ID (in this example 1234) can be obtained from the Twitch API or by viewing the source JSON response from a Twitch category page.
```
const TWITCH_CATEGORIES = [
	1234 => 'Example Game Name',
	9876 => 'Example Game Name 2',
];
```

#### Twitch API

Your registered Twitch Client ID. Twitch provides a Client ID when you [register a Twitch app](https://dev.twitch.tv/docs/authentication#registration).
```
define('TWITCH_CLIENT_ID', 'yourClientIDGoesHere');
```
Your Twitch Secret Token. Twitch provides a Secret Token when you register a Twitch app. Keep this a secret and don't expose to anyone!
```
define('TWITCH_CLIENT_SECRET_TOKEN', 'yourSecretTokenGoesHere');
```

#### [Notify Me](https://www.thomptronics.com/about/notify-me) Alexa Skill

You will receive an access code from Notify Me by [following these instructions here](https://www.thomptronics.com/about/notify-me#h.p_GOawS1aQOduh)
```
define('NOTIFY_ME_ACCESS_CODE', 'yourNotifyMeAccessCodeGoesHere');
```

## Cron Job Setup

A cron task is required for this program to run. The timing of the cron can be adjusted to your own requirements.

For example, this cron is set to run the program every 5 minutes to check for new streams.
**Update the example paths below accordingly**.
```
*/5 * * * * cd /your/server/path/to/twitch-unpopular-game-notify && php index.php >/dev/null 2>&1;
```

## Special Thanks
- [OAuth 2.0 Client](https://github.com/thephpleague/oauth2-client) by [The League of Extraordinary Packages
](https://github.com/thephpleague)
- [Twitch Helix Provider for OAuth 2.0 Client](https://github.com/vertisan/oauth2-twitch-helix) by [Paweł Farys](https://github.com/vertisan)
- [Notify Me](https://www.thomptronics.com/about/notify-me) Alexa Skill by [Thomptronics](https://www.thomptronics.com/)
