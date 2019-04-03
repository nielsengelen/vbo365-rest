<?php
require_once('../config.php');
require_once('../veeam.class.php');

session_start();

$veeam = new VBO($host, $port, $version);

if (isset($_SESSION['token'])) {
    $veeam->setToken($_SESSION['token']);
} 

if (isset($_SESSION['refreshtoken'])) {
    $veeam->refreshToken($_SESSION['refreshtoken']);
}

if (isset($_SESSION['token'])) {
    $user = $_SESSION['user'];
	$org = $veeam->getOrganizations();
?>
<div class="main-container">
    <h1>Organizations</h1>
    <?php
    if (count($org) != '0') {
    ?>
    <table class="table table-hover table-bordered table-padding table-striped" id="table-organizations">
        <thead>
            <tr>
                <th>Name</th>
                <th>Region</th>
                <th>First backup</th>
                <th>Last backup</th>
				<?php
				if ($version != 'v2') {
				?>
				<th>Backup size</th>
				<th class="text-center">Licensed users</th>
				<?php
				}
				?>
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
				<?php
				if ($version != 'v2') {
				?>
				<td id="size-<?php echo $org[$i]['id']; ?>"></td>
				<td class="pointer text-center" data-toggle="collapse" data-target="#licensedUsers<?php echo $i; ?>"><a href="#" onClick="return false;">View</a></td>
				<?php
				}
				?>
            </tr>
			<?php
			if ($version != 'v2') {
			?>
			<tr><!-- Start of table for licensed users -->
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
								?>
							</tbody>
						</table>
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
		if ($version != 'v2') {
			for ($i = 0; $i < count($org); $i++) {
				$repo = $veeam->getOrganizationRepository($org[$i]['id']);
			?>
				<script>
				var usedspace = filesize(<?php echo $repo[0]['usedSpaceBytes']; ?>, {round: 2});
				
				document.getElementById("size-<?php echo $org[$i]['id']; ?>").innerHTML = usedspace;
				</script>
			<?php
			}
		}
    } else {
        echo '<p>No organizations have been added.</p>';
    }
    ?>
</div>
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