<?php
define('AJAX_REQUEST', isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest');
if (!AJAX_REQUEST) { 
	header('Location: ../index.php'); 
}

require_once('../config.php');
require_once('../veeam.class.php');

session_start();

$veeam = new VBO($host, $port);
$veeam->setToken($_SESSION['token']);

$jobs = $veeam->getJobs();
$org = $veeam->getOrganizations();
$proxies = $veeam->getProxies();

echo '<h1>Veeam Backup for Office 365 RESTful API demo</h1>';
echo '<div id="infobox" class="text-center"></div>';

if (count($org) != '0') {
	echo '<span><a href="#wizard" role="button" class="btn btn-default" data-toggle="modal">Create job</a></span><br /><br />';
}

if (count($jobs) != '0') {
	echo '<table class="table table-bordered table-striped" id="table-jobs">';
	echo '<thead>';
	echo '<tr>';
	echo '<th>Job name</th>';
	echo '<th>Status</th>';
	echo '<th>Schedule</th>';
	echo '<th>Options</th>';
	echo '</tr>';
	echo '</thead>';
	echo '<tbody>';
	
	for ($i = 0; $i < count($jobs); $i++) {
		echo '<tr>';
		echo '<td>';
		echo $jobs[$i]['name'];
		
		$id = explode('/', $jobs[$i]['_links']['organization']['href']);
		
		// Get the organization and add it to the job name
		for ($j = 0; $j < count($org); $j++) {
			if ($org[$j]['id'] === end($id)) {
				echo ' <em><strong>(' . $org[$j]['name'] . ')</strong></em>';
			}
		}
		
		echo '</td>';

		if ($jobs[$i]['lastRun']) {
			echo '<td>' . $jobs[$i]['lastStatus'] . ' (' .  date('d/m/Y H:i T', strtotime($jobs[$i]['lastRun'])) . ')</td>';
		} else {
			echo '<td>' . $jobs[$i]['lastStatus'] . '</td>';	
		}
		
		echo '<td data-toggle="collapse" data-target="#items'.$i.'" class="pointer"><a href="#">View schedule</a></td>';
		echo '<td>';
		if ($jobs[$i]['isEnabled'] != 'true') {
			echo '<span id="span-job-' . $jobs[$i]['id'] . '"><button class="btn btn-default" id="btn-changejobstate" data-call="enable" data-name="' . $jobs[$i]['name'] . '" data-jid="' . $jobs[$i]['id'] . '" title="Enable job"><i class="fa fa-power-off text-success fa-lg" aria-hidden="true"></i></button></a></span>&nbsp;';
		} else {
			echo '<span id="span-job-' . $jobs[$i]['id'] . '"><button class="btn btn-default" id="btn-changejobstate" data-call="disable" data-name="' . $jobs[$i]['name'] . '" data-jid="' . $jobs[$i]['id'] . '" title="Disable job"><i class="fa fa-power-off text-danger fa-lg"></i></button></a></span>&nbsp;';
		}
		echo '<button class="btn btn-success" id="btn-start" data-call="startjob" data-name="' . $jobs[$i]['name'] . '" data-cid="' . $jobs[$i]['id'] . '" title="Start job"><i class="fa fa-play" aria-hidden="true"></i></button></a>&nbsp;';
		echo '<button class="btn btn-danger" id="btn-delete" data-call="removejob" data-rcall="jobs" data-name="' . $jobs[$i]['name'] . '" data-cid="' . $jobs[$i]['id'] . '" title="Delete Job"><i class="fa fa-trash-o" aria-hidden="true"></i></button></a>';
		echo '</td>';	
		echo '</tr>';
		echo '<tr>';
		echo '<td colspan="4" class="zeroPadding"><div id="items'.$i.'" class="accordian-body collapse"><strong>Schedule settings:</strong><br />';
		echo '<u>Job type:</u> ' . $jobs[$i]['schedulePolicy']['type'] . '<br />';
		echo '<u>Run every:</u> ' . $jobs[$i]['schedulePolicy']['dailyType'] . '<br />';
		echo '<u>Run at:</u> ' . $jobs[$i]['schedulePolicy']['dailyTime'] . '<br />';
		echo '<u>Retry job:</u> ';
		if ($jobs[$i]['schedulePolicy']['retryEnabled'] == '1') { echo 'Yes'; } else { echo 'No'; }
		echo '<br />';
		echo '<u>Retry times:</u> ' . $jobs[$i]['schedulePolicy']['retryNumber'] . '<br />';
		echo '</div>';
		echo '</div></td>';
		echo '</tr>';
	}
	
	echo '</tbody>';
	echo '</table>';
	echo '</table>';
} else {
	echo 'No backup jobs found.';
}
?>
<script>
/* Wizard related */
$('.next').click(function(e) {
	var nextId = $(this).parents('.tab-pane').next().attr('id');
	$('[href="#'+nextId+'"]').tab('show');
	return false;
});

$('.prev').click(function(e) {
	var prevId = $(this).parents('.tab-pane').prev().attr('id');
	$('[href="#'+prevId+'"]').tab('show');
	return false;
});

$('#wizard').on('hidden.bs.modal', function (e) {
	$('[href="#step1"]').tab('show');
});

/* Backup job related */
$('#job-backupall').on('change', function(e) {
	$('#job-mailboxes').addClass('hide'); 
});

$('#job-backupfollowing').on('change', function(e) {
	$('#job-mailboxes').removeClass('hide');
});

$('#job-org').change(function(e) {
	var id = $(this).val();

	$('#job-mailboxes').empty();
  
	$.get('veeam.php', {'action' : 'listmailboxes', 'id' : id}, function(data) {
		var response = data.split('#');
		var mid, email, name;
	
		for (i = 0; i < response.length-1; ++i) {
			result = response[i].split('|');
			mid = result[0];
			email = result[1];
			name = result[2];
			value = response[i];
		
			$('#job-mailboxes').append('<option id="' + mid + '" value="' + value + '">' + name + ' (' + email + ')</option>');
		}
	});
});

$('#job-proxy').change(function(e) {
	var id = $(this).val();

	$('#job-repo').empty();

	$.get('veeam.php', {'action' : 'listrepositories', 'id' : id}, function(data) {
		var response = data.split('#');
		var rid, name;
	
		for (i = 0; i < response.length-1; ++i) {
			result = response[i].split('|');
			rid = result[0];
			name = result[1];
			
			$('#job-repo').append('<option value="' + rid + '">' + name + '</option>');
		}
	});
});
</script>
<div id="wizard" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="wizardlabel" aria-hidden="true">
	<div class="modal-dialog">
	  <div class="modal-content">
		<div class="modal-header">
		  <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
		  <h3 id="wizardlabel" class="text-center">New backup job</h3>
		</div>
		<div class="modal-body" id="mywizard">
		  <form>
		  <div class="navbar hide">
			 <a href="#step1" data-toggle="tab" data-step="1"></a>
			 <a href="#step2" data-toggle="tab" data-step="2"></a>
			 <a href="#step3" data-toggle="tab" data-step="3"></a>
			 <a href="#step4" data-toggle="tab" data-step="4"></a>
		  </div>
		  <div class="tab-content">
			<div class="tab-pane active clearfix" id="step1">
				<div class="well">
					<label for="job-org">Organization:</label>
					<select class="form-control" id="job-org">
						<option disabled selected> -- select organization -- </option>
					<?php
					for ($i = 0; $i < count($org); $i++) {
						echo '<option value="' . $org[$i]['id'] . '">' . $org[$i]['name'] . '</option>';
					}
					?>
					</select>
					<br />
					<label for="job-name">Name:</label>
					<input type="text" class="form-control" id="job-name" placeholder="Backup Job"></input>
					<br />
					<label for="job-desc">Description:</label>
					<textarea class="form-control noresize" rows="4" id="job-desc"></textarea>
				</div>
				<a class="btn btn-default next pull-right" href="#">Next</a>
			</div>
			<div class="tab-pane fade clearfix" id="step2">
				<div class="well">
					<input type="radio" id="job-backupall" name="job-backupsetting" value="job-backupall"> Backup all mailboxes<br />
					<input type="radio" id="job-backupfollowing" name="job-backupsetting" value="job-backupfollowing"> Backup the following mailboxes
					<select class="form-control hide" id="job-mailboxes" size="5" multiple="multiple">
					</select>
				</div>
				<a class="btn btn-default prev pull-left" href="#">Previous</a>
				<a class="btn btn-default next pull-right" href="#">Next</a>
			</div>
			<div class="tab-pane fade clearfix" id="step3">
				<div class="well">
					<label for="job-proxy">Backup proxy:</label>
					<select class="form-control" id="job-proxy">
					<option disabled selected> -- select proxy -- </option>
					<?php
						for ($i = 0; $i < count($proxies); $i++) {
							echo '<option value="' . $proxies[$i]['id'] . '">' . $proxies[$i]['hostName'] . '</option>';
						}
					?>
					</select>
					<br />
					<label for="job-repo">Backup repository:</label>
					<select class="form-control" id="job-repo">
					</select>
				</div>
				<a class="btn btn-default prev pull-left" href="#">Previous</a>
				<a class="btn btn-default next pull-right" href="#">Next</a>
			</div>
			<div class="tab-pane fade clearfix" id="step4">
			   <div class="well">
				  <label for="job-scheduleperiod">Run the job:</label><br />
				  <input type="radio" id="job-dailyschedule" name="job-scheduleperiod" value="Daily"> Daily at this time:
				  <select id="job-dailyHour">
					<option value="00" selected>00</option>
					<option value="01">01</option>
					<option value="02">02</option>
					<option value="03">03</option>
					<option value="04">04</option>
					<option value="05">05</option>
					<option value="06">06</option>
					<option value="07">07</option>
					<option value="08">08</option>
					<option value="09">09</option>
					<option value="10">10</option>
					<option value="11">11</option>
					<option value="12">12</option>
				  </select>
				  :
				  <select id="job-dailyMin">
					<option value="00:00" selected>00</option>
					<option value="01:00">01</option>
					<option value="02:00">02</option>
					<option value="03:00">03</option>
					<option value="04:00">04</option>
					<option value="05:00">05</option>
					<option value="06:00">06</option>
					<option value="07:00">07</option>
					<option value="08:00">08</option>
					<option value="09:00">09</option>
					<option value="10:00">10</option>
					<option value="11:00">11</option>
					<option value="12:00">12</option>
					<option value="13:00">13</option>
					<option value="14:00">14</option>
					<option value="15:00">15</option>
					<option value="16:00">16</option>
					<option value="17:00">17</option>
					<option value="18:00">18</option>
					<option value="19:00">19</option>
					<option value="20:00">20</option>
					<option value="21:00">21</option>
					<option value="22:00">22</option>
					<option value="23:00">23</option>
					<option value="24:00">24</option>
					<option value="25:00">25</option>
					<option value="26:00">26</option>
					<option value="27:00">27</option>
					<option value="28:00">28</option>
					<option value="29:00">29</option>
					<option value="30:00">30</option>
					<option value="31:00">31</option>
					<option value="32:00">32</option>
					<option value="33:00">33</option>
					<option value="34:00">34</option>
					<option value="35:00">35</option>
					<option value="36:00">36</option>
					<option value="37:00">37</option>
					<option value="38:00">38</option>
					<option value="39:00">39</option>
					<option value="40:00">40</option>
					<option value="41:00">41</option>
					<option value="42:00">42</option>
					<option value="43:00">43</option>
					<option value="44:00">44</option>
					<option value="45:00">45</option>
					<option value="46:00">46</option>
					<option value="47:00">47</option>
					<option value="48:00">48</option>
					<option value="49:00">49</option>
					<option value="50:00">50</option>
					<option value="51:00">51</option>
					<option value="52:00">52</option>
					<option value="53:00">53</option>
					<option value="54:00">54</option>
					<option value="55:00">55</option>
					<option value="56:00">56</option>
					<option value="57:00">57</option>
					<option value="58:00">58</option>
					<option value="59:00">59</option>
				  </select>
				  <select id="job-dailyType">
					<option value="Everyday" selected>Everyday</option>
					<option value="Workdays">Workdays</option>
					<option value="Weekends">Weekends</option>
					<option value="Monday">Monday</option>
					<option value="Tuesday">Tuesday</option>
					<option value="Wednesday">Wednesday</option>
					<option value="Thursday">Thursday</option>
					<option value="Friday">Friday</option>
					<option value="Saturday">Saturday</option>
					<option value="Sunday">Sunday</option>
				  </select>
				  <br />
				  <input type="radio" id="job-periodicallyschedule" name="job-scheduleperiod" value="Periodically"> Periodically every:
				  <select id="job-periodicallyevery">
					<option value="Minutes5" selected>5 minutes</option>
					<option value="Minutes10">10 minutes</option>
					<option value="Minutes15">15 minutes</option>
					<option value="Minutes30">30 minutes</option>
					<option value="Hours1">1 hour</option>
					<option value="Hours2">2 hours</option>
					<option value="Hours4">4 hours</option>
					<option value="Hours8">8 hours</option>
				  </select>
				  <br /><br />
				  <input type="checkbox" id="job-retry"> Retry failed mailbox processing: <input type="number" id="job-retrynumber" min="1" max="99" value="3"> times<br />
				  Wait before each retry attempt for: <input type="number" id="job-retryinterval" min="1" max="999" value="10"> minutes<br />
				  <input type="checkbox" id="job-isrun"> Run the job when I click Finish
			   </div>
			   <a class="btn btn-default prev pull-left" href="#">Previous</a>
			   <button class="btn btn-success pull-right" id="btn-create-wizard" data-call="createjob" title="Finish">Finish</button>
			</div>
		  </div>
		  </form>
		</div>
		<div class="modal-footer">
		  <button class="btn pull-left" id="btn-reset" data-dismiss="modal">Cancel</button>
		</div>
	  </div>
	</div>
</div>