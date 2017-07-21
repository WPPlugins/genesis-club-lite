(function($) {

	var methods = { init :  function( options ) { 
    	var settings = $.extend({
			unwanted : '.entry-meta, .entry-footer, .post-info, .post-meta, .left-corner, .right-corner',
			exclude : '.sd-title,.jp-relatedposts h3',
			header_class : 'accordion-default-colors',
			content_class : 'accordion-default-colors',
			container_class : 'accordion-default-colors',
			header : 'h3',
			scroll_top : false,
			open_first : false,
			header_depth : 0
	    	}, options);
	    
    	return this.each(function() { 
    		var ele = $(this);
			$(this).find(settings.unwanted).remove(); //strip unwanted elements  					
			$(this).find(settings.header).not(settings.exclude).removeClass().addClass('accordion-header');
			$(this).find('.accordion-header a').each(function(index) { $(this).replaceWith($(this).html()); } ); //strip clickable links
			if (settings.header_class) $(this).find('.accordion-header').addClass(settings.header_class);
			$(this).find('.accordion-header').next().removeClass().addClass('accordion-content').addClass(settings.content_class).hide();
			if (settings.content_class) $(this).find('.accordion-content').addClass(settings.content_class);
			$(this).find('.accordion-header').parent().addClass('accordion-container').addClass(settings.container_class);
			if (settings.container_class) $(this).find('.accordion-container').addClass(settings.container_class);
			$(this).find('.accordion-header').click( function() {
			    var header = $(this);
				if (header.hasClass('accordion-selected')) {
					header.removeClass('accordion-selected').next().slideUp( 'slow');
				} else {
					if (settings.scroll_top)
            			ele.find('.accordion-header.accordion-selected').removeClass('accordion-selected').next().slideUp( 'slow','linear',
               			function(){ $('html,body').animate({ scrollTop: header.offset().top - settings.header_depth }, 500); });
               		else	
            			ele.find('.accordion-header.accordion-selected').removeClass('accordion-selected').next().slideUp( 'slow','linear');
							header.addClass('accordion-selected').next().slideDown('slow');
				}
			});	
			if (settings.open_first)
				$(this).find('.accordion-header').first().click();      
		});
    }       
  };

	$.fn.gcaccordion = function( method ) {
    	if ( methods[method] ) {
			return methods[ method ].apply( this, Array.prototype.slice.call( arguments, 1 ));
		} else if ( typeof method === 'object' || ! method ) {
      		return methods.init.apply( this, arguments );
    	} else {
      		$.error( 'Method ' +  method + ' does not exist on gcaccordion' );
    	}    
	};
	
})(jQuery);