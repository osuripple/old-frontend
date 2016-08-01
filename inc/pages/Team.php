<?php

class Team {
	const PageID = 35;
	const URL = 'Team';
	const Title = 'Ripple - Team';
	public $error_messages = [];
	public $mh_GET = [];
	public $mh_POST = ["url"];

	public function P() {

		P::GlobalAlert();
		echo '
		<div id="content">
			<div align="center">
				<h1><i class="fa fa-star"></i> Ripple Team</h1>
				<h4>This is the list of the people who keep ripple up and running and deal with its community.</h4>';

				echo '<hr>
				<h3><i class="fa fa-gavel"></i>	Community Managers</h3>
				Community Managers deal with bans, silences, name changes and pretty much everything that has to do with the community.<br>
				They are take care of our Discord server and reply to email sent to support@ripple.moe too. Community managers have a red name in game chat.<br><br>';
				self::printTeam("community manager");
				//echo '<div style="margin-bottom: 25%;"></div>';

				echo '<hr><h3><i class="fa fa-code"></i>	Developers</h3>
				Developers add new features to the server, squash bugs, keep the server up and running and take care of its maintenance.<br>
				They don\'t do anything related to the community. You can distringuish them because they have a blue name in game chat.<br><br>';
				self::printTeam("developer");
				//echo '<div style="margin-bottom: 25%;"></div>';

				echo '<hr><h3><i class="fa fa-reply"></i>	How to contact the team</h3>
				You can find every member of the team in our Discord server. If you want to speak privately with us, you can send an email to support@ripple.moe and a Community Manager will reply as soon as possible. If you want to contact a specific member of the team, you can click on the envelope button under their name in this page to send him an email. Remember that Developers cannot help you with bans, silences and such. Developers are able to help you only if you have technical questions or issues.<br>
				If you want to appeal your ban/restriction or report someone, send an email to support@ripple.moe (which is managed by CMs) instead.';
				echo '<div style="margin-bottom: 10%;"></div>';
			echo '</div>
		</div>';
	}

	public function printTeam($groupName) {
		global $teamConfig;
		$dudes = $GLOBALS["db"]->fetchAll("SELECT users.username, users.id, users.email FROM users LEFT JOIN privileges_groups ON (users.privileges = privileges_groups.privileges OR users.privileges = privileges_groups.privileges | ".Privileges::UserDonor.") WHERE privileges_groups.name = ? AND users.id > 999", [$groupName]);
		echo '<div class="row">';
		foreach ($dudes as $i => $dude) {
			echo '
			<div class="col-lg-'.round(12/floor(count($dudes))).' col-sm-6 text-center">
				<img class="img-circle img-center" src="' . URL::Avatar() . '/'.$dude["id"].'"><br>
				<span class="teammate-name"><a class="silent" href="index.php?u='.$dude["id"].'">'.$dude["username"].'</a></span><br>';
				if (array_key_exists("name", $teamConfig[$dude["id"]])) {
					echo '<h4 style="display: inline;">'.$teamConfig[$dude["id"]]["name"].'</h4><br>';
				}
				if (array_key_exists("role", $teamConfig[$dude["id"]])) {
					echo '<h5>'.$teamConfig[$dude["id"]]["role"].'</h5>';
				}

				if (array_key_exists("twitter", $teamConfig[$dude["id"]])) {
					echo '<span class="fa-stack">
						<a href="https://twitter.com/'.$teamConfig[$dude["id"]]["twitter"].'">
							<i class="fa fa-circle fa-stack-2x"></i>
							<i class="fa fa-twitter fa-stack-1x fa-inverse"></i>
						</a>
					</span>';
				}

				$email = array_key_exists("email", $teamConfig[$dude["id"]]) ? $teamConfig[$dude["id"]]["email"] : $dude["email"];
				echo '<span class="fa-stack">
					<a href="mailto:'.$email.'">
						<i class="fa fa-circle fa-stack-2x"></i>
						<i class="fa fa-envelope fa-stack-1x fa-inverse"></i>
					</a>
				</span>';

				if (array_key_exists("github", $teamConfig[$dude["id"]])) {
					echo '<span class="fa-stack">
						<a href="http://github.com/'.$teamConfig[$dude["id"]]["github"].'">
							<i class="fa fa-circle fa-stack-2x"></i>
							<i class="fa fa-github-alt fa-stack-1x fa-inverse"></i>
						</a>
					</span>';
				}

			echo '</div>';
		}
		echo '</div>';
	}
}
