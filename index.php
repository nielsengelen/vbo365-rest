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
	<?php 
	if (isset($_SESSION['token'])) {
	?>
    <script src="js/veeam.js"></script>
	<?php
	}
	?>
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
		<?php 
		include_once('includes/dashboard.php');
		?>
		</div>
		<?php
	} else { /* We are a tenant */
		header('Location: /exchange');
	}
} else { /* Show login form */
	unset($_SESSION);
    session_destroy();
?>
	<!--<script>
	Swal.fire({
		type: 'info',
		title: 'Session terminated',
		text: 'Your session has timed out and requires you to login again.'
	})
	</script>-->
	<link rel="stylesheet" href="css/loginform.css" />
	<div class="container-fluid login-content">
		<div class="row">
			<?php
			if ($login == 'error') {
			?>
			<div class="alert alert-danger">
				<a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
				<strong><i class="fa fa-exclamation-triangle"></i> Error</strong>
				<p>The username or password provided is incorrect.</p>
			</div>
			<?php
			}
			?>
			<div class="col-sm-6 col-sm-offset-3">
				<div class="form-top">
					<div class="form-top-left"><i class="fa fa-lock"></i></div>
					<div class="form-top-right"><h3><?php echo $title; ?></h3></div>
				</div>
				<div class="form-bottom">
					<form action="" class="form-login" id="login-form" method="post" style="display: block;">
						<div class="form-group">
							<input type="text" class="form-user form-control" name="user" placeholder="Username or email" autofocus /><span class="fa fa-user fa-2x icon"></span>
						</div>
						<div class="form-group">
							<input type="password" class="form-pass form-control" name="pass" placeholder="Password" /><span class="fa fa-lock fa-2x icon"></span>
						</div>
						<button type="submit" class="btn-login">Login</button><br />
					</form>
				</div>
			</div>
		</div>
	</div>
<?php
}
?>
</body>
</html>