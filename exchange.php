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
    <link rel="stylesheet" type="text/css" href="css/exchange.css" />
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
	  <li class="active"><a href="exchange">Exchange</a></li>
	  <?php
	  if (!isset($_SESSION['rtype'])) {
	  ?>
	  <li><a href="onedrive">OneDrive</a></li>
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
		<div class="logo-container"><i class="logo fa fa-envelope"></i></div>
		<div class="separator"></div>
		<menu class="menu-segment" id="menu">
		<?php
		if (!isset($_SESSION['rid'])) {
			echo '<ul id="ul-exchange-users">';
			
			if (strtolower($authtype) !== 'mfa' && $check === false && strtolower($administrator) === 'yes') {
				if (isset($_GET['oid'])) $oid = $_GET['oid'];
				
				$org = $veeam->getOrganizations();				
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
				$users = $veeam->getMailboxes($rid);

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
						echo '<li class="active"><a href="exchange/' . $oid . '/' . $value['id'] . '">' . $value['name'] . '</a></li>';
					} else {
						echo '<li><a href="exchange/' . $oid . '/' . $value['id'] . '">' . $value['name'] . '</a></li>';
					}
				}
				
				echo '</ul>';

				if (count($users['results']) >= $limit) {
					echo '<div class="text-center">';
					echo '<a class="btn btn-default load-more-link load-more-mailboxes" data-org="' . $oid . '" data-offset="' . count($users['results']) . '" href="' . $_SERVER['REQUEST_URI'] . '#">Load more mailboxes</a>';
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
		<h1>Exchange</h1>
		<div class="exchange-container">
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
				if (strtolower($authtype) === 'mfa' && $check === false) {
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
					
					if (isset($usersorted) && count($usersorted) !== 0) {
					?>
						<div class="alert alert-info">The following is a limited overview with backed up accounts and their Exchange objects within the organization. To view the full list, start a restore session.</div>
						<table class="table table-bordered table-padding table-striped" id="table-exchange-mailboxes">
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
					$owner = $veeam->getMailboxID($rid, $uid);
					$folders = $veeam->getMailboxFolders($rid, $uid);
					$parentfolders = array();
					
					for ($i = 0; $i < count($folders['results']); $i++) {
						if (empty($folders['results'][$i]['_links']['parent'])) {
							array_push($parentfolders, array('name' => $folders['results'][$i]['name'], 'id' => $folders['results'][$i]['id'], 'type' => $folders['results'][$i]['type']));
						}
					}
					?>
					<div class="row">
						<div class="col-sm-2 zeroPadding">
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
														
														echo '<li data-folderid="'.$parentfolders[$i]['id'].'" data-jstree=\'{ "opened" : true, "icon" : "' . $icon . '" }\'>'.$parentfolders[$i]['name'].'</li>';
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
													if (data === undefined || data.node === undefined || data.node.id === undefined || data.node.data.folderid === undefined)
														return;

													var folderid = data.node.data.folderid;
													var parent = data.node.id;
													
													loadMailboxItems(folderid, parent);
												});
											});
											</script>
											<?php
											if (count($folders['results']) >= $limit) {
												echo '<div class="text-center">';
												echo '<a class="btn btn-default load-more-link load-more-folders" data-offset="' . count($folders['results']) . '" href="' . $_SERVER['REQUEST_URI'] . '#">Load more folders</a>';
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
							<div class="exchange-controls-padding hide" id="exchange-controls">
								<input class="form-control search" id="search-mailbox" placeholder="Filter by item...">
								<div class="form-inline">
									<strong class="btn-group">Items:</strong>
									<div class="btn-group dropdown">
										<button class="btn-link dropdown-toggle" data-toggle="dropdown">Export <span class="caret"></span></button>
										<ul class="dropdown-menu">
										  <li><a class="dropdown-link" href="javascript:void(0);" onclick="downloadPST('fullfolder', '<?php echo $owner['name']; ?>', 'fullfolder')"><i class="fa fa-download"></i> All items</a></li>
										  <li><a class="dropdown-link" href="javascript:void(0);" onclick="downloadPST('multipleexport', '<?php echo $owner['name']; ?>', 'multiple')"><i class="fa fa-download"></i> Selected items</a></li>
										</ul>
									</div>
									<div class="btn-group dropdown">
										<button class="btn btn-link dropdown-toggle" data-toggle="dropdown">Restore <span class="caret"></span></button>
										<ul class="dropdown-menu">
										  <li class="dropdown-header">All items</li>
										  <li><a class="dropdown-link" href="javascript:void(0);" onclick="restoreToOriginal('multiplerestore', 'fullfolder')"><i class="fa fa-upload"></i> Original location</a></li>
										  <li><a class="dropdown-link" href="javascript:void(0);" onclick="restoreToDifferent('multiplerestore', 'fullfolder')"><i class="fa fa-upload"></i> Different location</a></li>
										  <li class="divider"></li>
										  <li class="dropdown-header">Selected items</li>
										  <li><a class="dropdown-link" href="javascript:void(0);" onclick="restoreToOriginal('multiplerestore', 'multiple')"><i class="fa fa-upload"></i> Original location</a></li>
										  <li><a class="dropdown-link" href="javascript:void(0);" onclick="restoreToDifferent('multiplerestore', 'multiple')"><i class="fa fa-upload"></i> Different location</a></li>
										</ul>
									</div>
								</div>
							</div>
							<table class="table table-hover table-bordered table-striped table-border" id="table-exchange-items">
								<thead>
									<tr>
										<th class="text-center"><input type="checkbox" id="chk-all" title="Select all"></th>
										<th class="text-center">Type</th>
										<th>From</th>
										<th>Subject</th>
										<th>Received</th>
										<th class="text-center">Options</th>
									</tr>
								</thead>
								<tbody>
									<tr>
										<td colspan="6">Select a folder to browse specific items.</td>
									</tr>
								</tbody>
							</table>
							<div class="text-center">
								<a class="btn btn-default hide load-more-link load-more-items" data-folderid="null" data-offset="<?php echo $limit; ?>" href="<?php echo $_SERVER['REQUEST_URI']; ?>#">Load more messages</a>
							</div>
						</div>
					</div>
					<?php
				} else {
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
								<td><a href="exchange/<?php echo $oid; ?>/<?php echo $value['id']; ?>"><?php echo $value['name']; ?></a></td>
								<td><?php echo $value['email']; ?></td>
								<td class="text-center">
									<div class="btn-group dropdown">
										<button class="btn btn-default dropdown-toggle" data-toggle="dropdown">Restore <span class="caret"></span></button>
										<ul class="dropdown-menu dropdown-menu-right">
										  <li class="dropdown-header">Export to</li>
										  <li><a class="dropdown-link" href="javascript:void(0);" onclick="downloadPST('<?php echo $value['id']; ?>', '<?php echo $value['name']; ?>', 'full')"><i class="fa fa-download"></i> PST file</a></li>
										  <li class="divider"></li>
										  <li class="dropdown-header">Restore to</li>
										  <li><a class="dropdown-link" href="javascript:void(0);" onclick="restoreToOriginal('<?php echo $value['id']; ?>', 'full')"><i class="fa fa-upload"></i> Original location</a></li>
										  <li><a class="dropdown-link" href="javascript:void(0);" onclick="restoreToDifferent('<?php echo $value['id']; ?>', 'full')"><i class="fa fa-upload"></i> Different location</a></li>
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
						echo '<a class="btn btn-default load-more-link load-more-mailboxes" data-org="' . $oid . '" data-offset="' . count($users['results']) . '" href="' . $_SERVER['REQUEST_URI'] . '#">Load more mailboxes</a>';
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

    var json = '{ "explore": { "datetime": "' + pit + '", "type": "vex", "ShowAllVersions": "true", "ShowDeleted": "true" } }';

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
				window.location.href = 'exchange';
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
						window.location.href = 'exchange';
					});
				} else {
					var response = JSON.parse(data);
				
					swalWithBootstrapButtons.fire({
						icon: 'error', 
						title: 'Restore session',
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

$('.load-more-folders').click(function(e) {
    var offset = $(this).data('offset');
    
	loadMailboxFolders(offset);
});
$('.load-more-items').click(function(e) {
    var folderid = $(this).data('folderid');
    var offset = $(this).data('offset');
	var type = $(this).data('type');
    
	loadMessages(folderid, offset);
});
$('.load-more-mailboxes').click(function(e) {
    var offset = $(this).data('offset');
    var org = $(this).data('org');
	
	loadMailboxes(org, offset);
});

function downloadMSG(itemid, mailsubject) {
    var rid = '<?php echo $rid; ?>';
	var mailboxid = '<?php echo $uid; ?>';
    var json = '{ "savetoMsg": null }';
	
	Swal.fire({
		icon: 'info',
		title: 'Export',
		text: 'Download will start soon',
		allowOutsideClick: false,
	});
    
	$.post('veeam.php', {'action': 'exportmailitem', 'rid' : rid, 'mailboxid' : mailboxid, 'itemid' : itemid, 'json' : json}).done(function(data) {	
		var response = JSON.parse(data);
		
		if (response['exportFailed'] === undefined) {
			if (response['exportFile'] !== undefined) {
				var file = response['exportFile'];
				
				$.redirect('download.php', {'ext' : 'msg', 'file' : file, 'name' : mailsubject}, 'POST');
				
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

function downloadPST(itemid, mailsubject, type) {
    var rid = '<?php echo $rid; ?>';
	<?php
	if (isset($uid)) {
	?>
	var mailboxid = '<?php echo $uid; ?>';
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
		var act = 'exportmultiplemailitems';
		var ids = '';
		var mailsubject = 'exported-mailitems-' + mailsubject;
		
		if ($("input[name='checkbox-mail']:checked").length === 0) {
			Swal.close();
			Swal.fire({
				icon: 'info',
				title: 'Export',
				text: 'Cannot export items. No items have been selected'
			});
			
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
		} else if (type == 'fullfolder') {
			var act = 'exportmailfolder';		
			var node = $('#jstree').jstree('get_selected', true);
			var itemid = node[0].data.folderid;
			var mailsubject = 'mailbox-folder-' + mailsubject;
		} else {
			var act = 'exportmailbox';	
			var mailboxid = itemid;
			var mailsubject = 'mailbox-' + mailsubject;
		}
		
		var json = '{ \
			"ExportToPst": { \
				"EnablePstSizeLimit": "false", \
			} \
		}';
	}

	$.post('veeam.php', {'action' : act, 'rid' : rid, 'mailboxid' : mailboxid, 'itemid' : itemid, 'json' : json}).then(function(data) {
		var response = JSON.parse(data);
		
		if (response['exportFailed'] === undefined) {
			if (response['exportFile'] !== undefined) {
				var file = response['exportFile'];
				
				$.redirect('download.php', {'ext' : 'pst', 'file' : file, 'name' : mailsubject}, 'POST');
				
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

function restoreToDifferent(itemid, type) {
    var rid = '<?php echo $rid; ?>';
	<?php
	if (isset($uid)) {
	?>
	var mailboxid = '<?php echo $uid; ?>';
	<?php
	}
	?>
	
	
	if (type === 'multiple' && $("input[name='checkbox-mail']:checked").length === 0) {
		Swal.fire({
			icon: 'info',
			title: 'Restore',
			text: 'Cannot restore items. No items have been selected'
		});
		
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
		title: 'Restore',
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
			'<h6 class="text-left">By default items will be restored in a folder named <em>Restored-emails</em>.</h6>' +
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
				title: 'Restore',
				text: 'Restore in progress...',
				allowOutsideClick: false,
			});
			
			if (typeof folder === undefined || !folder) {
				folder = 'Restored-emails';
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
				} else if (type == 'fullfolder') {
					var act = 'restoremailfolder';		
					var node = $('#jstree').jstree('get_selected', true);
					var itemid = node[0].data.folderid;
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

			$.post('veeam.php', {'action' : act, 'rid' : rid, 'mailboxid' : mailboxid, 'itemid' : itemid, 'json' : json}).done(function(data) {
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
		} else {
			return;
		}
	});
} 

function restoreToOriginal(itemid, type) {
    var rid = '<?php echo $rid; ?>';
	<?php
	if (isset($uid)) {
	?>
	var mailboxid = '<?php echo $uid; ?>';
	<?php
	}
	?>
	
	
	if (type == 'multiple' && $("input[name='checkbox-mail']:checked").length == 0) {
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
								title: 'Restore',
								text: 'Restore in progress...',
								allowOutsideClick: false,
							});

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
								} else if (type == 'fullfolder') {
									var act = 'restoremailfolder';		
									var node = $('#jstree').jstree('get_selected', true);
									var itemid = node[0].data.folderid;
								} else {
									var act = 'restoremailbox';
								}
								
								var json = '{ "restoretoOriginallocation": \
									{ "userName": "' + user + '", \
									  "userPassword": "' + pass + '", \
									} \
								}';
							}
							
							$.post('veeam.php', {'action' : act, 'rid' : rid, 'mailboxid' : mailboxid, 'itemid' : itemid, 'json' : json}).done(function(data) {
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
											} else if (type == 'fullfolder') {
												var act = 'restoremailfolder';		
												var node = $('#jstree').jstree('get_selected', true);
												var itemid = node[0].data.folderid;
											} else {
												var act = 'restoremailbox';
											}
											
											var json = '{ "restoretoOriginallocation": \
												{ "userCode": "' + usercode + '", \
												} \
											}';
										}
										
										$.post('veeam.php', {'action' : act, 'rid' : rid, 'mailboxid' : mailboxid, 'itemid' : itemid, 'json' : json}).done(function(data) {
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

function fillTable(response) {
	if (response.results.length !== 0) {
		for (var i = 0; i < response.results.length; i++) {
			if (response.results[i].itemClass !== 'IPM.Appointment') {
				var itemid = response.results[i].id;
				var mailsubject = response.results[i].subject;
				mailsubject = mailsubject.replace(/'/g, '');
				mailsubject = mailsubject.replace(/"/g, '');
				
				$('#table-exchange-items tbody').append('<tr> \
					<td class="text-center"><input type="checkbox" name="checkbox-mail" value="' + response.results[i].id + '"></td> \
					<td class="text-center"><span class="logo fa fa-envelope"></span></td> \
					<td>' + response.results[i].from + '</td> \
					<td>' + response.results[i].subject + '</td> \
					<td>' + moment(response.results[i].received).format('DD/MM/YYYY HH:mm') + '</td> \
					<td class="text-center"> \
					<div class="btn-group dropdown"> \
					<button class="btn btn-default dropdown-toggle" data-toggle="dropdown">Restore <span class="caret"></span></button> \
					<ul class="dropdown-menu dropdown-menu-right"> \
					<li class="dropdown-header">Export to</li> \
					<li><a class="dropdown-link" href="javascript:void(0);" onclick="downloadMSG(\'' + itemid + '\', \'' + mailsubject + '\')"><i class="fa fa-download"></i> MSG file</a></li> \
					<li><a class="dropdown-link" href="javascript:void(0);" onclick="downloadPST(\'' + itemid + '\', \'' + mailsubject + '\', \'single\')"><i class="fa fa-download"></i> PST file</a></li> \
					<li class="divider"></li> \
					<li class="dropdown-header">Restore to</li> \
					<li><a class="dropdown-link" href="javascript:void(0);" onclick="restoreToOriginal(\'' + itemid + '\', \'single\')"><i class="fa fa-upload"></i> Original location</a></li> \
					<li><a class="dropdown-link" href="javascript:void(0);" onclick="restoreToDifferent(\'' + itemid + '\', \'single\')"><i class="fa fa-upload"></i> Different location</a></li> \
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
					<button class="btn btn-default dropdown-toggle" data-toggle="dropdown">Restore <span class="caret"></span></button> \
					<ul class="dropdown-menu dropdown-menu-right"> \
					<li class="dropdown-header">Export to</li> \
					<li><a class="dropdown-link" href="javascript:void(0);" onclick="downloadMSG(\'' + itemid + '\', \'' + mailsubject + '\')"><i class="fa fa-download"></i> MSG file</a></li> \
					<li><a class="dropdown-link" href="javascript:void(0);" onclick="downloadPST(\'' + itemid + '\', \'' + mailsubject + '\', \'single\')"><i class="fa fa-download"></i> PST file</a></li> \
					<li class="divider"></li> \
					<li class="dropdown-header">Restore to</li> \
					<li><a class="dropdown-link" href="javascript:void(0);" onclick="restoreToOriginal(\'' + itemid + '\', \'single\')"><i class="fa fa-upload"></i> Original location</a></li> \
					<li><a class="dropdown-link" href="javascript:void(0);" onclick="restoreToDifferent(\'' + itemid + '\', \'single\')"><i class="fa fa-upload"></i> Different location</a></li> \
					</ul> \
					</div> \
					</td> \
					</tr>');
			}
		}
	}
}

function loadMailboxes(org, offset) {
	var limit = <?php echo $limit; ?>;
	var rid = '<?php echo $rid; ?>';
		
    $.post('veeam.php', {'action' : 'getmailboxes', 'rid' : rid, 'offset' : offset}).done(function(data) {
        var response = JSON.parse(data);

        if (typeof response !== undefined && response.results.length !== 0) {
			for (var i = 0; i < response.results.length; i++) {
				if ($('#table-exchange-mailboxes').length > 0){
					$('#table-exchange-mailboxes tbody').append('<tr> \
						<td><a href="exchange/' + org + '/' + response.results[i].id + '">' + response.results[i].name + '</a></td> \
						<td>' + response.results[i].email + '</td> \
						<td class="text-center"> \
						<div class="btn-group dropdown"> \
						<button class="btn btn-default dropdown-toggle" data-toggle="dropdown">Restore <span class="caret"></span></button> \
						<ul class="dropdown-menu dropdown-menu-right"> \
						<li class="dropdown-header">Export to</li> \
						<li><a class="dropdown-link" href="javascript:void(0);" onclick="downloadPST(\'' + response.results[i].id + '\', \'' + response.results[i].name + '\', \'full\')"><i class="fa fa-download"></i> PST file</a></li> \
						<li class="divider"></li> \
						<li class="dropdown-header">Restore to</li> \
						<li><a class="dropdown-link" href="javascript:void(0);" onclick="restoreToOriginal(\'' + response.results[i].name + '\', \'full\')"><i class="fa fa-upload"></i> Original location</a></li> \
						<li><a class="dropdown-link" href="javascript:void(0);" onclick="restoreToDifferent(\'' + response.results[i].name + '\', \'full\')"><i class="fa fa-upload"></i> Different location</a></li> \
						</ul> \
						</div> \
						</td> \
						</tr>');
				}
				
				$('#ul-exchange-users').append('<li><a href="exchange/' + org + '/' + response.results[i].id + '">' + response.results[i].name + '</a></li>');
			}
			
			if (response.results.length >= limit) {
				$('a.load-more-mailboxes').data('offset', offset + limit);
			} else {
				$('a.load-more-mailboxes').addClass('hide');
			}
		}
    });
}

<?php
	if (isset($uid)) {
?>
function loadMailboxFolders(offset) {
	var limit = <?php echo $limit; ?>;
	var mailboxid = '<?php echo $uid; ?>';
	var rid = '<?php echo $rid; ?>';
	
    $.post('veeam.php', {'action' : 'getmailboxfolders', 'rid' : rid, 'mailboxid' : mailboxid, 'offset' : offset}).done(function(data) {
        var response = JSON.parse(data);

        if (typeof response !== undefined && response.results.length !== 0) {
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
				
				$('#jstree').jstree('create_node', '#', {data: {"folderid" : response.results[i].id, "jstree" : {"icon" : icon}}, text: response.results[i].name});
			}
			
			if (response.results.length >= limit) {
				$('a.load-more-folders').data('offset', offset + limit);
			} else {
				$('a.load-more-folders').addClass('hide');
			}
		}
    });
}

function loadMailboxItems(folderid, parent) {
	var limit = <?php echo $limit; ?>;
	var mailboxid = '<?php echo $uid; ?>';
	var rid = '<?php echo $rid; ?>';
	
	disableTree();
	
	$.post('veeam.php', {'action' : 'getmailboxfolders', 'rid' : rid, 'folderid' : folderid, 'mailboxid' : mailboxid}).done(function(data) {
		var responsefolders = JSON.parse(data);
		
		if (parent !== null) {
			if (typeof responsefolders !== undefined && responsefolders.results.length !== 0) {
				var node = $('#jstree').jstree('get_selected');
				var children = $('#jstree').jstree('get_children_dom', node);
				var responsefolderid, responsefoldername;
				
				if (children.length === 0) {
					for (var i = 0; i < responsefolders.results.length; i++) {
						responsefolderid = responsefolders.results[i].id;
						responsefoldername = responsefolders.results[i].name;
						
						$('#jstree').jstree('create_node', parent, {data: {"folderid" : responsefolderid, "jstree" : {"icon" : "far fa-folder"}}, text: responsefoldername});
						
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
							$('#jstree').jstree('create_node', node, {data: {"folderid" : responsefolderid, "jstree" : {"icon" : "far fa-folder"}}, text: responsefoldername});
							
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
	
    $.post('veeam.php', {'action' : 'getmailboxitems', 'rid' : rid, 'mailboxid' : mailboxid, 'folderid' : folderid}).done(function(data) {
        var responseitems = JSON.parse(data);

        if (typeof responseitems !== undefined && responseitems.results.length !== 0) {
            fillTable(responseitems);
			$('#exchange-controls').removeClass('hide');
			
			if (responseitems.results.length >= limit) {
				$('a.load-more-items').removeClass('hide');
				$('a.load-more-items').data('folderid', folderid);
			} else {
				$('a.load-more-items').addClass('hide');
			}
		} else {
			$('#table-exchange-items tbody').append('<tr><td colspan="6">No items available.</td></tr>');
		}
		
		$('#loader').addClass('hide');
		enableTree();
    });
}

function loadMessages(folderid, offset) {
	var limit = <?php echo $limit; ?>;
	var mailboxid = '<?php echo $uid; ?>';
	var rid = '<?php echo $rid; ?>';
	
    $.post('veeam.php', {'action' : 'getmailboxitems', 'rid' : rid, 'mailboxid' : mailboxid, 'folderid' : folderid, 'offset' : offset}).done(function(data) {
        var response = JSON.parse(data);

        if (typeof response !== undefined && response.results.length !== 0) {
            fillTable(response);
			
			if (response.results.length >= limit) {
				$('a.load-more-items').removeClass('hide');
				$('a.load-more-items').data('offset', offset + limit);
			} else {
				$('a.load-more-items').addClass('hide');
			}
		} else {
			$('a.load-more-items').addClass('hide');
			$('#table-exchange-items tbody').append('<tr><td class="text-center" colspan="6">No more items available.</td></tr>');
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