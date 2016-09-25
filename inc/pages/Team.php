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
		P::MaintenanceStuff();
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
				They don\'t do anything related to the community. You can distinguish them because they have a blue name in game chat.<br><br>';
				self::printTeam("developer");

				echo '<hr><h3><i class="fa fa-music"></i>	BATs</h3>
				BATs play beatmaps in the ranking queue and decide whether they are good enough to be ranked or not.<br><br>';
				self::printTeam("bat");
				echo '<div style="margin-bottom: 5%;"></div>';

				echo '<hr><h3><i class="fa fa-heart"></i>	Special thanks</h3>
					<i class="fa fa-circle fa-bullet-list"></i>	
					<b>Franc[e]sco/lolisamurai</b>, for <a href="https://github.com/Francesco149/oppai" target="_blank">oppai</a>, used as standard pp calculator.<br>
					oppai is licensed under GPL v3.
					Our implementation can be found <a href="https://git.zxq.co/ripple/lets/src/master/pp/rippoppai.py" target="_blank">here</a>.<br>
					<div class="small-br"></div>
					<i class="fa fa-circle fa-bullet-list"></i>	<b>Tom94</b>, for <a href="https://github.com/ppy/osu-performance" target="_blank">osu-performance</a>, used as a reference for our mania pp calculator.<br>
					osu-performance is licensed under AGPL v3. Our implementation can be found <a href="https://git.zxq.co/ripple/lets/src/master/pp/wifipiano2.py" target="_blank">here</a>.<br>
					<div class="small-br"></div>
					<i class="fa fa-circle fa-bullet-list"></i>	<b>jrosdahl</b>, for <a href="https://github.com/jrosdahl/miniircd" target="_blank">miniircd</a>, used as a base for our IRC server.<br>
					miniircd is licensed under GPL v2. Our implementation can be found <a href="https://git.zxq.co/ripple/pep.py/src/master/irc/ircserver.py" target="_blank">here</a>.<br>
					<div class="small-br"></div>
					<i class="fa fa-circle fa-bullet-list"></i>	<b>Avail</b>, for hosting Ripple on his server.
					<div class="small-br"></div>
					<i class="fa fa-circle fa-bullet-list"></i>	<b>Angela Guerra</b>, for Ripple\'s logo.
					<div class="small-br"></div>
					<i class="fa fa-circle fa-bullet-list"></i>	<b><a href="#" data-toggle="modal" data-target="#donorsModal">Everyone</a></b> who has supported the Ripple project by donating or inviting other people.<br><b><i>Without you, Ripple would not have become what it is now.</b></i>';
				echo '<hr><h3><i class="fa fa-reply"></i>	How to contact the team</h3>
				You can find every member of the team in our Discord server. If you want to speak privately with us, you can send an email to support@ripple.moe and a Community Manager will reply as soon as possible. If you want to contact a specific member of the team, you can click on the envelope button under their name in this page to send him an email. Remember that Developers cannot help you with bans, silences and such. Developers are able to help you only if you have technical questions or issues.<br>
				If you want to appeal your ban/restriction or report someone, send an email to support@ripple.moe (which is managed by CMs) instead.';

		echo '<div class="modal fade" id="donorsModal" tabindex="-1" role="dialog" aria-labelledby="donorsModalLabel">
				<div class="modal-dialog" role="document">
					<div class="modal-content">
						<div class="modal-header">
							<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
							<h4 class="modal-title" id="donorsModalLabel"><i class="fa fa-heart"></i>	Thanks to those who supported us</h4>
						</div>
						<div class="modal-body">
							<div class="container" style="width: 100%">';
							$donors = $GLOBALS["db"]->fetchAll("SELECT id, username FROM users WHERE privileges & 3 > 0 AND donor_expire > 0 ORDER BY donor_expire DESC LIMIT 60");
							foreach ($donors as $i => $donor) {
								if ($i % 3 == 0)
									echo "</div>";
								if ($i % 3 == 0 || $i == 0)
									echo "<div class='row' style='margin-bottom: 7px;'>";
								echo "<div class='col-sm-4'>
									<a href='index.php?u=$donor[id]' target='_blank'><img src='//a.ripple.moe/$donor[id]' class='img-circle' style='width: 25px; height: 25px; float: left; margin-right: 5px;'></img><span style='float: left;'>$donor[username]</span></a>
								</div>";
							}
							if (count($donors) % 3 != 0) {
								echo "</div>";
							}
							$donorsCount = $GLOBALS["db"]->fetch("SELECT COUNT(*) as count FROM users WHERE privileges & 3 > 0 AND donor_expire > 0");
							if ($donorsCount["count"] > 60) {
								$c = $donorsCount["count"]-60;
								echo "<div class='row' style='margin-top: 30px;'><b>...and $c more people</b></div>";
							}
						echo '<hr><b>Do you want to be in this list?<br><a href="index.php?p=34">Support us with a donation!</b></a><br><i>(You get other cool perks too)</i>.</div>
						</div>
					</div>
				</div>
			</div>';
				echo '<div style="margin-bottom: 10%;"></div>';
			echo '</div>
		</div>';
	}

	public function printTeam($groupName) {
		global $teamConfig;
		global $teamHidden;
		$dudes = $GLOBALS["db"]->fetchAll("SELECT users.username, users.id, users.email FROM users LEFT JOIN privileges_groups ON (users.privileges = privileges_groups.privileges OR users.privileges = privileges_groups.privileges | ".Privileges::UserDonor.") WHERE privileges_groups.name = ? AND users.id > 999", [$groupName]);

		// Remove hidden users
		foreach ($dudes as $i => $dude) {
			if (in_array($dude["id"], $teamHidden)) {
				unset($dudes[$i]);
			}
		}
		
		echo '<div class="row">';
		foreach ($dudes as $i => $dude) {
			echo '
			<div class="col-lg-'.round(12/floor(count($dudes))).' col-sm-6 text-center">
				<img class="img-circle img-center" src="' . URL::Avatar() . '/'.$dude["id"].'" width="100"><br>
				<span class="teammate-name"><a class="silent" href="index.php?u='.$dude["id"].'">'.$dude["username"].'</a></span><br>';
				if (array_key_exists($dude["id"], $teamConfig)) {
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
				}

			echo '</div>';
		}
		echo '</div>';
	}
}
