<?php
/* Create dashboard stats */
$org = $veeam->getOrganizations();
$jobs = $veeam->getJobs();
$proxies = $veeam->getProxies();
$repos = $veeam->getBackupRepositories();
try {
	$objectrepos = $veeam->getObjectStorageRepositories();
} catch (Exception $e) {
	$e->getMessage();
}
$licensetotal = 0;
$newlicensetotal = 0;

for ($i = 0; $i < count($org); $i++) {
  $license = $veeam->getLicenseInfo($org[$i]['id']);
  $licensetotal += $license['licensedUsers'];
  $newlicensetotal += $license['newUsers'];
}
?>
<div class="main-container">
	<h1>Dashboard</h1>
	<div class="row">
		<div class="col-lg-3 col-md-6">
		  <div class="panel panel-primary">
			<div class="panel-heading">
			  <div class="row">
				<div class="col-xs-4">
				  <i class="fa fa-building fa-4x"></i>
				</div>
				<div class="col-xs-8 text-left">
				  <div class="medium">&nbsp;<?php echo count($org); ?> organizations</div>
				</div>
			  </div>
			</div>
			<a href="#" class="dash" data-call="organizations" onClick="return false;">
			<div class="panel-footer">
			  <span class="pull-left">Overview</span>
			  <span class="pull-right"><i class="fa fa-arrow-circle-right"></i></span>
			  <div class="clearfix"></div>
			</div>
			</a>
		  </div>
		</div>
		<div class="col-lg-3 col-md-6">
		  <div class="panel panel-green">
			<div class="panel-heading">
			  <div class="row">
				<div class="col-xs-4">
				  <i class="fa fa-calendar fa-4x"></i>
				</div>
				<div class="col-xs-8 text-left">
				  <div class="medium">&nbsp;<?php echo count($jobs); ?> backup jobs</div>
				</div>
			  </div>
			</div>
			<a href="#" class="dash" data-call="jobs" onClick="return false;">
			<div class="panel-footer">
			  <span class="pull-left">Overview</span>
			  <span class="pull-right"><i class="fa fa-arrow-circle-right"></i></span>
			  <div class="clearfix"></div>
			</div>
			</a>
		  </div>
		</div>
		<div class="col-lg-3 col-md-6">
		  <div class="panel panel-yellow">
			<div class="panel-heading">
			  <div class="row">
				<div class="col-xs-4">
				  <i class="fa fa-server fa-4x"></i>
				</div>
				<div class="col-xs-8 text-left">
				  <div class="medium">&nbsp;<?php echo count($proxies); ?> proxies</div>
				</div>
			  </div>
			</div>
			<a href="#" class="dash" data-call="proxies" onClick="return false;">
			<div class="panel-footer">
			  <span class="pull-left">Overview</span>
			  <span class="pull-right"><i class="fa fa-arrow-circle-right"></i></span>
			  <div class="clearfix"></div>
			</div>
			</a>
		  </div>
		</div>
	</div>
	<div class="row">
		<div class="col-lg-3 col-md-6">
		  <div class="panel panel-lightgreen">
			<div class="panel-heading">
			  <div class="row">
				<div class="col-xs-4">
				  <i class="fa fa-database fa-4x"></i>
				</div>
				<div class="col-xs-8 text-left">
				  <div class="medium">&nbsp;<?php echo count($repos); ?> repositories</div>
				</div>
			  </div>
			</div>
			<a href="#" class="dash" data-call="repositories" onClick="return false;">
			<div class="panel-footer">
			  <span class="pull-left">Overview</span>
			  <span class="pull-right"><i class="fa fa-arrow-circle-right"></i></span>
			  <div class="clearfix"></div>
			</div>
			</a>
		  </div>
		</div>
		<?php
		if (isset($objectrepos)) {
		?>
		<div class="col-lg-3 col-md-6">
		  <div class="panel panel-orange">
			<div class="panel-heading">
			  <div class="row">
				<div class="col-xs-4">
				  <i class="fa fa-cloud fa-4x"></i>
				</div>
				<div class="col-xs-8 text-left">
				  <div class="medium">&nbsp;<?php echo count($objectrepos); ?> object storage repositories</div>
				</div>
			  </div>
			</div>
			<a href="#" class="dash" data-call="repositories" onClick="return false;">
			<div class="panel-footer">
			  <span class="pull-left">Overview</span>
			  <span class="pull-right"><i class="fa fa-arrow-circle-right"></i></span>
			  <div class="clearfix"></div>
			</div>
			</a>
		  </div>
		</div>
		<?php
		}
		?>
		<div class="col-lg-3 col-md-6">
		  <div class="panel panel-gray">
			<div class="panel-heading">
			  <div class="row">
				<div class="col-xs-4">
				  <i class="fa fa-file-alt fa-4x"></i>
				</div>
				<div class="col-xs-8 text-left">
				  <div class="medium">&nbsp;<?php echo $licensetotal; ?> licenses used<br />&nbsp;<?php echo $newlicensetotal; ?> extra licenses</div>
				</div>
			  </div>
			</div>
			<a href="#" class="dash" data-call="licensing" onClick="return false;">
			<div class="panel-footer">
			  <span class="pull-left">Overview</span>
			  <span class="pull-right"><i class="fa fa-arrow-circle-right"></i></span>
			  <div class="clearfix"></div>
			</div>
			</a>
		  </div>
		</div>
	</div>
</div>
<script>
$('a.dash').click(function(e) {
	$('#main').load('includes/' + $(this).data('call') + '.php');
});
</script>