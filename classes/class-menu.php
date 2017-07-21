<?php
class Genesis_Club_Menu extends Genesis_Club_Module {
    const OPTION_NAME = 'menu';	
	const SEARCH_STYLE = 'gc-search-menu';
	
	protected $side_menu_left = '';
	protected $side_menu_right = '';
	protected $below_menu = false;	
	protected $search_text;
	protected $is_html5;
	
	protected $defaults  = array(
		'threshold' => '',
		'icon_size' => '',
		'icon_color' => '',
		'primary' => 'below',
		'secondary' => 'below',
		'header' => 'none',
		'search_menu' => 'none',
		'search_text' => 'Search',
		'search_text_color' => 'Gray',	
		'search_background_color' => 'transparent',	
		'search_border_color' => 'LightGray',
		'search_border_radius' => '4',
		'search_padding_top' => 5,
		'search_padding_bottom' => 5,
		'search_padding_threshold' => '',
		'search_button' => true,
	);

	function get_defaults() { return apply_filters('genesis_club_menu_defaults', $this->defaults); }
	function get_options_name() { return self::OPTION_NAME; }
	
	function init() {
		$this->is_html5 = $this->utils->is_html5();
		if (!is_admin()) add_action('wp',array($this,'prepare'));
	}

	function prepare() {
        do_action('genesis_club_menu_prepare');
	
		if ($this->get_option('threshold')) {
			if ($primary = $this->get_option('primary')) {
				add_filter('genesis_do_nav', array($this,'add_responsive_menu'),100,3);
			}
			if ($secondary = $this->get_option('secondary')) {            
				add_filter('genesis_do_subnav', array($this,'add_responsive_menu'),100,3);
			}
			if ($header = $this->get_option('header')) {
				add_filter('wp_nav_menu', array($this,'add_responsive_widget_menu'),100,2);
			}
			if ($primary || $secondary || $header) {				
				add_action('wp_enqueue_scripts',array($this,'enqueue_dashicons'));
                add_action('wp_enqueue_scripts', array($this,'enqueue_jquery'));
				if (in_array('left',array($primary,$secondary,$header)) || in_array('right',array($primary,$secondary,$header))) {
					add_action('wp_enqueue_scripts',array($this,'enqueue_sidr_styles'));
					add_action('wp_enqueue_scripts',array($this,'enqueue_sidr_scripts'));
				}
				add_action('wp_print_styles', array($this, 'print_styles'));
				add_action('wp_print_footer_scripts', array($this, 'print_scripts'));
			}				
		}

	 	if (($search = $this->get_option('search_menu')) && ('none' != $search)) {
	 		add_filter('wp_nav_menu_items',  array($this,'maybe_add_search_form'),10,2 );	
	 		add_action('wp_enqueue_scripts', array($this,'enqueue_search_styles'));
	 	}

	}

    function enqueue_jquery() {
	   wp_enqueue_script('jquery');
    }

	function enqueue_dashicons() {
		wp_enqueue_style('dashicons');
	}

	function enqueue_sidr_styles() {
		wp_enqueue_style('jquery-sidr', plugins_url('styles/jquery.sidr.dark.css',dirname(__FILE__)), array(), '1.2.1');
	}

	function enqueue_sidr_scripts() {
		wp_enqueue_script('jquery-sidr', plugins_url('scripts/jquery.sidr.min.js',dirname(__FILE__)), array('jquery'), '1.2.1', true);
	}

	function enqueue_search_styles() {
		wp_enqueue_style(self::SEARCH_STYLE, plugins_url('styles/menu-search.css',dirname(__FILE__)), array(), '1.0');

      $placeholder_css = $css = '';
	
		if ($search_text_color = $this->get_option('search_text_color')) {
         $placeholder_css = sprintf('.genesis-nav-menu li.searchbox input::-webkit-input-placeholder{color: %1$s;} .genesis-nav-menu li.searchbox input::-moz-input-placeholder {color: %1$s;} .genesis-nav-menu li.searchbox input:-ms-input-placeholder {color: %1$s;}',$search_text_color) ."\n";
         $css .= sprintf('color: %1$s;',$search_text_color);
		}
		if ($search_background_color = $this->get_option('search_background_color')) {
         $css .= sprintf('background-color:%1$s;',$search_background_color);
		}
		if ($search_border_color = $this->get_option('search_border_color')) {
         $css .= sprintf('border-width: 2px; border-style: solid; border-color:%1$s;',$search_border_color);
		}
		if ($search_border_radius = $this->get_option('search_border_radius')) {
         $css .= sprintf('border-radius:%1$spx;',$search_border_radius);
		}
      if (!empty($css)) {
         $css = sprintf('.genesis-nav-menu li.searchbox form.search-form input[type=\'search\'], .genesis-nav-menu li.searchbox form.searchform input[type=\'text\'] { %1$s }', $css) ."\n";
    }

      $padding = '';
		if ($top = $this->check_limit($this->get_option('search_padding_top'))) {
         $padding .= sprintf('padding-top:%1$spx;',$top);
      }
      if ($bottom = $this->check_limit($this->get_option('search_padding_bottom'))) {
         $padding .= sprintf('padding-bottom:%1$spx;',$bottom);
      }
      if (!empty($padding))  {
         if ($threshold = $this->check_limit($this->get_option('search_padding_threshold'), 1600))       
            $css .= sprintf ('@media only screen and (min-width: %1$spx) { .genesis-nav-menu li.searchbox {%2$s} }', $threshold, $padding) . "\n";
         else
            $css .= sprintf ('.genesis-nav-menu li.searchbox {%1$s} ', $padding) . "\n";
		}
      if (!empty($css)) {
         wp_add_inline_style( self::SEARCH_STYLE, $css . $placeholder_css);
      }
	}

	function maybe_add_search_form($items, $args) {
      $search_menu = $this->get_option('search_menu');
      if (($args->theme_location == $search_menu)
      || (('header' == $search_menu) && has_filter('wp_nav_menu', 'genesis_header_menu_wrap')))  {
         add_filter( 'genesis_search_text',  array($this, 'set_search_placeholder'));
         return $items . sprintf('<li class="searchbox%2$s">%1$s</li>', get_search_form( false ), $this->get_option('search_button') ? '' : ' nobutton' ) ;	         
      } else {
  		return $items;
	}
	}

	function set_search_placeholder($content) {
		return $this->get_option('search_text');
	} 

	function add_responsive_menu($content, $menu, $args) {
		if (strpos($content, $this->is_html5 ? '<nav class="nav-primary' : '<div id="nav') !== FALSE) 
			return $this->maybe_prefix_responsive_menu($content, $menu, 'primary') ;
		elseif (strpos($content, $this->is_html5 ? '<nav class="nav-secondary'  : '<div id="subnav')  !== FALSE)  
			return $this->maybe_prefix_responsive_menu($content, $menu, 'secondary') ;	
		else 
			return $content;
	}

	function add_responsive_widget_menu($content, $args) {
		if (has_filter('wp_nav_menu', 'genesis_header_menu_wrap'))
			return $this->maybe_prefix_responsive_menu($content, $content, 'header') ;		
		else
			return $content;
	}

	private function maybe_prefix_responsive_menu($content, $menu, $option) {
		$resp_menu  = $this->get_option($option);
		$hamburger = sprintf('<div class="gc-responsive-menu-icon gcm-resp-%1$s"><div class="dashicons dashicons-menu"></div></div>', $resp_menu);
        $strip_menu = apply_filters( 'genesis_club_menu_stripper', preg_replace('#\s(id|class)="[^"]+"#', '', strip_tags($menu,'<ul><li><a><span>')), $menu);  //strip tags, ids and classes  

		switch ($resp_menu) {
			case 'left':
				$this->side_menu_left .= $strip_menu; 
				$prefix = $hamburger;
				break;
			case 'right': 
				$this->side_menu_right .= $strip_menu; 
				$prefix = $hamburger;
				break;
			case 'below': 
				$this->below_menu = true;
				$prefix = $hamburger;
				break;
			default: $prefix ='';
		}
		return $prefix . $content;
	}

	private function check_limit($item, $limit = 50) {
		return (is_numeric($item) && (abs($item) <= $limit)) ? $item : false;
	}

	private function check_size($item, $default) {
		return (is_numeric($item) && ($item >= 1)) ? $item : $default;		
	}

	private function check_color($color) {
		return preg_match('/^#?[0-9a-f]{3}(?:[0-9a-f]{3})?$/iD', $color) ? $color : '#888' ;
	}

	private function check_unit($item) {
		$str = str_replace(' ','',trim($item));
		$suffix='';
		if (empty($str) || ('auto'==$str)
		|| (strstr($str, 'px') !== false) 
		|| (strstr($str, '%') !== false)) 
			return $str;
		 else
			return $str. 'px';
	}	

	function print_styles() { 
		$minimum_device_width = $this->check_unit($this->get_option('threshold'));
		$color = $this->check_color($this->get_option('icon_color'));
		$rsize = $this->check_size($this->get_option('icon_size'),2.4);
		$psize = round($rsize*10);	
    	print <<< CSS
<style type="text/css" media="screen"> 
.gc-responsive-menu-icon { display: none; text-align: center; }
.gc-responsive-menu-icon.gcm-resp-left.gcm-open { text-align: left; }
.gc-responsive-menu-icon.gcm-resp-right.gcm-open { text-align: right; }
.gc-responsive-menu-icon .dashicons { color: {$color}; font-size: {$psize}px; font-size: {$rsize}rem; height: {$psize}px; height: {$rsize}rem; width: {$psize}px;  width: {$rsize}rem;}
@media only screen and (max-width: {$minimum_device_width}) {   
.gc-responsive-menu { display: none; }
.gc-responsive-menu-icon { display: block; }
} 		
</style>

CSS;
		}
	
    function print_scripts () {
		if ($this->below_menu) $this->print_below_scripts();
		if ($this->side_menu_left) $this->print_side_scripts('left',$this->side_menu_left);
		if ($this->side_menu_right)	$this->print_side_scripts('right', $this->side_menu_right);
		$icon_color = $this->get_option('icon_color');
		if (empty($icon_color)) $this->print_dynamic_color_script();			
	}

    private function print_side_scripts($side, $menu) {
			$minimum_device_width = $this->get_option('threshold');
			print <<< MENU
<script type="text/javascript">
//<![CDATA[
jQuery(document).ready(function($) {
    $(".gc-responsive-menu-icon.gcm-resp-{$side}").next().addClass("gc-responsive-menu");
    $(".gc-responsive-menu-icon.gcm-resp-{$side}").sidr({
      name: "sidr-{$side}",
      source: "#sidr-{$side}",
      side: "{$side}"
    });   
	$(".gc-responsive-menu-icon.gcm-resp-{$side}" ).click(function() {
  		$(this).toggleClass("gcm-open");
	});
	$(window).resize(function(){ 
		if(window.innerWidth > {$minimum_device_width}) { 
			$.sidr("close", "sidr-{$side}");
		}
	});
});
//]]>
</script>
<div id="sidr-{$side}"><nav class="nav-sidr">{$menu}</nav></div>	
MENU;
    }	

    private function print_below_scripts() {
		$minimum_device_width = $this->get_option('threshold');
		print <<< SCRIPT
<script type="text/javascript">
//<![CDATA[
jQuery(document).ready(function($){
	$(".gc-responsive-menu-icon.gcm-resp-below").next().addClass('gc-responsive-menu');
	$(".gc-responsive-menu").find(".responsive-menu-icon").remove();
	$(".gc-responsive-menu").find("ul").removeClass("responsive-menu");
	$(".gc-responsive-menu-icon.gcm-resp-below").click(function(){ $(this).next().slideToggle();});
	$(window).resize(function(){ if(window.innerWidth > {$minimum_device_width}) { $(".gc-responsive-menu").removeAttr("style");}});
});
//]]>
</script>
	
SCRIPT;
    }	

    private function print_dynamic_color_script() {	
		print <<< SCRIPT
<script type="text/javascript">
//<![CDATA[
jQuery(document).ready(function($){
	$(".gc-responsive-menu-icon").each( function(index) {
			var color = $(this).next().find('a:first-child').css('color');
			$(this).find('.dashicons').css("color",color);
	});
});
//]]>
</script>
	
SCRIPT;
    }	

}
