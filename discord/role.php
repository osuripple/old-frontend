<?php
require_once '../inc/functions.php';

api_start();
try {
    checkDiscordSecret();
    $i = json_decode(file_get_contents("php://input"));
    if (!$i->uid || !$i->name || !$i->colour) {
        throw new Exception("Missing required arguments. " . $i, 400);
    }
    try {
        $strColour = ltrim(strtolower($i->colour), "#");
        $i->colour = hexdec($i->colour);
        if ($i->colour < 0 || $i->colour > 16777215) {
            throw new Exception();
        }
    } catch (Exception $e) {
        throw new Exception("Invalid colour", 400);
    }
    $r = $GLOBALS["db"]->fetch(
        "SELECT roleid, discordid
        FROM discord_roles
        JOIN users ON userid = users.id
        WHERE userid = ?
        AND privileges & " .(Privileges::UserDonor) . " > 0
        AND donor_expire > UNIX_TIMESTAMP()",
        [$i->uid],
    );
    if (!$r) {
        throw new Exception("You are not a donor", 403);
    }
    $bot = new \RestCord\DiscordClient(
        ["token" => $discordConfig["bot_token"]]
    );
    if ($r["roleid"] == 0) {
        $donorPosition = 0;
        $roles = $bot->guild->getGuildRoles([
            "guild.id" => $discordConfig["guild_id"]
        ]);
        foreach ($roles as $role) {
            if ($role->id == $discordConfig["donor_role_id"]) {
                $donorPosition = $role->position;
                break;
            }
        }
        if ($donorPosition == 0) {
            throw new Exception("Donator role not found in guild roles", 500);
        }
        $role = $bot->guild->createGuildRole([
            "guild.id" => $discordConfig["guild_id"],
            "name" => $i->name,
            "color" => $i->colour,
            "hoist" => false,
            "mentionable" => false,
        ]);
        if ($role === null) {
            throw new Exception(
                "Could not create discord role e mi inibisce l'uso del microfono",
                500
            );
        }
        $bot->guild->modifyGuildRolePositions([[
            "guild.id" => $discordConfig["guild_id"],
            "id" => $role->id,
            "position" => $donorPosition + 1,
        ]]);
        $bot->guild->addGuildMemberRole([
            "guild.id" => $discordConfig["guild_id"],
            "user.id" => (int)$r["discordid"],
            "role.id" => $role->id,
        ]);
        $roleID = $role->id;
    } else {
        $bot->guild->modifyGuildRole([
            "guild.id" => $discordConfig["guild_id"],
            "role.id" => $r["roleid"],
            "name" => $i->name,
            "color" => $i->colour,
        ]);
        $roleID = $r["roleid"];
    }
    // TODO: Track time (max once every 30s or so)
    $GLOBALS["db"]->execute(
        "UPDATE discord_roles SET `name` = ?, colour = ?, roleid = ? WHERE userid = ?",
        [$i->name, $strColour, $roleID, $i->uid],
    );
    $output = api_succ();
} catch (Exception $e) {
    Sentry\captureException($e);
    $output = api_error($e);
} finally {
    api_output($output);
}