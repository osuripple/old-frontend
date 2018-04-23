<?php
class Verify {
	const PageID = 38;
	const URL = 'verify';
	const Title = 'Ripple - Verify your account';
	const LoggedIn = false;
	public $mh_GET = ["u"];

	public function P() {
		// Make sure the "y" token is the one from the correct user
		global $discordConfig;
		try {
			// Make sure we have the "y" token
			if (!isset($_COOKIE["y"]))
				throw new Exception;
			// Make sure the "y" token is the correct one for this user
			$idt = getIdentityToken($_GET["u"], false);
			if ($idt == false || $idt != $_COOKIE["y"])
				throw new Exception;
			// Make sure "u" is pending verification
			if (!hasPrivilege(Privileges::UserPendingVerification, $_GET["u"]))
				throw new Exception;
			P::GlobalAlert();
			echo '<div class="narrow-content">
				<h1><i class="fa fa-bus"></i> Almost there...</h1>
				<p>
					<b>Your account has been created, but it\'s not active yet!</b> Please log in to <b>Bancho</b> to activate it.
					You don\'t know how to connect to Ripple? Follow <a target="_blank" href="index.php?p=16&id=1">this guide</a>!<br>
				</p>
				<div class="alert alert-danger animated shake">
					<i class="fa fa-exclamation-triangle"></i>
					<b>Do not let anyone except yourself log into your ripple account from their computer!</b> Get on our <a href="'.$discordConfig["invite_url"].'">Discord server</a>\'s #help channel instead so that we can help you out if you have trouble connecting.
				</div>
				<hr>
				<i class="fa fa-circle-o-notch fa-spin fa-3x fa-fw"></i>
				<h3>Waiting for Bancho login...</h3>
			</div>';
		} catch (Exception $e) {
			redirect("index.php");
		}

	}
}
