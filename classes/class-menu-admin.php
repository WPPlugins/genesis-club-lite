<?php
class Genesis_Club_Menu_Admin extends Genesis_Club_Admin {
    private $menu;
	protected $tips = array(
		'threshold' => array('heading' => 'Device Threshold', 'tip' => 'Enter the size in pixels at which the full menu is collapsed into the "hamburger" icon or leave blank to disable this feature.'),
		'icon_size' => array('heading' => 'Hamburger Icon Size', 'tip' => 'Size of the menu icon measured in rem, or leave blank to use the default which is 2.4 (1.5 times the size of a small icon).'),
		'icon_color' => array('heading' => 'Hamburger Icon Color', 'tip' => 'Color of the hamburger menu icon (e.g #808080), or leave blank if you want the icon to adopt the same color as the links in the menu.'),
		'primary' => array('heading' => 'Primary Menu', 'tip' => 'Choose where you want the primary menu to be displayed when the hamburger is clicked.'),
		'secondary' => array('heading' => 'Secondary Menu', 'tip' => 'Choose where you want the secondary menu to be displayed when the hamburger is clicked.'),
		'header' => array('heading' => 'Header Right Menu', 'tip' => 'Choose where you want the header right menu to be displayed when the hamburger is clicked.'),
		'search_menu' => array('heading' => 'Search Box Location', 'tip' => 'Here you can add a search box to the end of one of the menus'),
		'search_text' => array('heading' => 'Search Box Text', 'tip' => 'Enter the placeholder text you want to appear in the search box'),
		'search_text_color' => array('heading' => 'Text Color', 'tip' => 'Choose the color of the text in the search box'),
		'search_background_color' => array('heading' => 'Background Color', 'tip' => 'Choose the background color of the search box'),
		'search_border_color' => array('heading' => 'Border Color', 'tip' => 'Choose a color if you want a border around the search box or clear the color to have no border'),
		'search_border_radius' => array('heading' => 'Border Radius', 'tip' => 'You have an option to give the search box rounded corners. For example, for moderate rounding use 5 (px); for 0 for square corners'),
		'search_padding_top' => array('heading' => 'Padding Top', 'tip' => 'Enter padding above the search box (limit is 50px)'),
		'search_padding_bottom' => array('heading' => 'Padding Bottom', 'tip' => 'Enter padding below the search box (limit is 50px)'),
		'search_padding_threshold' => array('heading' => 'Padding Threshold', 'tip' => 'Remove padding for devices smaller than this threshold or leave blank to retain padding for all sizes of device'),
		'search_button' => array('heading' => 'Add Search Button', 'tip' => 'Click checkbox to show a search button (providing your WordPress theme has a visible search button)')
		);
		
	
	function init() {
	    $this->menu = $this->plugin->get_module('menu');
		add_action('admin_menu',array($this, 'admin_menu'));
	}
	
	function admin_menu() {
		$this->screen_id = add_submenu_page($this->get_parent_slug(), __('Menus'), __('Menus'), 'manage_options', 
			$this->get_slug(), array($this,'page_content'));
		add_action('load-'.$this->get_screen_id(), array($this, 'load_page'));
	}

	function page_content() {
		$title =  $this->admin_heading('Genesis Club Menu Settings');				
		$this->print_admin_form($title, __CLASS__, $this->get_keys()); 
	}    

	function load_page() {
		$this->set_tooltips($this->tips);
 		if (isset($_POST['options_update'])) $this->save_menu();
		$this->add_meta_box('intro', 'Intro',  'intro_panel');
		$this->add_meta_box('menu', 'Menu Settings', 'menu_panel', array ('options' => $this->menu->get_options()));
		$this->add_meta_box('news', 'Genesis Club News', 'news_panel',null, 'advanced');
		add_action ('admin_enqueue_scripts',array($this, 'enqueue_admin_styles'));
		add_action ('admin_enqueue_scripts',array($this, 'enqueue_metabox_scripts'));
		add_action ('admin_enqueue_scripts',array($this, 'enqueue_postbox_scripts'));
	}

	function save_menu() {
		check_admin_referer(__CLASS__);
		return $this->save_options($this->menu, 'Menu');
	}

	function search_panel($options){	
      return 	
         $this->fetch_form_field('search_menu', $options['search_menu'], 'select', 
            array('none' => 'No Search Box','primary' => 'Primary Navigation', 'secondary' => 'Secondary Navigation', 'header' => 'Header Right')).
         $this->responsive_text_field( 'search_text', $options['search_text'], 20 ) .
         $this->responsive_text_field( 'search_text_color', $options['search_text_color'], 7, '', 'color-picker' ) .
         $this->responsive_text_field( 'search_background_color', $options['search_background_color'], 7, '', 'color-picker' ) .
         $this->responsive_text_field( 'search_border_color', $options['search_border_color'], 7, '', 'color-picker' ) .
         $this->responsive_text_field( 'search_border_radius', $options['search_border_radius'], 5, 'px' ) .
         $this->responsive_text_field('search_padding_top',$options['search_padding_top'], 2, 'px') .
         $this->responsive_text_field('search_padding_bottom',$options['search_padding_bottom'], 2, 'px') .
         $this->responsive_text_field('search_padding_threshold',$options['search_padding_threshold'], 4, 'px') .
         $this->fetch_form_field('search_button', $options['search_button'], 'checkbox');
   }	

	function hamburger_panel($options){	
      return 	
         $this->responsive_text_field( 'threshold', $options['threshold'], 4, 'px' ) .
         $this->responsive_text_field( 'icon_size', $options['icon_size'], 4, 'rem' ) .
         $this->responsive_text_field( 'icon_color', $options['icon_color'], 7, '', 'color-picker' ) .
         $this->responsive_menu_locations('primary', $options['primary']) .
         $this->responsive_menu_locations('secondary', $options['secondary']) .
         $this->responsive_menu_locations('header', $options['header']);
	}	

	function responsive_menu_locations($name, $val) {
		$opts = array(
			'0' => 'No responsive menu', 
			'below' => 'Menu slides open below the hamburger');
		$sidr = array(
			'left' => 'Menu slides open on the left of the screen',
			'right' => 'Menu slides open on the right of the screen');	
		if ($this->utils->is_html5()) $opts = $opts + $sidr;	
		return $this->fetch_form_field($name,  $val, 'select', $opts) ;
	}

	function responsive_text_field($name, $val, $size, $suffix='', $class='') {	
		$args = array('size'=> $size);
		if (!empty($suffix)) $args['suffix'] = $suffix;
		if (!empty($class)) $args['class'] = $class;
		return $this->fetch_text_field($name, $val, $args) ;
	}

	function menu_panel($post,$metabox) {
      $options = $metabox['args']['options'];
      print $this->tabbed_metabox( $metabox['id'], apply_filters( 'genesis_club_menu_settings', array(
         'Reponsive Hamburger' => $this->hamburger_panel($options),
         'Search' => $this->search_panel($options),
		), $options));
   }	

 	function intro_panel(){	
		print <<< INTRO_PANEL
<p>The following section allows you to set up responsive hamburger menus and a search box on the primary, secondary and right header navigation areas.</p>
INTRO_PANEL;
	}

}
