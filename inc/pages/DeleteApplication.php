<?php
class DeleteApplication {
	const URL = 'deleteApplication';

	public function D() {
		// TODO: Error if user is restricted (?)
		if (empty($_GET['id'])) {
			addError("DON'T YOU TRY IT, DON'T YOU TRYYYYY IIIT ~");
		} else {
			$GLOBALS['db']->execute('DELETE FROM api_applications WHERE id = ? AND owner = ? LIMIT 1',
				[$_GET['id'], $_SESSION['userid']]);
			addSuccess("That application was deleted.");
		}
		redirect('index.php?p=32');
	}
}
