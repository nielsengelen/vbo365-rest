$(document).on('click', '#btn-create-wizard', function(e) {
	var action = $(this).data('call');
	
	if (action == 'createjob') {
		var name = $('#job-name').val();
		var desc = $('#job-desc').val();
		var proxy = $('#job-proxy').val();
		var repo = $('#job-repo').val();
		var setting = $('input:radio[name=job-backupsetting]:checked').val();
		var type = $('input:radio[name=job-scheduleperiod]:checked').val();
		
		if (typeof type === undefined || !type) {
			type = 'Daily';
		}
		
		var dailyHour = $('#job-dailyHour').val();
		var dailyMin = $('#job-dailyMin').val();
		var dailyType = $('#job-dailyType').val();
		var periodicallyEvery = $('#job-periodicallyevery').val();
		var retryNumber = $('#job-retrynumber').val();
		var retryWaitInterval = $('#job-retryinterval').val();
		var retryEnabled = 'false'; /* Pre-defined to prevent error */
		var isRun = 'false'; /* Pre-defined to prevent error */
		
		if ($('input:checkbox[id=job-retry]:checked').val() == 'on') {
			retryEnabled = 'true';
		} else {
			retryEnabled = 'false';
		}	
		
		if ($('input:checkbox[id=job-isrun]:checked').val() == 'on') {
			isRun = 'true';
		} else {
			isRun = 'false';
		}
		
		if (type == 'Daily') {
			var timestamp = dailyHour + ':' + dailyMin;
		} else {
			var timestamp = '00:00:00';
		}
		
		/* Used when creating a job via the job page to get the organization ID from the form */
		var oid = $('#job-org').val();
		
		if (typeof name === undefined || !name) {
			$('#infobox').html('<div class="alert alert-warning alert-dismissible" role="alert"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button><span class="label label-danger">Error</span><br /><strong>No job name defined.</strong></div>');
			$('#wizard').modal('hide');
			
			return false;
		}
		
		if (typeof setting === undefined || !setting) {
			$('#infobox').html('<div class="alert alert-warning alert-dismissible" role="alert"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button><span class="label label-danger">Error</span><br /><strong>Please define which mailboxes to backup (specific or all).</strong></div>');
			$('#wizard').modal('hide');
			
			return false;
		}
		
		if (setting == 'job-backupall') {
			var json = '{ \
					  "id": "string", \
					  "name": "' + name + '", \
					  "description": "' + desc + '", \
					  "allMailboxes": true, \
					  "schedulePolicy": { \
						"type": "' + type + '", \
						"periodicallyEvery": "' + periodicallyEvery + '", \
						"dailyType": "' + dailyType + '", \
						"dailyTime": "' + timestamp + '", \
						"terminationEnabled": true, \
						"terminationInterval": "Minutes5", \
						"retryEnabled": true, \
						"retryNumber": ' + retryNumber + ', \
						"retryWaitInterval": ' + retryWaitInterval + ' \
					  }, \
					  "proxyId": "' + proxy + '", \
					  "repositoryId": "' + repo + '", \
					  "isRun": ' + isRun +' \
					}';
		} else {
			var mailboxes = [];
			var selectedmailboxes = '';
			
			$.each($("#job-mailboxes :selected"), function(e) {
				mailboxes.push($(this).val());
			});
									
			for (var i = 0; i < mailboxes.length; i++) {
				var mailbox = mailboxes[i].toString().split("|");
				var mailboxid = mailbox[0];
				var mailboxemail = mailbox[1];
				var mailboxname = mailbox[2];

				selectedmailboxes = selectedmailboxes + '{ \
						  "id": "' + mailboxid + '", \
						  "email": "' + mailboxemail + '", \
						  "name": "' + mailboxname + '", \
						  "isBackedUp": true, \
						  "_links": {} \
						},';
			}

			var json = '{ \
					  "id": "string", \
					  "name": "' + name + '", \
					  "description": "' + desc + '", \
					  "allMailboxes": false, \
					  "selectedMailboxes": [ \
						' + selectedmailboxes.slice(0, -1) + ' \
					  ], \
					  "schedulePolicy": { \
						"type": "' + type + '", \
						"periodicallyEvery": "' + periodicallyEvery +'", \
						"dailyType": "' + dailyType + '", \
						"dailyTime": "' + timestamp + '", \
						"terminationEnabled": true, \
						"terminationInterval": "Minutes5", \
						"retryEnabled": ' + retryEnabled + ', \
						"retryNumber": ' + retryNumber + ', \
						"retryWaitInterval": ' + retryWaitInterval + ' \
					  }, \
					  "proxyId": "' + proxy + '", \
					  "repositoryId": "' + repo + '", \
					  "isRun": ' + isRun +' \
					}';
		}
		
		$.get('veeam.php', {'action' : action, 'json' : json, 'id' : oid}).done(function(data) {
			bootbox.alert({
				message: '' + data,
				backdrop: true,
			});
			
			$.get('veeam.php', {'action' : 'getjobs'}, function(data) {
				$('#content').html('<h1>Veeam Backup for Office 365 RESTful API demo</h1>' + data)
			});
		});
		
		$('#wizard').modal('hide');
		
		return false;
	}
	
	if (action == 'createorganization') {
		var deptype = $('#org-deptype').val();
		
		if ($('input:checkbox[id=org-grant]:checked').val() == 'on') {
			var grant = 'true';
		} else {
			var grant = 'false';
		}
		
		if (deptype == 'office365') {
			var region = $('#org-region').val();
			var user = $('#org-user-o365').val();
			var pass = $('#org-pass-o365').val();

			var json = '{ \
					"type": "' + deptype + '", \
					"region": "' + region + '", \
					"username": "' + user + '", \
					"password": "' + pass + '", \
					"grantImpersonation": "' + grant + '" \
					}';
		} else {
			var server = $('#org-server').val();
			var user = $('#org-user-local').val();
			var pass = $('#org-pass-local').val();

			/* Fix for JSON - DOMAIN\username needs to have double \ */
			if ((/\\/).test(user)) {
				user = user.replace(/\\/g, '\\\\');
			}
			
			if ($('input:checkbox[id=org-serverusessl]:checked').val() == 'on') {
				var serverusessl = 'true';
			} else {
				var serverusessl = 'false';
			}
			
			if ($('input:checkbox[id=org-serverskipca]:checked').val() == 'on') {
				var serverskipca = 'true';
			} else {
				var serverskipca = 'false';
			}
			
			if ($('input:checkbox[id=org-serverskipcn]:checked').val() == 'on') {
				var serverskipcn = 'true';
			} else {
				var serverskipcn = 'false';
			}
			
			if ($('input:checkbox[id=org-serverskiprc]:checked').val() == 'on') {
				var serverskiprc = 'true';
			} else {
				var serverskiprc = 'false';
			}
			
			if ($('input:checkbox[id=org-policy]:checked').val() == 'on') {
				var policy = 'true';
			} else {
				var policy = 'false';
			}
			
			var json = '{ \
					  "type": "' + deptype + '", \
					  "serverName": "' + server + '", \
					  "username": "' + user + '", \
					  "password": "' + pass + '", \
					  "GrantImpersonation": "' + grant + '", \
					  "useSSL": "' + serverusessl + '", \
					  "skipCAverification": "' + serverskipca + '", \
					  "skipCommonNameVerification": "' + serverskipcn + '", \
					  "skipRevocationCheck": "' + serverskiprc + '", \
					  "configureThrottlingPolicy": "' + policy + '" \
					}';
		}
		
		if (typeof user === undefined || !user) {
			$('#infobox').html('<div class="alert alert-warning alert-dismissible" role="alert"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button><span class="label label-danger">Error</span><br /><strong>No username defined.</strong></div>');
			$('#wizard').modal('hide');
			
			return false;
		}
		
		if (typeof pass === undefined || !pass) {
			$('#infobox').html('<div class="alert alert-warning alert-dismissible" role="alert"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button><span class="label label-danger">Error</span><br /><strong>No password defined.</strong></div>');
			$('#wizard').modal('hide');
			
			return false;
		}			

		$.get('veeam.php', {'action' : action, 'json' : json}).done(function(data) {
			bootbox.alert({
				message: '' + data,
				backdrop: true,
			});
			
			$.get('veeam.php', {'action' : 'getorganizations'}, function(data) {
				$('#content').html('<h1>Veeam Backup for Office 365 RESTful API demo</h1>' + data)
			});
		});
		
		$('#wizard').modal('hide');
		
		return false;
	}
	
	if (action == 'createproxy') {
		var name = $('#proxy-name').val();
		var port = $('#proxy-port').val();
		var desc = $('#proxy-desc').val();
		var user = $('#proxy-user').val();
		var pass = $('#proxy-pass').val();
						
		if (typeof name === undefined || !name) {
			$('#infobox').html('<div class="alert alert-warning alert-dismissible" role="alert"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button><span class="label label-danger">Error</span><br /><strong>No proxy name defined.</strong></div>');
			
			return false;
		}
		
		if (typeof user === undefined || !user) {
			$('#infobox').html('<div class="alert alert-warning alert-dismissible" role="alert"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button><span class="label label-danger">Error</span><br /><strong>No username defined.</strong></div>');
			
			return false;
		}
		
		if (typeof pass === undefined || !pass) {
			$('#infobox').html('<div class="alert alert-warning alert-dismissible" role="alert"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button><span class="label label-danger">Error</span><br /><strong>No password defined.</strong></div>');
			
			return false;
		}
		
		/* Fix for JSON - DOMAIN\username needs to have double \ */
		if ((/\\/).test(user)) {
			user = user.replace(/\\/g, '\\\\');
		}
		
		var json = '{ \
					  "id": "string", \
					  "hostName": "' + name + '", \
					  "description": "' + desc + '", \
					  "port": ' + port + ', \
					  "username": "' + user + '", \
					  "password": "' + pass + '", \
					  "threadsNumber": 0, \
					  "bandwidth": 0, \
					  "bandwidthType": "Mbps", \
					  "_links": {} \
					}';
					
		$.get('veeam.php', {'action' : action, 'json' : json}).done(function(data) {
			bootbox.alert({
				message: '' + data,
				backdrop: true,
			});
			
			$.get('veeam.php', {'action' : 'getproxies'}, function(data) {
				$('#content').html('<h1>Veeam Backup for Office 365 RESTful API demo</h1>' + data)
			});
		});
		
		$('#wizard').modal('hide');
		
		return false;
	}

	if (action == 'createrepository') {
		var name = $('#repo-name').val();
		var desc = $('#repo-desc').val();
		var path = $('#repo-path').val();
		var proxyinfo = $('#repo-proxy').val();
		proxyinfo = proxyinfo.split("|");
		var proxyname = proxyinfo[0];
		var proxyid = proxyinfo[1];
		
		var retention = $('#repo-retention').val();
		var retentionperiod = $('input:radio[name=repo-retentionperiod]:checked').val();
		
		var dailyHour = $('#repo-dailyHour').val();
		var dailyMin = $('#repo-dailyMin').val();
		var dailyType = $('#repo-dailyType').val();
		
		var monthlyHour = $('#repo-monthlyHour').val();
		var monthlyMin = $('#repo-monthlyMin').val();
		var monthlyDaynumber = $('#repo-monthlyDaynumber').val();
		var monthlyDayofweek = $('#repo-monthlyDayofweek').val();
		
		if (typeof retentionperiod === undefined || !retentionperiod) {
			retentionperiod = 'Monthly';
		}
		
		if (retentionperiod == 'Daily') {
			var timestamp = dailyHour + ':' + dailyMin;
		} else {
			var timestamp = monthlyHour + ':' + monthlyMin;
		}
			
		if (typeof name === undefined || !name) {
			$('#infobox').html('<div class="alert alert-warning alert-dismissible" role="alert"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button><span class="label label-danger">Error</span><br /><strong>No repository name defined.</strong></div>');
			$('#wizard').modal('hide');
			
			return false;
		}
		
		if (typeof path === undefined || !path) {
			$('#infobox').html('<div class="alert alert-warning alert-dismissible" role="alert"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button><span class="label label-danger">Error</span><br /><strong>No repository path defined.</strong></div>');
			$('#wizard').modal('hide');
			
			return false;
		}
		
		/* Fix for JSON - Path needs to have double \ */
		if ((/\\/).test(path)) {
			var newpath = path.replace(/\\/g, '\\\\');
		}
		
		var json = '{ \
					  "name": "' + name + '", \
					  "description": "' + desc + '", \
					  "path": "' + newpath + '", \
					  "hostName": "' + proxyname + '", \
					  "capacity": 0, \
					  "freeSpace": 0, \
					  "retentionPeriodType": "Yearly", \
					  "dailyRetentionPeriod": 14, \
					  "monthlyRetentionPeriod": 12, \
					  "yearlyRetentionPeriod": "' + retention + '", \
					  "retentionFrequencyType": "' + retentionperiod + '", \
					  "dailyTime": "' + timestamp + '", \
					  "dailyType": "' + dailyType + '", \
					  "monthlyTime": "' + timestamp + '", \
					  "monthlyDaynumber": "' + monthlyDaynumber + '", \
					  "monthlyDayofweek": "' + monthlyDayofweek + '", \
					  "proxyId": "' + proxyid + '", \
					  "_links": {} \
					}';
		
		$.get('veeam.php', {'action' : action, 'json' : json}).done(function(data) {
			bootbox.alert({
				message: '' + data,
				backdrop: true,
			});
			
			$.get('veeam.php', {'action' : 'getrepositories'}, function(data) {
				$('#content').html('<h1>Veeam Backup for Office 365 RESTful API demo</h1>' + data)
			});
		});
		
		$('#wizard').modal('hide');
		
		return false;
	}
});