<?php

class APITokens {
	static function GetToken() {
		if (isset($_SESSION["APIToken"])) {
			return $_SESSION["APIToken"];
		}
		$key = randomString(32, '0123456789abcdef');
		$GLOBALS["db"]->execute("INSERT INTO tokens(user, description, token, private) VALUES (?, ?, ?, '1')", array($_SESSION['userid'], $_SERVER['REMOTE_ADDR'], md5($key)));
		$_SESSION["APIToken"] = $key;
		return $key;
	}
}
