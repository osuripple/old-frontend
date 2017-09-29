<?php

class BlockTotpTwoFa {
	const PageID = 42;
	const URL = 'blocktotp2fa';
	const Title = 'Ripple - Nope!';
	const LoggedIn = true;

	public function P() {
		$ip = getIp();
		if (get2FAType($_SESSION["userid"]) != 2 || $GLOBALS["db"]->fetch("SELECT * FROM ip_user WHERE userid = ? AND ip = ?", [$_SESSION["userid"], $ip])) {
			redirect("index.php?p=1");
		}
		P::GlobalAlert();
		echo '
		<div style="content">
			<div align="center">
				<h1><i class="fa fa-shield"></i> MADUUUUU</h1>
				<br>
				<b>You are logging in from a new IP address and you have TOTP 2FA enabled on your account.</b><br>
				Please, <b>log in to your account from <a href="https://ripple.moe/" target="_blank">hanayo</a></b>, pass the 2FA check to trust this IP and <b><a href="index.php?p=42">reload</a> this page.</b>
			</div>
		</div>';
	}

	/*public function D() {
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
	}*/

	/*public function DoGetData() {
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
	}*/
}
