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
	$jobs = $veeam->getJobs();
	$org = $veeam->getOrganizations();
	$proxies = $veeam->getProxies();
?>
<div class="main-container">
    <h1>Jobs</h1>
    <?php
    if (count($jobs) != '0') {
    ?>
	<input class="form-control search" id="filter-jobs" placeholder="Filter jobs..." />
    <table class="table table-hover table-bordered table-striped table-border" id="table-jobs">
        <thead>
            <tr>
                <th>Job Name</th>
				<th>Organization</th>
                <th>Status</th>
                <th>Next Run</th>
                <th class="text-center">Schedule</th>
                <th class="text-center">Restore Points</th>
                <th class="text-center">Options</th>
            </tr>
        </thead>
        <tbody>
            <?php
            for ($i = 0; $i < count($jobs); $i++) {
                echo '<tr>';
                echo '<td>' . $jobs[$i]['name'] . '</td>';
                
				$id = explode('/', $jobs[$i]['_links']['organization']['href']); // Get the organization ID
				
                for ($j = 0; $j < count($org); $j++) {
					if ($version != 'v2') {
						if ($org[$j]['id'] === end($id)) {
							echo '<td>' . $org[$j]['name'] . '</td>';
						}
					} else {
						if ($org[$j]['id'] === end($id)) {
							echo '<td>' . $org[$j]['name'] . '</td>';
						} else { /* This only happens with the v2 API requesting organizations which were added by VBO v3 */
							echo '<td>N/A</td>';
						}
					}
                }

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
                echo '<td colspan="7" class="zeroPadding">';
                echo '<div id="schedule'.$i.'" class="accordian-body collapse">';
                ?>
                    <table class="table table-bordered table-small table-striped">
                        <thead>
                            <tr>
                                <th>Schedule Policy</th>
                                <th>Periodically Run</th>
                                <th>Daily Type</th>
                                <th>Run At</th>
                                <th class="text-center">Retry Enabled</th>
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
                echo '<td colspan="7" class="zeroPadding"><div id="restorepoints'.$i.'" class="accordian-body collapse">';
                ?>
                    <table class="table table-bordered table-small table-striped">
                    <thead>
                    <tr>
                    <th>Point In Time</th>
                    <th>Status</th>
					<th>Bottleneck</th>
					<th>Transferred</th>
					<th class="text-center">Session Log</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    $jobsession = $veeam->getJobSession($jobs[$i]['id']);

                    for ($j = 0; $j < count($jobsession); $j++) {
                        echo '<tr>';
                        echo '<td>' . (isset($jobsession[$j]['endTime']) ? date('d/m/Y H:i T', strtotime($jobsession[$j]['endTime'])) : 'N/A') . '</td>';
                        if (strcmp($jobsession[$j]['status'], 'Success') === 0) {
                            echo '<td><span class="label label-success">' . $jobsession[$j]['status'] . '</span></td>';    
                        } else if (strcmp($jobsession[$j]['status'], 'Warning') === 0) {
                            echo '<td><span class="label label-warning">' . $jobsession[$j]['status'] . '</span></td>';    
                        } else {
                            echo '<td><span class="label label-danger">' . $jobsession[$j]['status'] . '</span></td>';    
                        }
						echo '<td>' . $jobsession[$j]['statistics']['bottleneck'] . '</td>';
						echo '<td>' . $jobsession[$j]['statistics']['processedObjects'] . ' items processed</td>';
						echo '<td class="text-center"><a href="#" class="item" data-sessionid="' . $jobsession[$j]['id'] . '" onClick="return false;">View</a></td>';					
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
        echo '<p>No backup jobs have been configured.</p>';
    }
    ?>
</div>

<div class="modal" id="sessionModalCenter" role="dialog">
    <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h1 class="modal-title">Session info</h1>
      </div>
      <div class="modal-body">
        <table class="table table-bordered table-padding table-striped" id="table-session-content">
            <thead>
                <tr>
                    <th>Action</th>
                    <th>Duration</th>
                </tr>
            </thead>
            <tbody>
            </tbody>
        </table>
      </div>
	  <div class="modal-footer">
        <button type="button" class="btn btn-success" data-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>


<script>
/* Job filter */
$("#filter-jobs").keyup(function(e) {
    var searchText = $(this).val().toLowerCase();
    /* Show only matching row, hide rest of them */
    $.each($("#table-jobs tbody tr"), function(e) {
        if ($(this).text().toLowerCase().indexOf(searchText) === -1) {
           $(this).hide();
        } else {
           $(this).show();
        }
    });
});

/* Session window */
$(document).on("click", ".item", function(e) {
    var icon, text;
    var id = $(this).data("sessionid");
    
    $.get("veeam.php", {"action" : "getbackupsessionlog", "id" : id}).done(function(data) {
        response = JSON.parse(data);

        $('#table-session-content tbody').empty();
        
        for (var i = 0; i < response.results.length; i++) {
            if (response.results[i].title.match(/Success/g)) { /* Success icon */
                icon = 'check-circle';
                text = 'success';
            } else if (response.results[i].title.match(/Warning/g)) { /* Warning icon */
                icon = 'exclamation-triangle';
                text = 'warning';
            } else { /* Failed icon */
                icon = 'times-circle';
                text = 'danger';
            }
			
			var creationTime = moment(response.results[i].creationTime);
			var endTime = moment(response.results[i].endTime);
			var duration = moment.duration(endTime.diff(creationTime));
			
            $('#table-session-content tbody').append('<tr> \
                    <td><span class="fa fa-'+icon+' text-'+text+'" title="'+text.charAt(0).toUpperCase() + text.slice(1) +'"></span> ' + response.results[i].title + '</td> \
                    <td>' + moment.utc(duration.asMilliseconds()).format('HH:mm:ss') + '</td> \
                    </tr>');
        }
                
        $('#sessionModalCenter').modal('show');
    });
});

/* Job Buttons */
$(document).on("click", "#btn-change-job-state", function(e) {
    var jid = $(this).data("jid"); /* Job ID */
    var name = $(this).data("name"); /* Job name */
    var call = $(this).data("call"); /* Job call: enable or disable */
    var json = '{ "'+call+'": null }';
    
    $.get("veeam.php", {"action" : "changejobstate", "id" : jid, "json" : json}).done(function(data) {              
        if (call == "enable") {
            $('#span-job-' + jid).html('<button class="btn btn-default" id="btn-change-job-state" data-call="disable" data-name="' + name + '" data-jid="' + jid + '" title="Disable job"><i class="fa fa-power-off text-danger fa-lg" aria-hidden="true"></i></button></a>&nbsp;');
			Swal.fire({
				type: 'info',
				title: 'Job status',
				text: 'Job ' + name + ' has been ' + call + 'd.'
			})
        } else {
            $('#span-job-' + jid).html('<button class="btn btn-default" id="btn-change-job-state" data-call="enable" data-name="' + name + '" data-jid="' + jid + '" title="Enable job"><i class="fa fa-power-off text-success fa-lg" aria-hidden="true"></i></button></a>&nbsp;');
            Swal.fire({
				type: 'info',
				title: 'Job status',
				text: 'Job ' + name + ' has been ' + call + 'd.'
			})
        }
    });
});
$(document).on("click", "#btn-job-start", function(e) {
    var action = $(this).data("call"); /* Action to perform: start or stop */
    var id = $(this).data("cid"); /* Job ID */
    var name = $(this).data("name"); /* Job name */
    
    $.get("veeam.php", {"action" : action, "id" : id}).done(function(data) {
		Swal.fire({
			type: 'info',
			title: 'Job status',
			text: '' + data
		})
    });
});
</script>
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