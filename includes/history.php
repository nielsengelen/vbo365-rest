<?php
require_once('../config.php');
require_once('../veeam.class.php');

session_start();

$veeam = new VBO($host, $port);
$veeam->setToken($_SESSION['token']);

$sessions = $veeam->getSessions();
$time = array();

for ($i = 0; $i < count($sessions['results']); $i++) {
    array_push($time, array('name'=> $sessions['results'][$i]['name'], 'organization' => $sessions['results'][$i]['organization'], 'result' => $sessions['results'][$i]['result'], 'creationTime' => $sessions['results'][$i]['creationTime'], 'endTime' => $sessions['results'][$i]['endTime'], 'id' => $sessions['results'][$i]['id']));
}

usort($time, function($a, $b) { /* Sort the default list by start time (last one first) */
  $ad = new DateTime($a['creationTime']);
  $bd = new DateTime($b['creationTime']);

  if ($ad == $bd) {
    return 0;
  }

  return $ad > $bd ? -1 : 1;
});
?>
<div class="main-container">
    <h1>History: restore sessions</h1>
    <input class="form-control search-hover" id="filter-history" placeholder="Filter history..." />
    <br />
    <?php
    if (count($sessions) != '0') {
    ?>
    <table class="table table-bordered table-padding table-striped" id="table-sessions">
        <thead>
            <tr>
                <th><strong>Name</strong></th>
                <th><strong>Organization</strong></th>
                <th><strong>Status</strong></th>
                <th><strong>Start Time</strong></th>
                <th><strong>End Time</strong></th>
                <th class="text-center"><strong>Details</strong></th>
            </tr>
        </thead>
        <tbody>
            <?php    
            foreach ($time as $key => $value) {
            ?>
            <tr>
                <td><?php echo $value['name']; ?></td>
                <td><?php echo $value['organization']; ?></td>
                <td>
                <?php
                if (strtolower($value['result']) == 'failed') {
                    echo '<span class="label label-danger">' . $value['result'] . '</span>';
                } else {
                    echo '<span class="label label-'.strtolower($value['result']).'">' . $value['result'] . '</span>';
                }
                ?>
                </td>
                <td><?php echo date('d/m/Y H:i T', strtotime($value['creationTime'])); ?></td>
                <td><?php echo date('d/m/Y H:i T', strtotime($value['endTime'])); ?></td>
                <td class="text-center"><a href="#" class="item" data-sessionid="<?php echo $value['id']; ?>" onClick="return false;">View</a></td>
                </tr>
            <?php
            }
            ?>
        </tbody>
    </table>
<?php
} else {
    echo 'No restore sessions found.';
}

/* If we have 30 items from the first request, show message to load additional items */
if (count($sessions['results']) == '30') {
?>
<div class="text-center">
    <a class="btn btn-default load-more-link" data-offset="<?php echo count($sessions['results'])+1; ?>" href="<?php echo $_SERVER['REQUEST_URI']; ?>#"  onClick="return false;">Load more items</a>
</div>
<?php
}
?>
</div>

<div class="ui modal modalsessioninfo">
    <div class="header">Session info</div>
    <div class="scrolling content">
        <table class="table table-bordered table-padding table-striped" id="table-session-content">
            <thead>
                <tr>
                    <th>Message</th>
                    <th>Duration</th>
                </tr>
            </thead>
            <tbody>
            </tbody>
        </table>
        <br />
    </div>
    <div class="actions text-center">
        <div class="ui positive button">Close</div>
    </div>
</div>

<script>
/* Filter history */
$("#filter-history").keyup(function(e) {
    var searchText = $(this).val().toLowerCase();
    /* Show only matching row, hide rest of them */
    $.each($("#table-sessions tbody tr"), function(e) {
        if ($(this).text().toLowerCase().indexOf(searchText) === -1) {
           $(this).hide();
        } else {
           $(this).show();
        }
    });
});

/* Session window */
$(document).on('click', '.item', function(e) {
    var icon, text;
    var id = $(this).data('sessionid');
    
    $.get('veeam.php', {'action' : 'getsessionlog', 'id' : id}).done(function(data) {
        response = JSON.parse(data);

        $('#table-session-content tbody').empty();
        
        for (var i = 0; i < response.results.length; i++) {
            if (response.results[i].status == 'Success') { /* Success icon */
                icon = 'check-circle';
                text = 'success';
            } else if (response.results[i].status == 'Warning') { /* Warning icon */
                icon = 'exclamation-triangle';
                text = 'warning';
            } else { /* Failed icon */
                icon = 'times-circle';
                text = 'danger';
            }
            $('#table-session-content tbody').append('<tr> \
                    <td><span class="fa fa-'+icon+' text-'+text+'"></span> ' + response.results[i].message + '</td> \
                    <td>' + response.results[i].duration + '</td> \
                    </tr>');
        }
                
        $('.modalsessioninfo.modal').modal({
            centered : true,
            closable : true,
        }).modal('show');
    });
});

/* Load more link */
$(document).on('click', '.load-more-link', function(e) {
    var offset = $(this).data('offset');

    loadSessions(offset);
});

/*
 * @param offset Offset
 */
function loadSessions(offset) {
    var result, status;

    $.get('veeam.php', {'action' : 'getsessions', 'offset' : offset}).done(function(data) {
        var response = JSON.parse(data);

        for (var i = 0; i < response.results.length; i++) {
            result = response.results[i].result;

            if (result.toLowerCase() == 'failed') {
                status = '<span class="label label-danger">' + result + '</span>';
            } else {
                status = '<span class="label label-' + result.toLowerCase() + '">' + result + '</span>';
            }

            $('a.load-more-link').data('offset', offset + 30); /* Update offset for loading more items */
            $('#table-sessions tbody').append('<tr> \
                        <td>' + response.results[i].name + '</td> \
                        <td>' + response.results[i].organization + '</td> \
                        <td>' + status + '</td> \
                        <td>' + moment(response.results[i].creationTime).format('DD/MM/YYYY HH:mm') + '</td> \
                        <td>' + moment(response.results[i].endTime).format('DD/MM/YYYY HH:mm') + '</td> \
                        <td class="text-center"><a href="#" class="item" data-sessionid="' + response.results[i].id + '" onClick="return false;">View</a></td> \
                        </tr>');
        }
    });//27/07/2018 13:22 CEST vs 28 Jul 2018 15:51
}
</script>