<?php
require_once '../inc/functions.php';
api_start();

function discord_provider_factory() {
    global $discordConfig;
    return new \Wohali\OAuth2\Client\Provider\Discord([
        "clientId" => $discordConfig["client_id"],
        "clientSecret" => $discordConfig["client_secret"],
        "redirectUri" => $discordConfig["hanayo_base"] . "/settings/discord/finish",
    ]);
}

function oauth_flow_start() {
    if (!is_numeric(@$_GET["uid"])) {
        throw new Exception("Missing uid parameter", 400);
    }
    $uid = (int)$_GET["uid"];
    if ($GLOBALS["db"]->fetch(
        "SELECT discordid FROM discord_roles WHERE userid = ?", [$uid]
    )) {
        throw new Exception("This account is already linked to discord", 400);
    }
    if (!hasPrivilege(Privileges::UserDonor, $uid)) {
        throw new Exception("This account is not a donor", 400);
    }
    $provider = discord_provider_factory();
    $authUrl = $provider->getAuthorizationUrl([
        'scope' => ["identify", "guilds.join"]
    ]);
    $state = $provider->getState();

    redisConnect();
    $GLOBALS["redis"]->set("donorbot:$state", $uid);
    $GLOBALS["redis"]->expire("donorbot:$state", 600);
    return api_succ(["url" => $authUrl]);
}

function oauth_flow_end() {
    global $discordConfig;
    if (!isset($_GET["code"])) {
        throw new Exception("Missing code parameter", 400);
    }

    redisConnect();
    $state = @$_GET["state"];
    $uid = $GLOBALS["redis"]->get("donorbot:$state");
    if (!$uid || !is_numeric($uid)) {
        echo $uid;
        throw new Exception("Broken state", 400);
    }
    $uid = (int)$uid;
    if ($GLOBALS["db"]->fetch(
        "SELECT 1 FROM discord_roles WHERE userid = ?", [$uid]
    )) {
        throw new Exception("The account is already linked", 400);
    }
    if ($GLOBALS["db"]->fetch(
        "SELECT 1 FROM users WHERE id = ? AND (
            donor_expire <= UNIX_TIMESTAMP()
            OR privileges & ".Privileges::UserDonor." = 0
        )", [$uid]
    )) {
        throw new Exception("This account is not a donor", 400);
    }
    $provider = discord_provider_factory();
    $token = $provider->getAccessToken("authorization_code", [
        "code" => $_GET["code"]
    ]);
    $discordUserID = $provider->getResourceOwner($token)->getId();
    $bot = new \RestCord\DiscordClient(["token" => $discordConfig["bot_token"]]);
    $bot->guild->addGuildMember([
        "guild.id" => (int)$discordConfig["guild_id"],
        "user.id" => (int)$discordUserID,
        "access_token" => $token->getToken(),
    ]);
    try {
        $GLOBALS["db"]->execute(
            "INSERT INTO discord_roles (userid, discordid, roleid)
            VALUES (?, ?, 0)",
            [$uid, $discordUserID],
        );
        $bot->guild->addGuildMemberRole([
            "guild.id" => $discordConfig["guild_id"],
            "user.id" => (int)$discordUserID,
            "role.id" => $discordConfig["donor_role_id"],
        ]);
    } catch (Exception $e) {
        $GLOBALS["db"]->execute(
            "DELETE FROM discord_roles WHERE userid = ?", [$uid]
        );
        throw $e;
    }
    $GLOBALS["redis"]->del("donorbot:$state");
    return api_succ();
}

try {
    checkDiscordSecret();
    if (!isset($_GET["code"])) {
        $output = oauth_flow_start();
    } else {
        $output = oauth_flow_end();
    }
} catch (Exception $e) {
    Sentry\captureException($e);
    $output = api_error($e);
} finally {
    api_output($output);
}