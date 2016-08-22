<?php

class IRC {
	const PageID = 36;
	const URL = 'IRC';
	const Title = 'Ripple - IRC';
	const LoggedIn = true;

	public function P() {
		P::GlobalAlert();
		P::MaintenanceStuff();
		echo '
		<div id="content">
			<div align="center">
				<h1><i class="fa fa-link"></i> IRC Token</h1>
				Here you can generate a new IRC token. You can use it to connect to ripple\'s chat using IRC.<br>
				Remember that your IRC token is like a password, anyone who knows it, has access to your account.<br>
				<a href="index.php?p=16&id=11">Click here to know how to connect to ripple through IRC</a><br><br>
				<a href="submit.php?action=IRC" type="button" class="btn btn-primary"><i class="fa fa-refresh"></i>	Generate a new IRC token</a><br>
				<i>Your old token won\'t be valid anymore</i>
			</div>
		</div>';
	}

	public function D() {
		startSessionIfNotStarted();
		$GLOBALS["db"]->execute("DELETE FROM irc_tokens WHERE userid = ? LIMIT 1", [$_SESSION["userid"]]);
		while (true) {
			$token = randomString(32, '123456789abcdef');
			$tokenMD5 = md5($token);
			$notUnique = $GLOBALS["db"]->fetch("SELECT id FROM irc_tokens WHERE token = ? LIMIT 1", [$tokenMD5]);
			if ($notUnique)
				continue;
			else
				break;
		}
		$GLOBALS["db"]->execute("INSERT INTO irc_tokens (id, userid, token) VALUES (NULL, ?, ?)", [$_SESSION["userid"], $tokenMD5]);
		addSuccess("Your new IRC token is <code>$token</code>. The old IRC token is not valid anymore.<br>Keep it safe, don't show it around, and store it now! We won't show it to you again.");
		redirect('index.php?p=36');
	}
}
