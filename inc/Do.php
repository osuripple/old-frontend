<?php

// We aren't calling the class Do because otherwise it would conflict with do { } while ();
class D {
	/*
	 * Register
	 * Register function
	*/
	public static function Register() {
		global $reCaptchaConfig;
		try {
			// Check if everything is set
			if (empty($_POST['u']) || empty($_POST['p1']) || empty($_POST['p2']) || empty($_POST['e']) /*|| empty($_POST['k'])*/) {
				throw new Exception('Nope.');
			}
			// Get user IP
			$ip = getIp();
			// Make sure registrations are enabled
			if (!checkRegistrationsEnabled()) {
				throw new Exception('Registrations are currently disabled.');
			}
			// Validate password through our helper
			$pres = PasswordHelper::ValidatePassword($_POST['p1'], $_POST['p2']);
			if ($pres !== -1) {
				throw new Exception($pres);
			}
			// trim spaces or other memes from username (hi kirito)
			$_POST['u'] = trim($_POST['u']);
			// Check if email is valid
			if (!filter_var($_POST['e'], FILTER_VALIDATE_EMAIL)) {
				throw new Exception("Email isn't valid.");
			}
			// Check if username is valid
			if (!preg_match('/^[A-Za-z0-9 _\\-\\[\\]]{2,15}$/i', $_POST['u'])) {
				throw new Exception("Username is not valid! It must be from 2 to 15 characters long, " .
									"and can only contain alphanumeric chararacters, spaces, and these " .
									"characters: <code>_-[]</code>");
			}
			// Make sure username is not forbidden
			if (UsernameHelper::isUsernameForbidden($_POST['u'])) {
				throw new Exception('Username now allowed. Please choose another one.');
			}
			// Username with mixed spaces
			if (strpos($_POST["u"], " ") !== false && strpos($_POST["u"], "_") !== false) {
				throw new Exception('Usernames with both spaces and underscores are not supported.');
			}
			// Check if username is already in db
			$safe = safeUsername($_POST["u"]);
			if ($GLOBALS['db']->fetch('SELECT * FROM users WHERE username_safe = ?', [$safe])) {
				throw new Exception('That username was already found in the database! Perhaps someone stole it from you? Those bastards!');
			}
			// Check if email is already in db
			if ($GLOBALS['db']->fetch('SELECT * FROM users WHERE email = ?', $_POST['e'])) {
				throw new Exception('An user with that email already exists!');
			}
			// Check captcha
			if (!isset($_POST["g-recaptcha-response"])) {
				throw new Exception("Invalid captcha");
			}
			$data = [
				"secret" => $reCaptchaConfig["secret_key"],
				"response" => $_POST["g-recaptcha-response"]
 			];
			if ($reCaptchaConfig["ip"]) {
				$data[] = [
					"ip" => $ip
				];
			}
			$reCaptchaResponse = postJsonCurl("https://www.google.com/recaptcha/api/siteverify", $data, $timeout = 10);
			if (!$reCaptchaResponse["success"]) {
				throw new Exception("Invalid captcha");
			}
			// Multiacc notice if needed
			$multiIP = multiaccCheckIP($ip);
			$multiToken = multiaccCheckToken();
			if ($multiIP !== FALSE || $multiToken !== FALSE) {
				if ($multiIP !== FALSE) {
					$multiUserInfo = $multiIP;
					$criteria = "IP **($ip)**";
				} else {
					$multiUserInfo = $multiToken;
					$criteria = "Multiaccount token (IP is **$ip**)";
				}
				$multiUsername = $multiUserInfo["username"];
				$multiUserID = $multiUserInfo["userid"];
				@Schiavo::CM("User **$_POST[u]** registered from same $criteria as **$multiUsername** (https://ripple.moe/?u=$multiUserID). **POSSIBLE MULTIACCOUNT!!!**. Waiting for ingame verification...");
			}
			// Create password
			$md5Password = password_hash(md5($_POST['p1']), PASSWORD_DEFAULT);
			// Put some data into the db
			$GLOBALS['db']->execute("INSERT INTO `users`(username, username_safe, password_md5, salt, email, register_datetime, privileges, password_version)
			                                     VALUES (?,        ?,             ?,            '',   ?,     ?,                 ?, 2);",
												 		[$_POST['u'], $safe,      $md5Password,       $_POST['e'], time(true), Privileges::UserPendingVerification]);
			// Get user ID
			$uid = $GLOBALS['db']->lastInsertId();
			// Put some data into users_stats
			// TODO: Move this query above to avoid mysql thread conflict memes
			$GLOBALS['db']->execute("INSERT INTO `users_stats`(id, username, user_color, user_style, ranked_score_std, playcount_std, total_score_std, ranked_score_taiko, playcount_taiko, total_score_taiko, ranked_score_ctb, playcount_ctb, total_score_ctb, ranked_score_mania, playcount_mania, total_score_mania) VALUES (?, ?, 'black', '', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0);", [$uid, $_POST['u']]);
			// Update leaderboard (insert new user) for each mode.
			foreach (['std', 'taiko', 'ctb', 'mania'] as $m) {
				Leaderboard::Update($uid, 0, $m);
			}
			@Schiavo::CM("User (**$_POST[u]** | $_POST[e]) registered (successfully) from **" . $ip . "**");
			// Generate and set identity token ("y" cookie)
			setYCookie($uid);
			// log user ip
			logIP($uid);
			//addSuccess("You should now be signed up! Try to <a href='index.php?p=2'>login</a>.");
			// All fine, done
			redirect('index.php?p=38&u='.$uid);
		} catch(Exception $e) {
			// Redirect to Exception page
			addError($e->getMessage());
			redirect('index.php?p=3&iseethestopsign=1');
		}
	}

	/*
	 * ChangePassword
	 * Change password function
	*/
	public static function ChangePassword() {
		try {
			// Check if we are logged in
			sessionCheck();
			// Check if everything is set
			if (empty($_POST['pold']) || empty($_POST['p1']) || empty($_POST['p2'])) {
				throw new Exception('Nope.');
			}
			$pres = PasswordHelper::ValidatePassword($_POST['p1'], $_POST['p2']);
			if ($pres !== -1) {
				throw new Exception($pres);
			}
			if (!PasswordHelper::CheckPass($_SESSION['username'], $_POST['pold'], false)) {
				throw new Exception('Your old password is incorrect.');
			}
			// Calculate new password
			$newPassword = password_hash(md5($_POST['p1']), PASSWORD_DEFAULT);
			// Change both passwords and salt
			$GLOBALS['db']->execute("UPDATE users SET password_md5 = ?, password_version = 2, salt = '' WHERE username = ?", [$newPassword, $_SESSION['username']]);
			// Set in session that we've changed our password otherwise sessionCheck() will kick us
			$_SESSION['passwordChanged'] = true;
			// Redirect to success page
			addSuccess('Password changed!');
			redirect('index.php?p=7');
		}
		catch(Exception $e) {
			addError($e->getMessage());
			// Redirect to Exception page
			redirect('index.php?p=7');
		}
	}

	/*
	 * RecoverPassword()
	 * Form submission for printPasswordRecovery.
	*/
	public static function RecoverPassword() {
		global $MailgunConfig;
		try {
			if (!isset($_POST['username']) || empty($_POST['username'])) {
				throw new Exception(0);
			}
			$username = $_POST['username'];
			$user = $GLOBALS['db']->fetch('SELECT id, username, email FROM users WHERE username = ?', [$username]);
			// Check the user actually exists.
			if (!$user) {
				throw new Exception(1);
			}
			if (!hasPrivilege(Privileges::UserNormal, $user["id"]) && !hasPrivilege(Privileges::UserPendingVerification, $user["id"])) {
				throw new Exception(2);
			}
			$key = randomString(80);
			$GLOBALS['db']->execute('INSERT INTO password_recovery (k, u) VALUES (?, ?);', [$key, $username]);
			$mailer = new SimpleMailgun($MailgunConfig);
			$mailer->Send('Ripple <noreply@'.$MailgunConfig['domain'].'>', $user['email'], 'Ripple password recovery instructions', sprintf("Hey %s! Someone, which we really hope was you, requested a password reset for your account. In case it was you, please <a href='%s'>click here</a> to reset your password on Ripple. Otherwise, silently ignore this email.", $username, 'http://'.$_SERVER['HTTP_HOST'].'/index.php?p=19&k='.$key.'&user='.$username));
			redirect('index.php?p=18&s=sent');
		}
		catch(Exception $e) {
			redirect('index.php?p=18&e='.$e->getMessage());
		}
	}

	/*
	 * SaveSystemSettings
	 * Save system settings function (ADMIN CP)
	*/
	public static function SaveSystemSettings() {
		try {
			// Get values
			if (isset($_POST['wm'])) {
				$wm = $_POST['wm'];
			} else {
				$wm = 0;
			}
			if (isset($_POST['gm'])) {
				$gm = $_POST['gm'];
			} else {
				$gm = 0;
			}
			if (isset($_POST['r'])) {
				$r = $_POST['r'];
			} else {
				$r = 0;
			}
			if (!empty($_POST['ga'])) {
				$ga = $_POST['ga'];
			} else {
				$ga = '';
			}
			if (!empty($_POST['ha'])) {
				$ha = $_POST['ha'];
			} else {
				$ha = '';
			}
			// Save new values
			$GLOBALS['db']->execute("UPDATE system_settings SET value_int = ? WHERE name = 'website_maintenance' LIMIT 1", [$wm]);
			$GLOBALS['db']->execute("UPDATE system_settings SET value_int = ? WHERE name = 'game_maintenance' LIMIT 1", [$gm]);
			$GLOBALS['db']->execute("UPDATE system_settings SET value_int = ? WHERE name = 'registrations_enabled' LIMIT 1", [$r]);
			$GLOBALS['db']->execute("UPDATE system_settings SET value_string = ? WHERE name = 'website_global_alert' LIMIT 1", [$ga]);
			$GLOBALS['db']->execute("UPDATE system_settings SET value_string = ? WHERE name = 'website_home_alert' LIMIT 1", [$ha]);
			foreach (["std" , "taiko", "ctb", "mania"] as $key) {
				if (!isset($_POST["aql_$key"]) || !is_numeric($_POST["aql_$key"])) {
					continue;
				}
				$GLOBALS['db']->execute("UPDATE system_settings SET value_string = ? WHERE name = 'aql_threshold_" . $key . "' LIMIT 1", [$_POST["aql_$key"]]);
			}
			redisConnect();
			$GLOBALS["redis"]->publish("lets:reload_aql", "reload");
			
			// RAP log
			rapLog("has updated system settings");
			// Done, redirect to success page
			redirect('index.php?p=101&s=Settings saved!');
		}
		catch(Exception $e) {
			// Redirect to Exception page
			redirect('index.php?p=101&e='.$e->getMessage());
		}
	}

	/*
	 * SaveBanchoSettings
	 * Save bancho settings function (ADMIN CP)
	*/
	public static function SaveBanchoSettings() {
		try {
			// Get values
			if (isset($_POST['bm'])) {
				$bm = $_POST['bm'];
			} else {
				$bm = 0;
			}
			if (isset($_POST['od'])) {
				$od = $_POST['od'];
			} else {
				$od = 0;
			}
			if (isset($_POST['rm'])) {
				$rm = $_POST['rm'];
			} else {
				$rm = 0;
			}
			if (!empty($_POST['lm'])) {
				$lm = $_POST['lm'];
			} else {
				$lm = '';
			}
			if (!empty($_POST['ln'])) {
				$ln = $_POST['ln'];
			} else {
				$ln = '';
			}
			if (!empty($_POST['cv'])) {
				$cv = $_POST['cv'];
			} else {
				$cv = '';
			}
			if (!empty($_POST['cmd5'])) {
				$cmd5 = $_POST['cmd5'];
			} else {
				$cmd5 = '';
			}
			// Save new values
			$GLOBALS['db']->execute("UPDATE bancho_settings SET value_int = ? WHERE name = 'bancho_maintenance' LIMIT 1", [$bm]);
			$GLOBALS['db']->execute("UPDATE bancho_settings SET value_int = ? WHERE name = 'free_direct' LIMIT 1", [$od]);
			$GLOBALS['db']->execute("UPDATE bancho_settings SET value_int = ? WHERE name = 'restricted_joke' LIMIT 1", [$rm]);
			$GLOBALS['db']->execute("UPDATE bancho_settings SET value_string = ? WHERE name = 'login_messages' LIMIT 1", [$lm]);
			$GLOBALS['db']->execute("UPDATE bancho_settings SET value_string = ? WHERE name = 'login_notification' LIMIT 1", [$ln]);
			$GLOBALS['db']->execute("UPDATE bancho_settings SET value_string = ? WHERE name = 'osu_versions' LIMIT 1", [$cv]);
			$GLOBALS['db']->execute("UPDATE bancho_settings SET value_string = ? WHERE name = 'osu_md5s' LIMIT 1", [$cmd5]);
			// Pubsub
			redisConnect();
			$GLOBALS["redis"]->publish("peppy:reload_settings", "reload");
			// Rap log
			rapLog("has updated bancho settings");
			// Done, redirect to success page
			redirect('index.php?p=111&s=Settings saved!');
		}
		catch(Exception $e) {
			// Redirect to Exception page
			redirect('index.php?p=111&e='.$e->getMessage());
		}
	}

	/*
	 * RunCron
	 * Runs cron.php from admin cp with exec/redirect
	*/
	public static function RunCron() {
		if ($CRON['adminExec']) {
			// howl master linux shell pr0
			exec(PHP_BIN_DIR.'/php '.dirname(__FILE__).'/../cron.php 2>&1 > /dev/null &');
		} else {
			// Run from browser
			redirect('./cron.php');
		}
	}

	/*
	 * SaveEditUser
	 * Save edit user function (ADMIN CP)
	*/
	public static function SaveEditUser() {
		try {
			// Check if everything is set (username color, username style, rank, allowed and notes can be empty)
			if (!isset($_POST['id']) || !isset($_POST['u']) || !isset($_POST['e']) || !isset($_POST['up']) || !isset($_POST['aka']) || empty($_POST['id']) || empty($_POST['u']) || empty($_POST['e'])) {
				throw new Exception('Nice troll');
			}
			// Check if this user exists and get old data
			$oldData = $GLOBALS["db"]->fetch("SELECT * FROM users LEFT JOIN users_stats ON users.id = users_stats.id WHERE users.id = ? LIMIT 1", [$_POST["id"]]);
			if (!$oldData) {
				throw new Exception("That user doesn\'t exist");
			}
			// Check if we can edit this user
			if ( (($oldData["privileges"] & Privileges::AdminManageUsers) > 0) && $_POST['u'] != $_SESSION['username']) {
				throw new Exception("You don't have enough permissions to edit this user");
			}
			// Check if email is valid
			if (!filter_var($_POST['e'], FILTER_VALIDATE_EMAIL)) {
				throw new Exception("The email isn't valid");
			}


			// Check if silence end has changed. if so, we have to kick the client
			// in order to silence him
			//$oldse = current($GLOBALS["db"]->fetch("SELECT silence_end FROM users WHERE username = ?", array($_POST["u"])));

			// Save new data (email, and cm notes)
			$GLOBALS['db']->execute('UPDATE users SET email = ?, notes = ? WHERE id = ? LIMIT 1', [$_POST['e'], $_POST['ncm'], $_POST['id'] ]);
			// Edit silence time if we can silence users
			if (hasPrivilege(Privileges::AdminSilenceUsers)) {
				$GLOBALS['db']->execute('UPDATE users SET silence_end = ?, silence_reason = ? WHERE id = ? LIMIT 1', [$_POST['se'], $_POST['sr'], $_POST['id'] ]);
			}
			// Edit privileges if we can
			if (hasPrivilege(Privileges::AdminManagePrivileges) && ($_POST["id"] != $_SESSION["userid"])) {
				$GLOBALS['db']->execute('UPDATE users SET privileges = ? WHERE id = ? LIMIT 1', [$_POST['priv'], $_POST['id']]);
				updateBanBancho($_POST["id"]);
			}
			// Save new userpage
			$GLOBALS['db']->execute('UPDATE users_stats SET userpage_content = ? WHERE id = ? LIMIT 1', [$_POST['up'], $_POST['id']]);
			/* Save new data if set (rank, allowed, UP and silence)
			if (isset($_POST['r']) && !empty($_POST['r']) && $oldData["rank"] != $_POST["r"]) {
				$GLOBALS['db']->execute('UPDATE users SET rank = ? WHERE id = ?', [$_POST['r'], $_POST['id']]);
				rapLog(sprintf("has changed %s's rank to %s", $_POST["u"], readableRank($_POST['r'])));
			}
			if (isset($_POST['a'])) {
				$banDateTime = $_POST['a'] == 0 ? time() : 0;
				$newPrivileges = $oldData["privileges"] ^ Privileges::UserBasic;
				$GLOBALS['db']->execute('UPDATE users SET privileges = ?, ban_datetime = ? WHERE id = ?', [$newPrivileges, $banDateTime, $_POST['id']]);
			}*/
			// Get username style/color
			if (isset($_POST['c']) && !empty($_POST['c'])) {
				$c = $_POST['c'];
			} else {
				$c = 'black';
			}
			if (isset($_POST['bg']) && !empty($_POST['bg'])) {
				$bg = $_POST['bg'];
			} else {
				$bg = '';
			}
			// Update country flag if set
			if (isset($_POST['country']) && countryCodeToReadable($_POST['country']) != 'unknown country' && $oldData["country"] != $_POST['country']) {
				$GLOBALS['db']->execute('UPDATE users_stats SET country = ? WHERE id = ? LIMIT 1', [$_POST['country'], $_POST['id']]);
				rapLog(sprintf("has changed %s's flag to %s", $_POST["u"], $_POST['country']));
			}
			// Set username style/color/aka
			$GLOBALS['db']->execute('UPDATE users_stats SET user_color = ?, user_style = ?, username_aka = ? WHERE id = ? LIMIT 1', [$c, $bg, $_POST['aka'], $_POST['id']]);
			// RAP log
			rapLog(sprintf("has edited user %s", $_POST["u"]));
			// Done, redirect to success page
			redirect('index.php?p=102&s=User edited!');
		}
		catch(Exception $e) {
			// Redirect to Exception page
			redirect('index.php?p=102&e='.$e->getMessage());
		}
	}

	/*
	 * BanUnbanUser
	 * Ban/Unban user function (ADMIN CP)
	*/
	public static function BanUnbanUser() {
		try {
			// Check if everything is set
			if (empty($_GET['id'])) {
				throw new Exception('Nice troll.');
			}
			// Get user's username
			$userData = $GLOBALS['db']->fetch('SELECT username, privileges FROM users WHERE id = ? LIMIT 1', $_GET['id']);
			if (!$userData) {
				throw new Exception("User doesn't exist");
			}
			// Check if we can ban this user
			if ( ($userData["privileges"] & Privileges::AdminManageUsers) > 0) {
				throw new Exception("You don't have enough permissions to ban this user");
			}
			// Get new allowed value
			if ( ($userData["privileges"] & Privileges::UserNormal) > 0) {
				// Ban, reset UserNormal and UserPublic bits
				$banDateTime = time();
				$newPrivileges = $userData["privileges"] & ~Privileges::UserNormal;
				$newPrivileges &= ~Privileges::UserPublic;
				removeFromLeaderboard($_GET['id']);
			} else {
				// Unban, set UserNormal and UserPublic bits
				$banDateTime = 0;
				$newPrivileges = $userData["privileges"] | Privileges::UserNormal;
				$newPrivileges |= Privileges::UserPublic;
			}
			//$newPrivileges = $userData["privileges"] ^ Privileges::UserBasic;
			// Change privileges
			$GLOBALS['db']->execute('UPDATE users SET privileges = ?, ban_datetime = ? WHERE id = ? LIMIT 1', [$newPrivileges, $banDateTime, $_GET['id']]);
			updateBanBancho($_GET["id"]);
			// Rap log
			rapLog(sprintf("has %s user %s", ($newPrivileges & Privileges::UserNormal) > 0 ? "unbanned" : "banned", $userData["username"]));
			// Done, redirect to success page
			redirect('index.php?p=102&s=User banned/unbanned/activated!');
		}
		catch(Exception $e) {
			// Redirect to Exception page
			redirect('index.php?p=102&e='.$e->getMessage());
		}
	}

	/*
	 * QuickEditUser
	 * Redirects to the edit user page for the user with $_POST["u"] username
	*/
	public static function QuickEditUser($email = false) {
		try {
			// Check if everything is set
			if (empty($_POST['u'])) {
				throw new Exception('Nice troll.');
			}
			// Get user id
			$id = current($GLOBALS['db']->fetch(sprintf('SELECT id FROM users WHERE %s = ? LIMIT 1', $email ? 'email' : 'username'), [$_POST['u']]));
			// Check if that user exists
			if (!$id) {
				throw new Exception("That user doesn't exist");
			}
			// Done, redirect to edit page
			redirect('index.php?p=103&id='.$id);
		}
		catch(Exception $e) {
			// Redirect to Exception page
			redirect('index.php?p=102&e='.$e->getMessage());
		}
	}

	/*
	 * QuickEditUserBadges
	 * Redirects to the edit user badges page for the user with $_POST["u"] username
	*/
	public static function QuickEditUserBadges() {
		try {
			// Check if everything is set
			if (empty($_POST['u'])) {
				throw new Exception('Nice troll.');
			}
			// Get user id
			$id = current($GLOBALS['db']->fetch('SELECT id FROM users WHERE username = ? LIMIT 1', $_POST['u']));
			// Check if that user exists
			if (!$id) {
				throw new Exception("That user doesn't exist");
			}
			// Done, redirect to edit page
			redirect('index.php?p=110&id='.$id);
		}
		catch(Exception $e) {
			// Redirect to Exception page
			redirect('index.php?p=108&e='.$e->getMessage());
		}
	}

	/*
	 * ChangeIdentity
	 * Change identity function (ADMIN CP)
	*/
	public static function ChangeIdentity() {
		try {
			// Check if everything is set
			if (!isset($_POST['id']) || !isset($_POST['oldu']) || !isset($_POST['newu']) || empty($_POST['id']) || empty($_POST['oldu']) || empty($_POST['newu'])) {
				throw new Exception('Nice troll.');
			}
			// Check if we can edit this user
			$privileges = $GLOBALS["db"]->fetch("SELECT privileges FROM users WHERE id = ? LIMIT 1", [$_POST["id"]]);
			if (!$privileges) {
				throw new Exception("User doesn't exist");
			}
			$privileges = current($privileges);
			if ( (($privileges & Privileges::AdminManageUsers) > 0) && $_POST['oldu'] != $_SESSION['username']) {
				throw new Exception("You don't have enough permissions to edit this user");
			}
			// No username with mixed spaces
			if (strpos($_POST["newu"], " ") !== false && strpos($_POST["newu"], "_") !== false) {
				throw new Exception('Usernames with both spaces and underscores are not supported.');
			}
			// Check if username is already in db
			$safe = safeUsername($_POST["newu"]);
			if ($GLOBALS['db']->fetch('SELECT * FROM users WHERE username_safe = ? AND id != ? LIMIT 1', [$safe, $_POST["id"]])) {
				throw new Exception('Username already used by another user. No changes have been made.');
			}
			redisConnect();
			$GLOBALS["redis"]->publish("peppy:change_username", json_encode([
				"userID" => intval($_POST["id"]),
				"newUsername" => $_POST["newu"]
			]));
			// rap log
			rapLog(sprintf("has changed %s's username to %s", $_POST["oldu"], $_POST["newu"]));
			// Done, redirect to success page
			redirect('index.php?p=102&s=User identity changed! It might take a while to change the username if the user is online on Bancho.');
		}
		catch(Exception $e) {
			// Redirect to Exception page
			redirect('index.php?p=102&e='.$e->getMessage());
		}
	}

	/*
	 * SaveBadge
	 * Save badge function (ADMIN CP)
	*/
	public static function SaveBadge() {
		try {
			// Check if everything is set
			if (!isset($_POST['id']) || !isset($_POST['n']) || !isset($_POST['i']) || empty($_POST['n']) || empty($_POST['i'])) {
				throw new Exception('Nice troll.');
			}
			// Check if we are creating or editing a doc page
			if ($_POST['id'] == 0) {
				$GLOBALS['db']->execute('INSERT INTO badges (id, name, icon) VALUES (NULL, ?, ?)', [$_POST['n'], $_POST['i']]);
			} else {
				$GLOBALS['db']->execute('UPDATE badges SET name = ?, icon = ? WHERE id = ? LIMIT 1', [$_POST['n'], $_POST['i'], $_POST['id']]);
			}
			// RAP log
			rapLog(sprintf("has %s badge %s", $_POST['id'] == 0 ? "created" : "edited", $_POST["n"]));
			// Done, redirect to success page
			redirect('index.php?p=108&s=Badge edited!');
		}
		catch(Exception $e) {
			// Redirect to Exception page
			redirect('index.php?p=108&e='.$e->getMessage());
		}
	}

	/*
	 * SaveUserBadges
	 * Save user badges function (ADMIN CP)
	*/
	public static function SaveUserBadges() {
		try {
			// Check if everything is set
			if (!isset($_POST['u']) || !isset($_POST['b01']) || !isset($_POST['b02']) || !isset($_POST['b03']) || !isset($_POST['b04']) || !isset($_POST['b05']) || !isset($_POST['b06']) || empty($_POST['u'])) {
				throw new Exception('Nice troll.');
			}
			$user = $GLOBALS['db']->fetch('SELECT id FROM users WHERE username = ?', $_POST['u']);
			// Make sure that this user exists
			if (!$user) {
				throw new Exception("That user doesn't exist.");
			}
			// delete current badges
			$GLOBALS["db"]->execute("DELETE FROM user_badges WHERE user = ?", [$user["id"]]);
			// add badges
			for ($i = 0; $i <= 6; $i++) {
				$x = $_POST["b0" . $i];
				if ($x == 0) continue;
				$GLOBALS["db"]->execute("INSERT INTO user_badges(user, badge) VALUES (?, ?);", [$user["id"], $x]);
			}
			// RAP log
			rapLog(sprintf("has edited %s's badges", $_POST["u"]));
			// Done, redirect to success page
			redirect('index.php?p=108&s=Badge edited!');
		}
		catch(Exception $e) {
			// Redirect to Exception page
			redirect('index.php?p=108&e='.$e->getMessage());
		}
	}

	/*
	 * RemoveBadge
	 * Remove badge function (ADMIN CP)
	*/
	public static function RemoveBadge() {
		try {
			// Make sure that this is not the "None badge"
			if (empty($_GET['id'])) {
				throw new Exception("You can't delete this badge.");
			}
			// Make sure that this badge exists
			$name = $GLOBALS['db']->fetch('SELECT name FROM badges WHERE id = ? LIMIT 1', $_GET['id']);
			// Badge doesn't exists wtf
			if (!$name) {
				throw new Exception("This badge doesn't exists");
			}
			// Delete badge
			$GLOBALS['db']->execute('DELETE FROM badges WHERE id = ? LIMIT 1', $_GET['id']);
			// delete badge from relationships table
			$GLOBALS['db']->execute('DELETE FROM user_badges WHERE badge = ?', $_GET['id']);
			// RAP log
			rapLog(sprintf("has deleted badge %s", current($name)));
			// Done, redirect to success page
			redirect('index.php?p=108&s=Badge deleted!');
		}
		catch(Exception $e) {
			// Redirect to Exception page
			redirect('index.php?p=108&e='.$e->getMessage());
		}
	}

	/*
	 * SilenceUser
	 * Silence someone (ADMIN CP)
	*/
	public static function silenceUser() {
		try {
			// Check if everything is set
			if (!isset($_POST['u']) || !isset($_POST['c']) || !isset($_POST['un']) || !isset($_POST['r']) || !isset($_POST["r"]) || empty($_POST['u']) || empty($_POST['un']) || empty($_POST["r"])) {
				throw new Exception('Invalid request');
			}
			// Get user id
			$id = getUserID($_POST["u"]);
			// Check if that user exists
			if (!$id) {
				throw new Exception("That user doesn't exist");
			}
			// Calculate silence period length
			$sl = $_POST['c'] * $_POST['un'];
			// Make sure silence time is less than 7 days
			if ($sl > 604800) {
				throw new Exception('Invalid silence length. Maximum silence length is 7 days.');
			}
			// Silence and reconnect that user
			$GLOBALS["db"]->execute("UPDATE users SET silence_end = ?, silence_reason = ? WHERE id = ? LIMIT 1", [time() + $sl, $_POST["r"], $id]);
			updateSilenceBancho($id);
			// RAP log and redirect
			if ($sl > 0) {
				rapLog(sprintf("has silenced user %s for %s for the following reason: \"%s\"", $_POST['u'], timeDifference(time() + $sl, time(), false), $_POST["r"]));
				$msg = 'index.php?p=102&s=User silenced!';
			} else {
				rapLog(sprintf("has removed %s's silence", $_POST['u']));
				$msg = 'index.php?p=102&s=User silence removed!';
			}
			if (isset($_POST["resend"])) {
				redirect(stripSuccessError($_SERVER["HTTP_REFERER"]) . '&s='.$msg);
			} else {
				redirect('index.php?p=102&s='.$msg);
			}
		}
		catch(Exception $e) {
			// Redirect to Exception page
			if (isset($_POST["resend"])) {
				redirect(stripSuccessError($_SERVER["HTTP_REFERER"]) . '&e='.$e->getMessage());
			} else {
				redirect('index.php?p=102&e='.$e->getMessage());
			}
		}
	}

	/*
	 * KickUser
	 * Kick someone from bancho (ADMIN CP)
	*/
	public static function KickUser() {
		try {
			// Check if everything is set
			if (!isset($_POST['u']) || empty($_POST['u']) || !isset($_POST["r"]) || empty($_POST["r"])) {
				throw new Exception('Invalid request');
			}
			// Get user id
			$id = current($GLOBALS['db']->fetch('SELECT id FROM users WHERE username = ? LIMIT 1', $_POST['u']));
			// Check if that user exists
			if (!$id) {
				throw new Exception("That user doesn't exist");
			}
			// Kick that user
			redisConnect();
			$GLOBALS["redis"]->publish("peppy:disconnect", json_encode([
				"userID" => intval($id),
				"reason" => $_POST["r"]
			]));
			// Done, redirect to success page
			redirect('index.php?p=102&s=User kicked!');
		}
		catch(Exception $e) {
			// Redirect to Exception page
			redirect('index.php?p=102&e='.$e->getMessage());
		}
	}

	/*
	 * ResetAvatar
	 * Reset soneone's avatar (ADMIN CP)
	*/
	public static function ResetAvatar() {
		try {
			// Check if everything is set
			if (!isset($_GET['id']) || empty($_GET['id'])) {
				throw new Exception('Invalid request');
			}
			// Get user id
			$avatar = dirname(dirname(dirname(__FILE__))).'/avatars/'.$_GET['id'].'.png';
			if (!file_exists($avatar)) {
				throw new Exception("That user doesn't have an avatar");
			}
			// Delete user avatar
			unlink($avatar);
			// Rap log
			rapLog(sprintf("has reset %s's avatar", getUserUsername($_GET['id'])));
			// Done, redirect to success page
			redirect('index.php?p=102&s=Avatar reset!');
		}
		catch(Exception $e) {
			// Redirect to Exception page
			redirect('index.php?p=102&e='.$e->getMessage());
		}
	}

	/*
	 * Logout
	 * Logout and return to home
	*/
	public static function Logout() {
		// Logging out without being logged in doesn't make much sense
		if (checkLoggedIn()) {
			startSessionIfNotStarted();
			if (isset($_COOKIE['sli'])) {
				$rch = new RememberCookieHandler();
				$rch->Destroy();
			}
			$_SESSION = [];
			session_unset();
			session_destroy();
		} else {
			// Uhm, some kind of error/h4xx0r. Let's return to login page just because yes.
			redirect('index.php?p=2');
		}
	}

	/*
	 * ForgetEveryCookie
	 * Allows the user to delete every field in the remember database table with their username, so that it is logged out of every computer they were logged in.
	*/
	public static function ForgetEveryCookie() {
		startSessionIfNotStarted();
		$rch = new RememberCookieHandler();
		$rch->DestroyAll($_SESSION['userid']);
		redirect('index.php?p=1&s=forgetDone');
	}

	/*
	 * saveUserSettings
	 * Save user settings functions
	*/
	public static function saveUserSettings() {
		global $PlayStyleEnum;
		try {
			function valid($value, $min=0, $max=1) {
				return ($value >= $min && $value <= $max);
			}

			// Check if we are logged in
			sessionCheck();
			// Restricted check
			if (isRestricted()) {
				throw new Exception(1);
			}
			// Check everything is set
			if (!isset($_POST['c']) || !isset($_POST['aka']) || !isset($_POST['st']) || !isset($_POST['mode'])) {
				throw new Exception(0);
			}
			// Make sure values are valid
			if (!valid($_POST['mode'], 0, 3) || !valid($_POST['st']) || (isset($_POST["showCustomBadge"]) && !valid($_POST["showCustomBadge"]))) {
				throw new Exception(0);
			}
			// Check if username color is not empty and if so, set to black (default)
			if (empty($_POST['c']) || !preg_match('/^#[a-f0-9]{6}$/i', $_POST['c'])) {
				$c = 'black';
			} else {
				$c = $_POST['c'];
			}
			// Playmode stuff
			$pm = 0;
			foreach ($_POST as $key => $value) {
				$i = str_replace('_', ' ', substr($key, 3));
				if ($value == 1 && substr($key, 0, 3) == 'ps_' && isset($PlayStyleEnum[$i])) {
					$pm += $PlayStyleEnum[$i];
				}
			}
			// Save custom badge
			$canCustomBadge = current($GLOBALS["db"]->fetch("SELECT can_custom_badge FROM users_stats WHERE id = ? LIMIT 1", [$_SESSION["userid"]])) == 1;
			if (hasPrivilege(Privileges::UserDonor) && $canCustomBadge && isset($_POST["showCustomBadge"]) && isset($_POST["badgeName"]) && isset($_POST["badgeIcon"])) {
				// Script kiddie check 1
				$forbiddenNames = ["BAT", "Developer", "Community Manager"];
				if (in_array($_POST["badgeName"], $forbiddenNames)) {
					throw new Fava(0);
				}

				$oldCustomBadge = $GLOBALS["db"]->fetch("SELECT custom_badge_name AS name, custom_badge_icon AS icon FROM users_stats WHERE id = ? LIMIT 1", [$_SESSION["userid"]]);
				if ($oldCustomBadge["name"] != $_POST["badgeName"] || $oldCustomBadge["icon"] != $_POST["badgeIcon"]) {
					@Schiavo::CM("User **$_SESSION[username]** has changed his custom badge to **$_POST[badgeName]** *($_POST[badgeIcon])*");
				}

				// Script kiddie check 2
				// (is this even needed...?)
				$forbiddenClasses = ["fa-lg", "fa-2x", "fa-3x", "fa-4x", "fa-5x", "fa-ul", "fa-li", "fa-border", "fa-pull-right", "fa-pull-left", "fa-stack", "fa-stack-2x", "fa-stack-1x"];
				$icon = explode(" ", $_POST["badgeIcon"]);
				for ($i=0; $i < count($icon); $i++) {
					if (substr($icon[$i], 0, 3) != "fa-" || in_array($icon[$i], $forbiddenClasses)) {
						$icon[$i] = "";
					}
				}
				$icon = implode(" ", $icon);
				$GLOBALS["db"]->execute("UPDATE users_stats SET show_custom_badge = ?, custom_badge_name = ?, custom_badge_icon = ? WHERE id = ? LIMIT 1", [$_POST["showCustomBadge"], $_POST["badgeName"], $icon, $_SESSION["userid"]]);
			}
			// Save data in db
			$GLOBALS['db']->execute('UPDATE users_stats SET user_color = ?, username_aka = ?, safe_title = ?, play_style = ?, favourite_mode = ? WHERE id = ? LIMIT 1', [$c, $_POST['aka'], $_POST['st'], $pm, $_POST['mode'], $_SESSION['userid']]);
			// Update safe title cookie
			updateSafeTitle();
			// Done, redirect to success page
			redirect('index.php?p=6&s=ok');
		}
		catch(Exception $e) {
			// Redirect to Exception page
			redirect('index.php?p=6&e='.$e->getMessage());
		}
	}

	/*
	 * SaveUserpage
	 * Save userpage functions
	*/
	public static function SaveUserpage() {
		try {
			// Check if we are logged in
			sessionCheck();
			// Restricted check
			if (isRestricted()) {
				throw new Exception(2);
			}
			// Check if everything is set
			if (!isset($_POST['c'])) {
				throw new Exception(0);
			}
			// Check userpage length
			if (strlen($_POST['c']) > 1500) {
				throw new Exception(1);
			}
			// Save data in db
			$GLOBALS['db']->execute('UPDATE users_stats SET userpage_content = ? WHERE username = ?', [$_POST['c'], $_SESSION['username']]);
			if (isset($_POST['view']) && $_POST['view'] == 1) {
				redirect('index.php?u=' . $_SESSION['userid']);
			}
			// Done, redirect to success page
			redirect('index.php?p=8&s=ok');
		}
		catch(Exception $e) {
			// Redirect to Exception page
			redirect('index.php?p=8&e='.$e->getMessage().$r);
		}
	}

	/*
	 * ChangeAvatar
	 * Chhange avatar functions
	*/
	public static function ChangeAvatar() {
		try {
			// Check if we are logged in
			sessionCheck();
			// Restricted check
			if (isRestricted()) {
				throw new Exception(5);
			}
			// Check if everything is set
			if (!isset($_FILES['file'])) {
				throw new Exception(0);
			}
			// Check if image file is a actual image or fake image
			if (!getimagesize($_FILES['file']['tmp_name'])) {
				throw new Exception(1);
			}
			// Allow certain file formats
			$allowedFormats = ['jpg', 'jpeg', 'png'];
			if (!in_array(pathinfo($_FILES['file']['name']) ['extension'], $allowedFormats)) {
				throw new Exception(2);
			}
			// Check file size
			if ($_FILES['file']['size'] > 1000000) {
				throw new Exception(3);
			}
			// Resize
			if (!smart_resize_image($_FILES['file']['tmp_name'], null, 100, 100, false, dirname(dirname(dirname(__FILE__))).'/avatarserver/avatars/'.getUserID($_SESSION['username']).'.png', false, false, 100)) {
				throw new Exception(4);
			}
			/* "Convert" to png
												if (!move_uploaded_file($_FILES["file"]["tmp_name"], dirname(dirname(dirname(__FILE__)))."/avatarserver/avatars/".getUserID($_SESSION["username"]).".png")) {
												    throw new Exception(4);
												}*/
			// Done, redirect to success page
			redirect('index.php?p=5&s=ok');
		}
		catch(Exception $e) {
			// Redirect to Exception page
			redirect('index.php?p=5&e='.$e->getMessage());
		}
	}

	/*
	 * WipeAccount
	 * Wipes an account
	*/
	public static function WipeAccount() {
		try {
			if (!isset($_POST['id']) || empty($_POST['id'])) {
				throw new Exception('Invalid request');
			}
			$userData = $GLOBALS["db"]->fetch("SELECT username, privileges FROM users WHERE id = ? LIMIT 1", [$_POST["id"]]);
			if (!$userData) {
				throw new Exception('User doesn\'t exist.');
			}
			$username = $userData["username"];
			// Check if we can wipe this user
			if ( ($userData["privileges"] & Privileges::AdminManageUsers) > 0) {
				throw new Exception("You don't have enough permissions to wipe this account");
			}

			if ($_POST["gm"] == -1) {
				// All modes
				$modes = ['std', 'taiko', 'ctb', 'mania'];
			} else {
				// 1 mode
				if ($_POST["gm"] == 0) {
					$modes = ['std'];
				} else if ($_POST["gm"] == 1) {
					$modes = ['taiko'];
				} else if ($_POST["gm"] == 2) {
					$modes = ['ctb'];
				} else if ($_POST["gm"] == 3) {
					$modes = ['mania'];
				}
			}

			// Delete scores
			if ($_POST["gm"] == -1) {
				$GLOBALS['db']->execute('INSERT INTO scores_removed SELECT * FROM scores WHERE userid = ?', [$_POST['id']]);
				$GLOBALS['db']->execute('DELETE FROM scores WHERE userid = ?', [$_POST['id']]);
			} else {
				$GLOBALS['db']->execute('INSERT INTO scores_removed SELECT * FROM scores WHERE userid = ? AND play_mode = ?', [$_POST['id'], $_POST["gm"]]);
				$GLOBALS['db']->execute('DELETE FROM scores WHERE userid = ? AND play_mode = ?', [$_POST['id'], $_POST["gm"]]);
			}
			// Reset mode stats
			foreach ($modes as $k) {
				$GLOBALS['db']->execute('UPDATE users_stats SET ranked_score_'.$k.' = 0, total_score_'.$k.' = 0, replays_watched_'.$k.' = 0, playcount_'.$k.' = 0, avg_accuracy_'.$k.' = 0.0, total_hits_'.$k.' = 0, level_'.$k.' = 0, pp_'.$k.' = 0 WHERE id = ? LIMIT 1', [$_POST['id']]);
			}

			// RAP log
			rapLog(sprintf("has wiped %s's account", $username));

			// Done
			redirect('index.php?p=102&s=User scores and stats have been wiped!');
		}
		catch(Exception $e) {
			redirect('index.php?p=102&e='.$e->getMessage());
		}
	}

	/*
	 * AddRemoveFriend
	 * Add remove friends
	*/
	public static function AddRemoveFriend() {
		try {
			// Check if we are logged in
			sessionCheck();
			// Check if everything is set
			if (!isset($_GET['u']) || empty($_GET['u'])) {
				throw new Exception(0);
			}
			// Get our user id
			$uid = getUserID($_SESSION['username']);
			// Add/remove friend
			if (getFriendship($uid, $_GET['u'], true) == 0) {
				addFriend($uid, $_GET['u'], true);
			} else {
				removeFriend($uid, $_GET['u'], true);
			}
			// Done, redirect
			redirect('index.php?u='.$_GET['u']);
		}
		catch(Exception $e) {
			redirect('index.php?p=99&e='.$e->getMessage());
		}
	}
	
	/*
	 * ProcessRankRequest
	 * Rank/unrank a beatmap
	*/
	public static function ProcessRankRequest() {
		global $URL;
		global $ScoresConfig;
		try {
			if (!isset($_GET["id"]) || !isset($_GET["r"]) || empty($_GET["id"]))
				throw new Exception("no");

			// Get beatmapset id
			$requestData = $GLOBALS["db"]->fetch("SELECT * FROM rank_requests WHERE id = ? LIMIT 1", [$_GET["id"]]);
			if (!$requestData)
				throw new Exception("Rank request not found");

			if ($requestData["type"] == "s") {
				// We already have the beatmapset id
				$bsid = $requestData["bid"];
			} else {
				// We have the beatmap but we don't have the beatmap set id.
				$result = $GLOBALS["db"]->fetch("SELECT beatmapset_id FROM beatmaps WHERE beatmap_id = ? LIMIT 1", [$requestData["bid"]]);
				if (!$result)
					throw new Exception("Beatmap set id not found. Load the beatmap ingame and try again.");
				$bsid = current($result);
			}

			// TODO: Save all beatmaps from a set in db with a given beatmap set id

			if ($_GET["r"] == 0) {
				// Unrank the map set and force osu!api update by setting latest update to 01/01/1970 top stampa piede
				$GLOBALS["db"]->execute("UPDATE beatmaps SET ranked = 0, ranked_status_freezed = 0, latest_update = 0 WHERE beatmapset_id = ?", [$bsid]);
			} else {
				// Rank the map set and freeze status rank
				$GLOBALS["db"]->execute("UPDATE beatmaps SET ranked = 2, ranked_status_freezed = 1 WHERE beatmapset_id = ?", [$bsid]);

				// send a message to #announce
				$bm = $GLOBALS["db"]->fetch("SELECT beatmapset_id, song_name FROM beatmaps WHERE beatmapset_id = ? LIMIT 1", [$bsid]);

				$msg = "[https://osu.ppy.sh/s/" . $bsid . " " . $bm["song_name"] . "] is now ranked!";
				$to = "#announce";
				$requesturl = $URL["bancho"] . "/api/v1/fokabotMessage?k=" . urlencode($ScoresConfig["api_key"]) . "&to=" . urlencode($to) . "&msg=" . urlencode($msg);
				$resp = getJsonCurl($requesturl);

				if ($resp["message"] != "ok") {
					rapLog("failed to send FokaBot message :( err: " . print_r($resp["message"], true));
				}
			}

			// RAP log
			rapLog(sprintf("has %s beatmap set %s", $_GET["r"] == 0 ? "unranked" : "ranked", $bsid), $_SESSION["userid"]);

			// Done
			redirect("index.php?p=117&s=野生のちんちんが現れる");
		}
		catch(Exception $e) {
			redirect("index.php?p=117&e=".$e->getMessage());
		}
	}


	/*
	 * BlacklistRankRequest
	 * Toggle blacklist for a rank request
	*/
	public static function BlacklistRankRequest() {
		try {
			if (!isset($_GET["id"]) || empty($_GET["id"]))
				throw new Exception("no");
			$GLOBALS["db"]->execute("UPDATE rank_requests SET blacklisted = IF(blacklisted=1, 0, 1) WHERE id = ? LIMIT 1", [$_GET["id"]]);
			$reqData = $GLOBALS["db"]->fetch("SELECT type, bid FROM rank_requests WHERE id = ? LIMIT 1", [$_GET["id"]]);
			rapLog(sprintf("has toggled blacklist flag on beatmap %s %s", $reqData["type"] == "s" ? "set" : "", $reqData["bid"]), $_SESSION["userid"]);
			redirect("index.php?p=117&s=Blacklisted flag changed");
		}
		catch(Exception $e) {
			redirect('index.php?p=117&e='.$e->getMessage());
		}
	}

	public static function savePrivilegeGroup() {
		try {
			// Args check
			if (!isset($_POST["id"]) || !isset($_POST["n"]) || !isset($_POST["priv"]) || !isset($_POST["c"]))
				throw new Exception("DON'T YOU TRYYYY!!");

			if ($_POST["id"] == 0) {
				// New group
				// Make sure name is unique
				$other = $GLOBALS["db"]->fetch("SELECT id FROM privileges_groups WHERE name = ?", [$_POST["n"]]);
				if ($other) {
					throw new Exception("There's another group with the same name");
				}

				// Insert new group
				$GLOBALS["db"]->execute("INSERT INTO privileges_groups (id, name, privileges, color) VALUES (NULL, ?, ?, ?)", [$_POST["n"], $_POST["priv"], $_POST["c"]]);
			} else {
				// Get old privileges and make sure group exists
				$oldPriv = $GLOBALS["db"]->fetch("SELECT privileges FROM privileges_groups WHERE id = ? LIMIT 1", [$_POST["id"]]);
				if (!$oldPriv) {
					throw new Exception("That privilege group doesn't exist");
				}
				$oldPriv = current($oldPriv);
				// Update existing group
				$GLOBALS["db"]->execute("UPDATE privileges_groups SET name = ?, privileges = ?, color = ? WHERE id = ? LIMIT 1", [$_POST["n"], $_POST["priv"], $_POST["c"], $_POST["id"]]);
				// Get users in this group
				// I genuinely want to kill myself right now.
				$users = $GLOBALS["db"]->fetchAll("SELECT id FROM users WHERE privileges = ".$oldPriv." OR privileges = ".$oldPriv." | ".Privileges::UserDonor);
				foreach ($users as $user) {
					// Remove privileges from previous group
					$GLOBALS["db"]->execute("UPDATE users SET privileges = privileges & ~".$oldPriv." WHERE id = ? LIMIT 1", [$user["id"]]);
					// Add privileges from new group
					$GLOBALS["db"]->execute("UPDATE users SET privileges = privileges | ".$_POST["priv"]." WHERE id = ? LIMIT 1", [$user["id"]]);
				}
			}

			// Fin.
			redirect("index.php?p=118&s=Saved!");
		} catch (Exception $e) {
			// There's a memino divertentino
			redirect("index.php?p=118&e=".$e->getMessage());
		}
	}


	/*
	 * RestrictUnrestrictUser
	 * restricte/unrestrict user function (ADMIN CP)
	*/
	public static function RestrictUnrestrictUser() {
		try {
			// Check if everything is set
			if (empty($_GET['id'])) {
				throw new Exception('Nice troll.');
			}
			// Get user's username
			$userData = $GLOBALS['db']->fetch('SELECT username, privileges FROM users WHERE id = ? LIMIT 1', $_GET['id']);
			if (!$userData) {
				throw new Exception("User doesn't exist");
			}
			// Check if we can ban this user
			if ( ($userData["privileges"] & Privileges::AdminManageUsers) > 0) {
				throw new Exception("You don't have enough permissions to ban this user");
			}
			// Get new allowed value
			if (!isRestricted($_GET["id"])) {
				// Restrict, set UserNormal and reset UserPublic
				$banDateTime = time();
				$newPrivileges = $userData["privileges"] | Privileges::UserNormal;
				$newPrivileges &= ~Privileges::UserPublic;
				removeFromLeaderboard($_GET['id']);
			} else {
				// Remove restrictions, set both UserPublic and UserNormal
				$banDateTime = 0;
				$newPrivileges = $userData["privileges"] | Privileges::UserNormal;
				$newPrivileges |= Privileges::UserPublic;
			}
			// Change privileges
			$GLOBALS['db']->execute('UPDATE users SET privileges = ?, ban_datetime = ? WHERE id = ? LIMIT 1', [$newPrivileges, $banDateTime, $_GET['id']]);
			updateBanBancho($_GET["id"]);
			// Rap log
			rapLog(sprintf("has %s user %s", ($newPrivileges & Privileges::UserPublic) > 0 ? "removed restrictions on" : "restricted", $userData["username"]));
			// Done, redirect to success page
			if (isset($_GET["resend"])) {
				redirect(stripSuccessError($_SERVER["HTTP_REFERER"]) . '&s=User restricted/unrestricted!');
			} else {
				redirect('index.php?p=102&s=User restricted/unrestricted!');
			}
		}
		catch(Exception $e) {
			// Redirect to Exception page
			if (isset($_GET["resend"])) {
				redirect(stripSuccessError($_SERVER["HTTP_REFERER"]) . '&e='.$e->getMessage());
			} else {
				redirect('index.php?p=102&e='.$e->getMessage());
			}
		}
	}

	public static function GiveDonor() {
		try {
			if (!isset($_POST["id"]) || empty($_POST["id"]) || !isset($_POST["m"]) || empty($_POST["m"]))
				throw new Exception("Invalid user");
			$months = giveDonor($_POST["id"], $_POST["m"], $_POST["type"] == 0);
			rapLog(sprintf("has given donor for %s months to user %s", $_POST["m"], $username), $_SESSION["userid"]);
			redirect("index.php?p=102&s=Donor status changed. Donor for that user now expires in ".$months." months!");
		}
		catch(Exception $e) {
			redirect('index.php?p=102&e='.$e->getMessage());
		}
	}

	public static function RemoveDonor() {
		try {
			if (!isset($_GET["id"]) || empty($_GET["id"]))
				throw new Exception("Invalid user");
			$username = $GLOBALS["db"]->fetch("SELECT username FROM users WHERE id = ?", [$_GET["id"]]);
			if (!$username) {
				throw new Exception("That user doesn't exist");
			}
			$username = current($username);
			$GLOBALS["db"]->execute("UPDATE users SET privileges = privileges & ~".Privileges::UserDonor.", donor_expire = 0 WHERE id = ? LIMIT 1", [$_GET["id"]]);

			// Remove donor badge
			// 14 = donor badge id
			$GLOBALS["db"]->execute("DELETE FROM user_badges WHERE user = ? AND badge = ?", [$_GET["id"], 14]);

			rapLog(sprintf("has removed donor from user %s", $username), $_SESSION["userid"]);
			redirect("index.php?p=102&s=Donor status changed!");
		}
		catch(Exception $e) {
			redirect('index.php?p=102&e='.$e->getMessage());
		}
	}

	public static function Rollback() {
		try {
			if (!isset($_POST["id"]) || empty($_POST["id"]))
				throw new Exception("Invalid user");
			$userData = $GLOBALS["db"]->fetch("SELECT username, privileges FROM users WHERE id = ? LIMIT 1", [$_POST["id"]]);
			if (!$userData) {
				throw new Exception("That user doesn't exist");
			}
			$username = $userData["username"];
			// Check if we can rollback this user
			if ( ($userData["privileges"] & Privileges::AdminManageUsers) > 0) {
				throw new Exception("You don't have enough permissions to rollback this account");
			}
			switch ($_POST["period"]) {
				case "d": $periodSeconds = 86400; $periodName = "Day"; break;
				case "w": $periodSeconds = 86400*7; $periodName = "Week"; break;
				case "m": $periodSeconds = 86400*30; $periodName = "Month"; break;
				case "y": $periodSeconds = 86400*365; $periodName = "Year"; break;
			}

			//$removeAfterOsuTime = UNIXTimestampToOsuDate(time()-($_POST["length"]*$periodSeconds));
			$removeAfter = time()-($_POST["length"]*$periodSeconds);
			$rollbackString = $_POST["length"]." ".$periodName;
			if ($_POST["length"] > 1) {
				$rollbackString .= "s";
			}

			$GLOBALS["db"]->execute("INSERT INTO scores_removed SELECT * FROM scores WHERE userid = ? AND time >= ?", [$_POST["id"], $removeAfter]);
			$GLOBALS["db"]->execute("DELETE FROM scores WHERE userid = ? AND time >= ?", [$_POST["id"], $removeAfter]);

			rapLog(sprintf("has rolled back %s %s's account", $rollbackString, $username), $_SESSION["userid"]);
			redirect("index.php?p=102&s=User account has been rolled back!");
		} catch(Exception $e) {
			redirect('index.php?p=102&e='.$e->getMessage());
		}
	}

	public static function ToggleCustomBadge() {
		try {
			if (!isset($_GET["id"]) || empty($_GET["id"]))
				throw new Exception("Invalid user");
			$userData = $GLOBALS["db"]->fetch("SELECT username, privileges FROM users WHERE id = ? LIMIT 1", [$_GET["id"]]);
			if (!$userData) {
				throw new Exception("That user doesn't exist");
			}
			$username = $userData["username"];
			// Check if we can edit this user
			if ( ($userData["privileges"] & Privileges::AdminManageUsers) > 0) {
				throw new Exception("You don't have enough permissions to grant/revoke custom badge privilege on this account");
			}

			// Grant/revoke custom badge privilege
			$can = current($GLOBALS["db"]->fetch("SELECT can_custom_badge FROM users_stats WHERE id = ? LIMIT 1", [$_GET["id"]]));
			$grantRevoke = ($can == 0) ? "granted" : "revoked";
			$can = !$can;
			$GLOBALS["db"]->execute("UPDATE users_stats SET can_custom_badge = ? WHERE id = ? LIMIT 1", [$can, $_GET["id"]]);

			rapLog(sprintf("has %s custom badge privilege on %s's account", $grantRevoke, $username), $_SESSION["userid"]);
			redirect("index.php?p=102&s=Custom badge privilege revoked/granted!");
		} catch(Exception $e) {
			redirect('index.php?p=102&e='.$e->getMessage());
		}
	}


	public static function lockUnlockUser() {
		try {
			if (!isset($_GET["id"]) || empty($_GET["id"]))
				throw new Exception("Invalid user");
			$userData = $GLOBALS["db"]->fetch("SELECT id, privileges, username FROM users WHERE id = ? LIMIT 1", [$_GET["id"]]);
			if (!$userData) {
				throw new Exception("That user doesn't exist");
			}
			// Check if we can edit this user
			if ( ($userData["privileges"] & Privileges::AdminManageUsers) > 0) {
				throw new Exception("You don't have enough permissions to lock this account");
			}
			// Make sure the user is not banned/restricted
			if (!hasPrivilege(Privileges::UserPublic, $_GET["id"])) {
				throw new Exception("The user is banned or restricted. You can't lock an account if it's banned or restricted. Only normal accounts can be locked.");
			}

			// Grant/revoke custom badge privilege
			$lockUnlock = (hasPrivilege(Privileges::UserNormal, $_GET["id"])) ? "locked" : "unlocked";
			$GLOBALS["db"]->execute("UPDATE users SET privileges = privileges ^ 2 WHERE id = ? LIMIT 1", [$_GET["id"]]);
			rapLog(sprintf("has %s %s's account", $grantRevoke, $userData["username"]), $_SESSION["userid"]);
			redirect("index.php?p=102&s=User locked/unlocked!");
		} catch(Exception $e) {
			redirect('index.php?p=102&e='.$e->getMessage());
		}
	}

	public static function RankBeatmapNew() {
		global $URL;
		global $ScoresConfig;
		try {
			if (!isset($_POST["beatmaps"])) {
				throw new Exception("Invalid form data");
			}

			$bsid = -1;
			$result = "";
			$updateCache = false;

			// Do stuff for each beatmap
			foreach ($_POST["beatmaps"] as $beatmapID => $status) {
				$logToRap = true;

				// Get beatmap set id if not set yet
				if ($bsid == -1) {
					$bsid = $GLOBALS["db"]->fetch("SELECT beatmapset_id FROM beatmaps WHERE beatmap_id = ? LIMIT 1", [$beatmapID]);
					if (!$bsid) {
						throw new Exception("Beatmap set not found! Please load one diff from this set ingame and try again.");
					}
					$bsid = current($bsid);
				}

				// Change beatmap status
				switch ($status) {
					// Rank beatmap
					case "rank":
						$GLOBALS["db"]->execute("UPDATE beatmaps SET ranked = 2, ranked_status_freezed = 1 WHERE beatmap_id = ? LIMIT 1", [$beatmapID]);

						// Restore old scores
						$GLOBALS["db"]->execute("UPDATE scores s JOIN (SELECT userid, MAX(score) maxscore FROM scores JOIN beatmaps ON scores.beatmap_md5 = beatmaps.beatmap_md5 WHERE beatmaps.beatmap_md5 = (SELECT beatmap_md5 FROM beatmaps WHERE beatmap_id = ? LIMIT 1) GROUP BY userid) s2 ON s.score = s2.maxscore AND s.userid = s2.userid SET completed = 3", [$beatmapID]);
						$result .= "$beatmapID has been ranked and its scores have been restored. | ";
					break;

					// Force osu!api update (unfreeze)
					case "update":
						$updateCache = true;
						$GLOBALS["db"]->execute("UPDATE beatmaps SET ranked = 0, ranked_status_freezed = 0 WHERE beatmap_id = ? LIMIT 1", [$beatmapID]);
						$result .= "$beatmapID's ranked status is the same from official osu!. | ";
					break;

					// No changes
					case "no":
						$logToRap = false;
						$result .= "$beatmapID's ranked status has not been edited!. | ";
					break;

					// EH! VOLEVI!
					default:
						throw new Exception("Unknown ranked status value.");
					break;
				}

				// RAP Log
				if ($logToRap)
					rapLog(sprintf("has %s beatmap set %s", $status == "rank" ? "ranked" : "unranked", $bsid), $_SESSION["userid"]);
			}

			// Update beatmap set from osu!api if
			// at least one diff has been unfrozen
			global $URL;
			if ($updateCache) {
				post_content_http($URL["scores"]."/api/v1/cacheBeatmap", [
					"sid" => $bsid,
					"refresh" => 1
				], 30);
			}

			// Send a message to #announce
			$bm = $GLOBALS["db"]->fetch("SELECT beatmapset_id, song_name FROM beatmaps WHERE beatmapset_id = ? LIMIT 1", [$bsid]);
			$msg = "[https://osu.ppy.sh/s/" . $bsid . " " . $bm["song_name"] . "] is now ranked!";
			$to = "#announce";
			$requesturl = $URL["bancho"] . "/api/v1/fokabotMessage?k=" . urlencode($ScoresConfig["api_key"]) . "&to=" . urlencode($to) . "&msg=" . urlencode($msg);
			$resp = getJsonCurl($requesturl);
			if ($resp["message"] != "ok") {
				rapLog("failed to send FokaBot message :( err: " . print_r($resp["message"], true));
			}

			// Done
			redirect("index.php?p=117&s=".$result);
		} catch (Exception $e) {
			redirect('index.php?p=117&e='.$e->getMessage());
		}
	}

	public static function RedirectRankBeatmap() {
		try {
			if (!isset($_POST["id"]) || empty($_POST["id"]) || !isset($_POST["type"]) || empty($_POST["type"])) {
				throw new Exception("Invalid beatmap id or type");
			}
			if ($_POST["type"] == "bsid") {
				$bsid = htmlspecialchars($_POST["id"]);
			} else {
				$bsid = $GLOBALS["db"]->fetch("SELECT beatmapset_id FROM beatmaps WHERE beatmap_id = ? LIMIT 1", [$_POST["id"]]);
				if (!$bsid) {
					throw new Exception("Beatmap set not found in ripple's database. Please use beatmap set id or load at least one difficulty in game before trying to rank a beatmap by its id.");
				}
				$bsid = current($bsid);
			}
			redirect("index.php?p=124&bsid=".$bsid);
		} catch (Exception $e) {
			redirect('index.php?p=125&e='.$e->getMessage());
		}
	}

	public static function ClearHWIDMatches() {
		try {
			if (!isset($_GET["id"]) || empty($_GET["id"])) {
				throw new Exception("Invalid user ID");
			}
			$GLOBALS["db"]->execute("DELETE FROM hw_user WHERE userid = ?", [$_GET["id"]]);
			rapLog(sprintf("has cleared %s's HWID matches.", getUserUsername($_GET["id"])));
			redirect('index.php?p=102&s=HWID matches cleared! Make sure to clear multiaccounts\' HWID too, or the user might get restricted for multiaccounting!');
		} catch (Exception $e) {
			redirect('index.php?p=102&e='.$e->getMessage());
		}
	}

	public static function TakeReport() {
		try {
			if (!isset($_GET["id"]) || empty($_GET["id"])) {
				throw new Exception("Missing report id");
			}
			$status = $GLOBALS["db"]->fetch("SELECT assigned FROM reports WHERE id = ? LIMIT 1", [$_GET["id"]]);
			if (!$status) {
				throw new Exception("Invalid report id");
			}
			if ($status["assigned"] < 0) {
				throw new Exception("This report is closed");
			} else if ($status["assigned"] == $_SESSION["userid"]) {
				// Unassign
				$GLOBALS["db"]->execute("UPDATE reports SET assigned = 0 WHERE id = ? LIMIT 1", [$_GET["id"]]);
			} else {
				// Assign to current user
				$GLOBALS["db"]->execute("UPDATE reports SET assigned = ? WHERE id = ? LIMIT 1", [$_SESSION["userid"], $_GET["id"]]);
			}
			redirect("index.php?p=127&id=" . $_GET["id"] . "&s=Assignee changed!");
		} catch (Exception $e) {
			redirect("index.php?p=127&id=" . $_GET["id"] . "&e=" . $e->getMessage());
		}
	}

	public static function SolveUnsolveReport() {
		try {
			if (!isset($_GET["id"]) || empty($_GET["id"])) {
				throw new Exception("Missing report id");
			}
			$status = $GLOBALS["db"]->fetch("SELECT assigned FROM reports WHERE id = ? LIMIT 1", [$_GET["id"]]);
			if (!$status) {
				throw new Exception("Invalid report id");
			}
			if ($status["assigned"] < 0 && $status["assigned"] != -1) {
				throw new Exception("This report is closed or it's marked as useless");
			}
			if ($status["assigned"] == -1) {
				// Unsolve
				$GLOBALS["db"]->execute("UPDATE reports SET assigned = 0 WHERE id = ? LIMIT 1", [$_GET["id"]]);
			} else {
				// Solve
				$GLOBALS["db"]->execute("UPDATE reports SET assigned = -1 WHERE id = ? LIMIT 1", [$_GET["id"]]);
			}
			redirect("index.php?p=127&id=" . $_GET["id"] . "&s=Solved status changed!");
		} catch (Exception $e) {
			redirect("index.php?p=127&id=" . $_GET["id"] . "&e=" . $e->getMessage());
		}
	}

	public static function UselessUsefulReport() {
		try {
			if (!isset($_GET["id"]) || empty($_GET["id"])) {
				throw new Exception("Missing report id");
			}
			$status = $GLOBALS["db"]->fetch("SELECT assigned FROM reports WHERE id = ? LIMIT 1", [$_GET["id"]]);
			if (!$status) {
				throw new Exception("Invalid report id");
			}
			if ($status["assigned"] < 0 && $status["assigned"] != -2) {
				throw new Exception("This report is closed");
			}
			if ($status["assigned"] == -2) {
				// Useful (open)
				$GLOBALS["db"]->execute("UPDATE reports SET assigned = 0 WHERE id = ? LIMIT 1", [$_GET["id"]]);
			} else {
				// Useless
				$GLOBALS["db"]->execute("UPDATE reports SET assigned = -2 WHERE id = ? LIMIT 1", [$_GET["id"]]);
			}
			redirect("index.php?p=127&id=" . $_GET["id"] . "&s=Useful status changed!");
		} catch (Exception $e) {
			redirect("index.php?p=127&id=" . $_GET["id"] . "&e=" . $e->getMessage());
		}
	}

	public static function RestoreScoresSearchUser() {
		try {
			if (!isset($_POST["username"]) || empty($_POST["username"])) {
				throw new Exception("Missing username");
			}
			$userID = getUserID($_POST["username"]);
			if (!$userID) {
				throw new Exception("No such user");
			}
			redirect("index.php?p=134&id=" . $userID);
		} catch (Exception $e) {
			redirect("index.php?p=134&e=" . $e->getMessage());
		}
	}

	public static function RestoreScores() {
		try {
			if (!isset($_POST["userid"]) || empty($_POST["userid"]) || !isset($_POST["gm"]) || empty($_POST["gm"])) {
				throw new Exception("Missing required parameters");
			}

			$q = "SELECT * FROM scores_removed WHERE userid = ?";
			$qp = [$_POST["userid"]];
			if ($_POST["gm"] > -1 && $_POST["gm"] <= 3) {
				$q .= " AND play_mode = ?";
				array_push($qp, $_POST["gm"]);
			}
			if (isset($_POST["startdate"]) && !empty($_POST["startdate"])) {
				$h = isset($_POST["starttime"]) && !empty($_POST["starttime"]) ? $_POST["starttime"] : "00:00";
				$startts = getTimestampFromStr("$_POST[startdate] $h");
				$q .= " AND time >= ?";
				array_push($qp, $startts);
			}
			if (isset($_POST["enddate"])  && !empty($_POST["enddate"])) {
				$h = isset($_POST["endtime"]) && !empty($_POST["endtime"]) ? $_POST["endtime"] : "00:00";
				$endts = getTimestampFromStr("$_POST[enddate] $h");
				$q .= " AND time <= ?";
				array_push($qp, $endts);
			}

			$scoresToRecover = $GLOBALS["db"]->fetchAll($q, $qp);
			foreach ($scoresToRecover as $lostScore) {
				$restore = false;
				if ($lostScore["completed"] == 3) {
					// Restore completed 3 scores only if they havent been replaced by better scores
					$betterScore = $GLOBALS["db"]->fetch("SELECT id FROM scores WHERE userid = ? AND play_mode = ? AND beatmap_md5 = ? AND completed = 3 AND pp > ? LIMIT 1", [
						$lostScore["userid"],
						$lostScore["play_mode"],
						$lostScore["beatmap_md5"],
						$lostScore["pp"]
					]);
					$restore = !$betterScore;
				} else {
					// Restore all completed < 3 scores
					$restore = true;
				}
				if (!$restore) {
					echo "$lostScore[id] has a better score, not restoring<br>";
					continue;
				}
				$GLOBALS["db"]->execute("INSERT INTO scores SELECT * FROM scores_removed WHERE id = ? LIMIT 1", [$lostScore["id"]]);
				$GLOBALS["db"]->execute("DELETE FROM scores_removed WHERE id = ? LIMIT 1", [$lostScore["id"]]);
				echo "Restored $lostScore[id]<br>";
			}

			// redirect(index.php?p=134&id=" . $userID);
		} catch (Exception $e) {
			redirect("index.php?p=134&e=" . $e->getMessage());
		}
	}

	public static function UploadMainMenuIcon() {
		try {
			if (!isset($_POST["name"]) || empty($_POST["name"]) || !isset($_POST["url"]) || empty($_POST["url"])) {
				throw new Exception("Missing required parameter(s).");
			}
			if (!isset($_FILES["file"]) || empty($_FILES["file"]) || $_FILES["file"]["error"] != 0) {
				throw new Exception("Nothing uploaded");
			}
			$path = "main_menu_icons";
			$verifyImg = getimagesize($_FILES["file"]["tmp_name"]);
			if ($verifyImg["mime"] !== "image/png") {
				throw new Exception("Only png images are allowed");
			}
			$fileName = randomFileName($path, ".png");
			$finalFilePath = $path . "/" . $fileName . ".png";
			if (!move_uploaded_file($_FILES["file"]["tmp_name"], $finalFilePath)) {
				throw new Exception("File upload failed. Check server's permissions.");
			}
			$defaultCount = current($GLOBALS["db"]->fetch("SELECT COUNT(*) FROM main_menu_icons WHERE is_default = 1"));
			$GLOBALS["db"]->execute("INSERT INTO main_menu_icons (name, file_id, url, is_default) VALUES (?, ?, ?, ?)", [$_POST["name"], $fileName, $_POST["url"], (int)($defaultCount == 0)]);
			$msg = "Main menu icon uploaded successfully";
			$msg .= $defaultCount == 0 ? " and set as default image." : "!";
			redirect("index.php?p=111&s=" . $msg);
		} catch (Exception $e) {
			redirect("index.php?p=111&e=" . $e->getMessage());
		}
	}

	public static function DeleteMainMenuIcon() {
		try {
			if (!isset($_GET["id"]) || empty($_GET["id"])) {
				throw new Exception("Missing required parameter");
			}
			$icon = $GLOBALS["db"]->fetch("SELECT file_id FROM main_menu_icons WHERE id = ? LIMIT 1", [$_GET["id"]]);
			if (!$icon) {
				throw new Exception("The icon doesn't exist.");
			}
			unlink("main_menu_icons/" . $icon["file_id"] . ".png");
			$GLOBALS["db"]->execute("DELETE FROM main_menu_icons WHERE id = ? LIMIT 1", [$_GET["id"]]);
			updateMainMenuIconBancho();
			redirect("index.php?p=111&s=Main menu icon deleted successfully!");
		} catch (Exception $e) {
			redirect("index.php?p=111&e=" . $e->getMessage());
		}
	}

	public static function SetDefaultMainMenuIcon() {
		try {
			if (!isset($_GET["id"]) || empty($_GET["id"])) {
				throw new Exception("Missing required parameter");
			}
			$GLOBALS["db"]->execute("UPDATE main_menu_icons SET is_default = IF(id = ?, 1, 0)", [$_GET["id"]]);
			redirect("index.php?p=111&s=Default main menu icon set successfully!");
		} catch (Exception $e) {
			redirect("index.php?p=111&e=" . $e->getMessage());
		}
	}

	public static function SetMainMenuIcon() {
		try {
			if (!isset($_GET["id"]) || empty($_GET["id"])) {
				throw new Exception("Missing required parameter");
			}
			$GLOBALS["db"]->execute("UPDATE main_menu_icons SET is_current = IF(id = ?, 1, 0)", [$_GET["id"]]);
			updateMainMenuIconBancho();
			redirect("index.php?p=111&s=Main menu icon set successfully!");
		} catch (Exception $e) {
			redirect("index.php?p=111&e=" . $e->getMessage());
		}
	}

	public static function TestMainMenuIcon() {
		try {
			if (!isset($_GET["id"]) || empty($_GET["id"])) {
				throw new Exception("Missing required parameter");
			}
			$devs = $GLOBALS["db"]->fetchAll("SELECT id FROM users WHERE privileges & " . Privileges::AdminManageServers . " > 0");
			foreach ($devs as $row) {
				testMainMenuIconBancho($row["id"], $_GET["id"]);
			}
			redirect("index.php?p=111&s=Main menu icon sent to all online developers!");
		} catch (Exception $e) {
			redirect("index.php?p=111&e=" . $e->getMessage());
		}
	}

	public static function RestoreMainMenuIcon() {
		try {
			$GLOBALS["db"]->execute("UPDATE main_menu_icons SET is_current = IF((SELECT id FROM (SELECT * FROM main_menu_icons) AS x WHERE x.is_default = 1 AND x.id = main_menu_icons.id LIMIT 1), 1, 0)", [$_GET["id"]]);
			updateMainMenuIconBancho();
			redirect("index.php?p=111&s=Main menu icon restored successfully!");
		} catch (Exception $e) {
			redirect("index.php?p=111&e=" . $e->getMessage());
		}
	}

	public static function RemoveMainMenuIcon() {
		try {
			$GLOBALS["db"]->execute("UPDATE main_menu_icons SET is_current = 0", [$_GET["id"]]);
			updateMainMenuIconBancho();
			redirect("index.php?p=111&s=Main menu icon removed successfully!");
		} catch (Exception $e) {
			redirect("index.php?p=111&e=" . $e->getMessage());
		}
	}

	public static function BulkBan() {
		try {
			if (!isset($_POST["uid"]) || empty($_POST["uid"])) {
				throw new Exception("No user ids provided.");
			}
			$result = "";
			$errors = "";
			foreach ($_POST["uid"] as $uid) {
				$uid = (int)$uid;
				$user = $GLOBALS["db"]->fetch("SELECT privileges, username FROM users WHERE id = ? LIMIT 1", [$uid]);
				if (!$user) {
					$errors .= "$uid doesn't exist | ";
					continue;
				}
				if (($user["privileges"] & Privileges::AdminManageUsers) > 0) {
					$errors .= "No privileges to ban $uid. | ";
					continue;
				}
				$GLOBALS["db"]->execute("UPDATE users SET privileges = (privileges & ~3) WHERE id = ? LIMIT 1", [$uid]);
				if (isset($_POST["notes"]) && !empty($_POST["notes"])) {
					appendNotes($uid, $_POST["notes"]);
				}
				$result .= "$uid OK! | ";
				$result = trim($result, " | ");
				$errors = trim($errors, " | ");
				updateBanBancho($uid);
				rapLog(sprintf("has banned user %s", $user["username"]));
			}
			redirect("index.php?p=102&e=" . $errors . "&s=" . $result);
		} catch (Exception $e) {
			redirect("index.php?p=102&e=" . $e->getMessage());
		}
	}

	public static function Remove2FA() {
		try {
			if (!isset($_GET["id"]) || empty($_GET["id"])) {
				throw new Exception("No user ids provided.");
			}
			if (!has2Fa($_GET["id"])) {
				throw new Exception("2FA is not enabled on the specified account");
			}
			$user = $GLOBALS["db"]->fetch("SELECT privileges, username FROM users WHERE id = ? LIMIT 1", [$_GET["id"]]);
			if (($user["privileges"] & Privileges::AdminManageUsers) > 0) {
				throw new Exception("You don't have enough privileges to edit the specified user");
			}
			$GLOBALS["db"]->execute("DELETE FROM 2fa_totp WHERE userid = ? LIMIT 1", [$_GET["id"]]);
			rapLog(sprintf("has disabled 2FA on user %s", $user["username"]));
			redirect("index.php?p=102&s=2FA disabled!");
		} catch (Exception $e) {
			redirect("index.php?p=102&e=" . $e->getMessage());
		}
	}
}
