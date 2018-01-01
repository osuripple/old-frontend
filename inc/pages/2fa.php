<?php

class TwoFA {
	const PageID = 29;
	const URL = '2fa';
	const Title = 'Ripple - 2FA';
	const LoggedIn = true;
	public $error_messages = [];
	public $mh_GET = [];
	public $mh_POST = ["token"];

	public function P() {
		if (!is2FAEnabled($_SESSION["userid"])) {
			redirect("index.php?p=1");
		}
		P::GlobalAlert();
		echo '
		<div style="content">
			<div align="center">
				<h1><i class="fa fa-hand-paper-o"></i> You shall not pass!</h1>
				<br>
				You are logging in from a new IP address.<br>Enter the 2FA code you\'ve received on Telegram to continue.
				<form action="submit.php" method="POST">
					<div class="input-group" style="width:50%">
						<input name="action" value="2fa" hidden>
						<input name="token" type="text" class="form-control" placeholder="2FA Code">
						<span class="input-group-btn">
							<button class="btn btn-success" type="submit"><i class="fa fa-unlock"></i></button>
						</span>
					</div>
				</form>
				<br>
				You didn\'t receive the code?<br>
				<a href="submit.php?action=resend2FACode" type="button" class="btn btn-warning">Send 2FA code again</a>
			</div>
		</div>';
	}

	public function D() {
		startSessionIfNotStarted();
		$d = $this->DoGetData();
		if (isset($d["error"])) {
			addError($d['error']);
			redirect("index.php?p=29");
		} else {
			// No errors, log new IP address
			logIP($_SESSION["userid"]);
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
