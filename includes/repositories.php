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
	$repos = $veeam->getBackupRepositories();
?>
<div class="main-container">
    <h1>Repositories</h1>
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
		if ($version != 'v2') {
			for ($i = 0; $i < count($repos); $i++) { /* v3 added Bytes to the parameter */
			?>
			<script>
			var capacity = filesize(<?php echo $repos[$i]['capacityBytes']; ?>, {round: 2});
			var freespace = filesize(<?php echo $repos[$i]['freeSpaceBytes']; ?>, {round: 2});
			
			document.getElementById("size-<?php echo $repos[$i]['id']; ?>").innerHTML = capacity + " (" + freespace + " available)";
			</script>
			<?php
			}
		} else {
			for ($i = 0; $i < count($repos); $i++) {
			?>
			<script>
			var capacity = filesize(<?php echo $repos[$i]['capacity']; ?>, {round: 2});
			var freespace = filesize(<?php echo $repos[$i]['freeSpace']; ?>, {round: 2});
			
			document.getElementById("size-<?php echo $repos[$i]['id']; ?>").innerHTML = capacity + " (" + freespace + " available)";
			</script>
			<?php
			}
		}
    } else {
        echo '<p>No backup repositories available.</p>';
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