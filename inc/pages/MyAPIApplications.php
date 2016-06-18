<?php
class MyAPIApplications {
	const PageID = 32;
	const URL = 'myApplications';
	const Title = 'Ripple - My Applications';
	const LoggedIn = true;

	public function P() {
		$myApps = $GLOBALS['db']
			->fetchAll("SELECT id, name FROM api_applications WHERE owner = ?", [$_SESSION['userid']]);
		?>
		<div id="narrow-content" style="width:500px">
			<h1><i class="fa fa-plane"></i> My API applications</h1>
			<?php
			if (!$myApps) {
				echo '<b>Looks like you don\'t have any API application! (yet!)';
			} else {
				echo '<ul style="text-align:left;">';
				foreach ($myApps as $app) {
					echo "<li><a href='#$app[id]'>" . (trim($app['name']) == '' ? "(No title)" : htmlentities($app['name'])) . "</a></li>";
				}
				echo '</ul>';
			}
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
