<?php
require_once('../config.php');
require_once('../veeam.class.php');

session_start();

$veeam = new VBO($host, $port);
$veeam->setToken($_SESSION['token']);

$jobs = $veeam->getJobs();
$org = $veeam->getOrganizations();
$proxies = $veeam->getProxies();
?>
<div class="main-container">
    <h1>Jobs overview</h1>
    <div class="infobox text-center" id="infobox"></div>
    <?php
    if (count($jobs) != '0') {
    ?>
    <table class="table table-bordered table-padding table-striped" id="table-jobs">
        <thead>
            <tr>
                <th>Job Name</th>
                <th>Status</th>
                <th>Next Run</th>
                <th>Schedule</th>
                <th>Restore Points</th>
                <th class="text-center">Options</th>
            </tr>
        </thead>
        <tbody>
            <?php
            for ($i = 0; $i < count($jobs); $i++) {
                echo '<tr>';
                echo '<td>';
                echo $jobs[$i]['name'];
                
                $id = (explode('/', $jobs[$i]['_links']['organization']['href']));
                
                // Get the organization and add it to the job name
                for ($j = 0; $j < count($org); $j++) {
                    if ($org[$j]['id'] === end($id)) {
                        echo ' <em><strong>(' . $org[$j]['name'] . ')</strong></em>';
                    }
                }
                
                echo '</td>';

                echo '<td>' . (isset($jobs[$i]['lastRun']) ? $jobs[$i]['lastStatus'] . ' (' .  date('d/m/Y H:i T', strtotime($jobs[$i]['lastRun'])) . ')' : $jobs[$i]['lastStatus']) . '</td>';
                echo '<td>' . (isset($jobs[$i]['nextRun']) ? date('d/m/Y H:i T', strtotime($jobs[$i]['nextRun'])) : 'Not scheduled') . '</td>';
                echo '<td class="pointer text-center" data-toggle="collapse" data-target="#schedule'.$i.'"><a href="#" onClick="return false;">View</a></td>';
                echo '<td class="pointer text-center" data-toggle="collapse" data-target="#restorepoints'.$i.'"><a href="#" onClick="return false;">View</a></td>';
                echo '<td>';
                
                if ($jobs[$i]['isEnabled'] != 'true') {
                    echo '<span id="span-job-' . $jobs[$i]['id'] . '"><button class="btn btn-default" id="btn-change-job-state" data-call="enable" data-name="' . $jobs[$i]['name'] . '" data-jid="' . $jobs[$i]['id'] . '" title="Enable job"><i class="fa fa-power-off text-success fa-lg" aria-hidden="true"></i></button></a></span>&nbsp;';
                } else {
                    echo '<span id="span-job-' . $jobs[$i]['id'] . '"><button class="btn btn-default" id="btn-change-job-state" data-call="disable" data-name="' . $jobs[$i]['name'] . '" data-jid="' . $jobs[$i]['id'] . '" title="Disable job"><i class="fa fa-power-off text-danger fa-lg"></i></button></a></span>&nbsp;';
                }
                
                echo '<button class="btn btn-success" id="btn-job-start" data-call="startjob" data-name="' . $jobs[$i]['name'] . '" data-cid="' . $jobs[$i]['id'] . '" title="Start job"><i class="fa fa-play" aria-hidden="true"></i></button></a>&nbsp;';
                echo '</td>';
                echo '</tr>';
                
                echo '<tr>'; /* Start of table for job schedule */
                echo '<td colspan="6" class="zeroPadding">';
                echo '<div id="schedule'.$i.'" class="accordian-body collapse">';
                ?>
                    <table class="table table-bordered table-small table-striped">
                        <thead>
                            <tr>
                                <th>Schedule Policy</th>
                                <th>Periodically Run</th>
                                <th>Daily Type</th>
                                <th>Run at</th>
                                <th>Retry Enabled</th>
                                <th>Retry Number</th>
                                <th>Retry Wait Interval</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <?php
                                echo '<td>' . $jobs[$i]['schedulePolicy']['type'] . '</td>';
                                echo '<td>' . (isset($jobs[$i]['schedulePolicy']['periodicallyEvery']) ? $jobs[$i]['schedulePolicy']['periodicallyEvery'] : 'N/A') . '</td>';
                                echo '<td>' . (isset($jobs[$i]['schedulePolicy']['dailyType']) ? $jobs[$i]['schedulePolicy']['dailyType'] : 'N/A') . '</td>';
                                echo '<td>' . (isset($jobs[$i]['schedulePolicy']['dailyTime']) ? $jobs[$i]['schedulePolicy']['dailyTime'] : 'N/A') . '</td>';
                                echo '<td class="text-center">';
                                if ($jobs[$i]['schedulePolicy']['retryEnabled'] == 'true') { echo '<span class="label label-success">Yes</span>'; } else { echo '<span class="label label-danger">No</span>'; }
                                echo '</td>';
                                echo '<td>' . (isset($jobs[$i]['schedulePolicy']['retryNumber']) ? $jobs[$i]['schedulePolicy']['retryNumber'] : 'N/A') . '</td>';
                                echo '<td>' . (isset($jobs[$i]['schedulePolicy']['retryWaitInterval']) ? $jobs[$i]['schedulePolicy']['retryWaitInterval'] . 'm' : 'N/A') . '</td>';
                                ?>
                            </tr>
                        </tbody>
                    </table>
                <?php
                echo '</div>';
                echo '</td>';
                echo '</tr>';
                
                echo '<tr>'; /* Start of table for job restore points */
                echo '<td colspan="6" class="zeroPadding"><div id="restorepoints'.$i.'" class="accordian-body collapse">';
                ?>
                    <table class="table table-bordered table-small table-striped">
                    <thead>
                    <tr>
                    <th>Point in time</th>
                    <th>Status</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    $jobsession = $veeam->getJobSession($jobs[$i]['id']);
                    
                    for ($j = 0; $j < count($jobsession); $j++) {
                        echo '<tr>';
                        echo '<td>' . (isset($jobsession[$j]['creationTime']) ? date('d/m/Y H:i T', strtotime($jobsession[$j]['creationTime'])) : 'N/A') . '</td>';
                        if (strcmp($jobsession[$j]['status'], 'Success') === 0) {
                            echo '<td><span class="label label-success">' . $jobsession[$j]['status'] . '</span></td>';    
                        } else if (strcmp($jobsession[$j]['status'], 'Warning') === 0) {
                            echo '<td><span class="label label-warning">' . $jobsession[$j]['status'] . '</span></td>';    
                        } else {
                            echo '<td><span class="label label-danger">' . $jobsession[$j]['status'] . '</span></td>';    
                        }
                        echo '</tr>';
                    }
                    ?>
                    </tbody>
                    </table>
                <?php
                echo '</div>';
                echo '</td>';
                echo '</tr>';
            }
            ?>
        </tbody>
    </table>
    <?php
    } else {
        echo 'No backup jobs found.';
    }
    ?>
</div>
<script>
/* Job Buttons */
$(document).on('click', '#btn-change-job-state', function(e) {
    var jid = $(this).data('jid'); /* Job ID */
    var name = $(this).data('name'); /* Job name */
    var call = $(this).data('call'); /* Job call: enable or disable */
    var json = '{ "'+call+'": null }';
    
    $.get('veeam.php', {'action' : 'changejobstate', 'id' : jid, 'json' : json}).done(function(data) {              
        if (call == 'enable') {
            $('#span-job-' + jid).html('<button class="btn btn-default" id="btn-change-job-state" data-call="disable" data-name="' + name + '" data-jid="' + jid + '" title="Disable job"><i class="fa fa-power-off text-danger fa-lg" aria-hidden="true"></i></button></a>&nbsp;');
            
            $('#infobox').html('<div class="alert alert-success alert-dismissible" role="alert"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button><strong>Job '+name+' has been '+call+'d.</strong></div>');

        } else {
            $('#span-job-' + jid).html('<button class="btn btn-default" id="btn-change-job-state" data-call="enable" data-name="' + name + '" data-jid="' + jid + '" title="Enable job"><i class="fa fa-power-off text-success fa-lg" aria-hidden="true"></i></button></a>&nbsp;');
            
            $('#infobox').html('<div class="alert alert-danger alert-dismissible" role="alert"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button><strong>Job '+name+' has been '+call+'d.</strong></div>');

        }
    });
});
$(document).on('click', '#btn-job-start', function(e) {
    var action = $(this).data('call'); /* Action to perform: start or stop */
    var id = $(this).data('cid'); /* Job ID */
    var name = $(this).data('name'); /* Job name */
    
    $.get('veeam.php', {'action' : action, 'id' : id}).done(function(data) {
        $('#infobox').html('<div class="alert alert-info alert-dismissible" role="alert"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button><strong>' + data + '</strong></div>');
    });
});
</script>