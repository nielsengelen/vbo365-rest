<?php
require_once('../config.php');
require_once('../veeam.class.php');

session_start();
error_reporting(E_ALL || E_STRICT);

$veeam = new VBO($host, $port, $version);

if (isset($_SESSION['token'])) {
	$veeam->setToken($_SESSION['token']);	
    $user = $_SESSION['user'];
	$org = $veeam->getOrganizations();
?>
<div class="main-container">
    <h1>Organizations</h1>
    <?php
    if (count($org) !== 0) {
    ?>
    <table class="table table-hover table-bordered table-padding table-striped" id="table-organizations">
        <thead>
            <tr>
                <th>Name</th>
                <th>Region</th>
                <th>First Backup</th>
                <th>Last Backup</th>
				<th class="text-center">Licensed Users</th>
            </tr>
        </thead>
        <tbody> 
        <?php
        for ($i = 0; $i < count($org); $i++) {
        ?>
            <tr>
                <td><?php echo $org[$i]['name']; ?></td>
                <td><?php echo $org[$i]['region']; ?></td>
                <td><?php echo (isset($org[$i]['firstBackuptime']) ? date('d/m/Y H:i T', strtotime($org[$i]['firstBackuptime'])) : 'N/A'); ?></td>
                <td><?php echo (isset($org[$i]['lastBackuptime']) ? date('d/m/Y H:i T', strtotime($org[$i]['lastBackuptime'])) : 'N/A'); ?></td>
				<td class="pointer text-center" data-toggle="collapse" data-target="#licensedUsers<?php echo $i; ?>"><a href="#" onClick="return false;">View</a></td>
            </tr>
			<tr>
                <td colspan="6" class="zeroPadding">
					<div id="licensedUsers<?php echo $i; ?>" class="accordian-body collapse">
						<table class="table table-bordered table-small table-striped">
							<thead>
								<tr>
									<th>Name</th>
									<th>License State</th>
									<th>Last Backup</th>
									<th>Backed Up</th>
								</tr>
							</thead>
							<tbody>
								<?php
									$license = $veeam->getLicenseInfo($org[$i]['id']);
									
									if ($license['licensedUsers'] !== 0) {
										$users = $veeam->getLicensedUsers($org[$i]['id']);
									
										for ($j = 0; $j < count($users['results']); $j++) {
											echo '<tr>';
											echo '<td>' . $users['results'][$j]['name'] . '</td>';
											echo '<td>' . $users['results'][$j]['licenseState'] . '</td>';
											echo '<td>' . date('d/m/Y H:i T', strtotime($users['results'][$j]['lastBackupDate'])) . '</td>';
											echo '<td>'; 
											if ($users['results'][$j]['isBackedUp'] == 'true') { echo '<span class="label label-success">Yes</span>'; } else { echo '<span class="label label-danger">No</span>'; }
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
		$veeam->logout();
		?>
		<script>
		Swal.fire({
			icon: 'info',
			title: 'Session expired',
			text: 'Your session has expired and requires you to log in again',
		}).then(function(e) {
			window.location.href = '/';
		});
		</script>
		<?php
	}
}
?>