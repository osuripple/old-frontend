<?php

class P {
	/*
	 * AdminDashboard
	 * Prints the admin panel dashborad page
	*/
	public static function AdminDashboard() {
		// Get admin dashboard data
		$totalScores = number_format(current($GLOBALS['db']->fetch('SELECT COUNT(*) FROM scores LIMIT 1')));
		$betaKeysLeft = "∞";
		/*$totalPPQuery = $GLOBALS['db']->fetch("SELECT SUM(pp) FROM scores WHERE completed = 3 LIMIT 1");
		$totalPP = 0;
		foreach ($totalPPQuery as $pp) {
			$totalPP += $pp;
		}
		$totalPP = number_format($totalPP);*/
		$totalPP = "🍆";
		$recentPlays = $GLOBALS['db']->fetchAll('
		SELECT
			beatmaps.song_name, scores.beatmap_md5, users.username,
			scores.userid, scores.time, scores.score, scores.pp,
			scores.play_mode, scores.mods
		FROM scores
		LEFT JOIN beatmaps ON beatmaps.beatmap_md5 = scores.beatmap_md5
		LEFT JOIN users ON users.id = scores.userid
		ORDER BY scores.id DESC
		LIMIT 10');
		$topPlays = [];
		/*$topPlays = $GLOBALS['db']->fetchAll('SELECT
			beatmaps.song_name, scores.beatmap_md5, users.username,
			scores.userid, scores.time, scores.score, scores.pp,
			scores.play_mode, scores.mods
		FROM scores
		LEFT JOIN beatmaps ON beatmaps.beatmap_md5 = scores.beatmap_md5
		LEFT JOIN users ON users.id = scores.userid
		WHERE users.privileges & 1 > 0
		ORDER BY scores.pp DESC LIMIT 30');*/
		$onlineUsers = getJsonCurl("http://127.0.0.1:5001/api/v1/onlineUsers");
		if ($onlineUsers == false) {
			$onlineUsers = 0;
		} else {
			$onlineUsers = $onlineUsers["result"];
		}
		// Print admin dashboard
		echo '<div id="wrapper">';
		printAdminSidebar();
		echo '<div id="page-content-wrapper">';
		// Maintenance check
		self::MaintenanceStuff();
		// Global alert
		self::GlobalAlert();
		// Stats panels
		echo '<div class="row">';
		printAdminPanel('primary', 'fa fa-gamepad fa-5x', $totalScores, 'Total scores');
		printAdminPanel('green', 'fa fa-user fa-5x', $onlineUsers, 'Online users');
		printAdminPanel('red', 'fa fa-gift fa-5x', $betaKeysLeft, 'Beta keys left');
		printAdminPanel('yellow', 'fa fa-dot-circle-o fa-5x', $totalPP, 'Total PP');
		echo '</div>';
		// Recent plays table
		echo '<table class="table table-striped table-hover">
		<thead>
		<tr><th class="text-left"><i class="fa fa-clock-o"></i>	Recent plays</th><th>Beatmap</th></th><th>Mode</th><th>Sent</th><th>Score</th><th class="text-right">PP</th></tr>
		</thead>
		<tbody>';
		foreach ($recentPlays as $play) {
			// set $bn to song name by default. If empty or null, replace with the beatmap md5.
			$bn = $play['song_name'];
			// Check if this beatmap has a name cached, if yes show it, otherwise show its md5
			if (!$bn) {
				$bn = $play['beatmap_md5'];
			}
			// Get readable play_mode
			$pm = getPlaymodeText($play['play_mode']);
			// Print row
			echo '<tr class="success">';
			echo '<td><p class="text-left"><b><a href="index.php?u='.$play["username"].'">'.$play['username'].'</a></b></p></td>';
			echo '<td><p class="text-left">'.$bn.' <b>' . getScoreMods($play['mods']) . '</b></p></td>';
			echo '<td><p class="text-left">'.$pm.'</p></td>';
			echo '<td><p class="text-left">'.timeDifference(time(), $play['time']).'</p></td>';
			echo '<td><p class="text-left">'.number_format($play['score']).'</p></td>';
			echo '<td><p class="text-right"><b>'.number_format($play['pp']).'pp</b></p></td>';
			echo '</tr>';
		}
		echo '</tbody>';
		// Top plays table
		echo '<table class="table table-striped table-hover">
		<thead>
		<tr><th class="text-left"><i class="fa fa-trophy"></i>	Top plays</th><th>Beatmap</th></th><th>Mode</th><th>Sent</th><th class="text-right">PP</th></tr>
		</thead>
		<tbody>';
		echo '<tr class="danger"><td colspan=5>Disabled</td></tr>';
		foreach ($topPlays as $play) {
			// set $bn to song name by default. If empty or null, replace with the beatmap md5.
			$bn = $play['song_name'];
			// Check if this beatmap has a name cached, if yes show it, otherwise show its md5
			if (!$bn) {
				$bn = $play['beatmap_md5'];
			}
			// Get readable play_mode
			$pm = getPlaymodeText($play['play_mode']);
			// Print row
			echo '<tr class="warning">';
			echo '<td><p class="text-left"><a href="index.php?u='.$play["username"].'"><b>'.$play['username'].'</b></a></p></td>';
			echo '<td><p class="text-left">'.$bn.' <b>' . getScoreMods($play['mods']) . '</b></p></td>';
			echo '<td><p class="text-left">'.$pm.'</p></td>';
			echo '<td><p class="text-left">'.timeDifference(time(), $play['time']).'</p></td>';
			echo '<td><p class="text-right"><b>'.number_format($play['pp']).'</b></p></td>';
			echo '</tr>';
		}
		echo '</tbody>';
		echo '</div>';
	}

	/*
	 * AdminUsers
	 * Prints the admin panel users page
	*/
	public static function AdminUsers() {
		// Get admin dashboard data
		$totalUsers = current($GLOBALS['db']->fetch('SELECT COUNT(*) FROM users'));
		$supporters = current($GLOBALS['db']->fetch('SELECT COUNT(*) FROM users WHERE privileges & '.Privileges::UserDonor.' > 0'));
		$bannedUsers = current($GLOBALS['db']->fetch('SELECT COUNT(*) FROM users WHERE privileges & 1 = 0'));
		$modUsers = current($GLOBALS['db']->fetch('SELECT COUNT(*) FROM users WHERE privileges & '.Privileges::AdminAccessRAP.'> 0'));
		// Multiple pages
		$pageInterval = 100;
		$from = (isset($_GET["from"])) ? $_GET["from"] : 999;
		$to = $from+$pageInterval;
		$users = $GLOBALS['db']->fetchAll('SELECT * FROM users WHERE id >= ? AND id < ?', [$from, $to]);
		$groups = $GLOBALS["db"]->fetchAll("SELECT * FROM privileges_groups");
		// Print admin dashboard
		echo '<div id="wrapper">';
		printAdminSidebar();
		echo '<div id="page-content-wrapper">';
		// Maintenance check
		self::MaintenanceStuff();
		// Print Success if set
		if (isset($_GET['s']) && !empty($_GET['s'])) {
			self::SuccessMessageStaccah($_GET['s']);
		}
		// Print Exception if set
		if (isset($_GET['e']) && !empty($_GET['e'])) {
			self::ExceptionMessageStaccah($_GET['e']);
		}
		// Stats panels
		echo '<div class="row">';
		printAdminPanel('primary', 'fa fa-user fa-5x', $totalUsers, 'Total users');
		printAdminPanel('red', 'fa fa-thumbs-down fa-5x', $bannedUsers, 'Banned users');
		printAdminPanel('yellow', 'fa fa-money fa-5x', $supporters, 'Donors');
		printAdminPanel('green', 'fa fa-star fa-5x', $modUsers, 'Admins');
		echo '</div>';
		// Quick edit/silence/kick user button
		echo '<br><p align="center"><button type="button" class="btn btn-primary" data-toggle="modal" data-target="#quickEditUserModal">Quick edit user (username)</button>';
		echo '&nbsp;&nbsp; <button type="button" class="btn btn-info" data-toggle="modal" data-target="#quickEditEmailModal">Quick edit user (email)</button>';
		echo '&nbsp;&nbsp; <button type="button" class="btn btn-warning" data-toggle="modal" data-target="#silenceUserModal">Silence user</button>';
		echo '&nbsp;&nbsp; <button type="button" class="btn btn-danger" data-toggle="modal" data-target="#kickUserModal">Kick user from Bancho</button>';
		echo '</p>';
		// Users plays table
		echo '<table class="table table-striped table-hover table-50-center">
		<thead>
		<tr><th class="text-center"><i class="fa fa-user"></i>	ID</th><th class="text-center">Username</th><th class="text-center">Privileges Group</th><th class="text-center">Allowed</th><th class="text-center">Actions</th></tr>
		</thead>
		<tbody>';
		foreach ($users as $user) {

			// Get group color/text
			$groupColor = "default";
			$groupText = "None";
			foreach ($groups as $group) {
				if ($user["privileges"] == $group["privileges"] || $user["privileges"] == ($group["privileges"] | Privileges::UserDonor)) {
					$groupColor = $group["color"];
					$groupText = $group["name"];
				}
			}

			// Get allowed color/text
			$allowedColor = "success";
			$allowedText = "Ok";
			if (($user["privileges"] & Privileges::UserPublic) == 0 && ($user["privileges"] & Privileges::UserNormal) == 0) {
				// Not visible and not active, banned
				$allowedColor = "danger";
				$allowedText = "Banned";
			} else if (($user["privileges"] & Privileges::UserPublic) == 0 && ($user["privileges"] & Privileges::UserNormal) > 0) {
				// Not visible but active, restricted
				$allowedColor = "warning";
				$allowedText = "Restricted";
			} else if (($user["privileges"] & Privileges::UserPublic) > 0 && ($user["privileges"] & Privileges::UserNormal) == 0) {
				// Visible but not active, disabled (not supported yet)
				$allowedColor = "default";
				$allowedText = "Locked";
			}

			// Print row
			echo '<tr>';
			echo '<td><p class="text-center">'.$user['id'].'</p></td>';
			echo '<td><p class="text-center"><b>'.$user['username'].'</b></p></td>';
			echo '<td><p class="text-center"><span class="label label-'.$groupColor.'">'.$groupText.'</span></p></td>';
			echo '<td><p class="text-center"><span class="label label-'.$allowedColor.'">'.$allowedText.'</span></p></td>';
			echo '<td><p class="text-center">
			<div class="btn-group">
			<a title="Edit user" class="btn btn-xs btn-primary" href="index.php?p=103&id='.$user['id'].'"><span class="glyphicon glyphicon-pencil"></span></a>';
			if (hasPrivilege(Privileges::AdminBanUsers)) {
				if (isBanned($user["id"])) {
					echo '<a title="Unban user" class="btn btn-xs btn-success" onclick="sure(\'submit.php?action=banUnbanUser&id='.$user['id'].'\')"><span class="glyphicon glyphicon-thumbs-up"></span></a>';
				} else {
					echo '<a title="Ban user" class="btn btn-xs btn-warning" onclick="sure(\'submit.php?action=banUnbanUser&id='.$user['id'].'\')"><span class="glyphicon glyphicon-thumbs-down"></span></a>';
				}
				if (isRestricted($user["id"])) {
					echo '<a title="Remove restrictions" class="btn btn-xs btn-success" onclick="sure(\'submit.php?action=restrictUnrestrictUser&id='.$user['id'].'\')"><span class="glyphicon glyphicon-ok-circle"></span></a>';
				} else {
					echo '<a title="Restrict user" class="btn btn-xs btn-warning" onclick="sure(\'submit.php?action=restrictUnrestrictUser&id='.$user['id'].'\')"><span class="glyphicon glyphicon-remove-circle"></span></a>';
				}
			}
			echo '	<a title="Change user identity" class="btn btn-xs btn-danger" href="index.php?p=104&id='.$user['id'].'"><span class="glyphicon glyphicon-refresh"></span></a>
			</div>
			</p></td>';
			echo '</tr>';
		}
		echo '</tbody></table>';
		echo '<p align="center"><a href="index.php?p=102&from='.($from-($pageInterval+1)).'">< Previous page</a> | <a href="index.php?p=102&from='.($to).'">Next page ></a></p>';
		echo '</div>';
		// Quick edit modal
		echo '<div class="modal fade" id="quickEditUserModal" tabindex="-1" role="dialog" aria-labelledby="quickEditUserModalLabel">
		<div class="modal-dialog">
		<div class="modal-content">
		<div class="modal-header">
		<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
		<h4 class="modal-title" id="quickEditUserModalLabel">Quick edit user</h4>
		</div>
		<div class="modal-body">
		<p>
		<form id="quick-edit-user-form" action="submit.php" method="POST">
		<input name="action" value="quickEditUser" hidden>
		<div class="input-group">
		<span class="input-group-addon" id="basic-addon1"><span class="glyphicon glyphicon-user" aria-hidden="true"></span></span>
		<input type="text" name="u" class="form-control" placeholder="Username" aria-describedby="basic-addon1" required>
		</div>
		</form>
		</p>
		</div>
		<div class="modal-footer">
		<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
		<button type="submit" form="quick-edit-user-form" class="btn btn-primary">Edit user</button>
		</div>
		</div>
		</div>
		</div>';
		// Search user by email modal
		echo '<div class="modal fade" id="quickEditEmailModal" tabindex="-1" role="dialog" aria-labelledby="quickEditEmailModalLabel">
		<div class="modal-dialog">
		<div class="modal-content">
		<div class="modal-header">
		<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
		<h4 class="modal-title" id="quickEditEmailModalLabel">Quick edit user</h4>
		</div>
		<div class="modal-body">
		<p>
		<form id="quick-edit-user-email-form" action="submit.php" method="POST">
		<input name="action" value="quickEditUserEmail" hidden>
		<div class="input-group">
		<span class="input-group-addon" id="basic-addon1"><span class="glyphicon glyphicon-envelope" aria-hidden="true"></span></span>
		<input type="text" name="u" class="form-control" placeholder="Email" aria-describedby="basic-addon1" required>
		</div>
		</form>
		</p>
		</div>
		<div class="modal-footer">
		<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
		<button type="submit" form="quick-edit-user-email-form" class="btn btn-primary">Edit user</button>
		</div>
		</div>
		</div>
		</div>';
		// Silence user modal
		echo '<div class="modal fade" id="silenceUserModal" tabindex="-1" role="dialog" aria-labelledby="silenceUserModal">
		<div class="modal-dialog">
		<div class="modal-content">
		<div class="modal-header">
		<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
		<h4 class="modal-title" id="silenceUserModal">Silence user</h4>
		</div>
		<div class="modal-body">
		<p>
		<form id="silence-user-form" action="submit.php" method="POST">
		<input name="action" value="silenceUser" hidden>

		<div class="input-group">
		<span class="input-group-addon" id="basic-addon1"><span class="glyphicon glyphicon-user" aria-hidden="true"></span></span>
		<input type="text" name="u" class="form-control" placeholder="Username" aria-describedby="basic-addon1" required>
		</div>

		<p style="line-height: 15px"></p>

		<div class="input-group">
		<span class="input-group-addon" id="basic-addon1"><span class="glyphicon glyphicon-time" aria-hidden="true"></span></span>
		<input type="number" name="c" class="form-control" placeholder="How long" aria-describedby="basic-addon1" required>
		<select name="un" class="selectpicker" data-width="30%">
			<option value="1">Seconds</option>
			<option value="60">Minutes</option>
			<option value="3600">Hours</option>
			<option value="86400">Days</option>
		</select>
		</div>

		<p style="line-height: 15px"></p>

		<div class="input-group">
		<span class="input-group-addon" id="basic-addon1"><span class="glyphicon glyphicon-comment" aria-hidden="true"></span></span>
		<input type="text" name="r" class="form-control" placeholder="Reason" aria-describedby="basic-addon1">
		</div>

		<p style="line-height: 15px"></p>

		During the silence period, user\'s client will be locked. <b>Max silence time is 7 days.</b> Set length to 0 to remove the silence.

		</form>
		</p>
		</div>
		<div class="modal-footer">
		<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
		<button type="submit" form="silence-user-form" class="btn btn-primary">Silence user</button>
		</div>
		</div>
		</div>
		</div>';
		// Kick user modal
		echo '<div class="modal fade" id="kickUserModal" tabindex="-1" role="dialog" aria-labelledby="kickUserModalLabel">
		<div class="modal-dialog">
		<div class="modal-content">
		<div class="modal-header">
		<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
		<h4 class="modal-title" id="kickUserModalLabel">Kick user from Bancho</h4>
		</div>
		<div class="modal-body">
		<p>
		<form id="kick-user-form" action="submit.php" method="POST">
		<input name="action" value="kickUser" hidden>
		<div class="input-group">
		<span class="input-group-addon" id="basic-addon1"><span class="glyphicon glyphicon-user" aria-hidden="true"></span></span>
		<input type="text" name="u" class="form-control" placeholder="Username" aria-describedby="basic-addon1" required>
		</div>
		</p>
		<p>
		<div class="input-group">
		<span class="input-group-addon" id="basic-addon1"><span class="glyphicon glyphicon-asterisk" aria-hidden="true"></span></span>
		<input type="text" name="r" class="form-control" placeholder="Reason" aria-describedby="basic-addon1" value="You have been kicked from the server. Please login again." required>
		</div>
		</form>
		</p>
		</div>
		<div class="modal-footer">
		<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
		<button type="submit" form="kick-user-form" class="btn btn-primary">Kick user</button>
		</div>
		</div>
		</div>
		</div>';
	}

	/*
	 * AdminEditUser
	 * Prints the admin panel edit user page
	*/
	public static function AdminEditUser() {
		try {
			// Check if id is set
			if (!isset($_GET['id']) || empty($_GET['id'])) {
				throw new Exception('Invalid user ID!');
			}
			// Get user data
			$userData = $GLOBALS['db']->fetch('SELECT * FROM users WHERE id = ? LIMIT 1', $_GET['id']);
			$userStatsData = $GLOBALS['db']->fetch('SELECT * FROM users_stats WHERE id = ? LIMIT 1', $_GET['id']);
			$ips = $GLOBALS['db']->fetchAll('SELECT ip FROM ip_user WHERE userid = ?', $_GET['id']);
			// Check if this user exists
			if (!$userData || !$userStatsData) {
				throw new Exception("That user doesn't exist");
			}
			// Hax check
			if ($userData["aqn"] == 1) {
				$haxText = "Yes";
				$haxCol = "danger";
			} else {
				$haxText = "No";
				$haxCol = "success";
			}
			// Cb check
			if ($userStatsData["can_custom_badge"] == 1) {
				$cbText = "Yes";
				$cbCol = "success";
			} else {
				$cbText = "No";
				$cbCol = "danger";
			}
			// Set readonly stuff
			$readonly[0] = ''; // User data stuff
			$readonly[1] = ''; // Username color/style stuff
			$selectDisabled = '';
			// Check if we are editing our account
			if ($userData['username'] == $_SESSION['username']) {
				// Allow to edit only user stats
				$readonly[0] = 'readonly';
				$selectDisabled = 'disabled';
			} elseif (($userData["privileges"] & Privileges::AdminManageUsers) > 0) {
				// We are trying to edit a user with same/higher rank than us :akerino:
				redirect("index.php?p=102&e=You don't have enough permissions to edit this user");
				die();
			}
			// Print edit user stuff
			echo '<div id="wrapper">';
			printAdminSidebar();
			echo '<div id="page-content-wrapper">';
			// Maintenance check
			self::MaintenanceStuff();
			// Print Success if set
			if (isset($_GET['s']) && !empty($_GET['s'])) {
				self::SuccessMessageStaccah($_GET['s']);
			}
			// Print Exception if set
			if (isset($_GET['e']) && !empty($_GET['e'])) {
				self::ExceptionMessageStaccah($_GET['e']);
			}
			// Selected values stuff 1
			//$selected[0] = [1 => '', 2 => '', 3 => '', 4 => ''];
			// Selected values stuff 2
			//$selected[1] = [0 => '', 1 => '', 2 => ''];

			// Get selected stuff
			//$selected[0][current($GLOBALS['db']->fetch('SELECT rank FROM users WHERE id = ?', $_GET['id']))] = 'selected';
			//$selected[1][($userData["privileges"] & Privileges::UserBasic) > 0 ? 1 : 0] = 'selected';

			echo '<p align="center"><font size=5><i class="fa fa-user"></i>	Edit user</font></p>';
			echo '<table class="table table-striped table-hover table-50-center">';
			echo '<tbody><form id="system-settings-form" action="submit.php" method="POST"><input name="action" value="saveEditUser" hidden>';
			echo '<tr>
			<td>ID</td>
			<td><p class="text-center"><input type="number" name="id" class="form-control" value="'.$userData['id'].'" readonly></td>
			</tr>';
			echo '<tr>
			<td>Username</td>
			<td><p class="text-center"><input type="text" name="u" class="form-control" value="'.$userData['username'].'" readonly></td>
			</tr>';
			echo '<tr>
			<td>Email</td>
			<td><p class="text-center"><input type="text" name="e" class="form-control" value="'.$userData['email'].'" '.$readonly[0].'></td>
			</tr>';
			echo '<tr>
			<td>Country</td>
			<td>
			<select name="country" class="selectpicker" data-width="100%">
			';
			require_once dirname(__FILE__) . "/countryCodesReadable.php";
			asort($c);
			// Push XX to top
			$c = array('XX' => $c['XX']) + $c;
			reset($c);
			foreach ($c as $k => $v) {
				$sd = "";
				if ($userStatsData['country'] == $k)
					$sd = "selected";
				$ks = strtolower($k);
				if (!file_exists(dirname(__FILE__) . "/../images/flags/$ks.png"))
					$ks = "xx";
				echo "<option value='$k' $sd data-content=\""
					. "<img src='images/flags/$ks.png' alt='$k'>"
					. " $v\"></option>\n";
			}
			echo '
			</select>
			</td>
			</tr>';
			echo '<tr>
			<td>Allowed</td>
			<td>';

			if (isBanned($userData["id"])) {
				echo "Banned";
			} else if (isRestricted($userData["id"])) {
				echo "Restricted";
			} else if (!hasPrivilege(Privileges::UserNormal, $userData["id"])) {
				echo "Locked";
			} else {
				echo "Ok";
			}

			echo '</td>
			</tr>';
			if (isBanned($userData["id"]) || isRestricted($userData["id"])) {
				$canAppeal = time()-$userData["ban_datetime"] >= 86400*30;
				echo '<tr class="'; echo $canAppeal ? 'success' : 'warning'; echo '">
				<td>Ban/Restricted Date<br><i>(dd/mm/yyyy)</i></td>
				<td>' . date('d/m/Y', $userData["ban_datetime"]) . "<br>";
				echo $canAppeal ? '<i> (can appeal)</i>' : '<i> (can\'t appeal yet)<i>';
				echo '</td>
				</tr>';
			}
			if (hasPrivilege(Privileges::UserDonor,$userData["id"])) {
				$donorExpire = timeDifference($userData["donor_expire"], time(), false);
				echo '<tr>
				<td>Donor expires in</td>
				<td>'.$donorExpire.'</td>
				</tr>';
			}
			echo '<tr>
			<td>Username color<br><i>(HTML or HEX color)</i></td>
			<td><p class="text-center"><input type="text" name="c" class="form-control" value="'.$userStatsData['user_color'].'" '.$readonly[1].'></td>
			</tr>';
			echo '<tr>
			<td>Username CSS<br><i>(like fancy gifs as background)</i></td>
			<td><p class="text-center"><input type="text" name="bg" class="form-control" value="'.$userStatsData['user_style'].'" '.$readonly[1].'></td>
			</tr>';
			echo '<tr>
			<td>A.K.A</td>
			<td><p class="text-center"><input type="text" name="aka" class="form-control" value="'.htmlspecialchars($userStatsData['username_aka']).'"></td>
			</tr>';
			echo '<tr>
			<td>Userpage<br><a onclick="censorUserpage();">(reset userpage)</a></td>
			<td><p class="text-center"><textarea name="up" class="form-control" style="overflow:auto;resize:vertical;height:200px">'.$userStatsData['userpage_content'].'</textarea></td>
			</tr>';
			if (hasPrivilege(Privileges::AdminSilenceUsers)) {
				echo '<tr>
				<td>Silence end time<br><a onclick="removeSilence();">(remove silence)</a></td>
				<td><p class="text-center"><input type="text" name="se" class="form-control" value="'.$userData['silence_end'].'"></td>
				</tr>';
				echo '<tr>
				<td>Silence reason</td>
				<td><p class="text-center"><input type="text" name="sr" class="form-control" value="'.$userData['silence_reason'].'"></td>
				</tr>';
			}
			if (hasPrivilege(Privileges::AdminManagePrivileges)) {
				$gd = $userData["id"] == $_SESSION["userid"] ? "disabled" : "";
				echo '<tr>
				<td>Privileges<br><i>(Don\'t touch<br>UserPublic or UserNormal.<br>Use ban/restricted buttons<br>instead to avoid messing up)</i></td>
				<td>';
				$refl = new ReflectionClass("Privileges");
				$privilegesList = $refl->getConstants();
				foreach ($privilegesList as $i => $v) {
					if ($v <= 0)
						continue;
					$c = (($userData["privileges"] & $v) > 0) ? "checked" : "";
					$d = ($v <= 2 && $gd != "disabled") ? "disabled" : "";
					echo '<label><input name="privilege" value="'.$v.'" type="checkbox" onclick="updatePrivileges();" '.$c.' '.$gd.' '.$d.'>	'.$i.' ('.$v.')</label><br>';
				}
				echo '</tr>';
				$ro = $userData["id"] == $_SESSION["userid"] ? "readonly" : "";
				echo '<tr>
				<td>Privilege number</td>
				<td><input class="form-control" id="privileges-value" name="priv" value="'.$userData["privileges"].'" '.$ro.'></td>
				</tr>';
				echo '<tr>
				<td>Privilege group<br><i>(This is basically a preset<br>and will replace every<br>existing privilege)</i></td>
				<td>
					<select id="privileges-group" name="privgroup" class="selectpicker" data-width="100%" onchange="groupUpdated();" '.$gd.'>';
					$groups = $GLOBALS["db"]->fetchAll("SELECT * FROM privileges_groups");
					echo "<option value='-1'>None</option>";
					foreach ($groups as $group) {
						$s = (($userData["privileges"] == $group["privileges"]) || ($userData["privileges"] == ($group["privileges"] | Privileges::UserDonor)))? "selected": "";
						echo "<option value='$group[privileges]' $s>$group[name]</option>";
					}
					echo '</select>
				</td>
				</tr>';
			}
			echo '<tr>
			<td>Avatar<br><a onclick="sure(\'submit.php?action=resetAvatar&id='.$_GET['id'].'\')">(reset avatar)</a></td>
			<td>
				<p align="center">
					<img src="'.URL::Avatar().'/'.$_GET['id'].'" height="50" width="50"></img>
				</p>
			</td>
			</tr>';
			if (hasPrivilege(Privileges::UserDonor, $_GET["id"])) {
				echo '<tr>
				<td>Custom badge</td>
				<td>
					<p align="center">
						<i class="fa '.htmlspecialchars($userStatsData["custom_badge_icon"]).' fa-2x"></i>
						<br>
						<b>'.htmlspecialchars($userStatsData["custom_badge_name"]).'</b>
					</p>
				</td>
				</tr>';
			}
			echo '<tr>
			<td>Can edit custom badge</td>
			<td><span class="label label-'.$cbCol.'">'.$cbText.'</span></td>
			</tr>';
			echo '<tr>
			<td>Detected AQN folder
				<br>
				<i>(If \'yes\', AQN (hax) folder has been<br>detected on this user, so he is<br>probably cheating).</i></td>
			</td>
			<td><span class="label label-'.$haxCol.'">'.$haxText.'</span></td>
			</tr>';
			echo '<tr>
			<td>Notes for CMs
			<br>
			<i>(visible only from RAP)</i></td>
			<td><textarea name="ncm" class="form-control" style="overflow:auto;resize:vertical;height:100px">' . $userData["notes"] . '</textarea></td>
			</tr>';
			echo '<tr><td>IPs</td><td><ul>';
			foreach ($ips as $ip) {
				echo "<li>$ip[ip] <a class='getcountry' data-ip='$ip[ip]' title='Click to retrieve IP country'>(?)</a></li>";
			}
			echo '</ul></td></tr>';
			echo '</tbody></form>';
			echo '</table>';
			echo '<div class="text-center" style="width:50%; margin-left:25%;">
					<button type="submit" form="system-settings-form" class="btn btn-primary">Save changes</button><br><br>

					<br><br>
					<b>If you have made any changes to this user through this page, make sure to save them before using one of the following functions, otherwise unsubmitted changes will be lost.</b>
					<ul class="list-group">
						<li class="list-group-item list-group-item-info">Actions</li>
						<li class="list-group-item">';
							if (hasPrivilege(Privileges::AdminManageBadges)) {
								echo '<a href="index.php?p=110&id='.$_GET['id'].'" class="btn btn-success">Edit badges</a>';
							}
							echo '	<a href="index.php?p=104&id='.$_GET['id'].'" class="btn btn-info">Change identity</a>';
							if (hasPrivilege(Privileges::UserDonor, $_GET["id"])) {
								echo '	<a onclick="sure(\'submit.php?action=removeDonor&id='.$_GET['id'].'\');" class="btn btn-danger">Remove donor</a>';
							}
							echo '	<a href="index.php?p=121&id='.$_GET['id'].'" class="btn btn-warning">Give donor</a>';
							echo '	<a href="index.php?u='.$_GET['id'].'" class="btn btn-primary">View profile</a>';
						echo '</li>
					</ul>';

					echo '<ul class="list-group">
					<li class="list-group-item list-group-item-danger">Dangerous Zone</li>
					<li class="list-group-item">';
					if (hasPrivilege(Privileges::AdminWipeUsers)) {
						echo '	<a href="index.php?p=123&id='.$_GET["id"].'" class="btn btn-danger">Wipe account</a>';
						echo '	<a href="index.php?p=122&id='.$_GET["id"].'" class="btn btn-danger">Rollback account</a>';
					}
					if (hasPrivilege(Privileges::AdminBanUsers)) {
						echo '	<a onclick="sure(\'submit.php?action=banUnbanUser&id='.$_GET['id'].'\')" class="btn btn-danger">(Un)ban user</a>';
						echo '	<a onclick="sure(\'submit.php?action=restrictUnrestrictUser&id='.$_GET['id'].'\')" class="btn btn-danger">(Un)restrict user</a>';
						echo '	<a onclick="sure(\'submit.php?action=lockUnlockUser&id='.$_GET['id'].'\', \'Restrictions and bans will be removed from this account if you lock it. Make sure to lock only accounts that are not banned or restricted.\')" class="btn btn-danger">(Un)lock user</a>';
						echo '	<a onclick="sure(\'submit.php?action=clearHWID&id='.$_GET['id'].'\');" class="btn btn-danger">Clear HWID matches</a>';
					}
					echo '<br><br>';
					if (hasPrivilege(Privileges::AdminCaker)) {
						echo '<a href="index.php?p=128&uid=' . $_GET["id"] . '" class="btn btn-danger">Find ' . Fringuellina::$cakeRecipeName . '</a>';
					}
					echo '		<a onclick="sure(\'submit.php?action=toggleCustomBadge&id='.$_GET['id'].'\');" class="btn btn-danger">'.(($userStatsData["can_custom_badge"] == 1) ? "Revoke" : "Grant").' custom badge</a>';
					echo '<br>
						</li>
					</ul>';

				echo '</div>
				</div>';
		}
		catch(Exception $e) {
			// Redirect to exception page
			redirect('index.php?p=102&e='.$e->getMessage());
		}
	}

	/*
	 * AdminChangeIdentity
	 * Prints the admin panel change identity page
	*/
	public static function AdminChangeIdentity() {
		try {
			// Get user data
			$userData = $GLOBALS['db']->fetch('SELECT * FROM users WHERE id = ?', $_GET['id']);
			$userStatsData = $GLOBALS['db']->fetch('SELECT * FROM users_stats WHERE id = ?', $_GET['id']);
			// Check if this user exists
			if (!$userData || !$userStatsData) {
				throw new Exception("That user doesn't exist");
			}
			// Check if we are trying to edit our account or a higher rank account
			if ($userData['username'] != $_SESSION['username'] && (($userData['privileges'] & Privileges::AdminManageUsers) > 0)) {
				throw new Exception("You don't have enough permission to edit this user.");
			}
			// Print edit user stuff
			echo '<div id="wrapper">';
			printAdminSidebar();
			echo '<div id="page-content-wrapper">';
			// Maintenance check
			self::MaintenanceStuff();
			// Print Success if set
			if (isset($_GET['s']) && !empty($_GET['s'])) {
				self::SuccessMessageStaccah($_GET['s']);
			}
			// Print Exception if set
			if (isset($_GET['e']) && !empty($_GET['e'])) {
				self::ExceptionMessageStaccah($_GET['e']);
			}
			echo '<p align="center"><font size=5><i class="fa fa-refresh"></i>	Change identity</font></p>';
			echo '<table class="table table-striped table-hover table-50-center">';
			echo '<tbody><form id="system-settings-form" action="submit.php" method="POST"><input name="action" value="changeIdentity" hidden>';
			echo '<tr>
			<td>ID</td>
			<td><p class="text-center"><input type="number" name="id" class="form-control" value="'.$userData['id'].'" readonly></td>
			</tr>';
			echo '<tr>
			<td>Old Username</td>
			<td><p class="text-center"><input type="text" name="oldu" class="form-control" value="'.$userData['username'].'" readonly></td>
			</tr>';
			echo '<tr>
			<td>New Username</td>
			<td><p class="text-center"><input type="text" name="newu" class="form-control"></td>
			</tr>';
			echo '</tbody></form>';
			echo '</table>';
			echo '<div class="text-center"><button type="submit" form="system-settings-form" class="btn btn-primary">Change identity</button></div>';
			echo '</div>';
		}
		catch(Exception $e) {
			// Redirect to exception page
			redirect('index.php?p=102&e='.$e->getMessage());
		}
	}

	/*
	 * AdminSystemSettings
	 * Prints the admin panel system settings page
	*/
	public static function AdminSystemSettings() {
		// Print stuff
		echo '<div id="wrapper">';
		printAdminSidebar();
		echo '<div id="page-content-wrapper">';
		// Maintenance check
		self::MaintenanceStuff();
		// Print Success if set
		if (isset($_GET['s']) && !empty($_GET['s'])) {
			self::SuccessMessageStaccah($_GET['s']);
		}
		// Print Exception if set
		if (isset($_GET['e']) && !empty($_GET['e'])) {
			self::ExceptionMessageStaccah($_GET['e']);
		}
		// Get values
		$wm = current($GLOBALS['db']->fetch("SELECT value_int FROM system_settings WHERE name = 'website_maintenance'"));
		$gm = current($GLOBALS['db']->fetch("SELECT value_int FROM system_settings WHERE name = 'game_maintenance'"));
		$r = current($GLOBALS['db']->fetch("SELECT value_int FROM system_settings WHERE name = 'registrations_enabled'"));
		$ga = current($GLOBALS['db']->fetch("SELECT value_string FROM system_settings WHERE name = 'website_global_alert'"));
		$ha = current($GLOBALS['db']->fetch("SELECT value_string FROM system_settings WHERE name = 'website_home_alert'"));
		// Default select stuff
		$selected[0] = [1 => '', 2 => ''];
		$selected[1] = [1 => '', 2 => ''];
		$selected[2] = [1 => '', 2 => ''];
		// Checked stuff
		if ($wm == 1) {
			$selected[0][1] = 'selected';
		} else {
			$selected[0][2] = 'selected';
		}
		if ($gm == 1) {
			$selected[1][1] = 'selected';
		} else {
			$selected[1][2] = 'selected';
		}
		if ($r == 1) {
			$selected[2][1] = 'selected';
		} else {
			$selected[2][2] = 'selected';
		}
		echo '<p align="center"><font size=5><i class="fa fa-cog"></i>	System settings</font></p>';
		echo '<table class="table table-striped table-hover table-50-center">';
		echo '<tbody><form id="system-settings-form" action="submit.php" method="POST"><input name="action" value="saveSystemSettings" hidden>';
		echo '<tr>
		<td>Maintenance mode (website)</td>
		<td>
		<select name="wm" class="selectpicker" data-width="100%">
		<option value="1" '.$selected[0][1].'>On</option>
		<option value="0" '.$selected[0][2].'>Off</option>
		</select>
		</td>
		</tr>';
		echo '<tr>
		<td>Maintenance mode (in-game)</td>
		<td>
		<select name="gm" class="selectpicker" data-width="100%">
		<option value="1" '.$selected[1][1].'>On</option>
		<option value="0" '.$selected[1][2].'>Off</option>
		</select>
		</td>
		</tr>';
		echo '<tr>
		<td>Registration</td>
		<td>
		<select name="r" class="selectpicker" data-width="100%">
		<option value="1" '.$selected[2][1].'>On</option>
		<option value="0" '.$selected[2][2].'>Off</option>
		</select>
		</td>
		</tr>';
		echo '<tr>
		<td>Global alert<br>(visible on every page of the website)</td>
		<td><textarea type="text" name="ga" class="form-control" maxlength="512" style="overflow:auto;resize:vertical;height:100px">'.$ga.'</textarea></td>
		</tr>';
		echo '<tr>
		<td>Homepage alert<br>(visible only on the home page)</td>
		<td><textarea type="text" name="ha" class="form-control" maxlength="512" style="overflow:auto;resize:vertical;height:100px">'.$ha.'</textarea></td>
		</tr>';
		echo '<tr class="success"><td colspan=2><p align="center">Click <a href="index.php?p=111">here</a> for bancho settings</p></td></tr>';
		echo '</tbody></form>';
		echo '</table>';
		echo '<div class="text-center"><div class="btn-group" role="group">
		<button type="submit" form="system-settings-form" class="btn btn-primary">Save settings</button>
		</div></div>';
		echo '</div>';
	}

	/*
	 * AdminDocumentation
	 * Prints the admin panel documentation files page
	*/
	public static function AdminDocumentation() {
		// Get data
		$docsData = $GLOBALS['db']->fetchAll('SELECT id, doc_name, public, is_rule FROM docs');
		// Print docs stuff
		echo '<div id="wrapper">';
		printAdminSidebar();
		echo '<div id="page-content-wrapper">';
		// Maintenance check
		self::MaintenanceStuff();
		// Print Success if set
		if (isset($_GET['s']) && !empty($_GET['s'])) {
			self::SuccessMessageStaccah($_GET['s']);
		}
		// Print Exception if set
		if (isset($_GET['e']) && !empty($_GET['e'])) {
			self::ExceptionMessageStaccah($_GET['e']);
		}
		echo '<p align="center"><font size=5><i class="fa fa-book"></i>	Documentation</font></p>';
		echo '<table class="table table-striped table-hover table-50-center">';
		echo '<thead>
		<tr><th class="text-center"><i class="fa fa-book"></i>	ID</th><th class="text-center">Name</th><th class="text-center">Public</th><th class="text-center">Actions</th></tr>
		</thead>';
		echo '<tbody>';
		foreach ($docsData as $doc) {
			// Public label
			if ($doc['public'] == 1) {
				$publicColor = 'success';
				$publicText = 'Yes';
			} else {
				$publicColor = 'danger';
				$publicText = 'No';
			}
			$ruletxt = "";
			if ($doc['is_rule'])
				$ruletxt = " <b>(rules)</b>";
			// Print row for this doc page
			echo '<tr>
			<td><p class="text-center">'.$doc['id'].'</p></td>
			<td><p class="text-center">'.$doc['doc_name'].$ruletxt.'</p></td>
			<td><p class="text-center"><span class="label label-'.$publicColor.'">'.$publicText.'</span></p></td>
			<td><p class="text-center">
			<a title="Edit page" class="btn btn-xs btn-primary" href="index.php?p=107&id='.$doc['id'].'"><span class="glyphicon glyphicon-pencil"></span></a>
			<a title="View page" class="btn btn-xs btn-success" href="index.php?p=16&id='.$doc['id'].'"><span class="glyphicon glyphicon-eye-open"></span></a>
			<a title="Make rules page" class="btn btn-xs btn-warning" href="submit.php?action=setRulesPage&id='.$doc['id'].'"><i class="fa fa-exclamation-circle" aria-hidden="true"></i></a>
			<a title="Delete page" class="btn btn-xs btn-danger" onclick="sure(\'submit.php?action=removeDoc&id='.$doc['id'].'\');"><span class="glyphicon glyphicon-trash"></span></a>
			</p></td>
			</tr>';
		}
		echo '</tbody>';
		echo '</table>';
		echo '<div class="text-center"><div class="btn-group" role="group">
		<a href="index.php?p=107&id=0" type="button" class="btn btn-primary">Add documentation page</a>
		</div></div>';
		echo '</div>';
	}

	/*
	 * AdminBadges
	 * Prints the admin panel badges page
	*/
	public static function AdminBadges() {
		// Get data
		$badgesData = $GLOBALS['db']->fetchAll('SELECT * FROM badges');
		// Print docs stuff
		echo '<div id="wrapper">';
		printAdminSidebar();
		echo '<div id="page-content-wrapper">';
		// Maintenance check
		self::MaintenanceStuff();
		// Print Success if set
		if (isset($_GET['s']) && !empty($_GET['s'])) {
			self::SuccessMessageStaccah($_GET['s']);
		}
		// Print Exception if set
		if (isset($_GET['e']) && !empty($_GET['e'])) {
			self::ExceptionMessageStaccah($_GET['e']);
		}
		echo '<p align="center"><font size=5><i class="fa fa-certificate"></i>	Badges</font></p>';
		echo '<table class="table table-striped table-hover table-50-center">';
		echo '<thead>
		<tr><th class="text-center"><i class="fa fa-certificate"></i>	ID</th><th class="text-center">Name</th><th class="text-center">Icon</th><th class="text-center">Actions</th></tr>
		</thead>';
		echo '<tbody>';
		foreach ($badgesData as $badge) {
			// Print row for this badge
			echo '<tr>
			<td><p class="text-center">'.$badge['id'].'</p></td>
			<td><p class="text-center">'.$badge['name'].'</p></td>
			<td><p class="text-center"><i class="fa '.$badge['icon'].' fa-2x"></i></p></td>
			<td><p class="text-center">
			<a title="Edit badge" class="btn btn-xs btn-primary" href="index.php?p=109&id='.$badge['id'].'"><span class="glyphicon glyphicon-pencil"></span></a>
			<a title="Delete badge" class="btn btn-xs btn-danger" onclick="sure(\'submit.php?action=removeBadge&id='.$badge['id'].'\');"><span class="glyphicon glyphicon-trash"></span></a>
			</p></td>
			</tr>';
		}
		echo '</tbody>';
		echo '</table>';
		echo '<div class="text-center">
			<a href="index.php?p=109&id=0" type="button" class="btn btn-primary">Add a new badge</a>
			<a type="button" class="btn btn-success" data-toggle="modal" data-target="#quickEditUserBadgesModal">Edit user badges</a>
		</div>';
		echo '</div>';
		// Quick edit modal
		echo '<div class="modal fade" id="quickEditUserBadgesModal" tabindex="-1" role="dialog" aria-labelledby="quickEditUserBadgesModalLabel">
		<div class="modal-dialog">
		<div class="modal-content">
		<div class="modal-header">
		<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
		<h4 class="modal-title" id="quickEditUserBadgesModalLabel">Edit user badges</h4>
		</div>
		<div class="modal-body">
		<p>
		<form id="quick-edit-user-form" action="submit.php" method="POST">
		<input name="action" value="quickEditUserBadges" hidden>
		<div class="input-group">
		<span class="input-group-addon" id="basic-addon1"><span class="glyphicon glyphicon-user" aria-hidden="true"></span></span>
		<input type="text" name="u" class="form-control" placeholder="Username" aria-describedby="basic-addon1" required>
		</div>
		</form>
		</p>
		</div>
		<div class="modal-footer">
		<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
		<button type="submit" form="quick-edit-user-form" class="btn btn-primary">Edit user badges</button>
		</div>
		</div>
		</div>
		</div>';
	}

	/*
	 * AdminEditDocumentation
	 * Prints the admin panel edit documentation file page
	*/
	public static function AdminEditDocumentation() {
		try {
			// Check if id is set
			if (!isset($_GET['id'])) {
				throw new Exception('Invalid documentation page id');
			}
			// Check if we are editing or creating a new docs page
			if ($_GET['id'] > 0) {
				$docData = $GLOBALS['db']->fetch('SELECT * FROM docs WHERE id = ?', $_GET['id']);
			} else {
				$docData = ['id' => 0, 'doc_name' => 'New Documentation Page', 'doc_contents' => '', 'public' => 1];
			}
			// Check if this doc page exists
			if (!$docData) {
				throw new Exception("That documentation page doesn't exist");
			}
			// Print edit user stuff
			echo '<div id="wrapper">';
			printAdminSidebar();
			echo '<div id="page-content-wrapper">';
			// Maintenance check
			self::MaintenanceStuff();
			// Selected values stuff
			$selected[0] = [0 => '', 1 => ''];
			// Get selected stuff
			$selected[0][$docData['public']] = 'selected';
			echo '<p align="center"><font size=5><i class="fa fa-book"></i>	Edit documentation page</font></p>';
			echo '<table class="table table-striped table-hover table-75-center">';
			echo '<tbody><form id="edit-doc-form" action="submit.php" method="POST"><input name="action" value="saveDocFile" hidden>';
			echo '<tr>
			<td>ID</td>
			<td><p class="text-center"><input type="number" name="id" class="form-control" value="'.$docData['id'].'" readonly></td>
			</tr>';
			echo '<tr>
			<td>Page Name</td>
			<td><p class="text-center"><input type="text" name="t" class="form-control" value="'.$docData['doc_name'].'" ></td>
			</tr>';
			echo '<tr>
			<td>Page content</td>
			<td><textarea type="text" name="c" class="form-control" style="height: 200px;max-width:100%" spellcheck="false">'.$docData['doc_contents'].'</textarea></td>
			</tr>';
			echo '<tr class="success"><td></td><td>Tip: You can use markdown syntax instead of HTML syntax</td></tr>';
			echo '<tr>
			<td>Public</td>
			<td>
			<select name="p" class="selectpicker" data-width="100%">
			<option value="1" '.$selected[0][1].'>Yes</option>
			<option value="0" '.$selected[0][0].'>No</option>
			</select>
			</td>
			</tr>';
			echo '</tbody></form>';
			echo '</table>';
			echo '<div class="text-center"><button type="submit" form="edit-doc-form" class="btn btn-primary">Save changes</button></div>';
			echo '</div>';
		}
		catch(Exception $e) {
			// Redirect to exception page
			redirect('index.php?p=106&e='.$e->getMessage());
		}
	}

	/*
	 * AdminEditBadge
	 * Prints the admin panel edit badge page
	*/
	public static function AdminEditBadge() {
		try {
			// Check if id is set
			if (!isset($_GET['id'])) {
				throw new Exception('Invalid badge id');
			}
			// Check if we are editing or creating a new badge
			if ($_GET['id'] > 0) {
				$badgeData = $GLOBALS['db']->fetch('SELECT * FROM badges WHERE id = ?', $_GET['id']);
			} else {
				$badgeData = ['id' => 0, 'name' => 'New Badge', 'icon' => ''];
			}
			// Check if this doc page exists
			if (!$badgeData) {
				throw new Exception("That badge doesn't exist");
			}
			// Print edit user stuff
			echo '<div id="wrapper">';
			printAdminSidebar();
			echo '<div id="page-content-wrapper">';
			// Maintenance check
			self::MaintenanceStuff();
			echo '<p align="center"><font size=5><i class="fa fa-certificate"></i>	Edit badge</font></p>';
			echo '<table class="table table-striped table-hover table-50-center">';
			echo '<tbody><form id="edit-badge-form" action="submit.php" method="POST"><input name="action" value="saveBadge" hidden>';
			echo '<tr>
			<td>ID</td>
			<td><p class="text-center"><input type="number" name="id" class="form-control" value="'.$badgeData['id'].'" readonly></td>
			</tr>';
			echo '<tr>
			<td>Name</td>
			<td><p class="text-center"><input type="text" name="n" class="form-control" value="'.$badgeData['name'].'" ></td>
			</tr>';
			echo '<tr>
			<td>Icon</td>
			<td><p class="text-center"><input type="text" name="i" class="form-control icp icp-auto" value="'.$badgeData['icon'].'" ></td>
			</tr>';
			echo '</tbody></form>';
			echo '</table>';
			echo '<div class="text-center"><button type="submit" form="edit-badge-form" class="btn btn-primary">Save changes</button></div>';
			echo '</div>';
		}
		catch(Exception $e) {
			// Redirect to exception page
			redirect('index.php?p=108&e='.$e->getMessage());
		}
	}

	/*
	 * AdminEditUserBadges
	 * Prints the admin panel edit user badges page
	*/
	public static function AdminEditUserBadges() {
		try {
			// Check if id is set
			if (!isset($_GET['id'])) {
				throw new Exception('Invalid user id');
			}
			// get all badges
			$allBadges = $GLOBALS['db']->fetchAll("SELECT id, name FROM badges");
			// Get user badges
			$userBadges = $GLOBALS['db']->fetchAll('SELECT badge FROM user_badges ub WHERE ub.user = ?', $_GET['id']);
			// Get username
			$username = current($GLOBALS['db']->fetch('SELECT username FROM users WHERE id = ?', $_GET['id']));
			// Print edit user badges stuff
			echo '<div id="wrapper">';
			printAdminSidebar();
			echo '<div id="page-content-wrapper">';
			// Maintenance check
			self::MaintenanceStuff();
			echo '<p align="center"><font size=5><i class="fa fa-certificate"></i>	Edit user badges</font></p>';
			echo '<table class="table table-striped table-hover table-50-center">';
			echo '<tbody><form id="edit-user-badges" action="submit.php" method="POST"><input name="action" value="saveUserBadges" hidden>';
			echo '<tr>
			<td>User</td>
			<td><p class="text-center"><input type="text" name="u" class="form-control" value="'.$username.'" readonly></td>
			</tr>';
			for ($i = 1; $i <= 6; $i++) {
				echo '<tr>
				<td>Badge ' . $i . '</td>
				<td>';
				echo "<select name='b0$i' class='selectpicker' data-width='100%'>";
				foreach ($allBadges as $badge) {
					$selected = "";
					if ($badge["id"] == @$userBadges[$i-1]["badge"])
						$selected = " selected";
					echo "<option value='$badge[id]'$selected>$badge[name]</option>";
				}
				echo '</select></td>
				</tr>';
			}
			echo '</tbody></form>';
			echo '</table>';
			echo '<div class="text-center"><button type="submit" form="edit-user-badges" class="btn btn-primary">Save changes</button></div>';
			echo '</div>';
		}
		catch(Exception $e) {
			// Redirect to exception page
			redirect('index.php?p=108&e='.$e->getMessage());
		}
	}

	/*
	 * AdminBanchoSettings
	 * Prints the admin panel bancho settings page
	*/
	public static function AdminBanchoSettings() {
		// Print stuff
		echo '<div id="wrapper">';
		printAdminSidebar();
		echo '<div id="page-content-wrapper">';
		// Maintenance check
		self::MaintenanceStuff();
		// Print Success if set
		if (isset($_GET['s']) && !empty($_GET['s'])) {
			self::SuccessMessageStaccah($_GET['s']);
		}
		// Print Exception if set
		if (isset($_GET['e']) && !empty($_GET['e'])) {
			self::ExceptionMessageStaccah($_GET['e']);
		}
		// Get values
		$bm = current($GLOBALS['db']->fetch("SELECT value_int FROM bancho_settings WHERE name = 'bancho_maintenance'"));
		$od = current($GLOBALS['db']->fetch("SELECT value_int FROM bancho_settings WHERE name = 'free_direct'"));
		$rm = current($GLOBALS['db']->fetch("SELECT value_int FROM bancho_settings WHERE name = 'restricted_joke'"));
		$mi = current($GLOBALS['db']->fetch("SELECT value_string FROM bancho_settings WHERE name = 'menu_icon'"));
		$lm = current($GLOBALS['db']->fetch("SELECT value_string FROM bancho_settings WHERE name = 'login_messages'"));
		$ln = current($GLOBALS['db']->fetch("SELECT value_string FROM bancho_settings WHERE name = 'login_notification'"));
		$cv = current($GLOBALS['db']->fetch("SELECT value_string FROM bancho_settings WHERE name = 'osu_versions'"));
		$cmd5 = current($GLOBALS['db']->fetch("SELECT value_string FROM bancho_settings WHERE name = 'osu_md5s'"));
		// Default select stuff
		$selected[0] = [1 => '', 2 => ''];
		$selected[1] = [1 => '', 2 => ''];
		$selected[2] = [1 => '', 2 => ''];
		// Checked stuff
		if ($bm == 1) {
			$selected[0][1] = 'selected';
		} else {
			$selected[0][2] = 'selected';
		}
		if ($rm == 1) {
			$selected[1][1] = 'selected';
		} else {
			$selected[1][2] = 'selected';
		}
		if ($od == 1) {
			$selected[2][1] = 'selected';
		} else {
			$selected[2][2] = 'selected';
		}
		echo '<p align="center"><font size=5><i class="fa fa-server"></i>	Bancho settings</font></p>';
		echo '<table class="table table-striped table-hover table-50-center">';
		echo '<tbody><form id="system-settings-form" action="submit.php" method="POST"><input name="action" value="saveBanchoSettings" hidden>';
		echo '<tr>
		<td>Maintenance mode (bancho)</td>
		<td>
		<select name="bm" class="selectpicker" data-width="100%">
		<option value="1" '.$selected[0][1].'>On</option>
		<option value="0" '.$selected[0][2].'>Off</option>
		</select>
		</td>
		</tr>';
		echo '<tr>
		<td>Restricted mode joke</td>
		<td>
		<select name="rm" class="selectpicker" data-width="100%">
		<option value="1" '.$selected[1][1].'>On</option>
		<option value="0" '.$selected[1][2].'>Off</option>
		</select>
		</td>
		</tr>';
		echo '<tr>
		<td>Free osu!direct</td>
		<td>
		<select name="od" class="selectpicker" data-width="100%">
		<option value="1" '.$selected[2][1].'>On</option>
		<option value="0" '.$selected[2][2].'>Off</option>
		</select>
		</td>
		</tr>';
		echo '<tr>
		<td>Menu bottom icon<br>(imageurl|clickurl)</td>
		<td><p class="text-center"><input type="text" value="'.$mi.'" name="mi" class="form-control"></td>
		</tr>';
		echo '<tr>
		<td>Login #osu messages<br>One per line<br>(user|message)</td>
		<td><textarea type="text" name="lm" class="form-control" maxlength="512" style="overflow:auto;resize:vertical;height:100px">'.$lm.'</textarea></td>
		</tr>';
		echo '<tr>
		<td>Login notification</td>
		<td><textarea type="text" name="ln" class="form-control" maxlength="512" style="overflow:auto;resize:vertical;height:100px">'.$ln.'</textarea></td>
		</tr>';
		echo '<tr>
		<td>Supported osu! versions<br>(separated by |)</td>
		<td><p class="text-center"><input type="text" value="'.$cv.'" name="cv" class="form-control"></td>
		</tr>';
		echo '<tr>
		<td>Supported osu!.exe md5s<br>(separated by |)</td>
		<td><p class="text-center"><input type="text" value="'.$cmd5.'" name="cmd5" class="form-control"></td>
		</tr>';
		echo '<tr class="success">
		<td colspan=2><p align="center"><b>Settings are automatically reloaded on Bancho when you press "Save settings".</b> There\'s no need to do <i>!system reload</i> manually anymore.</p></td>
		</tr>';
		echo '</tbody><table>
		<div class="text-center"><button type="submit" class="btn btn-primary">Save settings</button></div></form>';
		echo '</div>';
	}

	/*
	 * AdminLog
	 * Prints the admin log page
	*/
	public static function AdminLog() {
		// TODO: Ask stampa piede COME SI DICHIARANO LE COSTANTY IN PIACCAPPI??
		$pageInterval = 50;

		// Get data
		$first = false;
		if (isset($_GET["from"])) {
			$from = $_GET["from"];
			$first = current($GLOBALS["db"]->fetch("SELECT id FROM rap_logs ORDER BY datetime DESC LIMIT 1")) == $from;
		} else {
			$from = current($GLOBALS["db"]->fetch("SELECT id FROM rap_logs ORDER BY datetime DESC LIMIT 1"));
			$first = true;
		}
		$to = $from-$pageInterval;
		$logs = $GLOBALS['db']->fetchAll('SELECT rap_logs.*, users.username FROM rap_logs LEFT JOIN users ON rap_logs.userid = users.id WHERE rap_logs.id <= ? AND rap_logs.id > ? ORDER BY rap_logs.datetime DESC', [$from, $to]);
		// Print sidebar and template stuff
		echo '<div id="wrapper">';
		printAdminSidebar();
		echo '<div id="page-content-wrapper" style="text-align: left;">';
		// Maintenance check
		self::MaintenanceStuff();
		// Print Success if set
		if (isset($_GET['s']) && !empty($_GET['s'])) {
			self::SuccessMessageStaccah($_GET['s']);
		}
		// Print Exception if set
		if (isset($_GET['e']) && !empty($_GET['e'])) {
			self::ExceptionMessageStaccah($_GET['e']);
		}
		// Header
		echo '<span align="center"><h2><i class="fa fa-calendar"></i>	Admin Log</h2></span>';
		// Main page content here
		echo '<div class="bubbles-container">';
		if (!$logs) {
			printBubble(999, "You", "have reached the end of the life the universe and everything. Now go fuck a donkey.", time()-(43*60), "The Hitchhiker's Guide to the Galaxy");
		} else {
			$lastDay = -1;
			foreach ($logs as $entry) {
				$currentDay = date("z", $entry["datetime"]);
				if ($lastDay != $currentDay)
					echo'<div class="line"><div class="line-text"><span class="label label-primary">' . date("d/m/Y", $entry["datetime"]) . '</span></div></div>';
				printBubble($entry["userid"], $entry["username"], $entry["text"], $entry["datetime"], $entry["through"]);
				$lastDay = $currentDay;
			}
		}
		echo '</div>';
		echo '<br><br><p align="center">';
		if (!$first)
			echo '<a href="index.php?p=116&from=' .($from+$pageInterval) . '">< Prev page</a>';
		if (!$first && $logs)
			echo ' | ';
		if ($logs)
			echo '<a href="index.php?p=116&from=' . $to . '">Next page</a> ></p>';
		// Template end
		echo '</div>';
	}

	/*
	 * HomePage
	 * Prints the homepage
	*/
	public static function HomePage() {
		P::GlobalAlert();
		// Home success message
		$success = ['forgetDone' => 'Done! Your "Stay logged in" tokens have been deleted from the database.'];
		$error = [1 => 'You are already logged in.'];
		if (!empty($_GET['s']) && isset($success[$_GET['s']])) {
			self::SuccessMessage($success[$_GET['s']]);
		}
		if (!empty($_GET['e']) && isset($error[$_GET['e']])) {
			self::ExceptionMessage($error[$_GET['e']]);
		}
		$color = "pink";
		if (mt_rand(0,9) == 0) {
			switch(mt_rand(0,3)) {
				case 0: $color = "red"; break;
				case 1: $color = "blue"; break;
				case 2: $color = "green"; break;
				case 3: $color = "orange"; break;
			}
		}
		echo '<p align="center">
		<object data="images/logos/logo-'.$color.'.svg" type="image/svg+xml" class="animated bounceIn"></object>
		</p>';
		global $isBday;
		if ($isBday) {
			echo '<h1>Happy birthday Ripple!</h1>';
		} else {
			echo '<h1>Welcome to Ripple</h1>';
		}
		// Home alert
		self::HomeAlert();
	}

	/*
	 * UserPage
	 * Print user page for $u user
	 *
	 * @param (int) ($u) ID of user.
	 * @param (int) ($m) Playmode.
	*/
	public static function UserPage($u, $m = -1) {
		global $ScoresConfig;
		global $PlayStyleEnum;

		// Maintenance check
		self::MaintenanceStuff();
		// Global alert
		self::GlobalAlert();
		try {
			$kind = $GLOBALS['db']->fetch('SELECT 1 FROM users WHERE id = ?', [$u]) ? "id" : "username";

			// Check banned status
			$userData = $GLOBALS['db']->fetch("
SELECT
	users_stats.*, users.privileges, users.id as usersuid, users.latest_activity,
	users.silence_end, users.silence_reason, users.register_datetime
FROM users_stats
LEFT JOIN users ON users.id=users_stats.id
WHERE users.$kind = ? LIMIT 1", [$u]);

			if (!$userData) {
				// LISCIAMI LE MELE SUDICIO
				throw new Fava('User not found');
			}

			// Get admin/pending/banned/restricted/visible statuses
			if (!checkLoggedIn()) {
				$imAdmin = false;
			} else {
				$imAdmin = hasPrivilege(Privileges::AdminManageUsers);
			}
			$isPending = (($userData["privileges"] & Privileges::UserPendingVerification) > 0);
			$isBanned = (($userData["privileges"] & Privileges::UserNormal) == 0) && (($userData["privileges"] & Privileges::UserPublic) == 0);
			$isRestricted = (($userData["privileges"] & Privileges::UserNormal) > 0) && (($userData["privileges"] & Privileges::UserPublic) == 0);
			$myUserID = (checkLoggedIn()) ? $_SESSION["userid"] : -1;
			$isVisible = (!$isBanned && !$isRestricted && !$isPending) || $userData["id"] == $myUserID;

			if (!$isVisible) {
				// The user is not visible
				if ($imAdmin) {
					// We are admin, show admin message and print profile
					if ($isPending) {
						echo '<div class="alert alert-warning"><i class="fa fa-exclamation-triangle"></i>	<b>This user has never logged in to Bancho and is pending verification.</b> Only admins can see this profile.</div>';
					} else if ($isBanned) {
						echo '<div class="alert alert-danger"><i class="fa fa-exclamation-triangle"></i>	<b>User banned.</b></div>';
					} else if ($isRestricted) {
						echo '<div class="alert alert-danger"><i class="fa fa-exclamation-triangle"></i>	<b>User restricted.</b></div>';
					}
				} else {
					// We are a normal user, print 404 and die
					throw new Exception('User not found');
				}
			}
			// Get all user stats for all modes and username
			$username = $userData["username"];
			$userID = $userData["usersuid"];
			// Set default modes texts, selected is bolded below
			$modesText = [0 => 'osu!standard', 1 => 'Taiko', 2 => 'Catch the Beat', 3 => 'osu!mania'];
			// Get stats for selected mode
			$m = ($m < 0 || $m > 3 ? $userData['favourite_mode'] : $m);
			$modeForDB = getPlaymodeText($m);
			$modeReadable = getPlaymodeText($m, true);
			// Standard stats
			$rankedScore = $userData['ranked_score_'.$modeForDB];
			$totalScore = $userData['total_score_'.$modeForDB];
			$playCount = $userData['playcount_'.$modeForDB];
			$totalHits = $userData['total_hits_'.$modeForDB];
			$accuracy = $userData['avg_accuracy_'.$modeForDB];
			$replaysWatchedByOthers = $userData['replays_watched_'.$modeForDB];
			$pp = $userData['pp_'.$modeForDB];
			$country = $userData['country'];
			$usernameAka = $userData['username_aka'];
			$level = $userData['level_'.$modeForDB];
			$latestActivity = $userData['latest_activity'];
			$silenceEndTime = $userData['silence_end'];
			$silenceReason = $userData['silence_reason'];

			// Get badges id and icon (max 6 badges)
			$badgeID = [];
			$badgeIcon = [];
			$badgeName = [];

			$badges = $GLOBALS["db"]->fetchAll("SELECT b.id, b.icon, b.name
			FROM user_badges ub
			INNER JOIN badges b ON b.id = ub.badge
			WHERE ub.user = ?", [$userID]);
			foreach ($badges as $key => $badge) {
				$badgeID[$key] = $badge["id"];
				$badgeIcon[$key] = htmlspecialchars($badge['icon']);
				$badgeName[$key] = htmlspecialchars($badge['name']);
				if (empty($badgeIcon[$key])) {
					$badgeIcon[$key] = 0;
				}
				if (empty($badgeName[$key])) {
					$badgeIcon[$key] = '';
				}
			}

			// Set custom badge
			$showCustomBadge = hasPrivilege(Privileges::UserDonor, $userData['id']) && $userData["show_custom_badge"] == 1 && $userData["can_custom_badge"] == 1;
			if ($showCustomBadge) {
				for ($i=0; $i < 6; $i++) {
					if (@$badgeID[$i] == 0) {
						$badgeID[$i] = -1;
						$badgeIcon[$i] = htmlspecialchars($userData["custom_badge_icon"]);
						$badgeName[$i] = "<i>".htmlspecialchars($userData["custom_badge_name"])."</i>";
						break;
					}
				}
			}

			// Make sure that we have at least one score to calculate maximum combo, otherwise maximum combo is 0
			$maximumCombo = $GLOBALS['db']->fetch('SELECT max_combo FROM scores WHERE userid = ? AND play_mode = ? ORDER BY max_combo DESC LIMIT 1', [$userData['id'], $m]);
			if ($maximumCombo) {
				$maximumCombo = current($maximumCombo);
			} else {
				$maximumCombo = 0;
			}
			// Get username style (for random funny stuff lmao)
			if ($silenceEndTime - time() > 0) {
				$userStyle = 'text-decoration: line-through;';
			} else {
				$userStyle = $userData["user_style"];
			}

			// Print API token data for scores retrieval
			APITokens::PrintScript(sprintf('var UserID = %s; var Mode = %s;', $userData["id"], $m));

			// Get top/recent plays for this mode
			$beatmapsTable = ($ScoresConfig["useNewBeatmapsTable"] ? "beatmaps" : "beatmaps_names" );
			$beatmapsField = ($ScoresConfig["useNewBeatmapsTable"] ? "song_name" : "beatmap_name" );
			$orderBy = ($ScoresConfig["enablePP"] ? "pp" : "score" );
			// Bold selected mode text.
			$modesText[$m] = '<b>'.$modesText[$m].'</b>';
			// Get userpage
			$userpageContent = $userData['userpage_content'];

			// seriosuly fuck this shit who the fuck thought it was sane to write this fucking piece
			// of fucking shit like holy titties fuck tits cock the whole code of oldfrontend is absolutely
			// fucked but i still can't believe how FUCKED the code of the user profiles are why are they
			// even called userpages in this fucking code they're supposed to be profiles not pages
			// userpages are the ones with custom data written in bbcode
			// why are userpages in bbcode
			// like
			// markdown is much superior
			// anyway
			// you might wonder why the fuck i am doing the next thing
			// and that is $u used to always be an userid
			// and then changes happened and the validation to check $_GET["u"] was an username or
			// an userid was moved into the userpage() function
			// problem is though
			// i forgot there was another check of more or less the same thing in functions.php
			// (fuck functions.php by the way)
			// and so yeah
			// $u then became either an username or an userid
			// except I didn't know it was used in other places apart from the initial lookup of the user.
			// fuck
			// this
			// gay
			// earth
			// https://www.youtube.com/watch?v=HnrjygAG18o
			// TOOONIGHT IM GONNA HAVE MYSELF A REAL GOOD TIME
			// I FEEL ALIIIVE AH AH AAAH
			// AND THE WORLD
			// IS TURNING INSIDE OUT YEAH
			// I'M FLOATING AROUND IN ECSTASY
			// SO DON'T STOP ME NOW
			// SO DON'T STOP ME NOW
			// CAUSE IM HAVING A GOOD TIME
			// HAVING A GOOD TIME
			// I'M A SUPERSTARE LEAKING THROUGH THE SKYES LIKE A TIGER
			// DEFYING THE LAWS OF GRAVITY'
			// I'M A RACING CAR PASSING BY LIKE LADY GODDIVA
			// I GOTTA GO
			// GO
			// GO
			// THERE'S NO STOPPING ME
			// Now that I filled my whole screen with this comment I can finally procede writing
			// some more shitty code
			// I hope my nonsense has made your day
			// And don't you dare post this on reddit.
			$u = $userData["id"];

			// Friend button
			if (!checkLoggedIn() || $username == $_SESSION['username']) {
				$friendButton = '';
			} else {
				$friendship = getFriendship($_SESSION['username'], $username);
				switch ($friendship) {
					case 1:
						$friendButton = '<div id="friend-button"><a href="submit.php?action=addRemoveFriend&u='.$u.'" type="button" class="btn btn-success"><span class="glyphicon glyphicon-star"></span>	Friend</a></div>';
					break;
					case 2:
						$friendButton = '<div id="friend-button"><a href="submit.php?action=addRemoveFriend&u='.$u.'" type="button" class="btn btn-danger"><span class="glyphicon glyphicon-heart"></span>	Mutual Friend</a></div>';
					break;
					default:
						$friendButton = '<div id="friend-button"><a href="submit.php?action=addRemoveFriend&u='.$u.'" type="button" class="btn btn-primary"><span class="glyphicon glyphicon-plus"></span>	Add as Friend</a></div>';
					break;
				}
			}
			// Get rank
			//$rank = intval(Leaderboard::GetUserRank($u, $modeForDB));
			redisConnect();
			$rank = intval($GLOBALS["redis"]->zrevrank("ripple:leaderboard:".$modeForDB, $u)) + 1;
			// Set rank char (trophy for top 3, # for everyone else)
			if ($rank <= 3) {
				$rankSymbol = '<i class="fa fa-trophy"></i> ';
			} else {
				$rank = sprintf('%02d', $rank);
				$rankSymbol = '#';
			}
			// Silence thing
			if ($silenceEndTime - time() > 0) {
				echo '<div class="alert alert-danger"><i class="fa fa-exclamation-triangle"></i>	<b>'.$username.'</b> can\'t speak in the chat for the next <b>'.timeDifference($silenceEndTime, time(), false).'</b> for the following reason: "<b>'.$silenceReason.'</b>"</div>';
			}
			// Userpage custom stuff
			if (strlen($userpageContent) > 0) {
				// BB Code parser
				require_once 'bbcode.php';
				// Collapse type (if < 500 chars, userpage will be shown)
				if (strlen($userpageContent) <= 500) {
					$ct = 'in';
				} else {
					$ct = 'out';
				}
				// Print userpage content
				echo '<div class="spoiler">
						<div class="panel panel-default">
							<div class="panel-heading">
								<button type="button" class="btn btn-default btn-xs spoiler-trigger" data-toggle="collapse">Expand userpage</button>';
				if (checkLoggedIn() && $username == $_SESSION['username']) {
					echo '	<a href="index.php?p=8" type="button" class="btn btn-default btn-xs"><i>Edit</i></a>';
				}
				echo '</div>
							<div class="panel-collapse collapse '.$ct.'">
								<div class="panel-body">'.bbcode::toHtml($userpageContent, true).'</div>
							</div>
						</div>
					</div>';
			}
			// Userpage header
			echo '<div id="userpage-header">
			<!-- Avatar, username and rank -->
			<p><img id="user-avatar" src="'.URL::Avatar().'/'.$userData["id"].'" height="100" width="100" /></p>
			<p id="username"><div style="display: inline; ' . (!empty($userData["user_color"]) ? "color: $userData[user_color];" : "") . ' font-size: 140%; '.$userStyle.'"><b>';
			if ($country != 'XX') {
				echo '<img src="./images/flags/'.strtolower($country).'.png">	';
			}
			if (isOnline($userData["id"])) {
				echo '<i class="fa fa-circle online-circle"></i>';
			}
			echo $username.'</b></div></p>';
			if ($usernameAka != '') {
				echo '<small><i>A.K.A '.htmlspecialchars($usernameAka).'</i></small>';
			}
			echo '<br><a href="index.php?u='.$u.'&m=0">'.$modesText[0].'</a> | <a href="index.php?u='.$u.'&m=1">'.$modesText[1].'</a> | <a href="index.php?u='.$u.'&m=2">'.$modesText[2].'</a> | <a href="index.php?u='.$u.'&m=3">'.$modesText[3].'</a>';

			echo "<br>";
			if (hasPrivilege(Privileges::AdminManageUsers)) {
				echo '<a href="index.php?p=103&id='.$u.'">Edit user</a> | <a href="index.php?p=110&id='.$u.'">Edit badges</a>';
			}
			if (hasPrivilege(Privileges::AdminBanUsers)) {
				echo ' | <a onclick="sure(\'submit.php?action=banUnbanUser&id='.$u.'\')";>Ban user</a> | <a onclick="sure(\'submit.php?action=restrictUnrestrictUser&id='.$u.'\')";>Restrict user</a>';
			}
			echo "</p>";

			echo '<div id="rank"><font size=5><b> '.$rankSymbol.$rank.'</b></font><br>';
			if ($ScoresConfig["enablePP"] && ($m == 0 || $m == 3)) echo '<b>' . number_format($pp) . ' pp</b>';
			echo $friendButton;
			echo '</div>';
			echo '</div>';
			echo '<div id="userpage-content">
			<div class="col-md-3">';
			// Badges Left colum
			if (@$badgeID[0] > 0 || @$badgeID[0] == -1) {
				echo '<i class="fa '.$badgeIcon[0].' fa-2x"></i><br><b>'.$badgeName[0].'</b><br><br>';
			}
			if (@$badgeID[2] > 0 || @$badgeID[2] == -1) {
				echo '<i class="fa '.$badgeIcon[2].' fa-2x"></i><br><b>'.$badgeName[2].'</b><br><br>';
			}
			if (@$badgeID[4] > 0 || @$badgeID[4] == -1) {
				echo '<i class="fa '.$badgeIcon[4].' fa-2x"></i><br><b>'.$badgeName[4].'</b><br><br>';
			}
			echo '</div>
			<div class="col-md-3">';
			// Badges Right column
			if (@$badgeID[1] > 0 || @$badgeID[1] == -1) {
				echo '<i class="fa '.$badgeIcon[1].' fa-2x"></i><br><b>'.$badgeName[1].'</b><br><br>';
			}
			if (@$badgeID[3] > 0 || @$badgeID[3] == -1) {
				echo '<i class="fa '.$badgeIcon[3].' fa-2x"></i><br><b>'.$badgeName[3].'</b><br><br>';
			}
			if (@$badgeID[5] > 0 || @$badgeID[5] == -1) {
				echo '<i class="fa '.$badgeIcon[5].' fa-2x"></i><br><b>'.$badgeName[5].'</b><br><br>';
			}
			// Calculate required score for our level
			$reqScore = getRequiredScoreForLevel($level);
			$reqScoreNext = getRequiredScoreForLevel($level + 1);
			$scoreDiff = $reqScoreNext - $reqScore;
			$ourScore = $reqScoreNext - $totalScore;
			$percText = 100 - floor((100 * $ourScore) / ($scoreDiff + 1)); // Text percentage, real one
			if ($percText < 10) {
				$percBar = 10;
			} else {
				$percBar = $percText;
			} // Progressbar percentage, minimum 10 or it's glitched
			echo '</div><div class="col-md-6 nopadding">
			<!-- Stats -->
			<b>Level '.$level.'</b>
			<div class="progress">
			<div class="progress-bar" role="progressbar" aria-valuenow="'.$percBar.'" aria-valuemin="10" aria-valuemax="100" style="width:'.$percBar.'%">'.$percText.'%</div>
			</div>
			<table>
			<tr>
			<td id="stats-name">Ranked Score</td>
			<td id="stats-value"><b>'.number_format($rankedScore).'</b></td>
			</tr>
			<tr>
			<td id="stats-name">Total score</td>
			<td id="stats-value">'.number_format($totalScore).'</td>
			<tr>
			<td id="stats-name">Play Count</td>
			<td id="stats-value"><b>'.number_format($playCount).'</b></td>
			</tr>
			<tr>
			<td id="stats-name">Hit Accuracy</td>
			<td id="stats-value"><b>'.(is_numeric($accuracy) ? accuracy($accuracy) : '0.00').'%</b></td>
			</tr>
			<tr>
			<td id="stats-name">Total Hits</td>
			<td id="stats-value"><b>'.number_format($totalHits).'</b></td>
			</tr>
			<tr>
			<td id="stats-name">Maximum Combo</td>
			<td id="stats-value"><b>'.number_format($maximumCombo).'</b></td>
			</tr>
			<tr>
				<td id="stats-name">Replays watched by others</td>
				<td id="stats-value"><b>'.number_format($replaysWatchedByOthers).'</b></td>
			</tr>';
			echo '<tr><td id="stats-name">From</td><td id="stats-value"><b>'.countryCodeToReadable($country).'</b></td></tr>';
			// Show latest activity only if it's valid
			if ($latestActivity != 0) {
				echo '<tr>
				<td id="stats-name">Latest activity</td>
				<td id="stats-value"><b>'.timeDifference(time(), $latestActivity).'</b></td>
			</tr>';
			}
			echo '<tr>
				<td id="stats-name">Registered</td>
				<td id="stats-value"><b>'.timeDifference(time(), $userData["register_datetime"]).'</b></td>
			</tr>';
			// Playstyle
			if ($userData['play_style'] > 0) {
				echo '<tr><td id="stats-name">Play style</td><td id="stats-value"><b>'.BwToString($userData['play_style'], $PlayStyleEnum).'</b></td></tr>';
			}

			if ($ScoresConfig["enablePP"] && ($m == 0 || $m == 3))
				$scoringName = "PP";
			else
				$scoringName = "Score";

			echo '</table>
			</div>
			</div>
			<div id ="userpage-plays">';

			echo '<table class="table" id="best-plays-table">
			<tr><th class="text-left"><i class="fa fa-trophy"></i>	Top plays</th><th class="text-right">' . $scoringName . '</th></tr>';
			echo '</table>';
			echo '<button type="button" class="btn btn-default load-more-user-scores" data-rel="best" disabled>Show me more!</button>';

			// brbr it's so cold
			echo '<br><br><br>';

			// print table skeleton
			echo '<table class="table" id="recent-plays-table">
			<tr><th class="text-left"><i class="fa fa-clock-o"></i>	Recent plays</th><th class="text-right">' . $scoringName . '</th></tr>';
			echo '</table>';
			echo '<button type="button" class="btn btn-default load-more-user-scores" data-rel="recent" disabled>Show me more!</button></div>';
		}
		catch(Exception $e) {
			echo '<br><div class="alert alert-danger" role="alert"><i class="fa fa-exclamation-triangle"></i>	<b>'.$e->getMessage().'</b></div>';
		}
	}

	/*
	 * AboutPage
	 * Prints the about page.
	*/
	public static function AboutPage() {
		// Maintenance check
		self::MaintenanceStuff();
		// Global alert
		self::GlobalAlert();
		echo file_get_contents('./html_static/about.html');
	}

	/*
	 * StopSign
	 * For preventing future multiaccounters.
	*/
	public static function StopSign() {
		// Maintenance check
		self::MaintenanceStuff();
		// Global alert
		self::GlobalAlert();
		if (!isset($_GET["user"])) {
			self::ExceptionMessage("lol");
			return;
		}
		echo str_replace("{}", htmlspecialchars($_GET["user"]), file_get_contents('./html_static/elmo_stop.html'));
	}

	/*
	 * RulesPage
	 * Prints the rules page.
	*/
	public static function RulesPage() {
		// Maintenance check
		self::MaintenanceStuff();
		// Global alert
		self::GlobalAlert();
		$doc = $GLOBALS['db']->fetch('SELECT doc_contents FROM docs WHERE is_rule = "1" LIMIT 1');
		if (!$doc) {
			self::ExceptionMessage('Looks like the admins forgot to set a rules page in their documentation file listing. Which means, anarchy reigns here!');
			return;
		}
		require_once 'parsedown.php';
		$p = new Parsedown();
		echo "<div class='text-left'>".$p->text($doc['doc_contents']).'</div>';
	}

	/*
	 * ChangelogPage
	 * Prints the Changelog page.
	*/
	public static function Changelogpage() {
		// Maintenance check
		self::MaintenanceStuff();
		// Global alert
		self::GlobalAlert();
		// Changelog
		getChangelog();
	}

	/*
	 * ExceptionMessage
	 * Display an error alert with a custom message.
	 *
	 * @param (string) ($e) The custom message (exception) to display.
	*/
	public static function ExceptionMessage($e, $ret = false) {
		$p = '<div class="container alert alert-danger" role="alert" style="width: 100%;"><p align="center"><b>An error occurred:<br></b>'.$e.'</p></div>';
		if ($ret) {
			return $p;
		}
		echo $p;
	}
	public static function ExceptionMessageStaccah($s, $ret = false) {
		return P::ExceptionMessage(htmlspecialchars($s), $ret);
	}

	/*
	 * SuccessMessage
	 * Display a success alert with a custom message.
	 *
	 * @param (string) ($s) The custom message to display.
	*/
	public static function SuccessMessage($s, $ret = false) {
		$p = '<div class="container alert alert-success" role="alert" style="width:100%;"><p align="center">'.$s.'</p></div>';
		if ($ret) {
			return $p;
		}
		echo $p;
	}
	public static function SuccessMessageStaccah($s, $ret = false) {
		return P::SuccessMessage(htmlspecialchars($s), $ret);
	}

	/*
	 * Messages
	 * Displays success/error messages from $_SESSION[errors] or $_SESSION[successes]
	 * (aka success/error messages set with addError and addSuccess).
	 *
	 * @return bool Whether something was printed.
	 */
	public static function Messages() {
		$p = false;
		if (isset($_SESSION['errors']) && is_array($_SESSION['errors'])) {
			foreach ($_SESSION['errors'] as $err) {
				self::ExceptionMessage($err);
				$p = true;
			}
			$_SESSION['errors'] = array();
		}
		if (isset($_SESSION['successes']) && is_array($_SESSION['successes'])) {
			foreach ($_SESSION['successes'] as $s) {
				self::SuccessMessage($s);
				$p = true;
			}
			$_SESSION['successes'] = array();
		}
		return $p;
	}

	/*
	 * LoggedInAlert
	 * Display a message to the user that he's already logged in.
	 * Printed when a logged in user tries to view a guest only page.
	*/
	public static function LoggedInAlert() {
		echo '<div class="alert alert-warning" role="alert">You are already logged in.</i></div>';
	}

	/*
	 * RegisterPage
	 * Prints the register page.
	*/
	public static function RegisterPage() {
		// Maintenance check
		self::MaintenanceStuff();
		// Global alert
		self::GlobalAlert();
		// Registration enabled check
		if (!checkRegistrationsEnabled()) {
			// Registrations are disabled
			self::ExceptionMessage('<b>Registrations are currently disabled.</b>');
			die();
		}
		echo '<br><div id="narrow-content"><h1><i class="fa fa-plus-circle"></i>	Sign up</h1>';

		$ip = getIp();

		// Multiacc warning checks
		// Exact IP
		$multiIP = multiaccCheckIP($ip);
		// "y" cookie
		$multiToken = multiaccCheckToken();
		$multiThing = $multiIP === FALSE ? $multiToken : $multiIP;

		// Show multiacc warning if ip or token match
		$errors = self::Messages();
		if (($multiIP !== FALSE || $multiToken !== FALSE)) {
			if (@$_GET["iseethestopsign"] == "1") {
				echo '<div class="container alert alert-warning" role="alert" style="width: 100%;"><p align="center">Since I love delivering completely random quotes:<br><i>if you keep going the way you are now... you\'re gonna have a bad time.</i></p></div>';
			} else {
				$multiName = $multiThing["username"];
				redirect("/index.php?p=41&user=" . $multiName);
			}
		} else if (!$errors) {
			// Print default warning message if we have no exception/success/multiacc warn
			echo '<p>Please fill every field in order to sign up.<br>';
		}
		echo '<div class="alert alert-danger animated shake" role="alert"><b><i class="fa fa-gavel"></i>	Please read the <a href="index.php?p=23" target="_blank">rules</a> before creating an account.</b></div>
		<a href="index.php?p=16&id=1" target="_blank">Need some help?</a></p>';
		// Print register form
		echo '	<form action="submit.php" method="POST">
		<input name="action" value="register" hidden>
		<div class="input-group"><span class="input-group-addon" id="basic-addon1"><span class="glyphicon glyphicon-user" max-width="25%"></span></span><input type="text" name="u" required class="form-control" placeholder="Username" aria-describedby="basic-addon1"></div><p style="line-height: 15px"></p>
		<div class="input-group"><span class="input-group-addon" id="basic-addon1"><span class="glyphicon glyphicon-lock" max-width="25%"></span></span><input type="password" name="p1" required class="form-control" placeholder="Password" aria-describedby="basic-addon1"></div><p style="line-height: 15px"></p>
		<div class="input-group"><span class="input-group-addon" id="basic-addon1"><span class="glyphicon glyphicon-lock" max-width="25%"></span></span><input type="password" name="p2" required class="form-control" placeholder="Repeat Password" aria-describedby="basic-addon1"></div><p style="line-height: 15px"></p>
		<div class="input-group"><span class="input-group-addon" id="basic-addon1"><span class="glyphicon glyphicon-envelope" max-width="25%"></span></span><input type="text" name="e" required class="form-control" placeholder="Email" aria-describedby="basic-addon1"></div><p style="line-height: 15px"></p>
		<br>
		<div class="g-recaptcha" style="padding-left:25%;" data-sitekey="6LdGziUTAAAAAKz2wTjAmKkgYsj329N8ohb_A4Qt"></div>
		<hr>
		<button type="submit" class="btn btn-primary">Sign up!</button>
		</form>
		';
	}

	/*
	 * ChangePasswordPage
	 * Prints the change password page.
	*/
	public static function ChangePasswordPage() {
		// Maintenance check
		self::MaintenanceStuff();
		// Global alert
		self::GlobalAlert();
		echo '<div id="narrow-content"><h1><i class="fa fa-lock"></i>	Change password</h1>';
		// Print messages
		self::Messages();
		// Print default message if we have no exception/success
		if (!isset($_GET['e']) && !isset($_GET['s'])) {
			echo '<p>Fill the form with your existing and new desired password.</p>';
		}
		// Print change password form
		echo '<form action="submit.php" method="POST">
		<input name="action" value="changePassword" hidden>
		<div class="input-group"><span class="input-group-addon" id="basic-addon1"><span class="glyphicon glyphicon-lock" max-width="25%"></span></span><input type="password" name="pold" required class="form-control" placeholder="Current password" aria-describedby="basic-addon1"></div><p style="line-height: 15px"></p>
		<div class="input-group"><span class="input-group-addon" id="basic-addon1"><span class="glyphicon glyphicon-lock" max-width="25%"></span></span><input type="password" name="p1" required class="form-control" placeholder="New password" aria-describedby="basic-addon1"></div><p style="line-height: 15px"></p>
		<div class="input-group"><span class="input-group-addon" id="basic-addon1"><span class="glyphicon glyphicon-lock" max-width="25%"></span></span><input type="password" name="p2" required class="form-control" placeholder="Repeat new password" aria-describedby="basic-addon1"></div><p style="line-height: 15px"></p>
		<button type="submit" class="btn btn-primary">Change password</button>
		</form>
		</div>';
	}

	/*
	 * userSettingsPage
	 * Prints the user settings page.
	*/
	public static function userSettingsPage() {
		global $PlayStyleEnum;
		// Maintenance check
		self::MaintenanceStuff();
		// Global alert
		self::GlobalAlert();
		// Get user settings data
		$data = $GLOBALS['db']->fetch('SELECT * FROM users_stats WHERE id = ? LIMIT 1', $_SESSION['userid']);
		// Title
		echo '<div id="narrow-content"><h1><i class="fa fa-cog"></i>	User settings</h1>';
		// Print Exception if set
		$exceptions = ['Nice troll.', 'You can\'t edit your settings while you\'re restricted.'];
		if (isset($_GET['e']) && isset($exceptions[$_GET['e']])) {
			self::ExceptionMessage($exceptions[$_GET['e']]);
		}
		// Print Success if set
		if (isset($_GET['s']) && $_GET['s'] == 'ok') {
			self::SuccessMessage('User settings saved!');
		}
		// Print default message if we have no exception/success
		if (!isset($_GET['e']) && !isset($_GET['s'])) {
			echo '<p>You can edit your account settings here.</p>';
		}

		// Default select stuff
		$selected[1] = [0 => '', 1 => ''];
		$selected[2] = [0 => '', 1 => ''];

		$selected[1][isset($_COOKIE['st']) && $_COOKIE['st'] == 1] = 'selected';
		$selected[2][$data['show_custom_badge']] = 'selected';

		// Howl is cool so he does it in his own way
		$mode = $data['favourite_mode'];
		$cj = function ($index) use ($mode) {
			$r = "value='$index'";
			if ($index == $mode) {
				return $r.' selected';
			}

			return $r.'';
		};

		// Print form
		echo '<form action="submit.php" method="POST">
		<input name="action" value="saveUserSettings" hidden>
		<div class="input-group" style="width:100%">
			<span class="input-group-addon" id="basic-addon1" style="width:40%">Safe page title</span>
			<select name="st" class="selectpicker" data-width="100%">
				<option value="1" '.$selected[1][1].'>Yes</option>
				<option value="0" '.$selected[1][0].'>No</option>
			</select>
		</div>
		<p style="line-height: 15px"></p>
		<div class="input-group" style="width:100%">
			<span class="input-group-addon" id="basic-addon4" style="width:40%">Favourite gamemode</span>
			<select name="mode" class="selectpicker" data-width="100%">
				<option '.$cj(0).'>osu! Standard</option>
				<option '.$cj(1).'>Taiko</option>
				<option '.$cj(2).'>Catch the Beat</option>
				<option '.$cj(3).'>osu!mania</option>
			</select>
		</div>
		<p style="line-height: 15px"></p>
		<div class="input-group" style="width:100%">
			<span class="input-group-addon" id="basic-addon2" style="width:40%">Username colour</span>
			<input type="text" name="c" class="form-control colorpicker" value="'.$data['user_color'].'" placeholder="HEX/Html color" aria-describedby="basic-addon2" spellcheck="false">
		</div>
		<p style="line-height: 15px"></p>
		<div class="input-group" style="width:100%">
			<span class="input-group-addon" id="basic-addon3" style="width:40%">A.K.A</span>
			<input type="text" name="aka" class="form-control" value="'.htmlspecialchars($data['username_aka']).'" placeholder="Alternative username (not for login)" aria-describedby="basic-addon3" spellcheck="false">
		</div>';

		if (hasPrivilege(Privileges::UserDonor)) {
			echo '<p style="line-height: 15px"></p>
			<div class="input-group" style="width:100%">
				<span class="input-group-addon" id="basic-addon0" style="width:40%">Show custom badge</span>
				<select name="showCustomBadge" class="selectpicker" data-width="100%">
					<option value="1" '.$selected[2][1].'>Yes</option>
					<option value="0" '.$selected[2][0].'>No</option>
				</select>
			</div>';
		}
		echo '<p style="line-height: 15px"></p><hr>';
		if (hasPrivilege(Privileges::UserDonor)) {
			echo '<h3>Custom Badge</h3>';
			if ($data["can_custom_badge"] == 0) {
				echo '<div class="alert alert-danger">
					<i class="fa fa-exclamation-triangle"></i>
					Due to an incorrect use of custom badges, we\'ve <b>revoked your ability to create custom badges.</b>
				</div>';
			} else {
				echo '
				<div class="alert alert-warning">
					<i class="fa fa-exclamation-triangle"></i>
					<b>Do not use offensive badges and do not pretend to be someone else with your badge.</b> If you abuse the badges system, you\'ll be <b>silenced</b> and you won\'t be able to <b>edit your custom badge</b> anymore.
				</div>
				<div class="row">
					<div class="col-md-6">
						<i id="badge-icon" class="fa '.htmlspecialchars($data["custom_badge_icon"]).' fa-2x"></i>
						<br>
						<b><span id="badge-name">'.htmlspecialchars($data["custom_badge_name"]).'</span></b>
					</div>
					<div class="col-md-6" style="text-align: left;">
						<input id="badge-icon-input" type="text" placeholder="Icon" name="badgeIcon" data-placement="bottomLeft" class="form-control icp icp-auto" value="'.htmlspecialchars($data["custom_badge_icon"]).'" maxlength="32">
						<p style="line-height: 15px"></p>
						<input id="badge-name-input" type="text" placeholder="Name" name="badgeName" class="form-control" value="'.htmlspecialchars($data["custom_badge_name"]).'" maxlength="24">
						<p style="line-height: 15px"></p>
					</div>
				</div>';
			}
			echo '<p style="line-height: 15px"></p>
				<hr>';
		}

		echo '<h3>Playstyle</h3>
		<div>
		';
		// Display playstyle checkboxes
		$playstyle = $data['play_style'];
		foreach ($PlayStyleEnum as $k => $v) {
			echo "
			<label style='font-weight: normal;'><input type='checkbox' name='ps_$k' value='1' ".($playstyle & $v ? 'checked' : '')."> $k</label><br>";
		}
		echo '
		</div>
		<p style="line-height: 15px"></p>
		<button type="submit" class="btn btn-primary">Save settings</button>
		</form>
		</div>';
	}

	/*
	 * ChangeAvatarPage
	 * Prints the change avatar page.
	*/
	public static function ChangeAvatarPage() {
		// Maintenance check
		self::MaintenanceStuff();
		// Global alert
		self::GlobalAlert();
		// Title
		echo '<div id="narrow-content"><h1><i class="fa fa-picture-o"></i>	Change avatar</h1>';
		// Print Exception if set
		$exceptions = ['Nice troll.', 'That file is not a valid image.', 'Invalid file format. Supported extensions are .png, .jpg and .jpeg', 'The file is too large. Maximum file size is 1MB.', 'Error while uploading avatar.', "You can't change your avatar while you're restricted."];
		if (isset($_GET['e']) && isset($exceptions[$_GET['e']])) {
			self::ExceptionMessage($exceptions[$_GET['e']]);
		}
		// Print Success if set
		if (isset($_GET['s']) && $_GET['s'] == 'ok') {
			self::SuccessMessage('Avatar changed!');
		}
		// Print default message if we have no exception/success
		if (!isset($_GET['e']) && !isset($_GET['s'])) {
			echo '<p>Give a nice touch to your profile with a custom avatar!<br></p>';
		}
		// Print form
		echo '
		<b>Current avatar:</b><br><img src="'.URL::Avatar().'/'.getUserID($_SESSION['username']).'" height="100" width="100"/>
		<p style="line-height: 15px"></p>
		<form action="submit.php" method="POST" enctype="multipart/form-data">
		<input name="action" value="changeAvatar" hidden>
		<p align="center"><input type="file" name="file"></p>
		<i>Max size: 1MB<br>
		.jpg, .jpeg or <b>.png (recommended)</b><br>
		Recommended size: 100x100</i>
		<p style="line-height: 15px"></p>
		<button type="submit" class="btn btn-primary">Change avatar</button>
		</form>
		</div>';
	}

	/*
	 * UserpageEditorPage
	 * Prints the userpage editor page.
	*/
	public static function UserpageEditorPage() {
		// Maintenance check
		self::MaintenanceStuff();
		// Global alert
		self::GlobalAlert();
		// Get userpage content from db
		$content = $GLOBALS['db']->fetch('SELECT userpage_content FROM users_stats WHERE username = ?', $_SESSION['username']);
		$userpageContent = htmlspecialchars(current(($content === false ? ['t' => ''] : $content)));
		// Title
		echo '<h1><i class="fa fa-pencil"></i>	Userpage</h1>';
		// Print Exception if set
		$exceptions = ['Nice troll.', "Your userpage <b>can't be longer than 1500 characters</b> (bb code syntax included)", "You can't edit your userpage while you're restricted."];
		if (isset($_GET['e']) && isset($exceptions[$_GET['e']])) {
			self::ExceptionMessage($exceptions[$_GET['e']]);
		}
		// Print Success if set
		if (isset($_GET['s']) && $_GET['s'] == 'ok') {
			self::SuccessMessage('Userpage saved!');
		}
		// Print default message if we have no exception/success
		if (!isset($_GET['e']) && !isset($_GET['s'])) {
			echo '<p>Introduce yourself here! <i>(max 1500 chars)</i></p>';
		}
		// Print form
		echo '<form action="submit.php" method="POST">
		<input name="action" value="saveUserpage" hidden>
		<p align="center"><textarea name="c" class="sceditor" style="width:700px; height:400px;">'.$userpageContent.'</textarea></p>
		<p style="line-height: 15px"></p>
		<button type="submit" class="btn btn-primary">Save userpage</button>
		<button type="submit" class="btn btn-success" name="view" value="1">Save and view userpage</a>
		</form>
		';
	}

	/*
	 * PasswordRecovery - print the page to recover your password if you lost it.
	*/
	public static function PasswordRecovery() {
		// Maintenance check
		self::MaintenanceStuff();
		// Global alert
		self::GlobalAlert();
		echo '<div id="narrow-content" style="width:500px"><h1><i class="fa fa-exclamation-circle"></i> Recover your password</h1>';
		// Print Exception if set and in array.
		$exceptions = ['Nice troll.', "That user doesn't exist.", "You are banned from Ripple. We won't let you come back in."];
		if (isset($_GET['e']) && isset($exceptions[$_GET['e']])) {
			self::ExceptionMessage($exceptions[$_GET['e']]);
		}
		if (isset($_GET['s'])) {
			self::SuccessMessage('You should have received an email containing instructions on how to recover your Ripple account.');
		}
		if (checkLoggedIn()) {
			echo 'What are you doing here? You\'re already logged in, you moron!<br>';
			echo 'If you really want to fake that you\'ve lost your password, you should at the very least log out of Ripple, you know.';
		} else {
			echo '<p>Let\'s get some things straight. We can only help you if you DID put your actual email address when you signed up. If you didn\'t, you\'re screwed. Hope to know the admins well enough to tell them to change the password for you, otherwise your account is now dead.</p><br>
			<form action="submit.php" method="POST">
			<input name="action" value="recoverPassword" hidden>
			<div class="input-group"><span class="input-group-addon" id="basic-addon1"><span class="fa fa-user" max-width="25%"></span></span><input type="text" name="username" required class="form-control" placeholder="Type your username." aria-describedby="basic-addon1"></div><p style="line-height: 15px"></p>
			<button type="submit" class="btn btn-primary">Recover my password!</button>
			</form></div>';
		}
	}

	/*
	 * MaintenanceAlert
	 * Prints the maintenance alert and die if we are normal users
	 * Prints the maintenance alert and keep printing the page if we are mod/admin
	*/
	public static function MaintenanceAlert() {
		try {
			// Check if we are logged in
			if (!checkLoggedIn()) {
				throw new Exception();
			}
			// Check our rank
			if (!hasPrivilege(Privileges::AdminAccessRAP)) {
				throw new Exception();
			}
			// Mod/admin, show alert and continue
			echo '<div class="alert alert-warning" role="alert"><p align="center"><i class="fa fa-cog fa-spin"></i>	Ripple\'s website is in <b>maintenance mode</b>. Only moderators and administrators have access to the full website.</p></div>';
		}
		catch(Exception $e) {
			// Normal user, show alert and die
			echo '<div class="alert alert-warning" role="alert"><p align="center"><i class="fa fa-cog fa-spin"></i>	Ripple\'s website is in <b>maintenance mode</b>. We are working for you, <b>please come back later.</b></p></div>';
			die();
		}
	}

	/*
	 * GameMaintenanceAlert
	 * Prints the game maintenance alert
	*/
	public static function GameMaintenanceAlert() {
		try {
			// Check if we are logged in
			if (!checkLoggedIn()) {
				throw new Exception();
			}
			// Check our rank
			if (!hasPrivilege(Privileges::AdminAccessRAP)) {
				throw new Exception();
			}
			// Mod/admin, show alert and continue
			echo '<div class="alert alert-danger" role="alert"><p align="center"><i class="fa fa-cog fa-spin"></i>	Ripple\'s score system is in <b>maintenance mode</b>. <u>Your scores won\'t be saved until maintenance ends.</u><br><b>Make sure to disable game maintenance mode from the admin control panel as soon as possible!</b></p></div>';
		}
		catch(Exception $e) {
			// Normal user, show alert and die
			echo '<div class="alert alert-danger" role="alert"><p align="center"><i class="fa fa-cog fa-spin"></i>	Ripple\'s score system is in <b>maintenance mode</b>. <u>Your scores won\'t be saved until maintenance ends.</u></b></p></div>';
		}
	}

	/*
	 * BanchoMaintenance
	 * Prints the game maintenance alert
	*/
	public static function BanchoMaintenanceAlert() {
		try {
			// Check if we are logged in
			if (!checkLoggedIn()) {
				throw new Exception();
			}
			// Check our rank
			if (!hasPrivilege(Privileges::AdminAccessRAP)) {
				throw new Exception();
			}
			// Mod/admin, show alert and continue
			echo '<div class="alert alert-danger" role="alert"><p align="center"><i class="fa fa-server"></i>	Ripple\'s Bancho server is in maintenance mode. You can\'t play on Ripple right now. Try again later.<br><b>Make sure to disable game maintenance mode from the admin control panel as soon as possible!</b></p></div>';
		}
		catch(Exception $e) {
			// Normal user, show alert and die
			echo '<div class="alert alert-danger" role="alert"><p align="center"><i class="fa fa-server"></i>	Ripple\'s Bancho server is in maintenance mode. You can\'t play on Ripple right now. Try again later.</p></div>';
		}
	}

	/*
	 * MaintenanceStuff
	 * Prints website/game maintenance alerts
	*/
	public static function MaintenanceStuff() {
		// Check Bancho maintenance
		if (checkBanchoMaintenance()) {
			self::BanchoMaintenanceAlert();
		}
		// Game maintenance check
		if (checkGameMaintenance()) {
			self::GameMaintenanceAlert();
		}
		// Check website maintenance
		if (checkWebsiteMaintenance()) {
			self::MaintenanceAlert();
		}
	}

	/*
	 * GlobalAlert
	 * Prints the global alert (only if not empty)
	*/
	public static function GlobalAlert() {
		$m = current($GLOBALS['db']->fetch("SELECT value_string FROM system_settings WHERE name = 'website_global_alert'"));
		if ($m != '') {
			echo '<div class="alert alert-warning" role="alert"><p align="center">'.$m.'</p></div>';
		}
		self::RestrictedAlert();
	}

	/*
	 * HomeAlert
	 * Prints the home alert (only if not empty)
	*/
	public static function HomeAlert() {
		$m = current($GLOBALS['db']->fetch("SELECT value_string FROM system_settings WHERE name = 'website_home_alert'"));
		if ($m != '') {
			echo '<div class="alert alert-warning" role="alert"><p align="center">'.$m.'</p></div>';
		}
	}

	/*
	 * FriendlistPage
	 * Prints the friendlist page.
	*/
	public static function FriendlistPage() {
		// Maintenance check
		self::MaintenanceStuff();
		// Global alert
		self::GlobalAlert();
		// Get user friends
		$ourID = getUserID($_SESSION['username']);
		$friends = $GLOBALS['db']->fetchAll('
		SELECT user2, users.username
		FROM users_relationships
		LEFT JOIN users ON users_relationships.user2 = users.id
		WHERE user1 = ? AND users.privileges & 1 > 0', [$ourID]);
		// Title and header message
		echo '<h1><i class="fa fa-star"></i>	Friends</h1>';
		if (count($friends) == 0) {
			echo '<b>You don\'t have any friends.</b> You can add someone to your friends list<br>by clicking the <b>"Add as friend"</b> button on someones\'s profile.<br>You can add friends from the game client too.';
		} else {
			// Friendlist
			echo '<table class="table table-striped table-hover table-50-center">
			<thead>
			<tr><th class="text-center">Username</th><th class="text-center">Mutual</th></tr>
			</thead>
			<tbody>';
			// Loop through every friend and output its username and mutual status
			foreach ($friends as $friend) {
				$uname = $friend['username'];
				$mutualIcon = ($friend['user2'] == 999 || getFriendship($friend['user2'], $ourID, true) == 2) ? '<i class="fa fa-heart"></i>' : '';
				echo '<tr><td><div align="center"><a href="index.php?u='.$friend['user2'].'">'.$uname.'</a></div></td><td><div align="center">'.$mutualIcon.'</div></td></tr>';
			}
			echo '</tbody></table>';
		}
	}

	/*
	 * AdminRankRequests
	 * Prints the admin rank requests
	*/
	public static function AdminRankRequests() {
		global $ScoresConfig;
		// Get data
		$rankRequestsToday = $GLOBALS["db"]->fetch("SELECT COUNT(*) AS count FROM rank_requests WHERE time > ? LIMIT ".$ScoresConfig["rankRequestsQueueSize"], [time()-(24*3600)]);
		$rankRequests = $GLOBALS["db"]->fetchAll("SELECT rank_requests.*, users.username FROM rank_requests LEFT JOIN users ON rank_requests.userid = users.id WHERE time > ? ORDER BY id DESC LIMIT ".$ScoresConfig["rankRequestsQueueSize"], [time()-(24*3600)]);
		// Print sidebar and template stuff
		echo '<div id="wrapper">';
		printAdminSidebar();
		echo '<div id="page-content-wrapper">';
		// Maintenance check
		self::MaintenanceStuff();
		// Print Success if set
		if (isset($_GET['s']) && !empty($_GET['s'])) {
			self::SuccessMessageStaccah($_GET['s']);
		}
		// Print Exception if set
		if (isset($_GET['e']) && !empty($_GET['e'])) {
			self::ExceptionMessageStaccah($_GET['e']);
		}
		// Header
		echo '<span align="center"><h2><i class="fa fa-music"></i>	Beatmap rank requests</h2></span>';
		// Main page content here
		echo '<div class="page-content-wrapper">';
		//echo '<div style="width: 50%; margin-left: 25%;" class="alert alert-info" role="alert"><i class="fa fa-info-circle"></i>	Only the requests made in the past 24 hours are shown. <b>Make sure to load every difficulty in-game before ranking a map.</b><br><i>(We\'ll add a system that does it automatically soonTM)</i></div>';
		echo '<hr>
		<h2 style="display: inline;">'.$rankRequestsToday["count"].'</h2><h3 style="display: inline;">/'.$ScoresConfig["rankRequestsQueueSize"].'</h3><br><h4>requests submitted today</h4>
		<hr>';
		echo '<table class="table table-striped table-hover" style="width: 94%; margin-left: 3%;">
		<thead>
		<tr><th><i class="fa fa-music"></i>	ID</th><th>Artist & song</th><th>Difficulties</th><th>Mode</th><th>From</th><th>When</th><th class="text-center">Actions</th></tr>
		</thead>';
		echo '<tbody>';
		foreach ($rankRequests as $req) {
			$criteria = $req["type"] == "s" ? "beatmapset_id" : "beatmap_id";
			$b = $GLOBALS["db"]->fetch("SELECT beatmapset_id, song_name, ranked FROM beatmaps WHERE ".$criteria." = ? LIMIT 1", [$req["bid"]]);

			if ($b) {
				$matches = [];
				if (preg_match("/(.+)(\[.+\])/i", $b["song_name"], $matches)) {
					$song = $matches[1];
				} else {
					$song = "Wat";
				}
			} else {
				$song = "Unknown";
			}

			if ($req["type"] == "s")
				$bsid = $req["bid"];
			else
				$bsid = $b ? $b["beatmapset_id"] : 0;

			$today = !($req["time"] < time()-86400);
			$beatmaps = $GLOBALS["db"]->fetchAll("SELECT song_name, beatmap_id, ranked, difficulty_std, difficulty_taiko, difficulty_ctb, difficulty_mania FROM beatmaps WHERE beatmapset_id = ? LIMIT 15", [$bsid]);
			$diffs = "";
			$allUnranked = true;
			$forceParam = "1";
			$modes = [];
			foreach ($beatmaps as $beatmap) {
				$icon = ($beatmap["ranked"] >= 2) ? "check" : "times";
				$name = htmlspecialchars("$beatmap[song_name] ($beatmap[beatmap_id])");
				$diffs .= "<a href='#' data-toggle='popover' data-placement='bottom' data-content=\"$name\" data-trigger='hover'>";
				$diffs .= "<i class='fa fa-$icon'></i>";
				$diffs .= "</a>";
				if ($beatmap["difficulty_std"] > 0 && !in_array("std", $modes)) {
					$modes[] = "std";
				} else if ($beatmap["difficulty_std"] == 0) {
					if ($beatmap["difficulty_taiko"] > 0 && !in_array("taiko", $modes)) {
						$modes[] = "taiko";
					} else if ($beatmap["difficulty_ctb"] > 0 && !in_array("ctb", $modes)) {
						$modes[] = "ctb";
					} else if ($beatmap["difficulty_mania"] > 0 && !in_array("mania", $modes)) {
						$modes[] = "mania";
					}
				}

				if ($beatmap["ranked"] >= 2) {
					$allUnranked = false;
					$forceParam = "0";
				}
			}

			$modes = implode(", ", $modes);

			if (count($beatmaps) >= 15) {
				$diffs .= "...";
				$modes .= "...";
			}

			if ($req["blacklisted"] == 1) {
				$rowClass = "danger";
			} else if ($allUnranked) {
				$rowClass = $today ? "success" : "default";
			} else {
				$rowClass = "default";
			}

			/*if (($bsid & 1073741824) > 0) {
				$host = "osu!mp";
			} else if (($bsid & 536870912) > 0) {
				$host = "ripple";
			} else {
				$host = "osu!";
			}*/

			echo "<tr class='$rowClass'>
				<td><a href='https://storage.ripple.moe/d/$bsid' target='_blank'>$req[type]/$req[bid]</a></td>
				<td>$song</td>
				<td>
					$diffs
				</td>
				<td>$modes</td>
				<td>$req[username]</td>
				<td>".timeDifference(time(), $req["time"])."</td>
				<td>
					<p class='text-center'>
						<a title='Edit ranked status' class='btn btn-xs btn-primary' href='index.php?p=124&bsid=$bsid&force=".$forceParam."'><span class='glyphicon glyphicon-pencil'></span></a>
						<a title='Toggle blacklist' class='btn btn-xs btn-danger' href='submit.php?action=blacklistRankRequest&id=$req[id]'><span class='glyphicon glyphicon-flag'></span></a>
					</p>
				</td>
			</tr>";
		}
		echo '</tbody>';
		echo '</table>';
		// Template end
		echo '</div>';
	}

	public static function AdminPrivilegesGroupsMain() {
		// Get data
		$groups = $GLOBALS['db']->fetchAll('SELECT * FROM privileges_groups ORDER BY id ASC');
		// Print sidebar and template stuff
		echo '<div id="wrapper">';
		printAdminSidebar();
		echo '<div id="page-content-wrapper">';
		// Maintenance check
		self::MaintenanceStuff();
		// Print Success if set
		if (isset($_GET['s']) && !empty($_GET['s'])) {
			self::SuccessMessageStaccah($_GET['s']);
		}
		// Print Exception if set
		if (isset($_GET['e']) && !empty($_GET['e'])) {
			self::ExceptionMessageStaccah($_GET['e']);
		}
		// Header
		echo '<span align="center"><h2><i class="fa fa-group"></i>	Privilege Groups</h2></span>';
		// Main page content here
		echo '<div align="center">';
		echo '<table class="table table-striped table-hover table-75-center">
		<thead>
		<tr><th class="text-left"><i class="fa fa-group"></i>	ID</th><th class="text-center">Name</th><th class="text-center">Privileges</th><th class="text-center">Action</th></tr>
		</thead>
		<tbody>';
		foreach ($groups as $group) {
			echo "<tr>
					<td style='text-align: center;'>$group[id]</td>
					<td style='text-align: center;'>$group[name]</td>
					<td style='text-align: center;'>$group[privileges]</td>
					<td style='text-align: center;'>
						<div class='btn-group'>
							<a href='index.php?p=119&id=$group[id]' title='Edit' class='btn btn-xs btn-primary'><span class='glyphicon glyphicon-pencil'></span></a>
							<a href='index.php?p=119&h=$group[id]' title='Inherit' class='btn btn-xs btn-warning'><span class='glyphicon glyphicon-copy'></span></a>
							<a href='index.php?p=120&id=$group[id]' title='View users in this group' class='btn btn-xs btn-success'><span class='glyphicon glyphicon-search'></span></a>
						</div>
					</td>
				</tr>";
		}
		echo '</tbody>
		</table>';

		echo '<a href="index.php?p=119" type="button" class="btn btn-primary">New group</a>';

		echo '</div>';
		// Template end
		echo '</div>';
	}


	public static function AdminEditPrivilegesGroups() {
		try {
			// Check if id is set, otherwise set it to 0 (new badge)
			if (!isset($_GET['id']) && !isset($_GET["h"])) {
				$_GET['id'] = 0;
			}
			// Check if we are editing, creating or inheriting a new group
			if (isset($_GET["h"])) {
				$privilegeGroupData = $GLOBALS['db']->fetch('SELECT * FROM privileges_groups WHERE id = ?', [$_GET['h']]);
				$privilegeGroupData["id"] = 0;
				$privilegeGroupData["name"] .= " (child)";
			} else if ($_GET["id"] > 0) {
				$privilegeGroupData = $GLOBALS['db']->fetch('SELECT * FROM privileges_groups WHERE id = ?', $_GET['id']);
			} else {
				$privilegeGroupData = ['id' => 0, 'name' => 'New Privilege Group', 'privileges' => 0, 'color' => 'default'];
			}
			// Check if this group exists
			if (!$privilegeGroupData) {
				throw new Exception("That privilege group doesn't exists");
			}
			// Print edit user stuff
			echo '<div id="wrapper">';
			printAdminSidebar();
			echo '<div id="page-content-wrapper">';
			// Maintenance check
			self::MaintenanceStuff();
			echo '<p align="center"><font size=5><i class="fa fa-group"></i>	Privilege Group</font></p>';
			echo '<table class="table table-striped table-hover table-50-center">';
			echo '<tbody><form id="edit-badge-form" action="submit.php" method="POST"><input name="action" value="savePrivilegeGroup" hidden>';
			echo '<tr>
			<td>ID</td>
			<td><input type="number" name="id" class="form-control" value="'.$privilegeGroupData['id'].'" readonly></td>
			</tr>';
			echo '<tr>
			<td>Name</td>
			<td><input type="text" name="n" class="form-control" value="'.$privilegeGroupData['name'].'" ></td>
			</tr>';
			echo '<tr>
			<td>Privileges</td>
			<td>';

			$refl = new ReflectionClass("Privileges");
			$privilegesList = $refl->getConstants();
			foreach ($privilegesList as $i => $v) {
				if ($v <= 0)
					continue;
				$c = (($privilegeGroupData["privileges"] & $v) > 0) ? "checked" : "";
				echo '<label class="colucci"><input name="privileges" value="'.$v.'" type="checkbox" onclick="updatePrivileges();" '.$c.'>	'.$i.' ('.$v.')</label><br>';
			}
			echo '</td></tr>';

			echo '<tr>
			<td>Privileges number</td>
			<td><input class="form-control" id="privileges-value" name="priv" value="'.$privilegeGroupData["privileges"].'"></td>
			</tr>';

			// Selected stuff
			$sel = ["","","","","",""];
			switch($privilegeGroupData["color"]) {
				case "default": $sel[0] = "selected"; break;
				case "success": $sel[1] = "selected"; break;
				case "warning": $sel[2] = "selected"; break;
				case "danger": $sel[3] = "selected"; break;
				case "primary": $sel[4] = "selected"; break;
				case "info": $sel[5] = "selected"; break;
			}

			echo '<tr>
			<td>Color<br><i>(used in RAP users listing page)</i></td>
			<td>
			<select name="c" class="selectpicker" data-width="100%">
				<option value="default" '.$sel[0].'>Gray</option>
				<option value="success" '.$sel[1].'>Green</option>
				<option value="warning" '.$sel[2].'>Yellow</option>
				<option value="danger" '.$sel[3].'>Red</option>
				<option value="primary" '.$sel[4].'>Blue</option>
				<option value="info" '.$sel[5].'>Light Blue</option>
			</select>
			</td>
			</tr>';
			echo '</tbody></form>';
			echo '</table>';
			echo '<div class="text-center"><button type="submit" form="edit-badge-form" class="btn btn-primary">Save changes</button></div>';
			echo '</div>';
		}
		catch(Exception $e) {
			// Redirect to exception page
			redirect('index.php?p=119&e='.$e->getMessage());
		}
	}


	public static function AdminShowUsersInPrivilegeGroup() {
		// Exist check
		try {
			if (!isset($_GET["id"])) {
				throw new Exception("That group doesn't exist");
			}

			// Get data
			$groupData = $GLOBALS["db"]->fetch("SELECT * FROM privileges_groups WHERE id = ?", [$_GET["id"]]);
			if (!$groupData) {
				throw new Exception("That group doesn't exist");
			}
			$users = $GLOBALS['db']->fetchAll('SELECT * FROM users WHERE privileges = ? OR privileges = ? | '.Privileges::UserDonor, [$groupData["privileges"], $groupData["privileges"]]);
			// Print sidebar and template stuff
			echo '<div id="wrapper">';
			printAdminSidebar();
			echo '<div id="page-content-wrapper">';
			// Maintenance check
			self::MaintenanceStuff();
			// Header
			echo '<span align="center"><h2><i class="fa fa-search"></i>	Users in '.$groupData["name"].' group</h2></span>';
			// Main page content here
			echo '<div align="center">';
			echo '<table class="table table-striped table-hover table-75-center">
			<thead>
			<tr><th class="text-left"><i class="fa fa-group"></i>	ID</th><th class="text-center">Username</th></tr>
			</thead>
			<tbody>';
			foreach ($users as $user) {
				echo "<tr>
						<td style='text-align: center;'>$user[id]</td>
						<td style='text-align: center;'><a href='index.php?u=$user[id]'>$user[username]</a></td>
					</tr>";
			}
			echo '</tbody>
			</table>';

			echo '</div>';
			// Template end
			echo '</div>';
		} catch(Exception $e) {
			redirect("index.php?p=118?e=".$e->getMessage());
		}
	}


	public static function RestrictedAlert() {
		if (!checkLoggedIn()) {
			return;
		}

		if (!hasPrivilege(Privileges::UserPublic)) {
			echo '<div class="alert alert-danger" role="alert">
					<p align="center"><i class="fa fa-exclamation-triangle"></i><b>Your account is currently in restricted mode</b> due to inappropriate behavior or a violation of the <a href=\'index.php?p=23\'>rules</a>.<br>You can\'t interact with other users, you can perform limited actions and your user profile and scores are hidden.<br>Read the <a href=\'index.php?p=23\'>rules</a> again carefully, and if you think this is an error, send an email to <b>support@ripple.moe</b>.</p>
				  </div>';
		}
	}

	/*
	 * AdminGiveDonor
	 * Prints the admin give donor page
	*/
	public static function AdminGiveDonor() {
		try {
			// Check if id is set
			if (!isset($_GET['id'])) {
				throw new Exception('Invalid user id');
			}
			echo '<div id="wrapper">';
			printAdminSidebar();
			echo '<div id="page-content-wrapper">';
			// Maintenance check
			self::MaintenanceStuff();
			echo '<p align="center"><font size=5><i class="fa fa-money"></i>	Give donor</font></p>';
			$username = $GLOBALS["db"]->fetch("SELECT username FROM users WHERE id = ?", [$_GET["id"]]);
			if (!$username) {
				throw new Exception("Invalid user");
			}
			$username = current($username);
			echo '<table class="table table-striped table-hover table-50-center"><tbody>';
			echo '<form id="edit-user-badges" action="submit.php" method="POST"><input name="action" value="giveDonor" hidden>';
			echo '<tr>
			<td>User ID</td>
			<td><p class="text-center"><input type="text" name="id" class="form-control" value="'.$_GET["id"].'" readonly></td>
			</tr>';
			echo '<tr>
			<td>Username</td>
			<td><p class="text-center"><input type="text" class="form-control" value="'.$username.'" readonly></td>
			</tr>';
			echo '<tr>
			<td>Period</td>
			<td>
			<input name="m" type="number" class="form-control" placeholder="Months" required></input>
			</td>
			</tr>';
			echo '<tr>
			<td>Operation type</td>
			<td>
			<select name="type" class="selectpicker" data-width="100%">
				<option value=0 selected>Add months</option>
				<option value=1>Replace months</option>
			</select></td>
			</tr>';


			echo '</tbody></form>';
			echo '</table>';
			echo '<div class="text-center"><button type="submit" form="edit-user-badges" class="btn btn-primary">Give donor</button></div>';
			echo '</div>';
		}
		catch(Exception $e) {
			// Redirect to exception page
			redirect('index.php?p=108&e='.$e->getMessage());
		}
	}


	/*
	 * AdminRollback
	 * Prints the admin rollback page
	*/
	public static function AdminRollback() {
		try {
			// Check if id is set
			if (!isset($_GET['id'])) {
				throw new Exception('Invalid user id');
			}
			echo '<div id="wrapper">';
			printAdminSidebar();
			echo '<div id="page-content-wrapper">';
			// Maintenance check
			self::MaintenanceStuff();
			echo '<p align="center"><font size=5><i class="fa fa-fast-backward"></i>	Rollback account</font></p>';
			$username = $GLOBALS["db"]->fetch("SELECT username FROM users WHERE id = ?", [$_GET["id"]]);
			if (!$username) {
				throw new Exception("Invalid user");
			}
			$username = current($username);
			echo '<table class="table table-striped table-hover table-50-center"><tbody>';
			echo '<form id="user-rollback" action="submit.php" method="POST"><input name="action" value="rollback" hidden>';
			echo '<tr>
			<td>User ID</td>
			<td><p class="text-center"><input type="text" name="id" class="form-control" value="'.$_GET["id"].'" readonly></td>
			</tr>';
			echo '<tr>
			<td>Username</td>
			<td><p class="text-center"><input type="text" class="form-control" value="'.$username.'" readonly></td>
			</tr>';
			echo '<tr>
			<td>Period</td>
			<td>
			<input type="number" name="length" class="form-control" style="width: 40%; display: inline;">
			<div style="width: 5%; display: inline-block;"></div>
			<select name="period" class="selectpicker" data-width="53%">
				<option value="d">Days</option>
				<option value="w">Weeks</option>
				<option value="m">Months</option>
				<option value="y">Years</option>
			</select>
			</td>
			</tr>';

			echo '</tbody></form>';
			echo '</table>';
			echo '<div class="text-center"><button type="submit" form="user-rollback" class="btn btn-primary">Rollback account</button></div>';
			echo '</div>';
		}
		catch(Exception $e) {
			// Redirect to exception page
			redirect('index.php?p=108&e='.$e->getMessage());
		}
	}



	/*
	 * AdminWipe
	 * Prints the admin wipe page
	*/
	public static function AdminWipe() {
		try {
			// Check if id is set
			if (!isset($_GET['id'])) {
				throw new Exception('Invalid user id');
			}
			echo '<div id="wrapper">';
			printAdminSidebar();
			echo '<div id="page-content-wrapper">';
			// Maintenance check
			self::MaintenanceStuff();
			echo '<p align="center"><font size=5><i class="fa fa-eraser"></i>	Wipe account</font></p>';
			$username = $GLOBALS["db"]->fetch("SELECT username FROM users WHERE id = ?", [$_GET["id"]]);
			if (!$username) {
				throw new Exception("Invalid user");
			}
			$username = current($username);
			echo '<table class="table table-striped table-hover table-50-center"><tbody>';
			echo '<form id="user-wipe" action="submit.php" method="POST"><input name="action" value="wipeAccount" hidden>';
			echo '<tr>
			<td>User ID</td>
			<td><p class="text-center"><input type="text" name="id" class="form-control" value="'.$_GET["id"].'" readonly></td>
			</tr>';
			echo '<tr>
			<td>Username</td>
			<td><p class="text-center"><input type="text" class="form-control" value="'.$username.'" readonly></td>
			</tr>';
			echo '<tr>
			<td>Gamemode</td>
			<td>
			<select name="gm" class="selectpicker" data-width="100%">
				<option value="-1">All</option>
				<option value="0">Standard</option>
				<option value="1">Taiko</option>
				<option value="2">Catch the beat</option>
				<option value="3">Mania</option>
			</select>
			</td>
			</tr>';

			echo '</tbody></form>';
			echo '</table>';
			echo '<div class="text-center"><button type="submit" form="user-wipe" class="btn btn-primary">Wipe account</button></div>';
			echo '</div>';
		}
		catch(Exception $e) {
			// Redirect to exception page
			redirect('index.php?p=108&e='.$e->getMessage());
		}
	}



	/*
	 * AdminRankBeatmap
	 * Prints the admin rank beatmap page
	*/
	public static function AdminRankBeatmap() {
		try {
			// Check if id is set
			if (!isset($_GET['bsid']) || empty($_GET['bsid'])) {
				throw new Exception('Invalid beatmap set id');
			}
			echo '<div id="wrapper">';
			printAdminSidebar();
			echo '<div id="page-content-wrapper">';
			// Maintenance check
			self::MaintenanceStuff();
			echo '<p align="center"><h2><i class="fa fa-music"></i>	Rank beatmap</h2></p>';

			echo '<br><br>';

			echo '<div id="main-content">
				<i class="fa fa-circle-o-notch fa-spin fa-3x fa-fw"></i>
				<h3>Loading beatmap data from osu!api...</h3>
				<h5>This might take a while</h5>
			</div>';
			echo '</div>';
			echo '</div>';
		}
		catch(Exception $e) {
			// Redirect to exception page
			redirect('index.php?p=117&e='.$e->getMessage());
		}
	}

	/*
	 * AdminRankBeatmap
	 * Prints the admin rank beatmap page
	*/
	public static function AdminRankBeatmapManually() {
		echo '<div id="wrapper">';
		printAdminSidebar();
		echo '<div id="page-content-wrapper">';
		// Maintenance check
		self::MaintenanceStuff();
		// Print Exception if set
		if (isset($_GET['e']) && !empty($_GET['e'])) {
			self::ExceptionMessageStaccah($_GET['e']);
		}
		echo '<p align="center"><h2><i class="fa fa-level-up"></i>	Rank beatmap manually</h2></p>';

		echo '<br>';

		echo '
		<div id="narrow-content">
			<form action="submit.php" method="POST">
				<input name="action" value="redirectRankBeatmap" hidden>
				<input name="id" type="text" class="form-control" placeholder="Beatmap(set) id" style="width: 40%; display: inline;">
				<div style="width: 1%; display: inline-block;"></div>
				<select name="type" class="selectpicker bs-select-hidden" data-width="25%">
					<option value="bid" selected="">Beatmap ID</option>
					<option value="bsid">Beatmap Set ID</option>
				</select>
				<hr>
				<button type="submit" class="btn btn-primary">Edit ranked status</button>
			</form>

		</div>';

		echo '</div>';
		echo '</div>';
	}



	public static function AdminViewReports() {
		echo '<div id="wrapper">';
		printAdminSidebar();
		echo '<div id="page-content-wrapper">';
		self::MaintenanceStuff();
		if (isset($_GET['e']) && !empty($_GET['e'])) {
			self::ExceptionMessageStaccah($_GET['e']);
		}
		echo '<p align="center"><h2><i class="fa fa-flag"></i>	Reports</h2></p>';

		echo '<br>';

		$reports = $GLOBALS["db"]->fetchAll("SELECT * FROM reports ORDER BY id DESC LIMIT 50;");
		echo '<table class="table table-striped table-hover table-75-center">
		<thead>
		<tr><th class="text-center"><i class="fa fa-flag"></i>	ID</th><th class="text-center">From</th><th class="text-center">Target</th><th class="text-l">Reason</th><th class="text-center">When</th><th class="text-center">Assignee</th><th class="text-center">Actions</th></tr>
		</thead>';
		echo '<tbody>';
		foreach ($reports as $report) {
			if ($report['assigned'] == 0) {
				$rowClass = "danger";
				$assignee = "No one";
			} else if ($report['assigned'] == -1) {
				$rowClass = "success";
				$assignee = "Solved";
			} else if ($report["assigned"] == -2) {
				$rowClass = "warning";
				$assignee = "Useless";
			} else {
				$rowClass = "";
				$assignee = '<img class="circle" style="width: 30px; height: 30px; margin-top: 0px;" src="https://a.ripple.moe/' . $report['assigned'] . '"> ' . getUserUsername($report['assigned']);
			}
			echo '<tr class="' . $rowClass . '">
			<td><p class="text-center">'.$report['id'].'</p></td>
			<td><p class="text-center"><a href="index.php?u=' . $report["from_uid"] . '" target="_blank">'.getUserUsername($report['from_uid']).'</a></p></td>
			<td><p class="text-center"><b><a href="index.php?u=' . $report["to_uid"] . '" target="_blank">'.getUserUsername($report['to_uid']).'</a></b></p></td>
			<td><p>'.htmlspecialchars(substr($report['reason'], 0, 40)).'</p></td>
			<td><p>'.timeDifference(time(), $report['time']).'</p></td>
			<td><p class="text-center">' . $assignee . '</p></td>
			<td><p class="text-center">
			<a title="View/Edit report" class="btn btn-xs btn-primary" href="index.php?p=127&id='.$report['id'].'"><span class="glyphicon glyphicon-zoom-in"></span></a>
			<!-- <a title="Set as solved" class="btn btn-xs btn-success"><span class="glyphicon glyphicon-ok"></span></a>-->
			</p></td>
			</tr>';
		}
		echo '</tbody>';
		echo '</table>';

		echo '</div>';
		echo '</div>';
	}

	public static function AdminViewReport() {
		try {
			if (!isset($_GET["id"]) || empty($_GET["id"])) {
				throw new Exception("Missing report id");
			}
			$report = $GLOBALS["db"]->fetch("SELECT * FROM reports WHERE id = ? LIMIT 1", [$_GET["id"]]);
			if (!$report) {
				throw new Exception("Invalid report id");
			}
			$statusRowClass = "";
			if ($report["assigned"] == 0) {
				$status = "Unassigned";
			} else if ($report["assigned"] == -1) {
				$status = "Solved";
				$statusRowClass = "info";
			} else if ($report["assigned"] == -2) {
				$status = "Useless";
				$statusRowClass = "warning";
			} else {
				$status = "Assigned to " . getUserUsername($report["assigned"]);
				if ($report["assigned"] == $_SESSION["userid"]) {
					$statusRowClass = "success";
				}
			}
			$reportedCount = $GLOBALS["db"]->fetch("SELECT COUNT(*) AS count FROM reports WHERE to_uid = ? AND time >= ? LIMIT 1", [$report["to_uid"], time() - 86400 * 30])["count"];
			$uselessCount = $GLOBALS["db"]->fetch("SELECT COUNT(*) AS count FROM reports WHERE from_uid = ? AND assigned = -2 AND time >= ? LIMIT 1", [$report["from_uid"], time() - 86400 * 30])["count"];

			$takeButtonText = $report["assigned"] == 0 || $report["assigned"] != $_SESSION["userid"] ? "Take" : "Leave";
			$takeButtonDisabled = $report["assigned"] < 0  ? "disabled" : "";

			$solvedButtonText = $report["assigned"] != -1 ? "Mark as solved" : "Mark as unsolved";
			$solvedButtonDisabled = $report["assigned"] < 0 && $report["assigned"] != -1 ? "disabled" : "";

			$uselessButtonText = $report["assigned"] != -2 ? "Mark as useless" : "Mark as useful";
			$uselessButtonDisabled = $report["assigned"] < 0 && $report["assigned"] != -2 ? "disabled" : "";

			echo '<div id="wrapper">';
			printAdminSidebar();
			echo '<div id="page-content-wrapper">';
			self::MaintenanceStuff();
			if (isset($_GET['e']) && !empty($_GET['e'])) {
				self::ExceptionMessageStaccah($_GET['e']);
			}
			if (isset($_GET['s']) && !empty($_GET['s'])) {
				self::SuccessMessageStaccah($_GET['s']);
			}
			echo '<p align="center">
				<h2><i class="fa fa-flag"></i>	View report</h2>
				<h4><a href="index.php?p=126"><i class="fa fa-chevron-left"></i>&nbsp;&nbsp;Back</a></h4>
			</p>';

			echo '<br>';

			echo '
			<div id="narrow-content">
				<table class="table table-striped table-hover table-100-center"><tbody>
					<tr>
						<td><b>From</b></td>
						<td>' . getUserUsername($report["from_uid"]) . '</td>
					</tr>
					<tr>
						<td><b>Reported user</b></td>
						<td><b>' . getUserUsername($report["to_uid"]) . '</b></td>
					</tr>
					<tr>
						<td><b>Reason</b></td>
						<td><b>' . htmlspecialchars($report["reason"]) . '</b></td>
					</tr>
					<tr>
						<td><b>When</b></td>
						<td>' . timeDifference(time(), $report["time"]) . '</td>
					</tr>
					<tr>
						<td><b>Chatlog*</b></td>
						<td>' . str_replace("\n", "<br>", $report["chatlog"]) .  '</td>
					</tr>
					<tr class="' . $statusRowClass . '">
						<td><b>Status</b></td>
						<td>' . $status . '</td>
					</tr>
					<tr class="info">
						<td colspan=2><b>' . getUserUsername($report["to_uid"]) . '</b> has been reported <b>' . $reportedCount . '</b> times in the last month</td>
					</tr>
					<tr class="info">
						<td colspan=2><b>' . getUserUsername($report["from_uid"]) . '</b> has sent <b>' . $uselessCount . '</b> useless reports in the last month</td>
					</tr>
				</table>

				<ul class="list-group">
					<li class="list-group-item list-group-item-warning">Ticket actions</li>
					<li class="list-group-item">
						<a class="btn btn-warning ' . $takeButtonDisabled . '" href="submit.php?action=takeReport&id=' . $report["id"] . '"><i class="fa fa-bolt"></i> ' . $takeButtonText .' ticket</a>
						<a class="btn btn-success ' . $solvedButtonDisabled . '" href="submit.php?action=solveUnsolveReport&id=' . $report["id"] . '"><i class="fa fa-check"></i> ' . $solvedButtonText . '</a>
						<a class="btn btn-danger ' . $uselessButtonDisabled . '" href="submit.php?action=uselessUsefulReport&id=' . $report["id"] . '"><i class="fa fa-trash"></i> ' . $uselessButtonText . '</a>
					</li>
				</ul>

				<ul class="list-group">
					<li class="list-group-item list-group-item-danger">Quick actions</li>
					<li class="list-group-item">
						<a class="btn btn-primary" href="index.php?p=103&id=' . $report["to_uid"] . '"><i class="fa fa-expand"></i> View reported user in RAP</a>
						<div class="btn btn-warning" data-toggle="modal" data-target="#silenceUserModal" data-who="' . getUserUsername($report["to_uid"]) . '"><i class="fa fa-microphone-slash"></i> Silence reported user</div>
						<div class="btn btn-warning" data-toggle="modal" data-target="#silenceUserModal" data-who="' . getUserUsername($report["from_uid"]) . '"><i class="fa fa-microphone-slash"></i> Silence source user</div>
						';
						$restrictedDisabled = isRestricted($report["to_uid"]) ? "disabled" : "";
						echo '<a class="btn btn-danger ' . $restrictedDisabled . '" onclick="sure(\'submit.php?action=restrictUnrestrictUser&id=' . $report["to_uid"] . '&resend=1\')"><i class="fa fa-times"></i> Restrict reported user</a>';
					echo '</li>
				</ul>

				<i><b>*</b> Latest 10 public messages sent from reported user before getting reported, trimmed to 50 characters.</i>

			</div>';

			echo '</div>';
			echo '</div>';
			// Silence user modal
			echo '<div class="modal fade" id="silenceUserModal" tabindex="-1" role="dialog" aria-labelledby="silenceUserModal">
			<div class="modal-dialog">
			<div class="modal-content">
			<div class="modal-header">
			<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
			<h4 class="modal-title">Silence user</h4>
			</div>
			<div class="modal-body">
			<p>
			<form id="silence-user-form" action="submit.php" method="POST">
			<input name="action" value="silenceUser" hidden>
			<input name="resend" value="1" hidden>

			<div class="input-group">
			<span class="input-group-addon" id="basic-addon1"><span class="glyphicon glyphicon-user" aria-hidden="true"></span></span>
			<input type="text" name="u" class="form-control" placeholder="Username" aria-describedby="basic-addon1" required>
			</div>

			<p style="line-height: 15px"></p>

			<div class="input-group">
			<span class="input-group-addon" id="basic-addon1"><span class="glyphicon glyphicon-time" aria-hidden="true"></span></span>
			<input type="number" name="c" class="form-control" placeholder="How long" aria-describedby="basic-addon1" required>
			<select name="un" class="selectpicker" data-width="30%">
				<option value="1">Seconds</option>
				<option value="60">Minutes</option>
				<option value="3600">Hours</option>
				<option value="86400">Days</option>
			</select>
			</div>

			<p style="line-height: 15px"></p>

			<div class="input-group">
			<span class="input-group-addon" id="basic-addon1"><span class="glyphicon glyphicon-comment" aria-hidden="true"></span></span>
			<input type="text" name="r" class="form-control" placeholder="Reason" aria-describedby="basic-addon1">
			</div>

			<p style="line-height: 15px"></p>

			During the silence period, user\'s client will be locked. <b>Max silence time is 7 days.</b> Set length to 0 to remove the silence.

			</form>
			</p>
			</div>
			<div class="modal-footer">
			<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
			<button type="submit" form="silence-user-form" class="btn btn-primary">Silence user</button>
			</div>
			</div>
			</div>
			</div>';
		} catch (Exception $e) {
			redirect("index.php?p=126&e=" . $e->getMessage());
		}

	}
}

// LISCIAMI LE MELE SUDICIO
class Fava extends Exception {
	 public function __construct($message, $code = 0, Exception $previous = null) {
        parent::__construct($message, $code, $previous);
    }
}
