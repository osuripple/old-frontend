<?php

// Database config
define('DATABASE_HOST', 'localhost');	// MySQL host. usually localhost
define('DATABASE_USER', 'root');		// MySQL username
define('DATABASE_PASS', 'meme');		// MySQL password
define('DATABASE_NAME', 'allora');		// Database name
define('DATABASE_WHAT', 'host');		// "host" or unix socket path

// Server urls, no slash
$URL['avatar'] = 'https://a.ripple.moe';
$URL['server'] = 'https://ripple.moe';

// Submit modular config. Ignore if you are using LETS
$SUBMIT['AESKey'] = 'h89f2-890h2h89b34g-h80g134n90133'; // AES Encryption key for decrypt score data. Don't touch.
$SUBMIT['outputParams'] = false; // If true, outputs $_POST params to a txt file. Only for debugging purposes.
$SUBMIT['saveFailedScores'] = false; // If true, failed/retried scores will be saved in database too (but not shown in leaderboard). Might cause some issues, leave to false.
$SUBMIT['okOutput'] = 'ok'; // Output when a score is submitted successfully. Change with pass/beatmap to get a notification in osu! to make sure that this script works fine

// Getscores config. Ignore if you are using LETS
$GETSCORES['everythingIsRanked'] = true; // False: Default, get ranked maps from db; True: All beatmaps are ranked
$GETSCORES['outputParams'] = false; // If true, outputs $_GET params to a txt file. Only for debugging purposes.

// Cron.php config. Ignore if you are using cron.go
$CRON['showSapi'] = false; // If true, cron.php will show php_sapi_name, so you can set $CRON["sapi"] to the correct value
$CRON['sapi'] = ['cli']; // php_sapi_name() required to run cron.php. Set to "cli" if cron.php is run from command line. You can specify multiple values.
$CRON['adminExec'] = false; // If true, "Run cron.php" button will run cron.php from command line with exec. If false, the button will run cron.php from browser. Set to false if you are on a windows server

// Changelog config
$ChangelogConfig = [
	// If in the commit message any of these words appear, don't show the commit in the changelog.
	'forbidden_keywords' => ['[HIDE]', '[SECRET]'],
	// These commits will be hidden
	'forbidden_commits' => [],
	// Labels (keyword,label text,color)
	'labels' => ['[FIX],fix,danger', '[WEB],web,info', '[BANCHO],bancho,warning', '[SCORES],scores,primary', '[NEW],new,success'],
	// If you want to change names from what they appear in the git logs, you can set here a different name for you and your project contributors.
	'change_name' => ['fuck' => 'a donkey', 'suck' => 'a duck'],
];

// Mailgun config
$MailgunConfig = ['domain' => '', 'key' => ''];

// WebHook configuration. Refer to the wiki for more information.
$WebHookReport = '';
$KeyAkerino = '';

// Server status page configuration
$ServerStatusConfig = [
	'service_status' => [
		'enable' 			=> true, // Must be true if you want to enable "Service status" section
		'bancho_url'        => 'http://127.0.0.1:5001', // Bancho URL
		'avatars_url'       => 'http://127.0.0.1:5000', // Avatar server URL
		'beatmap_url'       => 'http://bcache.zxq.co', 	// Beatmap mirror URL
		'api_url'           => 'http://127.0.0.1/api', 	// Ripple API URL
		'api_url'           => 'http://127.0.0.1:5002', // LETS URL
	],

	'netdata' => [
		'enable'            => true, 	// Must be true if you want to enable server stats (cpu, ram, ipv4 and so on)
		'server_url'        => 'http://127.0.0.1:19999', // Your netdata server
		'header_enable'     => true, 	// Show header with main server stats
		'system_enable'     => true, 	// Show cpu/load/ram graphs
		'network_enable'    => true, 	// Show IPv4 graphss
		'disk_enable'       => true, 	// Show disk graphs
		'disk_name'         => 'vda', 	// Your disk name
		'mysql_server'      => 'srv', 	// MySQL server name inside netstat's config file
		'mysql_enable'      => true, 	// Show mysql graphs. You must have configured netstat's mysql plugin.
		'nginx_enable'      => true, 	// Show nginx graphs. You must have configured netstat's nginx plugin.
	]
];

// Scores/PP config
$ScoresConfig = [
	"enablePP" => true,
	"useNewBeatmapsTable" => true		// 0: get beatmaps names from beatmaps_names (old php scores server)
										// 1: get beatmaps names from beatmaps (LETS)
];
