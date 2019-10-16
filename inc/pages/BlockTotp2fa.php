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
				<h1><i class="fa fa-shield-alt"></i> MADUUUUU</h1>
				<br>
				<b>You are logging in from a new IP address and you have TOTP 2FA enabled on your account.</b><br>
				Please, <b>log in to your account from <a href="https://ripple.moe/" target="_blank">hanayo</a></b>, pass the 2FA check to trust this IP and <b><a href="index.php?p=42">reload</a> this page.</b>
			</div>
		</div>';
	}
}
