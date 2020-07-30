<?php
require_once '../inc/functions.php';
api_start();
try {
    checkDiscordSecret();
    if (!is_numeric(@$_GET["uid"])) {
        throw new Exception("Missing argument uid", 400);
    }
    $uid = (int)$_GET["uid"];
    try {
        unlinkDiscord($uid);
    } catch (DiscordAlreadyUnlinkedException $e) {
        throw new Exception("Discord account already unlinked", 400);
    }
    $output = api_succ();
} catch (Exception $e) {
    Sentry\captureException($e);
    $output = api_error($e);
} finally {
    api_output($output);
}