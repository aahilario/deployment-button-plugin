jQuery(document).ready(function($) {
  $('li#wp-admin-bar-deployment-button-trigger').click(function(e){
    e.preventDefault();
    $.ajax({
      type       : 'POST',
      url        : deploy_button_ajax_obj.ajax_url,
      data       : { 
        _ajax_nonce: deploy_button_ajax_obj.nonce,
        action : 'trigger' 
      },
      cache      : false,
      dataType   : 'json',
      async      : true,
      beforeSend : (function(){
        console.log('Sending trigger request');
      }),
      complete   : (function(jqueryXHR, textStatus){}),
      success    : (function(data, httpstatus, jqueryXHR){
        setTimeout(function(){
          send_status_query();
        },1000);
      }) 
    });
  });

  function send_status_query() {
    $.ajax({
      type       : 'POST',
      url        : deploy_button_ajax_obj.ajax_url,
      data       : { 
        _ajax_nonce: deploy_button_ajax_obj.queryid,
        action : 'query' 
      },
      cache      : false,
      dataType   : 'json',
      async      : true,
      beforeSend : (function(){
        console.log('Querying trigger state');
      }),
      complete   : (function(jqueryXHR, textStatus){}),
      error      : (function(jqueryXHR, textStatus, errorThrown){
        $('li#wp-admin-bar-deployment-button-trigger')
          .removeClass('deploy-error')
          .removeClass('deploy-pending')
          .addClass('deploy-error')
          .find('div.ab-item')
          .each(function(){ 
            $(this).attr('title',textStatus); 
          });
        setTimeout(function(){
          send_status_query();
        },2000);
      }),
      success    : (function(data, httpstatus, jqueryXHR){
        console.log('Got query reply');
        $('li#wp-admin-bar-deployment-button-trigger')
          .removeClass('deploy-error')
          .removeClass('deploy-pending')
          .find('div.ab-item')
          .each(function(){ 
            $(this)
              .attr('title','Trigger deployment to '+data.target)
              .empty()
              .text('Deploy '+data.target); 
          });
        if ( data.state == "Pending" ) {
          $('li#wp-admin-bar-deployment-button-trigger')
            .addClass('deploy-pending')
            .find('div.ab-item')
            .each(function(){ $(this).empty().text(data.info); });
        }
        if ( data.state == "Error" ) {
          $('li#wp-admin-bar-deployment-button-trigger')
            .addClass('deploy-error')
            .find('div.ab-item')
            .each(function(){ $(this).attr('title',data.info); });
        }
        if ( data.info == "Done" ) {
          data.interval = 2000;
          $('li#wp-admin-bar-deployment-button-trigger').data('ptarget', data.target);
          console.log('About to open '+data.target);
          setTimeout(function(){
            console.log('Opening '+$('li#wp-admin-bar-deployment-button-trigger').data('ptarget'));
            var w = window.open($('li#wp-admin-bar-deployment-button-trigger').data('ptarget'),'_blank');
            if ( w ) w.focus();
          },1000);
        }
        setTimeout(function(){
          send_status_query();
        },data.interval);
      }) 
    });
  }

  send_status_query();

});


