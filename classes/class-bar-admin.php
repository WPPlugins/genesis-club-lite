<?php
class Genesis_Club_Bar_Admin extends Genesis_Club_Admin {
    const TOGGLE_BAR = 'genesis_club_toggle_bar';

   private $bar;
   
	private $archive_tips = array(
	   Genesis_Club_Bar::HIDE_BAR_METAKEY => array('heading' => 'Hide the top bar', 'tip' => 'Click to hide the top bar on this archive'),
	   Genesis_Club_Bar::SHOW_BAR_METAKEY => array('heading' => 'Show the top bar', 'tip' => 'Click to show the top bar on this archive'));
   
	private $tips = array(
			'bar_title' => array('heading' => 'Title', 'tip' => 'Only displayed on admin site for labelling purposes'),
			'bar_enabled' => array('heading' => 'Enable Default Bar', 'tip' => 'Click to enable this Top Bar on the home page, archives, posts, etc. If not enabled then any settings below are ignored and top bar widgets will be displayed'),
			'bar_full_message' => array('heading' => 'Full Message', 'tip' => 'Enter the full message you want to display. This can be HTML so can have a link or a button. This message will be displayed on all sizes of device if you leave the following fields blank.'),
			'bar_laptop_message' => array('heading' => 'Laptop Message', 'tip' => 'Enter the message you want to display on laptop devices - device width is between 800 and 1024 px. Leave blank to use the full message.'),
			'bar_tablet_message' => array('heading' => 'Tablet Message', 'tip' => 'Enter the message you want to display on tablets - device width is between 480 and 800 px. Leave blank to use the full message or the laptop size message if you have specified one.'),
			'bar_short_message' => array('heading' => 'Mobile Message', 'tip' => 'Enter the message you want to display on mobile devices under a width of 480px. This could be short message probably no more than 20 characters or maybe just a button with some text on it. Leave blank to use the full message, the laptop size message or the tablet size message if you have specified one.'),
			'bar_font_color' => array('heading' => 'Font Color', 'tip' => 'Enter the color of the font text.'),
			'bar_background' => array('heading' => 'Background', 'tip' => 'Enter the background for the bar. This can be a simple color, or a background image. For example, #FED444 url(http://images.site.com/bg.jpg) no-repeat fixed center top'),
			'bar_show_timeout'=> array('heading' => 'Delay to Show The Bar', 'tip' => 'Enter the number of seconds to wait before displaying the bar. Leave blank or set to zero if you want the bar to load immediately'),
			'bar_hide_timeout' => array('heading' => 'Delay to Hide The Bar', 'tip' => 'Enter the number of seconds to wait before hiding the bar. Leave blank or set to zero to have the bar remain visible.'),
			'bar_bounce' => array('heading' => 'Bounce', 'tip' => 'Click to have the bar bounce on being displayed to attract attention.'),
			'bar_shadow' => array('heading' => 'Shadow', 'tip' => 'Click to add a shadow under the bar.'),
			'bar_opener' => array('heading' => 'Opener Tab', 'tip' => 'Here you can choose have a Tab/Button on the right hand side on the bar that can be used to open the bar. This also adds a button on the bar to close it.'),
			'bar_location' => array('heading' => 'HTML Element', 'tip' => 'Leave the default setting of body unless your theme has a fixed header in which case enter the fixed element to which you want to attach the bar.'),
			'bar_position' => array('heading' => 'Position', 'tip' => 'Locate the bar at the top or the bottom of your chosen element.'),
			'bar_hide_on_home' => array('heading' => 'Hide On Home', 'tip' => 'Do not show the default top bar on the home page.'),
			'bar_show_only_on_home' => array('heading' => 'Show Only On Home', 'tip' => 'Show the default top bar only on the home page.'),
			);

	function init() {
	   $this->bar = $this->plugin->get_module('bar');
		add_action('admin_menu',array($this, 'admin_menu'));
		add_action('load-edit-tags.php', array($this, 'load_archive_page'));	
        add_action('load-term.php', array($this, 'load_archive_page'));	
		add_action('edit_term', array($this, 'save_archive'), 10, 2 );			
		add_action('genesis_club_hiding_settings_save', array($this, 'save_page_visibility'), 10, 1);
		add_filter('genesis_club_hiding_settings_show', array($this, 'add_page_visibility'), 10, 2);
	}
	
	function admin_menu() {
		$this->screen_id = add_submenu_page($this->get_parent_slug(), __('Bar'), __('Bar'), 'manage_options', 
			$this->get_slug(), array($this,'page_content'));
		add_action('load-'.$this->get_screen_id(), array($this, 'load_page'));
	}

	function page_content() {
 		$title = $this->admin_heading('Genesis Club Bar Settings');				
		$this->print_admin_form($title, __CLASS__, (array)$this->get_keys()); 
	} 

	function load_page() {
 		if (isset($_POST['options_update'])) $this->save_bar();	
		$callback_params = array ('options' =>  $this->bar->get_options());
		$this->add_meta_box('bar', 'Top Bar',  'intro_panel', $callback_params);
		$this->add_meta_box('defaults', 'Defaults',  'defaults_panel', $callback_params);
		$this->add_meta_box('news', 'Genesis Club News', 'news_panel',null, 'advanced');
		$this->set_tooltips($this->tips);
		add_action('admin_enqueue_scripts',array($this, 'enqueue_admin_styles'));
		add_action('admin_enqueue_scripts',array($this, 'enqueue_metabox_scripts'));
		add_action('admin_enqueue_scripts',array($this, 'enqueue_postbox_scripts'));
	}

 	function load_archive_page() {
        if (isset($_GET['post_type']) && $this->plugin->is_post_type_enabled($_GET['post_type'])) {
            $this->set_tooltips($this->archive_tips);
            add_action( Genesis_Club_Dashboard::ARCHIVE_HOOK_ID, array($this, 'add_archive_panel'), 10, 3 );	
        }
	}

	function save_archive($term_id, $tt_id) {
        $key = $this->bar->get_toggle_termmeta_key();
		return isset( $_POST['genesis_club_archive_bar'] )  ?
			$this->utils->update_term_meta( $term_id, $key, isset($_POST[$key])) : false;
	}	

	function add_archive_panel($content, $term, $slug) {
		$key = $this->bar->get_toggle_termmeta_key();
        $value = $this->utils->get_term_meta($term->term_id, $key, false);
        return $content + array(
            'Bar' => sprintf('<table class="form-table">%1$s</table><p class="meta-options"><input type="hidden" name="genesis_club_archive_bar" value="1" /></p>', 
                 $this->form_field($key, $key, $this->get_tip($key), $value,  'checkbox', array(), array(), 'tr'))) ;
    }	
   
	function esc_html_field($field_name) {
		if (array_key_exists($field_name, $_POST))
			$_POST[$field_name] = esc_html(trim(stripslashes($_POST[$field_name])));
	}

	function sanitise_bar() {
		$this->esc_html_field('bar_full_message');
		$this->esc_html_field('bar_laptop_message');
		$this->esc_html_field('bar_tablet_message');
		$this->esc_html_field('bar_short_message');
	}

	function save_bar() {
		check_admin_referer(__CLASS__);
		$this->sanitise_bar();
		return $this->save_options($this->bar, 'Bar', 4);
	}

    function bar_on_posts($post_type) {
		return 'post'==$post_type;
    }  
    
	function add_page_visibility($content, $post) {
      return $content . $this->fetch_page_visibility($post);
	}

	function fetch_page_visibility($post) {
		$key = self::TOGGLE_BAR;	
		$meta_key = $this->bar->get_toggle_postmeta_key($post->post_type);
		return $this->form_field($key, $key, 
			__(strpos($meta_key, 'hide') !== FALSE ? 'Do not show the top bar on this page' : 'Show the top bar on this page'), 
			get_post_meta($post->ID, $meta_key, true),  'checkbox', array(), array(), 'br') ;
    }

	function save_page_visibility($post_id) {
		$key = self::TOGGLE_BAR;
		$post_type = get_post_type( $post_id);
		$meta_key = $this->bar->get_toggle_postmeta_key($post_type);	
		update_post_meta( $post_id, $meta_key, array_key_exists($key, $_POST) ? $_POST[$key] : false);
	}

	function bar_visibility_panel() {
		global $post;
		$hide = $this->bar_on_posts($post->post_type) ;
		$key = $hide ? Genesis_Club_Bar::HIDE_BAR_METAKEY : Genesis_Club_Bar::SHOW_BAR_METAKEY;
		$toggle = get_post_meta($post->ID, $key, true);
		$bar_toggle = $toggle?' checked="checked"':'';		
		$action = $hide ? 'hide' : 'show'; 
		$label =  __($hide ? 'Do not show the top bar on this page' : 'Show the top bar on this page');
		print <<< BAR_VISIBILITY
<p class="meta-options"><input type="hidden" name="genesis_club_toggle_bar" value="{$action}" />
<label><input class="valinp" type="checkbox" name="{$key}" id="{$key}" {$bar_toggle} value="1" />&nbsp;{$label}</label></p>
BAR_VISIBILITY;
    }    
 

	function intro_panel($post,$metabox){	
		$options = $metabox['args']['options'];
		print <<< INTRO
<p>The top bar is a responsive bar that allows you add a message at the top of each page: you can display different messages on different devices. For example, you
can specify a click to call button on mobile devices.<p>
<p>Below you can set the default bar settings. Use this feature if you want to have the same message content in the top bar on most of the pages on the site. The default top bar is shown on the home page, on archive pages, on posts but not on pages or custom posts types by default. You can use the <em>Genesis Club Post Settings</em> in the Page Editor to  override this on a page by page basis.</p>
<p>With Genesis Club Pro you can use Genesis Club Top Bar widgets to override the top bar with a different message. We recommend you use a plugin such as Widget Logic to control which widgets appear on which pages.</p>
INTRO;
		print $this->fetch_form_field('bar_enabled',$options['enabled'], 'checkbox');
	}

 
 	function defaults_panel($post,$metabox) {
		$options = $metabox['args']['options'];
      print $this->tabbed_metabox( $metabox['id'], array(
         'Messages' => $this->messages_panel($options),
         'Colors' => $this->colors_panel($options),
         'Timings' => $this->timings_panel($options),
         'Effects' => $this->effects_panel($options),
         'Location' => $this->location_panel($options)
		));
	}

	function messages_panel($options){	
      return
         $this->fetch_text_field('bar_full_message',$options['full_message'], array('class' => 'large-text')).
         $this->fetch_text_field('bar_laptop_message',$options['laptop_message'],  array('class' => 'large-text')).
         $this->fetch_text_field('bar_tablet_message',$options['tablet_message'], array('class' => 'large-text')).
         $this->fetch_text_field('bar_short_message',$options['short_message'], array('class' => 'large-text'));
	}

	function colors_panel($options){	
		return
         $this->fetch_text_field('bar_font_color',$options['font_color'], array('size' => 8, 'class' => 'color-picker')).
         $this->fetch_text_field('bar_background',$options['background'], array('class' => 'large-text'));
	}

	function timings_panel($options){	
		return
         $this->fetch_text_field('bar_show_timeout',$options['show_timeout'], array('size' => 4, 'suffix' => 'seconds')).
         $this->fetch_text_field('bar_hide_timeout',$options['hide_timeout'], array('size' => 4, 'suffix' => 'seconds'));
	}

	function effects_panel($options){	
		return
         $this->fetch_form_field('bar_bounce',$options['bounce'], 'checkbox').
         $this->fetch_form_field('bar_shadow',$options['shadow'], 'checkbox').
         $this->fetch_form_field('bar_opener',$options['opener'], 'checkbox');
	}

	function location_panel($options){	
		return
         $this->fetch_text_field('bar_location',$options['location'], array('size' => 20)).
         $this->fetch_form_field('bar_position',$options['position'], 'radio', array('top' => 'Top', 'bottom' => 'Bottom')).
         $this->fetch_form_field('bar_hide_on_home',$options['hide_on_home'], 'checkbox').
         $this->fetch_form_field('bar_show_only_on_home',$options['show_only_on_home'], 'checkbox');
	}

}
