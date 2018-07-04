<?php
/**
 * RememberCookieHandler
 * A simple way to remember an user over time.
 *
 * @author Howl <the@howl.moe>
 * @version 1.1
 */
class RememberCookieHandler {
	/**
	 * Check
	 * Checks whether the user is currently not logged in and has the required
	 * cookies for the "stay logged in" thing.
	 *
	 * @return bool true if the cookies are valid, false otherwise.
	 */
	public function Check() {
		startSessionIfNotStarted();
		return !isset($_SESSION["username"]) && @$_COOKIE["sli"] != "";
	}

	/**
	 * Validate
	 * Checks if a remember cookie is ok, and logs in the user if it is.
	 *
	 * @return int
	 * @see ValidateValue
	 */
	public function Validate() {
		$parts = explode("|", $_COOKIE["sli"], 2);
		if (count($parts) < 2) {
			$this->UnsetCookies();
			return ValidateValue::Failure;
		}
		$r = $GLOBALS["db"]->fetch("SELECT id, userid, token_sha FROM remember WHERE series_identifier = ? LIMIT 1",
			[$parts[0]]);
		if (!$r) {
			$this->UnsetCookies();
			return ValidateValue::Failure;			
		}
		if ($r["token_sha"] == hash("sha256", $parts[1])) {
			// all checks successful, login
			// login will return either NowLoggedIn or UserBanned
			return $this->Login($r["userid"]);
		}
		// was not equal
		// this means that someone's trying to access this user's account
		// kick their dick
		// glory to arstotzka
		$GLOBALS["db"]->execute("DELETE FROM remember WHERE id = ? LIMIT 1", [$r['id']]);
		$this->UnsetCookies();
		return ValidateValue::Thieving;
	}

	/**
	 * IssueNew
	 * Issue new permanent cookie for auto-login.
	 */
	public function IssueNew($u) {
		$num = unpack("L", random_bytes(4))[1];
		$tok = base64_encode(random_bytes(75));
		setcookie("sli", ((string)$num) . "|" . $tok, time() + 60 * 60 * 24 * 30 * 3);
		$GLOBALS["db"]->execute("INSERT INTO remember(userid, series_identifier, token_sha) VALUES
			(?, ?, ?)", [$u, $num, hash("sha256", $tok)]);
	}

	/**
	 * Destroy
	 * Destroys a particular sid and token in the database.
	 */
	public function Destroy() {
		if (!isset($_SESSION["userid"]))
			return;
		$GLOBALS["db"]->execute("DELETE FROM remember WHERE userid = ? AND series_identifier = ? LIMIT 1;", 
			[$_SESSION["userid"], explode("|", $_COOKIE["sli"])[0]]);
		$this->UnsetCookies();
	}

	/**
	 * DestroyAll
	 * Destroys all sids and tokens for the user in the database.
	 *
	 * @param int $u UserID
	 */
	public function DestroyAll($u) { 
		$GLOBALS["db"]->execute("DELETE FROM rememeber WHERE userid = ?;", [$u]);
	}

	/**
	 * Login
	 * Login into user's account, onto successful validation.
	 *
	 * @return int ValidateValue
	 */
	private function Login($userID) {
		$u = $GLOBALS['db']->fetch("SELECT id, username, privileges, password_md5 
			FROM users WHERE id = ? LIMIT 1", [$userID]);
		if (!$u || (($u["privileges"] & Privileges::UserNormal) === 0)) {
			$this->UnsetCookies();
			return ValidateValue::UserBanned;
		}
		// set cookie for another 3 months
		setcookie("sli", $_COOKIE["sli"], time() + 60 * 60 * 24 * 30 * 3);
		startSessionIfNotStarted();
		$_SESSION['username'] = $u['username'];
		$_SESSION['userid'] = $u['id'];
		$_SESSION['password'] = $u['password_md5'];
		$_SESSION['passwordChanged'] = false;
		logIP($u['id']);
		// Get safe title
		updateSafeTitle();
		// Save latest activity
		updateLatestActivity($u['id']);
		return ValidateValue::NowLoggedIn;
	}

	/**
	 * UnsetCookies
	 * Unset the sli cookie in the user's browser.
	 */
	public function UnsetCookies() {
		unsetCookie("sli");		
	}
}

abstract class ValidateValue {
	const UserBanned  = -2; // user is banned, show warning
	const Thieving    = -1; // someone appears to be thieving. the token is deleted from db for security
	const Failure     = 0;  // cookie has been removed
	const NowLoggedIn = 1;  // the user is now logged in successfully
}
