<?php

class Privileges {
	const UserBanned			= 0;
	const UserPublic			= 1;
	const UserNormal			= 2 << 0;
	const UserDonor				= 2 << 1;
	const AdminAccessRAP		= 2 << 2;
	const AdminManageUsers		= 2 << 3;
	const AdminBanUsers			= 2 << 4;
	const AdminSilenceUsers		= 2 << 5;
	const AdminWipeUsers		= 2 << 6;
	const AdminManageBeatmaps	= 2 << 7;
	const AdminManageServers	= 2 << 8;
	const AdminManageSettings	= 2 << 9;
	const AdminManageBetaKeys	= 2 << 10;
	const AdminManageReports	= 2 << 11;
	const AdminManageDocs		= 2 << 12;
	const AdminManageBadges		= 2 << 13;
	const AdminViewRAPLogs		= 2 << 14;
	const AdminManagePrivileges	= 2 << 15;
	const AdminSendAlerts		= 2 << 16;
	const AdminChatMod			= 2 << 17;
	const AdminKickUsers		= 2 << 18;
	const UserPendingVerification = 2 << 19;
	const UserTournamentStaff 	= 2 << 20;
	const AdminCaker			= 2 << 21;
	const AdminViewTopScores	= 2 << 22;
}
