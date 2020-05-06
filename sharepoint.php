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
	  <li><a href="exchange">Exchange</a></li>
	  <li><a href="onedrive">OneDrive</a></li>
	  <li class="active"><a href="sharepoint">SharePoint</a></li>
	</ul>
	<ul class="nav navbar-nav navbar-right">
	  <li><a href="#"><span class="fa fa-user"></span> Welcome <i><?php echo $user; ?></i> !</a></li>
	  <li id="logout"><a href="#"><span class="fa fa-sign-out-alt"></span> Logout</a></li>
	</ul>
</nav>
<div class="container-fluid">
    <link rel="stylesheet" href="css/sharepoint.css" />
    <aside id="sidebar">
        <div class="logo-container"><i class="logo fa fa-share-alt"></i></div>
        <div class="separator"></div>
        <menu class="menu-segment" id="menu">
		<?php
		if (!isset($_SESSION['rid'])) { /* No restore session is running */
			$check = filter_var($user, FILTER_VALIDATE_EMAIL);

			echo '<ul id="ul-sharepoint-users">';
			
			if ($check === false && strtolower($administrator) == 'yes') {
				$oid = $_GET['oid'];
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
				
				echo '<li class="active"><a href="sharepoint">' . $org['name'] . '</a></li>';
			}
			
			echo '</ul>';
		} else { /* Restore session is running */
			$rid = $_SESSION['rid'];

			if (strcmp($_SESSION['rtype'], 'vesp') === 0) {
				$sid = $_GET['sid'];
				$org = $veeam->getOrganizationID($rid);

				echo '<button class="btn btn-default btn-danger btn-stop-restore" title="Stop Restore">Stop Restore</button>';
				echo '<div class="separator"></div>';

				if (isset($sid) && !empty($sid)) {
					$libraries = $veeam->getSharePointContent($rid, $sid, 'libraries');
					$lists = $veeam->getSharePointContent($rid, $sid, 'lists');
					$content = array();
					
					if ($libraries == '500' || $lists == '500') { /* Restore session has expired or was killed */
						unset($_SESSION['rid']);
						?>
						<script>
						Swal.fire({
							type: 'info',
							title: 'Restore session expired',
							text: 'Your restore session has expired.'
						}).then(function(e) {
							window.location.href = '/sharepoint';
						});
						</script>
						<?php
					} else {
						echo '<a href="sharepoint/' . $org['id'] . '"><i class="fa fa-reply"></i> Parent site</a>';
						echo '<ul id="ul-sharepoint-sites">';
						echo '<div class="separator"></div>';

						for ($i = 0; $i < count($libraries['results']); $i++) {
							array_push($content, array('name'=> $libraries['results'][$i]['name'], 'id' => $libraries['results'][$i]['id'], 'type' => 'library'));
						}

						for ($i = 0; $i < count($lists['results']); $i++) {
							array_push($content, array('name'=> $lists['results'][$i]['name'], 'id' => $lists['results'][$i]['id'], 'type' => 'list'));
						}

						uasort($content, function($a, $b) {
							return strcasecmp($a['name'], $b['name']);
						});

						foreach ($content as $key => $value) {
							if (isset($sid) && !empty($sid) && ($sid == $value['id'])) {
								echo '<li class="active"><a data-type="' . $value['type'] . '" href="sharepoint/' . $org['id'] . '/' . $sid . '/' . $value['id'] . '/' . $value['type'] . '">' . $value['name'] . '</a></li>';
							} else {
								echo '<li><a data-type="' . $value['type'] . '" href="sharepoint/' . $org['id'] . '/' . $sid . '/' . $value['id'] . '/' . $value['type'] . '">' . $value['name'] . '</a></li>';
							}
						}

						echo '</ul>';
					}
				} else {
					$sites = $veeam->getSharePointSites($rid);
					$content = array();
					
					if ($sites == '500') { /* Restore session has expired or was killed */
						unset($_SESSION['rid']);
						?>
						<script>
						Swal.fire({
							type: 'info',
							title: 'Restore session expired',
							text: 'Your restore session has expired.'
						}).then(function(e) {
							window.location.href = '/sharepoint';
						});
						</script>
						<?php
					} else {
						for ($i = 0; $i < count($sites['results']); $i++) {
							array_push($content, array('name'=> $sites['results'][$i]['name'], 'id' => $sites['results'][$i]['id']));
						}

						uasort($content, function($a, $b) {
							return strcasecmp($a['name'], $b['name']);
						});

						foreach ($content as $key => $value) {
							echo '<li><a href="sharepoint/' . $org['id'] . '/' . $value['id'] . '">' . $value['name'] . '</a></li>';
						}
					}
				}
			} else {
			   ?>
				<script>
				Swal.fire({
					type: 'info',
					showConfirmButton: false,
					title: 'Restore session running',
					text: 'Found another restore session running, please stop the session first if you want to restore SharePoint items.',
					<?php
					if (strcmp($_SESSION['rtype'], 'vex') === 0) {
						echo "footer: '<a href=\"/exchange\">Go to restore session</a>'";
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
		<h1>SharePoint</h1>
        <div class="sharepoint-container">
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
							
							/* Only store data when the SharePoint data is backed up */
							if (!is_null($userdata) && $userdata['isPersonalSiteBackedUp']) {
								array_push($repousersarray, array(
										'id' => $userdata['accountId'], 
										'email' => $userdata['email'],
										'name' => $userdata['displayName'],
										'isPersonalSiteBackedUp' => $userdata['isPersonalSiteBackedUp']
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
								<th>Personal sites</th>
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
								if ($usersort[$i]['isPersonalSiteBackedUp']) {
									echo '<i class="fa fa-share-alt fa-2x" style="color:green" title="SharePoint site"></i> ';
								} else {
									echo '<i class="fa fa-share-alt fa-2x" style="color:red" title="SharePoint site"></i> ';
								}
								echo '</td>';
								echo '<td>' . date('d/m/Y H:i T', strtotime($usersarray[$licinfo]['lastBackupDate'])) . '</td>';
								echo '</tr>';
							}
						?>
						</tbody>
					</table>
				<?php
				} else { /* No sites available for the organization ID */
					if ($check === false && strtolower($administrator) == 'yes') {
						echo '<p>No SharePoint sites found for this organization.</p>';
					} else {
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
            if (isset($sid) && !empty($sid)) {
                $cid = $_GET['cid'];
                $type = $_GET['type'];
                $name = $veeam->getSharePointSiteName($rid, $sid);

                if (isset($cid) && !empty($cid)) {
                    $folders = $veeam->getSharePointTree($rid, $sid, $cid);

                    if (strcmp($type, 'list') === 0) { /* Lists have folders and items */
                        $items = $veeam->getSharePointTree($rid, $sid, $cid, 'Items');
                        $list = $veeam->getSharePointListName($rid, $sid, $cid, 'Lists');
                    } else { /* Libraries have folders and documents */
                        $documents = $veeam->getSharePointTree($rid, $sid, $cid, 'Documents');
                        $list = $veeam->getSharePointListName($rid, $sid, $cid, 'Libraries');
                    }
					?>
					<ul class="breadcrumb">
						<li><a href="sharepoint/<?php echo $org['id']; ?>"><i class="fa fa-reply"></i> Parent site</a></li>
						<?php
						if (isset($list) && !empty($list)) {
							echo '<li><a href="sharepoint/' . $org['id'] . '/' . $sid . '">' . $name["name"] . '</a></li>';
							echo '<li class="active">' . $list["name"]. '</li>'; 
						} else {
							echo '<li class="active">' . $name["name"] . '</li>';
						}
						?>
					</ul>
					<?php
					if (strcmp($type, 'list') === 0 && (count($folders['results']) == '0' && count($items['results']) == '0')) {
						echo '<p>No items available in this list.</p>';
					} elseif (strcmp($type, 'library') === 0 && (count($folders['results']) == '0' && count($documents['results']) == '0')) {
						echo '<p>No items available in this library.</p>';
					} else {
					?>
					<div class="col-sm-2 text-left">
						<div class="btn-group dropdown"> <!-- Multiple restore dropdown -->
							<button class="btn btn-default dropdown-toggle form-control" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">Restore selected <span class="caret"></span></button>
							<ul class="dropdown-menu dropdown-menu-right">
							  <li class="dropdown-header">Download as</li>
						      <li><a class="dropdown-link download-zip" data-itemid="multipleexport" data-itemname="<?php echo $owner['name']; ?>" data-type="multiple" data-siteid="<?php echo $sid; ?>" href="<?php echo $_SERVER['REQUEST_URI']; ?>#"><i class="fa fa-download"></i> ZIP file</a></li>
						      <li class="divider"></li>
							  <li class="dropdown-header">Restore to</li>
							  <li><a class="dropdown-link restore-original" data-itemid="multiplerestore" data-siteid="<?php echo $sid; ?>" data-type="multiple" href="<?php echo $_SERVER['REQUEST_URI']; ?>#"><i class="fa fa-upload"></i> Original location</a></li>
							</ul>
						</div>
					</div>
					<div class="col-sm-2">
						<select class="form-control padding" id="sharepoint-nav">
							<option disabled selected>-- Jump to folder --</option>
							<?php
							for ($i = 0; $i < count($folders['results']); $i++) {
							?>
								<option data-folderid="<?php echo $folders['results'][$i]['id']; ?>" data-siteid="<?php echo $sid; ?>"><?php echo $folders['results'][$i]['name']; ?></option>
							<?php
							}
							?>
						</select>
					</div>
					<div class="col-sm-8">
						<input class="form-control search" id="search-sharepoint" placeholder="Filter by item..." />
					</div>
					<table class="table table-bordered table-padding table-striped" id="table-sharepoint-items">
						<thead>
							<tr>
								<th class="text-center"><input type="checkbox" id="chk-all" title="Select all"></th>
								<?php
								if (strcmp($type, 'list') === 0) {
									echo '<th><strong>Title</strong></th>';
								} else {
									echo '<th><strong>Name</strong></th>';
								}
								?>
								<th><strong>Size</strong></th>
								<th><strong>Version</strong></th>
								<th class="text-center"><strong>Options</strong></th>
							</tr>
						</thead>
						<tbody>
							<?php
							for ($i = 0; $i < count($folders['results']); $i++) {
							?>
							<tr>
								<td></td>
								<td>
								<?php echo '<i class="far fa-folder"></i> <a class="sharepoint-folder" data-folderid="' . $folders['results'][$i]['id'] . '" data-parentid="index" data-siteid="' . $sid . '" href="sharepoint/' . $org['id'] . '/' . $sid . '/'. $cid . '/' . $type . '#">' . $folders['results'][$i]['name'] . '</a>'; ?><br />
								<em>Last modified: <?php echo date('d/m/Y H:i', strtotime($folders['results'][$i]['modificationTime'])) . ' (by ' . $folders['results'][$i]['modifiedBy'] . ')'; ?></em>
								</td>
								<td></td>
								<td></td>
								<td class="text-center">
									<div class="btn-group dropdown"> <!-- Single restore dropdown -->
										<button class="btn btn-default dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">Options <span class="caret"></span></button>
										<ul class="dropdown-menu dropdown-menu-right">
										  <li class="dropdown-header">Restore to</li>
										  <li><a class="dropdown-link restore-original" data-itemid="<?php echo $folders['results'][$i]['id']; ?>" data-siteid="<?php echo $sid; ?>" data-filetype="folders" data-type="single" href="<?php echo $_SERVER['REQUEST_URI']; ?>#"><i class="fa fa-upload"></i> Original location</a></li>
										</ul>
									</div>
								</td>
							</tr>
							<?php
							}

							if (strcmp($type, 'list') === 0) { /* Lists have folders and items */
								for ($i = 0; $i < count($items['results']); $i++) {
							?>
							<tr>
								<td></td>
								<td>
								<?php echo $items['results'][$i]['title']; ?><br />
								<em>Last modified: <?php echo date('d/m/Y H:i', strtotime($items['results'][$i]['modificationTime'])) . ' (by ' . $items['results'][$i]['modifiedBy'] . ')'; ?></em>
								</td>
								<td></td>
								<td><?php echo $items['results'][$i]['version']; ?></td>
								<td class="text-center">
									<div class="btn-group dropdown"> <!-- Single restore dropdown -->
										<button class="btn btn-default dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">Options <span class="caret"></span></button>
										<ul class="dropdown-menu dropdown-menu-right">
										  <li><a class="dropdown-link restore-original" data-itemid="<?php echo $items['results'][$i]['id']; ?>" data-siteid="<?php echo $sid; ?>" data-filetype='items' data-type="single" href="<?php echo $_SERVER['REQUEST_URI']; ?>#"><i class="fa fa-upload"></i> Restore item</a></li>
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
										  <li><a class="dropdown-link download-file" data-itemid="<?php echo $documents['results'][$i]['id']; ?>" data-itemname="<?php echo $documents['results'][$i]['name']; ?>" data-siteid="<?php echo $sid; ?>" data-filetype="documents" data-type="single" href="<?php echo $_SERVER['REQUEST_URI']; ?>#"><i class="fa fa-download"></i> Plain file</a></li>
										  <li><a class="dropdown-link download-zip" data-itemid="<?php echo $documents['results'][$i]['id']; ?>" data-itemname="<?php echo $documents['results'][$i]['name']; ?>" data-filetype="documents" data-type="single" data-siteid="<?php echo $sid; ?>" href="<?php echo $_SERVER['REQUEST_URI']; ?>#"><i class="fa fa-download"></i> ZIP file</a></li>
										  <li class="divider"></li>
										  <li class="dropdown-header">Restore to</li>
										  <li><a class="dropdown-link restore-original" data-itemid="<?php echo $documents['results'][$i]['id']; ?>" data-siteid="<?php echo $sid; ?>" data-filetype="documents" data-type="single" href="<?php echo $_SERVER['REQUEST_URI']; ?>#"><i class="fa fa-upload"></i> Original location</a></li>
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
					}
                } else { /* Select a library or list */
				    ?>
					<ul class="breadcrumb">
						<li><a href="sharepoint/<?php echo $org['id']; ?>"><i class="fa fa-reply"></i> Parent site</a></li>
						<li class="active"><?php echo $name['name']; ?></li>
					</ul>
                    <p>Select a library or list to view the specific content.</p>
					<?php
				}
            } else { /* List all sites */
			?>
			<table class="table table-bordered table-padding table-striped">
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
						<td><a href="sharepoint/<?php echo $org['id']; ?>/<?php echo $value['id']; ?>"><?php echo $value['name']; ?></a></td>
						<td class="text-center">
							<div class="btn-group dropdown"> <!-- Full restore dropdown -->
								<button class="btn btn-default dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">Options <span class="caret"></span></button>
								<ul class="dropdown-menu dropdown-menu-right">
								  <li class="dropdown-header">Restore to</li>
								  <li><a class="dropdown-link restore-original" data-itemid="<?php echo $value['name']; ?>" data-siteid="<?php echo $value['id']; ?>" data-type="full" href="<?php echo $_SERVER['REQUEST_URI']; ?>#"><i class="fa fa-upload"></i> Original location</a></li>
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

/* SharePoint Restore Buttons */
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

    var json = '{ "explore": { "datetime": "' + pit + '", "type": "vesp", "ShowAllVersions": "true", "ShowDeleted": "true" } }'; /* JSON code to start the restore session */

    $(':button').prop('disabled', true); /* Disable all buttons to prevent double start */

    $.get('veeam.php', {'action' : 'startrestore', 'json' : json, 'id' : oid}).done(function(data) {
        if (data.match(/([a-zA-Z0-9]{8})-([a-zA-Z0-9]{4})-([a-zA-Z0-9]{4})-([a-zA-Z0-9]{4})-([a-zA-Z0-9]{12})/g)) {
            e.preventDefault();

			Swal.fire({
				type: 'success',
				title: 'Session started',
				text: 'Restore session has been started and you can now perform item restores.'
			}).then(function(e) {
				window.location.href = 'sharepoint';
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
					window.location.href = 'sharepoint';
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
$('#search-sharepoint').keyup(function(e) {
    var searchText = $(this).val().toLowerCase();
    /* Show only matching row, hide rest of them */
    $.each($('#table-sharepoint-items tbody tr'), function(e) {
        if ($(this).text().toLowerCase().indexOf(searchText) === -1) {
           $(this).hide();
        } else {
           $(this).show();
        }
    });
});

/* Users and folder navigation content */
$('#sharepoint-nav').change(function(e) {
    var folderid = $('#sharepoint-nav option:selected').data('folderid');
    var siteid = $('#sharepoint-nav option:selected').data('siteid');
    var offset = 0;
    var rid = '<?php echo $rid; ?>';
	
	$('#table-sharepoint-items tbody').empty();
    loadItems(folderid, siteid, rid, offset);
});

/* Export and restore options for restore buttons based upon specific action per button */
/* Export to plain file */
$('.download-file').click(function(e) {
    var filetype = $(this).data('filetype');
	var itemid = $(this).data('itemid');
    var itemname = $(this).data('itemname');
    var siteid = $(this).data('siteid');
    var rid = '<?php echo $rid; ?>';
	var json = '{ "save": { "asZip": "false" } }';
	
	Swal.fire({
		type: 'info',
		title: 'Download is starting',
		text: 'Export in progress and your download will start soon...'
	})

	$.get('veeam.php', {'action' : 'exportsharepointitem', 'itemid' : itemid, 'siteid' : siteid, 'rid'	: rid, 'json' : json, 'type' : filetype}).done(function(data) {
		e.preventDefault();

		if (data) {
			$.redirect('download.php', {ext : 'plain', file : data, name : itemname}, 'POST');
			
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
	var filetype = $(this).data('filetype');
	var itemid = $(this).data('itemid');
    var itemname = $(this).data('itemname');
    var siteid = $(this).data('siteid');
    var rid = '<?php echo $rid; ?>';
	var type = $(this).data('type');

	Swal.fire({
		type: 'info',
		title: 'Download is starting',
		text: 'Export in progress and your download will start soon...'
	})
	
	if (type == 'multiple') { /* Multiple items export */
		var act = 'exportmultiplesharepointitems';
		var filetype = 'documents';
		var ids = '';
		var filename = 'exported-sharepointitems-' + $(this).data('itemname'); /* exported-sharepointitems-username */  
		
		if ($("input[name='checkbox-sharepoint']:checked").length == 0) { /* Error handling for multiple export button */
			Swal.close();
			
			Swal.fire({
				type: 'error',
				title: 'Restore failed',
				text: 'No items have been selected.'
			})
			
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
		if (type == 'single') {	/* Single item export */
			var act = 'exportsharepointitem';
			var filename = $(this).data('itemname');
		} else { /* Full SharePoint export */
			var act = 'exportsharepoint';
			var filename = 'sharepoint-' + $(this).data('itemname'); /* sharepoint-username */
		}
		
		var filetype = $(this).data('filetype');
		var json = '{ "save": { "asZip": "true" } }';
	}

	$.get('veeam.php', {'action' : act, 'itemid' : itemid, 'siteid' : siteid, 'rid' : rid, 'json' : json, 'type' : filetype}).done(function(data) {
		e.preventDefault();
	console.log(data);
	console.log(json);
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
    var siteid = $(this).data('siteid');
    var rid = '<?php echo $rid; ?>';
	var type = $(this).data('type');
	
	if (type == 'multiple' && $("input[name='checkbox-sharepoint']:checked").length == 0) { /* Error handling for multiple restore button */
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
			'<option value="merge">Merge file</option>' +
			'<option value="overwrite">Overwrite file</option>' +
			'</select></div>' +
			'</div>' +
			'<div class="form-group row">' +
			'<label for="restore-original-permissions" class="col-sm-4 col-form-label text-right">Restore permissions:</label>' +
			'<div class="col-sm-8"><select class="form-control restoredata" id="restore-original-permissions">' +
			'<option value="true">Yes</option>' +
			'<option value="false">No</option>' +
			'</select></div>' +
			'<input type="hidden" id="restore-original-listname" value="<?php echo $list['name']; ?>"></input>' +
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
					$('#restore-original-listname').val(),
					$('#restore-original-permissions').val(),
				 ]);
			});
		},
	}).then(function(result) {
		if (result.value) {
			var user = $('#restore-original-user').val();
			var pass = $('#restore-original-pass').val();
			var listname = $('#restore-original-listname').val();
        	var restoreaction = $('#restore-original-action').val();
		    var restorepermissions = $('#restore-original-permissions').val();
		
			Swal.fire({
				type: 'info',
				title: 'Item restore in progress',
				text: 'Restore in progress...'
			})
			
			if (type == 'multiple') { /* Multiple items restore */
				var act = 'restoremultiplesharepointitems';
				filetype = 'documents';
				var ids = '';
				
				$("input[name='checkbox-sharepoint']:checked").each(function(e) {
					ids = ids + '{ "Id": "' + this.value + '" }, ';
				});
				
				var json = '{ "restoreTo": \
					{ "userName": "' + user + '", \
					  "userPassword": "' + pass + '", \
					  "list" : "' + listname + '", \
					  "restorePermissions" : "' + restorepermissions + '", \
					  "sendSharedLinksNotification": "true", \
					  "documentVersion" : "last", \
					  "documentLastVersionAction" : "' + restoreaction + '", \
					  "Documents": [ \
						' + ids + ' \
					  ] \
					} \
				}';
			} else {
				if (type == 'single') { /* Single item restore */
					var act = 'restoresharepointitem';
					
					if ((filetype == 'libraries') || (filetype == 'lists')) {
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
				} else if (type == 'full') { /* Full SharePoint restore */
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

			$.get('veeam.php', {'action' : act, 'itemid' : itemid, 'siteid' : siteid, 'rid' : rid, 'json' : json, 'type' : filetype}).done(function(data) {
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
$('.sharepoint-folder').click(function(e) {
    var folderid = $(this).data('folderid');
    var parentid = $(this).data('parentid');
    var siteid = $(this).data('siteid');
    var rid = '<?php echo $rid; ?>';

    loadFolderItems(folderid, parentid, rid, siteid);
});
$('.sharepoint-folder-up').click(function(e) {
    var parentid = $(this).data('parentid');
    var siteid = $(this).data('siteid');
    var rid = '<?php echo $rid; ?>';

    if (parentid == 'index') {
        window.location.href = window.location.href.split('#')[0];
        return false;
    } else {
        loadParentFolderItems(parentid, rid, siteid);
    }
});

/* Load more link */
$('.load-more-link').click(function(e) {
    var folderid = $(this).data('folderid');
    var siteid = $(this).data('siteid');
    var offset = $(this).data('offset');
    var rid = '<?php echo $rid; ?>';

    loadItems(folderid, siteid, rid, offset);
});

/* SharePoint functions */
/*
 * @param response JSON data
 * @param siteid SharePoint Site ID
 * @param type Documents or items
 */
function fillTableDocuments(response, siteid, type) {
    if (response.results.length != '0') {
        for (var i = 0; i < response.results.length; i++) {
            if (type == 'documents') {
                var size = filesize(response.results[i].sizeBytes, {round: 2});
				
				$('#table-sharepoint-items tbody').append('<tr> \
				<td></td> \
                <td>' + response.results[i].name + '<br /><em>Last modified: ' + moment(response.results[i].modificationTime).format('DD/MM/YYYY HH:mm') + ' (by ' + response.results[i].modifiedBy + ')</em></td> \
                <td>' + size + '</td> \
                <td>' + response.results[i].version + '</td> \
                <td class="text-center"> \
                <div class="dropdown"> \
                <button class="btn btn-default dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">Options <span class="caret"></span></button> \
                <ul class="dropdown-menu dropdown-menu-right"> \
                <li class="dropdown-header">Download as</li> \
                <li><a class="dropdown-link download-file" data-itemid="' + response.results[i].id + '" data-itemname="' + response.results[i].id + '" data-siteid="' + siteid + '" data-filetype="documents" href="' + window.location + '"><i class="fa fa-download"></i> Plain file</a></li> \
				<li><a class="dropdown-link download-zip" data-itemid="' + response.results[i].id + '" data-itemname="' + response.results[i].id + '" data-filetype="documents" data-type="single" data-siteid="' + siteid + '" href="' + window.location + '"><i class="fa fa-download"></i> ZIP file</a></li> \
                <li class="divider"></li> \
                <li class="dropdown-header">Restore to</li> \
                <li><a class="dropdown-link restore-original" data-itemid="' + response.results[i].id + '" data-siteid="' + siteid + '" data-filetype="documents" href="' + window.location + '"><i class="fa fa-upload"></i> Original location</a></li> \
                </ul> \
                </div> \
                </td> \
                </tr>');
            } else {			
				$('#table-sharepoint-items tbody').append('<tr> \
				<td class="text-center"><input type="checkbox" name="checkbox-sharepoint" value="' + response.results[i].id + '"></td> \
                <td>' + response.results[i].name + '<br /><em>Last modified: ' + moment(response.results[i].modificationTime).format('DD/MM/YYYY HH:mm') + ' (by ' + response.results[i].modifiedBy + ')</em></td> \
                <td></td> \
                <td>' + response.results[i].version + '</td> \
                <td class="text-center"> \
                <div class="dropdown"> \
                <button class="btn btn-default dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">Options <span class="caret"></span></button> \
                <ul class="dropdown-menu dropdown-menu-right"> \
                <li class="dropdown-header">Download as</li> \
                <li><a class="dropdown-link download-file" data-itemid="' + response.results[i].id + '" data-itemname="' + response.results[i].id + '" data-siteid="' + siteid + '" data-filetype="documents" href="' + window.location + '"><i class="fa fa-download"></i> Plain file</a></li> \
				<li><a class="dropdown-link download-zip" data-itemid="' + response.results[i].id + '" data-itemname="' + response.results[i].id + '" data-filetype="documents" data-type="single" data-siteid="' + siteid + '" href="' + window.location + '"><i class="fa fa-download"></i> ZIP file</a></li> \
                <li class="divider"></li> \
                <li class="dropdown-header">Restore to</li> \
                <li><a class="dropdown-link restore-original" data-itemid="' + response.results[i].id + '" data-siteid="' + siteid + '" data-filetype="documents" href="' + window.location + '"><i class="fa fa-upload"></i> Original location</a></li> \
                </ul> \
                </div> \
                </td> \
                </tr>');
            }            
        }
    }
}

/*
 * @param response JSON data
 * @param folderid Folder ID
 * @param siteid SharePoint Site ID
 */
function fillTableFolders(response, folderid, siteid) {
    if (response.results.length != '0') {
        for (var i = 0; i < response.results.length; i++) {
            $('#table-sharepoint-items tbody').append('<tr> \
				<td></td> \
                <td><a class="sharepoint-folder" data-folderid="' + response.results[i].id + '" data-parentid="' + folderid +'" data-siteid="<?php echo $sid; ?>" href="'+ window.location +'">' + response.results[i].name + '</a><br /><em>Last modified: ' + moment(response.results[i].modificationTime).format('DD/MM/YYYY HH:mm') + ' (by ' + response.results[i].modifiedBy + ')</em></td> \
                <td></td> \
                <td></td> \
                <td class="text-center"> \
                <div class="dropdown"> \
                <button class="btn btn-default dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">Options <span class="caret"></span></button> \
                <ul class="dropdown-menu dropdown-menu-right"> \
                <li class="dropdown-header">Restore to</li> \
                <li><a class="dropdown-link restore-original" data-itemid="' + response.results[i].id + '" data-siteid="' + siteid + '" data-filetype="folders" href="' + window.location + '"><i class="fa fa-upload"></i> Original location</a></li> \
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
 * @param siteid SharePoint Site ID
 */
function loadFolderItems(folderid, parentid, rid, siteid) { /* Used for navigation to next folder */
    var responsedocuments, responsefolders;

    /* First we load the folders */
    $.get('veeam.php', {'action' : 'getsharepointitemsbyfolder', 'folderid' : folderid, 'rid' : rid, 'siteid' : siteid, 'type' : 'folders'}).done(function(data) {
        responsefolders = JSON.parse(data);
    });

    <?php
    if (strcmp($type, 'list') === 0) {
    ?>
    /* Second we load the items */
    $.get('veeam.php', {'action' : 'getsharepointitemsbyfolder', 'folderid' : folderid, 'rid' : rid, 'siteid' : siteid, 'type' : 'items'}).done(function(data) {
        responsedocuments = JSON.parse(data);
    });
    <?php
    } else {
    ?>
     /* Second we load the documents */
    $.get('veeam.php', {'action' : 'getsharepointitemsbyfolder', 'folderid' : folderid, 'rid' : rid, 'siteid' : siteid, 'type' : 'documents'}).done(function(data) {
        responsedocuments = JSON.parse(data);
    });
    <?php
    }
     ?>

    /* We delay the output to assure our call is performed */
    setTimeout(function(e) {
        $('#table-sharepoint-items tbody').empty();
        $('#table-sharepoint-items tbody').append('<tr><td colspan="4"><a class="sharepoint-folder-up" data-parentid="' + parentid + '" data-siteid="<?php echo $sid; ?>" href="' + window.location + '"><i class="fa fa-reply"></i> Parent directory</a></td></tr>');

        if (typeof responsefolders !== undefined) {
            fillTableFolders(responsefolders, folderid, siteid);
        }

        if (typeof responsedocuments !== undefined) {
            <?php
            if (strcmp($type, 'list') === 0) {
            ?>
            fillTableDocuments(responsedocuments, siteid, 'items');
            <?php
            } else {
            ?>
            fillTableDocuments(responsedocuments, siteid, 'documents');
            <?php
            }
            ?>
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
 * @param siteid SharePoint Site ID
 */
function loadParentFolderItems(parentid, rid, siteid) { /* Used for navigation to parent folder */
    var newparentid, parentdata, parenturl, responsedocuments, responsefolders;

    /* First we load the folders */
    $.get('veeam.php', {'action' : 'getsharepointitemsbyfolder', 'folderid' : parentid, 'rid' : rid, 'siteid' : siteid, 'type' : 'folders'}).done(function(data) {
        responsefolders = JSON.parse(data);
    });

    /* Second we load the documents */
    $.get('veeam.php', {'action' : 'getsharepointitemsbyfolder', 'folderid' : parentid, 'rid' : rid, 'siteid' : siteid, 'type' : 'documents'}).done(function(data) {
        responsedocuments = JSON.parse(data);
    });

    /* We delay the output to assure our call is performed */
    setTimeout(function(e) {
        if (typeof responsefolders !== undefined && responsefolders.results.length != '0') {
            parenturl = responsefolders.results[0]._links.parent.href;
            newparentid = parenturl.split('/').pop();

            $.get('veeam.php', {'action' : 'getsharepointparentfolder', 'folderid' : newparentid, 'rid' : rid, 'siteid' : siteid, 'type' : 'folders'}).done(function(data) {
                parentdata = JSON.parse(data);

                if (parentdata === undefined || parentdata._links.parent === undefined) {
                    newparentid = 'index';
                } else {
                    parenturl = parentdata._links.parent.href;
                    newparentid = parenturl.split('/').pop();
                }
            });
        } else if (typeof responsedocuments !== undefined && responsedocuments.results.length != '0') {
            parenturl = responsedocuments.results[0]._links.parent.href;
            newparentid = parenturl.split('/').pop();

            $.get('veeam.php', {'action' : 'getsharepointparentfolder', 'folderid' : newparentid, 'rid' : rid, 'siteid' : siteid, 'type' : 'documents'}).done(function(data) {
                parentdata = JSON.parse(data);

                if (parentdata === undefined || parentdata._links.parent === undefined) {
                    newparentid = 'index';
                } else {
                    parenturl = parentdata._links.parent.href;
                    newparentid = parenturl.split('/').pop();
                }
            });
        } else {
            return false;
        }

        setTimeout(function(e) {
            $('#table-sharepoint-items tbody').empty();
            $('#table-sharepoint-items tbody').append('<tr><td colspan="4"><a class="sharepoint-folder-up" data-parentid="' + newparentid + '" data-siteid="<?php echo $sid; ?>" href="' + window.location + '"><i class="fa fa-reply"></i> Parent directory</a></td></tr>');

            if (typeof responsefolders !== undefined) {
                fillTableFolders(responsefolders, parentid, siteid);
            }

            if (typeof responsedocuments !== undefined) {
                <?php
                if (strcmp($type, 'list') === 0) {
                ?>
                fillTableDocuments(responsedocuments, siteid, 'items');
                <?php
                } else {
                ?>
                fillTableDocuments(responsedocuments, siteid, 'documents');
                <?php
                }
                ?>
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
 * @param siteid SharePoint Site ID
 * @param rid Restore session ID
 * @param offset Offset
 */
function loadItems(folderid, siteid, rid, offset) { /* Used for loading additional items in a folder */
    var responsedocuments, responsefolders;

    $.get('veeam.php', {'action' : 'getsharepointitems', 'folderid' : folderid, 'rid' : rid, 'siteid' : siteid, 'offset' : offset, 'type' : 'folders'}).done(function(data) {
        responsefolders = JSON.parse(data);
    });

    $.get('veeam.php', {'action' : 'getsharepointitems', 'folderid' : folderid, 'rid' : rid, 'siteid' : siteid, 'offset' : offset, 'type' : 'documents'}).done(function(data) {
        responsedocuments = JSON.parse(data);
    });

    /* We delay the output to assure our call is performed */
    setTimeout(function(e) {
        if (typeof responsefolders !== undefined) {
            fillTableFolders(responsefolders, folderid, siteid);
        }

        if (typeof responsedocuments !== undefined) {
            fillTableDocuments(responsedocuments, siteid);
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