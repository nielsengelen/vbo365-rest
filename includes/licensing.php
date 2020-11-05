<?php
require_once('../config.php');
require_once('../veeam.class.php');

session_start();

if (isset($_SESSION['token'])) {
	$veeam = new VBO($host, $port, $version);
	$veeam->setToken($_SESSION['token']);	
	$org = $veeam->getOrganizations();
?>
<div class="main-container">
    <h1>Licensing</h1>
    <?php
    if (count($org) != '0') {
    ?>
    <table class="table table-bordered table-padding table-striped" id="table-organizations">
        <thead>
            <tr>
                <th>Organization</th>
                <th>Licenses used</th>
                <th>Licenses exceeded</th>
				<th class="text-center">Licensed users</th>
            </tr>
        </thead>
        <tbody> 
        <?php
        for ($i = 0; $i < count($org); $i++) {
            $license = $veeam->getLicenseInfo($org[$i]['id']);
			$users = $veeam->getLicensedUsers($org[$i]['id']);
			$repo = $veeam->getOrganizationRepository($org[$i]['id']);
			$usersarray = array();

			for ($x = 0; $x < count($users['results']); $x++) {
				array_push($usersarray, array(
					'email' => $users['results'][$x]['name'],
					'isBackedUp' => $users['results'][$x]['isBackedUp'],
					'lastBackupDate' => $users['results'][$x]['lastBackupDate'],
					'licenseState' => $users['results'][$x]['licenseState']
				));
			}

			if (count($repo) != '0') {
				$repousersarray = array();
				
				for ($j = 0; $j < count($repo); $j++) {
					$id = explode('/', $repo[$j]['_links']['backupRepository']['href']);
					$repoid = end($id);

					for ($k = 0; $k < count($users['results']); $k++) {
						$combinedid = $users['results'][$k]['backedUpOrganizationId'] . $users['results'][$k]['id'];
						$userdata = $veeam->getUserData($repoid, $combinedid);

						if (!is_null($userdata)) {
							if (!empty($userdata['email'])) {
								$email = $userdata['email'];
							} else {
								$email = 'N/A';
							}
							
							array_push($repousersarray, array(
									'id' => $userdata['id'],
									'email' => $email,
									'name' => $userdata['displayName'],
									'isMailboxBackedUp' => $userdata['isMailboxBackedUp'],
									'isOneDriveBackedUp' => $userdata['isOneDriveBackedUp'],
									'isArchiveBackedUp' => $userdata['isArchiveBackedUp'],
									'isPersonalSiteBackedUp' => $userdata['isPersonalSiteBackedUp']
							));
						}
					}
				}
				
				$usersort = array_values(array_column($repousersarray , null, 'id'));			
			}
			?>
            <tr>
                <td><?php echo $org[$i]['name']; ?></td>
                <td><?php echo $license['licensedUsers']; ?></td>
                <td><?php echo $license['newUsers']; ?></td>
				<td class="pointer text-center" data-toggle="collapse" data-target="#licensedUsers<?php echo $i; ?>"><a href="#" onClick="return false;">View</a></td>
			</tr>
			<tr><!-- Start of table for licensed users -->
				<td colspan="4" class="zeroPadding">
					<div id="licensedUsers<?php echo $i; ?>" class="accordian-body collapse">
						<table class="table table-bordered table-small table-striped">
							<thead>
								<tr>
									<th>Name</th>
									<th>Licensed</th>
									<th>Last Backup</th>
									<th>Objects in backup</th>
								</tr>
							</thead>
							<tbody>
								<?php
								if ($license['licensedUsers'] != '0') {
									for ($y = 0; $y < count($usersort); $y++) {
										$licinfo = array_search($usersort[$y]['email'], array_column($usersarray, 'email')); /* Get the last backup date for this specific account */
										echo '<tr>';
										echo '<td>' . $usersort[$y]['name'] . ' (' . $usersort[$y]['email'] . ')</td>';
										echo '<td>'; 
										if (strtolower($usersarray[$licinfo]['licenseState']) == 'licensed') { echo '<span class="label label-success">Yes</span>'; } else { echo '<span class="label label-danger">No</span>'; }
										echo '</td>';
										echo '<td>' . date('d/m/Y H:i T', strtotime($usersarray[$licinfo]['lastBackupDate'])) . '</td>';
										echo '<td>';
										if ($usersort[$y]['isMailboxBackedUp']) {
											echo '<i class="far fa-envelope fa-2x" style="color:green" title="Mailbox"></i> ';
										} else {
											echo '<i class="far fa-envelope fa-2x" style="color:red" title="Mailbox"></i> ';
										}
										if ($usersort[$y]['isArchiveBackedUp']) {
											echo '<i class="fa fa-archive fa-2x" style="color:green" title="Archive"></i> ';
										} else {
											echo '<i class="fa fa-archive fa-2x" style="color:red" title="Archive"></i> ';
										}
										if ($usersort[$y]['isOneDriveBackedUp']) {
											echo '<i class="fa fa-cloud fa-2x" style="color:green" title="OneDrive for Business"></i> ';
										} else {
											echo '<i class="fa fa-cloud fa-2x" style="color:red" title="OneDrive for Business"></i> ';
										}
										if ($usersort[$y]['isPersonalSiteBackedUp']) {
											echo '<i class="fa fa-share-alt fa-2x" style="color:green" title="SharePoint site"></i> ';
										} else {
											echo '<i class="fa fa-share-alt fa-2x" style="color:red" title="SharePoint site"></i> ';
										}
										echo '</td>';
										echo '</tr>';
									}
								} else {
									echo '<tr><td colspan="4">No licensed users.</td></tr>';
								}
								?>
							</tbody>
						</table>
					</div>
				</td>
			</tr>
			<?php
		}
		?>
        </tbody>
    </table>
    <?php
    } else {
        echo '<p>No organizations have been added.</p>';
    }
    ?>
</div>
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
			text: 'Your session has expired and requires you to login again',
		}).then(function(e) {
			window.location.href = '/index.php';
		});
		</script>
		<?php
	}
}
?>