<?php
class Discord {
	const PageID = 40;
	const URL = 'Discord';
	const Title = 'Ripple - Discord Donor';
	const LoggedIn = true;

	function __construct() {
		global $discordConfig;
		global $URL;
		$this->provider = new \Discord\OAuth\Discord([
			'clientId'     => $discordConfig["client_id"],
			'clientSecret' => $discordConfig["client_secret"],
			'redirectUri'  => $URL["server"]."/index.php?p=40",
		]);
	}

	public function P() {
		global $discordConfig;
		global $URL;
		startSessionIfNotStarted();
		if (!hasPrivilege(Privileges::UserDonor))
			redirect("/");

		echo '<div id="content">
				<div align="center">
					<h1><i class="fa fa-comments"></i> Discord Donor Privileges</h1>';
		if (!isset($_GET["code"]) || empty($_GET["code"])) {
			P::GlobalAlert();
			P::MaintenanceStuff();
			$discordID = $GLOBALS["db"]->fetch("SELECT discordid FROM discord_roles WHERE userid = ? LIMIT 1", [$_SESSION["userid"]]);
			if ($discordID == false || $discordID["discordid"] == 0) {
			echo '<b>Donors get special privileges on our Discord server too!</b><br>
				Discord is a chatroom with text and voice channels, bots and lots of other cool features.<br>
				You can <b>download Discord for free <a href="http://discord.gg/" target="_blank">here</a></b> and you can <b>join our official Discord server <a href="'.$discordConfig["invite_url"].'" target="_blank">here</a></b>.<br>
				<br>
				<h3>Donors get the following privileges on Discord:</h3>
				<h4 style="display: inline">1.</h4> Access to /nick command, to change your Discord nickname<br>
				<h4 style="display: inline">2.</h4> Access to #donators text and voice channels<br>
				<h4 style="display: inline">4.</h4> Username on donors list<br>
				<h4 style="display: inline">3.</h4> Custom role with custom username
				<hr>
				<b><a href="'.$discordConfig["invite_url"].'" target="_blank">Join our Discord server</a>, then click this button</b><br><a href="'.$this->provider->getAuthorizationUrl().'" type="button" class="btn btn-danger"><i class="fa fa-heart"></i>	Get Discord donor privileges</a>';
			} else {
				echo '<p class="half">Your discord account has been <b>linked</b> to this Ripple account. <b>Welcome to the donors club and thank you for supporting us!</b>
			You have now access to the <b>#donators</b> text and voice channels on our official Discord server!
			You can also set a <b>custom role</b> name and username <b>color</b> and change your <b>nickname</b> on Discord.
			If you want to change your username, you can use the <code>/nick</code> command.
			To set or edit your <b>custom role</b> name and color, use the command <code>!role HEX_COLOR ROLE_NAME</code>.
			You can pick your HEX color <a href="http://www.colorpicker.com/" target="_blank">here</a>, it\'s the one that starts with \'#\'
			You can change your role name and color <b>whenever you want!</b>
			<br><br>
			<h4>Thank you for supporting us and have fun on Ripple!</h4></p>';
			}
		} else {
			$this->D();
		}
		echo '</div>
		</div>';
	}

	public function D() {
		startSessionIfNotStarted();
		$d = $this->DoGetData();
		if (isset($d["error"])) {
			addError($d['error']);
		}
		redirect("index.php?p=40");
	}

	public function DoGetData() {
		global $discordConfig;
		global $URL;
		try {
			startSessionIfNotStarted();
			if (!hasPrivilege(Privileges::UserDonor))
				throw new Exception('Don\'t even try');

			// Make sure this account is not already linked to a discord account
			$exists = $GLOBALS["db"]->fetch("SELECT id FROM discord_roles WHERE discordid != 0 AND userid = %s", [$_SESSION["userid"]]);
			if ($exists)
				throw new Exception('This Ripple account is already linked to a Discord account.');

			// Get discord user ID
			$token = $this->provider->getAccessToken("authorization_code", [
				"code" => $_GET["code"],
			]);
			$user = $this->provider->getResourceOwner($token);
			$discordID = $user->id;

			// Donor bot API request
			$data = [
				"secret" => $discordConfig["donor_bot_secret"],
				"discord_id" => $discordID
			];
			$botResponse = postJsonCurl($discordConfig["donor_bot_url"]."/api/v1/give_donor", $data, 60);
			if ($botResponse["status"] == 404) {
				throw new Exception('<b>It seems you haven\'t joined the server yet</b>. Please <a href="'.$discordConfig["invite_url"].'" target="_blank">join our Discord server</a> and try again.');
			} else if ($botResponse["status"] != 200) {
				throw new Exception("<b>Error ".htmlspecialchars($botResponse["status"]).". ".htmlspecialchars($botResponse["message"])."</b><br> Try again later. If this keeps happening, contact a developer.");
			}

			// Save discord ID/ripple ID in database
			$GLOBALS["db"]->execute("INSERT INTO discord_roles (id, userid, discordid, roleid) VALUES (NULL, ?, ?, 0)", [$_SESSION["userid"], $discordID]);

			// Redirect to the page again
			redirect("index.php?p=40");
		} catch (Exception $e) {
			$ret["error"] = $e->getMessage();
		}

		return $ret;
	}
}
