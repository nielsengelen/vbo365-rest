<?php
define('AJAX_REQUEST', isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest');
if (!AJAX_REQUEST) { 
	header('Location: ../index.php'); 
}

require_once('../config.php');
require_once('../veeam.class.php');

if (isset($_GET['id'])) { $id = $_GET['id']; }

session_start();

$veeam = new VBO($host, $port);
$veeam->setToken($_SESSION['token']);

$jobs = $veeam->getJobs($id);
$proxies = $veeam->getProxies();

echo '<h1>Veeam Backup for Office 365 RESTful API demo</h1>';
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