(function($){
  $(document).ready(function(){
	$('.ajaxLoadmoreBtn').click(function(){
      var t = $(this).html();
	  var btn = $(this);
	  $(this).html('Loading...');
	  $(this).addClass('loading');
	  var p = $(this).closest('.ajaxLoadmore');
	  var loadingDiv = p.find('.ajax-loading-div');
	  // loadingDiv.css('display', 'block');
	  var paged = parseInt(p.attr('data-page'));
	  paged = paged + 1;
	  var id = parseInt(p.attr('data-id'));
	  
	  var data = {
       'action': 'wp_ajax_loadmore',
       'paged': paged,
	   'id': id,
	   'nonce': ajaxLoadmore_params.wp_ajax_loadmore
      };
	  
	  $.ajax({
	    url : ajaxLoadmore_params.ajaxurl, // AJAX handler
	    data : data,
	    type : 'POST',
	    beforeSend : function ( xhr ) { 
		  // add your custom code here..
	    },
	    success : function( data ){ 
		  p.attr('data-page', paged);
		  $(btn).html(t);
		  $(btn).removeClass('loading');
		  loadingDiv.css('display', 'none');
		  if(data != ''){
			p.find('.ajaxLoadmore-inner').append(data);
		  } else {
			$(btn).remove();
		  }
		}  
	  }); 
	  
	});
  });
})(jQuery);