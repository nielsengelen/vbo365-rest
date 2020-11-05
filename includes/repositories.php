<?php
require_once('../config.php');
require_once('../veeam.class.php');

session_start();

if (isset($_SESSION['token'])) {
	$veeam = new VBO($host, $port, $version);
	$veeam->setToken($_SESSION['token']);
    $user = $_SESSION['user'];
	$repos = $veeam->getBackupRepositories();
	
	try {
		$objectrepos = $veeam->getObjectStorageRepositories();
	} catch (Exception $e) {
		$e->getMessage();
	}
?>
<div class="main-container">
    <h1>Backup Repositories</h1>
    <?php
    if (count($repos) != '0') {
    ?>
    <table class="table table-hover table-bordered table-padding table-striped" id="table-proxies">
        <thead>
            <tr>
                <th>Name</th>
                <th>Host</th>
				<th>Retention Type</th>
                <th>Capacity</th>
                <th>Description</th>
            </tr>
        </thead>
        <tbody> 
        <?php
        for ($i = 0; $i < count($repos); $i++) {
            $proxy = $veeam->getProxy($repos[$i]['proxyId']);
        ?>
            <tr>
                <td><?php echo $repos[$i]['name']; ?></td>
                <td><?php echo $proxy['hostName']; ?></td>
				<td>
				<?php 
				if (strcmp($repos[$i]['retentionType'], 'ItemLevel') === 0) {
					echo 'Item-level';
				} else {
					echo 'Snapshot-based';
				}
				?>
				</td>
                <td id="size-<?php echo $repos[$i]['id']; ?>"></td>
                <td><?php echo $repos[$i]['description']; ?></td>
            </tr>
        <?php
        }
        ?>
        </tbody>
    </table>
    <?php
		for ($i = 0; $i < count($repos); $i++) { /* v3 added Bytes to the parameter */
		?>
		<script>
		var capacity = filesize(<?php echo $repos[$i]['capacityBytes']; ?>, {round: 1});
		var freespace = filesize(<?php echo $repos[$i]['freeSpaceBytes']; ?>, {round: 1});
		
		document.getElementById('size-<?php echo $repos[$i]['id']; ?>').innerHTML = capacity + ' (' + freespace + ' available)';
		</script>
		<?php
		}
    } else {
        echo '<p>No backup repositories available.</p>';
    }
	
	if (isset($objectrepos)) {
    ?>
		<h1>Object Storage Repositories</h1>
		<?php
		if (count($objectrepos) != '0') {
		?>
		<table class="table table-hover table-bordered table-padding table-striped" id="table-proxies">
			<thead>
				<tr>
					<th>Name</th>
					<th>Type</th>
					<th>Capacity</th>
					<th>Configured Limit</th>
					<th>Description</th>
				</tr>
			</thead>
			<tbody> 
			<?php
			for ($i = 0; $i < count($objectrepos); $i++) {
			?>
				<tr>
					<td><?php echo $objectrepos[$i]['name']; ?></td>
					<td><?php echo $objectrepos[$i]['type']; ?></td>
					<td id="size-object-<?php echo $objectrepos[$i]['id']; ?>"></td>
					<td>
					<?php 
					if ($objectrepos[$i]['sizeLimitEnabled']) {
						echo $objectrepos[$i]['sizeLimitGB'] . ' GB';
					} else {
						echo 'Not set';
					}
					?>
					</td>
					<td><?php echo $objectrepos[$i]['description']; ?></td>
				</tr>
			<?php
			}
			?>
			</tbody>
		</table>
		<?php
			for ($i = 0; $i < count($objectrepos); $i++) {
				if (isset($objectrepos[$i]['usedSpaceBytes']) && !isset($objectrepos[$i]['freeSpaceBytes'])) {
					?>
					<script>
					var usedspaceobject = filesize(<?php echo $objectrepos[$i]['usedSpaceBytes']; ?>, {round: 1});
					
					document.getElementById('size-object-<?php echo $objectrepos[$i]['id']; ?>').innerHTML = usedspaceobject + ' used';
					</script>
					<?php
				} else if (isset($objectrepos[$i]['usedSpaceBytes']) && isset($objectrepos[$i]['freeSpaceBytes'])) {
					?>
					<script>
					var freespaceobject = filesize(<?php echo $objectrepos[$i]['freeSpaceBytes']; ?>, {round: 1});
					var usedspaceobject = filesize(<?php echo $objectrepos[$i]['usedSpaceBytes']; ?>, {round: 1});
					
					document.getElementById('size-object-<?php echo $objectrepos[$i]['id']; ?>').innerHTML = usedspaceobject + ' used (' + freespaceobject + ' available)';
					</script>
					<?php
				} else {
					?>
					<script>
					document.getElementById('size-object-<?php echo $objectrepos[$i]['id']; ?>').innerHTML = 'N/A';
					</script>
					<?php
				}
			}
		} else {
			echo '<p>No object storage repositories available.</p>';
		}
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
			text: 'Your session has expired and requires you to login again.',
		}).then(function(e) {
			window.location.href = '/index.php';
		});
		</script>
		<?php
	}
}
?>