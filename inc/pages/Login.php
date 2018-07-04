<?php

class Login {
	const PageID = 2;
	const URL = 'login';
	const Title = 'Ripple - Login';
	const LoggedIn = false;
	public $mh_POST = ['u', 'p'];
	public $error_messages = ['You are not logged in.', 'Session expired. Please login again.', 'Invalid auto-login cookie.', 'You are already logged in.'];

	public function P() {
		clir(true, 'index.php?p=1&e=1');
		echo '<br><div class="narrow-content"><h1><i class="fa fa-sign-in"></i>	Login</h1>';
		if (!isset($_GET['e']) && !isset($_GET['s'])) {
			echo '<p>Please enter your credentials.</p>';
		}
		echo '<p><a href="index.php?p=18">Forgot your password, perhaps?</a></p>';
		// Print login form
		echo '<form action="submit.php" method="POST">
		<input name="action" value="login" hidden>
		<div class="input-group"><span class="input-group-addon" id="basic-addon1"><span class="glyphicon glyphicon-user" max-width="25%"></span></span><input type="text" name="u" required class="form-control" placeholder="Username" aria-describedby="basic-addon1"></div><p style="line-height: 15px"></p>
		<div class="input-group"><span class="input-group-addon" id="basic-addon1"><span class="glyphicon glyphicon-lock" max-width="25%"></span></span><input type="password" name="p" required class="form-control" placeholder="Password" aria-describedby="basic-addon1"></div>
		<p style="line-height: 15px"></p>
		<p><label><input type="checkbox" name="remember" value="yes"> Stay logged in?</label></p>
		<p style="line-height: 15px"></p>
		<button type="submit" class="btn btn-primary">Login</button>
		<a href="index.php?p=3" type="button" class="btn btn-default">Sign up</a>
		</form>
		</div>';
	}

	public function D() {
		$d = $this->DoGetData();
		if (isset($d['success'])) {
			if (isset($_SESSION['redirpage']) && $_SESSION['redirpage'] != '')
				redirect($_SESSION['redirpage']);
			redirect('index.php?p=1');
		} else {
			addError($d['error']);
			redirect('index.php?p=2');
		}
	}

	public function PrintGetData() {
		return [];
	}

	public function DoGetData() {
		$ret = [];
		try {
			if (!PasswordHelper::CheckPass($_POST['u'], $_POST['p'], false)) {
				throw new Exception('Wrong username or password.');
			}
			$us = $GLOBALS['db']->fetch('
			SELECT
				users.id, users.password_md5,
				users.username, users_stats.country
			FROM users
			LEFT JOIN users_stats ON users_stats.id = users.id
			WHERE users.username_safe = ?', [safeUsername($_POST['u'])]);
			// Set multiacc identity token
			setYCookie($us["id"]);
			// Old frontend shall be seen by no human on earth. Except for
			// staff members. Those aren't human.
			if (!hasPrivilege(Privileges::AdminAccessRAP, $us["id"])) {
				redirect("https://ripple.moe/login");
			}

			// Get username with right case
			$username = $us['username'];

			// Everything ok, create session and do login stuff
			startSessionIfNotStarted();
			$_SESSION['username'] = $username;
			$_SESSION['userid'] = $us['id'];
			$_SESSION['password'] = $us['password_md5'];
			$_SESSION['passwordChanged'] = false;
			$_SESSION['csrf'] = csrfToken();
			
			// Check if the user requested to be remembered. If they did, initialise cookies.
			if (isset($_POST['remember']) && (bool) $_POST['remember']) {
				$m = new RememberCookieHandler();
				$m->IssueNew($us['id']);
			}
			// update ip logs only if we don't have 2FA enabled or this ip is allowed
			// if 2FA is enabled, logIP will be run when this IP has been allowed
			if (get2FAType($us['id']) != 2)
				logIP($us['id']);
			// Get safe title
			updateSafeTitle();
			// Save latest activity
			updateLatestActivity($us['id']);
			// Update country if XX
			if ($us['country'] == 'XX')
				updateUserCountry($us['id'], 'id');
			$ret['success'] = true;
		}
		catch(Exception $e) {
			$ret['error'] = $e->getMessage();
		}

		return $ret;
	}
}
