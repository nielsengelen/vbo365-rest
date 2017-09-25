$(document).ready(function(e) {
	/* Login form content */
	$('#login-content').backstretch([
		'images/1.jpg'
	  , 'images/2.jpg'
	  , 'images/3.jpg'
	  , 'images/4.jpg'
	  , 'images/5.jpg'
	 ], {duration: 3000, fade: 750});
	 
	/* Form validation */
    $('.form-login input[type="text"], .form-login input[type="password"]').on('focus', function() {
    	$(this).removeClass('input-error');
    });
    
    $('.form-login').on('submit', function(e) {
    	$(this).find('input[type="text"], input[type="password"]').each(function(){
    		if ($(this).val() == '') {
    			e.preventDefault();
    			$(this).addClass('input-error');
    		} else {
    			$(this).removeClass('input-error');
    		}
    	});
    });
	
	/* Main content */
	$('#menusection li').click(function (e) {
		var id = this.id;
		
		if (typeof id === undefined || !id) {
			return;
		}
		
		if (id == 'logout') {
			$.post('index.php', {'logout' : true}, function(data) {
				bootbox.confirm({
					message: 'You are about to logout. Are you sure you want to continue?',
					backdrop: true,
					callback: function (result) {
						if (result) {
							window.location.replace('index.php');
						}
					}
				});
			});
		} else {
			var call = $(this).attr('data-call');
			
			$.get('veeam.php', {'action' : call, 'id' : id}, function(data) {
				$('#content').html('<h1>Veeam Backup for Office 365 RESTful API demo</h1>' + data)
			});
		}
	});
	
	$("#jobspanel").click(function(e) {
	  var call = $(this).attr('data-call');
	  
      $.get('veeam.php', {'action' : call, 'id' : 'jobs'}, function(data) {
		$('#content').html('<h1>Veeam Backup for Office 365 RESTful API demo</h1>' + data)
	  });
    });
	
	$("#organizationspanel").click(function(e) {
	  var call = $(this).attr('data-call');
	  
      $.get('veeam.php', {'action' : call, 'id' : 'organizations'}, function(data) {
		$('#content').html('<h1>Veeam Backup for Office 365 RESTful API demo</h1>' + data)
	  });
    });
	
	$("#proxiespanel").click(function(e) {
	  var call = $(this).attr('data-call');
	  
      $.get('veeam.php', {'action' : call, 'id' : 'proxies'}, function(data) {
		$('#content').html('<h1>Veeam Backup for Office 365 RESTful API demo</h1>' + data)
	  });
    });
	
	$("#repospanel").click(function(e) {
	  var call = $(this).attr('data-call');
	  
      $.get('veeam.php', {'action' : call, 'id' : 'repositories'}, function(data) {
		$('#content').html('<h1>Veeam Backup for Office 365 RESTful API demo</h1>' + data)
	  });
    });
	
	/* Iteam search */
    $("#search").keyup(function(e) {
        var searchText = $(this).val().toLowerCase();
        // Show only matching row, hide rest of them
        $.each($('[name="table-mailitems"] tbody tr'), function(e) {
            if($(this).text().toLowerCase().indexOf(searchText) === -1)
               $(this).hide();
            else
               $(this).show();
        });
    });
});

String.prototype.capitalize = function() {
    return this.charAt(0).toUpperCase() + this.slice(1);
}

$(document).on('click', '#btn-changejobstate', function(e) {
	var jid = $(this).data('jid');
	var name = $(this).data('name');
	var call = $(this).data('call');

	var json = '{ "'+call+'": null }';
	
	$.get('veeam.php', {action : 'changejobstate', 'id' : jid, 'json' : json}).done(function(data) {
		bootbox.alert({
			message: 'Job has been '+call+'d.',
			backdrop: true
		});
		
		if (call == 'enable') {
			$('#span-job-' + jid).html('<button class="btn btn-default" id="btn-changejobstate" data-call="disable" data-name="' + name + '" data-jid="' + jid + '" title="Disable job"><i class="fa fa-power-off text-danger fa-lg" aria-hidden="true"></i></button></a>&nbsp;');
		} else {
			$('#span-job-' + jid).html('<button class="btn btn-default" id="btn-changejobstate" data-call="enable" data-name="' + name + '" data-jid="' + jid + '" title="Enable job"><i class="fa fa-power-off text-success fa-lg" aria-hidden="true"></i></button></a>&nbsp;');
		}
	});
});

$(document).on('click', '#btn-delete', function(e) {
	var action = $(this).data('call');
	var id = $(this).data('cid');
	var name = $(this).data('name');
	var rcall = $(this).data('rcall');
	
	if (action == 'endrestore') {
		var json = '{ "stop": null }';
		var message = 'Are you sure you want to terminate session <strong>' + id + '</strong>?';
		
		bootbox.confirm({
			message: message,
			callback: function (result) {
				if (result) {
					$.get('veeam.php', {action : action, 'id' : id, 'json' : json}).done(function(data) {
						bootbox.alert({
							message: '<strong>' + id + '</strong> has been terminated.',
							backdrop: true
						});
						
						$.get('veeam.php', {'action' : rcall}, function(data) {
							$('#content').html('<h1>Veeam Backup for Office 365 RESTful API demo</h1>' + data)
						});
					});
				}
			}
		});
	} else {
		var message = 'Are you sure you want to delete <strong>' + name + '</strong>?';
		
		bootbox.confirm({
			message: message,
			callback: function (result) {
				if (result) {
					$.get('veeam.php', {action : action, 'id' : id}).done(function(data) {
						bootbox.alert({
							message: '' + data,
							backdrop: true
						});

						$.get('veeam.php', {'action' : rcall}, function(data) {
							$('#content').html('<h1>Veeam Backup for Office 365 RESTful API demo</h1>' + data)
						});
					});
				}
			}
		});
	}
});

$(document).on('click', '#btn-start', function(e) {
	var action = $(this).data('call');
	var id = $(this).data('cid');
	var name = $(this).data('name');
	
	bootbox.confirm({
		message: 'Are you sure you want to start <strong>' + name + '</strong>?',
		callback: function (result) {
			if (result) {
				$.get('veeam.php', {action : action, 'id' : id}).done(function(data) {
					$('#infobox').html('<div class="alert alert-info alert-dismissible" role="alert"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button><strong>' + data + '</strong></div>');
				});		
			}
		}
	});
});

/* Restore buttons */
$(document).on('click', '#btn-start-item-restore', function(e) {
	var oid = $(this).data('oid'); /* Organization ID */
	var jid = $(this).data('jid'); /* Job ID */
	var rp = $(this).data('rp'); /* Restore Point */
	var json = '{ "explore": { "datetime": "' + rp + '" } }';
	
	$.get('veeam.php', {'action' : 'startrestore', 'id' : oid, 'json' : json}).done(function(data) {
		if (data) {
			$('#infobox').html('<div class="alert alert-info alert-dismissible" role="alert"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button><strong>Explorer has been started and you can now perform item restores.</strong></div>');
		
			$('#span-item-restore-' + jid).html('<button class="btn btn-default btn-danger" id="btn-stop-item-restore" data-oid="' + oid + '" data-jid="' + jid + '" data-rid="' + data + '" data-rp="' + rp + '" title="Stop Explorer">Stop Explorer</button>');
			$('a[name="link-restore"]').attr('data-rid', data); /* Adding the restore session ID to the 'View items' option */
		} else {
			$('#infobox').html('<div class="alert alert-info alert-dismissible" role="alert"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button><strong>' + data + '</strong></div>');
		}
	});
});
  
$(document).on('click', '#btn-stop-item-restore', function(e) {
	var rid = $(this).data('rid'); /* Restore Session ID */
	var oid = $(this).data('oid'); /* Organization ID */
	var jid = $(this).data('jid'); /* Job ID */
	var rp = $(this).data('rp'); /* Restore Point */
	var json = '{ "stop": null }';

	bootbox.confirm({
		message: 'Stop the Veeam Explorer restore session?',
		callback: function (result) {
			if (result) {
				$.get('veeam.php', {action : 'endrestore', 'id' : rid, 'json' : json}).done(function(data) {
					bootbox.alert({
						message: 'Wizard has been terminated.',
						backdrop: true
					});

					$('#div-item-restore').html('<button class="btn btn-default btn-success" id="btn-start-item-restore" data-oid="' + oid + '" data-jid="' + jid + '" data-rp="' + rp + '" title="Start Explorer">Start Explorer</button>');
				});
			}
			
			$.get('veeam.php', {'action' : 'getmailboxes', 'id' : oid}, function(data) {
				$('#content').html('<h1>Veeam Backup for Office 365 RESTful API demo</h1>' + data)
			});
		}
	});
});

$(document).on('click', '#btn-exportmsg', function(e) {
	var id = $(this).data('iid'); /* Item ID */
	var oid = $(this).data('oid'); /* Restore and mailbox ID */
	var cid = oid + '|' + id;
	var json = '{ "savetoMsg": null }';
	
	$.get('veeam.php', {action : 'exportitem', 'id' : cid, 'json' : json}).done(function(data) {
		window.location.href = 'download.php?file=' + data + '&type=msg';
	});
});

$(document).on('click', '#btn-exportpst', function(e) {
	var id = $(this).data('iid'); /* Item ID */
	var oid = $(this).data('oid'); /* Restore and mailbox ID */
	var cid = oid + '|' + id;
	var json = '{ "ExportToPst": { "ContentKeyword": "" } }';
	
	$.get('veeam.php', {action : 'exportitem', 'id' : cid, 'json' : json}).done(function(data) {
		window.location.href = 'download.php?file=' + data + '&type=pst';
	});
});

$(document).on('click', '#btn-restore-different', function(e) {
	var id = $(this).data('iid'); /* Item ID */
	var oid = $(this).data('oid'); /* Mailbox and restore ID */
	
	$('#restore-folder').empty();
	$('#restore-folder').append('<option disabled selected> -- select folder -- </option>');
  
	$.get('veeam.php', {'action' : 'listfolders', 'id' : oid}, function(data) {
		var response = data.split('#');
		console.log(data + ' --- \n ' + response);
		for (i = 0; i < response.length-1; ++i) {
			$('#restore-folder').append('<option value="' + response[i] + '">' + response[i] + '</option>');
		}
	});

	bootbox.confirm({
		title: 'Please fill in the correct information to perform the restore.',
		message: $('#form-restore-different').html(),
		backdrop: true,
		buttons: {
			cancel: {
				label: '<i class="fa fa-times"></i> Cancel'
			},
			confirm: {
				label: '<i class="fa fa-check"></i> Ok'
			}
		},
		callback: function (result) {
			if (result) {
				var user = $('#restore-user', '.bootbox').val();
				var pass = $('#restore-pass', '.bootbox').val();
				var folder = $('#restore-folder', '.bootbox').val();
				var mailbox = $('#restore-mailbox', '.bootbox').val();
				var casserver = $('#restore-casserver', '.bootbox').val();
				var json = '{ \
						 "restoreTo": \
						 { \
						  "casServer": "' + casserver + '", \
						  "mailbox": "' + mailbox + '", \
						  "folder": "' + folder + '" \
						  "userName": "' + user + '", \
						  "userPassword": "' + pass + '", \
						  "changedItems": "True", \
						  "deletedItems": "True", \
						  "markRestoredAsUnread": "True", \
						  "excludeDrafts": "False", \
						  "excludeDeletedItems": "False", \
						  "excludeInPlaceHoldItems": "True", \
						  "excludeLitigationHoldItems": "True" \
						 } \
						}';

				if (typeof user === undefined || !user) {
					$('#infobox').html('<div class="alert alert-warning alert-dismissible" role="alert"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button><span class="label label-danger">Error</span><br /><strong>No username defined.</strong></div>');
					return;
				}
				
				if (typeof pass === undefined || !pass) {
					$('#infobox').html('<div class="alert alert-warning alert-dismissible" role="alert"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button><span class="label label-danger">Error</span><br /><strong>No password defined.</strong></div>');
					return;
				}
				
				$.get('veeam.php', {action : 'restoreto', 'id' : cid, 'json' : json}).done(function(data) {
					$('#infobox').html('<div class="alert alert-info alert-dismissible" role="alert"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button><strong>' + data + '</strong></div>');
				});
			}
		}
	});
});

$(document).on('click', '#btn-restoreoriginal', function(e) {
	var id = $(this).data('iid'); /* Item ID */
	var oid = $(this).data('oid'); /* Mailbox and restore ID */
	var cid = oid + '|' + id;

	bootbox.confirm({
		title: 'Please fill in the correct information to perform the restore.',
		message: $('#form-restore-original').html(),
		backdrop: true,
		buttons: {
			cancel: {
				label: '<i class="fa fa-times"></i> Cancel'
			},
			confirm: {
				label: '<i class="fa fa-check"></i> Ok'
			}
		},
		callback: function (result) {
			if (result) {
				var user = $('#restore-user', '.bootbox').val();
				var pass = $('#restore-pass', '.bootbox').val();
				var json = '{ \
						 "restoretoOriginallocation": \
						{ "userName": "' + user + '", \
						  "userPassword": "' + pass + '", \
						  "ChangedItems": "True", \
						  "DeletedItems": "True", \
						  "MarkRestoredAsUnread": "True" \
						}';
				if (typeof user === undefined || !user) {
					$('#infobox').html('<div class="alert alert-warning alert-dismissible" role="alert"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button><span class="label label-danger">Error</span><br /><strong>No username defined.</strong></div>');
					return;
				}
				
				if (typeof pass === undefined || !pass) {
					$('#infobox').html('<div class="alert alert-warning alert-dismissible" role="alert"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button><span class="label label-danger">Error</span><br /><strong>No password defined.</strong></div>');
					return;
				}
				
				$.get('veeam.php', {action : 'restoreoriginal', 'id' : cid, 'json' : json}).done(function(data) {
					$('#infobox').html('<div class="alert alert-info alert-dismissible" role="alert"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button><strong>' + data + '</strong></div>');
				});
			}
		}
	});
});

$(document).on('click', 'a[name="link-restore"]', function(e) {
	var mid = $(this).data('mid'); /* Mailbox ID */
	var rid = $(this).data('rid'); /* Restore Session ID */
	var id = mid + '|' + rid; /* Combine them - we'll handle this in the PHP class */
	var target = $(this).data('target');
	
	if (typeof rid === undefined || !rid) {
		bootbox.alert({
			message: 'Explorer not started, please start if first by clicking "Start Explorer".',
			backdrop: true
		});
		
		return;
	} else {
		$(target).collapse('toggle');
	}
	
	$.get('veeam.php', {action : 'getitems', 'id' : id}).done(function(data) {
		var response = JSON.parse(data);
		$('#table-mailitems-' + mid + ' tbody').empty(); /* Clear the items table */
		
		if (response.results.length != '0') {
			for (var i = 0; i < response.results.length; i++) {
				var date = Date(response.results[i].received).toString();
				date = date.split('(');
				$('#table-mailitems-' + mid + ' tbody').append('<tr> \
					<td>' + response.results[i].from + '</td> \
					<td>' + response.results[i].subject + '</td> \
					<td>' + date[0].slice(0, -1) + '</td> \
					<td class="text-center"> \
					<button class="btn btn-info" id="btn-restore-original" data-toggle="tooltip" data-placement="bottom" title="Restore to the orignal location" data-iid="' + response.results[i].id + '" data-oid="' + id + '"><i class="fa fa-server" aria-hidden="true"></i></button> \
					<button class="btn btn-info" id="btn-restore-different" data-toggle="tooltip" data-placement="bottom" title="Restore to a different location" data-iid="' + response.results[i].id + '" data-oid="' + id + '"><i class="fa fa-paperclip" aria-hidden="true"></i></button> \
					<br /><button class="btn btn-info" id="btn-exportmsg" data-toggle="tooltip" data-placement="bottom" title="Save as MSG" data-iid="' + response.results[i].id + '" data-oid="' + id + '"><i class="fa fa-envelope" aria-hidden="true"></i></button> \
					<button class="btn btn-info" id="btn-exportpst" data-toggle="tooltip" data-placement="bottom" title="Export as PST" data-iid="' + response.results[i].id + '" data-oid="' + id + '"><i class="fa fa-download" aria-hidden="true"></i></button> \
					</td> \
					</tr>');
			}
		} else {
			$('#table-mailitems-' + mid + ' tbody').append('<tr><td colspan="3">No e-mails found.</td></tr>');
		}
	});
});