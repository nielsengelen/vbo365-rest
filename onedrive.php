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
          <li><a href="exchange">Exchange</a></li>
          <li class="active"><a href="onedrive">OneDrive</a></li>
          <li><a href="sharepoint">SharePoint</a></li>
        </ul>
        <ul class="nav navbar-nav navbar-right">
          <li><a href="#"><span class="fa fa-user"></span> Welcome <i><?php echo $user; ?></i> !</a></li>
          <li id="logout"><a href="#"><span class="fa fa-sign-out"></span> Logout</a></li>
        </ul>
    </div>
</nav>
<div class="container-fluid">
    <link rel="stylesheet" href="css/onedrive.css" />
    <aside id="sidebar">
        <div class="logo-container"><i class="logo fa fa-cloud"></i></div>
        <div class="separator"></div>
        <menu class="menu-segment" id="menu">
            <?php
            if (!isset($_SESSION['rid'])) { /* No restore session is running */
                $check = filter_var($user, FILTER_VALIDATE_EMAIL);

                if ($check === false) { /* We are an admin so we list all the organizations in the menu */
                    $oid = $_GET['oid'];
                    $org = $veeam->getOrganizations();
                    
                    echo '<ul id="ul-onedrive-users">';
                    
                    for ($i = 0; $i < count($org); $i++) {
                        if (isset($oid) && !empty($oid) && ($oid == $org[$i]['id'])) {
                            echo '<li class="active"><a href="onedrive/' . $org[$i]['id'] . '">' . $org[$i]['name'] . '</a></li>';
                        } else {
                            echo '<li><a href="onedrive/' . $org[$i]['id'] . '">' . $org[$i]['name'] . '</a></li>';
                        }
                    }
                    
                    echo '</ul>';
                } else {
                    $org = $veeam->getOrganization();
                    ?>
                    <button class="btn btn-default btn-secondary btn-start-onedrive-restore" title="Start Restore" data-type="veod">Start Restore</button><br /><br />
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

                if (strcmp($_SESSION['rtype'], 'veod') === 0) {
                    $uid = $_GET['uid'];
                    $content = array();
                    $org = $veeam->getOrganizationID($rid);
                    $users = $veeam->getOneDrives($rid);

                    for ($i = 0; $i < count($users['results']); $i++) {
                        array_push($content, array('name'=> $users['results'][$i]['name'], 'id' => $users['results'][$i]['id']));
                    }

                    uasort($content, function($a, $b) {
                        return strcmp($a['name'], $b['name']);
                    });

                    echo '<span id="span-item-onedrive"><button class="btn btn-default btn-danger" id="btn-stop-onedrive-restore" title="Stop Restore">Stop Restore</button></span>';
                    echo '<div class="separator"></div>';
                    echo '<ul id="ul-onedrive-users">';

                    foreach ($content as $key => $value) {
                        if (isset($uid) && !empty($uid) && ($uid == $value['id'])) {
                            echo '<li class="active"><a href="onedrive/' . $org['id'] . '/' . $value['id'] . '">' . $value['name'] . '</a></li>';
                        } else {
                            echo '<li><a href="onedrive/' . $org['id'] . '/' . $value['id'] . '">' . $value['name'] . '</a></li>';
                        }
                    }

                    echo '</ul>';
                } else {
                   echo 'Found another session running, <br />please terminate that one first if you want to restore OneDrive items.';
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
            if (strcmp($_SESSION['rtype'], 'veod') !== 0) {
                if (strcmp($_SESSION['rtype'], 'vesp') === 0) {
                    echo '<div class="alert alert-danger alert-dismissible" role="alert"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button><strong>Found a restore session running for SharePoint, <br />please terminate that one first if you want to restore OneDrive items.</strong></div>';

                    echo '<a href="sharepoint">Go to running session</a>';
                } else {
                    echo '<div class="alert alert-danger alert-dismissible" role="alert"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button><strong>Found a restore session running for Exchange, <br />please terminate that one first if you want to restore OneDrive items.</strong></div>';

                    echo '<a href="exchange">Go to running session</a>';
                }

                exit;
            }
        }
        ?>
        </div>
        <div class="row onedrive-container">
        <?php    
        if (!isset($_SESSION['rid'])) { /* No restore session is running */
            if (isset($oid) && !empty($oid)) { /* We got an organization ID so list all available jobs */
                $jobs = $veeam->getJobs($oid);
                
                if (count($jobs) != '0') {
                    for ($i = 0; $i < count($jobs); $i++) {
                        $items = $veeam->getJobSelectedItems($jobs[$i]['id']);
                        
                        if ($items[0]['oneDrive'] == 1) {
            ?>
            <h1>OneDrive</h1>
            <div id="div-onedrive-pitlist">
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
                                echo '<td class="text-center"><span id="span-item-onedrive-' . $jobs[$i]['id'] . '"><button class="btn btn-default btn-secondary btn-start-onedrive-restore" title="Start Restore" data-jid="' . $jobs[$i]['id'] . '" data-oid="' . $oid . '" data-pit="' . date('Y.m.d H:i:s', strtotime($jobsession[$j]['creationTime'])) . '" data-type="veod">Start Restore</button></span></td>';
                                echo '</tr>';
                            }
                        }
                        ?>
                    </tbody>
                </table>
            </div>
            <div class="hide" id="div-onedrive-default">Select a OneDrive user to list the content.</div>
                    <?php
                        }
                    }
                } else { /* No jobs available for the organization ID */
                    echo '<h1>OneDrive</h1><div id="div-onedrive-default">No OneDrive backup jobs found for this organization.</div>';
                }
            } else { /* No organization has been selected */
                if ($check === false) { /* No organization has been selected */
                    echo '<h1>OneDrive</h1><div id="div-onedrive-default">Select an organization to list the restore points.</div>';
                } else {
                    echo '<h1>OneDrive</h1><div id="div-onedrive-default">Select a point in time and start the restore.</div>';
                }
            }
        } else { /* Restore session is running */
            if (isset($uid) && !empty($uid)) {
                $owner = $veeam->getOneDriveID($rid, $uid);
                $folders = $veeam->getOneDriveTree($rid, $uid);
                $documents = $veeam->getOneDriveTree($rid, $uid, 'documents');

                if ((count($folders['results']) != '0') || (count($documents['results']) != '0')) {
                ?>
                <h1>OneDrive content for: <em><?php echo $owner['name']; ?></em></h1>
                <table class="table table-bordered table-padding table-striped" id="table-onedrive-items">
                    <thead>
                        <tr>
                            <th><strong>Name</strong></th>
                            <th><strong>Size</strong></th>
                            <th><strong>Version</strong></th>
                            <th class="text-center"><strong>Options</strong></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    for ($i = 0; $i < count($folders['results']); $i++) {
                    ?>
                        <tr>
                            <td>
                            <?php echo '<i class="far fa-folder"></i> <a class="onedrive-folder" data-folderid="' . $folders['results'][$i]['id'] . '" data-parentid="index" data-userid="' . $uid . '" href="onedrive/' . $org['id'] . '/' . $uid . '#">' . $folders['results'][$i]['name'] . '</a>'; ?><br />
                            <em>Last modified: <?php echo date('d/m/Y H:i', strtotime($folders['results'][$i]['modificationTime'])) . ' (by ' . $folders['results'][$i]['modifiedBy'] . ')'; ?></em>
                            </td>
                            <td></td>
                            <td><?php echo $folders['results'][$i]['version']; ?></td>
                            <td class="text-center">
                                <div class="dropdown">
                                    <button class="btn btn-default dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">Options <span class="caret"></span></button>
                                    <ul class="dropdown-menu dropdown-menu-right">
                                      <li class="dropdown-header">Download as</li>
                                      <li><a class="dropdown-link" data-action="download-zip" data-itemid="<?php echo $folders['results'][$i]['id']; ?>" data-itemname="<?php echo $folders['results'][$i]['name']; ?>" data-userid="<?php echo $uid; ?>" data-type="folders" href="<?php echo $_SERVER['REQUEST_URI']; ?>#"><i class="fa fa-download"></i> ZIP file</a></li>
                                      <li class="divider"></li>
                                      <li class="dropdown-header">Restore to</li>
                                      <li><a class="dropdown-link" data-action="restore-original" data-itemid="<?php echo $folders['results'][$i]['id']; ?>" data-userid="<?php echo $uid; ?>" data-type="folders" href="<?php echo $_SERVER['REQUEST_URI']; ?>#"><i class="fa fa-upload"></i> Original location</a></li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                    <?php
                    }

                    for ($i = 0; $i < count($documents['results']); $i++) {
                    ?>
                        <tr>
                            <td>
                            <i class="far fa-file"></i> <?php echo $documents['results'][$i]['name']; ?><br />
                            <em>Last modified: <?php echo date('d/m/Y H:i', strtotime($documents['results'][$i]['modificationTime'])) . ' (by ' . $documents['results'][$i]['modifiedBy'] . ')'; ?></em>
                            </td>
                            <td><script>document.write(filesize(<?php echo $documents['results'][$i]['size']; ?>, {round: 2}));</script></td>
                            <td><?php echo $documents['results'][$i]['version']; ?></td>
                            <td class="text-center">
                                <div class="dropdown">
                                    <button class="btn btn-default dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">Options <span class="caret"></span></button>
                                    <ul class="dropdown-menu dropdown-menu-right">
                                      <li class="dropdown-header">Download as</li>
                                      <li><a class="dropdown-link" data-action="download-file" data-itemid="<?php echo $documents['results'][$i]['id']; ?>" data-itemname="<?php echo $documents['results'][$i]['name']; ?>" data-userid="<?php echo $uid; ?>" data-type="documents" href="<?php echo $_SERVER['REQUEST_URI']; ?>#"><i class="fa fa-download"></i> Plain file</a></li>
                                      <li><a class="dropdown-link" data-action="download-zip" data-itemid="<?php echo $documents['results'][$i]['id']; ?>" data-itemname="<?php echo $documents['results'][$i]['name']; ?>" data-userid="<?php echo $uid; ?>" data-type="documents" href="<?php echo $_SERVER['REQUEST_URI']; ?>#"><i class="fa fa-download"></i> ZIP file</a></li>
                                      <li class="divider"></li>
                                      <li class="dropdown-header">Restore to</li>
                                      <li><a class="dropdown-link" data-action="restore-original" data-itemid="<?php echo $documents['results'][$i]['id']; ?>" data-userid="<?php echo $uid; ?>" data-type="documents" href="<?php echo $_SERVER['REQUEST_URI']; ?>#"><i class="fa fa-upload"></i> Original location</a></li>
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
                    if (count($documents['results']) == '30') { /* If we have 30 items from the first request, show message to load additional items */
                    ?>
                        <a class="btn btn-default load-more-link" data-folderid="null" data-userid="<?php echo $uid; ?>" data-offset="<?php echo count($documents['results'])+1; ?>" href="<?php echo $_SERVER['REQUEST_URI']; ?>#">Load more items</a>
                    <?php
                    } else { /* Else hide the load more items message */
                    ?>
                        <a class="btn btn-default hide load-more-link" data-folderid="null" data-userid="<?php echo $uid; ?>" data-offset="<?php echo count($documents['results'])+1; ?>" href="<?php echo $_SERVER['REQUEST_URI']; ?>#">Load more items</a>
                    <?php
                    }
                    ?>
                </div>
                <?php
                } else {
                    echo '<h1>OneDrive</h1><div id="div-onedrive-default">No items available for this OneDrive user.</div>';
                }
            } else {
                echo '<h1>OneDrive</h1><div id="div-onedrive-default">Select a OneDrive user to list the content.</div>';
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
        <div class="alert alert-warning" role="alert">Warning: this will restore the last version of the item.</div>
        <label for="restore-original-user">Username:</label>
        <input type="text" class="form-control" id="restore-original-user" placeholder="user@example.onmicrosoft.com"></input>
        <br />
        <label for="restore-original-pass">Password:</label>
        <input type="password" class="form-control" id="restore-original-pass" placeholder="password"></input>
        <label for="restore-original-action">If the file exists:</label>
        <select class="form-control" id="restore-original-action">
            <option value="keep">Keep original file</option>
            <option value="overwrite">Overwrite file</option>
        </select>
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
    <div class="header text-center">Restore session has stopped.</div>
    <div class="content">
      <p>The restore session has stopped successfully.</p>
    </div>
    <div class="actions text-center">
      <div class="ui positive approve button"><i class="checkmark icon"></i> Ok</div>
    </div>
</div>

<script>
/* Onedrive Explorer Buttons */
$(document).on('click', '.btn-start-onedrive-restore', function(e) {
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
                    window.location.href = 'onedrive';
                }
            }).modal('show');
        } else {
            $('#infobox').html('<div class="alert alert-danger alert-dismissible" role="alert"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button><strong>' + data + '</strong></div>');
            $(':button').prop('disabled', false); /* Enable all buttons again */
        }
    });
});
$(document).on('click', '#btn-stop-onedrive-restore', function(e) {
    var rid = "<?php echo $rid; ?>"; /* Restore Session ID */

    e.preventDefault();

    $('.modalstoprestorefirst.modal').modal({
        centered : true,
        closable : true,
        onApprove: function(e) {
            $.get('veeam.php', {'action' : 'stopexplorer', 'id' : rid}).done(function(data) {
                $('.modalstoprestoresecond.modal').modal({
                    centered : true,
                    closable : false,
                    onApprove: function(e) {
                        window.location.href = 'onedrive';
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
    var itemname = $(this).data("itemname");
    var type = $(this).data("type");
    var userid = $(this).data("userid");
    var rid = "<?php echo $rid; ?>";

    if (action == "download-zip") {
        var json = '{ "save" : { "asZip" : "true" } }';

        $.get("veeam.php", {"action" : "exportonedriveitem", "itemid" : itemid, "userid" : userid, "rid" : rid, "json" : json, "type" : type}).done(function(data) {
            window.location.href = "download.php?ext=zip&file=" + data + "&name=" + itemname;
        });
    } else if (action == "download-file") {
        var json = '{ "save" : { "asZip" : "false" } }';

        $.get("veeam.php", {"action" : "exportonedriveitem", "itemid" : itemid, "userid" : userid, "rid" : rid, "json" : json, "type" : type}).done(function(data) {
            window.location.href = "download.php?ext=plain&file=" + data + "&name=" + itemname;
        });
    } else if (action == "restore-original") {
        $(".modalrestoreoriginal.modal").modal({
            centered : true,
            closable : true,
            onApprove: function(e) {
                var user = $("#restore-original-user").val();
                var pass = $("#restore-original-pass").val();
                var restoreaction = $("#restore-original-action").val();

                if (typeof user === undefined || !user) {
                    $("#infobox").html('<div class="alert alert-danger alert-dismissible" role="alert"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button><strong>Restore failed: no username defined.</strong></div>');
                    return;
                }

                if (typeof pass === undefined || !pass) {
                    $("#infobox").html('<div class="alert alert-danger alert-dismissible" role="alert"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button><strong>Restore failed: no password defined.</strong></div>');
                    return;
                }

                $("#infobox").slideDown();
                $("#infobox").html('<div class="alert alert-info alert-dismissible" role="alert"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button><strong>Restore in progress...</strong></div>');

                var json = '{ "restoretoOriginallocation": \
                        { "userName": "' + user + '", \
                          "userPassword": "' + pass + '", \
                          "DocumentVersion" : "last", \
                          "DocumentAction" : "' + restoreaction + '" } \
                        }';

                $.get("veeam.php", {"action" : "restoreonedriveitem", "itemid" : itemid, "userid" : userid, "rid" : rid, "json" : json, "type" : type}).done(function(data) {
                    $("#infobox").html('<div class="alert alert-info alert-dismissible" role="alert"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button><strong>' + data + '</strong></div>');
                });
            },
            onDeny   : function(e){
              return;
            },
        }).modal("show");
    }
});

/* Folder browser */
$(document).on("click", ".onedrive-folder", function(e) {
    var folderid = $(this).data("folderid");
    var parentid = $(this).data("parentid");
    var userid = $(this).data("userid");
    var rid = "<?php echo $rid; ?>";

    loadFolderItems(folderid, parentid, rid, userid);
});
$(document).on("click", ".onedrive-folder-up", function(e) {
    var parentid = $(this).data("parentid");
    var userid = $(this).data("userid");
    var rid = "<?php echo $rid; ?>";

    if (parentid == "index") {
        window.location.href = window.location.href.split('#')[0];
        return false;
    } else {
        loadParentFolderItems(parentid, rid, userid);
    }
});

/* Load more link */
$(document).on("click", ".load-more-link", function(e) {
    var folderid = $(this).data("folderid");
    var userid = $(this).data("userid");
    var offset = $(this).data("offset");
    var rid = "<?php echo $rid; ?>";

    loadItems(folderid, userid, rid, offset);
});

/* Warn user if session is running and fade out infobox */
$("#infobox").fadeTo(2000, 500).slideUp(500, function(e) {
    $("#infobox").slideUp(500);
});

/* Used for stop restore session modal */
$(".coupled.modal").modal({
    allowMultiple: false
});
<?php
}
?>

/* OneDrive functions */
/*
 * @param response JSON data
 * @param userid User ID
 */
function fillTableDocuments(response, userid) {
    if (response.results.length != '0') {
        for (var i = 0; i < response.results.length; i++) {
            $('#table-onedrive-items tbody').append('<tr> \
                <td>' + response.results[i].name + '<br /><em>Last modified: ' + moment(response.results[i].modificationTime).format('DD/MM/YYYY HH:mm') + ' (by ' + response.results[i].modifiedBy + ')</em></td> \
                <td>' + filesize(response.results[i].size, {round: 2}) + '</td> \
                <td>' + response.results[i].version + '</td> \
                <td class="text-center"> \
                <div class="dropdown"> \
                <button class="btn btn-default dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">Options <span class="caret"></span></button> \
                <ul class="dropdown-menu dropdown-menu-right"> \
                <li class="dropdown-header">Download as</li> \
                <li><a class="dropdown-link" data-action="download-file" data-itemid="' + response.results[i].id + '" data-itemname="' + response.results[i].id + '"data-userid="' + userid + '" href="' + window.location + '"><i class="fa fa-download"></i> Plain file</a></li> \
                <li><a class="dropdown-link" data-action="download-zip" data-itemid="' + response.results[i].id + '" data-itemname="' + response.results[i].id + '"data-userid="' + userid + '" href="' + window.location + '"><i class="fa fa-download"></i> ZIP file</a></li> \
                <li class="divider"></li> \
                <li class="dropdown-header">Restore to</li> \
                <li><a class="dropdown-link" data-action="restore-original" data-itemid="' + response.results[i].id + '" data-userid="' + userid + '" data-type="documents" href="' + window.location + '"><i class="fa fa-upload"></i> Original location</a></li> \
                </ul> \
                </div> \
                </td> \
                </tr>');
        }
    }
}

/*
 * @param response JSON data
 * @param folderid Folder ID
 * @param userid User ID
 */
function fillTableFolders(response, folderid, userid) {
    if (response.results.length != '0') {
        for (var i = 0; i < response.results.length; i++) {
            $('#table-onedrive-items tbody').append('<tr> \
                <td><a class="onedrive-folder" data-folderid="' + response.results[i].id + '" data-parentid="' + folderid +'" data-userid="<?php echo $uid; ?>" href="'+ window.location +'">' + response.results[i].name + '</a><br /><em>Last modified: ' + moment(response.results[i].modificationTime).format('DD/MM/YYYY HH:mm') + ' (by ' + response.results[i].modifiedBy + ')</em></td> \
                <td></td> \
                <td></td> \
                <td class="text-center"> \
                <div class="dropdown"> \
                <button class="btn btn-default dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">Options <span class="caret"></span></button> \
                <ul class="dropdown-menu dropdown-menu-right"> \
                <li class="dropdown-header">Download as</li> \
                <li><a class="dropdown-link" data-action="download-file" data-itemid="' + response.results[i].id + '" data-itemname="' + response.results[i].id + '"data-userid="' + userid + '" href="' + window.location + '"><i class="fa fa-download"></i> Plain file</a></li> \
                <li><a class="dropdown-link" data-action="download-zip" data-itemid="' + response.results[i].id + '" data-itemname="' + response.results[i].id + '"data-userid="' + userid + '" href="' + window.location + '"><i class="fa fa-download"></i> ZIP file</a></li> \
                <li class="divider"></li> \
                <li class="dropdown-header">Restore to</li> \
                <li><a class="dropdown-link" data-action="restore-original" data-itemid="' + response.results[i].id + '" data-userid="' + userid + '" data-type="documents" href="' + window.location + '"><i class="fa fa-upload"></i> Original location</a></li> \
                </ul> \
                </div> \
                </td> \
                </tr>');
        }
    }
}

/*
 * @param folderid Folder ID
 * @param parentid Parent Folder ID
 * @param rid Restore session ID
 * @param userid User ID
 */
function loadFolderItems(folderid, parentid, rid, userid) { /* Used for navigation to next folder */
    var responsedocuments, responsefolders;

    /* First we load the folders */
    $.get("veeam.php", {"action" : "getonedriveitemsbyfolder", "folderid" : folderid, "rid" : rid, "userid" : userid, "type" : "folders"}).done(function(data) {
        responsefolders = JSON.parse(data);
    });

    /* Second we load the documents */
    $.get("veeam.php", {"action" : "getonedriveitemsbyfolder", "folderid" : folderid, "rid" : rid, "userid" : userid, "type" : "documents"}).done(function(data) {
        responsedocuments = JSON.parse(data);
    });

    /* We delay the output to assure our call is performed */
    setTimeout(function(e) {
        $('#table-onedrive-items tbody').empty();
        $('#table-onedrive-items tbody').append('<tr><td colspan="4"><a class="onedrive-folder-up" data-parentid="' + parentid + '" data-userid="<?php echo $uid; ?>" href="' + window.location + '"><i class="fa fa-reply"></i> Parent directory</a></td></tr>');

        if (typeof responsefolders !== undefined) {
            fillTableFolders(responsefolders, folderid, userid);
        }

        if (typeof responsedocuments !== undefined) {
            fillTableDocuments(responsedocuments, userid);
        }

        if (responsefolders.results.length == '30' || responsedocuments.results.length == '30') {
            $('a.load-more-link').removeClass('hide');
            $('a.load-more-link').data('offset', 30); /* Update offset for loading more items */
            $('a.load-more-link').data('folderid', folderid); /* Update folder ID for loading more items */
        } else {
            $('a.load-more-link').addClass('hide');
        }
    }, 2000);
}

/*
 * @param parentid Parent Folder ID
 * @param rid Restore session ID
 * @param userid User ID
 */
function loadParentFolderItems(parentid, rid, userid) { /* Used for navigation to parent folder */
    var newparentid, parentdata, parenturl, responsedocuments, responsefolders;

    /* First we load the folders */
    $.get("veeam.php", {"action" : "getonedriveitemsbyfolder", "folderid" : parentid, "rid" : rid, "userid" : userid, "type" : "folders"}).done(function(data) {
        responsefolders = JSON.parse(data);
    });

    /* Second we load the documents */
    $.get("veeam.php", {"action" : "getonedriveitemsbyfolder", "folderid" : parentid, "rid" : rid, "userid" : userid, "type" : "documents"}).done(function(data) {
        responsedocuments = JSON.parse(data);
    });

    /* We delay the output to assure our call is performed */
    setTimeout(function(e) {
        if (typeof responsefolders !== undefined && responsefolders.results.length != '0') {
            parenturl = responsefolders.results[0]._links.parent.href;
            newparentid = parenturl.split("/").pop();

            $.get("veeam.php", {"action" : "getonedriveparentfolder", "folderid" : newparentid, "rid" : rid, "userid" : userid, "type" : "folders"}).done(function(data) {
                parentdata = JSON.parse(data);

                if (parentdata === undefined || parentdata._links.parent === undefined) {
                    newparentid = "index";
                } else {
                    parenturl = parentdata._links.parent.href;
                    newparentid = parenturl.split("/").pop();
                }
            });
        } else if (typeof responsedocuments !== undefined && responsedocuments.results.length != '0') {
            parenturl = responsedocuments.results[0]._links.parent.href;
            newparentid = parenturl.split("/").pop();

            $.get("veeam.php", {"action" : "getonedriveparentfolder", "folderid" : newparentid, "rid" : rid, "userid" : userid, "type" : "documents"}).done(function(data) {
                parentdata = JSON.parse(data);
                
                if (parentdata === undefined || parentdata._links.parent === undefined) {
                    newparentid = "index";
                } else {
                    parenturl = parentdata._links.parent.href;
                    newparentid = parenturl.split("/").pop();
                }
            });
        } else {
            return false;
        }

        setTimeout(function(e) {
            $('#table-onedrive-items tbody').empty();
            $('#table-onedrive-items tbody').append('<tr><td colspan="4"><a class="onedrive-folder-up" data-parentid="' + newparentid + '" data-userid="<?php echo $uid; ?>" href="' + window.location + '"><i class="fa fa-reply"></i> Parent directory</a></td></tr>');

            if (typeof responsefolders !== undefined) {
                fillTableFolders(responsefolders, parentid, userid);
            }

            if (typeof responsedocuments !== undefined) {
                fillTableDocuments(responsedocuments, userid);
            }

            if (responsefolders.results.length == '30' || responsedocuments.results.length == '30') {
                $('a.load-more-link').removeClass('hide');
                $('a.load-more-link').data('offset', 30); /* Update offset for loading more items */
                $('a.load-more-link').data('folderid', folderid); /* Update folder ID for loading more items */
            } else {
                $('a.load-more-link').addClass('hide');
            }
        }, 1000);
    }, 1000);
}

/*
 * @param userid User ID
 * @param rid Restore session ID
 * @param offset Offset
 */
function loadItems(folderid, userid, rid, offset) { /* Used for loading additional items in folder */
    var responsedocuments, responsefolders;

    $.get("veeam.php", {"action" : "getonedriveitems", "folderid" : folderid, "rid" : rid, "userid" : userid, "offset" : offset, "type" : "folders"}).done(function(data) {
        responsefolders = JSON.parse(data);
    });

    $.get("veeam.php", {"action" : "getonedriveitems", "folderid" : folderid, "rid" : rid, "userid" : userid, "offset" : offset, "type" : "documents"}).done(function(data) {
        responsedocuments = JSON.parse(data);
    });

    /* We delay the output to assure our call is performed */
    setTimeout(function(e) {
        if (typeof responsefolders !== undefined) {
            fillTableFolders(responsefolders, folderid, userid);
        }
        
        if (typeof responsedocuments !== undefined) {
            fillTableDocuments(responsedocuments, userid);
        }

        if (responsefolders.results.length == '30' || responsedocuments.results.length == '30') {
            $('a.load-more-link').removeClass('hide');
            $('a.load-more-link').data('offset', offset + 30); /* Update offset for loading more items */
            $('a.load-more-link').data('folderid', folderid); /* Update folder ID for loading more items */
        } else {
            $('a.load-more-link').addClass('hide');
        }
    }, 2000);
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