<?php
require 'config.php';
require 'veeam.class.php';

//$action = 'getmailbox';
//$id = '6e24860e-3ed6-4ad3-a256-95635c5a8014';

if (isset($_GET['action'])) { $action = $_GET['action']; }
if (isset($_GET['id'])) { $id = $_GET['id']; }

$veeam = new VBO($host, $port, $user, $pass);

if ($action == 'getjobs') {
	$jobs = $veeam->getJobs();

	echo '<table class="table table-bordered table-striped" id="table-jobs">';
	echo '<thead>';
	echo '<tr>';
	echo '<th>Job name</th>';
	echo '<th>Status</th>';
	echo '<th class="text-center settings">Schedule</th>';
	echo '</tr>';
	echo '</thead>';
	echo '<tbody>'; 
	for ($i = 0; $i < count($jobs); $i++) {
		echo '<tr>';
		echo '<td>' . $jobs[$i]['name'] . '</td>';
		$epoch = strtotime($jobs[$i]['lastRun']);
		$dt = new DateTime("@$epoch");
		echo '<td>' . $jobs[$i]['lastStatus'] . ' (' . $dt->format('Y-m-d H:i:s T') . ')</td>';
		echo '<td data-toggle="collapse" data-target="#items'.$i.'" class="pointer"><a href="#">View schedule</a></td>';
		echo '</tr>';
		echo '<tr>';
		echo '<td colspan="3" class="zeroPadding"><div id="items'.$i.'" class="accordian-body collapse"><strong>Schedule settings:</strong><br />';
		echo '<u>Job type:</u> ' . $jobs[$i]['schedulePolicy']['type'] . '<br />';
		echo '<u>Run every:</u> ' . $jobs[$i]['schedulePolicy']['dailyType'] . '<br />';
		echo '<u>Run at:</u> ' . $jobs[$i]['schedulePolicy']['dailyTime'] . '<br />';
		echo '<u>Retry job:</u> ';
		if ($jobs[$i]['schedulePolicy']['retryEnabled'] == '1') { echo 'Yes'; } else { echo 'No'; }
		echo '<br />';
		echo '<u>Retry times:</u> ' . $jobs[$i]['schedulePolicy']['retryNumber'] . '<br />';
		echo '</div>';
		echo '</div></td>';
		echo '</tr>';
	}
	echo '</tbody>';
	echo '</table>';
}

if ($action == 'getmailbox') {
	$mailbox = $veeam->getMailbox($id);
	
	echo '<div id="div-item-restore"><button class="btn btn-default" id="btn-start-item-restore" data-id="' . $id . '" title="Start item restore">Start item restore</button></div>';
	echo '<table class="table table-bordered table-striped" id="table-mailboxes">';
	echo '<thead>';
	echo '<tr>';
	echo '<th class="jobname">Name</th>';
	echo '<th class="jobtype">E-mail address</th>';
	echo '<th class="text-center settings">Options</th>';
	echo '</tr>';
	echo '</thead>';
	echo '<tbody>'; 
	for ($i = 0; $i < count($mailbox['results']); $i++) {
		echo '<tr>';
		echo '<td>' . $mailbox['results'][$i]['name'] . '</td>';
		echo '<td>' . $mailbox['results'][$i]['email'] . '</td>';
		echo '<td data-toggle="collapse" data-target="#items'.$i.'" class="pointer"><a href="#">View items</a></td>';
		echo '</tr>';
		echo '<tr>';
		echo '<td colspan="3" class="zeroPadding"><div id="items'.$i.'" class="accordian-body collapse"><strong>Available items:</strong>';
		echo '</div>';
		echo '</div></td>';
		echo '</tr>';
	}
	echo '</tbody>';
	echo '</table>';
}

if ($action == 'getproxies') {
	$proxies = $veeam->getProxies();
	
	echo '<table class="table table-bordered table-striped" id="table-proxies">';
	echo '<thead>';
	echo '<tr>';
	echo '<th>Name</th>';
	echo '<th>Port</th>';
	echo '<th>Description</th>';
	echo '<th class="text-center settings">Options</th>';
	echo '</tr>';
	echo '</thead>';
	echo '<tbody>'; 
	for ($i = 0; $i < count($proxies); $i++) {
		echo '<tr>';
		echo '<td>' . $proxies[$i]['hostName'] . '</td>';
		echo '<td>' . $proxies[$i]['port'] . '</td>';
		echo '<td>' . $proxies[$i]['description'] . '</td>';
		echo '<td>';
		echo '<button class="btn btn-default" id="btn-change-proxy" data-hostname="' . $proxies[$i]['hostName'] . '" title="Modify"><i class="fa fa-cog" aria-hidden="true"></i></button>&nbsp;';
		echo '<button class="btn btn-danger" id="btn-delete-proxy" data-hostname="' . $proxies[$i]['hostName'] . '" title="Delete"><i class="fa fa-trash-o" aria-hidden="true"></i></button></a>';
		echo '</td>';	
		echo '</tr>';
	}
	echo '</tbody>';
	echo '</table>';
}

if ($action == 'getrepos') {
	$repos = $veeam->getBackupRepositories();
	
	echo '<table class="table table-bordered table-striped" id="table-proxies">';
	echo '<thead>';
	echo '<tr>';
	echo '<th>Name</th>';
	echo '<th>Host</th>';
	echo '<th>Capacity</th>';
	echo '<th>Description</th>';
	echo '<th class="text-center settings">Options</th>';
	echo '</tr>';
	echo '</thead>';
	echo '<tbody>'; 
	for ($i = 0; $i < count($repos); $i++) {
		echo '<tr>';
		echo '<td>' . $repos[$i]['name'] . '</td>';
		echo '<td>' . $repos[$i]['hostName'] . '</td>';
		echo '<td>' . round($repos[$i]['capacity']/1024/1024/1024, 1) . ' GB (' . round($repos[$i]['freeSpace']/1024/1024/1024, 1)  . ' GB free)</td>';
		echo '<td>' . $repos[$i]['description'] . '</td>';
		echo '<td>';
		echo '<button class="btn btn-default" id="btn-change-repo" data-hostname="' . $repos[$i]['name'] . '" title="Modify"><i class="fa fa-cog" aria-hidden="true"></i></button>&nbsp;';
		echo '<button class="btn btn-danger" id="btn-delete-repo" data-hostname="' . $repos[$i]['hostName'] . '" title="Delete"><i class="fa fa-trash-o" aria-hidden="true"></i></button></a>';
		echo '</td>';	
		echo '</tr>';
	}
	echo '</tbody>';
	echo '</table>';
}

if ($action == 'startrestore') {
	$items = $veeam->startRestoreSession($id);
	echo $items['id'];
}

if ($action == 'endrestore') {
	$items = $veeam->endRestoreSession($id);
}