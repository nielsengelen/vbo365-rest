<?php
session_start();
set_time_limit(0);

require_once('config.php');
require_once('veeam.class.php');

if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
	if (isset($_POST['action'])) { $action = $_POST['action']; }
	if (isset($_POST['json'])) { $json = $_POST['json']; }
	if (isset($_POST['limit'])) { $limit = $_POST['limit']; }
	if (isset($_POST['offset'])) { $offset = $_POST['offset']; }
	if (isset($_POST['type'])) { $type = $_POST['type']; }

	if (isset($_POST['rid'])) { $rid = $_POST['rid']; }
	
	if (isset($_POST['id'])) { $id = $_POST['id']; }
	if (isset($_POST['folderid'])) { $folderid = $_POST['folderid']; }
	if (isset($_POST['itemid'])) { $itemid = $_POST['itemid']; }
	if (isset($_POST['mailboxid'])) { $mailboxid = $_POST['mailboxid']; }
	if (isset($_POST['siteid'])) { $siteid = $_POST['siteid']; }
	if (isset($_POST['userid'])) { $userid = $_POST['userid']; }
	
	if (isset($_POST['teamid'])) { $teamid = $_POST['teamid']; }
	if (isset($_POST['channelid'])) { $channelid = $_POST['channelid']; }
	if (isset($_POST['parentid'])) { $parentid = $_POST['parentid']; }
	if (isset($_POST['tabid'])) { $tabid = $_POST['tabid']; }

	$veeam = new VBO($host, $port, $version);
	
	if (isset($_SESSION['token'])) {
		$veeam->setToken($_SESSION['token']);
	}
	
	if ($action == 'changejobstate') {
		$veeam->changeJobState($id, $json);
	}
	if ($action == 'getjobs') {
		$jobs = $veeam->getJobs($id);
		echo json_encode($jobs);
	}
	if ($action == 'getjobsession') {
		$getjobsession = $veeam->getJobSession($id);
		echo json_encode($getjobsession);
	}
	if ($action == 'startjob') {
		$job = $veeam->startJob($id);
		echo json_encode($job);
	}

	if ($action == 'getorganizations') {
		$org = $veeam->getOrganizations();
		echo json_encode($org);
	}

	if ($action == 'getrepo') {
		$repo = $veeam->getBackupRepository($id);
		echo json_encode($repo);
	}

	if ($action == 'getsessionlog') {
		$log = $veeam->getSessionLog($id);
		echo json_encode($log);
	}
	if ($action == 'getsessions') {
		$log = $veeam->getSessions($offset);
		echo json_encode($log);
	}
	if ($action == 'getbackupsessionlog') {
		$log = $veeam->getBackupSessionLog($id);
		echo json_encode($log);
	}
	if ($action == 'getbackupsessions') {
		$log = $veeam->getBackupSessions();
		echo json_encode($log);
	}
	if ($action == 'getrestoresessionevents') {
		$log = $veeam->getRestoreSessionEvents($id);
		echo json_encode($log);
	}
	if ($action == 'getrestoresessions') {
		$log = $veeam->getRestoreSessions();
		echo json_encode($log);
	}

	if ($action == 'startrestore') {
		if (isset($id) && ($id !== 'tenant')) {
			$session = $veeam->startRestoreSession($json, $id);
		} else {
			$session = $veeam->startRestoreSession($json);
		}
		
		$_SESSION['rid'] = $session['id'];
		$_SESSION['rtype'] = strtolower($session['type']);
		
		echo $session['id'];
	}
	if ($action == 'stoprestore') {
		$session = $veeam->stopRestoreSession($rid);
		
		if (isset($session['error'])) {
			echo json_encode($session['error']);
			
			unset($_SESSION['rid']);
			unset($_SESSION['rtype']);
		} else {
			$session = array('message' => 'success');
			echo json_encode($session);
		
			unset($_SESSION['rid']);
			unset($_SESSION['rtype']);
		}
	}
	if ($action == 'getrestoredevicecode') {
		$code = $veeam->getRestoreDeviceCode($rid, $json);
		echo json_encode($code);
	}

	if ($action == 'getmailboxes') {
		if (isset($offset)) {
			$mailboxes = $veeam->getMailboxes($rid, $offset);
		} else {
			$mailboxes = $veeam->getMailboxes($rid);
		}
		
		echo json_encode($mailboxes);
	}
	if ($action == 'getmailboxfolders') {
		if (isset($folderid)) {
			if (isset($offset)) {
				$folders = $veeam->getMailboxFolders($rid, $mailboxid, $folderid, $offset);
			} else {
				$folders = $veeam->getMailboxFolders($rid, $mailboxid, $folderid);
			}
		} else {
			if (isset($offset)) {
				$folders = $veeam->getMailboxFolders($rid, $mailboxid, $fid = 'null', $offset);
			} else {
				$folders = $veeam->getMailboxFolders($rid, $mailboxid, $fid = 'null');
			}
		}
		
		echo json_encode($folders);
	}
	if ($action == 'getmailboxitems') {
		if (isset($offset)) {
			$items = $veeam->getMailboxItems($rid, $mailboxid, $folderid, $offset);
		} else {
			$items = $veeam->getMailboxItems($rid, $mailboxid, $folderid);
		}
		
		echo json_encode($items);
	}
	
	if ($action == 'exportmailbox') {
		$export = $veeam->exportMailbox($rid, $mailboxid, $json);
		echo json_encode($export);
	}
	if ($action == 'exportmailfolder') {
		$export = $veeam->exportMailFolder($rid, $mailboxid, $itemid, $json);
		echo json_encode($export);
	}
	if ($action == 'exportmailitem') {
		$export = $veeam->exportMailItem($rid, $mailboxid, $itemid, $json);
		echo json_encode($export);
	}
	if ($action == 'exportmultiplemailitems') {
		$export = $veeam->exportMultipleMailItems($rid, $mailboxid, $itemid, $json);
		echo json_encode($export);
	}

	if ($action == 'restoremailbox') {
		$restore = $veeam->restoreMailbox($rid, $mailboxid, $json);
		echo json_encode($restore);
	}
	if ($action == 'restoremailfolder') {
		$restore = $veeam->restoreMailFolder($rid, $mailboxid, $itemid, $json);
		echo json_encode($restore);
	}
	if ($action == 'restoremailitem') {
		$restore = $veeam->restoreMailItem($rid, $mailboxid, $itemid, $json);
		echo json_encode($restore);
	}
	if ($action == 'restoremultiplemailitems') {
		$restore = $veeam->restoreMultipleMailItems($rid, $mailboxid, $json);
		echo json_encode($restore);
	}

	if ($action == 'getonedriveaccounts') {
		if (isset($offset)) {
			$accounts = $veeam->getOneDrives($rid, $offset);
		} else {
			$accounts = $veeam->getOneDrives($rid);
		}
		echo json_encode($accounts);
	}
	if ($action == 'getonedrivefolders') {
		if (isset($offset)) {
			$folders = $veeam->getOneDriveFolders($rid, $userid, $folderid, $offset);
		} else {
			$folders = $veeam->getOneDriveFolders($rid, $userid, $folderid);
		}
		
		echo json_encode($folders);
	}
	if ($action == 'getonedriveitems') {
		if (isset($offset)) {
			$items = $veeam->getOneDriveTree($rid, $userid, $type, $folderid, $offset);
		} else {
			$items = $veeam->getOneDriveTree($rid, $userid, $type, $folderid);
		}
		
		echo json_encode($items);
	}
	if ($action == 'getonedriveitemsbyfolder') {
		$items = $veeam->getOneDriveTree($rid, $userid, $type, $folderid);
		
		echo json_encode($items);
	}

	if ($action == 'exportonedrive') {
		$export = $veeam->exportOneDrive($rid, $userid, $json);
		echo json_encode($export);
	}
	if ($action == 'exportonedriveitem') {
		$export = $veeam->exportOneDriveItem($rid, $userid, $itemid, $json, $type);
		echo json_encode($export);
	}
	if ($action == 'exportmultipleonedriveitems') {
		$export = $veeam->exportMultipleOneDriveItems($rid, $userid, $itemid, $json, $type);
		echo json_encode($export);
	}

	if ($action == 'restoreonedrive') {
		$restore = $veeam->restoreOneDrive($rid, $userid, $json);
		echo json_encode($restore);
	}
	if ($action == 'restoreonedriveitem') {
		$restore = $veeam->restoreOneDriveItem($rid, $userid, $itemid, $json, $type);
		echo json_encode($restore);
	}
	if ($action == 'restoremultipleonedriveitems') {
		$restore = $veeam->restoreMultipleOneDriveItems($rid, $userid, $json);
		echo json_encode($restore);
	}

	if ($action == 'getsharepointitems') {
		if (isset($offset)) {
			$items = $veeam->getSharePointItems($rid, $siteid, $folderid, $type, $offset);
		} else {
			$items = $veeam->getSharePointItems($rid, $siteid, $folderid, $type);
		}

		echo json_encode($items);
	}
	if ($action == 'getsharepointfolders') {
		if (isset($offset)) {
			$folders = $veeam->getSharePointFolders($rid, $siteid, $folderid, $offset);
		} else {
			$folders = $veeam->getSharePointFolders($rid, $siteid, $folderid);
		}
		
		echo json_encode($folders);
	}
	if ($action == 'getsharepointcontent') {
		if (isset($offset)) {
			$content = $veeam->getSharePointContent($rid, $siteid, $type, $offset);
		} else {
			$content = $veeam->getSharePointContent($rid, $siteid, $type);
		}
		
		echo json_encode($content);
	}
	if ($action == 'getsharepointsites') {
		if (isset($offset)) {
			$sites = $veeam->getSharePointSites($rid, $offset);
		} else {
			$sites = $veeam->getSharePointSites($rid, $offset);
		}
		
		echo json_encode($sites);
	}

	if ($action == 'exportsharepoint') {
		$export = $veeam->exportSharePoint($rid, $siteid, $json);
		echo json_encode($export);
	}
	if ($action == 'exportsharepointitem') {
		$export = $veeam->exportSharePointItem($rid, $siteid, $itemid, $json, $type);
		echo json_encode($export);
	}
	if ($action == 'exportmultiplesharepointitems') {
		$export = $veeam->exportMultipleSharePointItems($rid, $siteid, $itemid, $json, $type);
		echo json_encode($export);
	}

	if ($action == 'restoresharepoint') {
		$restore = $veeam->restoreSharePoint($rid, $siteid, $json);
		echo json_encode($restore);
	}
	if ($action == 'restoresharepointitem') {
		$restore = $veeam->restoreSharePointItem($rid, $siteid, $itemid, $json, $type);
		echo json_encode($restore);
	}
	if ($action == 'restoremultiplesharepointitems') {
		$restore = $veeam->restoreMultipleSharePointItems($rid, $siteid, $json);
		echo json_encode($restore);
	}

	if ($action == 'getteamsfiles') {
		if (isset($offset)) {
			$files = $veeam->getTeamsFiles($rid, $teamid, $channelid, $parentid, $offset);
		} else {
			$files = $veeam->getTeamsFiles($rid, $teamid, $channelid, $parentid);
		}
	
		echo json_encode($files);
	}
	if ($action == 'getteamsposts') {
		if (isset($offset)) {
			$posts = $veeam->getTeamsPosts($rid, $teamid, $channelid, $parentid, $offset);
		} else {
			$posts = $veeam->getTeamsPosts($rid, $teamid, $channelid, $parentid);
		}
	
		echo json_encode($posts);
	}
	if ($action == 'getteamstabs') {
		if (isset($offset)) {
			$tabs = $veeam->getTeamsTabs($rid, $teamid, $channelid, $offset);
		} else {
			$tabs = $veeam->getTeamsTabs($rid, $teamid, $channelid);
		}
	
		echo json_encode($tabs);
	}

	if ($action == 'restoreteam') {
		$restore = $veeam->restoreTeam($rid, $teamid, $json);
		echo json_encode($restore);
	}
	if ($action == 'restoreteamschannel') {
		$restore = $veeam->restoreTeamsChannel($rid, $teamid, $channelid, $json);
		echo json_encode($restore);
	}
	
	if ($action == 'exportteamsfile') {
		$export = $veeam->exportTeamsFile($rid, $teamid, $itemid, $json);
		echo json_encode($export);
	}
	if ($action == 'exportteamsmultiplefiles') {
		$export = $veeam->exportTeamsMultipleFiles($rid, $teamid, $itemid, $json);
		echo json_encode($export);
	}
	if ($action == 'exportteamschannelfiles') {
		$export = $veeam->exportTeamsChannelFiles($rid, $teamid, $json);
		echo json_encode($export);
	}

	if ($action == 'restoreteamsfile') {
		$restore = $veeam->restoreTeamsFile($rid, $teamid, $itemid, $json);
		echo json_encode($restore);
	}
	if ($action == 'restoreteamsmultiplefiles') {
		$restore = $veeam->restoreTeamsMultipleFiles($rid, $teamid, $json);
		echo json_encode($restore);
	}

	if ($action == 'exportteamspost') {
		$export = $veeam->exportTeamsPost($rid, $teamid, $itemid, $json);
		echo json_encode($export);
	}
	if ($action == 'exportteamsmultipleposts') {
		$export = $veeam->exportTeamsMultiplePosts($rid, $teamid, $itemid, $json);
		echo json_encode($export);
	}
	if ($action == 'restoreteamsmultipleposts') {
		$restore = $veeam->restoreTeamsMultiplePosts($rid, $teamid, $json);
		echo json_encode($restore);
	}

	if ($action == 'restoreteamstab') {
		$restore = $veeam->restoreTeamsTab($rid, $teamid, $channelid, $tabid, $json);
		echo json_encode($restore);
	}
	if ($action == 'restoreteamsmultipletabs') {
		$restore = $veeam->restoreTeamsMultipleTabs($rid, $teamid, $channelid, $json);
		echo json_encode($restore);
	}
} else {
	$veeam = new VBO($host, $port, $version);
	$veeam->logout();
}
?>