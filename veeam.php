<?php
require_once('config.php');
require_once('veeam.class.php');

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

session_start();

$veeam = new VBO($host, $port);
$veeam->setToken($_SESSION['token']);

/* Jobs calls */
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
if ($action == 'removejob') {
    $veeam->removeJob($id);
}
if ($action == 'startjob') {
    $veeam->startJob($id);
}


/* Organizations calls */
if ($action == 'createorganization') {
    $veeam->createOrganization($json);
}
if ($action == 'getorganizations') {
    $org = $veeam->getOrganizations();
    echo json_encode($org);
}
if ($action == 'removeorganization') {
    $veeam->removeOrganization($id);
}


/* Proxies calls */
if ($action == 'createproxy') {
    $veeam->createProxy($json);
}
if ($action == 'removeproxy') {
    $veeam->removeProxy($id);
}


/* Repositories calls */
if ($action == 'createrepository') {
    $veeam->createRepository($json);
}
if ($action == 'getrepo') {
    $repo = $veeam->getBackupRepository($id);
    echo json_encode($repo);
}
if ($action == 'removerepo') {
    $veeam->removeRepo($id);
}


/* Session calls */
if ($action == 'getsessionlog') {
    $log = $veeam->getSessionLog($id);
    echo json_encode($log);
}
if ($action == 'getsessions') {
    $log = $veeam->getSessions($offset);
    echo json_encode($log);
}


/* Explorer calls */
if ($action == 'startexplorer') {
    if (isset($id) && ($id != "tenant")) {
        $session = $veeam->startExplorer($json, $id);
    } else {
        $session = $veeam->startExplorer($json);
    }
    
    $_SESSION['rid'] = $session['id'];
    $_SESSION['rtype'] = strtolower($session['type']);
    echo $session['id']; /* Get the Restore Session ID */
}
if ($action == 'stopexplorer') {
    $session = $veeam->stopExplorer($id);
    unset($_SESSION['rid']);
    unset($_SESSION['rtype']);
}


/* Exchange calls */
if ($action == 'getmailitems') {
    $items = $veeam->getMailboxItems($mailboxid, $rid, $folderid, $offset);
    echo json_encode($items);
}

/* Exchange restore calls */
if ($action == 'exportmailitem') {
    $veeam->exportMailItem($itemid, $mailboxid, $rid, $json);
}
if ($action == 'restoremailoriginal' || $action == 'restoremailto') {
    $veeam->restoreMailItem($itemid, $mailboxid, $rid, $json);
}


/* OneDrive calls */
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

/* OneDrive restore calls */
if ($action == 'exportonedriveitem') {
    $veeam->exportOneDriveItem($itemid, $userid, $rid, $json, $type);
}
if ($action == 'restoreonedriveitem') {
    $veeam->restoreOneDriveItem($itemid, $userid, $rid, $json, $type);
}


/* SharePoint calls */
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

/* SharePoint restore calls */
if ($action == 'exportsharepointitem') {
    $veeam->exportSharePointItem($itemid, $siteid, $rid, $json, $type);
}
if ($action == 'restoresharepointitem') {
    $veeam->restoreSharePointItem($itemid, $siteid, $rid, $json, $type);
}
?>