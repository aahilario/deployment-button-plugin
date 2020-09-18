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
        console.log('Got reply');
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
              .text('Deploy <b>'+data.target+'</b>'); 
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
        setTimeout(function(){
          send_status_query();
        },data.interval);
      }) 
    });
  }

  send_status_query();

});


