<?php
define('AJAX_REQUEST', isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest');
if (!AJAX_REQUEST) { 
	header('Location: ../index.php'); 
}

require_once('../config.php');
require_once('../veeam.class.php');

session_start();

$veeam = new VBO($host, $port);
$veeam->setToken($_SESSION['token']);

$sessions = $veeam->getSessions();

echo '<h1>Veeam Backup for Office 365 RESTful API demo</h1>';
echo '<div id="infobox" class="text-center"></div>';
	
if (count($sessions) != '0') {
	echo '<table class="table table-bordered table-striped" id="table-proxies">';
	echo '<tbody>'; 
	echo '<tr>';
	echo '<th colspan="4"><h3>Running sessions</h3></th>';
	echo '</tr>';
	echo '<tr>';
	echo '<td><strong>ID</strong></td>';
	echo '<td><strong>Type</strong></td>';
	echo '<td><strong>State</strong></td>';
	echo '<td><strong>Options</strong></td>';
	echo '</tr>';
	
	for ($i = 0; $i < count($sessions); $i++) {
		if (strtolower($sessions[$i]['state']) == 'working') {
			echo '<tr>';
			echo '<td>' . $sessions[$i]['id'] . '</td>';
			echo '<td>' . $sessions[$i]['type'] . '</td>';
			echo '<td>' . $sessions[$i]['state'] . '</td>';
			echo '<td><button class="btn btn-danger" id="btn-delete" data-call="endrestore" data-rcall="sessions" data-cid="' . $sessions[$i]['id'] . '" title="End session"><i class="fa fa-trash-o" aria-hidden="true"></i></button></a></td>';	
			echo '</tr>';
		}
	}
	
	echo '<tr>';
	echo '<td colspan="4"><h3>Terminated sessions</h3></td>';
	echo '</tr>';
	echo '<tr>';
	echo '<td><strong>ID</strong></td>';
	echo '<td><strong>Type</strong></td>';
	echo '<td><strong>State</strong></td>';
	echo '</tr>';
	
	for ($i = 0; $i < count($sessions); $i++) {
		if (strtolower($sessions[$i]['state']) != 'working') {
			echo '<tr>';
			echo '<td>' . $sessions[$i]['id'] . '</td>';
			echo '<td>' . $sessions[$i]['type'] . '</td>';
			echo '<td>' . $sessions[$i]['state'] . '</td>';
			echo '<td></td>';
			echo '</tr>';
		}
	}
	echo '</tbody>';
	echo '</table>';
} else {
	echo 'No sessions found.';
}
?>