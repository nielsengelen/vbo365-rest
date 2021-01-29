<?php
if (!session_id()) {
	session_start();
	session_regenerate_id(true);
}

error_reporting(E_ALL || E_STRICT);
set_time_limit(0);

require_once('config.php');
require_once('veeam.class.php');

if (empty($host) || empty($port) || empty($version)) {
    exit('Modify the configuration file first and configure the Veeam Backup for Microsoft Office 365 host, port and RESTful API version settings.');
}

if (!preg_match('/v[4-5]/', $version)) {
	exit('Invalid API version found. Modify the configuration file and configure the Veeam Backup for Microsoft Office 365 RESTful API version setting. Only version 4 and 5 are supported.');
}

if (isset($_POST['logout'])) {
	$veeam = new VBO($host, $port, $version);
    $veeam->logout();
} else {
    if (!empty($_POST['user'])) { $user = $_POST['user']; }
    if (!empty($_POST['pass'])) { $pass = $_POST['pass']; }
	if (!empty($_POST['authtype'])) { $authtype = $_POST['authtype']; }
	if (!empty($_POST['assertion'])) { $assertion = $_POST['assertion']; }
	if (!empty($_POST['applicationid'])) { $applicationid = $_POST['applicationid']; }
	if (!empty($_POST['tenantid'])) { $tenantid = $_POST['tenantid']; }

    if (isset($user) && isset($pass)) {
		$veeam = new VBO($host, $port, $version);
		$login = $veeam->login($user, $pass);
		
        $_SESSION['refreshtoken'] = $veeam->getRefreshToken();
        $_SESSION['token'] = $veeam->getToken();
		$_SESSION['authtype'] = $authtype;
        $_SESSION['user'] = $user;
    } else if (isset($tenantid) && isset($assertion)) {
		$veeam = new VBO($host, $port, $version);
		$login = $veeam->MFALogin($tenantid, $assertion);
		
        $_SESSION['refreshtoken'] = $veeam->getRefreshToken();
        $_SESSION['token'] = $veeam->getToken();
		$_SESSION['applicationid'] = $applicationid;
		$_SESSION['tenantid'] = $tenantid;
		$_SESSION['authtype'] = $authtype;
		$_SESSION['user'] = str_replace('.onmicrosoft.com', '', $tenantid);
	} else {
		if (!empty($_POST)) {
			$login = 1;
		}
	}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo $title; ?></title>
    <base href="/" />
    <link rel="shortcut icon" href="favicon.ico" />
    <link rel="stylesheet" type="text/css" href="css/bootstrap.min.css" />
    <link rel="stylesheet" type="text/css" href="css/fontawesome.min.css" />
    <link rel="stylesheet" type="text/css" href="css/style.css" />
	<link rel="stylesheet" type="text/css" href="css/sweetalert2.min.css" />	
    <script src="js/jquery.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
	<script src="js/clipboard.min.js"></script>
    <script src="js/fontawesome.min.js"></script>
    <script src="js/filesize.min.js"></script>
	<script src="js/jquery.redirect.js"></script>
    <script src="js/moment.min.js"></script>
	<script src="js/sweetalert2.all.min.js"></script>
</head>
<body>
<?php
if (file_exists('setup.php')) {
	?>
	<script>
	Swal.fire({
		icon: 'error',
		title: 'Error',
		allowOutsideClick: false,
		showConfirmButton: false,
		text: 'Setup file is still available within the installation folder. You must remove this file in order to continue'
	});
	</script>
	<?php
	die();
}

if (isset($_SESSION['token'])) {
	$veeam = new VBO($host, $port, $version);
    $veeam->setToken($_SESSION['token']);
	
	if (isset($_SESSION['user'])) {
		$user = $_SESSION['user'];
		$check = filter_var($user, FILTER_VALIDATE_EMAIL);
	} else {
		empty($user);
	}
	
	if (isset($_SESSION['authtype'])) {
		$authtype = $_SESSION['authtype'];
	}
	
	if (isset($user) && strtolower($authtype) != 'mfa' && $check === false && strtolower($administrator) == 'yes') {
	?>
	<nav class="navbar navbar-inverse navbar-static-top">
		<div class="container-fluid">
			<ul class="nav navbar-header">
			  <li><a class="navbar-brand navbar-logo" href="/"><img src="images/logo.svg" alt="Veeam Backup for Microsoft Office 365" class="logo"></a></li>
			</ul>
			<ul class="nav navbar-nav" id="nav">
			  <?php
			  if (!isset($_SESSION['rtype'])) {
			  ?>
			  <li><a href="exchange">Exchange</a></li>
			  <li><a href="onedrive">OneDrive</a></li>
			  <li><a href="sharepoint">SharePoint</a></li>
			  <?php
			  if ($version === 'v5') {
				echo '<li><a href="teams">Teams</a></li>';
			  }
			  ?>
			  <?php
			  } else {
				$rtype = $_SESSION['rtype'];
				
				if ($rtype == 'vex') {
					echo '<li><a href="exchange">Exchange</a></li>';
				} else if ($rtype == 'veo') {
					echo '<li><a href="onedrive">OneDrive</a></li>';
				} else if ($rtype == 'vesp') {
					echo '<li><a href="sharepoint">SharePoint</a></li>';
				} else {
					echo '<li><a href="teams">Teams</a></li>';
				}
			  }
			  ?>
			</ul>
			<ul class="nav navbar-nav navbar-right">
			  <li><a href="#" onClick="return false;"><span class="fa fa-user"></span> Welcome <i><?php echo $user; ?></i> !</a></li>
			  <li id="logout"><a href="#" onClick="return false;"><span class="fa fa-sign-out-alt"></span> Logout</a></li>
			</ul>
		</div>
	</nav>
	<div class="container-fluid">
		<div id="sidebar">
			<div class="logo-container"><i class="logo fa fa-cogs"></i></div>
			<div class="separator"></div>
			<menu class="menu-segment" id="menu">
				<ul class="menu">
					<li class="active"><a href="/"><i class="fa fa-tachometer-alt"></i> Dashboard</a></li>
					<li id="organizations"><a href="#" onclick="loadPage('organizations');"><i class="fa fa-building"></i> Organizations</a></li>
					<li id="jobs"><a href="#" onclick="loadPage('jobs');"><i class="fa fa-calendar"></i> Jobs</a></li>
					<li id="proxies"><a href="#" onclick="loadPage('proxies');"><i class="fa fa-server"></i> Proxies</a></li>
					<li id="repositories"><a href="#" onclick="loadPage('repositories');"><i class="fa fa-database"></i> Repositories</a></li>
					<li id="licensing"><a href="#" onclick="loadPage('licensing');"><i class="fa fa-file-alt"></i> Licensing</a></li>
					<li id="history"><a href="#" onclick="loadPage('history');"><i class="fa fa-history"></i> History</a></li>
				</ul>
			</menu>
			<div class="separator"></div>
			<div class="bottom-padding"></div>
		</div>
		<div id="main">
			<?php
			$licensetotal = 0;
			$newlicensetotal = 0;
			$org = $veeam->getOrganizations();
			$jobs = $veeam->getJobs();
			$proxies = $veeam->getProxies();
			$repos = $veeam->getBackupRepositories();
			$sessions = $veeam->getSessions();

			try {
				$objectrepos = $veeam->getObjectStorageRepositories();
			} catch (Exception $e) {
				$e->getMessage();
			}
			
			for ($i = 0; $i < count($org); $i++) {
			  $license = $veeam->getLicenseInfo($org[$i]['id']);
			  $licensetotal += $license['licensedUsers'];
			  $newlicensetotal += $license['newUsers'];
			}
			?>
			<div class="main-container">
				<h1>Infrastructure Dashboard</h1>
				<div class="row panel-row">
					<div class="col-lg-4 col-md-4">
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
						<a href="#" class="dash" data-call="organizations" onClick="loadPage('organizations');">
						<div class="panel-footer">
						  <span class="pull-left">Overview</span>
						  <span class="pull-right"><i class="fa fa-arrow-circle-right"></i></span>
						  <div class="clearfix"></div>
						</div>
						</a>
					  </div>
					</div>
					<div class="col-lg-4 col-md-4">
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
						<a href="#" class="dash" data-call="jobs" onClick="loadPage('jobs');">
						<div class="panel-footer">
						  <span class="pull-left">Overview</span>
						  <span class="pull-right"><i class="fa fa-arrow-circle-right"></i></span>
						  <div class="clearfix"></div>
						</div>
						</a>
					  </div>
					</div>
					<div class="col-lg-4 col-md-4">
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
						<a href="#" class="dash" data-call="proxies" onClick="loadPage('proxies');">
						<div class="panel-footer">
						  <span class="pull-left">Overview</span>
						  <span class="pull-right"><i class="fa fa-arrow-circle-right"></i></span>
						  <div class="clearfix"></div>
						</div>
						</a>
					  </div>
					</div>
				</div>
				<div class="row panel-row">
					<div class="col-lg-4 col-md-4">
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
						<a href="#" class="dash" data-call="repositories" onClick="loadPage('repositories');">
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
					<div class="col-lg-4 col-md-4">
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
						<a href="#" class="dash" data-call="repositories" onClick="loadPage('repositories');">
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
					<div class="col-lg-4 col-md-4">
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
						<a href="#" class="dash" data-call="licensing" onClick="loadPage('licensing');">
						<div class="panel-footer">
						  <span class="pull-left">Overview</span>
						  <span class="pull-right"><i class="fa fa-arrow-circle-right"></i></span>
						  <div class="clearfix"></div>
						</div>
						</a>
					  </div>
					</div>
					<?php
					if (!isset($objectrepos)) {
					?>
					<div class="col-lg-4 col-md-4">
					  <div class="panel panel-red">
						<div class="panel-heading">
						  <div class="row">
							<div class="col-xs-4">
							  <i class="fa fa-history fa-4x"></i>
							</div>
							<div class="col-xs-8 text-left">
							  <div class="medium">
							  <?php 
							  echo count($sessions['results']);
							  
							  if (count($sessions['results']) >= $limit) {
							  echo '+';
							  }
							  ?> 
							  sessions
							  </div>
							</div>
						  </div>
						</div>
						<a href="#" class="dash" data-call="history" onClick="loadPage('history');">
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
				</div>
				<?php
				if (isset($objectrepos)) {
				?>
				<div class="row panel-row">
					<div class="col-lg-4 col-md-4">
					  <div class="panel panel-red">
						<div class="panel-heading">
						  <div class="row">
							<div class="col-xs-4">
							  <i class="fa fa-history fa-4x"></i>
							</div>
							<div class="col-xs-8 text-left">
							  <div class="medium">
							  <?php 
							  echo count($sessions['results']);
							  
							  if (count($sessions['results']) >= $limit) {
							  echo '+';
							  }
							  ?> 
							  sessions
							  </div>
							</div>
						  </div>
						</div>
						<a href="#" class="dash" data-call="history" onClick="loadPage('history');">
						<div class="panel-footer">
						  <span class="pull-left">Overview</span>
						  <span class="pull-right"><i class="fa fa-arrow-circle-right"></i></span>
						  <div class="clearfix"></div>
						</div>
						</a>
					  </div>
					</div>
				</div>
				<?php
				}
				?>
			</div>
		</div>
	</div>
	<script>		
	$('#logout').click(function(e) {	
		const swalWithBootstrapButtons = Swal.mixin({
		  customClass: {
			  confirmButton: 'btn btn-success btn-margin',
			  cancelButton: 'btn btn-danger'
		  },
		  buttonsStyling: false,
		});
		
		swalWithBootstrapButtons.fire({
			icon: 'question',
			title: 'Logout',
			text: 'You are about to log out. Are you sure you want to continue?',
			showCancelButton: true,
			confirmButtonText: 'Logout',
			cancelButtonText: 'Cancel',
		}).then(function(result) {
			if (result.isConfirmed) {
				$.redirect('index.php', {'logout' : true}, 'POST');
			  } else {
				return;
			}
		})
	});
		
	$('#menu a').click(function(e) {
		$('#menu').find('li.active').removeClass('active');
		$(this).parent('li').addClass('active');
	});
	
	$('.dash').click(function(e) {
		var call = $(this).data('call');
		
		$('#menu').find('li.active').removeClass('active');
		$('#' + call).addClass('active');
	});
	
	function loadPage(call) {
		$('#main').load('includes/' + call + '.php');
	}
	</script>
	<?php
	} else {
		header('Location: /exchange');
	}
} else {
	unset($_SESSION);
	?>
	<section class="login-block">
		<div class="container login-container">
			<div class="row">
				<div class="col-md-4 login-sec">
					<h2 class="text-center">Welcome</h2>
					<div id="div-forms">
						<div class="form-group" id="basic-login">
							<form id="basic_login" method="POST">
								<input type="hidden" name="authtype" value="basic">
								<label for="username" class="text-uppercase">Username:</label>
								<input type="text" class="input-loginform form-control" name="user" autofocus><span class="fa fa-user fa-2x icon"></span><br>
								<label for="password" class="text-uppercase">Password:</label>
								<input type="password" class="input-loginform form-control" name="pass"><span class="fa fa-lock fa-2x icon"></span><br>
								<div class="form-check text-center">
									<button type="submit" class="btn btn-login">Login</button>
								</div>
								<div class="divider-line"><span>OR</span></div>
								<div class="form-check text-center">
									<button type="button" class="btn btn-link btn-link-login" id="btn_mfa_login">Use MFA login</button>
								</div>
							</form>
						</div>
						<div class="form-group" id="mfa-login" style="display:none;">
							<div class="wizard">
								<div class="tab-content">
									<div class="tab-pane active fade in" role="tabpanel" id="step1">
										<label for="tenant" class="text-uppercase">Tenant ID:</label>
										<input type="text" class="input-loginform form-control" id="tenantid" name="tenant" placeholder="company.onmicrosoft.com" autofocus><span class="fa fa-user fa-2x icon"></span><br>
										<label for="application" class="text-uppercase">Application ID:</label>
										<input type="text" class="input-loginform form-control" id="applicationid" name="application"><span class="fa fa-desktop fa-2x icon"></span>
										<div class="alert alert-info text-left">You can find this number in the application settings of your Microsoft Azure Active Directory, as described in <a href="https://docs.microsoft.com/en-us/azure/active-directory/develop/howto-create-service-principal-portal" target="_blank">this Microsoft article</a>.</div>
										<div class="form-check text-center">
											<button type="button" class="btn btn-next form-check text-center">Next</button>
										</div>
										<div class="divider-line"><span>OR</span></div>
										<div class="form-check text-center">
											<button type="button" class="btn btn-link btn-link-login" id="btn_basic_login">Use basic login</button>
										</div>
									</div>
									<div class="tab-pane fade in" role="tabpanel" id="step2">
										<span>To sign in, open the page <a href="https://microsoft.com/devicelogin" target="_blank">https://microsoft.com/devicelogin</a> and enter the below code to authenticate.</span><br><br>
										<input type="text" class="form-control" id="user-code" readonly>
										<div class="form-check text-center">
											<button type="button" class="btn form-check" id="btn-copy" data-clipboard-target="#user-code" data-placement="right">Copy to clipboard</button>
										</div>
										<br>
										<div id="polling"><i class="fas fa-info-circle" style="color:#4997C7;"></i> Waiting for user authentication...</div><br>
										<script>
										var clipboard = new ClipboardJS('#btn-copy');
										
										function hideTooltip() {
										  setTimeout(function() {
											$('#btn-copy').tooltip('hide');
										  }, 1000);
										}
										function setTooltip(message) {
										  $('#btn-copy').tooltip('hide')
											.attr('data-original-title', message)
											.tooltip('show');
										}
										
										clipboard.on('success', function(e) {
										  setTooltip('Copied');
										  hideTooltip();
										});
										clipboard.on('error', function(e) {
										  setTooltip('Failed');
										  hideTooltip();
										});
										</script>
										<div class="form-check text-center">
											<button type="button" class="btn btn-prev form-check text-center">Back</button>
											<button type="button" class="btn btn-login btn-mfa-login btn-next" disabled>Login</button>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
					<div class="text-center">
					<?php
					if (isset($login)) {
						if ($login == 0) {
							echo '<br><span class="text-warning" id="login-state">The provided credentials are incorrect</span>';
						} else if ($login == 1) {
							echo '<br><span class="text-warning" id="login-state">No credentials provided</span>';
						} else {
							echo '<br><span class="text-warning" id="login-state">' . $login . '</span>';
						}
					}
					?>
					</div>
					<div class="divider"></div>
				</div>
				<div class="col-md-8 banner-sec"></div>
			</div>
		</div>
	</section>
	<script>
	var modalAnimateTime = 300;
	var divForms = $('#div-forms');
	var formBasic = $('#basic-login');
    var formMFA = $('#mfa-login');

	$('#btn_basic_login').click( function(e) { modalAnimate(formMFA, formBasic) });
	$('#btn_mfa_login').click( function(e) { modalAnimate(formBasic, formMFA) });
	
	function modalAnimate(oldForm, newForm) {
        var oldH = oldForm.height();
        var newH = newForm.height();
		
		$('#login-state').empty();
        divForms.css('height', oldH);
        oldForm.fadeToggle(modalAnimateTime, function(e) {
            divForms.animate({height: newH}, modalAnimateTime, function(e) {
                newForm.fadeToggle(modalAnimateTime);
            });
        });
    }
		
	$(document).ready(function(e) {
		var callInverval;

		function getStoredValue(key) {
			if (sessionStorage) {
				return sessionStorage.getItem(key);
			} else {
				return $.cookies.get(key);
			}
		}

		function storeValue(key, value) {
			if (sessionStorage) {
				sessionStorage.setItem(key, value);
			} else {
				$.cookies.set(key, value);
			}
		}

		const callApi = (clientid, tenantid, devicecode) => {
		  return new Promise((resolve, reject) => {
			setTimeout(() => {
				$.post('microsoft.php', {'action' : 'gettoken', 'clientid' : clientid, 'tenantid' : tenantid, 'devicecode' : devicecode}).done(function(data) {
					resolve(JSON.parse(data));
				});
			}, 1000);
		  });
		}

		const checkAuthenticated = (clientid, tenantid, devicecode, interval) => {  
		  callInverval = setInterval(async() => {
			const response = await callApi(clientid, tenantid, devicecode);

			if (response['error'] === 'authorization_pending') {
				$('#polling').hide().html('<i class="fas fa-info-circle" style="color:#4997C7;"></i> Waiting for user authentication...').fadeIn('slow');
				
				storeValue('assertion', 'error');
			} else if (response['error'] === 'authorization_declined') {
				$('#polling').hide().html('<i class="fas fa-info-circle" style="color:red"></i> The authorization request has been denied. Hit the refresh button and try again.').fadeIn('slow');
				
				storeValue('assertion', 'error');
			} else if (response['error'] === 'expired_token') {
				$('#polling').hide().html('<i class="fas fa-exclamation-circle" style="color:red"></i> Token expired. Hit the refresh button and try again.').fadeIn('slow');
				
				clearInterval(callInverval);
				storeValue('assertion', 'error');
			} else if (response['error'] === 'invalid_grant') {
				$('#polling').hide().html('<i class="fas fa-exclamation-circle" style="color:red"></i> Authorization code was already redeemed, retry with a new valid code.').fadeIn('slow');
				
				clearInterval(callInverval);
				storeValue('assertion', 'error');
			} else if (typeof response['token_type'] !== 'undefined' && response['token_type'].toLowerCase() === 'bearer') {
				$('#polling').hide().html('<i class="fas fa-check-circle" style="color:green"></i> You are authenticated against Microsoft Office 365. Click <strong>login</strong> to continue.').fadeIn('slow');
				$('.btn-mfa-login').prop('disabled', false);
				
				storeValue('assertion', JSON.stringify(response));
				clearInterval(callInverval);
			} else {
				$('#polling').hide().html('Unknown error. In order to continue, restart the login procedure.').fadeIn('slow');
				
				clearInterval(callInverval);
				storeValue('assertion', 'error');
			}
		  }, interval);
		}

		$('.btn-next').click(function(e) {
			var step = $(this).parents('.tab-pane').attr('id');
			var step1 = $('#step1');
			var step2 = $('#step2');
			var applicationid = $('#applicationid').val();
			var tenantid = $('#tenantid').val();

			if (typeof tenantid === undefined || !tenantid) {
				Swal.fire({
					icon: 'error',
					title: 'Error',
					allowOutsideClick: true,
					text: 'Provide your Tenant ID and try again'
				});
				
				return;
			}
			
			if (typeof applicationid === undefined || !applicationid) {
				Swal.fire({
					icon: 'error',
					title: 'Error',
					allowOutsideClick: true,
					text: 'Provide your Application ID and try again'
				});
				
				return;
			}

			if (step === 'step1') {
				$.post('microsoft.php', {'action' : 'getdevicecode', 'clientid' : applicationid, 'tenantid' : tenantid}).done(function(data) {
					var response = JSON.parse(data);
					var devicecode = response['device_code'];
					var interval = response['interval'] * 1000 | 0;
					var usercode = response['user_code'];

					$('#user-code').val(usercode);
					storeValue('devicecode', devicecode);
					storeValue('assertion', 'error');
					checkAuthenticated(applicationid, tenantid, devicecode, interval);
					modalAnimate(step1, step2);
				});
			} else if (step === 'step2') {
				var assertion = getStoredValue('assertion');
				
				if (assertion === 'error') {
					Swal.fire({
						icon: 'error',
						title: 'Error',
						allowOutsideClick: true,
						showConfirmButton: true,
						text: 'You are not authenticated against Microsoft Office 365. Follow the required steps and try again'
					});
					
					return;
				} else {
					clearInterval(callInverval);

					$.redirect('index.php', {'authtype' : 'mfa', 'applicationid' : applicationid, 'tenantid' : tenantid, 'assertion' : assertion}, 'POST');
				}
			}
		});
		
		$('.btn-prev').click(function(e) {
			var step1 = $('#step1');
			var step2 = $('#step2');
			
			clearInterval(callInverval);
			modalAnimate(step2, step1);
		});
	});
	</script>
	<?php
}
?>
</body>
</html>