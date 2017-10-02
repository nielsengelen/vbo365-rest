<?php
/**
 * Mailboxes page HTML design
 * Used for organization login only
 */

define('AJAX_REQUEST', isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest');
if (!AJAX_REQUEST) { 
	header('Location: ../index.php'); 
}

require_once('../config.php');
require_once('../veeam.class.php');

session_start();

$veeam = new VBO($host, $port);
$veeam->setToken($_SESSION['token']);

/* Do we have a session running? If not start one, else use it. */
if (isset($_SESSION['rid'])) {
	$id = $_SESSION['rid'];
} else {
	$session = $veeam->startSession();
	$id = $session['id'];
	$_SESSION['rid'] = $id;
}

$mailbox = $veeam->getMailbox($id);

for ($i = 0; $i < count($mailbox['results']); $i++) {
?>
<script>
/* Iteam search */
$('#search-<?php echo $mailbox['results'][$i]['id']; ?>').keyup(function(e) {
	var searchText = $(this).val().toLowerCase();
	// Show only matching row, hide rest of them
	$.each($('#table-mailitems-<?php echo $mailbox['results'][$i]['id']; ?> tbody tr'), function(e) {
		if($(this).text().toLowerCase().indexOf(searchText) === -1)
		   $(this).hide();
		else
		   $(this).show();
	});
});
</script>
<?php
}

if (count($mailbox) != '0') {
	echo '<table class="table table-bordered table-striped" id="table-mailboxes">';
	echo '<thead>';
	echo '<tr>';
	echo '<th>Name</th>';
	echo '<th>E-mail address</th>';
	echo '<th class="text-center settings">Items</th>';
	echo '</tr>';
	echo '</thead>';
	echo '<tbody>'; 
	
	for ($i = 0; $i < count($mailbox['results']); $i++) {
		echo '<tr>';
		echo '<td>' . $mailbox['results'][$i]['name'] . '</td>';
		echo '<td>' . $mailbox['results'][$i]['email'] . '</td>';
		echo '<td data-toggle="collapse" data-target="#items-'.$mailbox['results'][$i]['id'].'"><a href="#" name="link-restore" data-mid="' . $mailbox['results'][$i]['id'] . '" data-rid="' . $id . '">View items</a></td>';
		echo '</tr>';
		echo '<tr>';
		echo '<td colspan="4" class="zeroPadding"><div id="items-'.$mailbox['results'][$i]['id'].'" class="accordian-body collapse"><u><h3>Items available for restore:</h3></u>';
		echo '<input class="search" id="search-' . $mailbox['results'][$i]['id'] . '" placeholder="Search item" /><br /><br />';
		echo '<table class="table table-bordered table-striped table-padding" name="table-mailitems" id="table-mailitems-' . $mailbox['results'][$i]['id'] . '">';
		echo '<thead>';
		echo '<tr>';
		echo '<th class="mail-from">From</th>';
		echo '<th class="mail-subject">Subject</th>';
		echo '<th class="mail-received">Received</th>';
		echo '<th class="text-center settings mail-options">Options</th>';
		echo '</tr>';
		echo '<tbody>';
		echo '</tbody>';
		echo '</table>';
		echo '</div></td>';
		echo '</tr>';		
	}
	
	echo '</tbody>';
	echo '</table>';		
} else {
	echo 'No mailboxes found.';
	
	unset($_SESSION['rid']); /* Prevent dead sessions */
}
?>