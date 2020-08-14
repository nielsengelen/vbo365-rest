<?php
session_start();

require_once('config.php');
require_once('veeam.class.php');

if(!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
	if (isset($_POST['action'])) { $action = $_POST['action']; }
	if (isset($_POST['json'])) { $json = $_POST['json']; }
	if (isset($_POST['limit'])) { $limit = $_POST['limit']; }
	if (isset($_POST['offset'])) { $offset = $_POST['offset']; }
	if (isset($_POST['type'])) { $type = $_POST['type']; }

	if (isset($_POST['id'])) { $id = $_POST['id']; }
	if (isset($_POST['folderid'])) { $folderid = $_POST['folderid']; }
	if (isset($_POST['itemid'])) { $itemid = $_POST['itemid']; }
	if (isset($_POST['mailboxid'])) { $mailboxid = $_POST['mailboxid']; }
	if (isset($_POST['rid'])) { $rid = $_POST['rid']; }
	if (isset($_POST['siteid'])) { $siteid = $_POST['siteid']; }
	if (isset($_POST['userid'])) { $userid = $_POST['userid']; }

	$veeam = new VBO($host, $port, $version);
	$veeam->setToken($_SESSION['token']);

	/* Jobs Calls */
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
		$veeam->startJob($id);
	}

	/* Organizations Calls */
	if ($action == 'getorganizations') {
		$org = $veeam->getOrganizations();
		echo json_encode($org);
	}

	/* Repositories Calls */
	if ($action == 'getrepo') {
		$repo = $veeam->getBackupRepository($id);
		echo json_encode($repo);
	}

	/* Sessions Calls */
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

	/* Restore Session Calls */
	if ($action == 'startrestore') {
		if (isset($id) && ($id != "tenant")) {
			$session = $veeam->startRestoreSession($json, $id);
		} else {
			$session = $veeam->startRestoreSession($json);
		}
		
		$_SESSION['rid'] = $session['id'];
		$_SESSION['rtype'] = strtolower($session['type']);
		
		echo $session['id'];
	}
	if ($action == 'stoprestore') {
		$session = $veeam->stopRestoreSession($id);
		
		unset($_SESSION['rid']);
		unset($_SESSION['rtype']);
	}

	/* Exchange Calls */
	if ($action == 'getmailitems') {
		$items = $veeam->getMailboxItems($mailboxid, $rid, $folderid, $offset = null);
		
		echo json_encode($items);
	}
	if ($action == 'getmailfolders') {
		$folders = $veeam->getMailboxFolders($mailboxid, $rid, $folderid);
		
		echo json_encode($folders);
	}

	/* Exchange Restore Calls */
	if ($action == 'exportmailbox') {
		$veeam->exportMailbox($mailboxid, $rid, $json);
	}
	if ($action == 'exportmailitem') {
		$veeam->exportMailItem($itemid, $mailboxid, $rid, $json);
	}
	if ($action == 'exportmultiplemailitems') {
		$veeam->exportMultipleMailItems($itemid, $mailboxid, $rid, $json);
	}
	if ($action == 'restoremailbox') {
		$veeam->restoreMailbox($mailboxid, $rid, $json);
	}
	if ($action == 'restoremailitem') {
		$veeam->restoreMailItem($itemid, $mailboxid, $rid, $json);
	}
	if ($action == 'restoremultiplemailitems') {
		$veeam->restoreMultipleMailItems($mailboxid, $rid, $json);
	}

	/* OneDrive Calls */
	if ($action == 'getonedriveitems') {
		$items = $veeam->getOneDriveTree($rid, $userid, $type, $folderid, $offset);
		
		echo json_encode($items);
	}
	if ($action == 'getonedriveitemsbyfolder') {
		$items = $veeam->getOneDriveTree($rid, $userid, $type, $folderid);
		
		echo json_encode($items);
	}
	if ($action == 'getonedriveparentfolder') {
		$items = $veeam->getOneDriveParentFolder($rid, $userid, $type, $folderid);
		
		echo json_encode($items);
	}

	/* OneDrive Restore Calls */
	if ($action == 'exportonedrive') {
		$veeam->exportOneDrive($userid, $rid, $json);
	}
	if ($action == 'exportonedriveitem') {
		$veeam->exportOneDriveItem($itemid, $userid, $rid, $json, $type);
	}
	if ($action == 'exportmultipleonedriveitems') {
		$veeam->exportMultipleOneDriveItems($itemid, $userid, $rid, $json, $type);
	}
	if ($action == 'restoreonedrive') {
		$veeam->restoreOneDrive($userid, $rid, $json);
	}
	if ($action == 'restoreonedriveitem') {
		$veeam->restoreOneDriveItem($itemid, $userid, $rid, $json, $type);
	}
	if ($action == 'restoremultipleonedriveitems') {
		$veeam->restoreMultipleOneDriveItems($userid, $rid, $json);
	}

	/* SharePoint Calls */
	if ($action == 'getsharepointcontent') {
		if (isset($folderid)) {
			$content = $veeam->getSharePointContent($rid, $siteid, $type, $folderid);
		} else {
			$content = $veeam->getSharePointContent($rid, $siteid, $type);
		}
		
		echo json_encode($content);
	}
	if ($action == 'getsharepointitems') {
		$items = $veeam->getSharePointTree($rid, $siteid, $folderid, $type, $offset = null);

		echo json_encode($items);
	}
	if ($action == 'getsharepointparentfolder') {
		$items = $veeam->getSharePointParentFolder($rid, $siteid, $type, $folderid);
		
		echo json_encode($items);
	}

	/* SharePoint Restore Calls */
	if ($action == 'exportsharepoint') {
		$veeam->exportSharePoint($siteid, $rid, $json);
	}
	if ($action == 'exportsharepointitem') {
		$veeam->exportSharePointItem($itemid, $siteid, $rid, $json, $type);
	}
	if ($action == 'exportmultiplesharepointitems') {
		$veeam->exportMultipleSharePointItems($itemid, $siteid, $rid, $json, $type);
	}
	if ($action == 'restoresharepoint') {
		$veeam->restoreSharePoint($siteid, $rid, $json);
	}
	if ($action == 'restoresharepointitem') {
		$veeam->restoreSharePointItem($itemid, $siteid, $rid, $json, $type);
	}
	if ($action == 'restoremultiplesharepointitems') {
		$veeam->restoreMultipleSharePointItems($siteid, $rid, $json);
	}
} else {
	$veeam = new VBO($host, $port, $version);
	$veeam->logout();
}
?>