<?php
error_reporting(E_ALL || E_STRICT);

require_once('config.php');
require_once('veeam.class.php');

session_start();

if (empty($host) || empty($port) || empty($version)) {
    exit('Please modify the configuration file first and configure the Veeam Backup for Microsoft Office 365 host, port and RESTful API version settings.');
}

if ($version != 'v2' && $version != 'v3') {
	exit('Invalid API version found. Please modify the configuration file and configure the Veeam Backup for Microsoft Office 365 RESTful API version setting. Supported versions are either v2 or v3. v1 is not supported.');
}

$veeam = new VBO($host, $port, $version);

if (isset($_POST['logout'])) {
    $veeam->logout();
} else {
    if (!empty($_POST['user'])) { $user = $_POST['user']; }
    if (!empty($_POST['pass'])) { $pass = $_POST['pass']; }

    if (isset($user) && isset($pass)) {
        $login = $veeam->login($user, $pass);

        $_SESSION['refreshtoken'] = $veeam->getRefreshToken();
        $_SESSION['token'] = $veeam->getToken();
        $_SESSION['user'] = $user;
    }
}

if (isset($_SESSION['token'])) {
    $veeam->setToken($_SESSION['token']);
}

if (isset($_SESSION['refreshtoken'])) {
	$veeam->refreshToken($_SESSION['refreshtoken']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo $title; ?></title>
    <base href="/" />
    <link rel="shortcut icon" href="images/favicon.ico" />
    <link rel="stylesheet" type="text/css" href="vendor/twbs/bootstrap/dist/css/bootstrap.min.css" />
    <link rel="stylesheet" type="text/css" href="css/fontawesome.min.css" />
    <link rel="stylesheet" type="text/css" href="css/style.css" />
	<link rel="stylesheet" type="text/css" href="css/sweetalert2.min.css" />	
    <script src="vendor/components/jquery/jquery.min.js"></script>
    <script src="vendor/twbs/bootstrap/dist/js/bootstrap.min.js"></script>
    <script src="js/fontawesome.min.js"></script>
    <script src="js/filesize.min.js"></script>
    <script src="js/moment.min.js"></script>
	<script src="js/sweetalert2.all.min.js"></script>
</head>
<body>
<?php
if (isset($_SESSION['token'])) {
    $user = $_SESSION['user'];
    $check = filter_var($user, FILTER_VALIDATE_EMAIL);

	if ($check === false && strtolower($administrator) == 'yes') { /* We are an admin */
	?>
	<nav class="navbar navbar-inverse navbar-static-top">
		<ul class="nav navbar-header">
		  <li><a class="navbar-brand navbar-logo" href="/"><img src="images/logo.svg" alt="Veeam Backup for Microsoft Office 365" class="logo" /></a></li>
		</ul>
		<ul class="nav navbar-nav" id="nav">
		  <li><a href="exchange">Exchange</a></li>
		  <li><a href="onedrive">OneDrive</a></li>
		  <li><a href="sharepoint">SharePoint</a></li>
		</ul>
		<ul class="nav navbar-nav navbar-right">
		  <li><a href="#" onClick="return false;"><span class="fa fa-user"></span> Welcome <i><?php echo $user; ?></i> !</a></li>
		  <li id="logout"><a href="#" onClick="return false;"><span class="fa fa-sign-out-alt"></span> Logout</a></li>
		</ul>
	</nav>
	<div class="container-fluid">
		<aside id="sidebar">
			<div class="logo-container"><i class="logo fa fa-cogs"></i></div>
			<div class="separator"></div>
			<menu class="menu-segment">
			<ul class="menu">
				<li id="dashboard" data-call="dashboard"><i class="fa fa-tachometer-alt"></i> Dashboard</li>
				<li id="jobs" data-call="jobs"><i class="fa fa-calendar"></i> Jobs</li>
				<li id="organizations" data-call="organizations"><i class="fa fa-building"></i> Organizations</li>
				<li id="proxies" data-call="proxies"><i class="fa fa-server"></i> Proxies</li>
				<li id="repositories" data-call="repositories"><i class="fa fa-database"></i> Repositories</li>
				<li id="licensing" data-call="licensing"><i class="fa fa-file-alt"></i> Licensing</li>
				<li id="activity" data-call="activity"><i class="fa fa-tasks"></i> Activity</li>
			</ul>
			</menu>
			<div class="separator"></div>
			<div class="bottom-padding"></div>
		</aside>
		<main id="main">
		<?php 
		include_once('includes/dashboard.php');
		?>
		</main>
	</div>
	<script>
	/* Logout option */
	$('#logout').click(function(e) {
		e.preventDefault();
		
		const swalWithBootstrapButtons = Swal.mixin({
		  confirmButtonClass: 'btn btn-success btn-margin',
		  cancelButtonClass: 'btn btn-danger',
		  buttonsStyling: false,
		})
		
		swalWithBootstrapButtons.fire({
			type: 'question',
			title: 'Logout',
			text: 'You are about to logout. Are you sure you want to continue?',
			showCancelButton: true,
			confirmButtonText: 'Yes',
			cancelButtonText: 'No',
		}).then((result) => {
			if (result.value) {
				$.post('index.php', {'logout' : true}, function(data) {
					window.location.replace('index.php');
				});
			  } else {
				return;
			}
		})
	});

	/* Dashboard menu handler */
	$('ul.menu li').click(function(e) {
		var call = $(this).data('call');
		var id = this.id;

		if (typeof id === undefined || !id) {
			return;
		}
		
		if (call == 'dashboard') {
			window.location.replace('index.php');
		} else {
			$('#main').load('includes/' + call + '.php');
		}
	});
	</script>
	<?php
	} else { /* We are a tenant */
		header('Location: /exchange');
	}
} else { /* Show login form */
	unset($_SESSION);
    session_destroy();
?>
<section class="login-block">
	<div class="container login-container">
		<div class="row">
			<div class="col-md-4 login-sec">
				<h2 class="text-center">Login</h2>
				<form class="login-form" method="post">
					<div class="form-group">
						<label for="username" class="text-uppercase">Username:</label>
						<input type="text" class="input-loginform form-control" name="user" autofocus /><span class="fa fa-user fa-2x icon"></span>
					</div>
					<div class="form-group">
						<label for="password" class="text-uppercase">Password:</label>
						<input type="password" class="input-loginform form-control" name="pass" /><span class="fa fa-lock fa-2x icon"></span>
					</div>
					<div class="form-check text-center">
						<button type="submit" class="btn btn-login">Login</button>
					</div>
					<div class="text-center">
					<?php
					if ($login == 'error') {
						echo '<br /><p class="text-warning">The username or password provided is incorrect.</p>';
					}
					?>
					</div>
				</form>
			</div>
			<div class="col-md-8 banner-sec"></div>				
		</div>
	</div>
</section>	
<?php
}
?>
</body>
</html>