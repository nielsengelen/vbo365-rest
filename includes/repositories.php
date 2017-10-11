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

$repos = $veeam->getBackupRepositories();

echo '<h1>Veeam Backup for Office 365 RESTful API demo</h1>';
echo '<div id="infobox" class="text-center"></div>';
echo '<span><a href="#wizard" role="button" class="btn btn-default" data-toggle="modal">Create repository</a></span><br /><br />';

if (count($repos) != '0') {
	$proxies = $veeam->getProxies();
	
	echo '<table class="table table-bordered table-striped" id="table-proxies">';
	echo '<thead>';
	echo '<tr>';
	echo '<th>Name</th>';
	echo '<th>Capacity</th>';
	echo '<th>Description</th>';
	echo '<th>Options</th>';
	echo '</tr>';
	echo '</thead>';
	echo '<tbody>'; 
	for ($i = 0; $i < count($repos); $i++) {
		echo '<tr>';
		echo '<td>' . $repos[$i]['name'] . '</td>';
		echo '<td>' . round($repos[$i]['capacity']/1024/1024/1024, 1) . ' GB (' . round($repos[$i]['freeSpace']/1024/1024/1024, 1)  . ' GB free)</td>';
		echo '<td>' . $repos[$i]['description'] . '</td>';
		echo '<td><button class="btn btn-danger" id="btn-delete" data-call="removerepo" data-rcall="repositories" data-name="' . $repos[$i]['name'] . '" data-cid="' . $repos[$i]['id'] . '" title="Delete repository"><i class="fa fa-trash-o" aria-hidden="true"></i></button></a></td>';	
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