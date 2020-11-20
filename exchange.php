<?php
error_reporting(E_ALL || E_STRICT);
set_time_limit(0);
session_start();

require_once('config.php');
require_once('veeam.class.php');

if (empty($host) || empty($port) || empty($version)) {
    exit('Please modify the configuration file first and configure the Veeam Backup for Microsoft Office 365 host, port and RESTful API version settings.');
}

if (!preg_match('/v[3-5]/', $version)) {
	exit('Invalid API version found. Please modify the configuration file and configure the Veeam Backup for Microsoft Office 365 RESTful API version setting. Only version 3, 4 and 5 are supported.');
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
    <script src="js/jquery.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
	<script src="js/clipboard.min.js"></script>
    <script src="js/fontawesome.min.js"></script>
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
	
    if (isset($_SESSION['user'])) {
		$user = $_SESSION['user'];
	} else {
		empty($user);
	}
	
	if (isset($_SESSION['authtype'])) {
		$authtype = $_SESSION['authtype'];
	}
	
	if (isset($_SESSION['applicationid'])) {
		$applicationid = $_SESSION['applicationid'];
	}
?>
<nav class="navbar navbar-inverse navbar-static-top">
	<ul class="nav navbar-header">
	  <li><a class="navbar-brand navbar-logo" href="/"><img src="images/logo.svg" alt="Veeam Backup for Microsoft Office 365" class="logo"></a></li>
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
	<link rel="stylesheet" type="text/css" href="css/exchange.css" />
	<aside id="sidebar">
		<div class="logo-container"><i class="logo fa fa-envelope"></i></div>
		<div class="separator"></div>
		<menu class="menu-segment" id="menu">
		<?php
		if (!isset($_SESSION['rid'])) { /* No restore session is running */
			$check = filter_var($user, FILTER_VALIDATE_EMAIL);

			echo '<ul id="ul-exchange-users">';
			
			if (strtolower($authtype) != 'mfa' && $check === false && strtolower($administrator) == 'yes') {
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
				$users = $veeam->getMailboxes($rid);

				if ($users === 500) {
					unset($_SESSION['rid']);
					?>
					<script>
					Swal.fire({
						icon: 'info',
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

					if (count($users['results']) >= 50) {
						echo '<div class="text-center">';
						echo '<a class="btn btn-default load-more-link load-more-mailboxes" data-org="' . $org['id'] . '" data-offset="' . count($users['results']) . '" href="' . $_SERVER['REQUEST_URI'] . '#">Load more mailboxes</a>';
						echo '</div>';
					}
				}
			} else {
				?>
				<script>
				Swal.fire({
					icon: 'info',
					showConfirmButton: false,
					title: 'Restore session running',
					text: 'Found another restore session running, please stop the session first if you want to restore Exchange items',
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
				if (strtolower($authtype) != 'mfa' && $check === false && strtolower($administrator) == 'yes') {
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

					if (count($users['results']) != 0) {
						$repousersarray = array();
						
						for ($i = 0; $i < count($repo); $i++) {
							$repoid = end(explode('/', $repo[$i]['_links']['backupRepository']['href']));

							for ($j = 0; $j < count($users['results']); $j++) {
								$combinedid = $users['results'][$j]['backedUpOrganizationId'] . $users['results'][$j]['id'];
								$userdata = $veeam->getUserData($repoid, $combinedid);
								
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
						
						$usersorted = array_values(array_column($repousersarray , null, 'name'));
					}
					
					if (count($usersorted) != 0) {
					?>
						<div class="alert alert-info">The following is a limited overview with the backed up accounts and their Exchange objects within the organization. To view the full list, start a restore session.</div>
						<table class="table table-bordered table-padding table-striped" id="table-exchange-mailboxes">
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
								echo '<td>' . $usersorted[$i]['name'] . ' (' . $usersorted[$i]['email'] . ')</td>';
								echo '<td>' . date('d/m/Y H:i T', strtotime($usersarray[$licinfo]['lastBackupDate'])) . '</td>';
								echo '<td>';
								
								if ($usersorted[$i]['isMailboxBackedUp']) {
									echo '<i class="far fa-envelope fa-2x" style="color:green" title="Mailbox"></i> ';
								} else {
									echo '<i class="far fa-envelope fa-2x" style="color:red" title="Mailbox"></i> ';
								}
								if ($usersorted[$i]['isArchiveBackedUp']) {
									echo '<i class="fa fa-archive fa-2x" style="color:green" title="Archive"></i> ';
								} else {
									echo '<i class="fa fa-archive fa-2x" style="color:red" title="Archive"></i> ';
								}
								
								echo '</td>';
								echo '</tr>';
							}
							?>
							</tbody>
						</table>
						<?php
					} else {
						if (strtolower($authtype) != 'mfa' && $check === false && strtolower($administrator) == 'yes') {
							echo '<p>No users found for this organization.</p>';
						} else {
							echo '<p>Select a point in time and start the restore.</p>';
						}
					}
				} else {
					if (strtolower($authtype) != 'mfa' && $check === false && strtolower($administrator) == 'yes') {
						echo '<p>Select an organization to start a restore session.</p>';
					} else {
						echo '<p>Select a point in time and start the restore.</p>';
					}
				}
			} else { /* Restore session is running */
				if (isset($uid) && !empty($uid)) {
					$owner = $veeam->getMailboxID($rid, $uid);
					$folders = $veeam->getMailboxFolders($uid, $rid);
					$parentfolders = array();
					
					for ($i = 0; $i < count($folders['results']); $i++) {
						if (empty($folders['results'][$i][_links][parent])) {
							array_push($parentfolders, array('name' => $folders['results'][$i]['name'], 'id' => $folders['results'][$i]['id'], 'type' => $folders['results'][$i]['type']));
						}
					}
					?>
					<div class="row">
						<div class="col-sm-2 text-center div-browser">
							<div class="btn-group dropdown">
								<button class="btn btn-default dropdown-toggle form-control" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">Restore selected <span class="caret"></span></button>
								<ul class="dropdown-menu dropdown-menu-right">
								  <li class="dropdown-header">Download as</li>
								  <li><a class="dropdown-link" href="javascript:void(0);" onclick="downloadPST('multipleexport', '<?php echo $uid; ?>', '<?php echo $owner['name']; ?>', 'multiple')"><i class="fa fa-download"></i> PST file</a></li>
								  <li class="divider"></li>
								  <li class="dropdown-header">Restore to</li>
								  <li><a class="dropdown-link" href="javascript:void(0);" onclick="restoreToOriginal('multiplerestore', '<?php echo $uid; ?>', 'multiple')"><i class="fa fa-upload"></i> Original location</a></li>
								  <li><a class="dropdown-link" href="javascript:void(0);" onclick="restoreToDifferent('multiplerestore', '<?php echo $uid; ?>', 'multiple')"><i class="fa fa-upload"></i> Different location</a></li>
								</ul>
							</div>
						</div>
						<div class="col-sm-10">
							<input class="form-control search" id="search-mailbox" placeholder="Filter by item...">
						</div>
					</div>
					<div class="row">
						<div class="col-sm-2 div-browser zeroPadding">
							<table class="table table-bordered table-padding table-striped" id="table-exchange-folders">
								<thead>
									<tr>
										<th class="text-center"><strong>Folder Browser</strong></th>
									</tr>
								</thead>
								<tbody>
									<tr>
										<td>
											<input type="text" class="form-control search" id="jstree_q" placeholder="Hilight a folder...">
											<div id="jstree">
												<ul id="ul-exchange-folders">													
													<?php													
													for ($i = 0; $i < count($parentfolders); $i++) {
														switch (strtolower($parentfolders[$i]['type'])) {
															case 'appointment':
																$icon = 'far fa-calendar-check';
																break;
															case 'contact':
																$icon = 'far fa-address-book';
																break;
															case 'journal':
																$icon = 'fa fa-book';
																break;
															case 'stickynote':
																$icon = 'far fa-sticky-note';
																break;
															case 'task':
																$icon = 'fa fa-tasks';
																break;
															default:
																$icon = 'far fa-folder';
														}
														
														echo '<li data-folderid="'.$parentfolders[$i]['id'].'" data-mailboxid="'.$uid.'" data-jstree=\'{ "opened" : true, "icon" : "' . $icon . '" }\'>'.$parentfolders[$i]['name'].'</li>';
													}
													?>
												</ul>
											</div>
											<script>
											$(function() {
												$('#jstree').jstree({ 
													'core': {
													  'check_callback': true,
													  'dblclick_toggle': false
													},
													'plugins': [ 'search', 'sort' ]
												});
												
												var to = false;
												
												$('#jstree_q').keyup(function(e) {
													if (to) { 
														clearTimeout(to); 
													}
													
													to = setTimeout(function(e) {
														var v = $('#jstree_q').val();
														
														$('#jstree').jstree(true).search(v);
													}, 250);
												});
												
												$('#jstree').on('activate_node.jstree', function(e, data) {
													if (data == undefined || data.node == undefined || data.node.id == undefined || data.node.data.folderid == undefined)
														return;

													var folderid = data.node.data.folderid;
													var mailboxid = data.node.data.mailboxid;
													var parent = data.node.id;
													
													loadMailboxItems(folderid, mailboxid, parent);
												});
											});
											</script>
											<?php
											if (count($folders['results']) >= 50) {
												echo '<div class="text-center">';
												echo '<a class="btn btn-default load-more-link load-more-folders" data-mailboxid="' .  $uid . '" data-offset="' . count($folders['results']) . '" href="' . $_SERVER['REQUEST_URI'] . '#">Load more folders</a>';
												echo '</div>';
											}
											?>
										</td>
									</tr>
								</tbody>
							</table>
						</div>
						<div class="col-sm-10 div-browser-items zeroPadding">
							<div class="wrapper"><div class="loader hide" id="loader"></div></div>
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
									<tr>
										<td class="text-center" colspan="6">Select a folder to browse specific items.</td>
									</tr>
								</tbody>
							</table>
							<div class="text-center">
								<a class="btn btn-default hide load-more-link load-more-items" data-folderid="null" data-mailboxid="<?php echo $uid; ?>" data-offset="50" href="<?php echo $_SERVER['REQUEST_URI']; ?>#">Load more messages</a>
							</div>
						</div>
					</div>
					<?php
				} else { /* List mailboxes */
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
									<div class="btn-group dropdown">
										<button class="btn btn-default dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">Options <span class="caret"></span></button>
										<ul class="dropdown-menu dropdown-menu-right">
										  <li class="dropdown-header">Download as</li>
										  <li><a class="dropdown-link" href="javascript:void(0);" onclick="downloadPST('<?php echo $value['name']; ?>', '<?php echo $value['id']; ?>', '<?php echo $value['name']; ?>', 'full')"><i class="fa fa-download"></i> PST file</a></li>
										  <li class="divider"></li>
										  <li class="dropdown-header">Restore to</li>
										  <li><a class="dropdown-link" href="javascript:void(0);" onclick="restoreToOriginal('<?php echo $value['name']; ?>', '<?php echo $value['id']; ?>', 'full')"><i class="fa fa-upload"></i> Original location</a></li>
										  <li><a class="dropdown-link" href="javascript:void(0);" onclick="restoreToDifferent('<?php echo $value['name']; ?>', '<?php echo $value['id']; ?>', 'full')"><i class="fa fa-upload"></i> Different location</a></li>
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
					if (count($users['results']) >= 50) {
						echo '<div class="text-center">';
						echo '<a class="btn btn-default load-more-link load-more-mailboxes" data-org="' . $oid . '" data-offset="' . count($users['results']) . '" href="' . $_SERVER['REQUEST_URI'] . '#">Load more mailboxes</a>';
						echo '</div>';
					}
				}
			}
			?>
		</div>
	</main>
</div>
<div class="bottom-padding"></div>
<script>
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
		text: 'You are about to logout. Are you sure you want to continue?',
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
				icon: 'info',
				title: 'No date selected',
				text: 'Please select a date first before starting the restore or use the \"explore last backup\" button.'
			})
			
			return;
		} else {
			var pit = $('#pit-date').val();
			
			$('#pit-date').removeClass('errorClass');
		}
	}

    var json = '{ "explore": { "datetime": "' + pit + '", "type": "vex", "ShowAllVersions": "true", "ShowDeleted": "true" } }';

    $(':button').prop('disabled', true);
	
	Swal.fire({
		icon: 'info',
		title: 'Restore is starting',
		text: 'Just a moment while the restore session is starting...',
		allowOutsideClick: false,
	})

    $.post('veeam.php', {'action' : 'startrestore', 'json' : json, 'id' : oid}).done(function(data) {
        if (data.match(/([a-zA-Z0-9]{8})-([a-zA-Z0-9]{4})-([a-zA-Z0-9]{4})-([a-zA-Z0-9]{4})-([a-zA-Z0-9]{12})/g)) {
			Swal.fire({
				icon: 'success',
				title: 'Session started',
				text: 'Restore session has been started and you can now perform restores'
			}).then(function(e) {
				window.location.href = 'exchange';
			});
        } else {
            Swal.fire({
				icon: 'error',
				title: 'Error starting restore session',
				text: '' + data
			})
			
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
		title: 'Stop the restore session?',
		text: 'This will terminate any restore options for the specific point in time',
		showCancelButton: true,
		confirmButtonText: 'Stop',
		cancelButtonText: 'Cancel',
	}).then(function(result) {
		if (result.isConfirmed) {
			$.post('veeam.php', {'action' : 'stoprestore', 'rid' : rid}).done(function(data) {
				if (data === 'success') {
					swalWithBootstrapButtons.fire({
						icon: 'success', 
						title: 'Restore session was stopped',
						text: 'The restore session was stopped successfully',
					}).then(function(e) {
						window.location.href = 'exchange';
					});
				} else {
					var response = JSON.parse(data);
				
					swalWithBootstrapButtons.fire({
						icon: 'error', 
						title: 'Failed to stop restore session',
						text: '' + response.slice(0, -1),
					}).then(function(e) {
						window.location.href = 'exchange';
					});
				}
			});
		  } else {
			return;
		}
	})
});

$('hide.bs.dropdown').dropdown(function(e) {
    $(e.target).find('>.dropdown-menu:first').slideUp();
});
$('show.bs.dropdown').dropdown(function(e) {
    $(e.target).find('>.dropdown-menu:first').slideDown();
});

$('#chk-all').click(function(e) {
    var table = $(e.target).closest('table');
    $('tr:visible :checkbox', table).prop('checked', this.checked);
});

$('#search-mailbox').keyup(function(e) {
    var searchText = $(this).val().toLowerCase();
    
    $.each($('#table-exchange-items tbody tr'), function(e) {
        if ($(this).text().toLowerCase().indexOf(searchText) === -1) {
           $(this).hide();
        } else {
           $(this).show();
        }
    });
});

$('ul#ul-exchange-users li').click(function(e) {
    $(this).parent().find('li.active').removeClass('active');
    $(this).addClass('active');
});

$('.load-more-folders').click(function(e) {
    var mailboxid = $(this).data('mailboxid');
    var offset = $(this).data('offset');
    
	loadMailboxFolders(mailboxid, offset);
});
$('.load-more-items').click(function(e) {
    var folderid = $(this).data('folderid');
    var mailboxid = $(this).data('mailboxid');
    var offset = $(this).data('offset');
	var type = $(this).data('type');
    
	loadMessages(mailboxid, folderid, offset);
});
$('.load-more-mailboxes').click(function(e) {
    var offset = $(this).data('offset');
    var org = $(this).data('org');
	
	loadMailboxes(org, offset);
});

function downloadMsg(itemid, mailboxid, mailsubject) {
    var rid = '<?php echo $rid; ?>';
    var json = '{ "savetoMsg": null }';
	
	Swal.fire({
		icon: 'info',
		title: 'Download is starting',
		text: 'Download will start soon',
		allowOutsideClick: false,
	})
    
	$.post('veeam.php', {'action': 'exportmailitem', 'itemid' : itemid, 'mailboxid' : mailboxid, 'rid' : rid, 'json' : json}).done(function(data) {	
		if (data) {
			$.redirect('download.php', {ext : 'msg', file : data, name : mailsubject}, 'POST');
		} else {
			Swal.fire({
				icon: 'error',
				title: 'Export failed',
				text: 'Export failed.'
			})
			return;
		}
	});
}

function downloadPST(itemid, mailboxid, mailsubject, type) {
    var rid = '<?php echo $rid; ?>';
	
	Swal.fire({
		icon: 'info',
		title: 'Download is starting',
		text: 'Export in progress and your download will start soon',
		allowOutsideClick: false,
	})
	
	if (type == 'multiple') {
		var act = 'exportmultiplemailitems';
		var ids = '';
		var mailsubject = 'exported-mailitems-' + mailsubject;
		
		if ($("input[name='checkbox-mail']:checked").length === 0) {
			Swal.close();
			
			Swal.fire({
				icon: 'info',
				title: 'Unable to restore',
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
		if (type == 'single') {
			var act = 'exportmailitem';
		} else {
			var act = 'exportmailbox';
			var mailsubject = 'mailbox-' + mailsubject;
		}
		
		var json = '{ \
			"ExportToPst": { \
				"EnablePstSizeLimit": "false", \
			} \
		}';
	}

	$.post('veeam.php', {'action' : act, 'itemid' : itemid, 'mailboxid' : mailboxid, 'rid' : rid, 'json' : json}).done(function(data) {
		if (data && data != 500) {
			$.redirect('download.php', {'ext' : 'pst', 'file' : data, 'name' : mailsubject}, 'POST');
			
			Swal.close();
		} else {
			Swal.fire({
				icon: 'error',
				title: 'Export failed',
				text: '' + data
			})
		}
			
		return;
	});
}

function restoreToDifferent(itemid, mailboxid, type) {
    var rid = '<?php echo $rid; ?>';
	
	if (type == 'multiple' && $("input[name='checkbox-mail']:checked").length == 0) {
		Swal.fire({
			icon: 'info',
			title: 'Unable to restore',
			text: 'No items have been selected.'
		})
		return;
	}
	
	const swalWithBootstrapButtons = Swal.mixin({
	  customClass: {
		  confirmButton: 'btn btn-success btn-margin-restore',
		  cancelButton: 'btn btn-danger'
      },
	  buttonsStyling: false,
	  input: 'text',
	});
	
	swalWithBootstrapButtons.fire({
		title: 'Restore to different location',
		html: 
			'<form method="POST">' +
			'<div class="form-group row">' +
			'<label for="restore-different-mailbox" class="col-sm-4 text-left">Target mailbox:</label>' +
			'<div class="col-sm-8"><input type="text" class="form-control restoredata" id="restore-different-mailbox" placeholder="user@example.onmicrosoft.com"></div>' +
			'</div>' +
			'<div class="form-group row">' +
			'<label for="restore-different-server" class="col-sm-4 text-left">Target server:</label>' +
			'<div class="col-sm-8"><input type="text" class="form-control restoredata" id="restore-different-server" placeholder="outlook.office365.com" value="outlook.office365.com"></div>' +
			'</div>' +
			'<div class="form-group row">' +
			'<label for="restore-different-user" class="col-sm-4 text-left">Username:</label>' +
			'<div class="col-sm-8"><input type="text" class="form-control restoredata" id="restore-different-user" placeholder="user@example.onmicrosoft.com"></div>' +
			'</div>' +
			'<div class="form-group row">' +
			'<label for="restore-different-pass" class="col-sm-4 text-left">Password:</label>' +
			'<div class="col-sm-8"><input type="password" class="form-control restoredata" id="restore-different-pass" placeholder="password"></div>' +
			'</div>' + 
			'<div class="form-group row">' + 
			'<label for="restore-different-folder" class="col-sm-4 text-left">Folder:</label>' +
			'<div class="col-sm-8"><input type="text" class="form-control" id="restore-different-folder" placeholder="Custom folder (optional)">' +
			'<h6 class="text-left">By default items will be restored in a folder named <em>Restored-via-web-client</em>.</h6>' +
			'</div>' +
			'</div>' +
			'</form>',
		confirmButtonText: 'Restore',
		cancelButtonText: 'Cancel',
		allowOutsideClick: false,
		focusConfirm: false,
		reverseButtons: true,
		showCancelButton: true,
		inputValidator: () => {
			var restoredata = Object.values(document.getElementsByClassName('restoredata'));
			var errors = [ 'No target mailbox defined', 'No target mailbox server defined', 'No username defined', 'No password defined' ];
			
			for (var i = 0; i < restoredata.length; i++) {
				if (!restoredata[i].value)
					return errors[i];
			}
		},
		willOpen: function (dom) {
			swalWithBootstrapButtons.getInput().style.display = 'none';
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
		if (result.isConfirmed) {
			var user = $('#restore-different-user').val();
			var pass = $('#restore-different-pass').val();
			var server = $('#restore-different-server').val();
			var folder = $('#restore-different-folder').val();
			var mailbox = $('#restore-different-mailbox').val();
			
			Swal.fire({
				icon: 'info',
				title: 'Item restore',
				text: 'Restore in progress...',
				allowOutsideClick: false,
			})
			
			if (typeof folder === undefined || !folder) {
				folder = 'Restored-via-web-client';
			}
			
			if (type == 'multiple') {
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
				if (type == 'single') {
					var act = 'restoremailitem';
				} else {
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

			$.post('veeam.php', {'action' : act, 'itemid' : itemid, 'mailboxid' : mailboxid, 'rid' : rid, 'json' : json}).done(function(data) {
				var response = JSON.parse(data);
				
				if (response['restoreFailed'] === undefined) {
					var result = '';
				
					if (response['createdItemsCount'] >= '1') {
						result += response['createdItemsCount'] + ' item(s) successfully created<br>';
					}
					
					if (response['mergedItemsCount'] >= '1') {
						result += response['mergedItemsCount'] + ' item(s) merged<br>';
					}
					
					if (response['failedItemsCount'] >= '1') {
						result += response['failedItemsCount'] + ' item(s) failed<br>';
					}
					
					if (response['skippedItemsCount'] >= '1') {
						result += response['skippedItemsCount'] + ' item(s) skipped';
					}
				
					Swal.fire({
						icon: 'info',
						title: 'Item restore',
						html: '' + result,
						allowOutsideClick: false,
					})
				} else {
					Swal.fire({
						icon: 'info',
						title: 'Item restore',
						html: 'Restore failed: ' + response['restoreFailed'],
						allowOutsideClick: false,
					})
				}
			});
		} else {
			return;
		}
	});
} 

function restoreToOriginal(itemid, mailboxid, type) {
    var rid = '<?php echo $rid; ?>';

	if (type == 'multiple' && $("input[name='checkbox-mail']:checked").length == 0) {
		Swal.fire({
			icon: 'info',
			title: 'Unable to restore',
			text: 'No items have been selected.'
		})
		return;
	}
	
	const swalWithBootstrapButtons = Swal.mixin({
 	  title: 'Restore to original location',
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
		text: 'Select authentication method',
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
							
							Swal.fire({
								icon: 'info',
								title: 'Item restore',
								text: 'Restore in progress...',
								allowOutsideClick: false,
							})

							if (type == 'multiple') {
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
								if (type == 'single') {
									var act = 'restoremailitem';
								} else if (type == 'full') {
									var act = 'restoremailbox';
								}
								
								var json = '{ "restoretoOriginallocation": \
									{ "userName": "' + user + '", \
									  "userPassword": "' + pass + '", \
									} \
								}';
							}
							
							$.post('veeam.php', {'action' : act, 'itemid' : itemid, 'mailboxid' : mailboxid, 'rid' : rid, 'json' : json}).done(function(data) {
								var response = JSON.parse(data);
								
								if (response['restoreFailed'] === undefined) {
									var result = '';
								
									if (response['createdItemsCount'] >= '1') {
										result += response['createdItemsCount'] + ' item(s) successfully created<br>';
									}
									
									if (response['mergedItemsCount'] >= '1') {
										result += response['mergedItemsCount'] + ' item(s) merged<br>';
									}
									
									if (response['failedItemsCount'] >= '1') {
										result += response['failedItemsCount'] + ' item(s) failed<br>';
									}
									
									if (response['skippedItemsCount'] >= '1') {
										result += response['skippedItemsCount'] + ' item(s) skipped';
									}
								
									Swal.fire({
										icon: 'info',
										title: 'Item restore',
										html: '' + result,
										allowOutsideClick: false,
									})
								} else {
									Swal.fire({
										icon: 'info',
										title: 'Item restore',
										html: 'Restore failed: ' + response['restoreFailed'],
										allowOutsideClick: false,
									})
								}
							});
						}
					});
				} else {
					swalWithBootstrapButtons.fire({
						html: 
							'<form class="form-horizontal">' +
							'<div class="form-group margin-left">' +
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
							var json = '{ "targetApplicationId" : "' + applicationid + '", }';
							
							clipboard.on('success', function(e) {
							  setTooltip('Copied!');
							  hideTooltip();
							});
							clipboard.on('error', function(e) {
							  setTooltip('Failed!');
							  hideTooltip();
							});
							
							Swal.fire({
								title: 'Restore to original location',
								text: 'Loading, please wait...',
							});
							
							$.post('veeam.php', {'action' : 'getrestoredevicecode', 'rid' : rid, 'json' : json}).done(function(data) {
								var response = JSON.parse(data);
								var usercode = response['userCode'];

								swalWithBootstrapButtons.fire({
									html: 
										'<form class="form-horizontal">' +
										'<div class="form-group text-left margin-left">' +
										'<span>To continue, open <a href="https://microsoft.com/devicelogin" target="_blank">https://microsoft.com/devicelogin</a> and enter the below code to authenticate.</span><br><br>' +
										'<div class="row">' +
										'<div class="col-sm-4"><input type="text" class="form-control" id="restore-original-usercode" value="' + usercode + '" readonly></div>' +
										'<div class="col-sm-8"><button type="button" class="btn form-check" id="btn-copy" data-clipboard-target="#restore-original-usercode" data-placement="right">Copy to clipboard</button></div><br><br>' +
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
											title: 'Item restore',
											text: 'Restore in progress...',
											allowOutsideClick: false,
										})

										if (type == 'multiple') {
											var act = 'restoremultiplemailitems';
											var ids = '';
											
											$("input[name='checkbox-mail']:checked").each(function(e) {
												ids = ids + '{ "Id": "' + this.value + '" }, ';
											});
											
											var json = '{ "restoretoOriginallocation": \
												{ "userCode": "' + usercode + '", \
												  "items": [ \
													' + ids + ' \
												\ ] \
												} \
											}';
										} else {
											if (type == 'single') {
												var act = 'restoremailitem';
											} else if (type == 'full') {
												var act = 'restoremailbox';
											}
											
											var json = '{ "restoretoOriginallocation": \
												{ "userCode": "' + usercode + '", \
												} \
											}';
										}
										
										$.post('veeam.php', {'action' : act, 'itemid' : itemid, 'mailboxid' : mailboxid, 'rid' : rid, 'json' : json}).done(function(data) {
											var response = JSON.parse(data);
											
											if (response['restoreFailed'] === undefined) {
												var result = '';
											
												if (response['createdItemsCount'] >= '1') {
													result += response['createdItemsCount'] + ' item(s) successfully created<br>';
												}
												
												if (response['mergedItemsCount'] >= '1') {
													result += response['mergedItemsCount'] + ' item(s) merged<br>';
												}
												
												if (response['failedItemsCount'] >= '1') {
													result += response['failedItemsCount'] + ' item(s) failed<br>';
												}
												
												if (response['skippedItemsCount'] >= '1') {
													result += response['skippedItemsCount'] + ' item(s) skipped';
												}
											
												Swal.fire({
													icon: 'info',
													title: 'Item restore',
													html: '' + result,
													allowOutsideClick: false,
												})
											} else {
												Swal.fire({
													icon: 'info',
													title: 'Item restore',
													html: 'Restore failed: ' + response['restoreFailed'],
													allowOutsideClick: false,
												})
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

function hideTooltip() {
  setTimeout(function() {
	  $('#btn-copy').tooltip('hide');
  }, 1000);
}

function setTooltip(message) {
  $('#btn-copy').tooltip('hide').attr('data-original-title', message).tooltip('show');
}

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

function fillTable(response, mailboxid) {
    if (response.results.length !== 0) {
		for (var i = 0; i < response.results.length; i++) {
			if (response.results[i].itemClass != 'IPM.Appointment') {
				var itemid = response.results[i].id;
				var mailsubject = response.results[i].subject;
				
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
					<li><a class="dropdown-link" href="javascript:void(0);" onclick="downloadMsg(\'' + itemid + '\', \'' + mailboxid + '\', \'' + mailsubject + '\')"><i class="fa fa-download"></i> MSG file</a></li> \
					<li><a class="dropdown-link" href="javascript:void(0);" onclick="downloadPST(\'' + itemid + '\', \'' + mailboxid + '\', \'' + mailsubject + '\', \'single\')"><i class="fa fa-download"></i> PST file</a></li> \
					<li class="divider"></li> \
					<li class="dropdown-header">Restore to</li> \
					<li><a class="dropdown-link" href="javascript:void(0);" onclick="restoreToOriginal(\'' + itemid + '\', \'' + mailboxid + '\', \'single\')"><i class="fa fa-upload"></i> Original location</a></li> \
					<li><a class="dropdown-link" href="javascript:void(0);" onclick="restoreToDifferent(\'' + itemid + '\', \'' + mailboxid + '\', \'single\')"><i class="fa fa-upload"></i> Different location</a></li> \
					</ul> \
					</div> \
					</td> \
					</tr>');
			} else {
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
					<li><a class="dropdown-link" href="javascript:void(0);" onclick="downloadMsg(\'' + itemid + '\', \'' + mailboxid + '\', \'' + mailsubject + '\')"><i class="fa fa-download"></i> MSG file</a></li> \
					<li><a class="dropdown-link" href="javascript:void(0);" onclick="downloadPST(\'' + itemid + '\', \'' + mailboxid + '\', \'' + mailsubject + '\', \'single\')"><i class="fa fa-download"></i> PST file</a></li> \
					<li class="divider"></li> \
					<li class="dropdown-header">Restore to</li> \
					<li><a class="dropdown-link" href="javascript:void(0);" onclick="restoreToOriginal(\'' + itemid + '\', \'' + mailboxid + '\', \'single\')"><i class="fa fa-upload"></i> Original location</a></li> \
					<li><a class="dropdown-link" href="javascript:void(0);" onclick="restoreToDifferent(\'' + itemid + '\', \'' + mailboxid + '\', \'single\')"><i class="fa fa-upload"></i> Different location</a></li> \
					</ul> \
					</div> \
					</td> \
					</tr>');
			}
		}
	}
}

function loadMailboxes(org, offset) {
	var rid = '<?php echo $rid; ?>';
	
    $.post('veeam.php', {'action' : 'getmailboxes', 'offset' : offset, 'rid' : rid}).done(function(data) {
        var response = JSON.parse(data);

        if (response.results.length != 0) {
			for (var i = 0; i < response.results.length; i++) {
				if ($('#table-exchange-mailboxes').length > 0){
					$('#table-exchange-mailboxes tbody').append('<tr> \
						<td><a href="exchange/' + org + '/' + response.results[i].id + '">' + response.results[i].name + '</a></td> \
						<td>' + response.results[i].email + '</td> \
						<td class="text-center"> \
						<div class="btn-group dropdown"> \
						<button class="btn btn-default dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">Options <span class="caret"></span></button> \
						<ul class="dropdown-menu dropdown-menu-right"> \
						<li class="dropdown-header">Download as</li> \
						<li><a class="dropdown-link" href="javascript:void(0);" onclick="downloadPST(\'' + response.results[i].name + '\', \'' + response.results[i].id + '\', \'' + response.results[i].name + '\', \'full\')"><i class="fa fa-download"></i> PST file</a></li> \
						<li class="divider"></li> \
						<li class="dropdown-header">Restore to</li> \
						<li><a class="dropdown-link" href="javascript:void(0);" onclick="restoreToOriginal(\'' + response.results[i].name + '\', \'' + response.results[i].id + '\', \'full\')"><i class="fa fa-upload"></i> Original location</a></li> \
						<li><a class="dropdown-link" href="javascript:void(0);" onclick="restoreToDifferent(\'' + response.results[i].name + '\', \'' + response.results[i].id + '\', \'full\')"><i class="fa fa-upload"></i> Different location</a></li> \
						</ul> \
						</div> \
						</td> \
						</tr>');
				}
				
				$('#ul-exchange-users').append('<li><a href="exchange/' + org + '/' + response.results[i].id + '">' + response.results[i].name + '</a></li>');
			}
			
			if (response.results.length >= 50) {
				$('a.load-more-mailboxes').data('offset', offset + 50);
			} else {
				$('a.load-more-mailboxes').addClass('hide');
			}
		}
    });
}

function loadMailboxFolders(mailboxid, offset) {
	var rid = '<?php echo $rid; ?>';
	
    $.post('veeam.php', {'action' : 'getmailboxfolders', 'mailboxid' : mailboxid, 'offset' : offset, 'rid' : rid}).done(function(data) {
        var response = JSON.parse(data);

        if (response.results.length != 0) {
			var icon, type;
			
			for (var i = 0; i < response.results.length; i++) {
				type = response.results[i].type;
				
				switch (type.toLowerCase()) {
					case 'appointment':
						icon = 'far fa-calendar-check';
						break;
					case 'contact':
						icon = 'far fa-address-book';
						break;
					case 'journal':
						icon = 'fa fa-book';
						break;
					case 'stickynote':
						icon = 'far fa-sticky-note';
						break;
					case 'task':
						icon = 'fa fa-tasks';
						break;
					default:
						icon = 'far fa-folder';
				}
				
				$('#jstree').jstree('create_node', '#', {data: {"folderid" : response.results[i].id, "mailboxid" : mailboxid, "jstree" : {"icon" : icon}}, text: response.results[i].name});
			}
			
			if (response.results.length >= 50) {
				$('a.load-more-folders').data('offset', offset + 50);
			} else {
				$('a.load-more-folders').addClass('hide');
			}
		}
    });
}

function loadMailboxItems(folderid, mailboxid, parent) {
	var rid = '<?php echo $rid; ?>';
	
	disableTree();
	
	$.post('veeam.php', {'action' : 'getmailboxfolders', 'folderid' : folderid, 'mailboxid' : mailboxid, 'offset' : 0, 'limit' : 250, 'rid' : rid}).done(function(data) {
		var responsefolders = JSON.parse(data);
		
		if (parent !== null) {
			if (responsefolders.results.length !== 0) {
				var node = $('#jstree').jstree('get_selected');
				var children = $('#jstree').jstree('get_children_dom', node);
				var responsefolderid, responsefoldername;
				
				if (children.length === 0) {
					for (var i = 0; i < responsefolders.results.length; i++) {
						responsefolderid = responsefolders.results[i].id;
						responsefoldername = responsefolders.results[i].name;
						
						$('#jstree').jstree('create_node', parent, {data: {"folderid" : responsefolderid, "mailboxid" : mailboxid, "jstree" : {"icon" : "far fa-folder"}}, text: responsefoldername});
						
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
							$('#jstree').jstree('create_node', node, {data: {"folderid" : responsefolderid, "mailboxid" : mailboxid, "jstree" : {"icon" : "far fa-folder"}}, text: responsefoldername});
							
							$('#jstree').on('create_node.jstree', function (e, data) {
								$('#jstree').jstree('open_node', data.parent);
							});
						}
					}
				}
			}
		}
	});
	
	$('#table-exchange-items tbody').empty();
	$('#loader').removeClass('hide');
	$('a.load-more-items').addClass('hide');
	
    $.post('veeam.php', {'action' : 'getmailitems', 'folderid' : folderid, 'mailboxid' : mailboxid, 'rid' : rid}).done(function(data) {
        var responseitems = JSON.parse(data);

        if (typeof responseitems !== 'undefined' && responseitems.results.length != 0) {
            fillTable(responseitems, mailboxid);
			
			if (responseitems.results.length >= 50) {
				$('a.load-more-items').removeClass('hide');
				$('a.load-more-items').data('folderid', folderid);
			} else {
				$('a.load-more-items').addClass('hide');
			}
		} else {
			$('#table-exchange-items tbody').append('<tr><td class="text-center" colspan="6">No items available in this folder.</td></tr>');
		}
		
		$('#loader').addClass('hide');
		enableTree();
    });
}

function loadMessages(mailboxid, folderid, offset) {
	var rid = '<?php echo $rid; ?>';
	
    $.post('veeam.php', {'action' : 'getmailitems', 'folderid' : folderid, 'offset' : offset, 'mailboxid' : mailboxid, 'rid' : rid}).done(function(data) {
        var response = JSON.parse(data);

        if (response.results.length != 0) {
            fillTable(response, mailboxid);
			
			if (response.results.length >= 50) {
				$('a.load-more-items').removeClass('hide');
				$('a.load-more-items').data('offset', offset + 50);
			} else {
				$('a.load-more-items').addClass('hide');
			}
		} else {
			$('#table-exchange-items tbody').append('<tr><td class="text-center" colspan="6">No more items available in this folder.</td></tr>');
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
			icon: 'info',
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