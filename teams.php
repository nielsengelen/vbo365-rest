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
	<link rel="stylesheet" type="text/css" href="css/teams.css" />
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
	  <li><a href="sharepoint">SharePoint</a></li>
	  <?php
	  }
	  ?>
	  <li class="active"><a href="teams">Teams</a></li>
	</ul>
	<ul class="nav navbar-nav navbar-right">
	  <li><a href="#"><span class="fa fa-user"></span> Welcome <i><?php echo $user; ?></i> !</a></li>
	  <li id="logout"><a href="#"><span class="fa fa-sign-out-alt"></span> Logout</a></li>
	</ul>
</nav>
<div class="container-fluid">
    <div id="sidebar">
        <div class="logo-container"><i class="logo fa fa-user-friends"></i></div>
        <div class="separator"></div>
        <menu class="menu-segment" id="menu">
		<?php
		if (!isset($_SESSION['rid'])) {
			echo '<ul id="ul-teams-organizations">';
			
			if (strtolower($authtype) !== 'mfa' && $check === false && strtolower($administrator) === 'yes') {
				if (isset($_GET['oid'])) $oid = $_GET['oid'];
				
				$org = $veeam->getOrganizations();
				$menu = false;
				
				for ($i = 0; $i < count($org); $i++) {
					if (isset($oid) && !empty($oid) && $oid == $org[$i]['id']) {
						echo '<li class="active"><a href="teams/' . $org[$i]['id'] . '">' . $org[$i]['name'] . '</a></li>';
					} else {
						echo '<li><a href="teams/' . $org[$i]['id'] . '">' . $org[$i]['name'] . '</a></li>';
					}
				}
			} else {
				$org = $veeam->getOrganization();
				$oid = $org['id'];
				$menu = true;
				$restoretype = 'tenant';
				
				echo '<li class="active"><a href="teams">' . $org['name'] . '</a></li>';
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
				if (isset($_GET['tid'])) $tid = $_GET['tid'];
				
				$content = array();
				$org = $veeam->getOrganizationID($rid);
				$oid = $org['id'];
				$teams = $veeam->getTeams($rid);

				echo '<button class="btn btn-default btn-danger btn-stop-restore" title="Stop Restore">Stop Restore</button>';
				echo '<div class="separator"></div>';
				echo '<span class="teams-menu-channels"><strong>Teams Browser</strong></span>';
				echo '<div class="separator"></div>';
				echo '<ul id="ul-teams-data">';
			
				for ($i = 0; $i < count($teams['results']); $i++) {
					array_push($content, array('name'=> $teams['results'][$i]['displayName'], 'id' => $teams['results'][$i]['id']));
				}

				uasort($content, function($a, $b) {
					return strcasecmp($a['name'], $b['name']);
				});

				foreach ($content as $key => $value) {
					if (isset($tid) && !empty($tid) && $tid == $value['id']) {
						echo '<li class="active"><a href="teams/' . $oid . '/' . $value['id'] . '">' . $value['name'] . '</a></li>';
					} else {
						echo '<li><a href="teams/' . $oid . '/' . $value['id'] . '">' . $value['name'] . '</a></li>';
					}
				}
				
				echo '</ul>';
			}
		}
		?>
        </menu>
        <div class="separator"></div>
        <div class="bottom-padding"></div>
    </div>
    <div id="main">
		<h1>Teams</h1>
        <div class="teams-container">
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
					$org = $veeam->getOrganizationByID($oid);
					$repo = $veeam->getOrganizationRepository($oid);
					
					if (isset($repo) && empty($repo)) {
						echo '<p>No Teams data found for this organization.</p>';
						exit;
					}
					
					$repohref = explode('/', $repo[0]['_links']['backupRepository']['href']);
					$repoid = end($repohref);
					$orgdata = $veeam->getOrganizationData($repoid);
					$teams = $veeam->getTeamData($repoid);
					$teamsarray = array();
					
					for ($i = 0; $i < count($orgdata['results']); $i++) {
						if (strcasecmp($org['name'], $orgdata['results'][$i]['displayName']) === 0) {
							$orgid = $orgdata['results'][$i]['organizationId'];
						}
					}
					
					if (count($teams['results']) !== 0) {
						for ($i = 0; $i < count($teams['results']); $i++) {
							if (strcmp($teams['results'][$i]['organizationId'], $orgid) === 0) {
								array_push($teamsarray, array(
									'id' => $teams['results'][$i]['id'],
									'name' => $teams['results'][$i]['displayName']
								));
							}
						}
						
						usort($teamsarray, function($a, $b) {
							return strcmp($a['name'], $b['name']);
						});
					}
						
					if (isset($orgid) && count($teamsarray) !== 0) {
					?>
					<div class="alert alert-info">The following is a limited overview with backed up Teams data within the organization. To view the full list, start a restore session.</div>
					<table class="table table-bordered table-padding table-striped">
						<thead>
							<tr>
								<th>Team</th>
								<th>Objects In Backup</th>
							</tr>
						</thead>
						<tbody>
						<?php
							for ($i = 0; $i < count($teamsarray); $i++) {							
								echo '<tr>';
								echo '<td>' . $teamsarray[$i]['name'] . '</td>';
								echo '<td><i class="fa fa-user-friends fa-2x" style="color:green" title="Channel"></i></td>';
								echo '</tr>';
							}
						?>
						</tbody>
					</table>
					<?php
					} else {
						if (strtolower($authtype) !== 'mfa' && $check === false && strtolower($administrator) === 'yes') {
							echo '<p>No Teams data found for this organization.</p>';
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
				if (isset($_GET['tid'])) $tid = $_GET['tid'];
				
				if (isset($tid) && !empty($tid)) {
					$channels = $veeam->getTeamsChannels($rid, $tid);
					
					if (count($channels['results']) !== 0) {
					?>
					<div class="row">
						<div class="col-sm-2 zeroPadding">
							<table class="table table-bordered table-padding table-striped" id="table-teams-channels">
								<tbody>
									<tr>
										<td>
											<input type="text" class="form-control search" id="jstree_q" placeholder="Find a channel...">
											<a href="/teams/<?php echo $oid; ?>/<?php echo $tid; ?>"><i class="fas fa-columns"></i> Channels</a>
											<div id="jstree">
												<ul>	
												<?php
												for ($i = 0; $i < count($channels['results']); $i++) {
													echo '<li data-channelid="'.$channels['results'][$i]['id'].'" data-jstree=\'{ "opened" : true }\'>'.$channels['results'][$i]['displayName'].'</li>';
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
													if (data === undefined || data.node === undefined || data.node.id === undefined || data.node.data.channelid === undefined)
														return;

													var channelid = data.node.data.channelid;
													var node = $('#jstree').jstree('get_selected');

													if (data.node.children.length === 0 && data.node.parents.length === 1) {
														$('#jstree').jstree('create_node', node, {data: {"channelid" : channelid}, text: 'Posts'});
														$('#jstree').jstree('create_node', node, {data: {"channelid" : channelid}, text: 'Files'});
														$('#jstree').jstree('create_node', node, {data: {"channelid" : channelid}, text: 'Tabs'});				
													} else {
														var item = data.node.text.toLowerCase();
														
														$('input:checkbox').prop('checked', false);
														
														switch (item) {
															case 'files':
																loadChannelFiles(channelid);
																break;
															case 'posts':
																loadChannelPosts(channelid);
																break;
															case 'tabs':
																loadChannelTabs(channelid);
																break;
														}
													}
													
													$('#jstree').jstree('open_node', node);
													$(':button').prop('disabled', false);
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
							<div class="tab-content">
								<div class="tab-pane active fade in" role="tabpanel" id="channels">
									<table class="table table-bordered table-padding table-striped" id="table-teams-channels">
										<thead>
											<tr>
												<th>Channel</th>
												<th class="text-center">Options</th>
											</tr>
										</thead>
										<tbody>
											<?php
											for ($i = 0; $i < count($channels['results']); $i++) {
											?>
											<tr>
												<td><a href="javascript:void(0);" onclick="loadChannelPosts('<?php echo $channels['results'][$i]['id']; ?>');"><?php echo $channels['results'][$i]['displayName']; ?></a></td>
												<td class="text-center">
													<div class="btn-group dropdown">
														<button class="btn btn-default dropdown-toggle form-control" data-toggle="dropdown">Restore <span class="caret"></span></button>
														<ul class="dropdown-menu">
														  <li class="dropdown-header">Restore to</li>
														  <li><a class="dropdown-link" href="javascript:void(0);" onclick="restoreChannelToOriginal('<?php echo $channels['results'][$i]['id']; ?>')"><i class="fa fa-upload"></i> Original location</a></li>
														</ul>
													</div>
												</td>
											</tr>
											<?php
											}
											?>
										</tbody>
									</table>
								</div>
								<div class="tab-pane fade in" role="tabpanel" id="posts">
									<div class="teams-controls-padding hide" id="teams-controls-posts">
										<input class="form-control search search-teams" placeholder="Filter by item...">
										<div class="form-inline">
											<strong class="btn-group">Posts:</strong>
											<div class="btn-group dropdown">
												<button class="btn-link dropdown-toggle" data-toggle="dropdown">Export <span class="caret"></span></button>
												<ul class="dropdown-menu">
												  <li><a class="dropdown-link" href="javascript:void(0);" onclick="downloadHTML('<?php echo $tid; ?>', 'full')"><i class="fa fa-download"></i> All Items</a></li>
												  <li><a class="dropdown-link" href="javascript:void(0);" onclick="downloadHTML('<?php echo $tid; ?>', 'multiple')"><i class="fa fa-download"></i> Selected Items</a></li>
												</ul>
											</div>
											<div class="btn-group dropdown">
												<button class="btn-link dropdown-toggle" data-toggle="dropdown">Restore <span class="caret"></span></button>
												<ul class="dropdown-menu">
												  <li><a class="dropdown-link" href="javascript:void(0);" onclick="restorePostsToOriginal()"><i class="fa fa-upload"></i> All Items</a></li>
												</ul>
											</div>
										</div>
									</div>
									<table class="table table-bordered table-padding table-striped" id="table-teams-posts">
										<thead>
											<tr>
												<th class="text-center"><input type="checkbox" class="chk-all" title="Select all"></th>
												<th>Author</th>
												<th>Subject</th>
												<th>Created</th>
												<th>Last Modified</th>
												<th class="text-center">Options</th>
											</tr>
										</thead>
										<tbody>
											<tr>
												<td colspan="6">Select a channel to view the related items.</td>
											</tr>
										</tbody>
									</table>
									<div class="text-center">
										<a class="btn btn-default hide load-more-link load-more-items load-more-posts" data-offset="0" data-type="posts" href="<?php echo $_SERVER['REQUEST_URI']; ?>#">Load more posts</a>
									</div>
								</div>
								<div class="tab-pane fade in" role="tabpanel" id="files">
									<div class="teams-controls-padding hide" id="teams-controls-files">
										<input class="form-control search search-teams" placeholder="Filter by item...">
										<div class="form-inline">
											<strong class="btn-group">Files:</strong>
											<div class="btn-group dropdown">
												<button class="btn-link dropdown-toggle" data-toggle="dropdown" >Export <span class="caret"></span></button>
												<ul class="dropdown-menu">
												  <li><a class="dropdown-link" href="javascript:void(0);" onclick="downloadZIP('<?php echo $tid; ?>', '<?php echo $tid; ?>', 'full')"><i class="fa fa-download"></i> All Items</a></li>
												  <li><a class="dropdown-link" href="javascript:void(0);" onclick="downloadZIP('<?php echo $tid; ?>', '<?php echo $tid; ?>', 'multiple')"><i class="fa fa-download"></i> Selected Items</a></li>
												</ul>
											</div>
											<div class="btn-group dropdown">
												<button class="btn-link dropdown-toggle" data-toggle="dropdown">Restore <span class="caret"></span></button>
												<ul class="dropdown-menu">
												  <li><a class="dropdown-link" href="javascript:void(0);" onclick="restoreFilesToOriginal('<?php echo $tid; ?>', 'full')"><i class="fa fa-upload"></i> All Items</a></li>
												  <li><a class="dropdown-link" href="javascript:void(0);" onclick="restoreFilesToOriginal('<?php echo $tid; ?>', 'multiple')"><i class="fa fa-upload"></i> Selected Items</a></li>
												</ul>
											</div>
										</div>
									</div>
									<table class="table table-bordered table-padding table-striped" id="table-teams-files">
										<thead>
											<tr>
												<th class="text-center"><input type="checkbox" class="chk-all" title="Select all"></th>
												<th>Name</th>
												<th>Size</th>
												<th>Last Modified</th>
												<th>Version</th>
												<th class="text-center">Options</th>
											</tr>
										</thead>
										<tbody>
											<tr>
												<td colspan="6">Select a channel to view the related items.</td>
											</tr>
										</tbody>
									</table>
									<div class="text-center">
										<a class="btn btn-default hide load-more-link load-more-items load-more-files" data-offset="0" data-type="files" href="<?php echo $_SERVER['REQUEST_URI']; ?>#">Load more files</a>
									</div>
								</div>
								<div class="tab-pane fade in" role="tabpanel" id="tabs">
									<div class="teams-controls-padding hide" id="teams-controls-tabs">
										<input class="form-control search search-teams" placeholder="Filter by item...">
										<div class="form-inline">
											<strong class="btn-group">Tabs:</strong>
											<div class="btn-group dropdown">
												<button class="btn-link dropdown-toggle" data-toggle="dropdown">Restore <span class="caret"></span></button>
												<ul class="dropdown-menu">
												  <li><a class="dropdown-link" href="javascript:void(0);" onclick="restoreTabsToOriginal('<?php echo $tid; ?>', 'full')"><i class="fa fa-upload"></i> All Items</a></li>
												  <li><a class="dropdown-link" href="javascript:void(0);" onclick="restoreTabsToOriginal('<?php echo $tid; ?>', 'multiple')"><i class="fa fa-upload"></i> Selected Items</a></li>
												</ul>
											</div>
										</div>
									</div>
									<table class="table table-bordered table-padding table-striped" id="table-teams-tabs">
										<thead>
											<tr>
												<th class="text-center"><input type="checkbox" class="chk-all" title="Select all"></th>
												<th>Name</th>
												<th>Type</th>
												<th>Content URL</th>
												<th class="text-center">Options</th>
											</tr>
										</thead>
										<tbody>
											<tr>
												<td colspan="6">Select a channel to view the related items.</td>
											</tr>
										</tbody>
									</table>
									<div class="text-center">
										<a class="btn btn-default hide load-more-link load-more-items load-more-tabs" data-offset="0" data-type="tabs" href="<?php echo $_SERVER['REQUEST_URI']; ?>#">Load more tabs</a>
									</div>
								</div>
							</div>
						</div>
					</div>
					<?php
					} else {
						echo '<p>No channels available for this Team.</p>';
					}
				} else {
				?>
				<table class="table table-bordered table-padding table-striped" id="table-teams-data">
					<thead>
						<tr>
							<th>Team</th>
							<th class="text-center">Options</th>
						</tr>
					</thead>
					<tbody>
						<?php
						$teamslist = array();

						for ($i = 0; $i < count($teams['results']); $i++) {
							array_push($teamslist, array('name'=> $teams['results'][$i]['displayName'], 'id' => $teams['results'][$i]['id']));
						}

						uasort($teamslist, function($a, $b) {
							return strcasecmp($a['name'], $b['name']);
						});

						foreach ($teamslist as $key => $value) {
						?>
						<tr>
							<td><a href="teams/<?php echo $oid; ?>/<?php echo $value['id']; ?>"><?php echo $value['name']; ?></a></td>
							<td class="text-center">
								<div class="btn-group dropdown">
									<button class="btn btn-default dropdown-toggle" data-toggle="dropdown">Restore <span class="caret"></span></button>
									<ul class="dropdown-menu">
									  <li class="dropdown-header">Restore to</li>
									  <li><a class="dropdown-link" href="javascript:void(0);" onclick="restoreTeamToOriginal('<?php echo $value['id']; ?>')"><i class="fa fa-upload"></i> Original location</a></li>
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
    </div>
</div>

<div class="modal" id="conversationModalCenter" role="dialog">
    <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h1 class="modal-title">Conversation details</h1>
      </div>
      <div class="modal-body">
        <table class="table table-bordered table-padding table-striped" id="table-teams-posts-conversation">
			<thead>
				<tr>
					<th>Author</th>
					<th>Subject</th>
					<th>Created</th>
					<th>Last Modified</th>
					<th class="text-center">Options</th>
				</tr>
			</thead>
			<tbody>
			</tbody>
		</table>
      </div>
	  <div class="modal-footer">
        <button type="button" class="btn btn-success" data-dismiss="modal">Close</button>
      </div>
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

    var json = '{ "explore": { "datetime": "' + pit + '", "type": "vet", "ShowAllVersions": "true", "ShowDeleted": "true" } }';

    $(':button').prop('disabled', true);
	
	Swal.fire({
		icon: 'info',
		title: 'Restore session',
		text: 'Just a moment while the restore session is starting',
		allowOutsideClick: false,
	});

    $.post('veeam.php', {'action' : 'startrestore', 'json' : json, 'id' : oid}).done(function(data) {
		if (data.match(/([a-zA-Z0-9]{8})-([a-zA-Z0-9]{4})-([a-zA-Z0-9]{4})-([a-zA-Z0-9]{4})-([a-zA-Z0-9]{12})/g)) {
			Swal.fire({
				icon: 'success',
				title: 'Restore session',
				text: 'Restore session has been started and you can now perform restores'
			}).then(function(e) {
				window.location.href = 'teams';
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
						window.location.href = 'teams';
					});
				} else {
					var response = JSON.parse(data);
				
					swalWithBootstrapButtons.fire({
						icon: 'error', 
						title: 'Restore session',
						text: '' + response.slice(0, -1),
					}).then(function(e) {
						window.location.href = 'teams';
					});
				}
			});
		  } else {
			return;
		}
	})
});

$('.chk-all').click(function(e) {
    var table = $(e.target).closest('table');
    $('tr:visible :checkbox', table).prop('checked', this.checked);
});

$('.search-teams').keyup(function(e) {
    var searchText = $(this).val().toLowerCase();
	var type = $('#jstree').jstree('get_selected', true)[0].text.toLowerCase();
	
    $.each($('#table-teams-'+type+' tbody tr'), function(e) {
        if ($(this).text().toLowerCase().indexOf(searchText) === -1) {
           $(this).hide();
        } else {
           $(this).show();
        }
    });
});

function restoreTeamToOriginal(teamid) {
    var rid = '<?php echo $rid; ?>';
	
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

							var json = '{ "restore": \
								{ "userName": "' + user + '", \
								  "userPassword": "' + pass + '", \
								  "RestoreChangedItems": "true", \
								  "RestoreMissingItems": "true", \
								  "RestoreMembers": "true", \
								  "RestoreSettings": "true", \
								} \
							}';

							$.post('veeam.php', {'action' : 'restoreteam', 'teamid' : teamid, 'rid' : rid, 'json' : json}).done(function(data) {
								var response = JSON.parse(data);

								if (response['restoreFailed'] === undefined) {
									var result = 'Total items restored: ' + response['totalItemsCount'] + '<br><hr>';
									
									if (response['restoredItemsCount'] >= '1') {
										result += response['restoredItemsCount'] + ' item(s) successfully restored<br>';
									}
									
									if (response['failedItemsCount'] >= '1') {
										result += response['failedItemsCount'] + ' item(s) failed<br>';
									}
									
									if (response['skippedItemsCount'] >= '1') {
										result += response['skippedItemsCount'] + ' item(s) skipped (unchanged item)';
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
							  return;
							});
							clipboard.on('error', function(e) {
							  setTooltip('Failed');
							  hideTooltip();
							  return;
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
										
										var json = '{ "restore": \
											{ "userCode": "' + usercode + '", \
											  "RestoreChangedItems": "true", \
											  "RestoreMissingItems": "true", \
											  "RestoreMembers": "true", \
											  "RestoreSettings": "true", \
											} \
										}';

										$.post('veeam.php', {'action' : 'restoreteam', 'teamid' : teamid, 'rid' : rid, 'json' : json}).done(function(data) {
											var response = JSON.parse(data);
											
											if (response['restoreFailed'] === undefined) {
												var result = 'Total items restored: ' + response['totalItemsCount'] + '<br><hr>';
													
												if (response['restoredItemsCount'] >= '1') {
													result += response['restoredItemsCount'] + ' item(s) successfully restored<br>';
												}
												
												if (response['failedItemsCount'] >= '1') {
													result += response['failedItemsCount'] + ' item(s) failed<br>';
												}
												
												if (response['skippedItemsCount'] >= '1') {
													result += response['skippedItemsCount'] + ' item(s) skipped (unchanged item)';
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
	if (isset($tid)) {
?>
$('.load-more-items').click(function(e) {
    var offset = $(this).data('offset');
	var node = $('#jstree').jstree('get_selected', true);
	var channelid = node[0].data.channelid;
	var type = $(this).data('type');

    loadItems(channelid, offset, type);
});

function loadConversation(parentid) {
	var rid = '<?php echo $rid; ?>';
	var teamid = '<?php echo $tid; ?>';
	treedata = $('#jstree').jstree('get_selected', true);
	channelid = treedata[0]['data']['channelid'];

    $.post('veeam.php', {'action' : 'getteamsposts', 'teamid' : teamid, 'channelid' : channelid, 'parentid' : parentid, 'rid' : rid}).done(function(data) {
        response = JSON.parse(data);
        $('#table-teams-posts-conversation tbody').empty();
        
		if (response.results.length !== 0) {
			for (var i = 0; i < response.results.length; i++) {
				$('#table-teams-posts-conversation tbody').append('<tr> \
					<td>' + response.results[i].author + '</td> \
					<td>' + response.results[i].subject + '</td> \
					<td>' + moment(response.results[i].createdTime).format('DD/MM/YYYY HH:mm') + '</td> \
					<td>' + moment(response.results[i].lastModifiedTime).format('DD/MM/YYYY HH:mm') + '</td> \
					<td class="text-center"> \
					<div class="btn-group dropdown"> \
					<button class="btn btn-default dropdown-toggle" data-toggle="dropdown">Restore <span class="caret"></span></button> \
					<ul class="dropdown-menu"> \
					<li class="dropdown-header">Export to</li> \
					<li><a class="dropdown-link" href="javascript:void(0);" onclick="downloadHTML(\'' + response.results[i].id + '\', \'single\')"><i class="fa fa-download"></i> HTML file</a></li> \
					<li><a class="dropdown-link" href="javascript:void(0);" onclick="downloadMSG(\'' + response.results[i].id + '\')"><i class="fa fa-download"></i> MSG file</a></li> \
					</ul> \
					</div> \
					</td> \
					</tr>');
			}
		} else {
			$('#table-teams-posts-conversation tbody').append('<tr> \
				<td colspan="5">No additional posts in this conversation.</td> \
				</div> \
				</td> \
				</tr>');
		}
                
        $('#conversationModalCenter').modal('show');
    });
}

function fillTableFiles(response) {
	if (response.results.length !== 0) {
		for (var i = 0; i < response.results.length; i++) {
			var size = filesize(response.results[i].sizeBytes, {round: 2});
			var type = response.results[i].type.toLowerCase();

			$('#table-teams-files tbody').append('<tr> \
			<td class="text-center"><input type="checkbox" name="checkbox-teams" value="' + response.results[i].id + '"></td> \
			<td><i class="far fa-' + type + '"></i> ' + response.results[i].name + '</td> \
			<td>' + size + '</td> \
			<td>' + moment(response.results[i].modificationTime).format('DD/MM/YYYY HH:mm') + ' (by ' + response.results[i].modifiedBy + ')</td> \
			<td>' + response.results[i].version + '</td> \
			<td class="text-center"> \
			<div class="btn-group dropdown"> \
			<button class="btn btn-default dropdown-toggle" data-toggle="dropdown">Restore <span class="caret"></span></button> \
			<ul class="dropdown-menu dropdown-menu-right"> \
			<li class="dropdown-header">Export to</li> \
			<li><a class="dropdown-link" href="javascript:void(0);" onclick="downloadFile(\'' + response.results[i].id + '\', \'' + response.results[i].name + '\')"><i class="fa fa-download"></i> Plain file</a></li> \
			<li><a class="dropdown-link" href="javascript:void(0);" onclick="downloadZIP(\'' + response.results[i].id + '\', \'' + response.results[i].name + '\', \'single\')"><i class="fa fa-download"></i> ZIP file</a></li> \
			<li class="divider"></li> \
			<li class="dropdown-header">Restore to</li> \
			<li><a class="dropdown-link" href="javascript:void(0);" onclick="restoreFilesToOriginal(\'' + response.results[i].id + '\', \'single\')"><i class="fa fa-upload"></i> Original location</a></li> \
			</ul> \
			</div> \
			</td> \
			</tr>');          
		}
	}
}

function fillTablePosts(response) {
	if (response.results.length !== 0) {
		for (var i = 0; i < response.results.length; i++) {
			if (response.results[i]._links.children !== undefined) {
				$('#table-teams-posts tbody').append('<tr> \
					<td class="text-center"><input type="checkbox" name="checkbox-teams" value="' + response.results[i].id + '"></td> \
					<td><a href="javascript:void(0);" onclick="loadConversation(' + response.results[i].id + ');">' + response.results[i].author + '</a></td> \
					<td>' + response.results[i].subject + '</td> \
					<td>' + moment(response.results[i].createdTime).format('DD/MM/YYYY HH:mm') + '</td> \
					<td>' + moment(response.results[i].lastModifiedTime).format('DD/MM/YYYY HH:mm') + '</td> \
					<td class="text-center"> \
					<div class="btn-group dropdown"> \
					<button class="btn btn-default dropdown-toggle" data-toggle="dropdown">Restore <span class="caret"></span></button> \
					<ul class="dropdown-menu dropdown-menu-right"> \
					<li class="dropdown-header">Export to</li> \
					<li><a class="dropdown-link" href="javascript:void(0);" onclick="downloadHTML(\'' + response.results[i].id + '\', \'single\')"><i class="fa fa-download"></i> HTML file</a></li> \
					<li><a class="dropdown-link" href="javascript:void(0);" onclick="downloadMSG(\'' + response.results[i].id + '\')"><i class="fa fa-download"></i> MSG file</a></li> \
					</ul> \
					</div> \
					</td> \
					</tr>');
			}
		}
	}
}

function fillTableTabs(response) {
	if (response.results.length !== 0) {
		for (var i = 0; i < response.results.length; i++) {		
			$('#table-teams-tabs tbody').append('<tr> \
			<td class="text-center"><input type="checkbox" name="checkbox-teams" value="' + response.results[i].id + '"></td> \
			<td><i class="far fa-file"></i> ' + response.results[i].displayName + '</td> \
			<td>' + response.results[i].type + '</td> \
			<td>' + response.results[i].contentUrl + '</td> \
			<td class="text-center"> \
			<div class="btn-group dropdown"> \
			<button class="btn btn-default dropdown-toggle" data-toggle="dropdown">Restore <span class="caret"></span></button> \
			<ul class="dropdown-menu dropdown-menu-right"> \
			<li class="dropdown-header">Restore to</li> \
			<li><a class="dropdown-link" href="javascript:void(0);" onclick="restoreTabsToOriginal(\'' + response.results[i].id + '\', \'single\')"><i class="fa fa-upload"></i> Original location</a></li> \
			</ul> \
			</div> \
			</td> \
			</tr>');          
		}
	}
}

function loadItems(channelid, offset, type, parentid) {
	if (arguments.length === 3) {
		parentid = 'null';
	}
	
	var limit = <?php echo $limit; ?>;
	var rid = '<?php echo $rid; ?>';
	var teamid = '<?php echo $tid; ?>';
	
	if (type === 'files') {	
		$.post('veeam.php', {'action' : 'getteamsfiles', 'teamid' : teamid, 'channelid' : channelid, 'parentid' : parentid, 'rid' : rid, 'offset' : offset, 'type' : type}).done(function(data) {
			response = JSON.parse(data);
		}).then(function() {
			fillTableFiles(response);
		});
	} else if (type === 'posts') {
		$.post('veeam.php', {'action' : 'getteamsposts', 'teamid' : teamid, 'channelid' : channelid, 'parentid' : parentid, 'rid' : rid, 'offset' : offset, 'type' : type}).done(function(data) {
			response = JSON.parse(data);
		}).then(function() {
			fillTablePosts(response);
		});
	} else {
		$.post('veeam.php', {'action' : 'getteamstabs', 'teamid' : teamid, 'channelid' : channelid, 'rid' : rid, 'offset' : offset, 'type' : type}).done(function(data) {
			response = JSON.parse(data);
		}).then(function() {
			fillTableTabs(response);
		});
	}
	
	if (typeof response !== undefined && response.results.length >= limit) {
		$('a.load-more-'+type).removeClass('hide');
		$('a.load-more-'+type).data('offset', offset + limit);
	} else {
		$('a.load-more-'+type).addClass('hide');
		
		if (type === 'files' || type === 'posts') {
			$('#table-teams-'+type+' tbody').append('<tr><td class="text-center" colspan="6">No more items available.</td></tr>');
		} else {
			$('#table-teams-'+type+' tbody').append('<tr><td class="text-center" colspan="6">No more items available.</td></tr>');
		}
	}
}

function loadChannelFiles(channelid, parentid) {
	if (arguments.length === 1) {
		parentid = 'null';
	}
	
	var limit = <?php echo $limit; ?>;
	var rid = '<?php echo $rid; ?>';
	var teamid = '<?php echo $tid; ?>';
	
	disableTree();
	
	$('.tab-pane').removeClass('active in');
	$('#files').addClass('active in');
	$('#table-teams-files tbody').empty();
	$('#loader').removeClass('hide');
	$('a.load-more-link').addClass('hide');
	
    $.post('veeam.php', {'action' : 'getteamsfiles', 'teamid' : teamid, 'channelid' : channelid, 'parentid' : parentid, 'rid' : rid}).done(function(data) {
        response = JSON.parse(data);
	}).then(function(e) {
		if (typeof response !== undefined && response.results.length === 0) {
			$('#table-teams-files tbody').append('<tr><td colspan="6">No files available for this channel.</td></tr>');
			$('#loader').addClass('hide');
			$('a.load-more-files').addClass('hide');
			
			enableTree();
		
			return;
		}
	
		if (typeof response !== undefined && response.results.length !== 0) {
			$('#teams-controls-files').removeClass('hide');
			fillTableFiles(response);
		}
		
		if (typeof response !== undefined && response.results.length >= limit) {
			$('a.load-more-files').removeClass('hide');
			$('a.load-more-files').data('offset', limit);
		} else {
			$('a.load-more-files').addClass('hide');
		}
	
		$('#loader').addClass('hide');
		enableTree();
	}); 
}

function loadChannelPosts(channelid, parentid) {
	if (arguments.length === 1) {
		parentid = 'null';
	}
	
	var limit = <?php echo $limit; ?>;
	var rid = '<?php echo $rid; ?>';
	var teamid = '<?php echo $tid; ?>';
	
	disableTree();
	
	$('.tab-pane').removeClass('active in');
	$('#posts').addClass('active in');
	$('#table-teams-posts tbody').empty();
	$('#loader').removeClass('hide');
	$('a.load-more-link').addClass('hide');
	
    $.post('veeam.php', {'action' : 'getteamsposts', 'teamid' : teamid, 'channelid' : channelid, 'parentid' : parentid, 'rid' : rid}).done(function(data) {
        response = JSON.parse(data);
		var children, parent, treedata, treeid, treechannelid;
		treedata = $('#jstree').jstree(true).get_json('#', {'flat': true});
		source = $('#jstree').jstree('get_selected', true);

		for (var i = 0; i < treedata.length; i++) {
			treeid = treedata[i]['id'];
			treechannelid = treedata[i]['data']['channelid'];
			
			if (treechannelid === channelid) {
				$('#jstree').jstree('deselect_all');
				$('#jstree').jstree('select_node', treeid);
				
				parent = $('#jstree').jstree('get_selected', true);
				children = $('#jstree').jstree('get_children_dom', treeid);
				
				if (children.length === 0 && parent[0].parents.length === 1) {
					$('#jstree').jstree('create_node', treeid, {data: {"channelid" : channelid}, text: 'Posts'});
					$('#jstree').jstree('create_node', treeid, {data: {"channelid" : channelid}, text: 'Files'});
					$('#jstree').jstree('create_node', treeid, {data: {"channelid" : channelid}, text: 'Tabs'});	

					children = $('#jstree').jstree('get_children_dom', treeid);
					
					$('#jstree').jstree('deselect_all');
					$('#jstree').jstree('select_node', children[1]);
				} else {
					$('#jstree').jstree('deselect_all');
					$('#jstree').jstree('select_node', source);
				}
			}
		}
	}).then(function(e) {
		if (typeof response !== undefined && response.results.length === 0) {
			$('#table-teams-posts tbody').append('<tr><td colspan="6">No posts available for this channel.</td></tr>');
			$('#loader').addClass('hide');
			$('a.load-more-posts').addClass('hide');
			
			enableTree();
		
			return;
		}
	
		if (typeof response !== undefined && response.results.length !== 0) {
			$('#teams-controls-posts').removeClass('hide');
			fillTablePosts(response);
		}
		
		if (typeof response !== undefined && response.results.length >= limit) {
			$('a.load-more-posts').removeClass('hide');
			$('a.load-more-posts').data('offset', limit);
		} else {
			$('a.load-more-posts').addClass('hide');
		}
	
		$('#loader').addClass('hide');
		enableTree();
	});	
}

function loadChannelTabs(channelid) {
	var limit = <?php echo $limit; ?>;
	var rid = '<?php echo $rid; ?>';
	var teamid = '<?php echo $tid; ?>';
	
	disableTree();
	
	$('.tab-pane').removeClass('active in');
	$('#tabs').addClass('active in');
	$('#table-teams-tabs tbody').empty();
	$('#loader').removeClass('hide');
	$('a.load-more-link').addClass('hide');
	
    $.post('veeam.php', {'action' : 'getteamstabs', 'teamid' : teamid, 'channelid' : channelid, 'rid' : rid}).done(function(data) {
        response = JSON.parse(data);
	}).then(function(e) {
		if (typeof response !== undefined && response.results.length === 0) {
			$('#table-teams-tabs tbody').append('<tr><td colspan="6">No tabs available for this channel.</td></tr>');
			$('#loader').addClass('hide');
			$('a.load-more-tabs').addClass('hide');
			
			enableTree();
		
			return;
		}
	
		if (typeof response !== undefined && response.results.length !== 0) {
			$('#teams-controls-tabs').removeClass('hide');
			fillTableTabs(response);
		}
		
		if (typeof response !== undefined && response.results.length >= limit) {
			$('a.load-more-tabs').removeClass('hide');
			$('a.load-more-tabs').data('offset', limit);
		} else {
			$('a.load-more-tabs').addClass('hide');
		}
	
		$('#loader').addClass('hide');
		enableTree();
	});	
}

function downloadFile(itemid, itemname) {
    var rid = '<?php echo $rid; ?>';
	var teamid = '<?php echo $tid; ?>';
	var json = '{ "save": { "asZip": "false" } }';
	
	Swal.fire({
		icon: 'info',
		title: 'Export',
		text: 'Export in progress and your download will start soon',
		allowOutsideClick: false,
	});

	$.post('veeam.php', {'action' : 'exportteamsfile', 'teamid' : teamid, 'itemid' : itemid, 'rid' : rid, 'json' : json}).done(function(data) {
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

function downloadHTML(itemid, type) {
    var rid = '<?php echo $rid; ?>';
	var teamid = '<?php echo $tid; ?>';
	
	Swal.fire({
		icon: 'info',
		title: 'Export',
		text: 'Export in progress and your download will start soon',
		allowOutsideClick: false,
	});
	
	if (type === 'multiple') {
		var act = 'exportteamsmultipleposts';
		var ids = '';
		
		if ($("input[name='checkbox-teams']:checked").length === 0) {
			Swal.close();
			Swal.fire({
				icon: 'info',
				title: 'Export',
				text: 'Cannot export items. No items have been selected'
			});
			
			return;
		}

		$("input[name='checkbox-teams']:checked").each(function(e) {
			ids = ids + '{ "Id": "' + this.value + '" }, ';
		});
		
		var json = '{ \
			"export": { \
				"posts": [ \
				  ' + ids + ' \
				] \
			} \
		}';
	} else {
		if (type === 'single') {
			var act = 'exportteamspost';
			var json = '{ "export": null }';
		} else if (type === 'full') {
			var act = 'exportteamsmultipleposts';
			var node = $('#jstree').jstree('get_selected', true);
			var channelid = node[0].data.channelid;
			var json = '{ \
					"export": { \
						"ChannelId": "' + channelid + '", \
					} \
				}';
		}
	}

	$.post('veeam.php', {'action' : act, 'teamid' : teamid, 'itemid' : itemid, 'rid' : rid, 'json' : json}).done(function(data) {
		var response = JSON.parse(data);
		
		if (response['exportFailed'] === undefined) {
			if (response['exportFile'] !== undefined) {
				var file = response['exportFile'];
				const now = new Date();
				var filename = 'teams-posts-' + now.getTime();
				
				$.redirect('download.php', {ext : 'html', file : file, name : filename}, 'POST');
				
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

function downloadMSG(itemid) {
    var rid = '<?php echo $rid; ?>';
	var teamid = '<?php echo $tid; ?>';	
    var json = '{ "save": null }';
	
	Swal.fire({
		icon: 'info',
		title: 'Export',
		text: 'Download will start soon',
		allowOutsideClick: false,
	});
    
	$.post('veeam.php', {'action': 'exportteamspost', 'rid' : rid, 'teamid' : teamid, 'itemid' : itemid, 'json' : json}).done(function(data) {	
		var response = JSON.parse(data);
		
		if (response['exportFailed'] === undefined) {
			if (response['exportFile'] !== undefined) {
				var file = response['exportFile'];
				const now = new Date();
				var filename = 'teams-posts-' + now.getTime();

				$.redirect('download.php', {'ext' : 'msg', 'file' : file, 'name' : filename}, 'POST');
				
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

function downloadZIP(itemid, itemname, type) {
    var rid = '<?php echo $rid; ?>';
	var teamid = '<?php echo $tid; ?>';
	
	Swal.fire({
		icon: 'info',
		title: 'Export',
		text: 'Export in progress and your download will start soon',
		allowOutsideClick: false,
	});
	
	if (type === 'multiple') {
		var act = 'exportteamsmultiplefiles';
		var ids = '';
		var filename = 'teams-files';
		
		if ($("input[name='checkbox-teams']:checked").length === 0) {
			Swal.close();
			Swal.fire({
				icon: 'info',
				title: 'Export',
				text: 'Cannot export items. No items have been selected'
			});
			
			return;
		}

		$("input[name='checkbox-teams']:checked").each(function(e) {
			ids = ids + '{ "Id": "' + this.value + '" }, ';
		});
		
		var json = '{ \
			"save": { \
				"Files": [ \
				  ' + ids + ' \
				] \
			} \
		}';
	} else {
		if (type === 'single') {
			var act = 'exportteamsfile';
			var filename = 'teams-file-' + itemname;
			var json = '{ "save": { "asZip": "true" } }';
		} else if (type === 'full') {
			var act = 'exportteamsmultiplefiles';
			var filename = 'teams-files-' + itemname;
			var node = $('#jstree').jstree('get_selected', true);
			var channelid = node[0].data.channelid;
			var json = '{ \
					"save": { \
						"asZip": "true", \
						"ChannelId": "' + channelid + '", \
					} \
				}';
		}
	}

	$.post('veeam.php', {'action' : act, 'teamid' : teamid, 'itemid' : itemid, 'rid' : rid, 'json' : json}).done(function(data) {
		var response = JSON.parse(data);
		
		if (response['exportFailed'] === undefined) {
			if (response['exportFile'] !== undefined) {
				var file = response['exportFile'];
				
				$.redirect('download.php', {ext : 'zip', file : file, name : itemname}, 'POST');
				
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

function restoreChannelToOriginal(channelid) {
    var rid = '<?php echo $rid; ?>';
	var teamid = '<?php echo $tid; ?>';
	
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

							var json = '{ "restore": \
								{ "userName": "' + user + '", \
								  "userPassword": "' + pass + '", \
								  "RestoreChangedItems": "true", \
								  "RestoreMissingItems": "true", \
								} \
							}';

							$.post('veeam.php', {'action' : 'restoreteamschannel', 'teamid' : teamid, 'channelid' : channelid, 'rid' : rid, 'json' : json}).done(function(data) {
								var response = JSON.parse(data);

								if (response['restoreFailed'] === undefined) {
									var result = 'Total items restored: ' + response['totalItemsCount'] + '<br><hr>';
									
									if (response['restoredItemsCount'] >= '1') {
										result += response['restoredItemsCount'] + ' item(s) successfully restored<br>';
									}
									
									if (response['failedItemsCount'] >= '1') {
										result += response['failedItemsCount'] + ' item(s) failed<br>';
									}
									
									if (response['skippedItemsCount'] >= '1') {
										result += response['skippedItemsCount'] + ' item(s) skipped (unchanged item)';
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
							  return;
							});
							clipboard.on('error', function(e) {
							  setTooltip('Failed');
							  hideTooltip();
							  return;
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
										
										var json = '{ "restore": \
											{ "userCode": "' + usercode + '", \
											  "RestoreChangedItems": "true", \
											  "RestoreMissingItems": "true", \
											} \
										}';

										$.post('veeam.php', {'action' : 'restoreteamschannel', 'teamid' : teamid, 'channelid' : channelid, 'rid' : rid, 'json' : json}).done(function(data) {
											var response = JSON.parse(data);
											
											if (response['restoreFailed'] === undefined) {
												var result = 'Total items restored: ' + response['totalItemsCount'] + '<br><hr>';
													
												if (response['restoredItemsCount'] >= '1') {
													result += response['restoredItemsCount'] + ' item(s) successfully restored<br>';
												}
												
												if (response['failedItemsCount'] >= '1') {
													result += response['failedItemsCount'] + ' item(s) failed<br>';
												}
												
												if (response['skippedItemsCount'] >= '1') {
													result += response['skippedItemsCount'] + ' item(s) skipped (unchanged item)';
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

function restoreFilesToOriginal(itemid, type) {
	var itemid = itemid;
	var type = type;
    var rid = '<?php echo $rid; ?>';
	var teamid = '<?php echo $tid; ?>';
	
	if (type === 'multiple' && $("input[name='checkbox-teams']:checked").length === 0) {
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
							'<option value="merge">Merge file</option>' +
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

							if (type === 'multiple') {
								var act = 'restoreteamsmultiplefiles';
								var ids = '';
								
								$("input[name='checkbox-teams']:checked").each(function(e) {
									ids = ids + '{ "Id": "' + this.value + '" }, ';
								});
								
								var json = '{ "restore": \
									{ "UserName": "' + user + '", \
									  "UserPassword": "' + pass + '", \
									  "RestoreChangedItems": "true", \
									  "RestoreMissingItems": "true", \
									  "FileVersion": "Last", \
									  "FileLastVersionAction": "' + restoreaction + '", \
									  "Files": [ \
										' + ids + ' \
									  ] \
									} \
								}';
							} else {
								if (type === 'single') {
									var act = 'restoreteamsfile';
									var json = '{ "restore": \
										{ "userName": "' + user + '", \
										  "userPassword": "' + pass + '", \
										  "RestoreChangedItems": "true", \
										  "RestoreMissingItems": "true", \
										  "FileVersion": "Last", \
										  "FileLastVersionAction": "' + restoreaction + '", \
										} \
									}';
								} else if (type === 'full') {
									var act = 'restoreteamsmultiplefiles';
									var node = $('#jstree').jstree('get_selected', true);
									var channelid = node[0].data.channelid;
									var json = '{ "restore": \
										{ "userName": "' + user + '", \
										  "userPassword": "' + pass + '", \
										  "ChannelId": "' + channelid + '", \
										  "RestoreChangedItems": "true", \
										  "RestoreMissingItems": "true", \
										  "FileVersion": "Last", \
										  "FileLastVersionAction": "' + restoreaction + '", \
										} \
									}';
								}
							}

							$.post('veeam.php', {'action' : act, 'teamid' : teamid, 'itemid' : itemid, 'rid' : rid, 'json' : json}).done(function(data) {
								var response = JSON.parse(data);

								if (response['restoreFailed'] === undefined) {
									var result = 'Total items restored: ' + response['totalItemsCount'] + '<br><hr>';
									
									if (response['restoredItemsCount'] >= '1') {
										result += response['restoredItemsCount'] + ' item(s) successfully restored<br>';
									}
									
									if (response['failedItemsCount'] >= '1') {
										result += response['failedItemsCount'] + ' item(s) failed<br>';
									}
									
									if (response['skippedItemsCount'] >= '1') {
										result += response['skippedItemsCount'] + ' item(s) skipped (unchanged item)';
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
							'<div class="alert alert-warning" role="alert">This will restore the last version of the item.</div>' +
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
							  return;
							});
							clipboard.on('error', function(e) {
							  setTooltip('Failed');
							  hideTooltip();
							  return;
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
										
										if (type === 'multiple') {
											var act = 'restoreteamsmultiplefiles';
											var ids = '';
											
											$("input[name='checkbox-teams']:checked").each(function(e) {
												ids = ids + '{ "id": "' + this.value + '" }, ';
											});
											
											var json = '{ "restore": \
												{ "userCode": "' + usercode + '", \
												  "RestoreChangedItems": "true", \
												  "RestoreMissingItems": "true", \
												  "FileVersion": "Last", \
												  "FileLastVersionAction": "' + restoreaction + '", \
												  "Files": [ \
													' + ids + ' \
												  ] \
												} \
											}';
										} else {
											if (type === 'single') {
												var act = 'restoreteamsfile';
												var json = '{ "restore": \
													{ "userCode": "' + usercode + '", \
													  "RestoreChangedItems": "true", \
													  "RestoreMissingItems": "true", \
													  "FileVersion": "Last", \
													  "FileLastVersionAction": "' + restoreaction + '", \
													} \
												}';
											} else if (type === 'full') {
												var act = 'restoreteamsmultiplefiles';
												var node = $('#jstree').jstree('get_selected', true);
												var channelid = node[0].data.channelid;
												var json = '{ "restore": \
													{ "userCode": "' + usercode + '", \
													  "ChannelId": "' + channelid + '", \
													  "RestoreChangedItems": "true", \
													  "RestoreMissingItems": "true", \
													  "FileVersion": "Last", \
													  "FileLastVersionAction": "' + restoreaction + '", \
													} \
												}';
											}
										}

										$.post('veeam.php', {'action' : act, 'itemid' : itemid, 'teamid' : teamid, 'rid' : rid, 'json' : json}).done(function(data) {
											var response = JSON.parse(data);
											
											if (response['restoreFailed'] === undefined) {
												var result = 'Total items restored: ' + response['totalItemsCount'] + '<br><hr>';
													
												if (response['restoredItemsCount'] >= '1') {
													result += response['restoredItemsCount'] + ' item(s) successfully restored<br>';
												}
												
												if (response['failedItemsCount'] >= '1') {
													result += response['failedItemsCount'] + ' item(s) failed<br>';
												}
												
												if (response['skippedItemsCount'] >= '1') {
													result += response['skippedItemsCount'] + ' item(s) skipped (unchanged item)';
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

function restorePostsToOriginal() {
    var rid = '<?php echo $rid; ?>';
	var teamid = '<?php echo $tid; ?>';
		
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
							var restoreaction = $('#restore-original-action').val();
							
							Swal.fire({
								icon: 'info',
								title: 'Restore',
								text: 'Restore in progress...',
								allowOutsideClick: false,
							});

							var act = 'restoreteamsmultipleposts';
							var node = $('#jstree').jstree('get_selected', true);
							var channelid = node[0].data.channelid;
							var json = '{ "restore": \
								{ "userName": "' + user + '", \
								  "userPassword": "' + pass + '", \
								  "ChannelId": "' + channelid + '", \
								} \
							}';
							
							$.post('veeam.php', {'action' : act, 'teamid' : teamid, 'rid' : rid, 'json' : json}).done(function(data) {
								var response = JSON.parse(data);

								if (response['restoreFailed'] === undefined) {
									var result = 'Total items restored: ' + response['totalItemsCount'] + '<br><hr>';
									
									if (response['restoredItemsCount'] >= '1') {
										result += response['restoredItemsCount'] + ' item(s) successfully restored<br>';
									}
									
									if (response['failedItemsCount'] >= '1') {
										result += response['failedItemsCount'] + ' item(s) failed<br>';
									}
									
									if (response['skippedItemsCount'] >= '1') {
										result += response['skippedItemsCount'] + ' item(s) skipped (unchanged item)';
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
							var restoreaction = $('#restore-original-action').val();
							var json = '{ "targetApplicationId" : "' + applicationid + '", }';
							
							clipboard.on('success', function(e) {
							  setTooltip('Copied');
							  hideTooltip();
							  return;
							});
							clipboard.on('error', function(e) {
							  setTooltip('Failed');
							  hideTooltip();
							  return;
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
										
										var act = 'restoreteamsmultipleposts';
										var node = $('#jstree').jstree('get_selected', true);
										var channelid = node[0].data.channelid;
										var json = '{ "restore": \
											{ "userCode": "' + usercode + '", \
											  "ChannelId": "' + channelid + '", \
											} \
										}';

										$.post('veeam.php', {'action' : act, 'itemid' : itemid, 'teamid' : teamid, 'rid' : rid, 'json' : json}).done(function(data) {
											var response = JSON.parse(data);
											
											if (response['restoreFailed'] === undefined) {
												var result = 'Total items restored: ' + response['totalItemsCount'] + '<br><hr>';
													
												if (response['restoredItemsCount'] >= '1') {
													result += response['restoredItemsCount'] + ' item(s) successfully restored<br>';
												}
												
												if (response['failedItemsCount'] >= '1') {
													result += response['failedItemsCount'] + ' item(s) failed<br>';
												}
												
												if (response['skippedItemsCount'] >= '1') {
													result += response['skippedItemsCount'] + ' item(s) skipped (unchanged item)';
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

function restoreTabsToOriginal(tabid, type) {
    var rid = '<?php echo $rid; ?>';
	var teamid = '<?php echo $tid; ?>';
	var node = $('#jstree').jstree('get_selected', true);
	var channelid = node[0].data.channelid;
	
	if (type === 'multiple' && $("input[name='checkbox-teams']:checked").length === 0) {
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

							if (type == 'single') {
								var act = 'restoreteamstab';
								
								var json = '{ "restore": \
									{ "userName": "' + user + '", \
									  "userPassword": "' + pass + '", \
									  "RestoreChangedTabs": "true", \
									  "RestoreMissingTabs": "true", \
									} \
								}';
							} else {
								var act = 'restoreteamsmultipletabs';
								var ids = '';
								
								if (type == 'multiple') {
									$("input[name='checkbox-teams']:checked").each(function(e) {
										ids = ids + '{ "Id": "' + this.value + '" }, ';
									});
								} else {
									$("input[name='checkbox-teams']").each(function(e) {
										ids = ids + '{ "Id": "' + this.value + '" }, ';
									});
								}
								
								var json = '{ "restore": \
									{ "UserName": "' + user + '", \
									  "UserPassword": "' + pass + '", \
									  "RestoreChangedTabs": "true", \
									  "RestoreMissingTabs": "true", \
									  "Tabs": [ \
										' + ids + ' \
									  ] \
									} \
								}';
							}

							$.post('veeam.php', {'action' : act, 'teamid' : teamid, 'channelid' : channelid, 'tabid' : tabid, 'rid' : rid, 'json' : json}).done(function(data) {
								var response = JSON.parse(data);

								if (response['restoreFailed'] === undefined) {
									var result = 'Total items restored: ' + response['totalItemsCount'] + '<br><hr>';
									
									if (response['restoredItemsCount'] >= '1') {
										result += response['restoredItemsCount'] + ' item(s) successfully restored<br>';
									}
									
									if (response['failedItemsCount'] >= '1') {
										result += response['failedItemsCount'] + ' item(s) failed<br>';
									}
									
									if (response['skippedItemsCount'] >= '1') {
										result += response['skippedItemsCount'] + ' item(s) skipped (unchanged item)';
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
							  return;
							});
							clipboard.on('error', function(e) {
							  setTooltip('Failed');
							  hideTooltip();
							  return;
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
										
										if (type === 'multiple') {
											var ids = '';
											
											$("input[name='checkbox-teams']:checked").each(function(e) {
												ids = ids + '{ "id": "' + this.value + '" }, ';
											});
											
											var json = '{ "restore": \
												{ "userCode": "' + usercode + '", \
												  "RestoreChangedTabs": "true", \
												  "RestoreMissingTabs": "true", \
												  "Tabs": [ \
													' + ids + ' \
												  ] \
												} \
											}';
										} else {
											var json = '{ "restore": \
												{ "userCode": "' + usercode + '", \
												  "RestoreChangedTabs": "true", \
												  "RestoreMissingTabs": "true", \
												} \
											}';
										}

										$.post('veeam.php', {'action' : act, 'teamid' : teamid, 'channelid' : channelid, 'tabid' : tabid, 'rid' : rid, 'json' : json}).done(function(data) {
											var response = JSON.parse(data);
											
											if (response['restoreFailed'] === undefined) {
												var result = 'Total items restored: ' + response['totalItemsCount'] + '<br><hr>';
													
												if (response['restoredItemsCount'] >= '1') {
													result += response['restoredItemsCount'] + ' item(s) successfully restored<br>';
												}
												
												if (response['failedItemsCount'] >= '1') {
													result += response['failedItemsCount'] + ' item(s) failed<br>';
												}
												
												if (response['skippedItemsCount'] >= '1') {
													result += response['skippedItemsCount'] + ' item(s) skipped (unchanged item)';
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