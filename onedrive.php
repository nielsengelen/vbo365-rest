<?php
session_start();
error_reporting(E_ALL || E_STRICT);
set_time_limit(0);

require_once('config.php');
require_once('veeam.class.php');

if (empty($host) || empty($port) || empty($version)) {
    exit('Modify the configuration file first and configure the Veeam Backup for Microsoft Office 365 host, port and RESTful API version settings.');
}

if (!preg_match('/v[4-5]/', $version)) {
	exit('Invalid API version found. Modify the configuration file and configure the Veeam Backup for Microsoft Office 365 RESTful API version setting. Only version 4 and 5 are supported.');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo $title; ?></title>
    <base href="/" />
    <link rel="shortcut icon" href="favicon.ico" />
    <link rel="stylesheet" type="text/css" href="css/bootstrap.min.css" />
    <link rel="stylesheet" type="text/css" href="css/flatpickr.min.css">
    <link rel="stylesheet" type="text/css" href="css/fontawesome.min.css" />
    <link rel="stylesheet" type="text/css" href="css/style.css" />
	<link rel="stylesheet" type="text/css" href="css/sweetalert2.min.css" />
	<link rel="stylesheet" type="text/css" href="css/jstree.min.css" />
	<link rel="stylesheet" type="text/css" href="css/onedrive.css" />
    <script src="js/jquery.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
	<script src="js/clipboard.min.js"></script>
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
if (file_exists('setup.php')) {
	?>
	<script>
	Swal.fire({
		icon: 'error',
		title: 'Error',
		allowOutsideClick: false,
		showConfirmButton: false,
		text: 'Setup file is still available within the installation folder. You must remove this file in order to continue'
	});
	</script>
	<?php
	die();
}

if (isset($_SESSION['token'])) {
	$veeam = new VBO($host, $port, $version);
    $veeam->setToken($_SESSION['token']);
	
    if (isset($_SESSION['user'])) {
		$user = $_SESSION['user'];
	} else {
		empty($user);
	}
	
	if (isset($_SESSION['authtype'])) $authtype = $_SESSION['authtype'];
	if (isset($_SESSION['applicationid'])) $applicationid = $_SESSION['applicationid'];
	if (!isset($limit)) $limit = 250;
	
	$check = filter_var($user, FILTER_VALIDATE_EMAIL);
?>
<nav class="navbar navbar-inverse navbar-static-top">
	<ul class="nav navbar-header">
	  <li><a class="navbar-brand navbar-logo" href="/"><img src="images/logo.svg" alt="Veeam Backup for Microsoft Office 365" class="logo"></a></li>
	</ul>
	<ul class="nav navbar-nav" id="nav">
	  <?php
	  if (!isset($_SESSION['rtype'])) {
	  ?>
	  <li><a href="exchange">Exchange</a></li>
	  <?php
	  }
	  ?>
	  <li class="active"><a href="onedrive">OneDrive</a></li>
	  <?php
	  if (!isset($_SESSION['rtype'])) {
	  ?>
	  <li><a href="sharepoint">SharePoint</a></li>
	  <?php
	    if ($version === 'v5') {
			echo '<li><a href="teams">Teams</a></li>';
		}
	  }
	  ?>
	</ul>
	<ul class="nav navbar-nav navbar-right">
	  <li><a href="#"><span class="fa fa-user"></span> Welcome <i><?php echo $user; ?></i> !</a></li>
	  <li id="logout"><a href="#"><span class="fa fa-sign-out-alt"></span> Logout</a></li>
	</ul>
</nav>
<div class="container-fluid">
    <div id="sidebar">
        <div class="logo-container"><i class="logo fa fa-cloud"></i></div>
        <div class="separator"></div>
        <menu class="menu-segment" id="menu">
		<?php
		if (!isset($_SESSION['rid'])) {
			echo '<ul id="ul-onedrive-users">';
			
			if (strtolower($authtype) !== 'mfa' && $check === false && strtolower($administrator) === 'yes') {
				if (isset($_GET['oid'])) $oid = $_GET['oid'];
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
				$restoretype = 'tenant';
				
				echo '<li class="active"><a href="onedrive">' . $org['name'] . '</a></li>';
			}
			
			echo '</ul>';
		} else {
			$rid = $_SESSION['rid'];		
			$session = $veeam->getRestoreSession($rid);
			
			if (preg_match('/stopped/', strtolower($session['state']))) {
				unset($_SESSION['rid']);
				unset($_SESSION['rtype']);
				?>
				<script>
				Swal.fire({
					icon: 'info',
					title: 'Restore session',
					text: 'The restore session has expired. In order to continue, start a new restore session'
				}).then(function(e) {
					window.location.href = '/sharepoint';
				});
				</script>
				<?php
			} else {
				if (isset($_GET['uid'])) $uid = $_GET['uid'];
				
				$content = array();
				$org = $veeam->getOrganizationID($rid);
				$oid = $org['id'];
				$users = $veeam->getOneDrives($rid);

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
						echo '<li class="active"><a href="onedrive/' . $oid . '/' . $value['id'] . '">' . $value['name'] . '</a></li>';
					} else {
						echo '<li><a href="onedrive/' . $oid . '/' . $value['id'] . '">' . $value['name'] . '</a></li>';
					}
				}

				echo '</ul>';
				
				if (count($users['results']) >= $limit) {
					echo '<div class="text-center">';
					echo '<a class="btn btn-default load-more-link load-more-accounts" data-org="' . $oid . '" data-offset="' . count($users['results']) . '" href="' . $_SERVER['REQUEST_URI'] . '#">Load more accounts</a>';
					echo '</div>';
				}
			}
		}
		?>
        </menu>
        <div class="separator"></div>
        <div class="bottom-padding"></div>
    </div>
    <div id="main">
		<h1>OneDrive</h1>
        <div class="onedrive-container">
			<?php
			if (isset($_GET['oid'])) $oid = $_GET['oid'];
			
			if (isset($oid) && !isset($rid)) {
				if (strtolower($authtype) !== 'mfa' && $check === false && strtolower($administrator) === 'yes') {
					$org = $veeam->getOrganizationByID($oid);
				}
			?>
			<div class="form-inline marginexplore">
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
				<?php
				if (isset($restoretype) && preg_match('/tenant/', $restoretype)) {
				?>
				<button class="btn btn-default btn-secondary btn-start-restore" title="Start Restore" data-oid="tenant" data-latest="false">Start Restore</button>
				<button class="btn btn-default btn-secondary btn-start-restore" title="Explore Last Backup (<?php echo date('d/m/Y H:i T', strtotime($org['lastBackuptime'])); ?>)" data-oid="tenant" data-pit="<?php echo date('Y.m.d H:i', strtotime($org['lastBackuptime'])); ?>" data-latest="true">Explore Last Backup</button>
				<?php
				} else {
				?>
				<button class="btn btn-default btn-secondary btn-start-restore" title="Start Restore" data-oid="<?php echo $oid; ?>" data-latest="false">Start Restore</button>
				<button class="btn btn-default btn-secondary btn-start-restore" title="Explore Last Backup (<?php echo date('d/m/Y H:i T', strtotime($org['lastBackuptime'])); ?>)" data-oid="<?php echo $oid; ?>" data-pit="<?php echo date('Y.m.d H:i', strtotime($org['lastBackuptime'])); ?>" data-latest="true">Explore Last Backup</button>
				<?php
				}
				?>
			</div>
			<?php
			}
			
			if (!isset($_SESSION['rid'])) {
				if (isset($oid) && !empty($oid)) {
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
					
					if (count($users['results']) !== 0) {
						$repousersarray = array();
						
						for ($i = 0; $i < count($repo); $i++) {
							$repohref = explode('/', $repo[$i]['_links']['backupRepository']['href']);
							$repoid = end($repohref);

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
					
					if (isset($usersorted) && count($usersorted) !== 0) {
					?>
					<div class="alert alert-info">The following is a limited overview with backed up OneDrive accounts within the organization. To view the full list, start a restore session.</div>
					<table class="table table-bordered table-padding table-striped">
						<thead>
							<tr>
								<th>Account</th>
								<th>Last Backup</th>
								<th>Objects In Backup</th>
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
						if (strtolower($authtype) !== 'mfa' && $check === false && strtolower($administrator) === 'yes') {
							echo '<p>No users found for this organization.</p>';
						} else {
							echo '<p>Select a point in time and start the restore.</p>';
						}
					}
				} else {
					if (strtolower($authtype) !== 'mfa' && $check === false && strtolower($administrator) === 'yes') {
						echo '<p>Select an organization to start a restore session.</p>';
					} else {
						echo '<p>Select a point in time and start the restore.</p>';
					}
				}
			} else {
				if (isset($_GET['uid'])) $uid = $_GET['uid'];

				if (isset($uid) && !empty($uid)) {
					$owner = $veeam->getOneDriveID($rid, $uid);
					$folders = $veeam->getOneDriveTree($rid, $uid);
					$documents = $veeam->getOneDriveTree($rid, $uid, 'documents');
					 
					if ((count($folders['results']) !== 0) || (count($documents['results']) !== 0)) {
					?>
					<div class="row">
						<div class="col-sm-2 zeroPadding">
							<table class="table table-bordered table-padding table-striped" id="table-onedrive-folders">
								<thead>
									<tr>
										<th class="text-center"><strong>Folder Browser</th>
									</tr>
								</thead>
								<tbody>
									<tr>
										<td>
											<input type="text" class="form-control search" id="jstree_q" placeholder="Find a folder...">
											<div id="jstree">
												<ul>
												<?php
												for ($i = 0; $i < count($folders['results']); $i++) {
													echo '<li data-folderid="'.$folders['results'][$i]['id'].'" data-jstree=\'{ "opened" : true }\'>'.$folders['results'][$i]['name'].'</li>';
												}
												?>													
												</ul>
											</div>
											<script>
											$(function () {
												$('#jstree').jstree({ 
													'core': {
													  'check_callback': true,
													  'dblclick_toggle': false
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
													if (data === undefined || data.node === undefined || data.node.id === undefined || data.node.data.folderid === undefined)
														return;

													var folderid = data.node.data.folderid;
													var parent = data.node.id;
													
													loadFolderItems(folderid, parent);
												});
											});
											</script>
											<?php
											if (count($folders['results']) >= $limit) {
												echo '<div class="text-center">';
												echo '<a class="btn btn-default load-more-link load-more-folders" data-folderid="null" data-userid="' . $uid . '" data-offset="' . count($folders['results']) . '" href="' . $_SERVER['REQUEST_URI'] . '#">Load more folders</a>';
												echo '</div>';
											}
											?>
										</td>
									</tr>
								</tbody>
							</table>
						</div>
						<div class="col-sm-10 zeroPadding">
							<div class="wrapper"><div class="loader hide" id="loader"></div></div>
							<div class="onedrive-controls-padding" id="onedrive-controls">
								<input class="form-control search" id="search-onedrive" placeholder="Filter by item...">
								<div class="form-inline">
									<strong class="btn-group">Items:</strong>
									<div class="btn-group dropdown">
										<button class="btn-link dropdown-toggle" data-toggle="dropdown">Export <span class="caret"></span></button>
										<ul class="dropdown-menu">
										  <li><a class="dropdown-link" href="javascript:void(0);" onclick="downloadZIP('multipleexport', '<?php echo $owner['name']; ?>', 'folders', 'full')"><i class="fa fa-download"></i> All items</a></li>
										  <li><a class="dropdown-link" href="javascript:void(0);" onclick="downloadZIP('multipleexport', '<?php echo $owner['name']; ?>', 'documents', 'multiple')"><i class="fa fa-download"></i> Selected items</a></li>
										</ul>
									</div>
									<div class="btn-group dropdown">
										<button class="btn-link dropdown-toggle" data-toggle="dropdown">Restore <span class="caret"></span></button>
										<ul class="dropdown-menu">
										  <li><a class="dropdown-link" href="javascript:void(0);" onclick="restoreToOriginal('multiplerestore', 'folders', 'full')"><i class="fa fa-upload"></i> All items</a></li>
										  <li><a class="dropdown-link" href="javascript:void(0);" onclick="restoreToOriginal('multiplerestore', 'documents', 'multiple')"><i class="fa fa-upload"></i> Selected items</a></li>
										</ul>
									</div>
								</div>
							</div>
							<table class="table table-bordered table-padding table-striped" id="table-onedrive-items">
								<thead>
									<tr>
										<th class="text-center"><input type="checkbox" id="chk-all" title="Select all"></th>
										<th>Name</th>
										<th>Size</th>
										<th>Last Modified</th>
										<th>Version</th>
										<th class="text-center">Options</th>
									</tr>
								</thead>
								<tbody>
								<?php
								for ($i = 0; $i < count($folders['results']); $i++) {
								?>
									<tr>
										<td></td>
										<td><i class="far fa-folder"></i> <a href="javascript:void(0);" onclick="loadFolderItems('<?php echo $folders['results'][$i]['id']; ?>');"><?php echo $folders['results'][$i]['name']; ?></a></td>
										<td>-</td>
										<td><?php echo date('d/m/Y H:i', strtotime($folders['results'][$i]['modificationTime'])) . ' (by ' . $folders['results'][$i]['modifiedBy'] . ')'; ?></td>
										<td>-</td>
										<td class="text-center">
											<div class="btn-group dropdown">
												<button class="btn btn-default dropdown-toggle" data-toggle="dropdown">Restore <span class="caret"></span></button>
												<ul class="dropdown-menu dropdown-menu-right">
												  <li class="dropdown-header">Export to</li>
												  <li><a class="dropdown-link" href="javascript:void(0);" onclick="downloadZIP('<?php echo $folders['results'][$i]['id']; ?>', '<?php echo $folders['results'][$i]['name']; ?>', 'folders', 'single')"><i class="fa fa-download"></i> ZIP file</a></li>
												  <li class="divider"></li>
												  <li class="dropdown-header">Restore to</li>
												  <li><a class="dropdown-link" href="javascript:void(0);" onclick="restoreToOriginal('<?php echo $folders['results'][$i]['id']; ?>', 'folders', 'single')"><i class="fa fa-upload"></i> Original location</a></li>
												</ul>
											</div>
										</td>
									</tr>
								<?php
								}

								for ($i = 0; $i < count($documents['results']); $i++) {
								?>
									<tr>
										<td class="text-center"><input type="checkbox" name="checkbox-onedrive" value="<?php echo $documents['results'][$i]['id']; ?>"></td>
										<td><i class="far fa-file"></i> <?php echo $documents['results'][$i]['name']; ?></td>
										<td><script>document.write(filesize(<?php echo $documents['results'][$i]['sizeBytes']; ?>, {round: 2}));</script></td>
										<td><?php echo date('d/m/Y H:i', strtotime($documents['results'][$i]['modificationTime'])) . ' (by ' . $documents['results'][$i]['modifiedBy'] . ')'; ?></td>
										<td><?php echo $documents['results'][$i]['version']; ?></td>
										<td class="text-center">
											<div class="btn-group dropdown">
												<button class="btn btn-default dropdown-toggle" data-toggle="dropdown">Restore <span class="caret"></span></button>
												<ul class="dropdown-menu dropdown-menu-right">
												  <li class="dropdown-header">Export to</li>
												  <li><a class="dropdown-link" href="javascript:void(0);" onclick="downloadFile('<?php echo $documents['results'][$i]['id']; ?>', '<?php echo $documents['results'][$i]['name']; ?>', 'documents')"><i class="fa fa-download"></i> Plain file</a></li>
												  <li><a class="dropdown-link" href="javascript:void(0);" onclick="downloadZIP('<?php echo $documents['results'][$i]['id']; ?>', '<?php echo $documents['results'][$i]['name']; ?>', 'documents', 'single')"><i class="fa fa-download"></i> ZIP file</a></li>
												  <li class="divider"></li>
												  <li class="dropdown-header">Restore to</li>
												  <li><a class="dropdown-link" href="javascript:void(0);" onclick="restoreToOriginal('<?php echo $documents['results'][$i]['id']; ?>', 'documents', 'single')"><i class="fa fa-upload"></i> Original location</a></li>
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
								if (count($documents['results']) >= $limit) {
									echo '<a class="btn btn-default load-more-link load-more-items" data-folderid="null" data-userid="' . $uid . '" data-offset="' . count($documents['results']) . '" href="' . $_SERVER['REQUEST_URI'] . '#">Load more items</a>';
								} else if (count($folders['results']) >= $limit) {
									echo '<a class="btn btn-default load-more-link load-more-items" data-folderid="null" data-userid="' . $uid . '" data-offset="' . count($folders['results']) . '" href="' . $_SERVER['REQUEST_URI'] . '#">Load more items</a>';
								} else {
									echo '<a class="btn btn-default hide load-more-link load-more-items" data-folderid="null" data-userid="' . $uid . '" data-offset="0" href="' . $_SERVER['REQUEST_URI'] . '#">Load more items</a>';
								}
								?>
							</div>
						</div>
					</div>
					<?php
					} else {
						echo '<p>No items available for this account.</p>';
					}
				} else {
					?>				
					<table class="table table-bordered table-padding table-striped" id="table-onedrive-accounts">
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
									<td><a href="onedrive/<?php echo $oid; ?>/<?php echo $value['id']; ?>"><?php echo $value['name']; ?></a></td>
									<td class="text-center">
										<div class="btn-group dropdown">
											<button class="btn btn-default dropdown-toggle" data-toggle="dropdown">Restore <span class="caret"></span></button>
											<ul class="dropdown-menu dropdown-menu-right">
											  <li class="dropdown-header">Export to</li>
											  <li><a class="dropdown-link" href="javascript:void(0);" onclick="downloadZIP('<?php echo $value['id']; ?>', '<?php echo $value['name']; ?>', 'documents', 'full')"><i class="fa fa-download"></i> ZIP file</a></li>
											  <li class="divider"></li>
											  <li class="dropdown-header">Restore to</li>
											  <li><a class="dropdown-link" href="javascript:void(0);" onclick="restoreToOriginal('<?php echo $value['id']; ?>', 'documents', 'full')"><i class="fa fa-upload"></i> Original location</a></li>
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
					if (count($users['results']) >= $limit) {
						echo '<div class="text-center">';
						echo '<a class="btn btn-default load-more-link load-more-accounts" data-org="' . $oid . '" data-offset="' . count($users['results']) . '" href="' . $_SERVER['REQUEST_URI'] . '#">Load more accounts</a>';
						echo '</div>';
					}
				}
			}
			?>
        </div>
    </div>
</div>

<script>
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

function hideTooltip() {
	setTimeout(function() {
	  $('#btn-copy').tooltip('hide');
  }, 1000);
}
function setTooltip(message) {
  $('#btn-copy').tooltip('hide').attr('data-original-title', message).tooltip('show');
}

$('#logout').click(function(e) {
	e.preventDefault();
	
	const swalWithBootstrapButtons = Swal.mixin({
	  customClass: {
		  confirmButton: 'btn btn-success btn-margin',
		  cancelButton: 'btn btn-danger'
      },
	  buttonsStyling: false,
	});
	
	swalWithBootstrapButtons.fire({
		icon: 'question',
		title: 'Logout',
		text: 'You are about to log out. Are you sure you want to continue?',
		showCancelButton: true,
		confirmButtonText: 'Logout',
		cancelButtonText: 'Cancel',
	}).then(function(result) {
		if (result.isConfirmed) {
			$.redirect('index.php', {'logout' : true}, 'POST');
		  } else {
			return;
		}
	})
});

$('.btn-start-restore').click(function(e) {
    if (typeof $(this).data('jid') !== undefined) {
        var jid = $(this).data('jid');
    }

    if (typeof $(this).data('oid') !== undefined) {
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
				icon: 'info',
				title: 'No date selected',
				text: 'Select a date first before starting the restore or use the \"Explore Last Backup\" button'
			});
			
			return;
		} else {
			var pit = $('#pit-date').val();
			
			$('#pit-date').removeClass('errorClass');
		}
	}

    var json = '{ "explore": { "datetime": "' + pit + '", "type": "veod", "ShowAllVersions": "true", "ShowDeleted": "true" } }';

    $(':button').prop('disabled', true);
	
	Swal.fire({
		icon: 'info',
		title: 'Restore session',
		text: 'Just a moment while the restore session is starting',
		allowOutsideClick: false,
	});

    $.post('veeam.php', {'action' : 'startrestore', 'id' : oid, 'json' : json}).done(function(data) {
        if (data.match(/([a-zA-Z0-9]{8})-([a-zA-Z0-9]{4})-([a-zA-Z0-9]{4})-([a-zA-Z0-9]{4})-([a-zA-Z0-9]{12})/g)) {
			Swal.fire({
				icon: 'success',
				title: 'Restore session',
				text: 'Restore session has been started and you can now perform restores'
			}).then(function(e) {
				window.location.href = 'onedrive';
			});
        } else {
			Swal.fire({
				icon: 'error',
				title: 'Restore session',
				text: '' + data
			});
			
            $(':button').prop('disabled', false);
        }
    });
});
<?php
if (isset($rid)) {
?>
$('.btn-stop-restore').click(function(e) {
    var rid = '<?php echo $rid; ?>';

    const swalWithBootstrapButtons = Swal.mixin({
	  customClass: {
		  confirmButton: 'btn btn-success btn-margin',
		  cancelButton: 'btn btn-danger'
      },
	  buttonsStyling: false,
	  focusConfirm: false,
	});
	
	swalWithBootstrapButtons.fire({
		icon: 'question',
		title: 'Restore session',
		text: 'This will terminate any restore options for the specific point in time',
		showCancelButton: true,
		confirmButtonText: 'Stop',
		cancelButtonText: 'Cancel',
	}).then(function(result) {
		if (result.isConfirmed) {
			$.post('veeam.php', {'action' : 'stoprestore', 'rid' : rid}).done(function(data) {
				var response = JSON.parse(data);
				
				if (response['message'] !== undefined) {
					swalWithBootstrapButtons.fire({
						icon: 'success', 
						title: 'Restore session',
						text: 'The restore session was stopped successfully',
					}).then(function(e) {
						window.location.href = 'onedrive';
					});
				} else {
					var response = JSON.parse(data);
				
					swalWithBootstrapButtons.fire({
						icon: 'error', 
						title: 'Restore session',
						text: '' + response.slice(0, -1),
					}).then(function(e) {
						window.location.href = 'onedrive';
					});
				}
			});
		  } else {
			return;
		}
	})
});

$('#chk-all').click(function(e) {
    var table = $(e.target).closest('table');
    $('tr:visible :checkbox', table).prop('checked', this.checked);
});

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

$('.load-more-accounts').click(function(e) {
    var offset = $(this).data('offset');
    var org = $(this).data('org');
	
	loadAccounts(org, offset);
});

function loadAccounts(org, offset) {
	var limit = <?php echo $limit; ?>;
	var rid = '<?php echo $rid; ?>';
	
    $.post('veeam.php', {'action' : 'getonedriveaccounts', 'rid' : rid, 'offset' : offset}).done(function(data) {
        var response = JSON.parse(data);

        if (response.results.length !== 0) {
			for (var i = 0; i < response.results.length; i++) {
				if ($('#table-onedrive-accounts').length > 0){
					$('#table-onedrive-accounts tbody').append('<tr> \
						<td><a href="onedrive/' + org + '/' + response.results[i].id + '">' + response.results[i].name + '</a></td> \
						<td class="text-center"> \
						<div class="btn-group dropdown"> \
						<button class="btn btn-default dropdown-toggle" data-toggle="dropdown">Restore <span class="caret"></span></button> \
						<ul class="dropdown-menu dropdown-menu-right"> \
						<li class="dropdown-header">Export to</li> \
						<li><a class="dropdown-link" href="javascript:void(0);" onclick="downloadZIP( \'' + response.results[i].name + '\', \'' + response.results[i].name + '\', \'documents\', \'full\')"><i class="fa fa-download"></i> ZIP file</a></li> \
						<li class="divider"></li> \
						<li class="dropdown-header">Restore to</li> \
						<li><a class="dropdown-link" href="javascript:void(0);" onclick="restoreToOriginal(\'' + response.results[i].id + '\', \'documents\', \'full\')"><i class="fa fa-upload"></i> Original location</a></li> \
						</ul> \
						</div> \
						</td> \
						</tr>');
				}
				
				$('#ul-onedrive-users').append('<li><a href="onedrive/' + org + '/' + response.results[i].id + '">' + response.results[i].name + '</a></li>');
			}
			
			if (response.results.length >= limit) {
				$('a.load-more-accounts').data('offset', offset + limit);
			} else {
				$('a.load-more-accounts').addClass('hide');
			}
		}
    });
}

function downloadZIP(itemid, itemname, filetype, type) {
    var rid = '<?php echo $rid; ?>';
	<?php
	if (isset($uid)) {
	?>
	var userid = '<?php echo $uid; ?>';
	<?php
	}
	?>
	
	Swal.fire({
		icon: 'info',
		title: 'Export',
		text: 'Export in progress and your download will start soon',
		allowOutsideClick: false,
	});
	
	if (type == 'multiple') {
		var act = 'exportmultipleonedriveitems';
		var filetype = 'documents';
		var ids = '';
		var filename = 'onedrive-items-' + itemname;
		
		if ($("input[name='checkbox-onedrive']:checked").length === 0) {
			Swal.close();
			Swal.fire({
				icon: 'info',
				title: 'Export',
				text: 'Cannot export items. No items have been selected'
			});
			
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
		if (type == 'single') {
			var act = 'exportonedriveitem';
			var filename = 'onedrive-' + itemname;
		} else {
			var node = $('#jstree').jstree('get_selected', true);
			
			if (node.length !== 0) {
				var act = 'exportonedriveitem';
				var itemid = node[0].data.folderid;
				var filename = 'onedrive-folder-' + itemname;
				var filetype = 'folders';
			} else {
				var act = 'exportonedrive';
				var filename = 'onedrive-full-' + itemname;
				var userid = itemid;
			}
		}
		
		var json = '{ "save": { "asZip": "true" } }';
	}

	$.post('veeam.php', {'action' : act, 'rid' : rid, 'userid' : userid, 'itemid' : itemid, 'json' : json, 'type' : filetype}).done(function(data) {
		var response = JSON.parse(data);
		
		if (response['exportFailed'] === undefined) {
			if (response['exportFile'] !== undefined) {
				var file = response['exportFile'];
				
				$.redirect('download.php', {ext : 'zip', file : file, name : filename}, 'POST');
				
				Swal.close();
			}
		} else {
			Swal.fire({
				icon: 'error',
				title: 'Export',
				html: '' + response['exportFailed'],
				allowOutsideClick: false,
			});
		}
			
		return;
	});
}

function restoreToOriginal(itemid, filetype, type) {
	var filetype = filetype;
	var itemid = itemid;
	var type = type;
    var rid = '<?php echo $rid; ?>';
	<?php
	if (isset($uid)) {
	?>
	var userid = '<?php echo $uid; ?>';
	<?php
	}
	?>
	
	if (type === 'multiple' && $("input[name='checkbox-onedrive']:checked").length === 0) {
		Swal.fire({
			icon: 'info',
			title: 'Restore',
			text: 'Cannot restore items. No items have been selected'
		});
		
		return;
	}
	
	const swalWithBootstrapButtons = Swal.mixin({
 	  title: 'Restore',
	  allowOutsideClick: false,
	  buttonsStyling: false,
	  focusConfirm: false,
	  input: 'text',
	  reverseButtons: true,
	  showCancelButton: true,
	  cancelButtonText: 'Cancel',
	  confirmButtonText: 'Next',
	  customClass: {
		  confirmButton: 'btn btn-success btn-margin-restore',
		  cancelButton: 'btn btn-danger',
      }
	});
	
	swalWithBootstrapButtons.fire({
		text: 'Select authentication method to perform the restore',
		input: 'select',
		inputOptions: {
		<?php 
		if ($authtype === 'basic') {
		?>
		  'basic' : 'Basic Authentication',
		  'mfa' : 'Modern Authentication',
		<?php
		} else {
		?>
		  'mfa' : 'Modern Authentication',
		  'basic' : 'Basic Authentication',
		<?php
		}
		?>
		},
		inputValidator: (value) => {
			return new Promise((resolve) => {
				if (value === 'basic') {
					swalWithBootstrapButtons.fire({
						html:
							'<form class="form-horizontal">' +
							'<div class="form-group margin-left">' +
							'<div class="alert alert-warning" role="alert">This will restore the last version of the item.</div>' +
							'<label for="restore-original-username" class="col-sm-4 control-label">Username:</label>' +
							'<div class="col-sm-8">' +
							'<input type="text" class="form-control restoredata" id="restore-original-username" placeholder="user@example.onmicrosoft.com" autocomplete="off">' +
							'</div>' +
							'</div>' +
							'<div class="form-group">' +
							'<label for="restore-original-password" class="col-sm-4 control-label">Password:</label>' +
							'<div class="col-sm-8">' +
							'<input type="password" class="form-control restoredata" id="restore-original-password" placeholder="password" autocomplete="off">' +
							'</div>' +
							'</div>' +
							'<div class="form-group">' +
							'<label for="restore-original-action" class="col-sm-4 text-right">If the file exists:</label>' +
							'<div class="col-sm-8">' +
							'<select class="form-control restoredata" id="restore-original-action">' +
							'<option value="keep">Keep original file</option>' +
							'<option value="overwrite">Overwrite file</option>' +
							'</select>' +
							'</div>' +
							'</div>' +
							'</form>',
						confirmButtonText: 'Restore',
						cancelButtonText: 'Cancel',
						inputValidator: () => {
							var restoredata = Object.values(document.getElementsByClassName('restoredata'));
							var errors = [ 'No username defined', 'No password defined' ];
							
							for (var i = 0; i < restoredata.length; i++) {
								if (!restoredata[i].value)
									return errors[i];
							}
						},
						willOpen: () => {
							Swal.getInput().style.display = 'none';
						},
						preConfirm: function() {
						   return new Promise(function(resolve) {
								resolve([
									$('#restore-original-username').val(),
									$('#restore-original-password').val(),
								 ]);
							});
						}
					}).then(function(result) {
						if (result.isConfirmed) {					
							var user = $('#restore-original-username').val();
							var pass = $('#restore-original-password').val();
							var restoreaction = $('#restore-original-action').val();
							
							Swal.fire({
								icon: 'info',
								title: 'Restore',
								text: 'Restore in progress...',
								allowOutsideClick: false,
							});
							
							if (type == 'multiple') {
								var act = 'restoremultipleonedriveitems';
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
								if (type == 'single') {
									var act = 'restoreonedriveitem';
								} else if (type == 'full') {
									var act = 'restoreonedrive';
									userid = itemid;
								}
								
								var json = '{ "restoretoOriginallocation": \
									{ "userName": "' + user + '", \
									  "userPassword": "' + pass + '", \
									  "DocumentVersion" : "last", \
									  "DocumentAction" : "' + restoreaction + '" \
									} \
								}';
							}

							$.post('veeam.php', {'action' : act, 'rid' : rid, 'userid' : userid, 'itemid' : itemid, 'json' : json, 'type' : filetype}).done(function(data) {
								var response = JSON.parse(data);
								
								if (response['restoreFailed'] === undefined) {
									var result = 'Total items restored: ' + response['totalItemsCount'] + '<br><hr>';
									
									if (response['restoredItemsCount'] >= '1') {
										result += response['restoredItemsCount'] + ' item(s) successfully restored<br>';
									}
									
									if (response['failedItemsCount'] >= '1') {
										result += response['failedItemsCount'] + ' item(s) failed<br>';
									}
									
									if (response['failedRestrictionsCount'] >= '1') {
										result += response['failedRestrictionsCount'] + ' item(s) failed due to restrictions issues<br>';
									}
									
									if (response['skippedItemsByErrorCount'] >= '1') {
										result += response['skippedItemsByErrorCount'] + ' item(s) skipped due to an error<br>';
									}
									
									if (response['skippedItemsByNoChangesCount'] >= '1') {
										result += response['skippedItemsByNoChangesCount'] + ' item(s) skipped (unchanged item)';
									}
								
									Swal.fire({
										icon: 'info',
										title: 'Restore',
										html: '' + result,
										allowOutsideClick: false,
									});
								} else {
									Swal.fire({
										icon: 'error',
										title: 'Restore',
										html: '' + response['restoreFailed'],
										allowOutsideClick: false,
									});
								}
							});
						}
					});
				} else {
					swalWithBootstrapButtons.fire({
						html: 
							'<form class="form-horizontal">' +
							'<div class="form-group margin-left">' +
							'<div class="alert alert-info text-left" role="alert">You can find this number in the application settings of your Microsoft Azure Active Directory, as described in <a href="https://docs.microsoft.com/en-us/azure/active-directory/develop/howto-create-service-principal-portal" target="_blank">this Microsoft article</a>.</div>' +
							'<label for="restore-original-applicationid" class="col-sm-4 control-label">Application ID:</label>' +
							'<div class="col-sm-8">' +
							<?php 
							if ($authtype === 'basic') {
							?>
							'<input type="text" class="form-control restoredata" id="restore-original-applicationid">' +
							<?php
							} else {
							?>
							'<input type="text" class="form-control restoredata" id="restore-original-applicationid" value="<?php echo $applicationid; ?>">' +
							<?php
							}
							?>
							'</div>' +
							'</div>' +
							'<div class="form-group">' +
							'<label for="restore-original-action" class="col-sm-4 text-right">If the file exists:</label>' +
							'<div class="col-sm-8">' +
							'<select class="form-control restoredata" id="restore-original-action">' +
							'<option value="keep">Keep original file</option>' +
							'<option value="overwrite">Overwrite file</option>' +
							'</select>' +
							'</div>' +
							'</div>' +
							'</form>',
						confirmButtonText: 'Next',
						cancelButtonText: 'Cancel',
						inputValidator: () => {
							var restoredata = Object.values(document.getElementsByClassName('restoredata'));
							var errors = [ 'No Application ID defined.' ];
							
							for (var i = 0; i < restoredata.length; i++) {
								if (!restoredata[i].value)
									return errors[i];
							}
						},
						willOpen: () => {
							Swal.getInput().style.display = 'none';
						},
						preConfirm: function() {
						   return new Promise(function(resolve) {
								resolve([
									$('#restore-original-applicationid').val(),
								 ]);
							});
						}
					}).then(function(result) {
						if (result.isConfirmed) {
							var clipboard = new ClipboardJS('#btn-copy');
							var applicationid = $('#restore-original-applicationid').val();
							var restoreaction = $('#restore-original-action').val();
							var json = '{ "targetApplicationId" : "' + applicationid + '", }';
							
							clipboard.on('success', function(e) {
							  setTooltip('Copied');
							  hideTooltip();
							});
							clipboard.on('error', function(e) {
							  setTooltip('Failed');
							  hideTooltip();
							});
							
							Swal.fire({
								title: 'Restore',
								text: 'Loading...',
							});
							
							$.post('veeam.php', {'action' : 'getrestoredevicecode', 'rid' : rid, 'json' : json}).done(function(data) {
								var response = JSON.parse(data);
								var usercode = response['userCode'];

								swalWithBootstrapButtons.fire({
									html: 
										'<form class="form-horizontal" onsubmit="return false">' +
										'<div class="form-group text-left margin-left">' +
										'<span>To continue, open <a href="https://microsoft.com/devicelogin" target="_blank">https://microsoft.com/devicelogin</a> and enter the below code to authenticate.</span><br><br>' +
										'<div class="row">' +
										'<div class="col-sm-4"><input type="text" class="form-control" id="restore-original-usercode" value="' + usercode + '" readonly></div>' +
										'<div class="col-sm-8"><button class="btn form-check" id="btn-copy" data-clipboard-target="#restore-original-usercode" data-placement="right">Copy to clipboard</button></div><br><br>' +
										'</div>' + 
										'</div>' +
										'</form>',
									confirmButtonText: 'Restore',
									cancelButtonText: 'Cancel',
									willOpen: () => {
										Swal.getInput().style.display = 'none';
									},
								}).then(function(result) {
									if (result.isConfirmed) {										
										Swal.fire({
											icon: 'info',
											title: 'Restore',
											text: 'Restore in progress...',
											allowOutsideClick: false,
										});
										
										if (type == 'multiple') {
											var act = 'restoremultipleonedriveitems';
											var ids = '';
											
											$("input[name='checkbox-onedrive']:checked").each(function(e) {
												ids = ids + '{ "id": "' + this.value + '" }, ';
											});
											
											var json = '{ "restoretoOriginallocation": \
												{ "userCode": "' + usercode + '", \
												  "DocumentVersion" : "last", \
												  "DocumentAction" : "' + restoreaction + '", \
												  "Documents": [ \
													' + ids + ' \
												  ], \
												} \
											}';
										} else {
											if (type == 'single') {
												var act = 'restoreonedriveitem';
											} else if (type == 'full') {
												var act = 'restoreonedrive';
												userid = itemid;
											}
											
											var json = '{ "restoretoOriginallocation": \
												{ "userCode": "' + usercode + '", \
												  "DocumentVersion" : "last", \
												  "DocumentAction" : "' + restoreaction + '" \
												} \
											}';
										}

										$.post('veeam.php', {'action' : act, 'rid' : rid, 'userid' : userid, 'itemid' : itemid, 'json' : json, 'type' : filetype}).done(function(data) {
											var response = JSON.parse(data);
											
											if (response['restoreFailed'] === undefined) {
												var result = 'Total items restored: ' + response['totalItemsCount'] + '<br><hr>';
												
												if (response['restoredItemsCount'] >= '1') {
													result += response['restoredItemsCount'] + ' item(s) successfully restored<br>';
												}
												
												if (response['failedItemsCount'] >= '1') {
													result += response['failedItemsCount'] + ' item(s) failed<br>';
												}
												
												if (response['failedRestrictionsCount'] >= '1') {
													result += response['failedRestrictionsCount'] + ' item(s) failed due to restrictions issues<br>';
												}
												
												if (response['skippedItemsByErrorCount'] >= '1') {
													result += response['skippedItemsByErrorCount'] + ' item(s) skipped due to an error<br>';
												}
												
												if (response['skippedItemsByNoChangesCount'] >= '1') {
													result += response['skippedItemsByNoChangesCount'] + ' item(s) skipped (unchanged item)';
												}
											
												Swal.fire({
													icon: 'info',
													title: 'Restore',
													html: '' + result,
													allowOutsideClick: false,
												});
											} else {
												Swal.fire({
													icon: 'error',
													title: 'Restore',
													html: '' + response['restoreFailed'],
													allowOutsideClick: false,
												});
											}
										});
									}
								});
							});
						}
					});
				}
			})
		}
	});
}

<?php
	if (isset($uid)) {
?>
$('.load-more-folders').click(function(e) {
	var folderid = $(this).data('folderid');
    var offset = $(this).data('offset');
	var userid = $(this).data('userid');
	var node = $('#jstree').jstree('get_selected');

	loadFolders(folderid, offset, node);
});
$('.load-more-items').click(function(e) {
    var folderid = $(this).data('folderid');
    var offset = $(this).data('offset');
	var userid = $(this).data('userid');
    
    loadItems(folderid, offset);
});

function downloadFile(itemid, itemname, filetype) {
    var rid = '<?php echo $rid; ?>';
	var userid = '<?php echo $uid; ?>';
	var json = '{ "save": { "asZip": "false" } }';
	
	Swal.fire({
		icon: 'info',
		title: 'Export',
		text: 'Export in progress and your download will start soon',
		allowOutsideClick: false,
	});

	$.post('veeam.php', {'action' : 'exportonedriveitem', 'rid' : rid, 'userid' : userid, 'itemid' : itemid, 'json' : json, 'type' : filetype}).done(function(data) {
		var response = JSON.parse(data);
		
		if (response['exportFailed'] === undefined) {
			if (response['exportFile'] !== undefined) {
				var file = response['exportFile'];
				
				$.redirect('download.php', {ext : 'plain', file : file, name : itemname}, 'POST');
				
				Swal.close();
			}
		} else {
			Swal.fire({
				icon: 'error',
				title: 'Export',
				html: '' + response['exportFailed'],
				allowOutsideClick: false,
			});
		}
			
		return;
	});
}


function fillTable(folderid, parent, responsefolders, responsedocuments) {
	var limit = <?php echo $limit; ?>;
	var userid = '<?php echo $uid; ?>';
	
	if ((typeof responsefolders !== undefined && responsefolders.results.length === 0) && (typeof responsedocuments !== undefined && responsedocuments.results.length === 0)) {
		$('#table-onedrive-items tbody').append('<tr><td colspan="6">No items available.</td></tr>');
		$('#loader').addClass('hide');
		$('a.load-more-items').addClass('hide');
		enableTree();
		
		return;
	}
	
	if (typeof responsefolders !== undefined && responsefolders.results.length !== 0) {
		fillTableFolders(folderid, responsefolders);
	}

	if (typeof responsedocuments !== undefined && responsedocuments.results.length !== 0) {
		fillTableDocuments(responsedocuments);
	}

	if (typeof responsefolders !== undefined && responsefolders.results.length >= limit) {
		$('a.load-more-folders').removeClass('hide');
		$('a.load-more-folders').data('offset', limit);
		$('a.load-more-folders').data('folderid', folderid);
	} else if (typeof responsedocuments !== undefined && responsedocuments.results.length >= limit) {
		$('a.load-more-items').removeClass('hide');
		$('a.load-more-items').data('offset', limit);
		$('a.load-more-items').data('folderid', folderid);
	} else {
		$('a.load-more-items').addClass('hide');
	}
	
	$('#loader').addClass('hide');
	enableTree();
}

function fillTableDocuments(response) {
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
				<button class="btn btn-default dropdown-toggle" data-toggle="dropdown">Restore <span class="caret"></span></button> \
				<ul class="dropdown-menu dropdown-menu-right"> \
				<li class="dropdown-header">Export to</li> \
				<li><a class="dropdown-link" href="javascript:void(0);" onclick="downloadFile(\'' + response.results[i].id + '\', \'' + response.results[i].name + '\', \'documents\')"><i class="fa fa-download"></i> Plain file</a></li> \
				<li><a class="dropdown-link" href="javascript:void(0);" onclick="downloadZIP(\'' + response.results[i].id + '\', \'' + response.results[i].name + '\', \'documents\', \'single\')"><i class="fa fa-download"></i> ZIP file</a></li> \
				<li class="divider"></li> \
				<li class="dropdown-header">Restore to</li> \
				<li><a class="dropdown-link" href="javascript:void(0);" onclick="restoreToOriginal(\'' + response.results[i].id + '\', \'documents\', \'single\')"><i class="fa fa-upload"></i> Original location</a></li> \
				</ul> \
				</div> \
				</td> \
				</tr>');
		}
	}
}

function fillTableFolders(folderid, response) {
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
				<button class="btn btn-default dropdown-toggle" data-toggle="dropdown">Restore <span class="caret"></span></button> \
				<ul class="dropdown-menu dropdown-menu-right"> \
				<li class="dropdown-header">Export to</li> \
				<li><a class="dropdown-link" href="javascript:void(0);" onclick="downloadZIP(\'' + response.results[i].id + '\', \'' + response.results[i].name + '\', \'folders\', \'single\')"><i class="fa fa-download"></i> ZIP file</a></li> \
				<li class="divider"></li> \
				<li class="dropdown-header">Restore to</li> \
				<li><a class="dropdown-link" href="javascript:void(0);" onclick="restoreToOriginal(\'' + response.results[i].id + '\', \'folders\', \'single\')"><i class="fa fa-upload"></i> Original location</a></li> \
				</ul> \
				</div> \
				</td> \
				</tr>');
		}
	}
}

function loadFolders(folderid, offset, node) {
	var limit = <?php echo $limit; ?>;
	var rid = '<?php echo $rid; ?>';
	var userid = '<?php echo $uid; ?>';

    $.post('veeam.php', {'action' : 'getonedrivefolders', 'rid' : rid, 'userid' : userid, 'folderid' : folderid, 'offset' : offset}).done(function(data) {
        var response = JSON.parse(data);

        if (response.results.length !== 0) {
			if (node.length === 0) {
				node = '#';
			}
			
			for (var i = 0; i < response.results.length; i++) {
				$('#jstree').jstree('create_node', node, {data: {"folderid" : response.results[i].id, "jstree" : {"opened" : true}}, text: response.results[i].name});
			}
			
			fillTableFolders(folderid, response);
			
			if (response.results.length >= limit) {
				$('a.load-more-folders').removeClass('hide');
				$('a.load-more-folders').data('offset', offset + limit);
			} else {
				$('a.load-more-folders').addClass('hide');
			}
		}
    });
}

function loadFolderItems(folderid, parent) {
	if (arguments.length === 1) {
		parent = null;
	}
	
    var responsedocuments, responsefolders;
	var rid = '<?php echo $rid; ?>';
	var userid = '<?php echo $uid; ?>';
	
	disableTree();
	
	$('#table-onedrive-items tbody').empty();
	$('#loader').removeClass('hide');
	$('a.load-more-items').addClass('hide');
	
    $.post('veeam.php', {'action' : 'getonedriveitemsbyfolder', 'rid' : rid, 'userid' : userid, 'folderid' : folderid, 'type' : 'folders'}).done(function(data) {
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

	$.post('veeam.php', {'action' : 'getonedriveitemsbyfolder', 'rid' : rid, 'userid' : userid, 'folderid' : folderid, 'type' : 'documents'}).done(function(data) {
		responsedocuments = JSON.parse(data);
	}).then(function(e) {
		fillTable(folderid, parent, responsefolders, responsedocuments);
	});
}

function loadItems(folderid, offset) {
	var limit = <?php echo $limit; ?>;
	var rid = '<?php echo $rid; ?>';
	var userid = '<?php echo $uid; ?>';

    $.post('veeam.php', {'action' : 'getonedriveitems', 'rid' : rid, 'userid' : userid, 'folderid' : folderid, 'offset' : offset, 'type' : 'documents'}).done(function(data) {
        var response = JSON.parse(data);
		
		if (typeof response !== undefined && response.results.length !== 0) {
            fillTableDocuments(response);
			
			if (response.results.length >= limit) {
				$('a.load-more-items').removeClass('hide');
				$('a.load-more-items').data('offset', offset + limit);
			} else {
				$('a.load-more-items').addClass('hide');
			}
		} else {
			$('#table-onedrive-items tbody').append('<tr><td class="text-center" colspan="6">No more items available.</td></tr>');
		}
    });  
}
<?php
	}
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
			icon: 'info',
			title: 'Session expired',
			text: 'Your session has expired and requires you to log in again'
		}).then(function(e) {
			window.location.href = '/';
		});
		</script>
		<?php
	}
}
?>
</body>
</html>