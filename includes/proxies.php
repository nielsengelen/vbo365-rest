<?php
require_once('../config.php');
require_once('../veeam.class.php');

session_start();
error_reporting(E_ALL || E_STRICT);

$veeam = new VBO($host, $port, $version);

if (isset($_SESSION['token'])) {
	$veeam->setToken($_SESSION['token']);
	$proxies = $veeam->getProxies();
?>
<div class="main-container">
    <h1>Proxies</h1>
    <?php
    if (count($proxies) !== 0) {
    ?>
    <table class="table table-hover table-bordered table-padding table-striped" id="table-proxies">
        <thead>
            <tr>
                <th>Name</th>
                <th>Port</th>
                <th>Status</th>
				<th>Description</th>
            </tr>
        </thead>
            <tbody> 
            <?php
            for ($i = 0; $i < count($proxies); $i++) {
            ?>
                <tr>
                    <td><?php echo $proxies[$i]['hostName']; ?></td>
                    <td><?php echo $proxies[$i]['port']; ?></td>
                    <td>
                    <?php
                    if (strtolower($proxies[$i]['status']) === 'online') { 
                        echo '<span class="label label-success">'.$proxies[$i]['status'].'</span>'; 
                    } else { 
                        echo '<span class="label label-danger">'.$proxies[$i]['status'].'</span>'; 
                    }
                    ?>
                    </td>
					<td><?php echo $proxies[$i]['description']; ?></td>
                </tr>
            <?php
            }
            ?>
        </tbody>
    </table>
    <?php
    } else {
        echo 'No proxies available.';
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