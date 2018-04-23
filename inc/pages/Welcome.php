<?php
class Welcome {
	const PageID = 39;
	const URL = 'welcome';
	const Title = 'Welcome to Ripple!';
	const LoggedIn = false;
	public $mh_GET = ["u"];

	public function P() {
		global $discordConfig;
		try {
			// Make sure we have the "y" token
			if (!isset($_COOKIE["y"]))
				throw new Exception;
			// Make sure the "y" token is the correct one for this user
			$idt = getIdentityToken($_GET["u"], false);
			if ($idt == false || $idt != $_COOKIE["y"])
				throw new Exception;
			// Make sure "u" is not pending verification
			if (hasPrivilege(Privileges::UserPendingVerification, $_GET["u"]))
				redirect("index.php?p=38&u=".$_GET["u"]);
			P::GlobalAlert();
			echo '<div class="narrow-content">';

			if (hasPrivilege(Privileges::UserPublic, $_GET["u"])) {
				echo '<h1><i class="fa fa-hand-peace-o"></i> Welcome to Ripple!</h1>
				<p>
					<b>Congratulations, your account is now active!</b> Have fun playing on Ripple!<br>
					You can now <a href="index.php?p=2">log in</a> from the website and start playing ingame!<br>
				</p>
				<hr>
				<b>Some pages you may want to check:</b>
				<h4><i class="fa fa-lock"></i>	<a target="_blank" href="index.php?p=2"> Login</a></h4>
				<h4><i class="fa fa-gavel"></i>	<a target="_blank" href="index.php?p=23">Rules</a></h4>
				<h4><i class="fa fa-question-circle"></i>	<a target="_blank" href="index.php?p=14">Help</a></h4>
				<h4><i class="fa fa-link"></i>	<a target="_blank" href="index.php?p=16&id=1">Connection guide</a></h4>
				<h4><i class="fa fa-server"></i>	<a target="_blank" href="http://status.ripple.moe">Server Status</a></h4>
				<h4><i class="fa fa-comment"></i>	<a target="_blank" href="'.$discordConfig["invite_url"].'">Official Discord</a></h4>
				<h4><i class="fa fa-reddit"></i>	<a target="_blank" href="https://reddit.com/r/osuripple">Official Subreddit</a></h4>';
			} else {
				echo '<h1><i class="fa fa-hand-paper-o"></i> No.</h1>
				<h3>Multiaccounts are not allowed on Ripple.</h3>
				<hr>
				<p>
					<div class="alert alert-danger" role="alert"><i class="fa fa-exclamation-triangle"></i>	Your new account has been <b>banned</b> and your main account has been <b>restricted</b>. You can appeal in a month by sending an email to <b>support@ripple.moe</b>. You\'d better read the rules next time.</div>
				</p>';
			}

			echo '</div>';
		} catch (Exception $e) {
			redirect("index.php");
		}
	}
}
