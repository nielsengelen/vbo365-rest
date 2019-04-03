$(document).ready(function(e) {
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

    /* Panels on admin dashboard */
    $('#jobspanel').click(function(e) {
      $('#main').load('includes/jobs.php');
    });
    $('#licensingpanel').click(function(e) {
      $('#main').load('includes/licensing.php');
    });
    $('#organizationspanel').click(function(e) {
      $('#main').load('includes/organizations.php');
    });
    $('#proxiespanel').click(function(e) {
      $('#main').load('includes/proxies.php');
    });
    $('#repositoriespanel').click(function(e) {
      $('#main').load('includes/repositories.php');
    });
});