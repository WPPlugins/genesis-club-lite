<?php
/* Adds short code for Simple Social Icons and allows you to have multiple sets of icons at different sizes on the same page 
   Functionality is only available if Simple Social Icons plugin is also installed
*/
class Genesis_Club_Icons extends Genesis_Club_Module {

    private $svgfile;
	protected $profiles;
	protected $css='';
	protected $initialized = false;

	protected $glyphs = array(
			'bloglovin'		=> '&#xe60c;',
			'dribbble'		=> '&#xe602;',
			'email'			=> '&#xe60d;',
			'facebook'		=> '&#xe606;',
			'flickr'		=> '&#xe609;',
			'github'		=> '&#xe60a;',
			'gplus'			=> '&#xe60e;',
			'instagram' 	=> '&#xe600;',
			'linkedin'		=> '&#xe603;',
			'pinterest'		=> '&#xe605;',
			'rss'			=> '&#xe60b;',
			'stumbleupon'	=> '&#xe601;',
			'tumblr'		=> '&#xe604;',
			'twitter'		=> '&#xe607;',
			'vimeo'			=> '&#xe608;',
			'youtube'		=> '&#xe60f;',
	);

	protected $profile_labels = array(
			'behance' => 'Behance',	
			'bloglovin' => 'BlogLovin',
			'dribbble' => 'Dribbble',
			'email' => 'Email',
			'facebook' =>  'Facebook',
			'flickr' => 'Flickr',
			'github' => 'GitHub',
			'gplus' =>  'Google+',
			'instagram' =>'Instagram',
			'linkedin' => 'Linkedin',
			'medium' => 'Medium',
			'pinterest' => 'Pinterest', 
			'rss' =>  'RSS',
			'snapchat'  => 'Snapchat', 
			'stumbleupon'  => 'StumbleUpon', 
			'tumblr' => 'Tumblr', 
			'twitter' => 'Twitter', 
			'vimeo' =>  'Vimeo', 
			'xing' =>  'Xing',
			'youtube' =>  'YouTube');

	protected $defaults =  array(
			'title'                  => '',
			'new_window'             => 0,
			'size'                   => 36,
			'border_radius'          => 3,
			'border_width'           => 0,
			'border_color'           => '#ffffff',
			'border_color_hover'     => '#ffffff',
			'icon_color'             => '#ffffff',
			'icon_color_hover'       => '#ffffff',
			'background_color'       => '#999999',
			'background_color_hover' => '#666666',
			'alignment'              => 'alignleft',
			'behance'                => '',
			'bloglovin'              => '',
			'dribbble'               => '',
			'email'                  => '',
			'facebook'               => '',
			'flickr'                 => '',
			'github'                 => '',
			'gplus'                  => '',
			'instagram'              => '',
			'linkedin'               => '',
			'medium'                 => '',
			'periscope'              => '',
			'phone'                  => '',
			'pinterest'              => '',
			'rss'                    => '',
			'snapchat'               => '',
			'stumbleupon'            => '',
			'tumblr'                 => '',
			'twitter'                => '',
			'vimeo'                  => '',
			'xing'                   => '',
			'youtube'                => '',
		);

	function get_defaults() {
		return $this->defaults; 
	}

	function get_options_name() {
       return false; // no global options 
   }

	function init() {
		if (class_exists('Simple_Social_Icons_Widget'))	{
			add_shortcode('simple_social_icons', array($this, 'display'));
			add_shortcode('simple-social-icons', array($this, 'display'));
			add_action('wp', array($this,'prepare'));				
            $this->svgfile = plugins_url() .'/simple-social-icons/symbol-defs.svg';
		}
	}

	function prepare() {
		global $wp_widget_factory;
		if ($obj = $wp_widget_factory->widgets['Simple_Social_Icons_Widget']) {
			remove_action('wp_head', array($obj,'css'));
			$new_obj = $this->recast_object($obj,'Genesis_Club_Icons_Widget'); //recast as an enhanced widget
			call_user_func(array($new_obj,'css'));	//improved widget allows multiple instances per page, each with its own CSS using inline_style	
		}
	}
	
	function recast_object($instance, $class) {
    	return unserialize(sprintf(
    	    'O:%d:"%s"%s',
    	    strlen($class),
    	    $class,
    	    strstr(strstr(serialize($instance), '"'), ':')
    	));
	}	
	
	function init_profiles() {
		if ($this->initialized) return;
		
		$this->defaults = apply_filters( 'simple_social_default_styles', $this->defaults );
		$this->glyphs = apply_filters( 'simple_social_default_glyphs', $this->glyphs );
		$profiles = array();
		foreach ($this->profile_labels as $profile => $label) 
			$profiles[$profile] =  array(
				'label'   => __( $label, 'simple-social-icons' ),
				'pattern' => sprintf('<li class="ssi-%1$s"><a href="%%s"><svg role="img" class="social-%1$s" aria-labelledby="social-%1$s"><title id="social-%1$s">%2$s</title><use xlink:href="%3$s"></use></svg></a></li>', 
						$profile, $label, esc_url($this->svgfile.'#social-'.$profile)));
		$this->profiles = apply_filters( 'simple_social_default_profiles', $profiles);
		$this->css = '';
		$this->initialized = true;
	}
	
	function display($attr) {
		$this->init_profiles();
		$instance = shortcode_atts($this->defaults, $attr) ;
		$instance['id'] = 'simple-social-icons-'.rand(1000,1000000);
		$new_window = $instance['new_window'] ? 'target="_blank"' : '';
		$output ='';
		foreach ( $this->profiles as $profile => $data ) {
			if ( empty( $instance[ $profile ] ) ) continue;
			if ( is_email( $instance[ $profile ] ) )
				$output .= sprintf( $data['pattern'], 'mailto:' . esc_attr( $instance[$profile] ), $new_window );
			else
				$output .= sprintf( $data['pattern'], esc_url( $instance[$profile] ), $new_window );
		}
		if ( $output ) {
			$output = sprintf( '<div id="%1$s" class="genesis-club-icons simple-social-icons" style="visibility:hidden"><ul class="%2$s">%3$s</ul></div>', 
				$instance['id'], $instance['alignment'], $output );
			$this->add_css($instance);
		}
		return $output;
	}


	function build_css($instance) {
    	$prefix = sprintf('#%1$s ul li', $instance['id']);	
		$font_size = round( (int) $instance['size'] / 2 );
		$icon_padding = round ( (int) $font_size / 2 );
		$css = $prefix.' a,'. $prefix . ' a:hover {';
		if ($font_size) $css .= sprintf('font-size: %1$spx;',$font_size);
		if ($icon_padding) $css .= sprintf('padding: %1$spx;',$icon_padding);
		if ($instance['icon_color']) $css .= sprintf('color: %1$s;',$instance['icon_color']);
		if ($instance['background_color']) $css .= sprintf('background-color: %1$s;',$instance['background_color']);
		if ($instance['border_radius']) $css .= sprintf('border-radius: %1$spx;-moz-border-radius: %1$spx;-webkit-border-radius:%1$spx;',$instance['border_radius']);
		$css .= '}';
		$css .= $prefix.' a:hover {';
		if ($instance['icon_color_hover']) $css .= sprintf('color: %1$s;',$instance['icon_color_hover']);
		if ($instance['background_color_hover']) $css .= sprintf('background-color: %1$s;',$instance['background_color_hover']);
		$css .= '}';
		return $css; 
	}

	function add_css($instance) {
		if (!$this->css) { //only need to enqueue on the first call
         add_action('wp_enqueue_scripts', array($this, 'early_inline_styles'));		
         add_action('wp_print_footer_scripts', array($this, 'late_inline_styles')); 
      }
      $this->css .= $this->build_css($instance); //append CSS for output later once all content has been parsed
	}

   function early_inline_styles() {
      if (!empty($this->css)) {
         if (wp_add_inline_style( 'simple-social-icons-font', $this->css)) //specific handle is a dependency
            $this->css = '';  //only clear if successfully added in the head
      }      
   }	
	
	function late_inline_styles() {
		if (empty($this->css)) return;
		print <<< SCRIPT
<script type="text/javascript">
//<![CDATA[
jQuery(document).ready(function($) { 
	$('<style type="text/css">{$this->css}</style>').appendTo('head');
	$('.genesis-club-icons').css('visibility','visible');
});	
//]]>
</script>
	
SCRIPT;
	}

}