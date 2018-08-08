<?php
require_once('../config.php');
require_once('../veeam.class.php');

session_start();

$veeam = new VBO($host, $port);
$veeam->setToken($_SESSION['token']);

$org = $veeam->getOrganizations();
?>
<div class="main-container">
    <h1>Licensing overview</h1>
    <?php
    if (count($org) != '0') {
    ?>
    <table class="table table-bordered table-padding table-striped" id="table-organizations">
        <thead>
            <tr>
                <th>Organization</th>
                <th>Licenses used</th>
                <th>Licenses exceeded</th>
            </tr>
        </thead>
        <tbody> 
        <?php
        for ($i = 0; $i < count($org); $i++) {
            $license = $veeam->getLicenseInfo($org[$i]['id']);
        ?>
            <tr>
                <td><?php echo $org[$i]['name']; ?></td>
                <td><?php echo $license['licensedUsers']; ?></td>
                <td><?php echo $license['newUsers']; ?></td>
            </tr>
        <?php
        }
        ?>
        </tbody>
    </table>
    <?php
    } else {
        echo 'No organizations have been added.';
    }
    ?>
</div>