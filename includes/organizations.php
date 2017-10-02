<?php
/**
 * Organizations page HTML design
 */

define('AJAX_REQUEST', isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest');
if (!AJAX_REQUEST) { 
	header('Location: ../index.php'); 
}

require_once('../config.php');
require_once('../veeam.class.php');

session_start();

$veeam = new VBO($host, $port);
$veeam->setToken($_SESSION['token']);

$org = $veeam->getOrganizations();

echo '<h1>Veeam Backup for Office 365 RESTful API demo</h1>';
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
		echo '<td><button class="btn btn-danger" id="btn-delete" data-call="removeorg" data-rcall="organizations" data-name="' . $org[$i]['name'] . '" data-cid="' . $org[$i]['id'] . '" title="Delete organization"><i class="fa fa-trash-o" aria-hidden="true"></i></button></a></td>';	
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

$('#job-backupall').on('change', function(e) {
	$('#job-mailboxes').addClass('hide'); 
});

$('#org-serverusessl').on('change', function(e) {
	if (this.checked) {
		$('#org-serverskipca').prop('disabled', false);
		$('#org-serverskipcn').prop('disabled', false);
		$('#org-serverskiprc').prop('disabled', false);
	} else {
		$('#org-serverskipca').prop('disabled', true);
		$('#org-serverskipcn').prop('disabled', true);
		$('#org-serverskiprc').prop('disabled', true);
	}
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
					<input type="checkbox" id="org-serverskipca" disabled> Skip certificate trusted authority verification<br />
					<input type="checkbox" id="org-serverskipcn" disabled> Skip certificate common name verification<br />
					<input type="checkbox" id="org-serverskiprc" disabled> Skip revocation check<br />
					Username: <input type="text" class="form-control" id="org-user-local" placeholder="DOMAIN\username"></input>
					Password: <input type="password" class="form-control" id="org-pass-local"></input>
					<input type="checkbox" id="org-grant" checked> Grant impersonation to this user<br />
					<input type="checkbox" id="org-policy" checked> Configure throttling policy
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