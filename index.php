<?php
error_reporting(E_ALL || E_STRICT);

require_once('config.php');
require_once('veeam.class.php');

session_start();

if (empty($host) || empty($port)) {
    exit('Please modify the configuration file first and configure the Veeam Backup for Microsoft Office 365 host and port settings.');
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo $title; ?></title>
    <base href="/" />
    <link rel="shortcut icon" href="images/favicon.ico" />
    <link rel="stylesheet" type="text/css" href="vendor/twbs/bootstrap/dist/css/bootstrap.min.css" />
    <link rel="stylesheet" type="text/css" href="vendor/semantic/ui/dist/semantic.min.css" />
    <link rel="stylesheet" type="text/css" href="css/fontawesome.min.css" />
    <link rel="stylesheet" type="text/css" href="css/style.css" />
    <script src="vendor/components/jquery/jquery.min.js"></script>
    <script src="vendor/twbs/bootstrap/dist/js/bootstrap.min.js"></script>
    <script src="vendor/semantic/ui/dist/semantic.min.js"></script>
    <script src="js/fontawesome.min.js"></script>
    <script src="js/filesize.min.js"></script>
    <script src="js/moment.min.js"></script>
    <script src="js/veeam.js"></script>
</head>
<body>
<?php
if (isset($_SESSION['token'])) {
    $user = $_SESSION['user'];
    $check = filter_var($user, FILTER_VALIDATE_EMAIL);
?>
<nav class="navbar navbar-inverse navbar-custom">
    <div class="container-fluid">
        <div class="navbar-header">
          <a class="navbar-left navbar-brand" href="index.php"><img src="images/logo.svg" alt="Veeam Backup for Microsoft Office 365" class="logo" /></a>
        </div>
        <ul class="nav navbar-nav" id="nav">
          <li><a href="exchange">Exchange</a></li>
          <li><a href="onedrive">OneDrive</a></li>
          <li><a href="sharepoint">SharePoint</a></li>
        </ul>
        <ul class="nav navbar-nav navbar-right">
          <li><a href="#" onClick="return false;"><span class="fa fa-user"></span> Welcome <i><?php echo $user; ?></i> !</a></li>
          <li id="logout"><a href="#" onClick="return false;"><span class="fa fa-sign-out"></span> Logout</a></li>
        </ul>
    </div>
</nav>
<div class="container-fluid">
<?php 
if ($check === false) { /* We are an admin */
    include_once('includes/dashboard.php');
} else { /* We are a tenant */
    header('Location: /exchange');
}
?>
</div>

<div class="ui tiny modallogout modal">
    <i class="close icon"></i>
    <div class="header text-center">Logout</div>
    <div class="content">
      <p>You are about to logout. Are you sure you want to continue?</p>
    </div>
    <div class="actions text-center">
      <div class="ui negative button"><i class="times icon"></i> No</div>
      <div class="ui positive button"><i class="checkmark icon"></i> Yes</div>
    </div>
</div>

<?php
} else { /* Show login form */
?>
<link rel="stylesheet" href="css/loginform.css" />
<div class="container-fluid login-content">
    <div class="row">
        <?php
        if ($login == 'error') {
        ?>
        <div class="ui icon negative message transition">
            <i class="fa fa-exclamation-triangle fa-2x"></i>
            <i class="close icon"></i>
            <div class="content">
                <div class="header">Error</div>
                <p>The username or password provided is incorrect. Make sure you are logging in with your Office 365 account.</p>
          </div>
        </div>
        <script>
        $('.message .close').on('click', function(e) {
            $(this).closest('.message').transition('fade');
        });
        </script>
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