<?php
require 'config.php';
require 'veeam.class.php';

if (isset($_GET['action'])) { $action = $_GET['action']; }
if (isset($_GET['id'])) { $id = $_GET['id']; }
if (isset($_GET['json'])) { $json = $_GET['json']; }

session_start();

$veeam = new VBO($host, $port);
$veeam->setToken($_SESSION['token']);

/* Menu calls */
if ($action == 'getjobs') {
	$jobs = $veeam->getJobs();
	$org = $veeam->getOrganizations();
	$proxies = $veeam->getProxies();

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
			echo '<button class="btn btn-danger" id="btn-delete" data-call="removejob" data-rcall="getjobs" data-name="' . $jobs[$i]['name'] . '" data-cid="' . $jobs[$i]['id'] . '" title="Delete Job"><i class="fa fa-trash-o" aria-hidden="true"></i></button></a>';
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
	
	// Only show the form when the total of organizations is atleast 1.
	if (count($org) != '0') {
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
	<?php
	}
}

if ($action == 'getmailboxes') {
	$jobs = $veeam->getJobs($id);
	$proxies = $veeam->getProxies();
	
	echo '<div id="infobox" class="text-center"></div>';
	echo '<span><a href="#wizard" role="button" class="btn btn-default" data-toggle="modal">Create job</a></span><br /><br />';

	if (count($jobs) != '0') {
		for ($i = 0; $i < count($jobs); $i++) {
			$jid = $jobs[$i]['id'];
			$mailbox = $veeam->getSelectedMailboxes($jid);
			
			echo '<strong>Backup job:</strong> ' . $jobs[$i]['name'] . '<br /><br />';
			
			if (count($mailbox['results']) != '0') {
				echo '<span id="span-item-restore-' . $jobs[$i]['id'] . '"><button class="btn btn-default btn-success" id="btn-start-item-restore" data-oid="' . $id . '" data-jid="' . $jobs[$i]['id'] . '" data-rp="' . date('Y.m.d H:i:s', strtotime($jobs[$i]['lastRun'])) . '" title="Start Explorer">Start Explorer</button></span><br />';
			
				for ($x = 0; $x < count($mailbox['results']); $x++) {
			?>
			<script>
			/* Iteam search */
			$('#search-<?php echo $mailbox['results'][$x]['id']; ?>').keyup(function(e) {
				var searchText = $(this).val().toLowerCase();
				/* Show only matching row, hide rest of them */
				$.each($('#table-mailitems-<?php echo $mailbox['results'][$x]['id']; ?> tbody tr'), function(e) {
					if($(this).text().toLowerCase().indexOf(searchText) === -1)
					   $(this).hide();
					else
					   $(this).show();
				});
			});
			</script>
			<?php
				}
			
				echo '<table class="table table-bordered table-striped" id="table-mailboxes">';
				echo '<thead>';
				echo '<tr>';
				echo '<th class="mail-name">Name</th>';
				echo '<th class="mail-email">E-mail address</th>';
				echo '<th class="text-center settings">Items</th>';
				echo '</tr>';
				echo '</thead>';
				echo '<tbody>'; 
				for ($j = 0; $j < count($mailbox['results']); $j++) {
					echo '<tr>';
					echo '<td>' . $mailbox['results'][$j]['name'] . '</td>';
					echo '<td>' . $mailbox['results'][$j]['email'] . '</td>';
					echo '<td><a href="#" name="link-restore" data-mid="' . $mailbox['results'][$j]['id'] . '"  data-target="#items-' . $mailbox['results'][$j]['id'] . '">View items</a></td>';
					echo '</tr>';
					echo '<tr>';
					echo '<td colspan="4" class="zeroPadding"><div id="items-' . $mailbox['results'][$j]['id'] . '" class="accordian-body collapse"><u><h3>Items available for restore:</h3></u>';
					echo '<input class="search" id="search-' . $mailbox['results'][$j]['id'] . '" placeholder="Search item" /><br /><br />';
					echo '<table class="table table-bordered table-striped" name="table-mailitems" id="table-mailitems-' . $mailbox['results'][$j]['id'] . '">';
					echo '<thead>';
					echo '<tr>';
					echo '<th class="mail-from">From</th>';
					echo '<th class="mail-subject">Subject</th>';
					echo '<th class="mail-received">Received</th>';
					echo '<th class="text-center settings mail-options">Options</th>';
					echo '</tr>';
					echo '<tbody>';
					echo '</tbody>';
					echo '</table>';
					echo '</div></td>';
					echo '</tr>';
				}
				echo '</tbody>';
				echo '</table>';
				echo '<hr />';
			} else {
				/* See if the mailboxes are in a job with 'Backup all mailboxes' enabled */
				$mailbox = $veeam->getOrganizationMailboxes($id);
				
				if (count($mailbox['results']) != '0') {
					echo '<span id="span-item-restore-' . $jobs[$i]['id'] . '"><button class="btn btn-default btn-success" id="btn-start-item-restore" data-oid="' . $id . '" data-jid="' . $jobs[$i]['id'] . '" data-rp="' . date('Y.m.d H:i:s', strtotime($jobs[$i]['lastRun'])) . '" title="Start Explorer">Start Explorer</button></span><br />';
				
					for ($x = 0; $x < count($mailbox['results']); $x++) {
				?>
				<script>
				/* Iteam search */
				$('#search-<?php echo $mailbox['results'][$x]['id']; ?>').keyup(function(e) {
					var searchText = $(this).val().toLowerCase();
					/* Show only matching row, hide rest of them */
					$.each($('#table-mailitems-<?php echo $mailbox['results'][$x]['id']; ?> tbody tr'), function(e) {
						if($(this).text().toLowerCase().indexOf(searchText) === -1)
						   $(this).hide();
						else
						   $(this).show();
					});
				});
				</script>
				<?php
					}
				
					echo '<table class="table table-bordered table-striped" id="table-mailboxes">';
					echo '<thead>';
					echo '<tr>';
					echo '<th class="mail-name">Name</th>';
					echo '<th class="mail-email">E-mail address</th>';
					echo '<th class="text-center settings">Items</th>';
					echo '</tr>';
					echo '</thead>';
					echo '<tbody>'; 
					for ($j = 0; $j < count($mailbox['results']); $j++) {
						echo '<tr>';
						echo '<td>' . $mailbox['results'][$j]['name'] . '</td>';
						echo '<td>' . $mailbox['results'][$j]['email'] . '</td>';
						echo '<td><a href="#" name="link-restore" data-mid="' . $mailbox['results'][$j]['id'] . '"  data-target="#items-' . $mailbox['results'][$j]['id'] . '">View items</a></td>';
						echo '</tr>';
						echo '<tr>';
						echo '<td colspan="4" class="zeroPadding"><div id="items-' . $mailbox['results'][$j]['id'] . '" class="accordian-body collapse"><u><h3>Items available for restore:</h3></u>';
						echo '<input class="search" id="search-' . $mailbox['results'][$j]['id'] . '" placeholder="Search item" /><br /><br />';
						echo '<table class="table table-bordered table-striped" name="table-mailitems" id="table-mailitems-' . $mailbox['results'][$j]['id'] . '">';
						echo '<thead>';
						echo '<tr>';
						echo '<th class="mail-from">From</th>';
						echo '<th class="mail-subject">Subject</th>';
						echo '<th class="mail-received">Received</th>';
						echo '<th class="text-center settings mail-options">Options</th>';
						echo '</tr>';
						echo '<tbody>';
						echo '</tbody>';
						echo '</table>';
						echo '</div></td>';
						echo '</tr>';
					}
					echo '</tbody>';
					echo '</table>';
					echo '<hr />';					
				} else {
					echo 'No mailboxes found in <strong>' . $jobs[$i]['name'] . '</strong>. Make sure the job has run atleast once.';
				}
			}
		}
	?>
	<div id="form-restore-original" class="form-content" style="display:none;">
	  <form class="form" role="form">
		<label for="restore-user">Username:</label>
		<input type="text" class="form-control" id="restore-user" placeholder="user@example.onmicrosoft.com"></input>
		<br />
		<label for="restore-pass">Password:</label>
		<input type="password" class="form-control" id="restore-pass" placeholder="password"></input>
	  </form>
	</div>
	<div id="form-restore-different" class="form-content" style="display:none;">
	  <form class="form" role="form">
		<label for="restore-mailbox">Target mailbox:</label>
		<input type="text" class="form-control" id="restore-mailbox" placeholder="user@example.onmicrosoft.com"></input>
		<br />
		<label for="restore-casserver">Target mailbox server (CAS):</label>
		<input type="text" class="form-control" id="restore-casserver" placeholder="outlook.office365.com" value="outlook.office365.com"></input>
		<br />
		<label for="restore-user">Username:</label>
		<input type="text" class="form-control" id="restore-user" placeholder="user@example.onmicrosoft.com"></input>
		<br />
		<label for="restore-pass">Password:</label>
		<input type="password" class="form-control" id="restore-pass" placeholder="password"></input>
		<br />
		<label for="restore-folder">Folder:</label>
		<select class="form-control" id="restore-folder">
			<option disabled selected> -- select folder -- </option>
		</select>
	  </form>
	</div>
	<?php
	} else {
		echo 'No backup jobs found for this organization.';
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

	$('#job-next').click(function(e) {
		var id = $('#job-org').val();

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
						<?php
							echo '<input type="hidden" id="job-org" value="' . $id . '">';
						?>
						<label for="job-name">Name:</label>
						<input type="text" class="form-control" id="job-name" placeholder="Backup Job"></input>
						<br />
						<label for="job-desc">Description:</label>
						<textarea class="form-control noresize" rows="4" id="job-desc"></textarea>
					</div>
					<a class="btn btn-default next pull-right" id="job-next" href="#">Next</a>
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
						<option disabled selected> -- select an organization -- </option>
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
					  <input type="checkbox" id="job-isrun"> Run the job when I click Create
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
	<?php
}

if ($action == 'getorganizations') {
	$org = $veeam->getOrganizations();
	
	echo '<div id="infobox" class="text-center"></div>';
	echo '<span><a href="#wizard" role="button" class="btn btn-default" data-toggle="modal">Create organization</a></span><br /><br />';

	if (count($org) != '0') {
		echo '<table class="table table-bordered table-striped" id="table-proxies">';
		echo '<thead>';
		echo '<tr>';
		echo '<th>Name</th>';
		echo '<th>Options</th>';
		echo '</tr>';
		echo '</thead>';
		echo '<tbody>'; 
		for ($i = 0; $i < count($org); $i++) {
			echo '<tr>';
			echo '<td>' . $org[$i]['name'] . '</td>';
			echo '<td><button class="btn btn-danger" id="btn-delete" data-call="removeorg" data-rcall="getorganizations" data-name="' . $org[$i]['name'] . '" data-cid="' . $org[$i]['id'] . '" title="Delete organization"><i class="fa fa-trash-o" aria-hidden="true"></i></button></a></td>';	
			echo '</tr>';
		}
		echo '</tbody>';
		echo '</table>';
	} else {
		echo 'No organizations have been added.';
	}
	?>
	<script>
	/* Wizard related */
	$('.prev').click(function(e) {
		$('[href="#step1"]').tab('show');
		return false;
	});

	$('#wizard').on('hidden.bs.modal', function (e) {
		$('[href="#step1"]').tab('show');
	});
	
	/* Organization related */
	$('#org-next').click(function(e) {
		var deptype = $('#org-deptype').val();
		
		if (deptype == 'office365') {
			$('[href="#step2"]').tab('show');
			return false;
		} else if (deptype == 'onpremises') {
			$('[href="#step3"]').tab('show');
			return false;
		} else {
			$('[href="#step4"]').tab('show');
			return false;
		}
	});
	
	$('#org-next-hybrid').click(function(e) {
		$('[href="#step3"]').tab('show');
		return false;
	});
	</script>
	<div id="wizard" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="wizardlabel" aria-hidden="true">
		<div class="modal-dialog">
		  <div class="modal-content">
			<div class="modal-header">
			  <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
			  <h3 id="wizardlabel" class="text-center">Add organization</h3>
			</div>
			<div class="modal-body" id="mywizard">
			  <form>
			  <div class="navbar hide">
				<ul class="nav nav-tabs">
				  <li><a href="#step1" data-toggle="tab" data-step="1"></a></li>
				  <li><a href="#step2" data-toggle="tab" data-step="2"></a></li>
				  <li><a href="#step3" data-toggle="tab" data-step="3"></a></li>
				  <li><a href="#step4" data-toggle="tab" data-step="4"></a></li>
				</ul>
			  </div>
			  <div class="tab-content">
				<div class="tab-pane active clearfix" id="step1">
					<div class="well">
						<label for="org-deptype">Organization deployment type:</label>
						<select class="form-control" id="org-deptype">
							<option value="office365" selected>Microsoft Office 365</option>
							<option value="hybrid">Hybrid deployment</option>
							<option value="onpremises">On-premises Microsoft Exchange</option>
						</select>
					</div>
					<a class="btn btn-default next pull-right" id="org-next" href="#">Next</a>
				</div>
				<div class="tab-pane fade clearfix" id="step2">
					<div class="well">
						<label for="org-region">Office 365 connection settings:</label>
						<select class="form-control" id="org-region">
							<option value="Worldwide" selected>Default</option>
							<option value="Germany">Germany</option>
							<option value="China">China</option>
							<option value="USgovCommunity">U.S. government (experimental)</option>
							<option value="USgovDefence">U.S. government DOD (experimental)</option>
						</select>
						<br />
						<label for="org-user">Username:</label>
						<input type="text" class="form-control" id="org-user-o365" placeholder="user@domain.onmicrosoft.com"></input>
						<br />
						<label for="org-pass">Password:</label>
						<input type="password" class="form-control" id="org-pass-o365"></input>
						<br />
						<input type="checkbox" id="org-grant"> Grant impersonation to this user
					</div>
					<a class="btn btn-default prev pull-left" href="#">Previous</a>
					<button class="btn btn-success pull-right" id="btn-create-wizard" data-call="createorganization" title="Finish">Finish</button>
				</div>
				<div class="tab-pane fade clearfix" id="step3">
				   <div class="well">
						<label for="org-onpremises">On-premises Exchange connection settings</label><br />
						Server: <input type="text" class="form-control" id="org-server"></input><br />
						<input type="checkbox" id="org-serverusessl"> Use SSL<br />
						<input type="checkbox" id="org-serverskipca"> Skip certificate trusted authority verification<br />
						<input type="checkbox" id="org-serverskipcn"> Skip certificate common name verification<br />
						<input type="checkbox" id="org-serverskiprc"> Skip revocation check<br />
						Username: <input type="text" class="form-control" id="org-user-local" placeholder="DOMAIN\username"></input>
						Password: <input type="password" class="form-control" id="org-pass-local"></input>
						<input type="checkbox" id="org-grant"> Grant impersonation to this user<br />
						<input type="checkbox" id="org-policy"> Configure throttling policy
				   </div>
				   <a class="btn btn-default prev pull-left" href="#">Previous</a>
				   <button class="btn btn-success pull-right" id="btn-create-wizard" data-call="createorganization" title="Finish">Finish</button>
				</div>
				<div class="tab-pane fade clearfix" id="step4">
					<div class="well">
						<label for="org-region">Office 365 connection settings:</label>
						<select class="form-control" id="org-region">
							<option value="Worldwide" selected>Default</option>
							<option value="Germany">Germany</option>
							<option value="China">China (experimental)</option>
							<option value="USgovCommunity">U.S. government (experimental)</option>
							<option value="USgovDefence">U.S. government DOD (experimental)</option>
						</select>
						<br />
						<label for="org-user">Username:</label>
						<input type="text" class="form-control" id="org-user-o365" placeholder="user@domain.onmicrosoft.com"></input>
						<br />
						<label for="org-pass">Password:</label>
						<input type="password" class="form-control" id="org-pass-o365"></input>
						<br />
						<input type="checkbox" id="org-grant"> Grant impersonation to this user
					</div>
					<a class="btn btn-default prev pull-left" href="#">Previous</a>
					<a class="btn btn-default next pull-right" id="org-next-hybrid" href="#">Next</a>
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
	<?php
}

if ($action == 'getproxies') {
	$proxies = $veeam->getProxies();

	echo '<div id="infobox" class="text-center"></div>';
	echo '<span><a href="#wizard" role="button" class="btn btn-default" data-toggle="modal">Create proxy</a></span><br /><br />';
	
	if (count($proxies) != '0') {
		echo '<table class="table table-bordered table-striped" id="table-proxies">';
		echo '<thead>';
		echo '<tr>';
		echo '<th>Name</th>';
		echo '<th>Port</th>';
		echo '<th>Description</th>';
		echo '<th>Options</th>';
		echo '</tr>';
		echo '</thead>';
		echo '<tbody>'; 
		for ($i = 0; $i < count($proxies); $i++) {
			echo '<tr>';
			echo '<td>' . $proxies[$i]['hostName'] . '</td>';
			echo '<td>' . $proxies[$i]['port'] . '</td>';
			echo '<td>' . $proxies[$i]['description'] . '</td>';
			echo '<td><button class="btn btn-danger" id="btn-delete" data-call="removeproxy" data-rcall="getproxies" data-name="' . $proxies[$i]['hostName'] . '" data-cid="' . $proxies[$i]['id'] . '" title="Delete proxy"><i class="fa fa-trash-o" aria-hidden="true"></i></button></a></td>';	
			echo '</tr>';
		}
		echo '</tbody>';
		echo '</table>';
		?>
	<div id="wizard" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="wizardlabel" aria-hidden="true">
		<div class="modal-dialog">
		  <div class="modal-content">
			<div class="modal-header">
			  <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
			  <h3 id="wizardlabel" class="text-center">New proxy</h3>
			</div>
			<div class="modal-body" id="mywizard">
			  <form>
			  <div class="navbar hide">
				 <a href="#step1" data-toggle="tab" data-step="1"></a>
			  </div>
			  <div class="tab-content">
				<div class="tab-pane active clearfix" id="step1">
					<div class="well">
						<label for="proxy-name">Hostname:</label>
						<input type="text" class="form-control" id="proxy-name" placeholder="Hostname"></input>
						<br />
						<label for="proxy-port">Port (default 9193):</label>
						<input type="text" class="form-control" id="proxy-port" value="9193"></input>
						<br />
						<label for="proxy-desc">Description:</label>
						<textarea class="form-control noresize" rows="3" id="proxy-desc"></textarea>
						<br />
						<label for="proxy-user">Username:</label>
						<input type="text" class="form-control" id="proxy-user" placeholder="DOMAIN\username"></input>
						<br />
						<label for="proxy-pass">Password:</label>
						<input type="password" class="form-control" id="proxy-pass" placeholder="password"></input>
					</div>
					<button class="btn pull-left" id="btn-reset" data-dismiss="modal">Cancel</button>
					<button class="btn btn-success pull-right" id="btn-create-wizard" data-call="createproxy" title="Finish">Finish</button>
				</div>
			  </div>
			  </form>
			</div>
		  </div>
		</div>
	</div>
	<?php
	} else {
		echo 'No proxies have been added.';
	}
}

if ($action == 'getrepositories') {
	$repos = $veeam->getBackupRepositories();
	
	echo '<div id="infobox" class="text-center"></div>';
	echo '<span><a href="#wizard" role="button" class="btn btn-default" data-toggle="modal">Create repository</a></span><br /><br />';
	
	if (count($repos) != '0') {
		$proxies = $veeam->getProxies();
		
		echo '<table class="table table-bordered table-striped" id="table-proxies">';
		echo '<thead>';
		echo '<tr>';
		echo '<th>Name</th>';
		echo '<th>Host</th>';
		echo '<th>Capacity</th>';
		echo '<th>Description</th>';
		echo '<th>Options</th>';
		echo '</tr>';
		echo '</thead>';
		echo '<tbody>'; 
		for ($i = 0; $i < count($repos); $i++) {
			echo '<tr>';
			echo '<td>' . $repos[$i]['name'] . '</td>';
			echo '<td>' . $repos[$i]['hostName'] . '</td>';
			echo '<td>' . round($repos[$i]['capacity']/1024/1024/1024, 1) . ' GB (' . round($repos[$i]['freeSpace']/1024/1024/1024, 1)  . ' GB free)</td>';
			echo '<td>' . $repos[$i]['description'] . '</td>';
			echo '<td><button class="btn btn-danger" id="btn-delete" data-call="removerepo" data-rcall="getrepositories" data-name="' . $repos[$i]['name'] . '" data-cid="' . $repos[$i]['id'] . '" title="Delete repository"><i class="fa fa-trash-o" aria-hidden="true"></i></button></a></td>';	
			echo '</tr>';
		}
		echo '</tbody>';
		echo '</table>';
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
	</script>
	<div id="wizard" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="wizardlabel" aria-hidden="true">
		<div class="modal-dialog">
		  <div class="modal-content">
			<div class="modal-header">
			  <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
			  <h3 id="wizardlabel" class="text-center">New backup repository</h3>
			</div>
			<div class="modal-body" id="mywizard">
			<form>
			  <div class="navbar hide">
				<ul class="nav nav-tabs">
					<li class="active"><a href="#step1" data-toggle="tab" data-step="1"></a></li>
					<li><a href="#step2" data-toggle="tab" data-step="2"></a></li>
				</ul>
			  </div>
			  <div class="tab-content">
				<div class="tab-pane active in clearfix" id="step1">
					<div class="well">
					  <label for="repo-name">Name:</label>
					  <input type="text" class="form-control" id="repo-name" placeholder="Repository"></input>
					  <br />
					  <label for="repo-desc">Description:</label>
					  <textarea class="form-control noresize" rows="3" id="repo-desc">Created via web portal.</textarea>
					  <label for="repo-proxy">Backup proxy:</label>
					  <select class="form-control" id="repo-proxy">
					  <?php
						for ($i = 0; $i < count($proxies); $i++) {
							echo '<option value="' . $proxies[$i]['hostName'] . '|' . $proxies[$i]['id'] . '">' . $proxies[$i]['hostName'] . '</option>';
						}
					  ?>
					  </select>
					  <br />
					  <label for="repo-path">Backup path:</label>
					  <input type="text" class="form-control" id="repo-path" value="C:\VeeamRepository"></input>
					</div>
					<a class="btn btn-default next pull-right" href="#">Next</a>
				</div>
				<div class="tab-pane fade clearfix" id="step2">
				   <div class="well">
					  <label for="repo-retention">Retention policy for mailbox items:</label>
					  <select class="form-control" id="repo-retention">
						<option value="Year1" selected>1 year</option>
						<option value="Year2">2 years</option>
						<option value="Year3">3 years</option>
						<option value="Year5">5 years</option>
						<option value="Year7">7 years</option>
						<option value="Year10">10 years</option>
						<option value="Year25">25 years</option>
						<option value="KeepForever">Keep forever</option>
						<option value="repoxdays">Specificied number of days</option>
					  </select>
					  <label>Apply retention policy:</label><br />
					  <input type="radio" id="repo-dailyretentionperiod" name="repo-retentionperiod" value="Daily"> Daily at
					  <select id="repo-dailyHour">
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
					  <select id="repo-dailyMin">
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
					  <select id="repo-dailyType">
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
					  <input type="radio" id="repo-monthlyretentionperiod" name="repo-retentionperiod" value="Monthly"> Monthly at
					  <select id="repo-monthlyHour">
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
					  <select id="repo-monthlyMin">
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
					  <select id="repo-monthlyDaynumber">
						<option value="First">First</option>
						<option value="Second">Second</option>
						<option value="Third">Third</option>
						<option value="Forth">Forth</option>
						<option value="Last">Last</option>
					  </select>
					  <select id="repo-monthlyDayofweek">
						<option value="Monday">Monday</option>
						<option value="Tuesday">Tuesday</option>
						<option value="Wednesday">Wednesday</option>
						<option value="Thursday">Thursday</option>
						<option value="Friday">Friday</option>
						<option value="Saturday">Saturday</option>
						<option value="Sunday">Sunday</option>
					  </select>
				   </div>
				   <a class="btn btn-default prev pull-left" href="#">Previous</a>
				   <button class="btn btn-success pull-right" id="btn-create-wizard" data-call="createrepository" title="Finish">Finish</button>
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
	<?php
	} else {
		'No backup repositories have been added.';
	}
}

if ($action == 'getsessions') {
	$sessions = $veeam->getSessions();

	echo '<div id="infobox" class="text-center"></div>';
		
	if (count($sessions) != '0') {
		echo '<table class="table table-bordered table-striped" id="table-proxies">';
		echo '<tbody>'; 
		echo '<tr>';
		echo '<th colspan="4"><h3>Running sessions</h3></th>';
		echo '</tr>';
		echo '<tr>';
		echo '<td><strong>ID</strong></td>';
		echo '<td><strong>Type</strong></td>';
		echo '<td><strong>State</strong></td>';
		echo '<td><strong>Options</strong></td>';
		echo '</tr>';
		
		for ($i = 0; $i < count($sessions); $i++) {
			if (strtolower($sessions[$i]['state']) == 'working') {
				echo '<tr>';
				echo '<td>' . $sessions[$i]['id'] . '</td>';
				echo '<td>' . $sessions[$i]['type'] . '</td>';
				echo '<td>' . $sessions[$i]['state'] . '</td>';
				echo '<td><button class="btn btn-danger" id="btn-delete" data-call="endrestore" data-rcall="getsessions" data-cid="' . $sessions[$i]['id'] . '" title="End session"><i class="fa fa-trash-o" aria-hidden="true"></i></button></a></td>';	
				echo '</tr>';
			}
		}
		
		echo '<tr>';
		echo '<td colspan="4"><h3>Terminated sessions</h3></td>';
		echo '</tr>';
		echo '<tr>';
		echo '<td><strong>ID</strong></td>';
		echo '<td><strong>Type</strong></td>';
		echo '<td><strong>State</strong></td>';
		echo '</tr>';
		
		for ($i = 0; $i < count($sessions); $i++) {
			if (strtolower($sessions[$i]['state']) != 'working') {
				echo '<tr>';
				echo '<td>' . $sessions[$i]['id'] . '</td>';
				echo '<td>' . $sessions[$i]['type'] . '</td>';
				echo '<td>' . $sessions[$i]['state'] . '</td>';
				echo '<td></td>';
				echo '</tr>';
			}
		}
		echo '</tbody>';
		echo '</table>';
	} else {
		echo 'No sessions found.';
	}
}


// Used for organization login
if ($action == 'getmailbox') {
	/* Do we have a session running? If not start one, else use it. */
	if (isset($_SESSION['rid'])) {
		$id = $_SESSION['rid'];
	} else {
		$session = $veeam->startSession();
		$id = $session['id'];
		$_SESSION['rid'] = $id;
	}
	
	$mailbox = $veeam->getMailbox($id);
	
	for ($i = 0; $i < count($mailbox['results']); $i++) {
	?>
	<script>
	/* Iteam search */
	$('#search-<?php echo $mailbox['results'][$i]['id']; ?>').keyup(function(e) {
		var searchText = $(this).val().toLowerCase();
		// Show only matching row, hide rest of them
		$.each($('#table-mailitems-<?php echo $mailbox['results'][$i]['id']; ?> tbody tr'), function(e) {
			if($(this).text().toLowerCase().indexOf(searchText) === -1)
			   $(this).hide();
			else
			   $(this).show();
		});
	});
	</script>
	<?php
	}
	
	if (count($mailbox) != '0') {
		echo '<table class="table table-bordered table-striped" id="table-mailboxes">';
		echo '<thead>';
		echo '<tr>';
		echo '<th>Name</th>';
		echo '<th>E-mail address</th>';
		echo '<th class="text-center settings">Items</th>';
		echo '</tr>';
		echo '</thead>';
		echo '<tbody>'; 
		
		for ($i = 0; $i < count($mailbox['results']); $i++) {
			echo '<tr>';
			echo '<td>' . $mailbox['results'][$i]['name'] . '</td>';
			echo '<td>' . $mailbox['results'][$i]['email'] . '</td>';
			echo '<td data-toggle="collapse" data-target="#items-'.$mailbox['results'][$i]['id'].'"><a href="#" name="link-restore" data-mid="' . $mailbox['results'][$i]['id'] . '" data-rid="' . $id . '">View items</a></td>';
			echo '</tr>';
			echo '<tr>';
			echo '<td colspan="4" class="zeroPadding"><div id="items-'.$mailbox['results'][$i]['id'].'" class="accordian-body collapse"><u><h3>Items available for restore:</h3></u>';
			echo '<input class="search" id="search-' . $mailbox['results'][$i]['id'] . '" placeholder="Search item" /><br /><br />';
			echo '<table class="table table-bordered table-striped table-padding" name="table-mailitems" id="table-mailitems-' . $mailbox['results'][$i]['id'] . '">';
			echo '<thead>';
			echo '<tr>';
			echo '<th class="mail-from">From</th>';
			echo '<th class="mail-subject">Subject</th>';
			echo '<th class="mail-received">Received</th>';
			echo '<th class="text-center settings mail-options">Options</th>';
			echo '</tr>';
			echo '<tbody>';
			echo '</tbody>';
			echo '</table>';
			echo '</div></td>';
			echo '</tr>';		
		}
		
		echo '</tbody>';
		echo '</table>';		
	} else {
		echo 'No mailboxes found.';
		
		unset($_SESSION['rid']); /* Prevent dead sessions */
	}
}


/* Other calls */
if ($action == 'createjob') {
	$veeam->createJob($id, $json);
}

if ($action == 'createorganization') {
	$veeam->createOrganization($json);
}

if ($action == 'createproxy') {
	$veeam->createProxy($json);
}

if ($action == 'createrepository') {
	$veeam->createRepository($json);
}

if ($action == 'changejobstate') {
	$veeam->changeJobState($id, $json);
}

if ($action == 'getrepo') {
	$repo = $veeam->getBackupRepository($id);
	
	echo json_encode($repo);
}

if ($action == 'listfolders') {
	$ids = preg_split('/\|/', $id);
	$mid = $ids[0]; 
	$rid = $ids[1];
	
	$folders = $veeam->getFolders($rid, $mid);
	echo $folders;
	/*if (count($folders) != '0') {
		for ($i = 0; $i < count($folders); $i++) {
			echo $folders[$i]['name'] . '#';	
		}
	}*/
} 

if ($action == 'listmailboxes') {
	$mailbox = $veeam->getOrganizationMailboxes($id);
	
	if (count($mailbox['results']) != '0') {
		for ($i = 0; $i < count($mailbox['results']); $i++) {
			echo $mailbox['results'][$i]['id'] . '|' . $mailbox['results'][$i]['email'] . '|' . $mailbox['results'][$i]['name'] . '#';	
		}
	}
}

if ($action == 'listrepositories') {
	$repos = $veeam->getBackupRepository($id);
	
	if (count($repos) != '0') {
		for ($i = 0; $i < count($repos); $i++) {
			echo $repos[$i]['id'] . '|' . $repos[$i]['name'] . '#';	
		}
	}
} 

if ($action == 'removejob') {
	$veeam->removeJob($id);
}

if ($action == 'removeorg') {
	$veeam->removeOrganization($id);
}

if ($action == 'removeproxy') {
	$veeam->removeProxy($id);
}

if ($action == 'removerepo') {
	$veeam->removeRepo($id);
}

if ($action == 'startjob') {
	$veeam->startJob($id);
}


/* Restore calls */
if ($action == 'exportitem') {
	$ids = preg_split('/\|/', $id);
	$mid = $ids[0]; 
	$rid = $ids[1];
	$iid = $ids[2];
	
	$veeam->exportItem($mid, $rid, $iid, $json);
}

if ($action == 'getitems') {
	$ids = preg_split('/\|/', $id);
	$mid = $ids[0]; 
	$rid = $ids[1];
	
	$items = $veeam->getItems($rid, $mid);
	
	echo json_encode($items);
}

if ($action == 'restoreoriginal' || $action == 'restoreto') {
	$ids = preg_split('/\|/', $id);
	$mid = $ids[0]; 
	$rid = $ids[1];
	$iid = $ids[2];
	
	$veeam->restoreItem($mid, $rid, $iid, $json);
}

if ($action == 'startrestore') {
	$items = $veeam->vexSessionHandler($id, $json);
	echo $items['id'];
}

if ($action == 'endrestore') {
	$veeam->vexSessionHandler($id, $json);
}