<?php
class EditApplication {
	const PageID = 33;
	const URL = 'editApplication';
	const Title = 'Ripple - Edit API application';
	const LoggedIn = true;
	public $mh_GET = ['id'];
	public $mh_POST = ['id', 'name', 'description'];

	public function P() {
		P::GlobalAlert();
		$app = $GLOBALS['db']
			->fetch("SELECT id, name, description FROM api_applications WHERE id = ? AND owner = ?",
				[$_GET['id'], $_SESSION['userid']]);
		if (!$app) {
			P::ExceptionMessage('That application could not be found!');
			return;
		}
		?>
		<div class="narrow-content" style="width:500px">
			<h1><i class="fa fa-plane"></i> Edit application</h1><br>
			<p>Here you can change your application's information. This will be shown to users when they are prompted to log in into your application.</p>
			<form action="submit.php?action=editApplication" method="post">
				<input name="name" class="form-control" placeholder="Application name" maxlength="64" spellcheck="false" value="<?= htmlentities($app["name"], ENT_QUOTES); ?>">
				<br>
				<textarea name="description" class="form-control" placeholder="Application description" maxlength="2000" spellcheck="false" rows="4"><?= htmlentities($app["description"]); ?></textarea>
				<br>
				<input type="hidden" name="id" value="<?= $app['id'] ?>">
				<a href="index.php?p=32"><button type="button" class="btn btn-default">Go back</button></a>
				<button type="button" class="btn btn-danger" onclick='reallysure("submit.php?action=deleteApplication&id=<?= $app['id'] ?>");'>Delete</button>
				<button type="submit" class="btn btn-primary">Save</button>
			</form>
		</div>
		<?php
	}
	public function D() {
		// TODO: Error if user is restricted
		startSessionIfNotStarted();
		$GLOBALS['db']->execute('UPDATE api_applications SET name = ?, description = ? WHERE id = ? AND owner = ? LIMIT 1',
			[$_POST["name"], $_POST["description"], $_POST["id"], $_SESSION["userid"]]);
		addSuccess("Changes saved!");
		redirect("index.php?p=33&id=" . $_POST["id"]);
	}
}
