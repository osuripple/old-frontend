<?php

class RequestRankedBeatmap {
	const PageID = 31;
	const URL = 'RequestRankedBeatmap';
	const Title = 'Ripple - Request Beatmap Ranking';
	const LoggedIn = true;
	public $error_messages = [];
	public $mh_GET = [];
	public $mh_POST = ["url"];

	public function P() {
		global $ScoresConfig;
		P::GlobalAlert();
		P::MaintenanceStuff();
		startSessionIfNotStarted();
		$myRequests = $GLOBALS["db"]->fetch("SELECT COUNT(*) AS count FROM rank_requests WHERE time > ? AND userid = ? LIMIT ".$ScoresConfig["rankRequestsPerUser"], [time()-(24*3600), $_SESSION["userid"]]);
		$rankRequests = $GLOBALS["db"]->fetchAll("SELECT * FROM rank_requests WHERE time > ? ORDER BY time ASC LIMIT ".$ScoresConfig["rankRequestsQueueSize"], [time()-(24*3600)]);
		echo '
		<div id="content">
			<div align="center">
				<h1><i class="fa fa-music"></i> Request beatmap ranking</h1>
				<h4>Here you can send a request to rank an unranked beatmap on ripple.</h4>';
				if ($myRequests["count"] >= $ScoresConfig["rankRequestsPerUser"]) {
					echo '<div class="alert alert-warning" role="alert"><i class="fa fa-warning"></i>	You can send only <b>'.$ScoresConfig["rankRequestsPerUser"].' rank requests</b> every 24 hours. <b>Please come back tomorrow.</b></div>';
					return;
				}
				if (count($rankRequests) >= $ScoresConfig["rankRequestsQueueSize"]) {
					echo '<div class="alert alert-warning" role="alert"><i class="fa fa-warning"></i>	A maximum of <b>'.$ScoresConfig["rankRequestsQueueSize"].' rank requests</b> can be sent every <b>24 hours</b>. No more requests can be submitted for now. <b>Please come back later.</b></div>';
					echo '<hr><h4 style="display: inline;">Estimated time until next request:</h4><br>
					<h3 style="display: inline;">'.timeDifference(time(), $rankRequests[0]["time"]+24*3600, false, "Less than a minute").'</h3>';
					return;
				}
				echo '<hr>
				<h2 style="display: inline;">'.count($rankRequests).'</h2><h3 style="display: inline;">/'.$ScoresConfig["rankRequestsQueueSize"].'</h3><br><h4>requests submitted</h4><h6>in the past 24 hours</h6>
				<hr>
				<div class="alert alert-warning" role="alert"><i class="fa fa-warning"></i>	Every user can send <b>'.$ScoresConfig["rankRequestsPerUser"].' rank requests every 24 hours</b>, and a maximum of <b>'.$ScoresConfig["rankRequestsQueueSize"].' beatmaps</b> can be requested <b>every 24 hours</b> by all users. <b>Remember that troll or invalid maps will still count as valid rank requests, so request only beatmaps that you <u>really</u> want to see ranked, since the number of daily rank requests is limited.</b></div>
				<b>Beatmap/Beatmap set link</b><br>
				<form action="submit.php" method="POST">
					<input name="action" value="RequestRankedBeatmap" hidden>
					<div class="input-group">
						<input type="text" name="url" class="form-control" placeholder="http://osu.ppy.sh/s/xxxxx">
						<span class="input-group-btn">
							<button class="btn btn-success" type="submit"><i class="fa fa-check" aria-hidden="true"></i>	Submit</button>
						</span>
					</div>
				</form>
			</div>
		</div>';
	}

	public function D() {
		startSessionIfNotStarted();
		$d = $this->DoGetData();
		if (isset($d["error"])) {
			addError($d["error"]);
			redirect("index.php?p=31");
		} else {
			// No errors, run botnet to add the new IP address
			addSuccess($d["success"]);
			redirect("index.php?p=31&s=1");
		}
	}

	public function DoGetData() {
		global $ScoresConfig;
		try {
			// Make sure the user is not banned/restricted
			if (!hasPrivilege(Privileges::UserPublic)) {
				throw new Exception("You can't submit beatmap ranking requests while you're restricted.");
			}

			// Make sure the user hasn't requested too many maps
			$myRequests = $GLOBALS["db"]->fetch("SELECT COUNT(*) AS count FROM rank_requests WHERE time > ? AND userid = ? LIMIT ".$ScoresConfig["rankRequestsPerUser"], [time()-(24*3600), $_SESSION["userid"]]);
			if ($myRequests["count"] >= $ScoresConfig["rankRequestsPerUser"]) {
				throw new Exception("You can have only ".$ScoresConfig["rankRequestsPerUser"]." requests every 24 hours.");
			}

			// Make sure < 10 rank requests have been submitted in the past 24 hours
			$rankRequests = $GLOBALS["db"]->fetchAll("SELECT COUNT(*) AS count FROM rank_requests WHERE time > ? LIMIT ".$ScoresConfig["rankRequestsQueueSize"], [time()-(24*3600)]);
			if ($rankRequests["count"] >= $ScoresConfig["rankRequestsQueueSize"]) {
				throw new Exception("A maximum of <b>".$ScoresConfig["rankRequestsQueueSize"]." rank requests</b> can be sent every <b>24 hours</b>. No more requests can be submitted for now.");
			}

			// Make sure the URL is valid
			$matches = [];
			if (!preg_match("/https?:\\/\\/(?:osu|new)\\.ppy\\.sh\\/(s|b)\\/(\\d+)/i", $_POST["url"], $matches)) {
				throw new Exception("Beatmap URL is not an osu.ppy.sh or new.ppy.sh URL.");
			}

			// Make sure the beatmap is not already ranked
			$criteria = $matches[1] == "b" ? "beatmap_id" : "beatmapset_id";
			$ranked = $GLOBALS["db"]->fetch("SELECT id FROM beatmaps WHERE ".$criteria." = ? AND ranked >= 2 LIMIT 1", [$matches[2]]);
			if ($ranked) {
				throw new Exception("That beatmap is already ranked.");
			}

			// Make sure the beatmap was not already requested in the past 24 hours
			// Exact match
			if ($GLOBALS["db"]->fetch("SELECT * FROM rank_requests WHERE bid = ? AND type = ? AND time > ?", [$matches[2], $matches[1], time()-(24*3600)])) {
				throw new Exception("That beatmap was already requested.");
			}

			// Opposite match (if found in db)
			$otherType = $matches[1] == "s" ? "b" : "s";
			$otherCriteria = $criteria == "beatmap_id" ? "beatmapset_id" : "beatmap_id";
			$otherID = $GLOBALS["db"]->fetch("SELECT ".$otherCriteria." FROM beatmaps WHERE ".$criteria." = ?", [$matches[2]]);
			if ($otherID) {
				if ($GLOBALS["db"]->fetch("SELECT * FROM rank_requests WHERE bid = ? AND type = ? AND time > ?", [current($otherID), $otherType, time()-(24*3600)])) {
					throw new Exception("That beatmap was already requested.");
				}
			}

			// Everything seems fine, add rank request in db
			$GLOBALS["db"]->execute("INSERT INTO rank_requests (id, userid, bid, type, time, blacklisted) VALUES (NULL, ?, ?, ?, ?, 0)", [$_SESSION["userid"], $matches[2], $matches[1], time()]);

			// Send schiavo message
			@Schiavo::Bunk("**".$_SESSION["username"]."** has sent a rank request for beatmap **".$_POST["url"]."**");

			// Set success message
			$ret["success"] = "Your beatmap ranking request has been submitted successfully! Our BATs will check your request and eventually rank it.";
		} catch (Exception $e) {
			$ret["error"] = $e->getMessage();
		}

		return $ret;
	}
}
