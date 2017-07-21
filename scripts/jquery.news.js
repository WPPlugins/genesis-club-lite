function genesis_club_news_ajax(url) {
	var data = { action: genesis_club_news.ajaxaction, security: genesis_club_news.ajaxnonce, url: url };     
	jQuery.post( genesis_club_news.ajaxurl, data, function( response ) {
   	var ele = jQuery(genesis_club_news.ajaxresults);
      if( response.success ) 
      	ele.append( response.data );
/*      else if ( response.data.error )
      	ele.append( response.data.error );
*/
   });
}    

jQuery(document).ready(function($) {
	if (typeof genesis_club_news0 != 'undefined') genesis_club_news_ajax(genesis_club_news0.feedurl );
	if (typeof genesis_club_news1 != 'undefined') genesis_club_news_ajax(genesis_club_news1.feedurl );   
	if (typeof genesis_club_news2 != 'undefined') genesis_club_news_ajax(genesis_club_news2.feedurl );
});