<?php
class Genesis_Club_Bar extends Genesis_Club_Module {
	const OPTION_NAME = 'bar';
	const HIDE_BAR_METAKEY = '_genesis_club_bar_hide';
	const SHOW_BAR_METAKEY = '_genesis_club_bar_show';

   protected $bar = false;

    protected $defaults  = array(
        'enabled' => false,
        'full_message' => '',
        'laptop_message' => '',
        'tablet_message' => '',
        'short_message' => '',
        'font_color' => '#FFFFFF',
        'background' => '#CDEEEE',
        'show_timeout'=> 0.5,
        'hide_timeout' => 0,
        'bounce' => false,
        'shadow' => false,
        'opener' => false,
        'location' => 'body',
        'position' => 'top',
        'hide_on_home' => false,
        'show_only_on_home' => false			
	);

	function get_defaults() {
    	return $this->defaults;
	}

	function get_options_name() {
       return self::OPTION_NAME; 
	}

	function init() {
		add_action('widgets_init',array($this,'register_widgets'));
		if (!is_admin()) add_action('wp',array($this,'prepare'));
		}

	function register_widgets() {
		if (class_exists('Genesis_Club_Bar_Widget')) register_widget( 'Genesis_Club_Bar_Widget' );
	}

	function prepare() {
		if ($this->is_bar_page()) {
			if ($this->get_option('enabled')) $this->add_bar($this->get_options()); 
			add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
		}
    }

    function add_bar($bar) {
        $this->bar = wp_parse_args( $bar, $this->get_defaults());
    }

	function enqueue_scripts($is_widget = false) {
		if ($this->is_bar_page($is_widget) ) {	
    		wp_enqueue_style('jquery-bar', plugins_url('styles/bar.css', dirname(__FILE__)),
    			array(), GENESIS_CLUB_VERSION);
    		wp_enqueue_script('jquery-bar', plugins_url('scripts/jquery.bar.js', dirname(__FILE__)),
    			array('jquery','jquery-effects-bounce'), GENESIS_CLUB_VERSION, true);
			add_action('wp_print_footer_scripts', array($this, 'init_bar'),10);
    	}
	}

	function is_bar_key($key) {
		return array_key_exists($key, array_keys($this->defaults));
	}

	function get_toggle_postmeta_key($post_type) {
		return 'post'==$post_type ? self::HIDE_BAR_METAKEY : self::SHOW_BAR_METAKEY;
	}
	
	function get_toggle_termmeta_key() {
		return is_tax() ? self::SHOW_BAR_METAKEY : self::HIDE_BAR_METAKEY;
	}

	function is_bar_page($is_widget = false) {
        if ($this->get_option('enabled')) {
            $default_bar_only_on_home = $this->get_option('show_only_on_home');
		    if (is_front_page())
                return ! $this->get_option('hide_on_home') || $is_widget;
		    elseif (is_category() || is_tag())
			    return (! $this->utils->get_term_meta(get_queried_object_id(), self::HIDE_BAR_METAKEY, false)) && ( $is_widget || ! $default_bar_only_on_home) ;
		    elseif (is_tax())
			    return $this->utils->get_term_meta(get_queried_object_id(), self::SHOW_BAR_METAKEY, false) || $is_widget ;
			elseif (is_singular('post'))
			    return  ! get_post_meta(get_queried_object_id(), self::HIDE_BAR_METAKEY,true) && ( !$default_bar_only_on_home || $is_widget) ;
			else
			    return is_singular() && (get_post_meta(get_queried_object_id(),self::SHOW_BAR_METAKEY,true) || $is_widget) ;
		} else
				return $is_widget;
	}

	function init_bar() {
		$bar = $this->bar;
        if (!is_array($bar)) return false;
		$font_color = $bar['font_color'];
		$background = $bar['background'] ;
		$opener = $bar['opener'] ? 'true' : 'false';
		$bounce = $bar['bounce'] ? 'true' : 'false';
		$shadow = $bar['shadow'] ? 'true' : 'false';
		$full_message = str_replace('"','\"',html_entity_decode($bar['full_message']));
		$laptop_message = str_replace('"','\"',html_entity_decode($bar['laptop_message']));
		$tablet_message = str_replace('"','\"',html_entity_decode($bar['tablet_message']));
		$short_message = str_replace('"','\"',html_entity_decode($bar['short_message']));
		$show_timeout = is_numeric($bar['show_timeout'])?1000.0*$bar['show_timeout'] : 0;
		$hide_timeout = is_numeric($bar['hide_timeout'])?1000.0*$bar['hide_timeout'] : 0;
		$location = $bar['location'] ;
		if (empty($location)) $location = 'body';
		$position = $bar['position'] ;
		if (empty($position)) $position = 'top';
		print <<< SCRIPT
<script type="text/javascript">
//<![CDATA[
jQuery(document).ready( function($) {
	jQuery('{$location}').bar({
		full_message	: "{$full_message}",
		laptop_message	: "{$laptop_message}",
		tablet_message	: "{$tablet_message}",
		short_message	: "{$short_message}",
		font_color : "{$font_color}",
		background : "{$background}",
		show_timeout : {$show_timeout},
		hide_timeout : {$hide_timeout},
		bounce : {$bounce},
		shadow : {$shadow},
		opener : {$opener},
		position : "{$position}"
	});
});
//]]>
</script>	
SCRIPT;
	}

}