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

});


