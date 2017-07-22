<?php
/*
 * Form submission php file
*/
require_once './inc/functions.php';
try {
	startSessionIfNotStarted();

	// Make sure we are not locked due to 2FA
	redirect2FA();

	// Find what the user wants to do (compatible with both GET/POST forms)
	if (isset($_POST['action']) && !empty($_POST['action'])) {
		$action = $_POST['action'];
	} elseif (isset($_GET['action']) && !empty($_GET['action'])) {
		$action = $_GET['action'];
	} else {
		throw new Exception("Couldn't find action parameter");
	}
	foreach ($pages as $page) {
		if ($action == $page::URL) {
			if (defined(get_class($page).'::LoggedIn')) {
				if ($page::LoggedIn) {
					clir();
				} else {
					clir(true, 'index.php?p=1&e=1');
				}
			}
			checkMustHave($page);
			$page->D();

			return;
		}
	}
	// What shall we do?
	switch ($action) {
		case 'register':
			D::Register();
		break;
		case 'changePassword':
			D::ChangePassword();
		break;
		case 'logout':
			D::Logout();
			redirect('index.php');
		break;
		case 'u':
			redirect('../ripple/index.php?u='.$_GET['data'].'&m=0');
		break;
		case 'recoverPassword':
			D::RecoverPassword();
		break;
		case 'saveUserSettings':
			D::saveUserSettings();
		break;
		case 'forgetEveryCookie':
			D::ForgetEveryCookie();
		break;
		case 'saveUserpage':
			D::SaveUserpage();
		break;
		case 'changeAvatar':
			D::ChangeAvatar();
		break;
		case 'addRemoveFriend':
			D::AddRemoveFriend();
		break;
		case 'resend2FACode':
			D::Resend2FACode();
		break;
		case 'disable2FA':
			D::Disable2FA();
		break;
		default:
			throw new Exception('Invalid action value');
		break;
			// Admin functions, need sessionCheckAdmin() because can be performed only by admins

		case 'saveSystemSettings':
			sessionCheckAdmin(Privileges::AdminManageSettings);
			D::SaveSystemSettings();
		break;
		case 'saveBanchoSettings':
			sessionCheckAdmin(Privileges::AdminManageSettings);
			D::SaveBanchoSettings();
		break;
		case 'runCron':
			sessionCheckAdmin(Privileges::AdminManageSettings);
			D::RunCron();
		break;
		case 'saveEditUser':
			sessionCheckAdmin(Privileges::AdminManageUsers);
			D::SaveEditUser();
		break;
		case 'banUnbanUser':
			sessionCheckAdmin(Privileges::AdminBanUsers);
			D::BanUnbanUser();
		break;
		case 'restrictUnrestrictUser':
			sessionCheckAdmin(Privileges::AdminBanUsers);
			D::RestrictUnrestrictUser();
		break;
		case 'quickEditUser':
			sessionCheckAdmin(Privileges::AdminManageUsers);
			D::QuickEditUser(false);
		break;
		case 'quickEditUserEmail':
			sessionCheckAdmin(Privileges::AdminManageUsers);
			D::QuickEditUser(true);
		break;
		case 'changeIdentity':
			sessionCheckAdmin(Privileges::AdminManageUsers);
			D::ChangeIdentity();
		break;
		case 'saveDocFile':
			sessionCheckAdmin(Privileges::AdminManageDocs);
			D::SaveDocFile();
		break;
		case 'removeDoc':
			sessionCheckAdmin(Privileges::AdminManageDocs);
			D::RemoveDocFile();
		break;
		case 'removeBadge':
			sessionCheckAdmin(Privileges::AdminManageBadges);
			D::RemoveBadge();
		break;
		case 'saveBadge':
			sessionCheckAdmin(Privileges::AdminManageBadges);
			D::SaveBadge();
		break;
		case 'quickEditUserBadges':
			sessionCheckAdmin(Privileges::AdminManageUsers);
			D::QuickEditUserBadges();
		break;
		case 'saveUserBadges':
			sessionCheckAdmin(Privileges::AdminManageUsers);
			D::SaveUserBadges();
		break;
		case 'silenceUser':
			sessionCheckAdmin(Privileges::AdminSilenceUsers);
			D::SilenceUser();
		break;
		case 'kickUser':
			sessionCheckAdmin(Privileges::AdminSilenceUsers);
			D::KickUser();
		break;
		case 'resetAvatar':
			sessionCheckAdmin(Privileges::AdminManageUsers);
			D::ResetAvatar();
		break;
		case 'wipeAccount':
			sessionCheckAdmin(Privileges::AdminWipeUsers);
			D::WipeAccount();
		break;
		case 'setRulesPage':
			sessionCheckAdmin(Privileges::AdminManageDocs);
			D::SetRulesPage();
		break;
		/*case 'processRankRequest':
			sessionCheckAdmin(Privileges::AdminManageBeatmaps);
			D::ProcessRankRequest();
		break;*/
		case 'blacklistRankRequest':
			sessionCheckAdmin(Privileges::AdminManageBeatmaps);
			D::BlacklistRankRequest();
		break;
		case 'savePrivilegeGroup':
			sessionCheckAdmin(Privileges::AdminManagePrivileges);
			D::savePrivilegeGroup();
		break;
		case 'giveDonor':
			sessionCheckAdmin(Privileges::AdminManageUsers);
			D::GiveDonor();
		break;
		case 'removeDonor':
			sessionCheckAdmin(Privileges::AdminManageUsers);
			D::RemoveDonor();
		break;
		case 'rollback':
			sessionCheckAdmin(Privileges::AdminWipeUsers);
			D::Rollback();
		break;
		case 'toggleCustomBadge':
			sessionCheckAdmin(Privileges::AdminManageUsers);
			D::ToggleCustomBadge();
		break;
		case 'lockUnlockUser':
			sessionCheckAdmin(Privileges::AdminBanUsers);
			D::LockUnlockUser();
		break;
		case 'rankBeatmapNew':
			sessionCheckAdmin(Privileges::AdminManageBeatmaps);
			D::RankBeatmapNew();
		break;
		case 'redirectRankBeatmap':
			sessionCheckAdmin(Privileges::AdminManageBeatmaps);
			D::RedirectRankBeatmap();
		break;
		case 'clearHWID':
			sessionCheckAdmin(Privileges::AdminBanUsers);
			D::ClearHWIDMatches();
		break;
		case 'takeReport':
			sessionCheckAdmin(Privileges::AdminManageReports);
			D::TakeReport();
		break;
		case 'solveUnsolveReport':
			sessionCheckAdmin(Privileges::AdminManageReports);
			D::SolveUnsolveReport();
		break;
		case 'uselessUsefulReport':
			sessionCheckAdmin(Privileges::AdminManageReports);
			D::UselessUsefulReport();
		break;
		case 'toggleCake':
			sessionCheckAdmin(Privileges::AdminCaker);
			Fringuellina::ToggleCake();
		break;
		case 'removeCake':
			sessionCheckAdmin(Privileges::AdminCaker);
			Fringuellina::RemoveCake();
		break;
		case 'saveCake':
			sessionCheckAdmin(Privileges::AdminCaker);
			Fringuellina::EditCake();
		break;
	}
}
catch(Exception $e) {
	// Redirect to Exception page
	redirect('index.php?p=99&e='.$e->getMessage());
}
