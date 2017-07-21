jQuery(document).ready( function($) {
	
	$( "#ctz" ).change(function() {
		var newctz = $(this).val();
    	var d = new Date();
    	d.setTime(d.getTime() + (365*24*60*60*1000));
    	var expires = "expires="+d.toGMTString();
    	document.cookie = "google_calendar_timezone=" + newctz + "; " + expires;
  		var iframe=jQuery(this).parents('form').find('iframe');
  		if (iframe) {
  			var src = iframe.attr('src').replace(/ctz=(.*)/, 'ctz='+encodeURI(newctz));
 			iframe.attr('src', src);
  		}
	});
});
	