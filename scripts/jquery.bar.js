(function($) {

  var methods = {
    init :  function( options ) { 
    	var settings = $.extend({
			full_message	: '',
			laptop_message	: '',
			notepad_message	: '',
			short_message	: '',
			background : 'orange',
			font_color : '#000',
			position : 'top',
			bounce : false,
			shadow : false,
			opener : false,
			show_timeout : 2000,
			hide_timeout : 5000,
			timer : 0	
	    	}, options);
		if (settings.show_timeout==0) settings.show_timeout = 500;

    	return this.each(function() { 
        	var $this = $(this), data = $this.data('bar');
         
         	// If the plugin hasn't been initialized yet
         	if ( ! data ) $(this).data('bar', { target : $this, settings : settings });    	      
			if (settings.show_timeout) 
				settings.timer = setTimeout($.proxy(function() { $(this).bar('add');}, $(this)), settings.show_timeout);
		});
    },
    
    add : function( ) { 
			var data = $(this).data('bar');
			var o = data.settings;
			var _full_message_span = $(document.createElement('span')).addClass('jbar-content full').css({"color" : o.font_color}).html(o.full_message);
			var _laptop_message_span = $(document.createElement('span')).addClass('jbar-content laptop').css({"color" : o.font_color}).html(o.laptop_message);
			var _tablet_message_span = $(document.createElement('span')).addClass('jbar-content tablet').css({"color" : o.font_color}).html(o.tablet_message);
			var _short_message_span = $(document.createElement('span')).addClass('jbar-content short').css({"color" : o.font_color}).html(o.short_message);
			var _wrap_container =  $(document.createElement('div')).attr('id','jbar-container');
			var _wrap_bar= $(document.createElement('div')).addClass('jbar');
			if (o.position == 'bottom') {
				_wrap_bar.addClass('jbar-bottom');
			} else {
				_wrap_bar.addClass('jbar-top') ;
				if (o.shadow) _wrap_bar.addClass('jbar-box-shadow') ;
			}
			_wrap_bar.css({"background" : o.background});
			_wrap_bar.append(_full_message_span);
			if (o.laptop_message) _wrap_bar.append(_laptop_message_span);
			if (o.tablet_message) _wrap_bar.append(_tablet_message_span);
			if (o.short_message) _wrap_bar.append(_short_message_span);
			if (! o.laptop_message) _full_message_span.addClass('laptop');
			if (! o.tablet_message) 
				if (o.laptop_message) 
					_laptop_message_span.addClass('tablet');
				else
					_full_message_span.addClass('tablet');
			if (! o.short_message) 
				if (o.tablet_message) 
					_tablet_message_span.addClass('short');
				else
					if (o.laptop_message) 
						_laptop_message_span.addClass('short');
					else
						_full_message_span.addClass('short');
			
			_wrap_container.append(_wrap_bar) ;			
			if(o.opener){
				var _open = $(document.createElement('a')).attr('href','#open').addClass('open').css({"background-color" : _wrap_bar.css("background-color")});
				_open.click( $.proxy(function() { $(this).bar('show');}, $(this)));
				var _close = $(document.createElement('a')).addClass('close').attr('href','#close');
				_close.click( $.proxy(function() { $(this).bar('hide');}, $(this)));
				_wrap_bar.append(_close);
				_wrap_container.append(_open); 
			}
			if (o.position=='bottom')	
				$(this).append(_wrap_container);
			else
				$(this).prepend(_wrap_container);
			if (!o.opener) $(this).bar('show');
     },
    
    show : function( ) { 
		var data = $(this).data('bar');
		var o = data.settings;    
		if($('#jbar-container').length){
			if (o.opener) $('#jbar-container .open').hide();			
			$('#jbar-container .jbar').show();
			$(this).addClass('has-jbar');
			$('#jbar-container').hide().css({ top: 0, height: 'auto' })
			if (o.bounce) 
				$('#jbar-container').fadeIn('fast').effect("bounce", { times:3 }, 300);
			else
				$('#jbar-container').fadeIn('fast');
			if (o.hide_timeout > 0) o.timer = setTimeout($.proxy(function() { $(this).bar('hide'); }, $(this)),o.hide_timeout);
		}	
    },
    
    hide : function( ) { 
		var data = $(this).data('bar');
		var o = data.settings; 
		if($('#jbar-container').length){
			$('#jbar-container').css({ height: 0 });
			$('#jbar-container .jbar').hide();
			$(this).removeClass('has-jbar');
			if (o.opener) 
				$('#jbar-container .open').show();
			else
				$(this).bar('remove');
		}	
    },
    
    remove : function( ) { 
		var data = $(this).data('bar');
		var o = data.settings;     
		if (o.timer) clearTimeout(o.timer);
		if ($('#jbar-container').length) $('#jbar-container').remove();
    }        
  };

    $.fn.bar = function( method ) {

    // Method calling logic
    if ( methods[method] ) {
      return methods[ method ].apply( this, Array.prototype.slice.call( arguments, 1 ));
    } else if ( typeof method === 'object' || ! method ) {
      return methods.init.apply( this, arguments );
    } else {
      $.error( 'Method ' +  method + ' does not exist on jQuery.bar' );
    }    

  };
	
})(jQuery);