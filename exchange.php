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
    <link rel="stylesheet" type="text/css" href="css/flatpickr.min.css">
    <link rel="stylesheet" type="text/css" href="css/fontawesome.min.css" />
    <link rel="stylesheet" type="text/css" href="css/style.css" />
    <script src="vendor/components/jquery/jquery.min.js"></script>
    <script src="vendor/twbs/bootstrap/dist/js/bootstrap.min.js"></script>
    <script src="vendor/semantic/ui/dist/semantic.min.js"></script>
    <script src="js/fontawesome.min.js"></script>
    <script src="js/filesize.min.js"></script>
    <script src="js/moment.min.js"></script>
    <script src="js/flatpickr.js"></script>
    <script src="js/veeam.js"></script>
</head>
<body>
<?php
if (isset($_SESSION['token'])) {
    $user = $_SESSION['user'];
?>
<nav class="navbar navbar-inverse navbar-custom">
    <div class="container-fluid">
        <div class="navbar-header">
          <a class="navbar-left navbar-brand" href="index.php"><img src="images/logo.svg" alt="Veeam Backup for Microsoft Office 365" class="logo" /></a>
        </div>
        <ul class="nav navbar-nav" id="nav">
          <li class="active"><a href="exchange">Exchange</a></li>
          <li><a href="onedrive">OneDrive</a></li>
          <li><a href="sharepoint">SharePoint</a></li>
        </ul>
        <ul class="nav navbar-nav navbar-right">
          <li><a href="#"><span class="fa fa-user"></span> Welcome <i><?php echo $user; ?></i> !</a></li>
          <li id="logout"><a href="#"><span class="fa fa-sign-out"></span> Logout</a></li>
        </ul>
    </div>
</nav>
<div class="container-fluid">
    <link rel="stylesheet" href="css/exchange.css" />
    <aside id="sidebar">
        <div class="logo-container"><i class="logo fa fa-envelope"></i></div>
        <div class="separator"></div>
        <menu class="menu-segment" id="menu">
            <?php
            if (!isset($_SESSION['rid'])) { /* No restore session is running */
                $check = filter_var($user, FILTER_VALIDATE_EMAIL);

                if ($check === false) { /* We are an admin so we list all the organizations in the menu */
                    $oid = $_GET['oid'];
                    $org = $veeam->getOrganizations();
                    
                    echo '<ul id="ul-exchange-users">';
                    
                    for ($i = 0; $i < count($org); $i++) {
                        if (isset($oid) && !empty($oid) && ($oid == $org[$i]['id'])) {
                            echo '<li class="active"><a href="exchange/' . $org[$i]['id'] . '">' . $org[$i]['name'] . '</a></li>';
                        } else {
                            echo '<li><a href="exchange/' . $org[$i]['id'] . '">' . $org[$i]['name'] . '</a></li>';
                        }
                    }
                    
                    echo '</ul>';
                } else {
                    $org = $veeam->getOrganization();
                    ?>
                    <button class="btn btn-default btn-secondary btn-start-exchange-restore" title="Start Restore" data-type="vex">Start Restore</button><br /><br />
                    <div class="input-group flatpickr paddingdate" data-wrap="true" data-clickOpens="false">
                        <input type="text" class="form-control" id="pit-date" placeholder="Select a date.." data-input>
                        <span class="input-group-addon" data-open><i class="fa fa-calendar"></i></span>
                        <script>
                        $('#pit-date').removeClass('errorClass');

                        $('.flatpickr').flatpickr({
                            dateFormat: "Y.m.d H:i",
                            enableTime: true,
                            minDate: "<?php echo date('Y.m.d', strtotime($org['firstBackuptime'])); ?>",
                            maxDate: "<?php echo date('Y.m.d', strtotime($org['lastBackuptime'])); ?>",
                            time_24hr: true
                        });
                        </script>
                    </div>
                    <?php
                }
            } else { /* Restore session is running */
                $rid = $_SESSION['rid'];

                if (strcmp($_SESSION['rtype'], 'vex') === 0) {
                    $uid = $_GET['uid'];
                    $content = array();
                    $org = $veeam->getOrganizationID($rid);
                    $users = $veeam->getMailbox($rid);

                    for ($i = 0; $i < count($users['results']); $i++) {
                        array_push($content, array('name'=> $users['results'][$i]['name'], 'id' => $users['results'][$i]['id']));
                    }

                    uasort($content, function($a, $b) {
                        return strcmp($a['name'], $b['name']);
                    });

                    echo '<div><span id="span-item-exchange"><button class="btn btn-default btn-danger" id="btn-stop-exchange-restore" title="Stop Restore">Stop Restore</button></span></div>';
                    echo '<div class="separator"></div>';
                    echo '<ul id="ul-exchange-users">';
                    
                    foreach ($content as $key => $value) {
                        if (isset($uid) && !empty($uid) && ($uid == $value['id'])) {
                            echo '<li class="active"><a href="exchange/' . $org['id'] . '/' . $value['id'] . '">' . $value['name'] . '</a></li>';
                        } else {
                            echo '<li><a href="exchange/' . $org['id'] . '/' . $value['id'] . '">' . $value['name'] . '</a></li>';
                        }
                    }
                    
                    echo '</ul>';
                } else {
                   echo 'Found another session running, <br />please terminate that one first if you want to restore Exchange items.';
                }
            }
            ?>
        </menu>
        <div class="separator"></div>
        <div class="bottom-padding"></div>
    </aside>
    <main id="main">
        <div class="infobox text-center" id="infobox">
        <?php
        if (isset($rid)) {
            if (strcmp($_SESSION['rtype'], 'vex') !== 0) {
                if (strcmp($_SESSION['rtype'], 'vesp') === 0) {
                    echo '<div class="alert alert-danger alert-dismissible" role="alert"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button><strong>Found a restore session running for SharePoint, <br />please terminate that one first if you want to restore Exchange items.</strong></div>';

                    echo '<a href="sharepoint">Go to running session</a>';
                } else {
                    echo '<div class="alert alert-danger alert-dismissible" role="alert"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button><strong>Found a restore session running for OneDrive, <br />please terminate that one first if you want to restore Exchange items.</strong></div>';

                    echo '<a href="onedrive">Go to running session</a>';
                }

                exit;
            }
        }
        ?>
        </div>
        <div class="row exchange-container">
        <?php    
        if (!isset($_SESSION['rid'])) { /* No restore session is running */
            if (isset($oid) && !empty($oid)) { /* We got an organization ID so list all available jobs */
                $jobs = $veeam->getJobs($oid);
                
                if (count($jobs) != '0') {
                    for ($i = 0; $i < count($jobs); $i++) {
                        $items = $veeam->getJobSelectedItems($jobs[$i]['id']);
                        
                        if ($items[0]['mailbox'] == 1 || $items[0]['archiveMailbox'] == 1) {
            ?>
            <h1>Exchange</h1>
            <div id="div-exchange-pitlist">
                <h3>Job name: <?php echo $jobs[$i]['name']; ?></h3>
                <table class="table table-bordered table-padding table-striped">
                    <thead>
                        <tr>
                            <th>Point in time</th>
                            <th>Status</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $jobsession = $veeam->getJobSession($jobs[$i]['id']);

                        for ($j = 0; $j < count($jobsession); $j++) {
                            if ((strcmp(strtolower($jobsession[$j]['status']), 'success') === 0) || (strcmp(strtolower($jobsession[$j]['status']), 'warning') === 0)) {
                                echo '<tr>';
                                echo '<td>' . (isset($jobsession[$j]['creationTime']) ? date('d/m/Y H:i', strtotime($jobsession[$j]['creationTime'])) : 'N/A') . '</td>';
                                echo '<td><span class="label label-' . strtolower($jobsession[$j]['status']) . '">' . $jobsession[$j]['status'] . '</span></td>';
                                echo '<td class="text-center"><span id="span-item-exchange-' . $jobs[$i]['id'] . '"><button class="btn btn-default btn-secondary btn-start-exchange-restore" title="Start Restore" data-jid="' . $jobs[$i]['id'] . '" data-oid="' . $oid . '" data-pit="' . date('Y.m.d H:i:s', strtotime($jobsession[$j]['creationTime'])) . '" data-type="vex">Start Restore</button></span></td>';
                                echo '</tr>';
                            }
                        }
                        ?>
                    </tbody>
                </table>
            </div>
            <div class="hide" id="div-exchange-default">Select a mailbox to list the content.</div>
                    <?php
                        }
                    }
                } else { /* No jobs available for the organization ID */
                    echo '<h1>Exchange</h1><div id="div-exchange-default">No Exchange backup jobs found for this organization.</div>';
                }
            } else {
                if ($check === false) { /* No organization has been selected */
                    echo '<h1>Exchange</h1><div id="div-exchange-default">Select an organization to list the restore points.</div>';
                } else {
                    echo '<h1>Exchange</h1><div id="div-exchange-default">Select a point in time and start the restore.</div>';
                }
            }
        } else { /* Restore session is running */
            if (isset($uid) && !empty($uid)) {
                $folders = $veeam->getMailboxFolders($uid, $rid);
                $items = $veeam->getMailboxItems($uid, $rid);

                if (count($items['results']) != '0') {
                ?>
                <div class="col-sm-4">
                    <select class="form-control padding" id="inbox-nav">
                        <option disabled selected>-- Filter by folder --</option>
                    <?php
                    for ($i = 0; $i < count($folders['results']); $i++) {
                    ?>
                        <option data-folderid="<?php echo $folders['results'][$i]['id']; ?>" data-mailboxid="<?php echo $uid; ?>"><?php echo $folders['results'][$i]['name']; ?></option>
                    <?php
                    }
                    ?>
                    </select>
                </div>
                <div class="col-sm-8">
                    <input class="form-control search-hover" id="search-mailbox" placeholder="Search item..." />
                </div>
                <br />
                <table class="table table-hover table-bordered table-striped table-border" id="table-exchange-items">
                    <thead>
                        <tr>
                            <th class="text-center"><strong>Type</strong></th>
                            <th><strong>From</strong></th>
                            <th><strong>Subject</strong></th>
                            <th class="text-center"><strong>Received</strong></th>
                            <th class="text-center"><strong>Options</strong></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    for ($i = 0; $i < count($items['results']); $i++) {
                    ?>
                        <tr>
                            <?php
                            if ($items['results'][$i]['itemClass'] != 'IPM.Appointment') {
                                echo '<td class="text-center"><span class="logo fa fa-envelope"></span></td>';
                                echo '<td>' . $items['results'][$i]['from'] . '</td>';
                            } else {
                                echo '<td class="text-center"><span class="logo fa fa-calendar"></span></td>';
                                echo '<td>' . $items['results'][$i]['organizer'] . '</td>';
                            }
                            ?>
                            <td><?php echo $items['results'][$i]['subject']; ?></td>
                            <td class="text-center">
                            <?php 
                            if ($items['results'][$i]['itemClass'] != 'IPM.Appointment') {
                                echo date('d/m/Y H:i', strtotime($items['results'][$i]['received']));
                            }
                            ?>
                            </td>
                            <td class="text-center">
                                <div class="dropdown">
                                    <button class="btn btn-default dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">Options <span class="caret"></span></button>
                                    <ul class="dropdown-menu dropdown-menu-right">
                                      <li class="dropdown-header">Download as</li>
                                      <li><a class="dropdown-link" data-action="download-msg" data-itemid="<?php echo $items['results'][$i]['id']; ?>" data-mailsubject="<?php echo $items['results'][$i]['subject']; ?>" data-mailboxid="<?php echo $uid; ?>" href="<?php echo $_SERVER['REQUEST_URI']; ?>#"><i class="fa fa-download"></i> MSG file</a></li>
                                      <li><a class="dropdown-link" data-action="download-pst" data-itemid="<?php echo $items['results'][$i]['id']; ?>" data-mailsubject="<?php echo $items['results'][$i]['subject']; ?>" data-mailboxid="<?php echo $uid; ?>" href="<?php echo $_SERVER['REQUEST_URI']; ?>#"><i class="fa fa-download"></i> PST file</a></li>
                                      <li class="divider"></li>
                                      <li class="dropdown-header">Restore to</li>
                                      <li><a class="dropdown-link" data-action="restore-different" data-itemid="<?php echo $items['results'][$i]['id']; ?>" data-mailboxid="<?php echo $uid; ?>" href="<?php echo $_SERVER['REQUEST_URI']; ?>#"><i class="fa fa-upload"></i> Different location</a></li>
                                      <li><a class="dropdown-link" data-action="restore-original" data-itemid="<?php echo $items['results'][$i]['id']; ?>" data-mailboxid="<?php echo $uid; ?>" href="<?php echo $_SERVER['REQUEST_URI']; ?>#"><i class="fa fa-upload"></i> Original location</a></li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                    <?php
                    }
                    ?>
                    </tbody>
                </table>
                <div class="text-center">
                    <?php
                    if (count($items['results']) == '30') { /* If we have 30 messages from the first request, show message to load additional messages */
                    ?>
                        <a class="btn btn-default load-more-link" data-folderid="null" data-mailboxid="<?php echo $uid; ?>" data-offset="<?php echo count($items['results'])+1; ?>" href="<?php echo $_SERVER['REQUEST_URI']; ?>#">Load more messages</a>
                    <?php
                    } else { /* Else hide the load more messages message */
                    ?>
                        <a class="btn btn-default hide load-more-link" data-folderid="null" data-mailboxid="<?php echo $uid; ?>" data-offset="<?php echo count($items['results'])+1; ?>" href="<?php echo $_SERVER['REQUEST_URI']; ?>#">Load more messages</a>
                    <?php
                    }
                    ?>
                </div>
                <?php
                } else {
                    echo '<h1>Exchange</h1><div id="div-exchange-default">No items available in this mailbox.</div>';
                }
            } else {
                echo '<h1>Exchange</h1><div id="div-exchange-default">Select a mailbox to list the content.</div>';
            }
        }
        ?>
        </div>
    </main>
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

<div class="ui modalrestoreoriginal modal">
    <div class="header text-center">Restore to the original location</div>
    <div class="content">
        <label for="restore-original-user">Username:</label>
        <input type="text" class="form-control" id="restore-original-user" placeholder="user@example.onmicrosoft.com"></input>
        <br />
        <label for="restore-original-pass">Password:</label>
        <input type="password" class="form-control" id="restore-original-pass" placeholder="password"></input>
    </div>
    <div class="actions text-center">
      <div class="ui negative button"><i class="times icon"></i> Cancel</div>
      <div class="ui positive button"><i class="checkmark icon"></i> Restore</div>
    </div>
</div>
<div class="ui modalrestoredifferent modal">
    <div class="header text-center">Restore to a different location</div>
    <div class="content">
        <label for="restore-different-mailbox">Target mailbox:</label>
        <input type="text" class="form-control" id="restore-different-mailbox" placeholder="user@example.onmicrosoft.com"></input>
        <br />
        <label for="restore-different-casserver">Target mailbox server (CAS):</label>
        <input type="text" class="form-control" id="restore-different-casserver" placeholder="outlook.office365.com" value="outlook.office365.com"></input>
        <br />
        <label for="restore-different-user">Username:</label>
        <input type="text" class="form-control" id="restore-different-user" placeholder="user@example.onmicrosoft.com"></input>
        <br />
        <label for="restore-different-pass">Password:</label>
        <input type="password" class="form-control" id="restore-different-pass" placeholder="password"></input>
        <br />
        <label for="restore-different-folder">Folder:</label>
        <input type="text" class="form-control" id="restore-different-folder" placeholder="Custom folder (optional)"></input>
  </div>
  <div class="actions text-center">
      <div class="ui negative button"><i class="times icon"></i> Cancel</div>
      <div class="ui positive button"><i class="checkmark icon"></i> Restore</div>
    </div>
</div>

<div class="ui tiny modalrestorestarted modal">
    <i class="close icon"></i>
    <div class="header text-center">Session started</div>
    <div class="content">
      <p>Restore session has been started and you can now perform item restores.</p>
    </div>
    <div class="actions text-center">
      <div class="ui positive button"><i class="checkmark icon"></i> Ok</div>
    </div>
</div>

<div class="ui tiny modalstoprestorefirst coupled modal">
    <i class="close icon"></i>
    <div class="header text-center">Stop the restore session?</div>
    <div class="content">
      <p>Are you sure you want to end the current restore session? This will terminate any restore options for the specific point in time.</p>
    </div>
    <div class="actions text-center">
      <div class="ui negative button"><i class="times icon"></i> No</div>
      <div class="ui positive button"><i class="checkmark icon"></i> Yes</div>
    </div>
</div>
<div class="ui tiny modalstoprestoresecond coupled modal">
    <i class="close icon"></i>
    <div class="header text-center">Restore session has stopped</div>
    <div class="content">
      <p>The restore session has stopped successfully.</p>
    </div>
    <div class="actions text-center">
      <div class="ui positive approve button"><i class="checkmark icon"></i> Ok</div>
    </div>
</div>

<script>
/* Exchange Restore Buttons */
$(document).on('click', '.btn-start-exchange-restore', function(e) {
    if (typeof $(this).data('jid') !== 'undefined') {
        var jid = $(this).data('jid'); /* Job ID */
    }

    if (typeof $(this).data('oid') !== 'undefined') {
        var oid = $(this).data('oid'); /* Organization ID */
        var pit = $(this).data('pit');
    } else {
        var oid = 'tenant';

        if (!document.getElementById('pit-date').value) { /* No date has been selected */
            $('#pit-date').addClass('errorClass');
            $('#infobox').html('<div class="alert alert-danger alert-dismissible" role="alert"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button><strong>No date selected, please select a date first before starting the restore.</strong></div>');
            return;
        } else {
            var pit = $('#pit-date').val();
            $('#pit-date').removeClass('errorClass');
        }
    }

    var type = $(this).data('type');
    var json = '{ "explore": { "datetime": "' + pit + '", "type": "' + type + '" } }'; /* JSON code to start the restore session */

    $(':button').prop('disabled', true); /* Disable all buttons to prevent double start */

    $.get('veeam.php', {'action' : 'startexplorer', 'json' : json, 'id' : oid}).done(function(data) {
        if (data.match(/([a-zA-Z0-9]{8})-([a-zA-Z0-9]{4})-([a-zA-Z0-9]{4})-([a-zA-Z0-9]{4})-([a-zA-Z0-9]{12})/g)) {
            e.preventDefault();

            $('.modalrestorestarted.modal').modal({
                centered : true,
                closable : false,
                onApprove: function(e) {
                    window.location.href = 'exchange';
                }
            }).modal('show');
        } else {
            $('#infobox').html('<div class="alert alert-danger alert-dismissible" role="alert"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button><strong>' + data + '</strong></div>');
            $(':button').prop('disabled', false); /* Enable all buttons again */
        }
    });
});
$(document).on('click', '#btn-stop-exchange-restore', function(e) {
    var rid = "<?php echo $rid; ?>"; /* Restore Session ID */

    e.preventDefault();

    $('.modalstoprestorefirst.modal').modal({
        centered : true,
        onApprove: function(e) {
            $.get('veeam.php', {'action' : 'stopexplorer', 'id' : rid}).done(function(data) {
                $('.modalstoprestoresecond.modal').modal({
                    centered : true,
                    closable : false,
                    onApprove: function(e) {
                        window.location.href = 'exchange';
                    }
                }).modal('show');
            });
        },
        onDeny   : function(e) {
          return;
        },
    }).modal('show');
});

<?php
if (isset($rid)) {
?>
/* Dropdown settings */
$(document).on("hide.bs.dropdown", ".dropdown", function(e) {
    $(e.target).find(">.dropdown-menu:first").slideUp();
});
$(document).on("show.bs.dropdown", ".dropdown", function(e) {
    $(e.target).find(">.dropdown-menu:first").slideDown();
});

/* Export and restore options */
$(document).on("click", ".dropdown-link", function(e) {
    var action = $(this).data("action");
    var itemid = $(this).data("itemid");
    var mailboxid = $(this).data("mailboxid");
    var rid = "<?php echo $rid; ?>";

    if (action == "download-msg") {
        var json = '{ "savetoMsg": null }';
        var mailsubject = $(this).data("mailsubject");
        
        $.get("veeam.php", {"action" : "exportmailitem", "itemid" : itemid, "mailboxid" : mailboxid, "rid" : rid, "json" : json}).done(function(data) {
            window.location.href = "download.php?ext=msg&file=" + data + "&name=" + mailsubject;
        });
    } else if (action == "download-pst") {
        var json = '{ "ExportToPst": { "ContentKeyword": "" } }';
        var mailsubject = $(this).data("mailsubject");
        
        $.get("veeam.php", {"action" : "exportmailitem", "itemid" : itemid, "mailboxid" : mailboxid, "rid" : rid, "json" : json}).done(function(data) {
            window.location.href = "download.php?ext=pst&file=" + data + "&name=" + mailsubject;
        });
    } else if (action == "restore-different") {        
        $(".modalrestoredifferent.modal").modal({
            centered : true,
            closable : true,
            onApprove: function(e) {
                var user = $("#restore-different-user").val();
                var pass = $("#restore-different-pass").val();
                var casserver = $("#restore-different-casserver").val();
                var folder = $("#restore-different-folder").val();
                var mailbox = $("#restore-different-mailbox").val();
                
                if (typeof folder === undefined || !folder) {
                    folder = "Restored-via-web-client";
                }
                
                if (typeof user === undefined || !user) {
                    $("#infobox").slideDown();
                    $("#infobox").html('<div class="alert alert-danger alert-dismissible" role="alert"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button><strong>Restore failed: no username defined.</strong></div>');
                    return;
                }
                            
                if (typeof pass === undefined || !pass) {
                    $("#infobox").slideDown();
                    $("#infobox").html('<div class="alert alert-danger alert-dismissible" role="alert"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button><strong>Restore failed: no password defined.</strong></div>');
                    return;
                }
            
                var json = '{ "restoreTo": \
                        { "casServer": "' + casserver + '", \
                          "mailbox": "' + mailbox + '", \
                          "folder": "' + folder + '", \
                          "userName": "' + user + '", \
                          "userPassword": "' + pass + '", \
                          "changedItems": "true", \
                          "deletedItems": "true", \
                          "markRestoredAsUnread": "true" } \
                        }';

                console.log(json);
                
                $.get("veeam.php", {"action" : "restoremailto", "itemid" : itemid, "mailboxid" : mailboxid, "rid" : rid, "json" : json}).done(function(data) {
                    $("#infobox").slideDown();
                    $("#infobox").html('<div class="alert alert-info alert-dismissible" role="alert"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button><strong>' + data + '</strong></div>');
                });
            },
            onDeny   : function(e){
              return;
            },
        }).modal("show");
    } else if (action == "restore-original") {
        $(".modalrestoreoriginal.modal").modal({
            centered : true,
            closable : true,
            onApprove: function(e) {
                var user = $("#restore-original-user").val();
                var pass = $("#restore-original-pass").val();

                if (typeof user === undefined || !user) {
                    $("#infobox").slideDown();
                    $("#infobox").html('<div class="alert alert-danger alert-dismissible" role="alert"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button><strong>Restore failed: no username defined.</strong></div>');
                    return;
                }
                            
                if (typeof pass === undefined || !pass) {
                    $("#infobox").slideDown();
                    $("#infobox").html('<div class="alert alert-danger alert-dismissible" role="alert"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button><strong>Restore failed: no password defined.</strong></div>');
                    return;
                }
                
                var json = '{ "restoretoOriginallocation": \
                        { "userName": "' + user + '", \
                          "userPassword": "' + pass + '", \
                          "ChangedItems": "True", \
                          "DeletedItems": "True", \
                          "MarkRestoredAsUnread": "True" } \
                        }';
                
                $.get("veeam.php", {"action" : "restoremailoriginal", "itemid" : itemid, "mailboxid" : mailboxid, "rid" : rid, "json" : json}).done(function(data) {
                    $("#infobox").slideDown();
                    $("#infobox").html('<div class="alert alert-info alert-dismissible" role="alert"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button><strong>' + data + '</strong></div>');
                });
            },
            onDeny   : function(e){
              return;
            },
        }).modal("show");
    }
});

/* Load more link */
$(document).on("click", ".load-more-link", function(e) {
    var folderid = $(this).data("folderid");
    var mailboxid = $(this).data("mailboxid");
    var offset = $(this).data("offset");
    var rid = "<?php echo $rid; ?>";
    
    loadMessages(folderid, mailboxid, rid, offset);
});

/* Warn user if session is running and fade out infobox */
$("#infobox").fadeTo(2000, 500).slideUp(500, function(e) {
    $("#infobox").slideUp(500);
});
    
/* Item search */
$("#search-mailbox").keyup(function(e) {
    var searchText = $(this).val().toLowerCase();
    /* Show only matching row, hide rest of them */
    $.each($("#table-exchange-items tbody tr"), function(e) {
        if ($(this).text().toLowerCase().indexOf(searchText) === -1) {
           $(this).hide();
        } else {
           $(this).show();
        }
    });
});

/* Users and folder navigation content */
$("#inbox-nav").change(function(e) {
    var folderid = $("#inbox-nav option:selected").data("folderid");
    var mailboxid = $("#inbox-nav option:selected").data("mailboxid");
    var offset = "0";
    var rid = "<?php echo $rid; ?>";

    loadMessages(folderid, mailboxid, rid, offset, "1");
});
$(document).on("click", "ul#ul-exchange-users li", function(e) {
    $(this).parent().find("li.active").removeClass("active");
    $(this).addClass("active");
});

/* Used for stop restore session modal */
$(".coupled.modal").modal({
    allowMultiple: false
});
<?php
}
?>

/* Exchange functions */
/*
 * @param folderid Folder ID
 * @param mailboxid Mailbox ID
 * @param rid Restore session ID
 * @param offset Offset
 * @param cleartable 1 or undefined
 */
function loadMessages(folderid, mailboxid, rid, offset, cleartable) {
    $.get("veeam.php", {"action" : "getmailitems",  "folderid" : folderid, "offset" : offset, "mailboxid" : mailboxid, "rid" : rid}).done(function(data) {
        var response = JSON.parse(data);

        if (typeof cleartable !== 'undefined') {
            $('#table-exchange-items tbody').empty();
        }
        
        if (response.results.length != '0') {
            $('a.load-more-link').removeClass('hide');
            $('a.load-more-link').data('offset', offset + 30); /* Update offset for loading more messages */
            $('a.load-more-link').data('folderid', folderid); /* Update folder ID for loading more messages */
            
            for (var i = 0; i < response.results.length; i++) {
                if (response.results[i].itemClass != 'IPM.Appointment') {
                    $('#table-exchange-items tbody').append('<tr> \
                        <td class="text-center"><span class="logo fa fa-envelope"></span></td> \
                        <td>' + response.results[i].from + '</td> \
                        <td>' + response.results[i].subject + '</td> \
                        <td class="text-center">' + moment(response.results[i].received).format('DD/MM/YYYY HH:mm') + '</td> \
                        <td class="text-center"> \
                        <div class="dropdown"> \
                        <button class="btn btn-default dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">Options <span class="caret"></span></button> \
                        <ul class="dropdown-menu dropdown-menu-right"> \
                        <li class="dropdown-header">Download as</li> \
                        <li><a class="dropdown-link" data-action="download-msg" data-itemid="' + response.results[i].id + '" data-mailboxid="' + mailboxid + '" href="' + window.location + '"><i class="fa fa-download"></i> MSG file</a></li> \
                        <li><a class="dropdown-link" data-action="download-pst" data-itemid="' + response.results[i].id + '" data-mailboxid="' + mailboxid + '" href="' + window.location + '"><i class="fa fa-download"></i> PST file</a></li> \
                        <li class="divider"></li> \
                        <li class="dropdown-header">Restore to</li> \
                        <li><a class="dropdown-link" data-action="restore-different" data-itemid="' + response.results[i].id + '" data-mailboxid="' + mailboxid + '" href="' + window.location + '"><i class="fa fa-upload"></i> Different location</a></li> \
                        <li><a class="dropdown-link" data-action="restore-original" data-itemid="' + response.results[i].id + '" data-mailboxid="' + mailboxid + '" href="' + window.location + '"><i class="fa fa-upload"></i> Original location</a></li> \
                        </ul> \
                        </div> \
                        </td> \
                        </tr>');
                } else {
                    $('#table-exchange-items tbody').append('<tr> \
                        <td class="text-center"><span class="logo fa fa-calendar"></span></td> \
                        <td>' + response.results[i].organizer + '</td> \
                        <td>' + response.results[i].subject + '</td> \
                        <td></td> \
                        <td class="text-center"> \
                        <div class="dropdown"> \
                        <button class="btn btn-default dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">Options <span class="caret"></span></button> \
                        <ul class="dropdown-menu dropdown-menu-right"> \
                        <li class="dropdown-header">Download as</li> \
                        <li><a class="dropdown-link" data-action="download-msg" data-itemid="' + response.results[i].id + '" data-mailboxid="' + mailboxid + '" href="' + window.location + '"><i class="fa fa-download"></i> MSG file</a></li> \
                        <li><a class="dropdown-link" data-action="download-pst" data-itemid="' + response.results[i].id + '" data-mailboxid="' + mailboxid + '" href="' + window.location + '"><i class="fa fa-download"></i> PST file</a></li> \
                        <li class="divider"></li> \
                        <li class="dropdown-header">Restore to</li> \
                        <li><a class="dropdown-link" data-action="restore-different" data-itemid="' + response.results[i].id + '" data-mailboxid="' + mailboxid + '" href="' + window.location + '"><i class="fa fa-upload"></i> Different location</a></li> \
                        <li><a class="dropdown-link" data-action="restore-original" data-itemid="' + response.results[i].id + '" data-mailboxid="' + mailboxid + '" href="' + window.location + '"><i class="fa fa-upload"></i> Original location</a></li> \
                        </ul> \
                        </div> \
                        </td> \
                        </tr>');
                }
            }
        } else {
            $('a.load-more-link').addClass('hide');
        }
    });
}
</script>
<?php
} else {
    unset($_SESSION);
    session_destroy();
    header('Location: /index.php');
}
?>
</body>
</html>