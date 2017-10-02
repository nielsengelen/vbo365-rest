<?php
/**
 * Action handler
 */

require_once('config.php');
require_once('veeam.class.php');

if (isset($_GET['action'])) { $action = $_GET['action']; }
if (isset($_GET['id'])) { $id = $_GET['id']; }
if (isset($_GET['json'])) { $json = $_GET['json']; }

session_start();

$veeam = new VBO($host, $port);
$veeam->setToken($_SESSION['token']);

/* Other calls */
if ($action == 'createjob') {
	$veeam->createJob($id, $json);
}

if ($action == 'createorganization') {
	$veeam->createOrganization($json);
}

if ($action == 'createproxy') {
	$veeam->createProxy($json);
}

if ($action == 'createrepository') {
	$veeam->createRepository($json);
}

if ($action == 'changejobstate') {
	$veeam->changeJobState($id, $json);
}

if ($action == 'getrepo') {
	$repo = $veeam->getBackupRepository($id);
	
	echo json_encode($repo);
}

if ($action == 'listfolders') {
	$ids = preg_split('/\|/', $id);
	$mid = $ids[0]; 
	$rid = $ids[1];
	
	$folders = $veeam->getFolders($rid, $mid);
	echo $folders;
	/*if (count($folders) != '0') {
		for ($i = 0; $i < count($folders); $i++) {
			echo $folders[$i]['name'] . '#';	
		}
	}*/
} 

if ($action == 'listmailboxes') {
	$mailbox = $veeam->getOrganizationMailboxes($id);
	
	if (count($mailbox['results']) != '0') {
		for ($i = 0; $i < count($mailbox['results']); $i++) {
			echo $mailbox['results'][$i]['id'] . '|' . $mailbox['results'][$i]['email'] . '|' . $mailbox['results'][$i]['name'] . '#';	
		}
	}
}

if ($action == 'listrepositories') {
	$repos = $veeam->getBackupRepository($id);
	
	if (count($repos) != '0') {
		for ($i = 0; $i < count($repos); $i++) {
			echo $repos[$i]['id'] . '|' . $repos[$i]['name'] . '#';	
		}
	}
} 

if ($action == 'removejob') {
	$veeam->removeJob($id);
}

if ($action == 'removeorg') {
	$veeam->removeOrganization($id);
}

if ($action == 'removeproxy') {
	$veeam->removeProxy($id);
}

if ($action == 'removerepo') {
	$veeam->removeRepo($id);
}

if ($action == 'startjob') {
	$veeam->startJob($id);
}


/* Restore calls */
if ($action == 'exportitem') {
	$ids = preg_split('/\|/', $id);
	$mid = $ids[0]; 
	$rid = $ids[1];
	$iid = $ids[2];
	
	$veeam->exportItem($mid, $rid, $iid, $json);
}

if ($action == 'getitems') {
	$ids = preg_split('/\|/', $id);
	$mid = $ids[0]; 
	$rid = $ids[1];
	
	$items = $veeam->getItems($rid, $mid);
	
	echo json_encode($items);
}

if ($action == 'restoreoriginal' || $action == 'restoreto') {
	$ids = preg_split('/\|/', $id);
	$mid = $ids[0]; 
	$rid = $ids[1];
	$iid = $ids[2];
	
	$veeam->restoreItem($mid, $rid, $iid, $json);
}

if ($action == 'startrestore') {
	$items = $veeam->vexSessionHandler($id, $json);
	echo $items['id'];
}

if ($action == 'endrestore') {
	$veeam->vexSessionHandler($id, $json);
}