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
          <li><a href="onedrive">OneDrive</a></li>
          <li class="active"><a href="sharepoint">SharePoint</a></li>
        </ul>
        <ul class="nav navbar-nav navbar-right">
          <li><a href="#"><span class="fa fa-user"></span> Welcome <i><?php echo $user; ?></i> !</a></li>
          <li id="logout"><a href="#"><span class="fa fa-sign-out"></span> Logout</a></li>
        </ul>
    </div>
</nav>
<div class="container-fluid">
    <link rel="stylesheet" href="css/sharepoint.css" />
    <aside id="sidebar">
        <div class="logo-container"><i class="logo fa fa-share-alt"></i></div>
        <div class="separator"></div>
        <menu class="menu-segment" id="menu">
            <?php
            if (!isset($_SESSION['rid'])) { /* No restore session is running */
                $check = filter_var($user, FILTER_VALIDATE_EMAIL);

                if ($check === false) { /* We are an admin so we list all the organizations in the menu */
                    $oid = $_GET['oid'];
                    $org = $veeam->getOrganizations();
                    
                    echo '<ul id="ul-sharepoint-sites">';
                    
                    for ($i = 0; $i < count($org); $i++) {
                        if (isset($oid) && !empty($oid) && ($oid == $org[$i]['id'])) {
                            echo '<li class="active"><a href="sharepoint/' . $org[$i]['id'] . '">' . $org[$i]['name'] . '</a></li>';
                        } else {
                            echo '<li><a href="sharepoint/' . $org[$i]['id'] . '">' . $org[$i]['name'] . '</a></li>';
                        }
                    }
                    
                    echo '</ul>';
                } else {
                    $org = $veeam->getOrganization();
                    ?>
                    <button class="btn btn-default btn-secondary btn-start-sharepoint-restore" title="Start Restore" data-type="vesp">Start Restore</button><br /><br />
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

                if (strcmp($_SESSION['rtype'], 'vesp') === 0) {
                    $sid = $_GET['sid'];
                    $org = $veeam->getOrganizationID($rid);

                    echo '<span id="span-item-sharepoint"><button class="btn btn-default btn-danger" id="btn-stop-sharepoint-restore" title="Stop Restore">Stop Restore</button></span>';
                    echo '<div class="separator"></div>';

                    if (isset($sid) && !empty($sid)) {
                        $libraries = $veeam->getSharePointContent($rid, $sid, 'libraries');
                        $lists = $veeam->getSharePointContent($rid, $sid, 'lists');
                        $content = array();

                        echo '<a href="sharepoint/' . $org['id'] . '"><i class="fa fa-reply"></i> Parent site</a>';
                        echo '<ul id="ul-sharepoint-sites">';
                        echo '<div class="separator"></div>';

                        for ($i = 0; $i < count($libraries['results']); $i++) {
                            array_push($content, array('name'=> $libraries['results'][$i]['name'], 'id' => $libraries['results'][$i]['id'], 'type' => 'library'));
                        }

                        for ($i = 0; $i < count($lists['results']); $i++) {
                            array_push($content, array('name'=> $lists['results'][$i]['name'], 'id' => $lists['results'][$i]['id'], 'type' => 'list'));
                        }

                        uasort($content, function($a, $b) {
                            return strcmp($a['name'], $b['name']);
                        });

                        foreach ($content as $key => $value) {
                            echo '<li><a data-type="' . $value['type'] . '" href="sharepoint/' . $org['id'] . '/' . $sid . '/' . $value['id'] . '/' . $value['type'] . '">' . $value['name'] . '</a></li>';
                        }

                        echo '</ul>';
                    } else {
                        $sites = $veeam->getSharePointSites($rid);
                        $content = array();

                        for ($i = 0; $i < count($sites['results']); $i++) {
                            array_push($content, array('name'=> $sites['results'][$i]['name'], 'id' => $sites['results'][$i]['id']));
                        }

                        uasort($content, function($a, $b) {
                            return strcmp($a['name'], $b['name']);
                        });

                        foreach ($content as $key => $value) {
                            echo '<li><a href="sharepoint/' . $org['id'] . '/' . $value['id'] . '">' . $value['name'] . '</a></li>';
                        }
                    }
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
            if (strcmp($_SESSION['rtype'], 'vesp') !== 0) {
                if (strcmp($_SESSION['rtype'], 'vex') === 0) {
                    echo '<div class="alert alert-danger alert-dismissible" role="alert"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button><strong>Found a restore session running for Exchange, <br />please terminate that one first if you want to restore Exchange items.</strong></div>';

                    echo '<a href="exchange">Go to running session</a>';
                } else {
                    echo '<div class="alert alert-danger alert-dismissible" role="alert"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button><strong>Found a restore session running for OneDrive, <br />please terminate that one first if you want to restore Exchange items.</strong></div>';

                    echo '<a href="onedrive">Go to running session</a>';
                }

                exit;
            }
        }
        ?>
        </div>
        <div class="row sharepoint-container">
        <?php
        if (!isset($_SESSION['rid'])) { /* No restore session is running */
            if (isset($oid) && !empty($oid)) { /* We got an organization ID so list all available jobs */
                $jobs = $veeam->getJobs($oid);
                
                if (count($jobs) != '0') {
                    for ($i = 0; $i < count($jobs); $i++) {
                        $items = $veeam->getJobSelectedItems($jobs[$i]['id']);

                        if ($items[0]['site'] == 1) {
            ?>
            <h1>SharePoint</h1>
            <div id="div-sharepoint-pitlist">
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
                                echo '<td class="text-center"><span id="span-item-sharepoint-' . $jobs[$i]['id'] . '"><button class="btn btn-default btn-secondary btn-start-sharepoint-restore" title="Start Restore" data-jid="' . $jobs[$i]['id'] . '" data-oid="' . $oid . '" data-pit="' . date('Y.m.d H:i:s', strtotime($jobsession[$j]['creationTime'])) . '" data-type="vesp">Start Restore</button></span></td>';
                                echo '</tr>';
                            }
                        }
                        ?>
                    </tbody>
                </table>
            </div>
            <div class="hide" id="div-sharepoint-default">Select a SharePoint site to list the content.</div>
                    <?php
                        }
                    }
                } else { /* No jobs available for the organization ID */
                    echo '<div id="div-sharepoint-default"><h1>SharePoint</h1>No SharePoint backup jobs found for this organization.</div>';
                }
            } else {
                if ($check === false) { /* No organization has been selected */
                    echo '<h1>SharePoint</h1><div id="div-sharepoint-default">Select an organization to list the restore points.</div>';
                } else {
                    echo '<h1>SharePoint</h1><div id="div-sharepoint-default">Select a point in time and start the restore.</div>';
                }
            }
        } else { /* Restore session is running */
            if (isset($sid) && !empty($sid)) {
                $cid = $_GET['cid'];
                $type = $_GET['type'];
                $name = $veeam->getSharePointSiteName($rid, $sid);

                if (isset($cid) && !empty($cid)) {
                    $folders = $veeam->getSharePointTree($rid, $sid, $cid);

                    if (strcmp($type, 'list') === 0) { /* Lists have folders and items */
                        $items = $veeam->getSharePointTree($rid, $sid, $cid, 'items');
                        $list = $veeam->getSharePointListName($rid, $sid, $cid, 'Lists');
                    } else { /* Libraries have folders and documents */
                        $documents = $veeam->getSharePointTree($rid, $sid, $cid, 'documents');
                        $list = $veeam->getSharePointListName($rid, $sid, $cid, 'Libraries');
                    }

                ?>
                    <h1>SharePoint content for: <em><?php echo $name['name']; ?></em></h1>
                    <table class="table table-bordered table-padding table-striped" id="table-sharepoint-items">
                    <thead>
                        <tr>
                            <?php
                            if (strcmp($type, 'list') === 0) {
                                echo '<th><strong>Title</strong></th>';
                            } else {
                                echo '<th><strong>Name</strong></th>';
                            }
                            ?>
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
                            <?php echo '<i class="far fa-folder"></i> <a class="sharepoint-folder" data-folderid="' . $folders['results'][$i]['id'] . '" data-parentid="index" data-siteid="' . $sid . '" href="sharepoint/' . $org['id'] . '/' . $sid . '/'. $cid . '/' . $type . '#">' . $folders['results'][$i]['name'] . '</a>'; ?><br />
                            <em>Last modified: <?php echo date('d/m/Y H:i', strtotime($folders['results'][$i]['modificationTime'])) . ' (by ' . $folders['results'][$i]['modifiedBy'] . ')'; ?></em>
                            </td>
                            <td></td>
                            <td></td>
                            <td class="text-center">
                                <div class="dropdown">
                                    <button class="btn btn-default dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">Options <span class="caret"></span></button>
                                    <ul class="dropdown-menu dropdown-menu-right">
                                      <li class="dropdown-header">Download as</li>
                                      <li><a class="dropdown-link" data-action="download-zip" data-itemid="<?php echo $folders['results'][$i]['id']; ?>" data-itemname="<?php echo $folders['results'][$i]['name']; ?>" data-siteid="<?php echo $sid; ?>" data-type="folders" href="<?php echo $_SERVER['REQUEST_URI']; ?>#"><i class="fa fa-download"></i> ZIP file</a></li>
                                      <li class="divider"></li>
                                      <li class="dropdown-header">Restore to</li>
                                      <li><a class="dropdown-link" data-action="restore-original" data-itemid="<?php echo $folders['results'][$i]['id']; ?>" data-siteid="<?php echo $sid; ?>" data-type="folders" href="<?php echo $_SERVER['REQUEST_URI']; ?>#"><i class="fa fa-upload"></i> Original location</a></li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                    <?php
                    }

                    if (strcmp($type, 'list') === 0) { /* Lists have folders and items */
                        for ($i = 0; $i < count($items['results']); $i++) {
                        ?>
                            <tr>
                                <td>
                                <?php echo $items['results'][$i]['title']; ?><br />
                                <em>Last modified: <?php echo date('d/m/Y H:i', strtotime($items['results'][$i]['modificationTime'])) . ' (by ' . $items['results'][$i]['modifiedBy'] . ')'; ?></em>
                                </td>
                                <td></td>
                                <td><?php echo $items['results'][$i]['version']; ?></td>
                                <td class="text-center">
                                    <div class="dropdown">
                                        <button class="btn btn-default dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">Options <span class="caret"></span></button>
                                        <ul class="dropdown-menu dropdown-menu-right">
                                          <li><a class="dropdown-link" data-action="restore-original" data-itemid="<?php echo $items['results'][$i]['id']; ?>" data-siteid="<?php echo $sid; ?>" data-type="item" href="<?php echo $_SERVER['REQUEST_URI']; ?>#"><i class="fa fa-upload"></i> Restore item</a></li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        <?php
                        }
                    } else {
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
                                          <li><a class="dropdown-link" data-action="download-file" data-itemid="<?php echo $documents['results'][$i]['id']; ?>" data-itemname="<?php echo $documents['results'][$i]['name']; ?>" data-siteid="<?php echo $sid; ?>" data-type="documents" href="<?php echo $_SERVER['REQUEST_URI']; ?>#"><i class="fa fa-download"></i> Plain file</a></li>
                                          <li><a class="dropdown-link" data-action="download-zip" data-itemid="<?php echo $documents['results'][$i]['id']; ?>" data-itemname="<?php echo $documents['results'][$i]['name']; ?>" data-siteid="<?php echo $sid; ?>" data-type="documents" href="<?php echo $_SERVER['REQUEST_URI']; ?>#"><i class="fa fa-download"></i> ZIP file</a></li>
                                          <li class="divider"></li>
                                          <li class="dropdown-header">Restore to</li>
                                          <li><a class="dropdown-link" data-action="restore-original" data-itemid="<?php echo $documents['results'][$i]['id']; ?>" data-siteid="<?php echo $sid; ?>" data-type="documents" href="<?php echo $_SERVER['REQUEST_URI']; ?>#"><i class="fa fa-upload"></i> Original location</a></li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        <?php
                        }
                    }
                    ?>
                    </tbody>
                </table>
                <?php
                } else {
                    echo '<h1>SharePoint content for: <em>' . $name['name'] .'</em></h1>';
                    echo '<div id="div-sharepoint-default">Select a library or list to view the specific content.</div>';
                }
            } else {
                echo '<h1>SharePoint</h1><div id="div-sharepoint-default">Select a SharePoint site to list the content.</div>';
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
        <input type="hidden" id="restore-original-listname" value="<?php echo $list['name']; ?>"></input>
        <div class="alert alert-warning" role="alert">Warning: this will restore the last version of the item.</div>
        <label for="restore-original-user">Username:</label>
        <input type="text" class="form-control" id="restore-original-user" placeholder="user@example.onmicrosoft.com"></input>
        <br />
        <label for="restore-original-pass">Password:</label>
        <input type="password" class="form-control" id="restore-original-pass" placeholder="password"></input>
        <label for="restore-original-action">If the file exists:</label>
        <select class="form-control" id="restore-original-action">
            <option value="merge">Merge file</option>
            <option value="overwrite">Overwrite file</option>
        </select>
        <label for="restore-original-permissions">Restore permissions:</label>
        <select class="form-control" id="restore-original-permissions">
            <option value="true">Yes</option>
            <option value="false">No</option>
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
/* SharePoint Explorer Buttons */
$(document).on('click', '.btn-start-sharepoint-restore', function(e) {
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
                    window.location.href = 'sharepoint';
                }
            }).modal('show');
        } else {
            $('#infobox').html('<div class="alert alert-danger alert-dismissible" role="alert"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button><strong>' + data + '</strong></div>');
            $(':button').prop('disabled', false); /* Enable all buttons again */
        }
    });
});
$(document).on('click', '#btn-stop-sharepoint-restore', function(e) {
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
                        window.location.href = 'sharepoint';
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
    var siteid = $(this).data("siteid");
    var rid = "<?php echo $rid; ?>";

    if (action == "download-zip") {
        var json = '{ "save" : { "asZip" : "true" } }';

        $.get("veeam.php", {"action" : "exportsharepointitem", "itemid" : itemid, "siteid" : siteid, "rid" : rid, "json" : json, "type" : type}).done(function(data) {
            window.location.href = "download.php?ext=zip&file=" + data + "&name=" + itemname;
        });
    } else if (action == "download-file") {
        var json = '{ "save" : { "asZip" : "false" } }';

        $.get("veeam.php", {"action" : "exportsharepointitem", "itemid" : itemid, "siteid" : siteid, "rid" : rid, "json" : json, "type" : type}).done(function(data) {
            window.location.href = "download.php?ext=plain&file=" + data + "&name=" + itemname;
        });
    } else if (action == "restore-original") {
        $(".modalrestoreoriginal.modal").modal({
            centered : true,
            closable : true,
            onApprove: function(e) {
                var user = $("#restore-original-user").val();
                var pass = $("#restore-original-pass").val();
                var listname = $("#restore-original-listname").val();
                var restoreaction = $("#restore-original-action").val();
                var restorepermissions = $("#restore-original-permissions").val();

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
                        { "userName": "' + user + '", \
                          "userPassword": "' + pass + '", \
                          "list" : "' + listname + '", \
                          "restorePermissions" : "' + restorepermissions + '", \
                          "sendSharedLinksNotification": "true", \
                          "documentVersion" : "last", \
                          "documentLastVersionAction" : "' + restoreaction + '" } \
                        }';

                $.get("veeam.php", {"action" : "restoresharepointitem", "itemid" : itemid, "siteid" : siteid, "rid" : rid, "json" : json, "type" : type}).done(function(data) {
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

/* Folder browser */
$(document).on("click", ".sharepoint-folder", function(e) {
    var folderid = $(this).data("folderid");
    var parentid = $(this).data("parentid");
    var siteid = $(this).data("siteid");
    var rid = "<?php echo $rid; ?>";

    loadFolderItems(folderid, parentid, rid, siteid);
});
$(document).on("click", ".sharepoint-folder-up", function(e) {
    var parentid = $(this).data("parentid");
    var siteid = $(this).data("siteid");
    var rid = "<?php echo $rid; ?>";

    if (parentid == "index") {
        window.location.href = window.location.href.split('#')[0];
        return false;
    } else {
        loadParentFolderItems(parentid, rid, siteid);
    }
});

/* Load more link */
$(document).on("click", ".load-more-link", function(e) {
    var folderid = $(this).data("folderid");
    var siteid = $(this).data("siteid");
    var offset = $(this).data("offset");
    var rid = "<?php echo $rid; ?>";

    loadItems(folderid, siteid, rid, offset);
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

/* SharePoint functions */
/*
 * @param response JSON data
 * @param siteid SharePoint Site ID
 * @param type Documents or items
 */
function fillTableDocuments(response, siteid, type) {
    if (response.results.length != '0') {
        for (var i = 0; i < response.results.length; i++) {
            if (type == 'documents') {
                var size = filesize(response.results[i].size, {round: 2});
            } else {
                var size = "";
            }

            $('#table-sharepoint-items tbody').append('<tr> \
                <td>' + response.results[i].name + '<br /><em>Last modified: ' + moment(response.results[i].modificationTime).format('DD/MM/YYYY HH:mm') + ' (by ' + response.results[i].modifiedBy + ')</em></td> \
                <td>' + size + '</td> \
                <td>' + response.results[i].version + '</td> \
                <td class="text-center"> \
                <div class="dropdown"> \
                <button class="btn btn-default dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">Options <span class="caret"></span></button> \
                <ul class="dropdown-menu dropdown-menu-right"> \
                <li class="dropdown-header">Download as</li> \
                <li><a class="dropdown-link" data-action="download-file" data-itemid="' + response.results[i].id + '" data-itemname="' + response.results[i].id + '"data-siteid="' + siteid + '" href="' + window.location + '"><i class="fa fa-download"></i> Plain file</a></li> \
                <li><a class="dropdown-link" data-action="download-zip" data-itemid="' + response.results[i].id + '" data-itemname="' + response.results[i].id + '"data-siteid="' + siteid + '" href="' + window.location + '"><i class="fa fa-download"></i> ZIP file</a></li> \
                <li class="divider"></li> \
                <li class="dropdown-header">Restore to</li> \
                <li><a class="dropdown-link" data-action="restore-original" data-itemid="' + response.results[i].id + '" data-siteid="' + siteid + '" data-type="documents" href="' + window.location + '"><i class="fa fa-upload"></i> Original location</a></li> \
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
 * @param siteid SharePoint Site ID
 */
function fillTableFolders(response, folderid, siteid) {
    if (response.results.length != '0') {
        for (var i = 0; i < response.results.length; i++) {
            $('#table-sharepoint-items tbody').append('<tr> \
                <td><a class="sharepoint-folder" data-folderid="' + response.results[i].id + '" data-parentid="' + folderid +'" data-siteid="<?php echo $sid; ?>" href="'+ window.location +'">' + response.results[i].name + '</a><br /><em>Last modified: ' + moment(response.results[i].modificationTime).format('DD/MM/YYYY HH:mm') + ' (by ' + response.results[i].modifiedBy + ')</em></td> \
                <td></td> \
                <td></td> \
                <td class="text-center"> \
                <div class="dropdown"> \
                <button class="btn btn-default dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">Options <span class="caret"></span></button> \
                <ul class="dropdown-menu dropdown-menu-right"> \
                <li class="dropdown-header">Download as</li> \
                <li><a class="dropdown-link" data-action="download-file" data-itemid="' + response.results[i].id + '" data-itemname="' + response.results[i].id + '"data-siteid="' + siteid + '" href="' + window.location + '"><i class="fa fa-download"></i> Plain file</a></li> \
                <li><a class="dropdown-link" data-action="download-zip" data-itemid="' + response.results[i].id + '" data-itemname="' + response.results[i].id + '"data-siteid="' + siteid + '" href="' + window.location + '"><i class="fa fa-download"></i> ZIP file</a></li> \
                <li class="divider"></li> \
                <li class="dropdown-header">Restore to</li> \
                <li><a class="dropdown-link" data-action="restore-original" data-itemid="' + response.results[i].id + '" data-siteid="' + siteid + '" data-type="documents" href="' + window.location + '"><i class="fa fa-upload"></i> Original location</a></li> \
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
 * @param siteid SharePoint Site ID
 */
function loadFolderItems(folderid, parentid, rid, siteid) { /* Used for navigation to next folder */
    var responsedocuments, responsefolders;

    /* First we load the folders */
    $.get("veeam.php", {"action" : "getsharepointitemsbyfolder", "folderid" : folderid, "rid" : rid, "siteid" : siteid, "type" : "folders"}).done(function(data) {
        responsefolders = JSON.parse(data);
    });

    <?php
    if (strcmp($type, 'list') === 0) {
    ?>
    /* Second we load the items */
    $.get("veeam.php", {"action" : "getsharepointitemsbyfolder", "folderid" : folderid, "rid" : rid, "siteid" : siteid, "type" : "items"}).done(function(data) {
        responsedocuments = JSON.parse(data);
    });
    <?php
    } else {
    ?>
     /* Second we load the documents */
    $.get("veeam.php", {"action" : "getsharepointitemsbyfolder", "folderid" : folderid, "rid" : rid, "siteid" : siteid, "type" : "documents"}).done(function(data) {
        responsedocuments = JSON.parse(data);
    });
    <?php
    }
     ?>

    /* We delay the output to assure our call is performed */
    setTimeout(function(e) {
        $('#table-sharepoint-items tbody').empty();
        $('#table-sharepoint-items tbody').append('<tr><td colspan="4"><a class="sharepoint-folder-up" data-parentid="' + parentid + '" data-siteid="<?php echo $sid; ?>" href="' + window.location + '"><i class="fa fa-reply"></i> Parent directory</a></td></tr>');

        if (typeof responsefolders !== undefined) {
            fillTableFolders(responsefolders, folderid, siteid);
        }

        if (typeof responsedocuments !== undefined) {
            <?php
            if (strcmp($type, 'list') === 0) {
            ?>
            fillTableDocuments(responsedocuments, siteid, 'items');
            <?php
            } else {
            ?>
            fillTableDocuments(responsedocuments, siteid, 'documents');
            <?php
            }
            ?>
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
 * @param siteid SharePoint Site ID
 */
function loadParentFolderItems(parentid, rid, siteid) { /* Used for navigation to parent folder */
    var newparentid, parentdata, parenturl, responsedocuments, responsefolders;

    /* First we load the folders */
    $.get("veeam.php", {"action" : "getsharepointitemsbyfolder", "folderid" : parentid, "rid" : rid, "siteid" : siteid, "type" : "folders"}).done(function(data) {
        responsefolders = JSON.parse(data);
    });

    /* Second we load the documents */
    $.get("veeam.php", {"action" : "getsharepointitemsbyfolder", "folderid" : parentid, "rid" : rid, "siteid" : siteid, "type" : "documents"}).done(function(data) {
        responsedocuments = JSON.parse(data);
    });

    /* We delay the output to assure our call is performed */
    setTimeout(function(e) {
        if (typeof responsefolders !== undefined && responsefolders.results.length != '0') {
            parenturl = responsefolders.results[0]._links.parent.href;
            newparentid = parenturl.split("/").pop();

            $.get("veeam.php", {"action" : "getsharepointparentfolder", "folderid" : newparentid, "rid" : rid, "siteid" : siteid, "type" : "folders"}).done(function(data) {
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

            $.get("veeam.php", {"action" : "getsharepointparentfolder", "folderid" : newparentid, "rid" : rid, "siteid" : siteid, "type" : "documents"}).done(function(data) {
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
            $('#table-sharepoint-items tbody').empty();
            $('#table-sharepoint-items tbody').append('<tr><td colspan="4"><a class="sharepoint-folder-up" data-parentid="' + newparentid + '" data-siteid="<?php echo $sid; ?>" href="' + window.location + '"><i class="fa fa-reply"></i> Parent directory</a></td></tr>');

            if (typeof responsefolders !== undefined) {
                fillTableFolders(responsefolders, parentid, siteid);
            }

            if (typeof responsedocuments !== undefined) {
                <?php
                if (strcmp($type, 'list') === 0) {
                ?>
                fillTableDocuments(responsedocuments, siteid, 'items');
                <?php
                } else {
                ?>
                fillTableDocuments(responsedocuments, siteid, 'documents');
                <?php
                }
                ?>
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
 * @param siteid SharePoint Site ID
 * @param rid Restore session ID
 * @param offset Offset
 */
function loadItems(folderid, siteid, rid, offset) { /* Used for loading additional items in folder */
    var responsedocuments, responsefolders;

    $.get("veeam.php", {"action" : "getsharepointitems", "folderid" : folderid, "rid" : rid, "siteid" : siteid, "offset" : offset, "type" : "folders"}).done(function(data) {
        responsefolders = JSON.parse(data);
    });

    $.get("veeam.php", {"action" : "getsharepointitems", "folderid" : folderid, "rid" : rid, "siteid" : siteid, "offset" : offset, "type" : "documents"}).done(function(data) {
        responsedocuments = JSON.parse(data);
    });

    /* We delay the output to assure our call is performed */
    setTimeout(function(e) {
        if (typeof responsefolders !== undefined) {
            fillTableFolders(responsefolders, folderid, siteid);
        }

        if (typeof responsedocuments !== undefined) {
            fillTableDocuments(responsedocuments, siteid);
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