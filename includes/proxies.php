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

$proxies = $veeam->getProxies();

echo '<h1>Veeam Backup for Office 365 RESTful API demo</h1>';
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
		echo '<td><button class="btn btn-danger" id="btn-delete" data-call="removeproxy" data-rcall="proxies" data-name="' . $proxies[$i]['hostName'] . '" data-cid="' . $proxies[$i]['id'] . '" title="Delete proxy"><i class="fa fa-trash-o" aria-hidden="true"></i></button></a></td>';	
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