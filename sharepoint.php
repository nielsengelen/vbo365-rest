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
	<link rel="stylesheet" type="text/css" href="css/sharepoint.css" />
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
	  <li><a href="onedrive">OneDrive</a></li>
	  <?php
	  }
	  ?>
	  <li class="active"><a href="sharepoint">SharePoint</a></li>
	  <?php
	  if (!isset($_SESSION['rtype'])) {
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
        <div class="logo-container"><i class="logo fa fa-share-alt"></i></div>
        <div class="separator"></div>
        <menu class="menu-segment" id="menu">
		<?php
		if (!isset($_SESSION['rid'])) {
			echo '<ul id="ul-sharepoint-organizations">';
			
			if (strtolower($authtype) !== 'mfa' && $check === false && strtolower($administrator) === 'yes') {
				if (isset($_GET['oid'])) $oid = $_GET['oid'];
				
				$org = $veeam->getOrganizations();
				$menu = false;
				
				for ($i = 0; $i < count($org); $i++) {
					if (isset($oid) && !empty($oid) && $oid == $org[$i]['id']) {
						echo '<li class="active"><a href="sharepoint/' . $org[$i]['id'] . '">' . $org[$i]['name'] . '</a></li>';
					} else {
						echo '<li><a href="sharepoint/' . $org[$i]['id'] . '">' . $org[$i]['name'] . '</a></li>';
					}
				}
			} else {
				$org = $veeam->getOrganization();
				$oid = $org['id'];
				$menu = true;
				$restoretype = 'tenant';
				
				echo '<li class="active"><a href="sharepoint">' . $org['name'] . '</a></li>';
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
				if (isset($_GET['sid'])) $sid = $_GET['sid'];
				
				$content = array();
				$org = $veeam->getOrganizationID($rid);
				$oid = $org['id'];
				
				echo '<button class="btn btn-default btn-danger btn-stop-restore" title="Stop Restore">Stop Restore</button>';
				echo '<div class="separator"></div>';

				if (isset($sid) && !empty($sid)) {
					if (isset($_GET['cid'])) $cid = $_GET['cid'];
					if (isset($_GET['type'])) $type = $_GET['type'];
					
					$libraries = $veeam->getSharePointContent($rid, $sid, 'libraries');
					$lists = $veeam->getSharePointContent($rid, $sid, 'lists');
					
					echo '<a href="sharepoint/' . $oid . '"><i class="fa fa-home"></i></a>';
					echo '<ul id="ul-sharepoint-sites">';
					echo '<div class="separator"></div>';

					for ($i = 0; $i < count($libraries['results']); $i++) {
						array_push($content, array('name' => $libraries['results'][$i]['name'], 'id' => $libraries['results'][$i]['id'], 'type' => 'library'));
					}

					for ($i = 0; $i < count($lists['results']); $i++) {
						array_push($content, array('name' => $lists['results'][$i]['name'], 'id' => $lists['results'][$i]['id'], 'type' => 'list'));
					}

					uasort($content, function($a, $b) {
						return strcasecmp($a['name'], $b['name']);
					});

					foreach ($content as $key => $value) {
						if (isset($cid) && !empty($cid) && ($cid == $value['id'])) {
							echo '<li class="active"><a data-type="' . $value['type'] . '" href="sharepoint/' . $oid . '/' . $sid . '/' . $value['id'] . '/' . $value['type'] . '">' . $value['name'] . '</a></li>';
						} else {
							echo '<li><a data-type="' . $value['type'] . '" href="sharepoint/' . $oid . '/' . $sid . '/' . $value['id'] . '/' . $value['type'] . '">' . $value['name'] . '</a></li>';
						}
					}

					echo '</ul>';
					
					if (count($libraries['results']) >= $limit || count($lists['results']) >= $limit) {
						echo '<div class="text-center">';
						echo '<a class="btn btn-default load-more-link load-more-content" data-org="' . $oid . '" data-offset="' . $limit . '" href="' . $_SERVER['REQUEST_URI'] . '#">Load more</a>';
						echo '</div>';
					}
				} else {
					$sites = $veeam->getSharePointSites($rid);

					echo '<a href="sharepoint/' . $oid . '"><i class="fa fa-home"></i></a>';
					echo '<ul id="ul-sharepoint-sites">';
					echo '<div class="separator"></div>';
				
					for ($i = 0; $i < count($sites['results']); $i++) {
						array_push($content, array('name'=> $sites['results'][$i]['name'], 'id' => $sites['results'][$i]['id']));
					}

					uasort($content, function($a, $b) {
						return strcasecmp($a['name'], $b['name']);
					});

					foreach ($content as $key => $value) {
						echo '<li><a href="sharepoint/' . $oid . '/' . $value['id'] . '">' . $value['name'] . '</a></li>';
					}
					
					echo '</ul>';
					
					if (count($sites['results']) >= $limit) {
						echo '<div class="text-center">';
						echo '<a class="btn btn-default load-more-link load-more-sites" data-org="' . $oid . '" data-offset="' . count($sites['results']) . '" href="' . $_SERVER['REQUEST_URI'] . '#">Load more sites</a>';
						echo '</div>';
					}
				}
			}
		}
		?>
        </menu>
        <div class="separator"></div>
        <div class="bottom-padding"></div>
    </div>
    <div id="main">
		<h1>SharePoint</h1>
        <div class="sharepoint-container">
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
				if (isset($oid) && !empty($oid) && !preg_match('/tenant/', $restoretype)) {
					$org = $veeam->getOrganizationByID($oid);
					$repo = $veeam->getOrganizationRepository($oid);
					
					if (isset($repo) && empty($repo)) {
						echo '<p>No SharePoint sites found for this organization.</p>';
						exit;
					}

					$repohref = explode('/', $repo[0]['_links']['backupRepository']['href']);
					$repoid = end($repohref);
					$orgdata = $veeam->getOrganizationData($repoid);
					$sites = $veeam->getSiteData($repoid);
					$sitesarray = array();
					
					for ($i = 0; $i < count($orgdata['results']); $i++) {
						if (strcasecmp($org['name'], $orgdata['results'][$i]['displayName']) === 0) {
							$orgid = $orgdata['results'][$i]['organizationId'];
						}
					}
					
					if (count($sites['results']) !== 0) {
						for ($i = 0; $i < count($sites['results']); $i++) {
							if (strcmp($sites['results'][$i]['organizationId'], $orgid) === 0) {
								if (!empty($sites['results'][$i]['backedUpTime'])) {
									array_push($sitesarray, array(
										'id' => $sites['results'][$i]['id'],
										'backedUpTime' => $sites['results'][$i]['backedUpTime'],
										'title' => $sites['results'][$i]['title'],
										'url' => $sites['results'][$i]['url']
									));
								}
							}
						}
						
						usort($sitesarray, function($a, $b) {
							return strcmp($a['title'], $b['title']);
						});
					}
						
					if (isset($orgid) && count($sitesarray) !== 0) {
					?>
					<div class="alert alert-info">The following is a limited overview with backed up (personal) SharePoint sites within the organization. To view the full list, start a restore session.</div>
					<table class="table table-bordered table-padding table-striped">
						<thead>
							<tr>
								<th>Sites</th>
								<th>Last Backup</th>
								<th>Objects In Backup</th>
							</tr>
						</thead>
						<tbody>
						<?php
							for ($i = 0; $i < count($sitesarray); $i++) {							
								echo '<tr>';
								echo '<td>' . $sitesarray[$i]['title'] . '</td>';
								echo '<td>' . date('d/m/Y H:i T', strtotime($sitesarray[$i]['backedUpTime'])) . '</td>';
								echo '<td>';
								
								if (preg_match('/personal/i', $sitesarray[$i]['url'])) {
									echo '<i class="fa fa-user-alt fa-2x" style="color:green" title="Personal SharePoint site"></i>';
								} else {
									echo '<i class="fa fa-share-alt fa-2x" style="color:green" title="SharePoint site"></i>';
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
							echo '<p>No SharePoint sites found for this organization.</p>';
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
				if (isset($_GET['sid'])) $sid = $_GET['sid'];
				
				if (isset($sid) && !empty($sid)) {
					$name = $veeam->getSharePointSiteName($rid, $sid);

					if (isset($cid) && !empty($cid)) {
						$folders = $veeam->getSharePointItems($rid, $sid, $cid);

						if (strcmp($type, 'list') === 0) {
							$items = $veeam->getSharePointItems($rid, $sid, $cid, 'Items');
							$list = $veeam->getSharePointListName($rid, $sid, $cid, 'Lists');
						} else {
							$documents = $veeam->getSharePointItems($rid, $sid, $cid, 'Documents');
							$list = $veeam->getSharePointListName($rid, $sid, $cid, 'Libraries');
						}
						?>
						<ul class="breadcrumb">
							<li><a href="sharepoint/<?php echo $oid; ?>"><i class="fa fa-home"></i></a></li>
							<?php
							if (isset($list) && !empty($list)) {
								echo '<li><a href="sharepoint/' . $oid . '/' . $sid . '">' . $name["name"] . '</a></li>';
								echo '<li class="active">' . $list['name']. '</li>'; 
							} else {
								echo '<li class="active">' . $name['name'] . '</li>';
							}
							?>
						</ul>
						<?php			
						if (count($folders['results']) === 0 && count($documents['results']) === 0) {
							echo '<p>No items available.</p>';
						} else {
						?>
						<div class="row">
							<div class="col-sm-2 zeroPadding">
								<table class="table table-bordered table-padding table-striped" id="table-sharepoint-folders">
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
													echo '<a class="btn btn-default load-more-link load-more-folders" data-folderid="' . $cid . '" data-offset="' . count($folders['results']) . '" href="' . $_SERVER['REQUEST_URI'] . '#">Load more folders</a>';
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
								<div class="sharepoint-controls-padding" id="sharepoint-controls">
									<input class="form-control search" id="search-sharepoint" placeholder="Filter by item...">
									<div class="form-inline">
									<strong class="btn-group">Items:</strong>
									<div class="btn-group dropdown">
										<button class="btn-link dropdown-toggle" data-toggle="dropdown">Export <span class="caret"></span></button>
										<ul class="dropdown-menu">
										  <li><a class="dropdown-link" href="javascript:void(0);" onclick="downloadZIP('<?php echo $cid; ?>', 'multipleexport', '<?php echo $type; ?>', 'fullcontent')"><i class="fa fa-download"></i> All items</a></li>
										  <li><a class="dropdown-link" href="javascript:void(0);" onclick="downloadZIP('<?php echo $cid; ?>', 'multipleexport', 'documents', 'multiple')"><i class="fa fa-download"></i> Selected items</a></li>
										</ul>
									</div>
									<div class="btn-group dropdown">
										<button class="btn-link dropdown-toggle form-control" data-toggle="dropdown">Restore <span class="caret"></span></button>
										<ul class="dropdown-menu">
										  <li><a class="dropdown-link" href="javascript:void(0);" onclick="restoreToOriginal('<?php echo $cid; ?>', '<?php echo $type; ?>', 'fullcontent')"><i class="fa fa-upload"></i> All items</a></li>
										  <li><a class="dropdown-link" href="javascript:void(0);" onclick="restoreToOriginal('multiplerestore', 'documents', 'multiple')"><i class="fa fa-upload"></i> Selected items</a></li>
										</ul>
									</div>
								</div>
								<table class="table table-bordered table-padding table-striped" id="table-sharepoint-items">
									<thead>
										<tr>
											<th class="text-center"><input type="checkbox" id="chk-all" title="Select all"></th>
											<?php
											if (strcmp($type, 'list') === 0) {
												echo '<th>Title</th>';
											} else {
												echo '<th>Name</th>';
											}
											?>
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
										if (strcmp($type, 'list') === 0) {
											for ($i = 0; $i < count($items['results']); $i++) {
										?>
										<tr>
											<td></td>
											<td><i class="far fa-file"></i> <?php echo $items['results'][$i]['title']; ?></td>
											<td>-</td>
											<td><?php echo date('d/m/Y H:i', strtotime($items['results'][$i]['modificationTime'])) . ' by ' . $items['results'][$i]['modifiedBy'] . ''; ?></em></td>
											<td><?php echo $items['results'][$i]['version']; ?></td>
											<td class="text-center">
												<div class="btn-group dropdown">
													<button class="btn btn-default dropdown-toggle" data-toggle="dropdown">Restore <span class="caret"></span></button>
													<ul class="dropdown-menu dropdown-menu-right">
													  <li><a class="dropdown-link" href="javascript:void(0);" onclick="restoreToOriginal('<?php echo $folders['results'][$i]['id']; ?>', 'items', 'single')"><i class="fa fa-upload"></i> Restore item</a></li>
													</ul>
												</div>
											</td>
										</tr>
										<?php
											}
										} else {
											for ($i = 0; $i < count($documents['results']); $i++) {
										?>
										<tr>
											<td class="text-center"><input type="checkbox" name="checkbox-sharepoint" value="<?php echo $documents['results'][$i]['id']; ?>"></td>
											<td><i class="far fa-file"></i> <?php echo $documents['results'][$i]['name']; ?></td>
											<td><script>document.write(filesize(<?php echo $documents['results'][$i]['sizeBytes']; ?>, {round: 2}));</script></td>
											<td><?php echo date('d/m/Y H:i', strtotime($documents['results'][$i]['modificationTime'])) . ' by ' . $documents['results'][$i]['modifiedBy'] . ''; ?></em></td>
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
									}
									?>
									</tbody>
								</table>
								<?php
								if (isset($items) && count($items['results']) >= $limit) {
									echo '<div class="text-center">';
									echo '<a class="btn btn-default load-more-link load-more-items" data-folderid="' . $cid . '" data-offset="' . count($items['results']) . '" data-type="items" href="' . $_SERVER['REQUEST_URI'] . '#">Load more items</a>';
									echo '</div>';
								} else {
									echo '<div class="text-center">';
									echo '<a class="btn btn-default load-more-link load-more-items" data-folderid="' . $cid . '" data-offset="' . count($documents['results']) . '" data-type="documents" href="' . $_SERVER['REQUEST_URI'] . '#">Load more items</a>';
									echo '</div>';
								}
								?>
							</div>
						</div>
						<?php
						}
					} else {
						?>
						<ul class="breadcrumb">
							<li><a href="sharepoint/<?php echo $oid; ?>"><i class="fa fa-home"></i></a></li>
							<li class="active"><?php echo $name['name']; ?></li>
						</ul>
						<p>Select a library or list to view the specific content.</p>
						<?php
					}
				} else {
				?>
				<table class="table table-bordered table-padding table-striped" id="table-sharepoint-sites">
					<thead>
						<tr>
							<th>Site</th>
							<th class="text-center">Options</th>
						</tr>
					</thead>
					<tbody>
						<?php
						$siteslist = array();

						for ($i = 0; $i < count($sites['results']); $i++) {
							array_push($siteslist, array('name'=> $sites['results'][$i]['name'], 'id' => $sites['results'][$i]['id']));
						}

						uasort($siteslist, function($a, $b) {
							return strcasecmp($a['name'], $b['name']);
						});

						foreach ($siteslist as $key => $value) {
						?>
						<tr>
							<td><a href="sharepoint/<?php echo $oid; ?>/<?php echo $value['id']; ?>"><?php echo $value['name']; ?></a></td>
							<td class="text-center">
								<div class="btn-group dropdown">
									<button class="btn btn-default dropdown-toggle" data-toggle="dropdown">Restore <span class="caret"></span></button>
									<ul class="dropdown-menu dropdown-menu-right">
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
				if (count($sites['results']) >= $limit) {
					echo '<div class="text-center">';
					echo '<a class="btn btn-default load-more-link load-more-sites" data-org="' . $oid . '" data-offset="' . count($sites['results']) . '" href="' . $_SERVER['REQUEST_URI'] . '#">Load more sites</a>';
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

    var json = '{ "explore": { "datetime": "' + pit + '", "type": "vesp", "ShowAllVersions": "true", "ShowDeleted": "true" } }';

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
				window.location.href = 'sharepoint';
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
						window.location.href = 'sharepoint';
					});
				} else {
					swalWithBootstrapButtons.fire({
						icon: 'error', 
						title: 'Restore session',
						text: '' + response.slice(0, -1),
					}).then(function(e) {
						window.location.href = 'sharepoint';
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

$('#search-sharepoint').keyup(function(e) {
    var searchText = $(this).val().toLowerCase();
    
    $.each($('#table-sharepoint-items tbody tr'), function(e) {
        if ($(this).text().toLowerCase().indexOf(searchText) === -1) {
           $(this).hide();
        } else {
           $(this).show();
        }
    });
});

$('.load-more-sites').click(function(e) {
    var offset = $(this).data('offset');
    var org = $(this).data('org');
	
	loadSites(org, offset);
});

function loadSites(org, offset) {
	var limit = <?php echo $limit; ?>;
	var rid = '<?php echo $rid; ?>';
	
    $.post('veeam.php', {'action' : 'getsharepointsites', 'rid' : rid, 'offset' : offset}).done(function(data) {
        var response = JSON.parse(data);

        if (response.results.length !== 0) {
			for (var i = 0; i < response.results.length; i++) {
				if ($('#table-sharepoint-sites').length > 0){
					$('#table-sharepoint-sites tbody').append('<tr> \
						<td><a href="sharepoint/' + org + '/' + response.results[i].id + '">' + response.results[i].name + '</a></td> \
						<td class="text-center"> \
						<div class="btn-group dropdown"> \
						<button class="btn btn-default dropdown-toggle" data-toggle="dropdown">Restore <span class="caret"></span></button> \
						<ul class="dropdown-menu dropdown-menu-right"> \
						<li class="dropdown-header">Restore to</li> \
						<li><a class="dropdown-link" href="javascript:void(0);" onclick="restoreToOriginal(\'' + response.results[i].id + '\', \'' + response.results[i].name + '\', \'documents\', \'full\')"><i class="fa fa-upload"></i> Original location</a></li> \
						</ul> \
						</div> \
						</td> \
						</tr>');
				}
				$('#ul-sharepoint-sites').append('<li><a href="sharepoint/' + org + '/' + response.results[i].id + '">' + response.results[i].name + '</a></li>');
			}
			
			if (response.results.length >= limit) {
				$('a.load-more-sites').data('offset', offset + limit);
			} else {
				$('a.load-more-sites').addClass('hide');
			}
		}
    });
}

function restoreToOriginal(itemid, filetype, type) {
    var filetype = filetype;
	var itemid = itemid;
	var type = type;
	var rid = '<?php echo $rid; ?>';
	<?php
	if (isset($sid)) {
	?>
	var siteid = '<?php echo $sid; ?>';
	<?php
	} else {
	?>
	var siteid = itemid;
	<?php
	}
	?>
	
	if (type === 'multiple' && $("input[name='checkbox-sharepoint']:checked").length === 0) {
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
							'<div class="alert alert-warning" role="alert">This will restore the last version of the item</div>' +
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
							'<option value="merge">Merge file</option>' +
							'<option value="overwrite">Overwrite file</option>' +
							'</select>' +
							'</div>' +
							'</div>' +
							'<div class="form-group">' +
							'<label for="restore-original-permissions" class="col-sm-4 text-right">Restore permissions:</label>' +
							'<div class="col-sm-8">' + 
							'<select class="form-control restoredata" id="restore-original-permissions">' +
							'<option value="true">Yes</option>' +
							'<option value="false">No</option>' +
							'</select>' + 
							'</div>' +
							<?php
							if (isset($list)) {
							?>
							'<input type="hidden" id="restore-original-listname" value="<?php echo $list['name']; ?>">' +
							<?php
							}
							?>
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
							var listname = $('#restore-original-listname').val();
							var restoreaction = $('#restore-original-action').val();
							var restorepermissions = $('#restore-original-permissions').val();
							
							Swal.fire({
								icon: 'info',
								title: 'Restore',
								text: 'Restore in progress...',
								allowOutsideClick: false,
							});

							if (type == 'multiple') {
								var act = 'restoremultiplesharepointitems';
								filetype = 'documents';
								var ids = '';
								
								$("input[name='checkbox-sharepoint']:checked").each(function(e) {
									ids = ids + '{ "Id": "' + this.value + '" }, ';
								});
								
								var json = '{ "restoreTo": \
									{ "UserName": "' + user + '", \
									  "UserPassword": "' + pass + '", \
									  "List" : "' + listname + '", \
									  "RestorePermissions" : "' + restorepermissions + '", \
									  "SendSharedLinksNotification": "true", \
									  "DocumentVersion" : "last", \
									  "DocumentLastVersionAction" : "' + restoreaction + '", \
									  "Documents": [ \
										' + ids + ' \
									  ] \
									} \
								}';
							} else {
								if (type == 'single') {
									var act = 'restoresharepointitem';
									
									if (filetype == 'libraries' || filetype == 'lists') {
										var json = '{ "restoreTo": \
											{ "userName": "' + user + '", \
											  "userPassword": "' + pass + '", \
											  "list" : "' + listname + '", \
											  "restorePermissions" : "' + restorepermissions + '", \
											  "sendSharedLinksNotification": "true", \
											  "documentVersion" : "last", \
											  "documentLastVersionAction" : "' + restoreaction + '", \
											  "RestoreListViews" : "true", \
											  "changedItems" : "true", \
											  "DeletedItems" : "true" \
											} \
										}';
									} else {
										var json = '{ "restoreTo": \
											{ "userName": "' + user + '", \
											  "userPassword": "' + pass + '", \
											  "list" : "' + listname + '", \
											  "restorePermissions" : "' + restorepermissions + '", \
											  "sendSharedLinksNotification": "true", \
											  "documentVersion" : "last", \
											  "documentLastVersionAction" : "' + restoreaction + '", \
											} \
										}';
									}
								} else if (type == 'fullcontent') {
									var act = 'restoresharepointitem';
									var node = $('#jstree').jstree('get_selected', true);

									if (node.length !== 0) {
										itemid = node[0].data.folderid;
										filetype = 'folders';
										
										var json = '{ "restoreTo": \
											{ "userName": "' + user + '", \
											  "userPassword": "' + pass + '", \
											  "list" : "' + listname + '", \
											  "restorePermissions" : "' + restorepermissions + '", \
											  "sendSharedLinksNotification": "true", \
											  "documentVersion" : "last", \
											  "documentLastVersionAction" : "' + restoreaction + '", \
											} \
										}';
									} else {
										itemid = '<?php echo $cid; ?>';
										<?php
										if (strcmp($type, 'list') === 0) {
										?>
										filetype = 'lists';
										<?php
										} else {
										?>
										filetype = 'libraries';
										<?php
										}
										?>
										
										var json = '{ "restoreTo": \
											{ "userName": "' + user + '", \
											  "userPassword": "' + pass + '", \
											  "list" : "' + listname + '", \
											  "restorePermissions" : "' + restorepermissions + '", \
											  "sendSharedLinksNotification": "true", \
											  "documentVersion" : "last", \
											  "documentLastVersionAction" : "' + restoreaction + '", \
											  "RestoreListViews" : "true", \
											  "changedItems" : "true", \
											  "DeletedItems" : "true" \
											} \
										}';
									}
								} else if (type == 'full') {
									var act = 'restoresharepoint';
									var json = '{ "restoreTo": \
										{ "userName": "' + user + '", \
										  "userPassword": "' + pass + '", \
										  "list" : "' + listname + '", \
										  "restorePermissions" : "' + restorepermissions + '", \
										  "sendSharedLinksNotification": "true", \
										  "documentVersion" : "last", \
										  "documentLastVersionAction" : "' + restoreaction + '", \
										  "RestoreListViews" : "true", \
										  "changedItems" : "true", \
										  "DeletedItems" : "true", \
										  "RestoreSubsites" : "true", \
										  "RestoreMasterPages" : "true" \
										} \
									}';
								}
							}

							$.post('veeam.php', {'action' : act, 'rid' : rid, 'siteid' : siteid, 'itemid' : itemid, 'json' : json, 'type' : filetype}).done(function(data) {			
								var response = JSON.parse(data);

								if (response['restoreFailed'] === undefined) {
									if (act === 'restoresharepoint') {
										var result = '';
										
										if (response['restoredListsCount'] >= '1') {
											result += response['restoredListsCount'] + ' list(s) successfully restored<br>';
										}
										
										if (response['restoredWebsCount'] >= '1') {
											result += response['restoredWebsCount'] + ' web item(s) successfully restored<br>';
										}
										
										if (response['failedRestrictionsCount'] >= '1') {
											result += response['failedRestrictionsCount'] + ' item(s) failed due to restrictions issues<br>';
										}
										
										if (response['failedListsCount'] >= '1') {
											result += response['failedListsCount'] + ' list(s) failed to restore<br>';
										}
										
										if (response['failedWebsCount'] >= '1') {
											result += response['failedWebsCount'] + ' web item(s) failed to restore';
										}
									} else {
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
							'<div class="alert alert-warning" role="alert">This will restore the last version of the item</div>' +
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
							'<option value="merge">Merge file</option>' +
							'<option value="overwrite">Overwrite file</option>' +
							'</select>' +
							'</div>' +
							'</div>' +
							'<div class="form-group">' +
							'<label for="restore-original-permissions" class="col-sm-4 text-right">Restore permissions:</label>' +
							'<div class="col-sm-8">' + 
							'<select class="form-control restoredata" id="restore-original-permissions">' +
							'<option value="true">Yes</option>' +
							'<option value="false">No</option>' +
							'</select>' + 
							'</div>' +
							<?php
							if (isset($list)) {
							?>
							'<input type="hidden" id="restore-original-listname" value="<?php echo $list['name']; ?>">' +
							<?php
							}
							?>
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
							var listname = $('#restore-original-listname').val();
							var restoreaction = $('#restore-original-action').val();
							var restorepermissions = $('#restore-original-permissions').val();
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
											var act = 'restoremultiplesharepointitems';
											filetype = 'documents';
											var ids = '';
											
											$("input[name='checkbox-sharepoint']:checked").each(function(e) {
												ids = ids + '{ "id": "' + this.value + '" }, ';
											});
											
											var json = '{ "restoreTo": \
												{ "userCode": "' + usercode + '", \
												  "List" : "' + listname + '", \
												  "RestorePermissions" : "' + restorepermissions + '", \
												  "SendSharedLinksNotification": "true", \
												  "DocumentVersion" : "last", \
												  "DocumentLastVersionAction" : "' + restoreaction + '", \
												  "Documents": [ \
													' + ids + ' \
												  ] \
												} \
											}';
										} else {
											if (type == 'single') {
												var act = 'restoresharepointitem';
												
												if ((filetype == 'libraries') || (filetype == 'lists')) {
													var json = '{ "restoreTo": \
														{ "userCode": "' + usercode + '", \
														  "list" : "' + listname + '", \
														  "restorePermissions" : "' + restorepermissions + '", \
														  "sendSharedLinksNotification": "true", \
														  "documentVersion" : "last", \
														  "documentLastVersionAction" : "' + restoreaction + '", \
														  "RestoreListViews" : "true", \
														  "changedItems" : "true", \
														  "DeletedItems" : "true" \
														} \
													}';
												} else {
													var json = '{ "restoreTo": \
														{ "userCode": "' + usercode + '", \
														  "list" : "' + listname + '", \
														  "restorePermissions" : "' + restorepermissions + '", \
														  "sendSharedLinksNotification": "true", \
														  "documentVersion" : "last", \
														  "documentLastVersionAction" : "' + restoreaction + '", \
														} \
													}';
												}
											} else if (type == 'fullcontent') {
												var act = 'restoresharepointitem';
												var node = $('#jstree').jstree('get_selected', true);

												if (node.length !== 0) {
													itemid = node[0].data.folderid;
													filetype = 'folders';
													
													var json = '{ "restoreTo": \
														{ "userCode": "' + usercode + '", \
														  "list" : "' + listname + '", \
														  "restorePermissions" : "' + restorepermissions + '", \
														  "sendSharedLinksNotification": "true", \
														  "documentVersion" : "last", \
														  "documentLastVersionAction" : "' + restoreaction + '", \
														} \
													}';
												} else {
													itemid = '<?php echo $cid; ?>';
													<?php
													if (strcmp($type, 'list') === 0) {
													?>
													filetype = 'lists';
													<?php
													} else {
													?>
													filetype = 'libraries';
													<?php
													}
													?>
													
													var json = '{ "restoreTo": \
														{ "userCode": "' + usercode + '", \
														  "list" : "' + listname + '", \
														  "restorePermissions" : "' + restorepermissions + '", \
														  "sendSharedLinksNotification": "true", \
														  "documentVersion" : "last", \
														  "documentLastVersionAction" : "' + restoreaction + '", \
														  "RestoreListViews" : "true", \
														  "changedItems" : "true", \
														  "DeletedItems" : "true" \
														} \
													}';
												}
											} else if (type == 'full') {
												var act = 'restoresharepoint';
												var json = '{ "restoreTo": \
													{ "userCode": "' + usercode + '", \
													  "list" : "' + listname + '", \
													  "restorePermissions" : "' + restorepermissions + '", \
													  "sendSharedLinksNotification": "true", \
													  "documentVersion" : "last", \
													  "documentLastVersionAction" : "' + restoreaction + '", \
													  "RestoreListViews" : "true", \
													  "changedItems" : "true", \
													  "DeletedItems" : "true", \
													  "RestoreSubsites" : "true", \
													  "RestoreMasterPages" : "true" \
													} \
												}';
											}
										}

										$.post('veeam.php', {'action' : act, 'rid' : rid, 'siteid' : siteid, 'itemid' : itemid, 'json' : json, 'type' : filetype}).done(function(data) {
											var response = JSON.parse(data);

											if (response['restoreFailed'] === undefined) {
												if (act === 'restoresharepoint') {
													var result = '';
													
													if (response['restoredListsCount'] >= '1') {
														result += response['restoredListsCount'] + ' list(s) successfully restored<br>';
													}
													
													if (response['restoredWebsCount'] >= '1') {
														result += response['restoredWebsCount'] + ' web item(s) successfully restored<br>';
													}
													
													if (response['failedRestrictionsCount'] >= '1') {
														result += response['failedRestrictionsCount'] + ' item(s) failed due to restrictions issues<br>';
													}
													
													if (response['failedListsCount'] >= '1') {
														result += response['failedListsCount'] + ' list(s) failed to restore<br>';
													}
													
													if (response['failedWebsCount'] >= '1') {
														result += response['failedWebsCount'] + ' web item(s) failed to restore';
													}
												} else {
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
	if (isset($sid)) {
?>
$('.load-more-folders').click(function(e) {
	var folderid = $(this).data('folderid');
    var offset = $(this).data('offset');
	var node = $('#jstree').jstree('get_selected');

	loadFolders(folderid, node, offset);
});
$('.load-more-content').click(function(e) {
    var offset = $(this).data('offset');
	var org = $(this).data('org');
	
	loadContent(org, offset);
});
$('.load-more-items').click(function(e) {
    var folderid = $(this).data('folderid');
    var offset = $(this).data('offset');
	var type = $(this).data('type');

    loadItems(folderid, offset, type);
});

function downloadFile(itemid, itemname, filetype) {
    var rid = '<?php echo $rid; ?>';
	var siteid = '<?php echo $sid; ?>';
	var json = '{ "save": { "asZip": "false" } }';
	
	Swal.fire({
		icon: 'info',
		title: 'Export',
		text: 'Export in progress and your download will start soon',
		allowOutsideClick: false,
	});

	$.post('veeam.php', {'action' : 'exportsharepointitem', 'rid' : rid, 'siteid' : siteid, 'itemid' : itemid, 'json' : json, 'type' : filetype}).done(function(data) {
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

function downloadZIP(itemid, itemname, filetype, type) {
    var rid = '<?php echo $rid; ?>';
	var siteid = '<?php echo $sid; ?>';

	Swal.fire({
		icon: 'info',
		title: 'Export',
		text: 'Export in progress and your download will start soon',
		allowOutsideClick: false,
	});
	
	if (type == 'multiple') {
		var act = 'exportmultiplesharepointitems';
		var filetype = 'documents';
		var ids = '';
		var filename = 'sharepoint-items-' + itemname;
		
		if ($("input[name='checkbox-sharepoint']:checked").length === 0) {
			Swal.close();
			Swal.fire({
				icon: 'info',
				title: 'Export',
				text: 'Cannot export items. No items have been selected'
			});
			
			return;
		}

		$("input[name='checkbox-sharepoint']:checked").each(function(e) {
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
		if (type == 'single') {
			var act = 'exportsharepointitem';
			var filename = 'sharepoint-' + itemname;
		} else if (type == 'fullcontent') {
			var act = 'exportsharepointitem';
			var node = $('#jstree').jstree('get_selected', true);

			if (node.length !== 0) {
				var itemid = node[0].data.folderid;
				var filename = 'sharepoint-folder-' + itemname;
				var filetype = 'folders';
			} else {
				var filename = 'sharepoint-<?php echo $type; ?>-' + itemname;
				<?php
				if (strcmp($type, 'list') === 0) {
				?>
				var filetype = 'lists';
				<?php
				} else {
				?>
				var filetype = 'libraries';
				<?php
				}
				?>
			}
		} else {
			var act = 'exportsharepoint';
			var filename = 'sharepoint-full-' + itemname;
		}
		
		var json = '{ "save": { "asZip": "true" } }';
	}

	$.post('veeam.php', {'action' : act, 'rid' : rid, 'siteid' : siteid, 'itemid' : itemid, 'json' : json, 'type' : filetype}).done(function(data) {
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

function fillTableDocuments(response, type) {
	if (response.results.length !== 0) {
		for (var i = 0; i < response.results.length; i++) {
			if (type == 'documents') {
				var size = filesize(response.results[i].sizeBytes, {round: 2});
				
				$('#table-sharepoint-items tbody').append('<tr> \
				<td class="text-center"><input type="checkbox" name="checkbox-sharepoint" value="' + response.results[i].id + '"></td> \
				<td><i class="far fa-file"></i> ' + response.results[i].name + '</td> \
				<td>' + size + '</td> \
				<td>' + moment(response.results[i].modificationTime).format('DD/MM/YYYY HH:mm') + ' (by ' + response.results[i].modifiedBy + ')</td> \
				<td>' + response.results[i].version + '</td> \
				<td class="text-center"> \
				<div class="dropdown"> \
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
			} else {			
				$('#table-sharepoint-items tbody').append('<tr> \
				<td class="text-center"><input type="checkbox" name="checkbox-sharepoint" value="' + response.results[i].id + '"></td> \
				<td><i class="far fa-file"></i> ' + response.results[i].name + '</td> \
				<td>-</td> \
				<td>' + moment(response.results[i].modificationTime).format('DD/MM/YYYY HH:mm') + ' (by ' + response.results[i].modifiedBy + ')</td> \
				<td>' + response.results[i].version + '</td> \
				<td class="text-center"> \
				<div class="dropdown"> \
				<button class="btn btn-default dropdown-toggle" data-toggle="dropdown">Restore <span class="caret"></span></button> \
				<ul class="dropdown-menu dropdown-menu-right"> \
				<li><a class="dropdown-link" href="javascript:void(0);" onclick="restoreToOriginal(\'' + response.results[i].id + '\', \'items\', \'single\')"><i class="fa fa-upload"></i> Restore item</a></li> \
				</ul> \
				</div> \
				</td> \
				</tr>');
			}
		}
    }
}

function fillTableFolders(response) {
	if (response.results.length !== 0) {
		for (var i = 0; i < response.results.length; i++) {
			$('#table-sharepoint-items tbody').append('<tr> \
				<td></td> \
				<td><i class="far fa-folder"></i> <a href="javascript:void(0);" onclick="loadFolderItems(\'' + response.results[i].id + '\');">' + response.results[i].name + '</a></td> \
				<td>-</td> \
				<td>' + moment(response.results[i].modificationTime).format('DD/MM/YYYY HH:mm') + ' (by ' + response.results[i].modifiedBy + ')</td> \
				<td>-</td> \
				<td class="text-center"> \
				<div class="dropdown"> \
				<button class="btn btn-default dropdown-toggle" data-toggle="dropdown">Restore <span class="caret"></span></button> \
				<ul class="dropdown-menu dropdown-menu-right"> \
				<li class="dropdown-header">Export to</li> \
				<li><a class="dropdown-link" href="javascript:void(0);" onclick="downloadZIP(\'' + response.results[i].id + '\', \'' + response.results[i].name + '\', \'folders\', \'single\')"><i class="fa fa-download"></i> ZIP file</a></li> \
				<li class="dropdown-header">Restore to</li> \
				<li><a class="dropdown-link" href="javascript:void(0);" onclick="restoreToOriginal(\'' + response.results[i].id + '\', \'folders\', \'single\')"><i class="fa fa-upload"></i> Original location</a></li> \
				</ul> \
				</div> \
				</td> \
				</tr>');
		}
	}
}

function loadFolders(folderid, node, offset) {
	var limit = <?php echo $limit; ?>;
	var rid = '<?php echo $rid; ?>';
	var siteid = '<?php echo $sid; ?>';

    $.post('veeam.php', {'action' : 'getsharepointfolders', 'rid' : rid, 'siteid' : siteid, 'folderid' : folderid, 'offset' : offset}).done(function(data) {
        var response = JSON.parse(data);

        if (response.results.length !== 0) {
			if (node.length === 0) {
				node = '#';
			}
			
			for (var i = 0; i < response.results.length; i++) {
				$('#jstree').jstree('create_node', node, {data: {"folderid" : response.results[i].id, "jstree" : {"opened" : true}}, text: response.results[i].name});
			}
			
			fillTableFolders(response);
			
			if (response.results.length >= limit) {
				$('a.load-more-folders').removeClass('hide');
				$('a.load-more-folders').data('offset', offset + limit);
			} else {
				$('a.load-more-folders').addClass('hide');
			}
		}
    });
}

function loadItems(folderid, offset, type) {
	var limit = <?php echo $limit; ?>;
	var rid = '<?php echo $rid; ?>';
	var siteid = '<?php echo $sid; ?>';
	
    $.post('veeam.php', {'action' : 'getsharepointitems', 'rid' : rid, 'siteid' : siteid, 'folderid' : folderid, 'offset' : offset, 'type' : type}).done(function(data) {
        var response = JSON.parse(data);

		if (typeof response !== undefined && response.results.length !== 0) {
            fillTableDocuments(response, type);
			
			if (response.results.length >= limit) {
				$('a.load-more-items').removeClass('hide');
				$('a.load-more-items').data('offset', offset + limit);
			} else {
				$('a.load-more-items').addClass('hide');
			}
		} else {
			$('#table-sharepoint-items tbody').append('<tr><td class="text-center" colspan="6">No more items available.</td></tr>');
			$('a.load-more-items').addClass('hide');
		}
	});
}

function loadContent(org, offset) {
	var limit = <?php echo $limit; ?>;
	var rid = '<?php echo $rid; ?>';
	var siteid = '<?php echo $sid; ?>';
	var responselibraries, responselists;
	
    $.post('veeam.php', {'action' : 'getsharepointcontent', 'rid' : rid, 'siteid' : siteid, 'offset' : offset, 'type' : 'libraries'}).done(function(data) {
        responselibraries = JSON.parse(data);
    }).then(function(e) {
		if (typeof responselibraries !== undefined && responselibraries.results.length !== 0) {
			for (var i = 0; i < responselibraries.results.length; i++) {
				$('#ul-sharepoint-sites').append('<li><a data-type="library" href="sharepoint/' + org + '/' + siteid + '/' + responselibraries.results[i].id + '/library">' + responselibraries.results[i].name + '</a></li>');
			}
		}
	}).then(function(e) {
		$.post('veeam.php', {'action' : 'getsharepointcontent', 'rid' : rid, 'siteid' : siteid, 'offset' : offset, 'type' : 'lists'}).done(function(data) {
			responselists = JSON.parse(data);
		}).then(function(e) {
			if (typeof responselists !== undefined && responselists.results.length !== 0) {
				for (var i = 0; i < responselists.results.length; i++) {
					$('#ul-sharepoint-sites').append('<li><a data-type="list" href="sharepoint/' + org + '/' + siteid + '/' + responselists.results[i].id + '/list">' + responselists.results[i].name + '</a></li>');
				}
			}
		});
	}).then(function(e) {
		if (typeof responselibraries !== undefined && responselibraries.results.length >= limit) {
			$('a.load-more-content').removeClass('hide');
			$('a.load-more-content').data('offset', offset + limit);
		} else if (typeof responselists !== undefined && responselists.results.length >= limit) {
			$('a.load-more-content').removeClass('hide');
			$('a.load-more-content').data('offset', offset + limit);
		} else {
			$('a.load-more-content').addClass('hide');
		}
	});
}

<?php
		if (isset($type)) {
?>
function fillTable(folderid, parent, responsefolders, responsedocuments) {
	var limit = <?php echo $limit; ?>;
	
	if ((typeof responsefolders !== undefined && responsefolders.results.length === 0) && (typeof responsedocuments !== undefined && responsedocuments.results.length === 0)) {
		$('#table-sharepoint-items tbody').append('<tr><td colspan="6">No items available.</td></tr>');
		$('#loader').addClass('hide');
		$('a.load-more-items').addClass('hide');
		enableTree();
		
		return;
	}
	
	if (typeof responsefolders !== undefined && responsefolders.results.length !== 0) {
		fillTableFolders(responsefolders);
	}

	if (typeof responsedocuments !== undefined && responsedocuments.results.length !== 0) {
		<?php
		if (strcmp($type, 'list') === 0) {
		?>
		fillTableDocuments(responsedocuments, 'items');
		<?php
		} else {
		?>
		fillTableDocuments(responsedocuments, 'documents');
		<?php
		}
		?>
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

function loadFolderItems(folderid, parent) {
	if (arguments.length === 1) {
		parent = null;
	}
	
    var responsedocuments, responsefolders;
	var rid = '<?php echo $rid; ?>';
	var siteid = '<?php echo $sid; ?>';
	
	disableTree();
	
	$('#table-sharepoint-items tbody').empty();
	$('#loader').removeClass('hide');
	$('a.load-more-link').addClass('hide');
	
    $.post('veeam.php', {'action' : 'getsharepointitems', 'rid' : rid, 'siteid' : siteid, 'folderid' : folderid, 'type' : 'folders'}).done(function(data) {
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

	<?php
	if (strcmp($type, 'list') === 0) {
	?>
	$.post('veeam.php', {'action' : 'getsharepointitems', 'rid' : rid, 'siteid' : siteid, 'folderid' : folderid, 'type' : 'items'}).done(function(data) {
		responsedocuments = JSON.parse(data);
	}).then(function(e) {
		fillTable(folderid, parent, responsefolders, responsedocuments);
	});
	<?php
	} else {
	?>
	$.post('veeam.php', {'action' : 'getsharepointitems', 'rid' : rid, 'siteid' : siteid, 'folderid' : folderid, 'type' : 'documents'}).done(function(data) {
		responsedocuments = JSON.parse(data);
	}).then(function(e) {
		fillTable(folderid, parent, responsefolders, responsedocuments);
	});
	<?php
	}
	?>	
}
<?php
		}
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