<?php
/* Action handler page for jQuery Calls */
require_once('config.php');
require_once('veeam.class.php');

session_start();

if (isset($_GET['action'])) { $action = $_GET['action']; }
if (isset($_GET['json'])) { $json = $_GET['json']; }
if (isset($_GET['limit'])) { $limit = $_GET['limit']; }
if (isset($_GET['offset'])) { $offset = $_GET['offset']; }
if (isset($_GET['type'])) { $type = $_GET['type']; }

if (isset($_GET['id'])) { $id = $_GET['id']; }
if (isset($_GET['folderid'])) { $folderid = $_GET['folderid']; }
if (isset($_GET['itemid'])) { $itemid = $_GET['itemid']; }
if (isset($_GET['mailboxid'])) { $mailboxid = $_GET['mailboxid']; }
if (isset($_GET['rid'])) { $rid = $_GET['rid']; }
if (isset($_GET['siteid'])) { $siteid = $_GET['siteid']; }
if (isset($_GET['userid'])) { $userid = $_GET['userid']; }

$veeam = new VBO($host, $port, $version);
$veeam->setToken($_SESSION['token']);

/* Jobs Calls */
if ($action == 'createjob') {
    $veeam->createJob($id, $json);
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
    $veeam->startJob($id);
}

/* Organizations Calls */
if ($action == 'createorganization') {
    $veeam->createOrganization($json);
}
if ($action == 'getorganizations') {
    $org = $veeam->getOrganizations();
    echo json_encode($org);
}

/* Proxies Calls */
if ($action == 'createproxy') {
    $veeam->createProxy($json);
}

/* Repositories Calls */
if ($action == 'createrepository') {
    $veeam->createRepository($json);
}
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
    echo $session['id']; /* Return the Restore Session ID */
}
if ($action == 'stoprestore') {
    $session = $veeam->stopRestoreSession($id);
    unset($_SESSION['rid']);
    unset($_SESSION['rtype']);
}

/* Exchange Calls */
if ($action == 'getmailitems') {
    $items = $veeam->getMailboxItems($mailboxid, $rid, $folderid, $offset);
    echo json_encode($items);
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
    $users = $veeam->getSharePointContent($rid, $siteid, $type);
    echo json_encode($users);
}
if ($action == 'getsharepointitems') {
    $items = $veeam->getSharePointTree($rid, $siteid, $folderid, $type, $offset);
    echo json_encode($items);
}
if ($action == 'getsharepointitemsbyfolder') {
    $items = $veeam->getSharePointTree($rid, $siteid, $folderid, $type);
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
if ($action == 'exportmultiplesharepointitem') {
    $veeam->exportMultipleSharePointItem($siteid, $rid, $json);
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
?>