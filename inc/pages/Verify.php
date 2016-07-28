<?php
class Verify {
	const PageID = 38;
	const URL = 'verify';
	const Title = 'Ripple - Verify your account';
	const LoggedIn = false;
	public $mh_GET = ["u"];

	public function P() {
		// Make sure the "y" token is the one from the correct user
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
			echo '<div id="narrow-content">
				<h1><i class="fa fa-bus"></i> Almost there...</h1>
				<p>
					<b>Your account has been created, but it\'s not active yet!</b> Please log in to <b>Bancho</b> to activate it.
					You don\'t know how to connect to Ripple? Follow <a target="_blank" href="index.php?p=16&id=1">this guide</a>!<br>
				</p>
				<hr>
				<i class="fa fa-circle-o-notch fa-spin fa-3x fa-fw"></i>
				<h3>Waiting for verification...</h3>
			</div>';
		} catch (Exception $e) {
			redirect("index.php");
		}

	}
}
