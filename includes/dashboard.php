<?php
/* Create dashboard stats */
$org = $veeam->getOrganizations();
$jobs = $veeam->getJobs();
$proxies = $veeam->getProxies();
$repos = $veeam->getBackupRepositories();
$licensetotal = 0;
$newlicensetotal = 0;

for ($i = 0; $i < count($org); $i++) {
  $license = $veeam->getLicenseInfo($org[$i]['id']);
  $licensetotal += $license['licensedUsers'];
  $newlicensetotal += $license['newUsers'];
}
?>
<div class="container-fluid">
    <aside id="sidebar">
        <div class="logo-container"><i class="logo fa fa-cogs"></i></div>
            <div class="separator"></div>
            <menu class="menu-segment">
            <ul class="menu">
                <li id="jobs" data-call="jobs"><i class="fa fa-calendar"></i> Jobs</li>
                <li id="organizations" data-call="organizations"><i class="fa fa-building"></i> Organizations</li>
                <li id="proxies" data-call="proxies"><i class="fa fa-server"></i> Proxies</li>
                <li id="repositories" data-call="repositories"><i class="fa fa-database"></i> Repositories</li>
                <li id="licensing" data-call="licensing"><i class="fa fa-file-alt"></i> Licensing</li>
                <li id="history" data-call="history"><i class="fa fa-file-alt"></i> History</li>
            </ul>
            </menu>
            <div class="separator"></div>
            <div class="bottom-padding"></div>
        </div>
    </aside>
</div>
<main id="main">
    <h1>Infrastructure overview</h1>
    <br />
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
            <a href="#" id="organizationspanel" onClick="return false;">
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
            <a href="#" id="jobspanel" onClick="return false;">
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
            <a href="#" id="proxiespanel" onClick="return false;">
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
            <a href="#" id="repositoriespanel" onClick="return false;">
            <div class="panel-footer">
              <span class="pull-left">Overview</span>
              <span class="pull-right"><i class="fa fa-arrow-circle-right"></i></span>
              <div class="clearfix"></div>
            </div>
            </a>
          </div>
        </div>
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
            <a href="#" id="licensingpanel" onClick="return false;">
            <div class="panel-footer">
              <span class="pull-left">Overview</span>
              <span class="pull-right"><i class="fa fa-arrow-circle-right"></i></span>
              <div class="clearfix"></div>
            </div>
            </a>
          </div>
        </div>
    </div>
</main>