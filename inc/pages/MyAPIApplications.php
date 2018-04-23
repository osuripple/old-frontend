<?php
class MyAPIApplications {
	const PageID = 32;
	const URL = 'myApplications';
	const Title = 'Ripple - My Applications';
	const LoggedIn = true;

	public function P() {
		P::GlobalAlert();
		$p = (int) @$_GET["page"];
		if ($p < 0) $p = 0;
		$myApps = $GLOBALS['db']
			->fetchAll("SELECT id, name FROM api_applications WHERE owner = ? LIMIT " . $p * 50 . ",50", [$_SESSION['userid']]);
		?>
		<div class="narrow-content" style="width:500px">
			<h1><i class="fa fa-plane"></i> My API applications</h1>
			<p>The Ripple public API allows developers to easily make applications and utilities that integrate with Ripple. If you don't know what an API is, you probably don't need to do anything on this page.</p>
			<p><a href="https://en.wikipedia.org/wiki/Application_programming_interface">API (Wikipedia)</a> | <a href="https://git.zxq.co/ripple/api-docs/wiki">Ripple API documentation</a></p>
			<?php
			if (!$myApps) {
				echo '<b>Looks like you don\'t have any API applications! (yet!)</b>';
			} else {
				echo '<ul style="text-align:left;">';
				foreach ($myApps as $app) {
					echo "<li><a href='index.php?p=33&id=$app[id]'>" . (trim($app['name']) == '' ? "(No title)" : htmlentities($app['name'])) . "</a></li>";
				}
				echo '</ul>';
			}
			?><?php
			$els = [];
			if ($p > 0) $els[] = '<a href="index.php?p=32&page=' . ($p - 1) . '">Previous page</a>';
			if (count($myApps) >= 50) $els[] = '<a href="index.php?p=32&page=' . ($p + 1) . '">Next page</a>';
			if (count($els) > 0) echo '<br><br>' . implode(' | ', $els);
			?><br><br>
			<a href="submit.php?action=myApplications"><button type="button" class="btn btn-primary">New application</button></a>
		</div>
		<?php
	}
	public function D() {
		// memes
		startSessionIfNotStarted();
		$oauth_token = randomString(50, '123456789abcdef');
		$GLOBALS['db']->execute('INSERT INTO api_applications(owner, name, description, oauth_token)
			VALUES(?, "", "", ?)', [$_SESSION['userid'], md5($oauth_token)]);
		addSuccess("Application generated! Edit the applications to change its details.<br>The token is <code>$oauth_token</code>. Keep it safe, don't show it around, and store it now! We won't show it to you again.");
		redirect('index.php?p=32');
	}
}
