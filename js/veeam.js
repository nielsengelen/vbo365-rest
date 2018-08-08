$(document).ready(function(e) {
    /* Logout option */
    $('#logout').click(function(e) {
        e.preventDefault();
        $('.modallogout.modal').modal({
            centered : true,
            closable : true,
            onApprove: function(e) {
                $.post('index.php', {'logout' : true}, function(data) {
                    window.location.replace('index.php');
                });
            },
            onDeny   : function(e){
              return;
            },
        }).modal('show');
    });  

    /* Dashboard menu handler */
    $('ul.menu li').click(function(e) {
        var call = $(this).data('call');
        var id = this.id;

        if (typeof id === undefined || !id) {
            return;
        }

        $('#main').load('includes/' + call + '.php');
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