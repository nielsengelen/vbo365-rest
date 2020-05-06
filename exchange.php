<?php
error_reporting(E_ALL || E_STRICT);
set_time_limit(0);

require_once('config.php');
require_once('veeam.class.php');

session_start();

if (empty($host) || empty($port) || empty($version)) {
    exit('Please modify the configuration file first and configure the Veeam Backup for Microsoft Office 365 host, port and RESTful API version settings.');
}

if (!preg_match('/v[3-4]/', $version)) {
	exit('Invalid API version found. Please modify the configuration file and configure the Veeam Backup for Microsoft Office 365 RESTful API version setting. Only version 3 and 4 are supported.');
}

$veeam = new VBO($host, $port, $version);

if (isset($_SESSION['token'])) {
    $veeam->setToken($_SESSION['token']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo $title; ?></title>
    <base href="/" />
    <link rel="shortcut icon" href="images/favicon.ico" />
    <link rel="stylesheet" type="text/css" href="vendor/twbs/bootstrap/dist/css/bootstrap.min.css" />
    <link rel="stylesheet" type="text/css" href="css/flatpickr.min.css">
    <link rel="stylesheet" type="text/css" href="css/fontawesome.min.css" />
    <link rel="stylesheet" type="text/css" href="css/style.css" />
	<link rel="stylesheet" type="text/css" href="css/sweetalert2.min.css" />
    <script src="vendor/components/jquery/jquery.min.js"></script>
    <script src="vendor/twbs/bootstrap/dist/js/bootstrap.min.js"></script>
    <script src="js/fontawesome.min.js"></script>
    <script src="js/filesize.min.js"></script>
    <script src="js/flatpickr.js"></script>
	<script src="js/jquery.redirect.js"></script>
	<script src="js/moment.min.js"></script>
	<script src="js/sweetalert2.all.min.js"></script>
</head>
<body>
<?php
if (isset($_SESSION['token'])) {
    $user = $_SESSION['user'];
?>
<nav class="navbar navbar-inverse navbar-static-top">
	<ul class="nav navbar-header">
	  <li><a class="navbar-brand navbar-logo" href="/"><img src="images/logo.svg" alt="Veeam Backup for Microsoft Office 365" class="logo" /></a></li>
	</ul>
	<ul class="nav navbar-nav" id="nav">
	  <li class="active"><a href="exchange">Exchange</a></li>
	  <li><a href="onedrive">OneDrive</a></li>
	  <li><a href="sharepoint">SharePoint</a></li>
	</ul>
	<ul class="nav navbar-nav navbar-right">
	  <li><a href="#"><span class="fa fa-user"></span> Welcome <i><?php echo $user; ?></i> !</a></li>
	  <li id="logout"><a href="#"><span class="fa fa-sign-out-alt"></span> Logout</a></li>
	</ul>
</nav>
<div class="container-fluid">
	<link rel="stylesheet" href="css/exchange.css" />
	<aside id="sidebar">
		<div class="logo-container"><i class="logo fa fa-envelope"></i></div>
		<div class="separator"></div>
		<menu class="menu-segment" id="menu">
		<?php
		if (!isset($_SESSION['rid'])) { /* No restore session is running */
			$check = filter_var($user, FILTER_VALIDATE_EMAIL);

			echo '<ul id="ul-exchange-users">';
			
			if ($check === false && strtolower($administrator) == 'yes') {
				$org = $veeam->getOrganizations();
				$oid = $_GET['oid'];
				$menu = false;
				
				for ($i = 0; $i < count($org); $i++) {
					if (isset($oid) && !empty($oid) && $oid == $org[$i]['id']) {
						echo '<li class="active"><a href="exchange/' . $org[$i]['id'] . '">' . $org[$i]['name'] . '</a></li>';
					} else {
						echo '<li><a href="exchange/' . $org[$i]['id'] . '">' . $org[$i]['name'] . '</a></li>';
					}
				}
			} else {
				$org = $veeam->getOrganization();
				$oid = $org['id'];
				$menu = true;
				
				echo '<li class="active"><a href="exchange">' . $org['name'] . '</a></li>';
			}
		
			echo '</ul>';
		} else { /* Restore session is running */
			$rid = $_SESSION['rid'];

			if (strcmp($_SESSION['rtype'], 'vex') === 0) {
				$uid = $_GET['uid'];
				$content = array();
				$org = $veeam->getOrganizationID($rid);
				$users = $veeam->getMailbox($rid);

				if ($users == '500') { /* Restore session has expired or was killed */
					unset($_SESSION['rid']);
					?>
					<script>
					Swal.fire({
						type: 'info',
						title: 'Restore session expired',
						text: 'Your restore session has expired.'
					}).then(function(e) {
						window.location.href = '/exchange';
					});
					</script>
					<?php
				} else {
					for ($i = 0; $i < count($users['results']); $i++) {
						array_push($content, array('name'=> $users['results'][$i]['name'], 'id' => $users['results'][$i]['id']));
					}

					uasort($content, function($a, $b) {
						return strcasecmp($a['name'], $b['name']);
					});

					echo '<button class="btn btn-default btn-danger btn-stop-restore" title="Stop Restore">Stop Restore</button>';
					echo '<div class="separator"></div>';
					echo '<ul id="ul-exchange-users">';
					
					foreach ($content as $key => $value) {
						if (isset($uid) && !empty($uid) && ($uid == $value['id'])) {
							echo '<li class="active"><a href="exchange/' . $org['id'] . '/' . $value['id'] . '">' . $value['name'] . '</a></li>';
						} else {
							echo '<li><a href="exchange/' . $org['id'] . '/' . $value['id'] . '">' . $value['name'] . '</a></li>';
						}
					}
					
					echo '</ul>';
				}
			} else {
				?>
				<script>
				Swal.fire({
					type: 'info',
					showConfirmButton: false,
					title: 'Restore session running',
					text: 'Found another restore session running, please stop the session first if you want to restore Exchange items.',
					<?php
					if (strcmp($_SESSION['rtype'], 'vesp') === 0) {
						echo "footer: '<a href=\"/sharepoint\">Go to restore session</a>'";
					} else {
						echo "footer: '<a href=\"/onedrive\">Go to restore session</a>'";
					}
					?>
				})
				</script>
				<?php
				exit;
			}
			}
			?>
		</menu>
		<div class="separator"></div>
		<div class="bottom-padding"></div>
	</aside>
	<main id="main">
		<h1>Exchange</h1>
		<div class="exchange-container">
		<?php
		if (isset($oid) || $menu) {
			if ($check === false && strtolower($administrator) == 'yes') {
				$org = $veeam->getOrganizationByID($oid);
			}
		?>
		<div class="row">
			<div class="col-sm-2 text-left marginexplore">
				<button class="btn btn-default btn-secondary btn-start-restore" title="Explore last backup (<?php echo date('d/m/Y H:i T', strtotime($org['lastBackuptime'])); ?>)" <?php if (isset($_GET['oid'])) { echo 'data-oid="' . $_GET['oid'] . '"'; } ?> data-pit="<?php echo date('Y.m.d H:i', strtotime($org['lastBackuptime'])); ?>" data-latest="true">Explore last backup</button>
			</div>
			<div class="col-sm-2 text-left">
				<div class="input-group flatpickr paddingdate" data-wrap="true" data-clickOpens="false">
					<input type="text" class="form-control" id="pit-date" placeholder="Select a date..." data-input>
					<span class="input-group-addon" data-open><i class="fa fa-calendar"></i></span>
					<script>
					$('#pit-date').removeClass('errorClass');

					$('.flatpickr').flatpickr({
						dateFormat: "Y.m.d H:i",
						enableTime: true,
						minDate: "<?php echo date('Y.m.d', strtotime($org['firstBackuptime'])); ?>",
						maxDate: "<?php echo date('Y.m.d', strtotime($org['lastBackuptime'])); ?>",
						time_24hr: true
					});
					</script>
				</div>
			</div>
			<div class="col-sm-8 text-left">
				<button class="btn btn-default btn-secondary btn-start-restore" title="Start Restore" <?php if (isset($_GET['oid'])) { echo 'data-oid="' . $_GET['oid'] . '"'; } ?> data-latest="false">Start Restore</button>
			</div>
		</div>
		<?php
		}
		
		if (!isset($_SESSION['rid'])) { /* No restore session is running */
			if (isset($oid) && !empty($oid)) { /* We got an organization ID so list all users and their state */
				$org = $veeam->getOrganizationByID($oid);
				$users = $veeam->getLicensedUsers($oid);
				$repo = $veeam->getOrganizationRepository($oid);
				$usersarray = array();
				
				for ($i = 0; $i < count($users['results']); $i++) {
					array_push($usersarray, array(
						'id' => $users['results'][$i]['id'],
						'isBackedUp' => $users['results'][$i]['isBackedUp'],
						'lastBackupDate' => $users['results'][$i]['lastBackupDate']
					));
				}

				if (count($users['results']) != '0') { /* Gather the backed up users from the repositories related to the organization */
					$repousersarray = array(); /* Array used to sort the users in case of double data on the repositories */
					
					for ($i = 0; $i < count($repo); $i++) {
						$id = explode('/', $repo[$i]['_links']['backupRepository']['href']); /* Get the organization ID */
						$repoid = end($id);

						for ($j = 0; $j < count($users['results']); $j++) {
							$combinedid = $users['results'][$j]['backedUpOrganizationId'] . $users['results'][$j]['id'];
							$userdata = $veeam->getUserData($repoid, $combinedid);
							
							/* Only store data when the Mailbox or Archive data is backed up */
							if (!is_null($userdata) && ($userdata['isMailboxBackedUp'] || $userdata['isArchiveBackedUp'])) {
								array_push($repousersarray, array(
										'id' => $userdata['accountId'], 
										'email' => $userdata['email'],
										'name' => $userdata['displayName'],
										'isMailboxBackedUp' => $userdata['isMailboxBackedUp'],
										'isArchiveBackedUp' => $userdata['isArchiveBackedUp']
								));
							}
						}
					}
					
					$usersort = array_values(array_column($repousersarray , null, 'name')); /* Sort the array and make sure every value is unique */
				}

				if (count($usersort) != '0') {
				?>
				<div class="alert alert-info">The following is an overview on all backed up accounts and their objects within the organization.</div>
				<table class="table table-bordered table-padding table-striped">
					<thead>
						<tr>
							<th>Account</th>
							<th>Objects in backup</th>
							<th>Last backup</th>
						</tr>
					</thead>
					<tbody>
					<?php
					for ($i = 0; $i < count($usersort); $i++) {
						$licinfo = array_search($usersort[$i]['id'], array_column($usersarray, 'id')); /* Get the last backup date for this specific account */
						echo '<tr>';
						echo '<td>' . $usersort[$i]['name'] . ' (' . $usersort[$i]['email'] . ')</td>';
						echo '<td>';
						if ($usersort[$i]['isMailboxBackedUp']) {
							echo '<i class="far fa-envelope fa-2x" style="color:green" title="Mailbox"></i> ';
						} else {
							echo '<i class="far fa-envelope fa-2x" style="color:red" title="Mailbox"></i> ';
						}
						if ($usersort[$i]['isArchiveBackedUp']) {
							echo '<i class="fa fa-archive fa-2x" style="color:green" title="Archive"></i> ';
						} else {
							echo '<i class="fa fa-archive fa-2x" style="color:red" title="Archive"></i> ';
						}
						echo '</td>';
						echo '<td>' . date('d/m/Y H:i T', strtotime($usersarray[$licinfo]['lastBackupDate'])) . '</td>';
						echo '</tr>';
					}
					?>
					</tbody>
				</table>
				<?php
				} else { /* No users available for the organization ID */
					if ($check === false && strtolower($administrator) == 'yes') { /* Admin */
						echo '<p>No users found for this organization.</p>';
					} else { /* Tenant */
						echo '<p>Select a point in time and start the restore.</p>';
					}
				}
				
			} else { /* No organization has been selected */
				if ($check === false && strtolower($administrator) == 'yes') { /* Admin */
					echo '<p>Select an organization to start a restore session.</p>';
				} else { /* Tenant */
					echo '<p>Select a point in time and start the restore.</p>';
				}
			}
		} else { /* Restore session is running */
			if (isset($uid) && !empty($uid)) {
				$owner = $veeam->getMailboxID($rid, $uid);
				$folders = $veeam->getMailboxFolders($uid, $rid);
				$items = $veeam->getMailboxItems($uid, $rid);

				if (count($items['results']) != '0') {
		?>
		<div class="col-sm-2 text-left">
			<div class="btn-group dropdown"> <!-- Multiple restore dropdown -->
				<button class="btn btn-default dropdown-toggle form-control" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">Restore selected <span class="caret"></span></button>
				<ul class="dropdown-menu dropdown-menu-right">
				  <li class="dropdown-header">Download as</li>
				  <li><a class="dropdown-link download-pst" data-itemid="multipleexport" data-mailboxid="<?php echo $uid; ?>" data-mailsubject="<?php echo $owner['name']; ?>" data-type="multiple" href="<?php echo $_SERVER['REQUEST_URI']; ?>#"><i class="fa fa-download"></i> PST file</a></li>
				  <li class="divider"></li>
				  <li class="dropdown-header">Restore to</li>
				  <li><a class="dropdown-link restore-original" data-itemid="multiplerestore" data-mailboxid="<?php echo $uid; ?>" data-type="multiple" href="<?php echo $_SERVER['REQUEST_URI']; ?>#"><i class="fa fa-upload"></i> Original location</a></li>
				  <li><a class="dropdown-link restore-different" data-itemid="multiplerestore" data-mailboxid="<?php echo $uid; ?>" data-type="multiple" href="<?php echo $_SERVER['REQUEST_URI']; ?>#"><i class="fa fa-upload"></i> Different location</a></li>
				</ul>
			</div>
		</div>
		<div class="col-sm-2">
			<select class="form-control padding" id="inbox-nav">
				<option disabled selected>-- Filter by folder --</option>
			<?php
			for ($i = 0; $i < count($folders['results']); $i++) {
			?>
				<option data-folderid="<?php echo $folders['results'][$i]['id']; ?>" data-mailboxid="<?php echo $uid; ?>"><?php echo $folders['results'][$i]['name']; ?></option>
			<?php
			}
			?>
			</select>
		</div>
		<div class="col-sm-8">
			<input class="form-control search" id="search-mailbox" placeholder="Filter by item..." />
		</div>
		<table class="table table-hover table-bordered table-striped table-border" id="table-exchange-items">
			<thead>
				<tr>
					<th class="text-center"><input type="checkbox" id="chk-all" title="Select all"></th>
					<th class="text-center"><strong>Type</strong></th>
					<th><strong>From</strong></th>
					<th><strong>Subject</strong></th>
					<th class="text-center"><strong>Received</strong></th>
					<th class="text-center"><strong>Options</strong></th>
				</tr>
			</thead>
			<tbody>
			<?php
			for ($i = 0; $i < count($items['results']); $i++) {
			?>
				<tr>
					<td class="text-center"><input type="checkbox" name="checkbox-mail" value="<?php echo $items['results'][$i]['id']; ?>"></td>
					<?php
					if ($items['results'][$i]['itemClass'] != 'IPM.Appointment') {
						echo '<td class="text-center"><span class="logo fa fa-envelope"></span></td>';
						echo '<td>' . $items['results'][$i]['from'] . '</td>';
					} else {
						echo '<td class="text-center"><span class="logo fa fa-calendar"></span></td>';
						echo '<td>' . $items['results'][$i]['organizer'] . '</td>';
					}
					?>
					<td><?php echo $items['results'][$i]['subject']; ?></td>
					<td class="text-center">
					<?php 
					if ($items['results'][$i]['itemClass'] != 'IPM.Appointment') {
						echo date('d/m/Y H:i', strtotime($items['results'][$i]['received']));
					}
					?>
					</td>
					<td class="text-center">
						<div class="btn-group dropdown"> <!-- Single restore dropdown -->
							<button class="btn btn-default dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">Options <span class="caret"></span></button>
							<ul class="dropdown-menu dropdown-menu-right">
							  <li class="dropdown-header">Download as</li>
							  <li><a class="dropdown-link download-msg" data-itemid="<?php echo $items['results'][$i]['id']; ?>" data-mailsubject="<?php echo $items['results'][$i]['subject']; ?>" data-mailboxid="<?php echo $uid; ?>" href="<?php echo $_SERVER['REQUEST_URI']; ?>#"><i class="fa fa-download"></i> MSG file</a></li>
							  <li><a class="dropdown-link download-pst" data-itemid="<?php echo $items['results'][$i]['id']; ?>" data-mailsubject="<?php echo $items['results'][$i]['subject']; ?>" data-mailboxid="<?php echo $uid; ?>" data-type="single" href="<?php echo $_SERVER['REQUEST_URI']; ?>#"><i class="fa fa-download"></i> PST file</a></li>
							  <li class="divider"></li>
							  <li class="dropdown-header">Restore to</li>
							  <li><a class="dropdown-link restore-original" data-itemid="<?php echo $items['results'][$i]['id']; ?>" data-mailboxid="<?php echo $uid; ?>" data-type="single" href="<?php echo $_SERVER['REQUEST_URI']; ?>#"><i class="fa fa-upload"></i> Original location</a></li>
							  <li><a class="dropdown-link restore-different" data-itemid="<?php echo $items['results'][$i]['id']; ?>" data-mailboxid="<?php echo $uid; ?>" data-type="single" href="<?php echo $_SERVER['REQUEST_URI']; ?>#"><i class="fa fa-upload"></i> Different location</a></li>
							</ul>
						</div>
					</td>
				</tr>
			<?php
			}
			?>
			</tbody>
		</table>
		<div class="text-center">
				<?php
				if (count($items['results']) == '30') { /* If we have 30 messages from the first request, show message to load additional messages */
				?>
					<a class="btn btn-default load-more-link" data-folderid="null" data-mailboxid="<?php echo $uid; ?>" data-offset="<?php echo count($items['results'])+1; ?>" href="<?php echo $_SERVER['REQUEST_URI']; ?>#">Load more messages</a>
				<?php
				} else { /* Else hide the load more messages message */
				?>
					<a class="btn btn-default hide load-more-link" data-folderid="null" data-mailboxid="<?php echo $uid; ?>" data-offset="<?php echo count($items['results'])+1; ?>" href="<?php echo $_SERVER['REQUEST_URI']; ?>#">Load more messages</a>
				<?php
				}
				?>
			</div>
		<?php
				} else {
					echo '<p>No items available in this mailbox.</p>';
				}
			} else { /* List all mailboxes */
				?>				
				<table class="table table-bordered table-padding table-striped">
					<thead>
						<tr>
							<th>Name</th>
							<th>E-mail</th>
							<th class="text-center">Options</th>
						</tr>
					</thead>
					<tbody>
						<?php
						$mailboxes = array();
						
						for ($i = 0; $i < count($users['results']); $i++) {
							array_push($mailboxes, array('name'=> $users['results'][$i]['name'], 'email'=> $users['results'][$i]['email'], 'id' => $users['results'][$i]['id']));
						}

						uasort($mailboxes, function($a, $b) {
							return strcasecmp($a['name'], $b['name']);
						});
				
						foreach ($mailboxes as $key => $value) {
						?>
						<tr>
							<td><a href="exchange/<?php echo $org['id']; ?>/<?php echo $value['id']; ?>"><?php echo $value['name']; ?></a></td>
							<td><?php echo $value['email']; ?></td>
							<td class="text-center">
								<div class="btn-group dropdown"> <!-- Full restore dropdown -->
									<button class="btn btn-default dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">Options <span class="caret"></span></button>
									<ul class="dropdown-menu dropdown-menu-right">
									  <li class="dropdown-header">Download as</li>
									  <li><a class="dropdown-link download-pst" data-itemid="<?php echo $value['name']; ?>" data-mailsubject="<?php echo $value['name']; ?>" data-mailboxid="<?php echo $value['id']; ?>" data-type="full" href="<?php echo $_SERVER['REQUEST_URI']; ?>#"><i class="fa fa-download"></i> PST file</a></li>
									  <li class="divider"></li>
									  <li class="dropdown-header">Restore to</li>
									  <li><a class="dropdown-link restore-original" data-itemid="<?php echo $value['name']; ?>" data-mailboxid="<?php echo $value['id']; ?>" data-type="full" href="<?php echo $_SERVER['REQUEST_URI']; ?>#"><i class="fa fa-upload"></i> Original location</a></li>
									  <li><a class="dropdown-link restore-different" data-itemid="<?php echo $value['name']; ?>" data-mailboxid="<?php echo $value['id']; ?>" data-type="full" href="<?php echo $_SERVER['REQUEST_URI']; ?>#"><i class="fa fa-upload"></i> Different location</a></li>
									</ul>
								</div>
							</td>
						</tr>
						<?php
						}
						?>
					</tbody>
				</table>
				<?php
			}
		}
		?>
		</div>
	</main>
</div>
<div class="bottom-padding"></div>
<script>
/* Logout option */
$('#logout').click(function(e) {
	e.preventDefault();
	
	const swalWithBootstrapButtons = Swal.mixin({
	  confirmButtonClass: 'btn btn-success btn-margin',
	  cancelButtonClass: 'btn btn-danger',
	  buttonsStyling: false,
	})
	
	swalWithBootstrapButtons.fire({
		type: 'question',
		title: 'Logout',
		text: 'You are about to logout. Are you sure you want to continue?',
		showCancelButton: true,
		confirmButtonText: 'Yes',
		cancelButtonText: 'No',
	}).then((result) => {
		if (result.value) {
			$.post('index.php', {'logout' : true}, function(data) {
				window.location.replace('index.php');
			});
		  } else {
			return;
		}
	})
});

/* Exchange Restore Buttons */
$('.btn-start-restore').click(function(e) {
    if (typeof $(this).data('jid') !== 'undefined') {
        var jid = $(this).data('jid'); /* Job ID */
    }

    if (typeof $(this).data('oid') !== 'undefined') {
        var oid = $(this).data('oid'); /* Organization ID */
    } else {
        var oid = 'tenant';
	}
	
	if ($(this).data('latest')) {
		var pit = $(this).data('pit');
	} else {
		if (!document.getElementById('pit-date').value) { /* No date has been selected */
			$('#pit-date').addClass('errorClass');
			Swal.fire({
				type: 'info',
				title: 'No date selected',
				<?php
				if ($check === false && strtolower($administrator) == 'yes') {
					echo "text: 'No date selected, please select a date first before starting the restore or use the \"explore last backup\" button.'";
				} else {
					echo "text: 'No date selected, please select a date first before starting the restore.'";
				}
				?>
			})
			return;
		} else {
			var pit = $('#pit-date').val(); /* Point in time date */
			$('#pit-date').removeClass('errorClass');
		}
	}

    var json = '{ "explore": { "datetime": "' + pit + '", "type": "vex", "ShowAllVersions": "true", "ShowDeleted": "true" } }'; /* JSON code to start the restore session */

    $(':button').prop('disabled', true); /* Disable all buttons to prevent double start */

    $.get('veeam.php', {'action' : 'startrestore', 'json' : json, 'id' : oid}).done(function(data) {
        if (data.match(/([a-zA-Z0-9]{8})-([a-zA-Z0-9]{4})-([a-zA-Z0-9]{4})-([a-zA-Z0-9]{4})-([a-zA-Z0-9]{12})/g)) {
            e.preventDefault();

			Swal.fire({
				type: 'success',
				title: 'Session started',
				text: 'Restore session has been started and you can now perform item restores.'
			}).then(function(e) {
				window.location.href = 'exchange';
			});
        } else {
            Swal.fire({
				type: 'error',
				title: 'Error starting restore session',
				text: '' + data
			})
            $(':button').prop('disabled', false); /* Enable all buttons again */
        }
    });
});
$('.btn-stop-restore').click(function(e) {
    var rid = '<?php echo $rid; ?>'; /* Restore Session ID */

    e.preventDefault();

	const swalWithBootstrapButtons = Swal.mixin({
	  confirmButtonClass: 'btn btn-success btn-margin',
	  cancelButtonClass: 'btn btn-danger',
	  buttonsStyling: false,
	})
	
	swalWithBootstrapButtons.fire({
		type: 'question',
		title: 'Stop the restore session?',
		text: 'This will terminate any restore options for the specific point in time.',
		showCancelButton: true,
		confirmButtonText: 'Yes',
		cancelButtonText: 'No',
	}).then((result) => {
		if (result.value) {
			$.get('veeam.php', {'action' : 'stoprestore', 'id' : rid}).done(function(data) {
				swalWithBootstrapButtons.fire({
					type: 'success', 
					title: 'Restore session has stopped',
					text: 'The restore session has stopped successfully.',
				}).then(function(e) {
					window.location.href = 'exchange';
				});
			});
		  } else {
			return;
		}
	})
});

<?php
if (isset($rid)) {
?>
/* Dropdown settings for restore buttons */
$('hide.bs.dropdown').dropdown(function(e) {
    $(e.target).find('>.dropdown-menu:first').slideUp();
});
$('show.bs.dropdown').dropdown(function(e) {
    $(e.target).find('>.dropdown-menu:first').slideDown();
});

/* Select all checkbox */
$('#chk-all').click(function(e) {
    var table = $(e.target).closest('table');
    $('tr:visible :checkbox', table).prop('checked', this.checked);
});

/* Item search */
$('#search-mailbox').keyup(function(e) {
    var searchText = $(this).val().toLowerCase();
    /* Show only matching row, hide rest of them */
    $.each($('#table-exchange-items tbody tr'), function(e) {
        if ($(this).text().toLowerCase().indexOf(searchText) === -1) {
           $(this).hide();
        } else {
           $(this).show();
        }
    });
});

/* Users and folder navigation content */
$('#inbox-nav').change(function(e) {
    var folderid = $('#inbox-nav option:selected').data('folderid');
    var mailboxid = $('#inbox-nav option:selected').data('mailboxid');
    var offset = 0;
    var rid = '<?php echo $rid; ?>'; /* Restore Session ID */

    loadMessages(folderid, mailboxid, rid, offset, '1');
});
$('ul#ul-exchange-users li').click(function(e) {
    $(this).parent().find('li.active').removeClass('active');
    $(this).addClass('active');
});

/* Load more link for e-mail content */
$('.load-more-link').click(function(e) {
    var folderid = $(this).data('folderid');
    var mailboxid = $(this).data('mailboxid');
    var offset = $(this).data('offset');
    var rid = '<?php echo $rid; ?>'; /* Restore Session ID */
    
    loadMessages(folderid, mailboxid, rid, offset);
});

/* Export and restore options for restore buttons based upon specific action per button */
/* Export to MSG file */
$('.download-msg').click(function(e) {
    var itemid = $(this).data('itemid');
    var mailboxid = $(this).data('mailboxid');
    var rid = '<?php echo $rid; ?>'; /* Restore Session ID */
    var json = '{ "savetoMsg": null }';
    var mailsubject = $(this).data('mailsubject');
	
	Swal.fire({
		type: 'info',
		title: 'Download is starting',
		text: 'Download will start soon.'
	})
        
	$.get('veeam.php', {'action': 'exportmailitem', 'itemid' : itemid, 'mailboxid' : mailboxid, 'rid' : rid, 'json' : json}).done(function(data) {
		e.preventDefault();
		
		if (data) {
			$.redirect('download.php', {ext : 'msg', file : data, name : mailsubject}, 'POST');
			
			Swal.close();
		} else {
			Swal.fire({
				type: 'error',
				title: 'Export failed',
				text: 'Export failed.'
			})
			return;
		}
	});
});

/* Export to PST file */
$('.download-pst').click(function(e) {
	var itemid = $(this).data('itemid');
    var mailboxid = $(this).data('mailboxid');
    var rid = '<?php echo $rid; ?>'; /* Restore Session ID */
	var type = $(this).data('type');
	
	Swal.fire({
		type: 'info',
		title: 'Download is starting',
		text: 'Export in progress and your download will start soon...'
	})
	
	if (type == 'multiple') { /* Multiple items export */
		var act = 'exportmultiplemailitems';
		var ids = '';
		var mailsubject = 'exported-mailitems-' + $(this).data('mailsubject'); /* exported-mailitems-username */  
		
		if ($("input[name='checkbox-mail']:checked").length == 0) { /* Error handling for multiple export button */
			Swal.close();
			
			Swal.fire({
				type: 'error',
				title: 'Restore failed',
				text: 'No items have been selected.'
			})
			
			return;
		}
		
		$("input[name='checkbox-mail']:checked").each(function(e) {
			ids = ids + '{ "Id": "' + this.value + '" }, ';
		});
		
		var json = '{ \
			"ExportToPst": { \
				"EnablePstSizeLimit": "false", \
				"items": [ \
				' + ids + ' \
				\ ] \
			} \
		}';
	} else {
		if (type == 'single') {	/* Single item export */
			var act = 'exportmailitem';
			var mailsubject = $(this).data('mailsubject');
		} else { /* Full mailbox export */
			var act = 'exportmailbox';
			var mailsubject = 'mailbox-' + $(this).data('mailsubject'); /* mailbox-username */
		}
		
		var json = '{ \
			"ExportToPst": { \
				"EnablePstSizeLimit": "false", \
			} \
		}';
	}

	$.get('veeam.php', {'action' : act, 'itemid' : itemid, 'mailboxid' : mailboxid, 'rid' : rid, 'json' : json}).done(function(data) {
		e.preventDefault();
		
		if (data && data != '500') {
			$.redirect('download.php', {ext : 'pst', file : data, name : mailsubject}, 'POST');
				
			Swal.close();
		} else {
			Swal.fire({
				type: 'error',
				title: 'Export failed',
				text: '' + data
			})
		}
			
		return;
	});
});

/* Restore to a different location */
$('.restore-different').click(function(e) {
	var itemid = $(this).data('itemid');
    var mailboxid = $(this).data('mailboxid');
    var rid = '<?php echo $rid; ?>'; /* Restore Session ID */
	var type = $(this).data('type');
	
	if (type == 'multiple' && $("input[name='checkbox-mail']:checked").length == 0) { /* Error handling for multiple restore button */
		Swal.fire({
			type: 'error',
			title: 'Restore failed',
			text: 'No items have been selected.'
		})
		return;
	}
	
	const swalWithBootstrapButtons = Swal.mixin({
	  confirmButtonClass: 'btn btn-success',
	  cancelButtonClass: 'btn btn-danger btn-margin',
	  buttonsStyling: false,
	  input: 'text'
	})
	
	swalWithBootstrapButtons.fire({
		title: 'Restore to a different location',
		html: 
			'<form>' +
			'<div class="form-group row">' +
			'<label for="restore-different-mailbox" class="col-sm-4 col-form-label text-right">Target mailbox:</label>' +
			'<div class="col-sm-8"><input type="text" class="form-control restoredata" id="restore-different-mailbox" placeholder="user@example.onmicrosoft.com"></input></div>' +
			'</div>' +
			'<div class="form-group row">' +
			'<label for="restore-different-server" class="col-sm-4 col-form-label text-right">Target server:</label>' +
			'<div class="col-sm-8"><input type="text" class="form-control restoredata" id="restore-different-server" placeholder="outlook.office365.com" value="outlook.office365.com"></input></div>' +
			'</div>' +
			'<div class="form-group row">' +
			'<label for="restore-different-user" class="col-sm-4 col-form-label text-right">Username:</label>' +
			'<div class="col-sm-8"><input type="text" class="form-control restoredata" id="restore-different-user" placeholder="user@example.onmicrosoft.com"></input></div>' +
			'</div>' +
			'<div class="form-group row">' +
			'<label for="restore-different-pass" class="col-sm-4 col-form-label text-right">Password:</label>' +
			'<div class="col-sm-8"><input type="password" class="form-control restoredata" id="restore-different-pass" placeholder="password"></input></div>' +
			'</div>' + 
			'<div class="form-group row">' + 
			'<label for="restore-different-folder" class="col-sm-4 col-form-label text-right">Folder:</label>' +
			'<div class="col-sm-8"><input type="text" class="form-control" id="restore-different-folder" placeholder="Custom folder (optional)"></input></div>' +
			'<br /><h6>By default items will be restored in a folder named <em>Restored-via-web-client</em>.</h6>' +
			'</div>' +
			'</form>',
		focusConfirm: false,
		showCancelButton: true,
		confirmButtonText: 'Restore',
		cancelButtonText: 'Cancel',
		reverseButtons: true,
		inputValidator: () => {
			var elem = document.getElementById('swal2-validation-message');
			elem.style.setProperty('margin', '10px 0px', '');
			
			var restoredata = Object.values(document.getElementsByClassName('restoredata'));
			var errors = [ 'No target mailbox defined.', 'No target mailbox server defined.', 'No username defined.', 'No password defined.' ];
			
			for (var i = 0; i < restoredata.length; i++) {
				if (!restoredata[i].value)
					return errors[i];
			}
		},
		onBeforeOpen: function (dom) {
			swal.getInput().style.display = 'none';
		},
		preConfirm: function() {
		   return new Promise(function(resolve) {
				resolve([
					$('#restore-different-mailbox').val(),
					$('#restore-different-server').val(),
					$('#restore-different-user').val(),
					$('#restore-different-pass').val(),
				 ]);
			});
		},
	}).then(function(result) {
		if (result.value) {
			var user = $('#restore-different-user').val();
			var pass = $('#restore-different-pass').val();
			var server = $('#restore-different-server').val();
			var folder = $('#restore-different-folder').val();
			var mailbox = $('#restore-different-mailbox').val();
			
			Swal.fire({
				type: 'info',
				title: 'Item restore in progress',
				text: 'Restore in progress...'
			})
			
			if (typeof folder === undefined || !folder) {
				folder = 'Restored-via-web-client';
			}
			
			if (type == 'multiple') { /* Multiple items restore */
				var act = 'restoremultiplemailitems';
				var ids = '';

				$("input[name='checkbox-mail']:checked").each(function(e) {
					ids = ids + '{ "Id": "' + this.value + '" }, ';
				});
				
				var json = '{ "restoreTo": \
					{ "casServer": "' + server + '", \
					  "mailbox": "' + mailbox + '", \
					  "folder": "' + folder + '", \
					  "userName": "' + user + '", \
					  "userPassword": "' + pass + '", \
					  "changedItems": "true", \
					  "deletedItems": "true", \
					  "markRestoredAsUnread": "true", \
					  "excludeDrafts": "false", \
					  "excludeDeletedItems": "false", \
					  "excludeInPlaceHoldItems": "true", \
					  "excludeLitigationHoldItems": "true", \
					  "items": [ \
						' + ids + ' \
					\ ] \
					} \
				}';
			} else {
				if (type == 'single') { /* Single item restore */
					var act = 'restoremailitem';
				} else { /* Full mailbox restore */
					var act = 'restoremailbox';
				}
				
				var json = '{ "restoreTo": \
					{ "casServer": "' + server + '", \
					  "mailbox": "' + mailbox + '", \
					  "folder": "' + folder + '", \
					  "userName": "' + user + '", \
					  "userPassword": "' + pass + '", \
					  "changedItems": "true", \
					  "deletedItems": "true", \
					  "markRestoredAsUnread": "true", \
					  "markRestoredAsUnread": "true", \
					  "excludeDrafts": "false", \
					  "excludeDeletedItems": "false", \
					  "excludeInPlaceHoldItems": "true", \
					  "excludeLitigationHoldItems": "true" \
					} \
				}';
			}

			$.get('veeam.php', {'action' : act, 'itemid' : itemid, 'mailboxid' : mailboxid, 'rid' : rid, 'json' : json}).done(function(data) {
				Swal.fire({
					type: 'info',
					title: 'Item restore',
					text: '' + data
				})
			});
		} else {
			return;
		}
	});
}); 

/* Restore to original location */
$('.restore-original').click(function(e) {
	var itemid = $(this).data('itemid');
    var mailboxid = $(this).data('mailboxid');
    var rid = '<?php echo $rid; ?>'; /* Restore Session ID */
	var type = $(this).data('type');

	if (type == 'multiple' && $("input[name='checkbox-mail']:checked").length == 0) { /* Error handling for multiple restore button */
		Swal.fire({
			type: 'error',
			title: 'Restore failed',
			text: 'No items have been selected.'
		})
		return;
	}
	
	const swalWithBootstrapButtons = Swal.mixin({
	  confirmButtonClass: 'btn btn-success',
	  cancelButtonClass: 'btn btn-danger btn-margin',
	  buttonsStyling: false,
	  input: 'text'
	})
	
	swalWithBootstrapButtons.fire({
		title: 'Restore to the original location',
		html: 
			'<form>' +
			'<div class="form-group row">' +
			'<label for="restore-original-user" class="col-sm-4 col-form-label text-right">Username:</label>' +
			'<div class="col-sm-8"><input type="text" class="form-control restoredata" id="restore-original-user" placeholder="user@example.onmicrosoft.com"></input></div>' +
			'</div>' +
			'<div class="form-group row">' +
			'<label for="restore-original-pass" class="col-sm-4 col-form-label text-right">Password:</label>' +
			'<div class="col-sm-8"><input type="password" class="form-control restoredata" id="restore-original-pass" placeholder="password"></input></div>' +
			'</div>' +
			'</form>',
		focusConfirm: false,
		showCancelButton: true,
		confirmButtonText: 'Restore',
		cancelButtonText: 'Cancel',
		reverseButtons: true,
		inputValidator: () => {
			var elem = document.getElementById('swal2-validation-message');
			elem.style.setProperty('margin', '10px 0px', '');
			
			var restoredata = Object.values(document.getElementsByClassName("restoredata"));
			var errors = [ 'No username defined.', 'No password defined.' ];
			
			for (var i = 0; i < restoredata.length; i++) {
				if (!restoredata[i].value)
					return errors[i];
			}
		},
		onBeforeOpen: function (dom) {
			swal.getInput().style.display = 'none';
		},
		preConfirm: function() {
		   return new Promise(function(resolve) {
				resolve([
					$('#restore-original-user').val(),
					$('#restore-original-pass').val(),
				 ]);
			});
		},
	}).then(function(result) {
		if (result.value) {
			var user = $('#restore-original-user').val();
			var pass = $('#restore-original-pass').val();
			
			Swal.fire({
				type: 'info',
				title: 'Item restore in progress',
				text: 'Restore in progress...'
			})

			if (type == 'multiple') { /* Multiple items restore */
				var act = 'restoremultiplemailitems';
				var ids = '';
				
				$("input[name='checkbox-mail']:checked").each(function(e) {
					ids = ids + '{ "Id": "' + this.value + '" }, ';
				});
				
				var json = '{ "restoretoOriginallocation": \
					{ "userName": "' + user + '", \
					  "userPassword": "' + pass + '", \
					  "items": [ \
						' + ids + ' \
					\ ] \
					} \
				}';
			} else {
				if (type == 'single') { /* Single item restore */
					var act = 'restoremailitem';
				} else if (type == 'full') { /* Full mailbox restore */
					var act = 'restoremailbox';
				}
				
				var json = '{ "restoretoOriginallocation": \
					{ "userName": "' + user + '", \
					  "userPassword": "' + pass + '", \
					} \
				}';
			}
			
			$.get('veeam.php', {'action' : act, 'itemid' : itemid, 'mailboxid' : mailboxid, 'rid' : rid, 'json' : json}).done(function(data) {
				Swal.fire({
					type: 'info',
					title: 'Item restore',
					text: '' + data
				})
			});
		  } else {
			return;
		}
	});
});

/* Exchange functions */
/*
 * @param folderid Folder ID
 * @param mailboxid Mailbox ID
 * @param rid Restore session ID
 * @param offset Offset
 * @param cleartable 1 or undefined
 */
function loadMessages(folderid, mailboxid, rid, offset, cleartable) {
    $.get('veeam.php', {'action' : 'getmailitems', 'folderid' : folderid, 'offset' : offset, 'mailboxid' : mailboxid, 'rid' : rid}).done(function(data) {
        var response = JSON.parse(data);

        if (typeof cleartable !== 'undefined') {
            $('#table-exchange-items tbody').empty();
        }
        
        if (response.results.length != '0') {
            $('a.load-more-link').removeClass('hide');
            $('a.load-more-link').data('offset', offset + 30); /* Update offset for loading more messages */
            $('a.load-more-link').data('folderid', folderid); /* Update folder ID for loading more messages */
            
            for (var i = 0; i < response.results.length; i++) {
                if (response.results[i].itemClass != 'IPM.Appointment') { /* Appointments have a different icon */
                    $('#table-exchange-items tbody').append('<tr> \
					    <td class="text-center"><input type="checkbox" name="checkbox-mail" value="' + response.results[i].id + '"></td> \
                        <td class="text-center"><span class="logo fa fa-envelope"></span></td> \
                        <td>' + response.results[i].from + '</td> \
                        <td>' + response.results[i].subject + '</td> \
                        <td class="text-center">' + moment(response.results[i].received).format('DD/MM/YYYY HH:mm') + '</td> \
                        <td class="text-center"> \
                        <div class="btn-group dropdown"> \
                        <button class="btn btn-default dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">Options <span class="caret"></span></button> \
                        <ul class="dropdown-menu dropdown-menu-right"> \
                        <li class="dropdown-header">Download as</li> \
                        <li><a class="dropdown-link download-msg" data-itemid="' + response.results[i].id + '" data-mailsubject="' + response.results[i].subject + '" data-mailboxid="' + mailboxid + '" href="' + window.location + '"><i class="fa fa-download"></i> MSG file</a></li> \
                        <li><a class="dropdown-link download-pst" data-itemid="' + response.results[i].id + '" data-mailsubject="' + response.results[i].subject + '" data-mailboxid="' + mailboxid + '" data-type="single" href="' + window.location + '"><i class="fa fa-download"></i> PST file</a></li> \
                        <li class="divider"></li> \
                        <li class="dropdown-header">Restore to</li> \
                        <li><a class="dropdown-link restore-original" data-itemid="' + response.results[i].id + '" data-mailboxid="' + mailboxid + '" data-type="single" href="' + window.location + '"><i class="fa fa-upload"></i> Original location</a></li> \
                        <li><a class="dropdown-link restore-different" data-itemid="' + response.results[i].id + '" data-mailboxid="' + mailboxid + '" data-type="single" href="' + window.location + '"><i class="fa fa-upload"></i> Different location</a></li> \
                        </ul> \
                        </div> \
                        </td> \
                        </tr>');
                } else { /* E-mails have a standard icon */
                    $('#table-exchange-items tbody').append('<tr> \
						<td class="text-center"><input type="checkbox" name="checkbox-mail" value="' + response.results[i].id + '"> \
                        <td class="text-center"><span class="logo fa fa-calendar"></span></td> \
                        <td>' + response.results[i].organizer + '</td> \
                        <td>' + response.results[i].subject + '</td> \
                        <td></td> \
                        <td class="text-center"> \
                        <div class="btn-group dropdown"> \
                        <button class="btn btn-default dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">Options <span class="caret"></span></button> \
                        <ul class="dropdown-menu dropdown-menu-right"> \
                        <li class="dropdown-header">Download as</li> \
                        <li><a class="dropdown-link download-msg" data-itemid="' + response.results[i].id + '" data-mailsubject="' + response.results[i].subject + '" data-mailboxid="' + mailboxid + '" href="' + window.location + '"><i class="fa fa-download"></i> MSG file</a></li> \
                        <li><a class="dropdown-link download-pst" data-itemid="' + response.results[i].id + '" data-mailsubject="' + response.results[i].subject + '" data-mailboxid="' + mailboxid + '" data-type="single" href="' + window.location + '"><i class="fa fa-download"></i> PST file</a></li> \
                        <li class="divider"></li> \
                        <li class="dropdown-header">Restore to</li> \
                        <li><a class="dropdown-link restore-original" data-itemid="' + response.results[i].id + '" data-mailboxid="' + mailboxid + '" data-type="single" href="' + window.location + '"><i class="fa fa-upload"></i> Original location</a></li> \
                        <li><a class="dropdown-link restore-different" data-itemid="' + response.results[i].id + '" data-mailboxid="' + mailboxid + '" data-type="single" href="' + window.location + '"><i class="fa fa-upload"></i> Different location</a></li> \
                        </ul> \
                        </div> \
                        </td> \
                        </tr>');
                }
            }
        } else {
            $('a.load-more-link').addClass('hide');
        }
    });
}
<?php
}
?>
</script>
<?php
} else {
	if (isset($_SESSION['refreshtoken'])) {
		$veeam->refreshToken($_SESSION['refreshtoken']);
		
		$_SESSION['refreshtoken'] = $veeam->getRefreshToken();
        $_SESSION['token'] = $veeam->getToken();
	} else {
		unset($_SESSION);
		session_destroy();
		?>
		<script>
		Swal.fire({
			type: 'info',
			title: 'Session expired',
			text: 'Your session has expired and requires you to login again.'
		}).then(function(e) {
			window.location.href = '/index.php';
		});
		</script>
		<?php
	}
}
?>
</body>
</html>