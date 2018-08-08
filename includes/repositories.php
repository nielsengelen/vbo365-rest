<?php
require_once('../config.php');
require_once('../veeam.class.php');

session_start();

$veeam = new VBO($host, $port);
$veeam->setToken($_SESSION['token']);

$repos = $veeam->getBackupRepositories();
?>
<div class="main-container">
    <h1>Repositories overview</h1>
    <?php
    if (count($repos) != '0') {
    ?>
    <table class="table table-bordered table-padding table-striped" id="table-proxies">
        <thead>
            <tr>
                <th>Name</th>
                <th>Host</th>
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
                <td id="size-<?php echo $repos[$i]['id']; ?>"></td>
                <td><?php echo $repos[$i]['description']; ?></td>
            </tr>
        <?php
        }
        ?>
        </tbody>
    </table>
    <?php
    for ($i = 0; $i < count($repos); $i++) {
    ?>
        <script>
        var capacity = filesize(<?php echo $repos[$i]['capacity']; ?>, {round: 2});
        var freespace = filesize(<?php echo $repos[$i]['freeSpace']; ?>, {round: 2});
        
        document.getElementById("size-<?php echo $repos[$i]['id']; ?>").innerHTML = capacity + " (" + freespace + " available)";
        </script>
    <?php
        }
    } else {
        echo 'No backup repositories available.';
    }
    ?>
</div>