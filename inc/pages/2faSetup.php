<?php

class TwoFASetup {
	const PageID = 30;
	const URL = '2faSetup';
	const Title = 'Ripple - 2FA Setup';
	const LoggedIn = true;
	public $error_messages = [];
	public $mh_GET = [];
	public $mh_POST = [];

	public function P() {
		//startSessionIfNotStarted();
		$_SESSION["2fa"] = is2FAEnabled($_SESSION["userid"], true);
		if (!$_SESSION["2fa"]) {
			cleanExpiredConfirmationToken();
			$token = getConfirmationToken($_SESSION["userid"]);
		}
		echo '
		<div id="narrow-content">
			<div align="center">
				<h1><i class="fa fa-ticket"></i> Two-Factor Auth Setup</h1>
				<br>
				With Two-Factor Authentication (or 2FA), we\'ll send you a special code through Telegram every time you log in from a new IP address.
				We highly reccomend setting up 2FA to increase your account security.<br><br>';

				if ($_SESSION["2fa"]) {
					echo '<div class="alert alert-success" role="alert"><i class="fa fa-check-circle"></i>	<b>Telegram 2FA is enabled on your account</b></div><br>
					<a onclick="sure(\'submit.php?action=disable2FA\')" type="button" class="btn btn-primary"><span class="fa-stack"><i class="fa fa-paper-plane fa-stack-1x"></i><i class="fa fa-ban fa-stack-2x text-danger"></i></span>	Disable Telegram 2FA</a><br>';
				} else {
					echo '<div class="alert alert-danger" role="alert"><i class="fa fa-exclamation-triangle"></i>	<b>Telegram 2FA is not enabled on your account</b></div>
					<br>
					<b>To enable 2FA, click this button, then click "Start" on Telegram.</b><br>
					<a href="http://telegram.me/osuripple_bot?start='.$token.'" type="button" class="btn btn-primary"><i class="fa fa-paper-plane"></i>	Enable Telegram 2FA</a><br>';
				}
			echo '</div>
		</div>';
	}

	public function D() {
		startSessionIfNotStarted();
		$d = $this->DoGetData();
		if (isset($d["error"])) {
			addError($d['error']);
			redirect("index.php?p=29");
		} else {
			// No errors, run botnet to add the new IP address
			botnet($_SESSION["userid"]);
			redirect("index.php?p=1");
		}
	}

	public function DoGetData() {
		try {
			// Get tokenID
			$token = $GLOBALS["db"]->fetch("SELECT * FROM 2fa WHERE userid = ? AND ip = ? AND token = ?", [$_SESSION["userid"], getIp(), $_POST["token"]]);
			// Make sure the token exists
			if (!$token) {
				throw new Exception("Invalid 2FA code.");
			}
			// Make sure the token is not expired
			if ($token["expire"] < time()) {
				throw new Exception("Your 2FA token is expired. Please enter the new code you've just received.");
			}
			// Everything seems fine, delete 2FA token to allow this session
			$GLOBALS["db"]->execute("DELETE FROM 2fa WHERE id = ?", [$token["id"]]);
		} catch (Exception $e) {
			$ret["error"] = $e->getMessage();
		}

		return $ret;
	}
}
