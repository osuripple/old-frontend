<?php

class APITokens {
	static function GetToken() {
		if (isset($_SESSION["APIToken"])) {
			return $_SESSION["APIToken"];
		}
		$key = randomString(32, '0123456789abcdef');
		$GLOBALS["db"]->execute("INSERT INTO tokens(user, privileges, description, token, private) VALUES (?, '0', ?, ?, '1')", array($_SESSION['userid'], $_SERVER['REMOTE_ADDR'], md5($key)));
		$_SESSION["APIToken"] = $key;
		return $key;
	}
	static function PrintScript() {
		echo '<script>
		// Why, hello there!
		// I see what you\'re doing here.
		// You\'re looking for a way to access the API, arent\'cha?
		// If that\'s the case, get on Discord, and go to the #api channel.
		// Scroll up a bit, and you\'ll find some screenshots that will get
		// you started right away with the API.
		// API documentation will come sometime, don\'t worry.
		var APIToken = "' . self::GetToken() . '";
		</script>';
	}
}
