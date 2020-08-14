<?php
error_reporting(E_ALL || E_STRICT);
set_time_limit(0);
session_start();

require_once('config.php');
require_once('veeam.class.php');

if (empty($host) || empty($port) || empty($version)) {
    exit('Please modify the configuration file first and configure the Veeam Backup for Microsoft Office 365 host, port and RESTful API version settings.');
}

if (!preg_match('/v[3-4]/', $version)) {
	exit('Invalid API version found. Please modify the configuration file and configure the Veeam Backup for Microsoft Office 365 RESTful API version setting. Only version 3 and 4 are supported.');
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
    <link rel="stylesheet" type="text/css" href="css/bootstrap.min.css" />
    <link rel="stylesheet" type="text/css" href="css/flatpickr.min.css">
    <link rel="stylesheet" type="text/css" href="css/fontawesome.min.css" />
    <link rel="stylesheet" type="text/css" href="css/style.css" />
	<link rel="stylesheet" type="text/css" href="css/sweetalert2.min.css" />
	<link rel="stylesheet" type="text/css" href="css/jstree.min.css" />
    <script src="js/jquery.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <script src="js/fontawesome.min.js"></script>
    <script src="js/filesize.min.js"></script>
	<script src="js/flatpickr.js"></script>
	<script src="js/jquery.redirect.js"></script>
    <script src="js/moment.min.js"></script>
	<script src="js/sweetalert2.all.min.js"></script>
	<script src="js/jstree.min.js"></script>	
</head>
<body>
<?php
if (isset($_SESSION['token'])) {
	$veeam = new VBO($host, $port, $version);
    $veeam->setToken($_SESSION['token']);
	
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

			echo '<ul id="ul-onedrive-users">';
			
			if ($check === false && strtolower($administrator) == 'yes') {
				$oid = $_GET['oid'];
				$org = $veeam->getOrganizations();
				$menu = false;
				
				for ($i = 0; $i < count($org); $i++) {
					if (isset($oid) && !empty($oid) && $oid == $org[$i]['id']) {
						echo '<li class="active"><a href="onedrive/' . $org[$i]['id'] . '">' . $org[$i]['name'] . '</a></li>';
					} else {
						echo '<li><a href="onedrive/' . $org[$i]['id'] . '">' . $org[$i]['name'] . '</a></li>';
					}
				}
			} else {
				$org = $veeam->getOrganization();
				$oid = $org['id'];
				$menu = true;
				
				echo '<li class="active"><a href="onedrive">' . $org['name'] . '</a></li>';
			}
			
			echo '</ul>';
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
			if (isset($oid) || $menu) {
				if ($check === false && strtolower($administrator) == 'yes') {
					$org = $veeam->getOrganizationByID($oid);
				}
			?>
			<div class="row marginexplore">
				<div class="col-sm-2">
					<div class="input-group flatpickr" data-wrap="true" data-clickOpens="false">
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
				<div class="col-sm-10">
					<button class="btn btn-default btn-secondary btn-start-restore" title="Start Restore" <?php if (isset($_GET['oid'])) { echo 'data-oid="' . $_GET['oid'] . '"'; } ?> data-latest="false">Start Restore</button>
				
					<button class="btn btn-default btn-secondary btn-start-restore" title="Explore last backup (<?php echo date('d/m/Y H:i T', strtotime($org['lastBackuptime'])); ?>)" <?php if (isset($_GET['oid'])) { echo 'data-oid="' . $_GET['oid'] . '"'; } ?> data-pit="<?php echo date('Y.m.d H:i', strtotime($org['lastBackuptime'])); ?>" data-latest="true">Explore last backup</button>
				</div>
			</div>
			<?php
			}
			
			if (!isset($_SESSION['rid'])) { /* No restore session is running */
				if (isset($oid) && !empty($oid)) {
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
					
					if (count($users['results']) != '0') {
						$repousersarray = array();
						
						for ($i = 0; $i < count($repo); $i++) {
							$repoid = end(explode('/', $repo[$i]['_links']['backupRepository']['href']));

							for ($j = 0; $j < count($users['results']); $j++) {
								$combinedid = $users['results'][$j]['backedUpOrganizationId'] . $users['results'][$j]['id'];
								$userdata = $veeam->getUserData($repoid, $combinedid);
								
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
						
						$usersorted = array_values(array_column($repousersarray , null, 'name'));
					}
					
					if (count($usersorted) != '0') {
					?>
					<div class="alert alert-info">The following is an overview with all the backed up OneDrive accounts within the organization.</div>
					<table class="table table-bordered table-padding table-striped">
						<thead>
							<tr>
								<th>Account</th>
								<th>Last backup</th>
								<th>Objects in backup</th>
							</tr>
						</thead>
						<tbody>
						<?php
						for ($i = 0; $i < count($usersorted); $i++) {
							$licinfo = array_search($usersorted[$i]['id'], array_column($usersarray, 'id'));
							
							echo '<tr>';
							echo '<td>' . $usersorted[$i]['name'] . '</td>';
							echo '<td>' . date('d/m/Y H:i T', strtotime($usersarray[$licinfo]['lastBackupDate'])) . '</td>';
							echo '<td>';
							
							if ($usersorted[$i]['isOneDriveBackedUp']) {
								echo '<i class="fa fa-cloud fa-2x" style="color:green" title="OneDrive for Business"></i> ';
							} else {
								echo '<i class="fa fa-cloud fa-2x" style="color:red" title="OneDrive for Business"></i> ';
							}
							
							echo '</td>';
							echo '</tr>';
						}
						?>
						</tbody>
					</table>
					<?php
					} else {
						if ($check === false && strtolower($administrator) == 'yes') {
							echo '<p>No users found for this organization.</p>';
						} else {
							echo '<p>Select a point in time and start the restore.</p>';
						}
					}
				} else {
					if ($check === false && strtolower($administrator) == 'yes') {
						echo '<p>Select an organization to start a restore session.</p>';
					} else {
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
					<div class="row">
						<div class="col-sm-2 text-center">
							<div class="btn-group dropdown">
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
						<div class="col-sm-10">
							<input class="form-control search" id="search-onedrive" placeholder="Filter by item..." />
						</div>
					</div>
					<div class="row">
						<div class="col-sm-2 zeroPadding">
							<table class="table table-bordered table-padding table-striped" id="table-onedrive-folders">
								<thead>
									<tr>
										<th class="text-center"><strong>Folder Browser</strong></th>
									</tr>
								</thead>
								<tbody>
									<tr>
										<td>
											<input type="text" class="form-control search" id="jstree_q" placeholder="Search a folder...">
											<div id="jstree">
												<ul>
													<li data-folderid="<?php echo $cid; ?>" data-jstree='{ "opened" : true, "selected": true }'>
														<?php echo $list["name"]; ?>
														<ul>
														<?php
														for ($i = 0; $i < count($folders['results']); $i++) {
															echo '<li data-folderid="'.$folders['results'][$i]['id'].'"  data-jstree=\'{ "opened" : true }\'>'.$folders['results'][$i]['name'].'</li>';
														}
														?>
														</ul>
													</li>
												</ul>
											</div>
											<script>
											$(function () {
												$('#jstree').jstree({ 
													core: {
													  check_callback: true,
													  dblclick_toggle: false
													},
													'plugins': [ 'search', 'sort' ]
												});
												
												var to = false;
												
												$('#jstree_q').keyup(function (e) {
													if (to) { 
														clearTimeout(to); 
													}
													
													to = setTimeout(function (e) {
														var v = $('#jstree_q').val();
														
														$('#jstree').jstree(true).search(v);
													}, 250);
												});
												
												$('#jstree').on('activate_node.jstree', function (e, data) {
													if (data == undefined || data.node == undefined || data.node.id == undefined || data.node.data.folderid == undefined)
														return;

													var folderid = data.node.data.folderid;
													var parent = data.node.id;
													
													loadFolderItems(folderid, parent);
												});
											});
											</script>
										</td>
									</tr>
								</tbody>
							</table>
						</div>
						<div class="col-sm-10 zeroPadding">
							<div class="wrapper"><div class="loader hide" id="loader"></div></div>
							<table class="table table-bordered table-padding table-striped" id="table-onedrive-items">
								<thead>
									<tr>
										<th class="text-center"><input type="checkbox" id="chk-all" title="Select all"></th>
										<th><strong>Name</strong></th>
										<th><strong>Size</strong></th>
										<th><strong>Modification date</strong></th>
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
										<td><i class="far fa-folder"></i> <a href="javascript:void(0);" onclick="loadFolderItems('<?php echo $folders['results'][$i]['id']; ?>');"><?php echo $folders['results'][$i]['name']; ?></a></td>
										<td>-</td>
										<td><?php echo date('d/m/Y H:i', strtotime($folders['results'][$i]['modificationTime'])) . ' (by ' . $folders['results'][$i]['modifiedBy'] . ')'; ?></td>
										<td>-</td>
										<td class="text-center">
											<div class="btn-group dropdown">
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
										<td><i class="far fa-file"></i> <?php echo $documents['results'][$i]['name']; ?></td>
										<td><script>document.write(filesize(<?php echo $documents['results'][$i]['sizeBytes']; ?>, {round: 2}));</script></td>
										<td><?php echo date('d/m/Y H:i', strtotime($documents['results'][$i]['modificationTime'])) . ' (by ' . $documents['results'][$i]['modifiedBy'] . ')'; ?></td>
										<td><?php echo $documents['results'][$i]['version']; ?></td>
										<td class="text-center">
											<div class="btn-group dropdown">
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
								if (count($documents['results']) == '30') {
								?>
									<a class="btn btn-default load-more-link" data-folderid="null" data-userid="<?php echo $uid; ?>" data-offset="<?php echo count($documents['results'])+1; ?>" href="<?php echo $_SERVER['REQUEST_URI']; ?>#">Load more items</a>
								<?php
								} else {
								?>
									<a class="btn btn-default hide load-more-link" data-folderid="null" data-userid="<?php echo $uid; ?>" data-offset="<?php echo count($documents['results'])+1; ?>" href="<?php echo $_SERVER['REQUEST_URI']; ?>#">Load more items</a>
								<?php
								}
								?>
							</div>
						</div>
					</div>
					<?php
					} else {
						echo '<p>No items available for this account.</p>';
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
										<div class="btn-group dropdown">
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

/* Onedrive Restore Buttons */
$('.btn-start-restore').click(function(e) {
    if (typeof $(this).data('jid') !== 'undefined') {
        var jid = $(this).data('jid');
    }

    if (typeof $(this).data('oid') !== 'undefined') {
        var oid = $(this).data('oid');
    } else {
        var oid = 'tenant';
	}
	
	if ($(this).data('latest')) {
		var pit = $(this).data('pit');
	} else {
		if (!document.getElementById('pit-date').value) {
			$('#pit-date').addClass('errorClass');
			
			Swal.fire({
				type: 'info',
				title: 'No date selected',
				text: 'Please select a date first before starting the restore or use the \"explore last backup\" button.'
			})
			
			return;
		} else {
			var pit = $('#pit-date').val();
			
			$('#pit-date').removeClass('errorClass');
		}
	}

    var json = '{ "explore": { "datetime": "' + pit + '", "type": "veod", "ShowAllVersions": "true", "ShowDeleted": "true" } }';

    $(':button').prop('disabled', true);

    $.post('veeam.php', {'action' : 'startrestore', 'json' : json, 'id' : oid}).done(function(data) {
        if (data.match(/([a-zA-Z0-9]{8})-([a-zA-Z0-9]{4})-([a-zA-Z0-9]{4})-([a-zA-Z0-9]{4})-([a-zA-Z0-9]{12})/g)) {
            e.preventDefault();

			Swal.fire({
				type: 'success',
				title: 'Session started',
				text: 'Restore session has been started and you can now perform restores.'
			}).then(function(e) {
				window.location.href = 'onedrive';
			});
        } else {
			Swal.fire({
				type: 'error',
				title: 'Error starting restore session',
				text: '' + data
			})
			
            $(':button').prop('disabled', false);
        }
    });
});
$('.btn-stop-restore').click(function(e) {
    var rid = '<?php echo $rid; ?>';

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
		confirmButtonText: 'Stop',
		cancelButtonText: 'Cancel',
	}).then((result) => {
		if (result.value) {
			$.post('veeam.php', {'action' : 'stoprestore', 'id' : rid}).done(function(data) {
				swalWithBootstrapButtons.fire({
					type: 'success', 
					title: 'Restore session was stopped',
					text: 'The restore session was stopped successfully.',
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
$('#search-onedrive').keyup(function(e) {
    var searchText = $(this).val().toLowerCase();
    
    $.each($('#table-onedrive-items tbody tr'), function(e) {
        if ($(this).text().toLowerCase().indexOf(searchText) === -1) {
           $(this).hide();
        } else {
           $(this).show();
        }
    });
});

$('ul#ul-onedrive-users li').click(function(e) {
    $(this).parent().find('li.active').removeClass('active');
    $(this).addClass('active');
});

/* Load more link */
$('.load-more-link').click(function(e) {
    var folderid = $(this).data('folderid');
    var userid = $(this).data('userid');
    var offset = $(this).data('offset');
    var rid = '<?php echo $rid; ?>';

    loadItems(folderid, userid, offset);
});

/* Export to file */
$('.download-file').click(function(e) {
    var filetype = $(this).data('filetype');
	var itemid = $(this).data('itemid');
    var filename = $(this).data('itemname');
    var userid = $(this).data('userid');
    var rid = '<?php echo $rid; ?>';
	var json = '{ "save": { "asZip": "false" } }';
	
	Swal.fire({
		type: 'info',
		title: 'Download is starting',
		text: 'Export in progress and your download will start soon.'
	})

	$.post('veeam.php', {'action' : 'exportonedriveitem', 'itemid' : itemid, 'userid' : userid, 'rid' : rid, 'json' : json, 'type' : filetype}).done(function(data) {
		e.preventDefault();

		if (data) {
			$.redirect('download.php', {ext : 'plain', file : data, name : filename}, 'POST');
			
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

/* Export to ZIP file */
$('.download-zip').click(function(e) {
	var itemid = $(this).data('itemid');
    var filename = $(this).data('itemname');
    var userid = $(this).data('userid');
    var rid = '<?php echo $rid; ?>';
	var type = $(this).data('type');

	Swal.fire({
		type: 'info',
		title: 'Download is starting',
		text: 'Export in progress and your download will start soon.'
	})
	
	if (type == 'multiple') { /* Multiple items export */
		var act = 'exportmultipleonedriveitems';
		var filetype = 'documents';
		var ids = '';
		var filename = 'exported-onedriveitems-' + $(this).data('itemname');
		
		if ($("input[name='checkbox-onedrive']:checked").length === 0) { /* Error handling for multiple export button */
			Swal.close();
			
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
				"asZip": "true", \
				"Documents": [ \
				  ' + ids + ' \
				] \
			} \
		}';
	} else {
		if (type == 'single') {	/* Single item export */
			var act = 'exportonedriveitem';
			var filename = $(this).data('itemname');
		} else { /* Full OneDrive export */
			var act = 'exportonedrive';
			var filename = 'onedrive-' + $(this).data('itemname'); /* onedrive-username */
		}
		
		var filetype = $(this).data('filetype');
		var json = '{ "save": { "asZip": "true" } }';
	}

	$.post('veeam.php', {'action' : act, 'itemid' : itemid, 'userid' : userid, 'rid' : rid, 'json' : json, 'type' : filetype}).done(function(data) {
		e.preventDefault();

		if (data && data != '500') {
			$.redirect('download.php', {ext : 'zip', file : data, name : filename}, 'POST');
			
			Swal.close();
		} else {
			Swal.fire({
				type: 'error',
				title: 'Export failed',
				text: '' + data
			})
			
			return;
		}
	});
});

/* Restore to original location */
$('.restore-original').click(function(e) {
	var filetype = $(this).data('filetype');
	var itemid = $(this).data('itemid');
    var userid = $(this).data('userid');
    var rid = '<?php echo $rid; ?>';
	var type = $(this).data('type');
	
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
			'<form method="POST">' +
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
			
			var restoredata = Object.values(document.getElementsByClassName('restoredata'));
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
					$('#restore-original-action').val(),
				 ]);
			});
		},
	}).then(function(result) {
		if (result.value) {
			var user = $('#restore-original-user').val();
			var pass = $('#restore-original-pass').val();
			var restoreaction = $('#restore-original-action').val();
			
			Swal.fire({
				type: 'info',
				title: 'Item restore in progress',
				text: 'Restore in progress...'
			})
			
			if (type == 'multiple') { /* Multiple items restore */
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
				if (type == 'single') { /* Single item restore */
					var act = 'restoreonedriveitem';
				} else if (type == 'full') { /* Full OneDrive restore */
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

			$.post('veeam.php', {'action' : act, 'itemid' : itemid, 'userid' : userid, 'rid' : rid, 'json' : json, 'type' : filetype}).done(function(data) {
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

function disableTree() {
  $('#jstree li.jstree-node').each(function(e) {
    $('#jstree').jstree('disable_node', this.id)
  })
  
  $('#jstree i.jstree-ocl').off('click.block').on('click.block', function(e) {
    return false;
  });
}  
function enableTree() {
  $('#jstree li.jstree-node').each(function(e) {
    $('#jstree').jstree('enable_node', this.id)
  });
  
  $('#jstree i.jstree-ocl').off('click.block');
}

/* OneDrive functions */
function fillTableDocuments(response, userid) {
    if (response.results.length !== 0) {
        for (var i = 0; i < response.results.length; i++) {
			var size = filesize(response.results[i].sizeBytes, {round: 2});
			
            $('#table-onedrive-items tbody').append('<tr> \
				<td class="text-center"><input type="checkbox" name="checkbox-onedrive" value="' + response.results[i].id + '"></td> \
                <td><i class="far fa-file"></i> ' + response.results[i].name + '</td> \
                <td>' + size + '</td> \
				<td>' + moment(response.results[i].modificationTime).format('DD/MM/YYYY HH:mm') + ' (by ' + response.results[i].modifiedBy + ')</td> \
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

function fillTableFolders(response, folderid, userid) {
    if (response.results.length !== 0) {
        for (var i = 0; i < response.results.length; i++) {
            $('#table-onedrive-items tbody').append('<tr> \
				<td></td> \
                 <td><i class="far fa-folder"></i> <a href="javascript:void(0);" onclick="loadFolderItems(\'' + response.results[i].id + '\');">' + response.results[i].name + '</a></td> \
                <td>-</td> \
				<td>' + moment(response.results[i].modificationTime).format('DD/MM/YYYY HH:mm') + ' (by ' + response.results[i].modifiedBy + ')</td> \
                <td>-</td> \
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

function loadFolderItems(folderid, parent) {
	if (arguments.length == 1) {
		parent = null;
	}
	
    var responsedocuments, responsefolders;
	var rid = '<?php echo $rid; ?>';
	var userid = '<?php echo $uid; ?>';
	
	disableTree();
	
	$('#table-onedrive-items tbody').empty();
	$('#loader').removeClass('hide');
	$('a.load-more-link').addClass('hide');
	
    $.post('veeam.php', {'action' : 'getonedriveitemsbyfolder', 'folderid' : folderid, 'rid' : rid, 'userid' : userid, 'type' : 'folders'}).done(function(data) {
        responsefolders = JSON.parse(data);

		if (parent !== null) {
			if (responsefolders.results.length !== 0) {
				var responsefolderid, responsefoldername;
				var node = $('#jstree').jstree('get_selected');
				var children = $('#jstree').jstree('get_children_dom', node);

				if (children.length === 0) {
					for (var i = 0; i < responsefolders.results.length; i++) {
						responsefolderid = responsefolders.results[i].id;
						responsefoldername = responsefolders.results[i].name;
						
						$('#jstree').jstree('create_node', parent, {data: {"folderid" : responsefolderid}, text: responsefoldername});
						
						$('#jstree').on('create_node.jstree', function (e, data) {
							$('#jstree').jstree('open_node', data.parent);
						});
					}
				} else {
					var childrenFolderidArray = [];
					var selectedNode, existingid;
					
					for (var j = 0; j < children.length; j++) {
						selectedNode = $('#jstree').jstree(true).get_node(children[j], true);
						existingid = selectedNode[0].dataset.folderid;
						
						childrenFolderidArray.push(existingid);
					}
					
					for (var i = 0; i < responsefolders.results.length; i++) {
						responsefolderid = responsefolders.results[i].id;
						responsefoldername = responsefolders.results[i].name;
						
						if (!childrenFolderidArray.push(responsefolderid)) {
							$('#jstree').jstree('create_node', parent, {data: {"folderid" : responsefolderid}, text: responsefoldername});
							
							$('#jstree').on('create_node.jstree', function (e, data) {
								$('#jstree').jstree('open_node', data.parent);
							});
						}
					}
				}
			}
		} else {
			var childrenFolderidArray = [];
			var children, treeid, treefolderid;
			var treedata = $('#jstree').jstree(true).get_json('#', {'flat': true});
			
			for (var i = 0; i < treedata.length; i++) {
				treeid = treedata[i]['id'];
				treefolderid = treedata[i]['data']['folderid'];
				
				if (treefolderid === folderid) {
					$('#jstree').jstree('deselect_all');
					$('#jstree').jstree('select_node', treeid);
					
					children = $('#jstree').jstree('get_children_dom', treeid);
					
					if (children.length === 0) {
						for (var x = 0; x < responsefolders.results.length; x++) {
							responsefolderid = responsefolders.results[x].id;
							responsefoldername = responsefolders.results[x].name;
							$('#jstree').jstree('create_node', treeid, {data: {"folderid" : responsefolderid}, text: responsefoldername});

							$('#jstree').on('create_node.jstree', function (e, data) {
								$('#jstree').jstree('open_node', data.parent);
							});
						}
					} else {
						for (var j = 0; j < children.length; j++) {
							selectedNode = $('#jstree').jstree(true).get_node(treeid, true);
							existingid = selectedNode[0].dataset.folderid;
							
							childrenFolderidArray.push(existingid);
						}
						
						for (var x = 0; x < responsefolders.results.length; x++) {
							responsefolderid = responsefolders.results[x].id;
							responsefoldername = responsefolders.results[x].name;
							
							if (!childrenFolderidArray.push(responsefolderid)) {
								$('#jstree').jstree('create_node', treeid, {data: {"folderid" : responsefolderid}, text: responsefoldername});
								console.log('createB');
								$('#jstree').on('create_node.jstree', function (e, data) {
									$('#jstree').jstree('open_node', data.parent);
								});
							}
						}
					}
				}
			}
		}
    });

	setTimeout(function(e) {
		$.post('veeam.php', {'action' : 'getonedriveitemsbyfolder', 'folderid' : folderid, 'rid' : rid, 'userid' : userid, 'type' : 'documents'}).done(function(data) {
			responsedocuments = JSON.parse(data);
		});
	}, 2000);

    setTimeout(function(e) {
		if ((typeof responsefolders !== 'undefined' && responsefolders.results.length === 0) && (typeof responsedocuments !== 'undefined' && responsedocuments.results.length === 0)) {
			$('#table-onedrive-items tbody').append('<tr><td class="text-center" colspan="6">No items available in this folder.</td></tr>');
		}
		
        if (typeof responsefolders !== 'undefined' && responsefolders.results.length !== 0) {
            fillTableFolders(responsefolders, folderid, userid);
        }

        if (typeof responsedocuments !== 'undefined' && responsedocuments.results.length !== 0) {
            fillTableDocuments(responsedocuments, userid);
        }

        if ((typeof responsefolders !== 'undefined' && responsefolders.results.length == '30') || (typeof responsedocuments !== 'undefined' && responsedocuments.results.length == '30')) {
            $('a.load-more-link').removeClass('hide');
            $('a.load-more-link').data('offset', 30);
            $('a.load-more-link').data('folderid', folderid);
        } else {
            $('a.load-more-link').addClass('hide');
        }
		
		$('#loader').addClass('hide');
		enableTree();
    }, 3000);
}

function loadItems(folderid, userid, offset) { /* Load additional items in folder */
    var responsedocuments, responsefolders;
	var rid = '<?php echo $rid; ?>';

    $.post('veeam.php', {'action' : 'getonedriveitems', 'folderid' : folderid, 'rid' : rid, 'userid' : userid, 'offset' : offset, 'type' : 'folders'}).done(function(data) {
        responsefolders = JSON.parse(data);
    });

    $.post('veeam.php', {'action' : 'getonedriveitems', 'folderid' : folderid, 'rid' : rid, 'userid' : userid, 'offset' : offset, 'type' : 'documents'}).done(function(data) {
        responsedocuments = JSON.parse(data);
    });

    setTimeout(function(e) {
        if (typeof responsefolders !== undefined) {
            fillTableFolders(responsefolders, folderid, userid);
        }
        
        if (typeof responsedocuments !== undefined) {
            fillTableDocuments(responsedocuments, userid);
        }

        if (responsefolders.results.length == '30' || responsedocuments.results.length == '30') {
            $('a.load-more-link').removeClass('hide');
            $('a.load-more-link').data('offset', offset + 30);
            $('a.load-more-link').data('folderid', folderid);
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
			text: 'Your session has expired and requires you to log in again.'
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