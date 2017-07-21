<?php
class Genesis_Club_Seo_Admin extends Genesis_Club_Admin {
   private $tab = '404';

   private $yoast_installed = false;
   protected $redirects;
   protected $seo;
   
    private $tips = array(
        'alt_404_page' => array('heading' => 'Alternative 404 Page', 'tip' => 'Send the user to your own custom 404 page if the chosen page is not found. You might want to do this for a period after a site move or a major site restructuring exercise where there are a lot of 404 errors.'),
        'alt_404_status' => array('heading' => 'HTTP Status', 'tip' => 'Normally you find want to return 404 however you can choose to return a 410 if say, you have just deleted a whole bunch of pages from your site, or if your site is narrowly based you might want to return a 301 providing the chosen alternative 404 page has a canonical URL.'),
        'clicky_id' => array('heading' => 'Clicky ID', 'tip' => 'Clicky Site ID - this is a number'),
        'gtm_container_id' => array('heading' => 'Container ID', 'tip' => 'The Google Tag Manager container ID typically starts with GTM-'),
        'gtm_track_members' => array('heading' => 'Track Members', 'tip' => 'You may want to check this option if your are running a membership site. This will logged in users who are subscribers but NOT track authors, editors or administrators.'),
        'home_script' => array('heading' => 'Home Page Script', 'tip' => 'Script will only be added at the foot of the home page. Please include your own open and closing script tags.'),
        'remove_versions' => array('heading' => 'Remove Script Version', 'tip' => 'Remove version numbers from SRC when loading stylesheets and scripts. This improves your site optimization ratings with the likes of GTMetrix.'),
    );

 
   function is_yoast_installed() {
      return $this->yoast_installed;
   }

	function init() {
		require_once(dirname(__FILE__).'/class-seo-admin-redirects.php');
	    $this->seo = $this->plugin->get_module('seo');
	    $this->yoast_installed = $this->utils->is_yoast_installed();
		$this->redirects = new Genesis_Club_Seo_Redirects_Admin($this->version, $this->path, $this->get_parent_slug(), $this->slug);
		add_action('genesis_club_hiding_settings_save', array($this, 'save_hide_from_search'), 10, 1);
		add_filter('genesis_club_hiding_settings_show', array($this, 'add_hide_from_search'), 10, 2);    
		add_action('admin_menu',array($this, 'admin_menu'));
	}

	function admin_menu() {
		$this->screen_id = add_submenu_page($this->get_parent_slug(), __('Genesis Club SEO'), __('SEO'), 'manage_options', 
			$this->get_slug(), array($this,'page_content'));
		add_action('load-'.$this->get_screen_id(), array($this, 'load_page'));			
	}		

	function page_content() {
		$title =  $this->admin_heading('Genesis Club SEO Settings');				
		$this->print_admin_form($title, __CLASS__, false, $this->get_keys(), $this->get_tabs($_SERVER['REQUEST_URI'])); 
	}

	function save_seo() {
        check_admin_referer(__CLASS__);
		return $this->save_options($this->seo, __('SEO',GENESIS_CLUB_DOMAIN ));
	}

	function load_page() {
 		add_action ('admin_enqueue_scripts', array($this, 'enqueue_styles')); 
		add_action ('admin_enqueue_scripts',array($this, 'enqueue_postbox_scripts'));
		$tab = array_key_exists('tab',$_GET) ? $_GET['tab'] : 'general';		
		switch ($tab) {
			case 'redirects' : $this->redirects->load_page(); break;
			case 'yoast' : $this->load_page_yoast(); break;
			default: {
                if (isset($_POST['options_update']) ) $this->save_seo();
                $callback_params = array ('options' => $this->seo->get_options(false));
                $this->add_meta_box('intro', 'Intro',  'intro_panel');
                $this->add_meta_box('search', 'General Settings', 'general_panel', $callback_params);
                $this->add_meta_box('news', 'Genesis Club News', 'news_panel', null, 'advanced');
                $this->set_tooltips($this->tips);
        		add_action ('admin_enqueue_scripts',array($this, 'enqueue_admin_styles'));
                add_action ('admin_enqueue_scripts',array($this, 'enqueue_metabox_scripts'));
		}
	}
	}

	function load_page_yoast() {
		require_once(dirname(__FILE__).'/class-seo-admin-yoast.php');
		$yoast = new Genesis_Club_Seo_Yoast_Admin($this->version, $this->path, $this->get_parent_slug(), $this->slug);
 		$yoast->load_page();
	}


	function enqueue_styles() {
		$this->enqueue_admin_styles();	
		wp_enqueue_style($this->get_code('seo'), plugins_url('styles/seo.css',dirname(__FILE__)), array(),$this->get_version());
 		wp_enqueue_script($this->get_code('seo'), plugins_url('scripts/seo.js',dirname(__FILE__)), array(),$this->get_version());
  }
  
	function get_tabs($current_url) {
		$s='';
		$tabs = array(
			'general' =>  __( 'General Settings' ),
			'redirects' =>  __( 'SEO Redirects' ),
			'yoast' =>  __( $this->is_yoast_installed() ? 'Migrate Genesis SEO to Yoast SEO' : 'Migrate Yoast SEO to Genesis SEO' ),
		);
		
		if (strpos($current_url,'tab=') !== FALSE) {
			$tab=substr($current_url,strpos($current_url,'tab=')+4);
			if (strpos($tab,'&') !== FALSE) $tab = substr($tab,0,strpos($tab,'&'));
		} else {
			$tab = 'general';
		}
		foreach ( $tabs as $tab_id => $label ) {
			$url = admin_url(sprintf('admin.php?page=%1$s&tab=%2$s', $this->get_slug(), $tab_id));
			$s .= sprintf('<a href="%1$s" class="nav-tab %2$s">%3$s</a>',
				$url, $tab == $tab_id ? ' nav-tab-active' : '', esc_html($label) );
		}
		return sprintf('<h3 class="nav-tab-wrapper">%1$s</h3>',$s); 
	}

	function general_panel($post, $metabox) {
        $options = $metabox['args']['options'];
        print $this->tabbed_metabox( $metabox['id'], array (
            'Alternate 404 Page' => $this->alt_404_panel($options),
            'Clicky' => $this->clicky_panel($options),
            'Google Tag Manager' => $this->gtm_panel($options),
            'Scripts' => $this->scripts_panel($options),
        ));
        if ($keys = $this->get_keys()) {
			$keys = is_array($keys) ? implode(',', $keys) : $keys;
			printf('<input type="hidden" name="page_options" value="%1$s" />', $keys);
        }      
        echo $this->submit_button();
    }	

	function scripts_panel($options) {
         return
            $this->fetch_form_field('remove_versions', $options['remove_versions'],  'checkbox') .
            $this->fetch_form_field('home_script', $options['home_script'], 'textarea', array(), array('rows' => 10, 'cols' => 50, 'class' => 'large-text'));
	}

	function clicky_panel($options) {
         return
            $this->fetch_form_field('clicky_id', $options['clicky_id'], 'number', array(), array('size' => 10, 'min' => 1));
	}

	function gtm_panel($options) {
         return
            $this->fetch_form_field('gtm_container_id', $options['gtm_container_id'], 'text', array(), array('size' => 12)) .
            $this->fetch_form_field('gtm_track_members', $options['gtm_track_members'],  'checkbox');
	}

	function alt_404_panel($options) {
        if ( ! ($status = $options['alt_404_status'])) $status = '404';
        add_filter('wp_dropdown_pages', array($this, 'add_home_to_pages_dropdown'), 10, 3);      
        return
            $this->fetch_form_field('alt_404_page', $options['alt_404_page'], 'page', array(), array('show_option_none' => 'Use default 404 page')) .
            $this->fetch_form_field('alt_404_status', $status,  'radio', 
                array('404' => '404 - Not Found', '410' => '410 - Gone Away', '301' => '301 - Moved Permanently', '307' => '307 - Moved Temporarily'));
	}

    function add_home_to_pages_dropdown($output, $args, $pages) {
        $selected = 'home' == $args['selected'] ? ' selected="selected"' : '';
        return preg_replace('#</select>$#', '<option'.$selected.' value="home">Home Page</option></select>', trim($output)); 
    }

	function add_hide_from_search($content, $post) {
      return $content . $this->fetch_hide_from_search($post);
    }

	function fetch_hide_from_search($post) {
		return $this->visibility_checkbox($post->ID,'hide', 'from_search',
		   __('%1$s this page on the site search results page', GENESIS_CLUB_DOMAIN)); 
    }

	function save_hide_from_search($post_id) {
		$meta_key = Genesis_Club_Seo::HIDE_FROM_SEARCH_METAKEY;	
		update_post_meta( $post_id, $meta_key, array_key_exists($meta_key, $_POST) ? $_POST[$meta_key] : false);
	}

    function upgrade() {
	   $this->seo->save_redirects(); 
    }
    
 	function intro_panel($post,$metabox){		
		print <<< INTRO_PANEL
<p>Under General Settings, you can set up a custom 404 page, set up Google Tag Manager, or add a home page scripts; other tabs are typically used for migration of SEO settings.</p>
INTRO_PANEL;
	}    

}
