<?php
error_reporting(E_ALL || E_STRICT);

require 'config.php';
require 'veeam.class.php';

$veeam = new VBO($host, $port, $user, $pass);
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<title>API demo for Veeam Backup for Office 365</title>
	<link rel="shortcut icon" href="images/favicon.ico" />
	<link rel="stylesheet" type="text/css" href="vendor/twbs/bootstrap/dist/css/bootstrap.min.css" />
    <link rel="stylesheet" type="text/css" href="vendor/twbs/bootstrap/dist/css/bootstrap-theme.min.css" />
	<link rel="stylesheet" href="css/font-awesome.min.css" media="screen" />
	<link rel="stylesheet" href="css/style.css" media="screen" />	
    <script src="vendor/components/jquery/jquery.min.js"></script>
    <script src="vendor/twbs/bootstrap/dist/js/bootstrap.min.js"></script>
	<script src="js/bootbox.min.js"></script>
	<script>
	$(document).ready(function() {
	  $('#menusection li').click(function () {
		var id = this.id;
		if (id == 'logout') {
			$.post('index.php', {'logout' : true}, function(data) {
				alert('Logout succesful.');
				window.location.replace('index.php');
			});
		} else {
			var call = $(this).attr('data-call');
			$.get('veeam.php', {'action' : call, 'id' : id}, function(data) {
				$('#content').html('<h1>API demo for Veeam Backup for Office 365 1.5 (BETA)</h1>' + data)
			});
		}
	  });
	  
	  $(document).on('click', '#btn-start-item-restore', function(e) {
		var id = $(this).data('id');
			
		bootbox.confirm({
			message: 'Start item restore wizard?',
			callback: function (result) {
				if (result) {
					$.get('veeam.php', {'id' : id, action : 'startrestore'}).done(function(data) {
						bootbox.alert({
							message: 'Wizard has been started and you can now perform item restores.',
							backdrop: true
						});
						
						alert(data);
						$('#div-item-restore').html('<button class="btn btn-default" id="btn-end-item-restore" data-orgid="' + id + '" data-id="' + data + '" title="End item restore">End item restore</button>');
					});
				}
			}
		});
	  });
	  
	  $(document).on('click', '#btn-end-item-restore', function(e) {
		var id = $(this).data('id');
		var orgid = $(this).data('orgid');
		
		bootbox.confirm({
			message: 'Terminate item restore wizard?',
			callback: function (result) {
				if (result) {
					$.get('veeam.php', {'id' : id, action : 'endrestore'}).done(function(data) {
						bootbox.alert({
							message: 'Wizard has been terminated.',
							backdrop: true
						});

						$('#div-item-restore').html('<button class="btn btn-default" id="btn-start-item-restore" data-id="' + orgid + '" title="Start item restore">Start item restore</button>');
					});
				}
			}
		});
	  });
	});
	</script>
</head>
<body>
<div>
	<header class="hdr">
		<a href="index.php"><img src="images/logo.svg" alt="Veeam Backup for Office 365" class="headerlogo" /></a>
	</header>
	<nav id="menu">
		<ul>		
			<div id="menusection">
				<strong>Organizations:</strong><br />
				<?php
				$org = $veeam->getOrganization();
				
				for ($i = 0; $i < count($org); $i++) {
					echo '<li id="' . $org[$i]['id'] . '" data-call="getmailbox">' . $org[$i]['name'] . '</li>';
				}
				?>
				<br />
				<li class="divider"></li>
				<strong>Configuration:</strong><br />
				<li id="jobs" data-call="getjobs">Jobs</li>
				<li class="divider"></li>
				<strong>Infrastructure:</strong>
				<li id="proxies" data-call="getproxies">Proxies</li>
				<li id="repositories" data-call="getrepos">Repositories</li>
			</div>
		</ul>
	</nav>
</div>
<div>
	<div id="content">
		<h1>API demo for Veeam Backup for Office 365 1.5 (BETA)</h1>
	</div>
</div>
</body>
</html>