<?php
require_once '../inc/functions.php';
api_start();
try {
    checkDiscordSecret();
    $expiredUserIDs = $GLOBALS["db"]->fetchAll(
        "SELECT userid
        FROM discord_roles LEFT JOIN users
        ON discord_roles.userid = users.id
        WHERE users.privileges & " . Privileges::UserDonor . " = 0
        OR donor_expire <= UNIX_TIMESTAMP()"
    );
    $c = 0;
    $exceptions = [];
    foreach ($expiredUserIDs as $uid) {
        try {
            unlinkDiscord($uid["userid"]);
            $c++;
        } catch (Exception $e) {
            array_push($exceptions, $e);
        }
    }
    if (!empty($exceptions)) {
        foreach ($exceptions as $e) {
            Sentry\captureException($e);
        }
        throw new Exception("Could not unlink some expired accounts.", 500);
    }
    $output = api_succ(["unlinked" => $c]);
} catch (Exception $e) {
    Sentry\captureException($e);
    $output = api_error($e);
} finally {
    api_output($output);
}