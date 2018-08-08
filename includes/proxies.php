<?php
require_once('../config.php');
require_once('../veeam.class.php');

session_start();

$veeam = new VBO($host, $port);
$veeam->setToken($_SESSION['token']);

$proxies = $veeam->getProxies();
?>
<div class="main-container">
    <h1>Proxies overview</h1>
    <?php
    if (count($proxies) != '0') {
    ?>
    <table class="table table-bordered table-padding table-striped" id="table-proxies">
        <thead>
            <tr>
                <th>Name</th>
                <th>Port</th>
                <th>Description</th>
                <th>Status</th>
            </tr>
        </thead>
            <tbody> 
            <?php
            for ($i = 0; $i < count($proxies); $i++) {
            ?>
                <tr>
                    <td><?php echo $proxies[$i]['hostName']; ?></td>
                    <td><?php echo $proxies[$i]['port']; ?></td>
                    <td><?php echo $proxies[$i]['description']; ?></td>
                    <td>
                    <?php
                    if (strtolower($proxies[$i]['status']) == 'online') { 
                        echo '<span class="label label-success">'.$proxies[$i]['status'].'</span>'; 
                    } else { 
                        echo '<span class="label label-danger">'.$proxies[$i]['status'].'</span>'; 
                    }
                    ?>
                    </td>
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