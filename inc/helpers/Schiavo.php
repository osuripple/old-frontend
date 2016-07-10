<?php

class Schiavo {
	/**
	 * Sends a message to the channel #bunker.
	 *
	 * @param string $message
	 */
	static function Bunk($message) {
		return __schiavoCall("bunk", $message);
	}

	static function CM($message) {
		return __schiavoCall("cm", $message);
	}
}

// Can a static function call a private function in the same class?
// I don't remember. And don't wanna test to figure it out. So fuck it.
function __schiavoCall($c, $message) {
	global $schiavoConfig;
	return file_get_contents($schiavoConfig["url"] . $c . "?message=" . urlencode("**old-frontend** " . $message));
}
