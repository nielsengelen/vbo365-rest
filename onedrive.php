<?php
error_reporting(E_ALL || E_STRICT);

require_once('config.php');
require_once('veeam.class.php');

session_start();

if (empty($host) || empty($port) || empty($version)) {
    exit('Please modify the configuration file first and configure the Veeam Backup for Microsoft Office 365 host, port and RESTful API version settings.');
}

if ($version != 'v2' && $version != 'v3') {
	exit('Invalid API version found. Please modify the configuration file and configure the Veeam Backup for Microsoft Office 365 RESTful API version setting. Supported versions are either v2 or v3. v1 is not supported.');
}

$veeam = new VBO($host, $port, $version);

if (isset($_SESSION['token'])) {
    $veeam->setToken($_SESSION['token']);
} 

if (isset($_SESSION['refreshtoken'])) {
    $veeam->refreshToken($_SESSION['refreshtoken']);
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
    <script src="js/veeam.js"></script>
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
	  <li><a href="exchange">Exchange</a></li>
	  <li class="active"><a href="onedrive">OneDrive</a></li>
	  <li><a href="sharepoint">SharePoint</a></li>
	</ul>
	<ul class="nav navbar-nav navbar-right">
	  <li><a href="#"><span class="fa fa-user"></span> Welcome <i><?php echo $user; ?></i> !</a></li>
	  <li id="logout"><a href="#"><span class="fa fa-sign-out-alt"></span> Logout</a></li>
	</ul>
</nav>
<div class="container-fluid">
    <link rel="stylesheet" href="css/onedrive.css" />
    <aside id="sidebar">
        <div class="logo-container"><i class="logo fa fa-cloud"></i></div>
        <div class="separator"></div>
        <menu class="menu-segment" id="menu">
		<?php
		if (!isset($_SESSION['rid'])) { /* No restore session is running */
			$check = filter_var($user, FILTER_VALIDATE_EMAIL);

			if ($check === false && strtolower($administrator) == 'yes') { /* We are an admin so we list all the organizations in the menu */
				$oid = $_GET['oid'];
				$org = $veeam->getOrganizations();
				
				echo '<ul id="ul-onedrive-users">';
				
				for ($i = 0; $i < count($org); $i++) {
					if (isset($oid) && !empty($oid) && ($oid == $org[$i]['id'])) {
						echo '<li class="active"><a href="onedrive/' . $org[$i]['id'] . '">' . $org[$i]['name'] . '</a></li>';
					} else {
						echo '<li><a href="onedrive/' . $org[$i]['id'] . '">' . $org[$i]['name'] . '</a></li>';
					}
				}
				
				echo '</ul>';
			} else {
				$org = $veeam->getOrganization();
				?>
				<button class="btn btn-default btn-secondary btn-start-restore" title="Start Restore">Start Restore</button><br /><br />
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
				<?php
			}
		} else { /* Restore session is running */
			$rid = $_SESSION['rid'];

			if (strcmp($_SESSION['rtype'], 'veod') === 0) {
				$uid = $_GET['uid'];
				$content = array();
				$org = $veeam->getOrganizationID($rid);
				$users = $veeam->getOneDrives($rid);

				if ($users == '500') { /* Restore session has expired or was killed */
					unset($_SESSION['rid']);
					?>
					<script>
					Swal.fire({
						type: 'info',
						title: 'Restore session expired',
						text: 'Your restore session has expired.'
					}).then(function(e) {
						window.location.href = '/onedrive';
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
					echo '<ul id="ul-onedrive-users">';

					foreach ($content as $key => $value) {
						if (isset($uid) && !empty($uid) && ($uid == $value['id'])) {
							echo '<li class="active"><a href="onedrive/' . $org['id'] . '/' . $value['id'] . '">' . $value['name'] . '</a></li>';
						} else {
							echo '<li><a href="onedrive/' . $org['id'] . '/' . $value['id'] . '">' . $value['name'] . '</a></li>';
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
					text: 'Found another restore session running, please stop the session first if you want to restore OneDrive items.',
					<?php
					if (strcmp($_SESSION['rtype'], 'vesp') === 0) {
						echo "footer: '<a href=\"/sharepoint\">Go to restore session</a>'";
					} else {
						echo "footer: '<a href=\"/exchange\">Go to restore session</a>'";
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
		<h1>OneDrive</h1>
        <div class="onedrive-container">
        <?php    
		if (!isset($_SESSION['rid'])) { /* No restore session is running */
			if (isset($oid) && !empty($oid)) { /* We got an organization ID so list all users and their state */
				$org = $veeam->getOrganizationByID($oid);
		
				if ($version == 'v2') { /* This requires is a live query thus slower */
					$users = $veeam->getOrganizationUsers($oid);
				} else {
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
								
								/* Only store data when the OneDrive data is backed up */
								if (!is_null($userdata) && $userdata['isOneDriveBackedUp']) {
									array_push($repousersarray, array(
											'id' => $userdata['accountId'], 
											'email' => $userdata['email'],
											'name' => $userdata['displayName'],
											'isOneDriveBackedUp' => $userdata['isOneDriveBackedUp']
									));
								}
							}
						}
						
						$usersort = array_values(array_column($repousersarray , null, 'name')); /* Sort the array and make sure every value is unique */
					}
				}
				
				if (($version == 'v2' && count($users['results']) != '0') || (count($usersort) != '0')) {
				?>
				<div class="row">
				<div class="col-sm-2 text-left marginexplore">
					<button class="btn btn-default btn-secondary btn-start-restore" title="Explore last backup (<?php echo date('d/m/Y H:i T', strtotime($org['lastBackuptime'])); ?>)" data-oid="<?php echo $oid; ?>" data-pit="<?php echo date('Y.m.d H:i', strtotime($org['lastBackuptime'])); ?>" data-latest="true">Explore last backup</button>
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
					<button class="btn btn-default btn-secondary btn-start-restore" title="Start Restore" data-oid="<?php echo $oid; ?>" data-latest="false">Start Restore</button>
				</div>
				</div>
				<?php
				}
			
				/* v2 shows all accounts with their backup status */
				if ($version == 'v2') {
					if (count($users['results']) != '0') {
						?>
						<div class="alert alert-info">The following is an overview on all accounts within the organization with their backup status.</div>
						<table class="table table-bordered table-padding table-striped">
							<thead>
								<tr>
									<th>Account</th>
									<th>Backed up</th>
								</tr>
							</thead>
							<tbody>
							<?php
							for ($i = 0; $i < count($users['results']); $i++) {
								echo '<tr>';
								echo '<td>' . $users['results'][$i]['name'] . '</td>';
								echo '<td>'; 
								if ($users['results'][$i]['isBackedUp'] == 'true') { 
									echo '<span class="label label-success">Yes</span>'; 
								} else { 
									echo '<span class="label label-danger">No</span>';
								}
								echo '</td>';
								echo '</tr>';
							}
							?>
							</tbody>
						</table>
					<?php
					} else { /* No users available for the organization ID */
						echo '<p>No users found for this organization.</p>';
					}
				} else { /* v3 (or higher) shows accounts by backed up objects  */
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
								echo '<td>' . $usersort[$i]['name'] . '</td>';
								echo '<td>';
								if ($usersort[$i]['isOneDriveBackedUp']) {
									echo '<i class="fa fa-cloud fa-2x" style="color:green" title="OneDrive for Business"></i> ';
								} else {
									echo '<i class="fa fa-cloud fa-2x" style="color:red" title="OneDrive for Business"></i> ';
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
						echo '<p>No users found for this organization.</p>';
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
				$owner = $veeam->getOneDriveID($rid, $uid);
				$folders = $veeam->getOneDriveTree($rid, $uid);
				$documents = $veeam->getOneDriveTree($rid, $uid, 'documents');
				
				if ((count($folders['results']) != '0') || (count($documents['results']) != '0')) {
				?>
				<div class="col-sm-2 text-left">
					<div class="btn-group dropdown"> <!-- Multiple restore dropdown -->
						<button class="btn btn-default dropdown-toggle form-control" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">Restore selected <span class="caret"></span></button>
						<ul class="dropdown-menu dropdown-menu-right">
						  <li class="dropdown-header">Download as</li>
						  <li><a class="dropdown-link download-zip" data-itemid="multipleexport" data-itemname="<?php echo $owner['name']; ?>" data-type="multiple" data-userid="<?php echo $uid; ?>" href="<?php echo $_SERVER['REQUEST_URI']; ?>#"><i class="fa fa-download"></i> ZIP file</a></li>
						  <li class="divider"></li>
						  <li class="dropdown-header">Restore to</li>
						  <li><a class="dropdown-link restore-original" data-itemid="multiplerestore" data-type="multiple" data-userid="<?php echo $uid; ?>" href="<?php echo $_SERVER['REQUEST_URI']; ?>#"><i class="fa fa-upload"></i> Original location</a></li>
						</ul>
					</div>
				</div>
				<div class="col-sm-2">
					<select class="form-control padding" id="onedrive-nav">
						<option disabled selected>-- Jump to folder --</option>
						<?php
						for ($i = 0; $i < count($folders['results']); $i++) {
						?>
							<option data-folderid="<?php echo $folders['results'][$i]['id']; ?>" data-userid="<?php echo $uid; ?>"><?php echo $folders['results'][$i]['name']; ?></option>
						<?php
						}
						?>
					</select>
				</div>
				<div class="col-sm-8">
					<input class="form-control search" id="search-onedrive" placeholder="Filter by item..." />
				</div>
				<table class="table table-bordered table-padding table-striped" id="table-onedrive-items">
					<thead>
						<tr>
							<th class="text-center"><input type="checkbox" id="chk-all" title="Select all"></th>
							<th><strong>Name</strong></th>
							<th><strong>Size</strong></th>
							<th><strong>Version</strong></th>
							<th class="text-center"><strong>Options</strong></th>
						</tr>
					</thead>
					<tbody>
					<?php
					for ($i = 0; $i < count($folders['results']); $i++) { /* Show folders first if available */
					?>
						<tr>
							<td></td>
							<td>
							<i class="far fa-folder"></i> <a class="onedrive-folder" data-folderid="<?php echo $folders['results'][$i]['id']; ?>" data-parentid="index" data-userid="<?php echo $uid; ?>" href="onedrive/<?php echo $org['id']; ?>/<?php echo $uid; ?>#"><?php echo $folders['results'][$i]['name']; ?></a><br />
							<em>Last modified: <?php echo date('d/m/Y H:i', strtotime($folders['results'][$i]['modificationTime'])) . ' (by ' . $folders['results'][$i]['modifiedBy'] . ')'; ?></em>
							</td>
							<td></td>
							<td><?php echo $folders['results'][$i]['version']; ?></td>
							<td class="text-center">
								<div class="btn-group dropdown"> <!-- Single restore dropdown -->
									<button class="btn btn-default dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">Options <span class="caret"></span></button>
									<ul class="dropdown-menu dropdown-menu-right">
									  <li class="dropdown-header">Download as</li>
									  <li><a class="dropdown-link download-zip" data-itemid="<?php echo $folders['results'][$i]['id']; ?>" data-itemname="<?php echo $folders['results'][$i]['name']; ?>" data-filetype="folders" data-type="single" data-userid="<?php echo $uid; ?>" href="<?php echo $_SERVER['REQUEST_URI']; ?>#"><i class="fa fa-download"></i> ZIP file</a></li>
									  <li class="divider"></li>
									  <li class="dropdown-header">Restore to</li>
									  <li><a class="dropdown-link restore-original" data-itemid="<?php echo $folders['results'][$i]['id']; ?>" data-filetype="folders" data-type="single" data-userid="<?php echo $uid; ?>" href="<?php echo $_SERVER['REQUEST_URI']; ?>#"><i class="fa fa-upload"></i> Original location</a></li>
									</ul>
								</div>
							</td>
						</tr>
					<?php
					}

					for ($i = 0; $i < count($documents['results']); $i++) { /* Show documents next if available */
					?>
						<tr>
							<td class="text-center"><input type="checkbox" name="checkbox-onedrive" value="<?php echo $documents['results'][$i]['id']; ?>"></td>
							<td>
							<i class="far fa-file"></i> <?php echo $documents['results'][$i]['name']; ?><br />
							<em>Last modified: <?php echo date('d/m/Y H:i', strtotime($documents['results'][$i]['modificationTime'])) . ' (by ' . $documents['results'][$i]['modifiedBy'] . ')'; ?></em>
							</td>
							<td><script>document.write(filesize(<?php echo $documents['results'][$i]['sizeBytes']; ?>, {round: 2}));</script></td>
							<td><?php echo $documents['results'][$i]['version']; ?></td>
							<td class="text-center">
								<div class="btn-group dropdown"> <!-- Single restore dropdown -->
									<button class="btn btn-default dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">Options <span class="caret"></span></button>
									<ul class="dropdown-menu dropdown-menu-right">
									  <li class="dropdown-header">Download as</li>
									  <li><a class="dropdown-link download-file" data-itemid="<?php echo $documents['results'][$i]['id']; ?>" data-itemname="<?php echo $documents['results'][$i]['name']; ?>" data-filetype="documents" data-type="single" data-userid="<?php echo $uid; ?>" href="<?php echo $_SERVER['REQUEST_URI']; ?>#"><i class="fa fa-download"></i> Plain file</a></li>
									  <li><a class="dropdown-link download-zip" data-itemid="<?php echo $documents['results'][$i]['id']; ?>" data-itemname="<?php echo $documents['results'][$i]['name']; ?>" data-filetype="documents" data-type="single" data-userid="<?php echo $uid; ?>" href="<?php echo $_SERVER['REQUEST_URI']; ?>#"><i class="fa fa-download"></i> ZIP file</a></li>
									  <li class="divider"></li>
									  <li class="dropdown-header">Restore to</li>
									  <li><a class="dropdown-link restore-original" data-itemid="<?php echo $documents['results'][$i]['id']; ?>" data-filetype="documents" data-type="single" data-userid="<?php echo $uid; ?>" href="<?php echo $_SERVER['REQUEST_URI']; ?>#"><i class="fa fa-upload"></i> Original location</a></li>
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
					if (count($documents['results']) == '30') { /* If we have 30 items from the first request, show message to load additional items */
					?>
						<a class="btn btn-default load-more-link" data-folderid="null" data-userid="<?php echo $uid; ?>" data-offset="<?php echo count($documents['results'])+1; ?>" href="<?php echo $_SERVER['REQUEST_URI']; ?>#">Load more items</a>
					<?php
					} else { /* Else hide the load more items message */
					?>
						<a class="btn btn-default hide load-more-link" data-folderid="null" data-userid="<?php echo $uid; ?>" data-offset="<?php echo count($documents['results'])+1; ?>" href="<?php echo $_SERVER['REQUEST_URI']; ?>#">Load more items</a>
					<?php
					}
					?>
				</div>
				<?php
				} else {
					echo '<p>No items available for this OneDrive account.</p>';
				}
			} else { /* List all accounts */
				?>				
				<table class="table table-bordered table-padding table-striped">
					<thead>
						<tr>
							<th>Name</th>
							<th class="text-center">Options</th>
						</tr>
					</thead>
					<tbody>
						<?php
						$onedrives = array();
						
						for ($i = 0; $i < count($users['results']); $i++) {
							array_push($onedrives, array('name'=> $users['results'][$i]['name'], 'id' => $users['results'][$i]['id']));
						}

						uasort($onedrives, function($a, $b) {
							return strcasecmp($a['name'], $b['name']);
						});
				
						foreach ($onedrives as $key => $value) {
						?>
							<tr>
								<td><a href="onedrive/<?php echo $org['id']; ?>/<?php echo $value['id']; ?>"><?php echo $value['name']; ?></a></td>
								<td class="text-center">
									<div class="btn-group dropdown"> <!-- Full restore dropdown -->
										<button class="btn btn-default dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">Options <span class="caret"></span></button>
										<ul class="dropdown-menu dropdown-menu-right">
										  <li class="dropdown-header">Download as</li>
										  <li><a class="dropdown-link download-zip" data-itemid="<?php echo $value['name']; ?>" data-itemname="<?php echo $value['name']; ?>" data-type="full" data-userid="<?php echo $value['id']; ?>" href="<?php echo $_SERVER['REQUEST_URI']; ?>#"><i class="fa fa-download"></i> ZIP file</a></li>
										  <li class="divider"></li>
										  <li class="dropdown-header">Restore to</li>
										  <li><a class="dropdown-link restore-original" data-itemid="<?php echo $value['name']; ?>" data-type="full" data-userid="<?php echo $value['id']; ?>" href="<?php echo $_SERVER['REQUEST_URI']; ?>#"><i class="fa fa-upload"></i> Original location</a></li>
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

<script>
/* Onedrive Restore Buttons */
$(document).on('click', '.btn-start-restore', function(e) {
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

    var json = '{ "explore": { "datetime": "' + pit + '", "type": "veod", "ShowAllVersions": "true", "ShowDeleted": "true" } }'; /* JSON code to start the restore session */

    $(':button').prop('disabled', true); /* Disable all buttons to prevent double start */

    $.get('veeam.php', {'action' : 'startrestore', 'json' : json, 'id' : oid}).done(function(data) {
        if (data.match(/([a-zA-Z0-9]{8})-([a-zA-Z0-9]{4})-([a-zA-Z0-9]{4})-([a-zA-Z0-9]{4})-([a-zA-Z0-9]{12})/g)) {
            e.preventDefault();

			Swal.fire({
				type: 'success',
				title: 'Session started',
				text: 'Restore session has been started and you can now perform item restores.'
			}).then(function(e) {
				window.location.href = 'onedrive';
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
$(document).on('click', '.btn-stop-restore', function(e) {
    var rid = "<?php echo $rid; ?>"; /* Restore Session ID */

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
					window.location.href = 'onedrive';
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
/* Dropdown settings */
$(document).on("hide.bs.dropdown", ".dropdown", function(e) {
    $(e.target).find(">.dropdown-menu:first").slideUp();
});
$(document).on("show.bs.dropdown", ".dropdown", function(e) {
    $(e.target).find(">.dropdown-menu:first").slideDown();
});

/* Select all checkbox */
$(document).on("click", "#chk-all", function(e) {
    var table = $(e.target).closest("table");
    $("tr:visible :checkbox", table).prop("checked", this.checked);
});
/* Item search */
$("#search-onedrive").keyup(function(e) {
    var searchText = $(this).val().toLowerCase();
    /* Show only matching row, hide rest of them */
    $.each($("#table-onedrive-items tbody tr"), function(e) {
        if ($(this).text().toLowerCase().indexOf(searchText) === -1) {
           $(this).hide();
        } else {
           $(this).show();
        }
    });
});

/* Users and folder navigation content */
$("#onedrive-nav").change(function(e) {
    var folderid = $("#onedrive-nav option:selected").data("folderid");
    var userid = $("#onedrive-nav option:selected").data("userid");
    var offset = 0;
    var rid = "<?php echo $rid; ?>";
	
	$('#table-onedrive-items tbody').empty();
    loadItems(folderid, userid, rid, offset);
});
/* Export and restore options for restore buttons based upon specific action per button */
/* Export to plain file */
$(document).on("click", ".download-file", function(e) {
    var filetype = $(this).data("filetype");
	var itemid = $(this).data("itemid");
    var itemname = $(this).data("itemname");
    var userid = $(this).data("userid");
    var rid = "<?php echo $rid; ?>";
	var json = '{ "save" : null }';

	$.get("veeam.php", {"action" : "exportonedriveitem", "itemid" : itemid, "userid" : userid, "rid" : rid, "json" : json, "type" : filetype}).done(function(data) {
		e.preventDefault();

		if (data) {
			$.redirect("download.php", {ext : "plain", file : data, name : itemname}, "POST");
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
/* Export to ZIP file */
$(document).on("click", ".download-zip", function(e) {
	var itemid = $(this).data("itemid");
    var itemname = $(this).data("itemname");
    var userid = $(this).data("userid");
    var rid = "<?php echo $rid; ?>";
	var type = $(this).data("type");

	if (type == "multiple") { /* Multiple items export */
		var act = 'exportmultipleonedriveitems';
		var filetype = 'documents';
		var ids = '';
		var filename = 'exported-onedriveitems-' + $(this).data("itemname"); /* exported-onedriveitems-username */  
		
		if ($("input[name='checkbox-onedrive']:checked").length == 0) { /* Error handling for multiple export button */
			Swal.fire({
				type: 'error',
				title: 'Restore failed',
				text: 'No items have been selected.'
			})
			return;
		}

		$("input[name='checkbox-onedrive']:checked").each(function(e) {
			ids = ids + '{ "Id": "' + this.value + '" }, ';
		});
		
		var json = '{ \
			"save": { \
				"asZip" : "true", \
				"Documents": [ \
				  ' + ids + ' \
				] \
			} \
		}';
	} else {
		if (type == "single") {	/* Single item export */
			var act = 'exportonedriveitem';
			var filename = $(this).data("itemname");
		} else { /* Full OneDrive export */
			var act = 'exportonedrive';
			var filename = 'onedrive-' + $(this).data("itemname"); /* onedrive-username */
		}
		
		var filetype = $(this).data("filetype");
		var json = '{ "save" : { "asZip" : "true" } }';
	}

	$.get("veeam.php", {"action" : act, "itemid" : itemid, "userid" : userid, "rid" : rid, "json" : json, "type" : filetype}).done(function(data) {
		e.preventDefault();

		if (data && data != '500') {
			$.redirect("download.php", {ext : "zip", file : data, name : itemname}, "POST");
		} else {
			if (data == '500') {
				Swal.fire({
					type: 'error',
					title: 'Export failed',
					text: 'Selected document library is empty.'
				})
			} else {
				Swal.fire({
					type: 'error',
					title: 'Export failed',
					text: 'Export failed.'
				})
			}
			return;
		}
	});
 });
/* Restore to original location */
$(document).on("click", ".restore-original", function(e) {
	var filetype = $(this).data("filetype");
	var itemid = $(this).data("itemid");
    var userid = $(this).data("userid");
    var rid = "<?php echo $rid; ?>";
	var type = $(this).data("type");
	
	if (type == 'multiple' && $("input[name='checkbox-onedrive']:checked").length == 0) { /* Error handling for multiple restore button */
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
			'<div class="alert alert-warning" role="alert">Warning: this will restore the last version of the item.</div>' +
			'<label for="restore-original-user" class="col-sm-4 col-form-label text-right">Username:</label>' +
			'<div class="col-sm-8"><input type="text" class="form-control restoredata" id="restore-original-user" placeholder="user@example.onmicrosoft.com"></input></div>' +
			'</div>' +
			'<div class="form-group row">' +
			'<label for="restore-original-pass" class="col-sm-4 col-form-label text-right">Password:</label>' +
			'<div class="col-sm-8"><input type="password" class="form-control restoredata" id="restore-original-pass" placeholder="password"></input></div>' +
			'</div>' +
			'<div class="form-group row">' +
			'<label for="restore-original-action" class="col-sm-4 col-form-label text-right">If the file exists:</label>' +
			'<div class="col-sm-8"><select class="form-control restoredata" id="restore-original-action">' +
			'<option value="keep">Keep original file</option>' +
			'<option value="overwrite">Overwrite file</option>' +
			'</select></div>' +
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
					$("#restore-original-action").val(),
				 ]);
			});
		},
	}).then(function(result) {
		if (result.value) {
			var user = $("#restore-original-user").val();
			var pass = $("#restore-original-pass").val();
			var restoreaction = $("#restore-original-action").val();
			
			Swal.fire({
				type: 'info',
				title: 'Item restore in progress',
				text: 'Restore in progress...'
			})
			
			if (type == "multiple") { /* Multiple items restore */
				var act = 'restoremultipleonedriveitems';
				filetype = 'documents';
				var ids = '';
				
				$("input[name='checkbox-onedrive']:checked").each(function(e) {
					ids = ids + '{ "id": "' + this.value + '" }, ';
				});
				
				var json = '{ "restoretoOriginallocation": \
					{ "userName": "' + user + '", \
					  "userPassword": "' + pass + '", \
					  "DocumentVersion" : "last", \
					  "DocumentAction" : "' + restoreaction + '", \
					  "Documents": [ \
						' + ids + ' \
					  ], \
					} \
				}';
			} else {
				if (type == "single") { /* Single item restore */
					var act = 'restoreonedriveitem';
				} else if (type == "full") { /* Full OneDrive restore */
					var act = 'restoreonedrive';
				}
				
				var json = '{ "restoretoOriginallocation": \
					{ "userName": "' + user + '", \
					  "userPassword": "' + pass + '", \
					  "DocumentVersion" : "last", \
					  "DocumentAction" : "' + restoreaction + '" \
					} \
				}';
			}

			$.get("veeam.php", {"action" : act, "itemid" : itemid, "userid" : userid, "rid" : rid, "json" : json, "type" : filetype}).done(function(data) {
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

/* Folder browser */
$(document).on("click", ".onedrive-folder", function(e) {
    var folderid = $(this).data("folderid");
    var parentid = $(this).data("parentid");
    var userid = $(this).data("userid");
    var rid = "<?php echo $rid; ?>";
	
    loadFolderItems(folderid, parentid, rid, userid);
});
$(document).on("click", ".onedrive-folder-up", function(e) {
    var parentid = $(this).data("parentid");
    var userid = $(this).data("userid");
    var rid = "<?php echo $rid; ?>";

    if (parentid == "index") {
        window.location.href = window.location.href.split('#')[0];
        return false;
    } else {
        loadParentFolderItems(parentid, rid, userid);
    }
});

/* Load more link */
$(document).on("click", ".load-more-link", function(e) {
    var folderid = $(this).data("folderid");
    var userid = $(this).data("userid");
    var offset = $(this).data("offset");
    var rid = "<?php echo $rid; ?>";

    loadItems(folderid, userid, rid, offset);
});

/* OneDrive functions */
/*
 * @param response JSON data
 * @param userid User ID
 */
function fillTableDocuments(response, userid) {
    if (response.results.length != '0') {
        for (var i = 0; i < response.results.length; i++) {
            $('#table-onedrive-items tbody').append('<tr> \
				<td class="text-center"><input type="checkbox" name="checkbox-onedrive" value="' + response.results[i].id + '"></td> \
                <td><i class="far fa-file"></i> ' + response.results[i].name + '<br /><em>Last modified: ' + moment(response.results[i].modificationTime).format('DD/MM/YYYY HH:mm') + ' (by ' + response.results[i].modifiedBy + ')</em></td> \
                <td>' + filesize(response.results[i].sizeBytes, {round: 2}) + '</td> \
                <td>' + response.results[i].version + '</td> \
                <td class="text-center"> \
                <div class="btn-group dropdown"> \
                <button class="btn btn-default dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">Options <span class="caret"></span></button> \
                <ul class="dropdown-menu dropdown-menu-right"> \
                <li class="dropdown-header">Download as</li> \
                <li><a class="dropdown-link download-file" data-itemid="' + response.results[i].id + '" data-itemname="' + response.results[i].id + '" data-filetype="documents" data-type="single" data-userid="' + userid + '" href="' + window.location + '"><i class="fa fa-download"></i> Plain file</a></li> \
                <li><a class="dropdown-link download-zip" data-itemid="' + response.results[i].id + '" data-itemname="' + response.results[i].id + '" data-filetype="documents" data-type="single" data-userid="' + userid + '" href="' + window.location + '"><i class="fa fa-download"></i> ZIP file</a></li> \
                <li class="divider"></li> \
                <li class="dropdown-header">Restore to</li> \
                <li><a class="dropdown-link restore-original" data-itemid="' + response.results[i].id + '" data-filetype="documents" data-type="single" data-userid="' + userid + '" href="' + window.location + '"><i class="fa fa-upload"></i> Original location</a></li> \
                </ul> \
                </div> \
                </td> \
                </tr>');
        }
    }
}

/*
 * @param response JSON data
 * @param folderid Folder ID
 * @param userid User ID
 */
function fillTableFolders(response, folderid, userid) {
    if (response.results.length != '0') {
        for (var i = 0; i < response.results.length; i++) {
            $('#table-onedrive-items tbody').append('<tr> \
				<td></td> \
                <td><i class="far fa-folder"></i> <a class="onedrive-folder" data-folderid="' + response.results[i].id + '" data-parentid="' + folderid +'" data-userid="<?php echo $uid; ?>" href="'+ window.location +'">' + response.results[i].name + '</a><br /><em>Last modified: ' + moment(response.results[i].modificationTime).format('DD/MM/YYYY HH:mm') + ' (by ' + response.results[i].modifiedBy + ')</em></td> \
                <td></td> \
                <td></td> \
                <td class="text-center"> \
                <div class="btn-group dropdown"> \
                <button class="btn btn-default dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">Options <span class="caret"></span></button> \
                <ul class="dropdown-menu dropdown-menu-right"> \
                <li class="dropdown-header">Download as</li> \
                <li><a class="dropdown-link download-file" data-itemid="' + response.results[i].id + '" data-itemname="' + response.results[i].id + '" data-filetype="folders" data-type="single" data-userid="' + userid + '" href="' + window.location + '"><i class="fa fa-download"></i> Plain file</a></li> \
                <li><a class="dropdown-link download-zip" data-itemid="' + response.results[i].id + '" data-itemname="' + response.results[i].id + '" data-filetype="folders" data-type="single" data-userid="' + userid + '" href="' + window.location + '"><i class="fa fa-download"></i> ZIP file</a></li> \
                <li class="divider"></li> \
                <li class="dropdown-header">Restore to</li> \
                <li><a class="dropdown-link restore-original" data-itemid="' + response.results[i].id + '" data-filetype="folders" data-type="single" data-userid="' + userid + '" href="' + window.location + '"><i class="fa fa-upload"></i> Original location</a></li> \
                </ul> \
                </div> \
                </td> \
                </tr>');
        }
    }
}

/*
 * @param folderid Folder ID
 * @param parentid Parent Folder ID
 * @param rid Restore session ID
 * @param userid User ID
 */
function loadFolderItems(folderid, parentid, rid, userid) { /* Used for navigation to next folder */
    var responsedocuments, responsefolders;

    /* First we load the folders */
    $.get("veeam.php", {"action" : "getonedriveitemsbyfolder", "folderid" : folderid, "rid" : rid, "userid" : userid, "type" : "folders"}).done(function(data) {
        responsefolders = JSON.parse(data);
    });

    /* Second we load the documents */
    $.get("veeam.php", {"action" : "getonedriveitemsbyfolder", "folderid" : folderid, "rid" : rid, "userid" : userid, "type" : "documents"}).done(function(data) {
        responsedocuments = JSON.parse(data);
    });

    /* We delay the output to assure our call is performed */
    setTimeout(function(e) {
        $('#table-onedrive-items tbody').empty();
        $('#table-onedrive-items tbody').append('<tr><td colspan="5"><a class="onedrive-folder-up" data-parentid="' + parentid + '" data-userid="<?php echo $uid; ?>" href="' + window.location + '"><i class="fa fa-reply"></i> Parent directory</a></td></tr>');

        if (typeof responsefolders !== undefined) {
            fillTableFolders(responsefolders, folderid, userid);
        }

        if (typeof responsedocuments !== undefined) {
            fillTableDocuments(responsedocuments, userid);
        }

        if (responsefolders.results.length == '30' || responsedocuments.results.length == '30') {
            $('a.load-more-link').removeClass('hide');
            $('a.load-more-link').data('offset', 30); /* Update offset for loading more items */
            $('a.load-more-link').data('folderid', folderid); /* Update folder ID for loading more items */
        } else {
            $('a.load-more-link').addClass('hide');
        }
    }, 2000);
}

/*
 * @param parentid Parent Folder ID
 * @param rid Restore session ID
 * @param userid User ID
 */
function loadParentFolderItems(parentid, rid, userid) { /* Used for navigation to parent folder */
    var newparentid, parentdata, parenturl, responsedocuments, responsefolders;

    /* First we load the folders */
    $.get("veeam.php", {"action" : "getonedriveitemsbyfolder", "folderid" : parentid, "rid" : rid, "userid" : userid, "type" : "folders"}).done(function(data) {
        responsefolders = JSON.parse(data);
    });

    /* Second we load the documents */
    $.get("veeam.php", {"action" : "getonedriveitemsbyfolder", "folderid" : parentid, "rid" : rid, "userid" : userid, "type" : "documents"}).done(function(data) {
        responsedocuments = JSON.parse(data);
    });

    /* We delay the output to assure our call is performed */
    setTimeout(function(e) {
        if (typeof responsefolders !== undefined && responsefolders.results.length != '0') {
            parenturl = responsefolders.results[0]._links.parent.href;
            newparentid = parenturl.split("/").pop();

            $.get("veeam.php", {"action" : "getonedriveparentfolder", "folderid" : newparentid, "rid" : rid, "userid" : userid, "type" : "folders"}).done(function(data) {
                parentdata = JSON.parse(data);

                if (parentdata === undefined || parentdata._links.parent === undefined) {
                    newparentid = "index";
                } else {
                    parenturl = parentdata._links.parent.href;
                    newparentid = parenturl.split("/").pop();
                }
            });
        } else if (typeof responsedocuments !== undefined && responsedocuments.results.length != '0') {
            parenturl = responsedocuments.results[0]._links.parent.href;
            newparentid = parenturl.split("/").pop();

            $.get("veeam.php", {"action" : "getonedriveparentfolder", "folderid" : newparentid, "rid" : rid, "userid" : userid, "type" : "documents"}).done(function(data) {
                parentdata = JSON.parse(data);
                
                if (parentdata === undefined || parentdata._links.parent === undefined) {
                    newparentid = "index";
                } else {
                    parenturl = parentdata._links.parent.href;
                    newparentid = parenturl.split("/").pop();
                }
            });
        } else {
            return false;
        }

        setTimeout(function(e) {
            $('#table-onedrive-items tbody').empty();
            $('#table-onedrive-items tbody').append('<tr><td colspan="5"><a class="onedrive-folder-up" data-parentid="' + newparentid + '" data-userid="<?php echo $uid; ?>" href="' + window.location + '"><i class="fa fa-reply"></i> Parent directory</a></td></tr>');

            if (typeof responsefolders !== undefined) {
                fillTableFolders(responsefolders, parentid, userid);
            }

            if (typeof responsedocuments !== undefined) {
                fillTableDocuments(responsedocuments, userid);
            }

            if (responsefolders.results.length == '30' || responsedocuments.results.length == '30') {
                $('a.load-more-link').removeClass('hide');
                $('a.load-more-link').data('offset', 30); /* Update offset for loading more items */
                $('a.load-more-link').data('folderid', folderid); /* Update folder ID for loading more items */
            } else {
                $('a.load-more-link').addClass('hide');
            }
        }, 1000);
    }, 1000);
}

/*
 * @param userid User ID
 * @param rid Restore session ID
 * @param offset Offset
 */
function loadItems(folderid, userid, rid, offset) { /* Used for loading additional items in folder */
    var responsedocuments, responsefolders;

    $.get("veeam.php", {"action" : "getonedriveitems", "folderid" : folderid, "rid" : rid, "userid" : userid, "offset" : offset, "type" : "folders"}).done(function(data) {
        responsefolders = JSON.parse(data);
    });

    $.get("veeam.php", {"action" : "getonedriveitems", "folderid" : folderid, "rid" : rid, "userid" : userid, "offset" : offset, "type" : "documents"}).done(function(data) {
        responsedocuments = JSON.parse(data);
    });

    /* We delay the output to assure our call is performed */
    setTimeout(function(e) {
        if (typeof responsefolders !== undefined) {
            fillTableFolders(responsefolders, folderid, userid);
        }
        
        if (typeof responsedocuments !== undefined) {
            fillTableDocuments(responsedocuments, userid);
        }

        if (responsefolders.results.length == '30' || responsedocuments.results.length == '30') {
            $('a.load-more-link').removeClass('hide');
            $('a.load-more-link').data('offset', offset + 30); /* Update offset for loading more items */
            $('a.load-more-link').data('folderid', folderid); /* Update folder ID for loading more items */
        } else {
            $('a.load-more-link').addClass('hide');
        }
    }, 2000);
}
<?php
}
?>
</script>
<?php
} else {
    unset($_SESSION);
    session_destroy();
?>
<script>
Swal.fire({
	type: 'info',
	title: 'Session terminated',
	text: 'Your session has timed out and requires you to login again.'
}).then(function(e) {
	window.location.href = '/index.php';
});
</script>
<?php
}
?>
</body>
</html>