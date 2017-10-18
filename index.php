<?php
error_reporting(E_ALL || E_STRICT);

require_once('config.php');
require_once('veeam.class.php');

session_start();

if (empty($host) || empty($port)) {
	exit('Please modify the configuration file first and configure the Veeam Backup for Office 365 host and port settings.');
}

$veeam = new VBO($host, $port);

if (isset($_SESSION['token'])) {
	$veeam->setToken($_SESSION['token']);
}

if (isset($_POST['logout'])) {
	if (isset($_SESSION['rid'])) {
		$veeam->endSession($_SESSION['rid']);
	}
	
	$veeam->logout();
} else {
	if (!empty($_POST['user'])) { $user = $_POST['user']; }
	if (!empty($_POST['pass'])) { $pass = $_POST['pass']; }

	if (isset($user) && isset($pass)) {
		$login = $veeam->login($user, $pass);

		$_SESSION['refreshtoken'] = $veeam->getRefreshToken();
		$_SESSION['token'] = $veeam->getToken();
		$_SESSION['user'] = $user;
	} else {
		if (isset($_SESSION['refreshtoken'])) {
			$veeam->refreshToken($_SESSION['refreshtoken']);
		}
	}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">	
	<title>Veeam Backup for Office 365 RESTful API demo</title>
	<link rel="shortcut icon" href="images/favicon.ico" />
	<link rel="stylesheet" type="text/css" href="vendor/twbs/bootstrap/dist/css/bootstrap.min.css" />
	<link rel="stylesheet" href="css/font-awesome.min.css" media="screen" />
	<link rel="stylesheet" href="css/style.css" media="screen" />
    <script src="vendor/components/jquery/jquery.min.js"></script>
	<script src="js/jquery.backstretch.min.js"></script>
    <script src="vendor/twbs/bootstrap/dist/js/bootstrap.min.js"></script>
	<script src="js/bootbox.min.js"></script>
	<script src="js/wizard.js"></script>
</head>
<body>
<?php
if (isset($_SESSION['token'])) {
	/* 'Hack' to see if we are logged in as full admin or end user by email check on the user stored in the session */
	$user = $_SESSION['user'];
	$check = filter_var($user, FILTER_VALIDATE_EMAIL);
	
	if ($check === false) {
		$org = $veeam->getOrganizations();
	}
?>
<header class="hdr">
	<a href="index.php"><img src="images/logo.svg" alt="Veeam Backup for Office 365" class="headerlogo" /></a>
</header>
<nav id="menu">
	<ul>		
		<div id="menusection">
		<?php 
		echo '<li class="divider"></li>';
		
		if ($check === false) {
			echo '<strong>Organizations:</strong><br />';
			for ($i = 0; $i < count($org); $i++) {
				echo '<li id="' . $org[$i]['id'] . '" data-call="mailboxes">' . $org[$i]['name'] . '</li>';
			}
			echo '<br />';
			echo '<li class="divider"></li>';
			echo '<strong>Configuration:</strong><br />';
			echo '<li id="jobs" data-call="jobs"><i class="fa fa-calendar"></i> Jobs</li>';
			echo '<li class="divider"></li>';
			echo '<strong>Infrastructure:</strong>';
			echo '<li id="organizations" data-call="organizations"><i class="fa fa-building"></i> Organizations</li>';
			echo '<li id="proxies" data-call="proxies"><i class="fa fa-server"></i> Proxies</li>';
			echo '<li id="repositories" data-call="repositories"><i class="fa fa-database"></i> Repositories</li>';
			echo '<li id="sessions" data-call="sessions"><i class="fa fa-file-text"></i> Restore sessions</li>';
		} else {
			echo '<li id="mailbox" data-call="mailbox"><i class="fa fa-envelope"></i> Mailbox</li>';
		}
		
		echo '<li class="divider"></li>';
		echo '<li id="logout"><i class="fa fa-sign-out"></i> Logout</li>';
		echo '<li class="divider"></li>';
		echo '<li id="about"><i class="fa fa-question-circle-o"></i> About</li>';
		?>
		</div>
	</ul>
</nav>
<div id="content">
	<h1>Veeam Backup for Office 365 RESTful API demo</h1>
	<br />
	<?php
	if ($check === false) {
		/* Required for dashboard stats */
		$jobs = $veeam->getJobs();
		$proxies = $veeam->getProxies();
		$repos = $veeam->getBackupRepositories();
	?>
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
			<a href="#" id="organizationspanel">
			<div class="panel-footer">
			  <span class="pull-left">Manage</span>
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
				  <div class="medium">&nbsp;<?php echo count($jobs); ?> jobs</div>
				</div>
			  </div>
			</div>
			<a href="#" id="jobspanel">
			<div class="panel-footer">
			  <span class="pull-left">Manage</span>
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
			<a href="#" id="proxiespanel">
			<div class="panel-footer">
			  <span class="pull-left">Manage</span>
			  <span class="pull-right"><i class="fa fa-arrow-circle-right"></i></span>
			  <div class="clearfix"></div>
			</div>
			</a>
		  </div>
		</div>
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
			<a href="#" id="repositoriespanel">
			<div class="panel-footer">
			  <span class="pull-left">Manage</span>
			  <span class="pull-right"><i class="fa fa-arrow-circle-right"></i></span>
			  <div class="clearfix"></div>
			</div>
			</a>
		  </div>
		</div>
	</div>
	<?php
	} else {
		echo 'Welcome to the self service restore demo.';
	}
	?>
</div>
<?php
} else {
?>
<link rel="stylesheet" href="css/form-elements.css" media="screen" />
<div id="login-content">
	<div class="top-content">
		<div class="container">
			<div class="row">
				<div class="col-sm-6 col-sm-offset-3 form-box">
					<div class="form-top">
						<div class="form-top-left">
							<h3>Veeam Backup for Office 365 RESTful API demo</h3>
							<p>Enter your username and password to log on.</p>
							<?php
							if ($login == '400') {
								echo '<div class="alert alert-error alert-dismissible alert-login" role="alert">';
								echo '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>';
								echo 'Incorrect Username or password!';
								echo '</div>';								
							}
							?>
						</div>
						<div class="form-top-right">
							<i class="fa fa-lock"></i>
						</div>
					</div>
					<div class="form-bottom">
						<form role="form" action="" method="post" class="form-login">
							<div class="form-group">
								<label class="sr-only" for="user">Username</label>
								<input type="text" name="user" placeholder="Username or email" class="form-user form-control" autofocus><span class="fa fa-user fa-2x icon"></span>
							</div>
							<div class="form-group">
								<label class="sr-only" for="pass">Password</label>
								<input type="password" name="pass" class="form-pass form-control"><span class="fa fa-lock fa-2x icon"></span>
							</div>
							<button type="submit" class="btn-login">Login</button>
						</form>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
<?php
}
?>
<script src="js/veeam.js"></script>
</body>
</html>