<?php

class Schiavo {
	/**
	 * Sends a message to the channel #bunker.
	 *
	 * @param string $message
     * @param int $cm
	 */
	static function Bunk($message, $cm = 0) {
		//if ($_SERVER["REMOTE_ADDR"] != "127.0.0.1")
			return file_get_contents("http://zxq.co:31155/bunk?message=" . urlencode("**old-frontend** " . $message) . ($cm != 0 ? "&chan=182600210206294026" : ""));
	}
}
