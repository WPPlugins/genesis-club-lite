<?php
class Genesis_Club_Seo_404_Admin extends Genesis_Club_Seo_Admin {
   const CODE = 'genesis-club-404'; //prefix ID of CSS elements

	private $tips = array(
		'alt_404_page' => array('heading' => 'Alternative 404 Page', 'tip' => 'Send the user to your own custom 404 page if the chosen page is not found.'),
		'alt_404_status' => array('heading' => 'HTTP Status', 'tip' => 'Normally you find want to return 404 however you can choose to return a 410 if say, you have just deleted a whole bunch of pages from your site, or if your site is narrowly based you might want to return a 301 providing the chosen alternative 404 page has a canonical URL.'),
	);

    function init() {
        $this->seo = $this->plugin->get_module('seo');
		add_action('genesis_club_hiding_settings_save', array($this, 'save_hide_from_search'), 10, 1);
		add_filter('genesis_club_hiding_settings_show', array($this, 'add_hide_from_search'), 10, 2);        
    }
	
	function load_page() {
 		if (isset($_POST['options_update']) ) $this->save_seo();
		$callback_params = array ('options' => $this->seo->get_options(false));
		$this->add_meta_box('intro', 'Intro',  'intro_panel');
		$this->add_meta_box('search', '404 Settings', 'search_panel', $callback_params);
		$this->add_meta_box('news', 'Genesis Club News', 'news_panel', null, 'advanced');
		$this->set_tooltips($this->tips);
		add_action ('admin_enqueue_scripts',array($this, 'enqueue_admin_styles'));
		add_action ('admin_enqueue_scripts',array($this, 'enqueue_metabox_scripts'));
		add_action ('admin_enqueue_scripts',array($this, 'enqueue_postbox_scripts'));
	}	

	function search_panel($post, $metabox) {
        $options = $metabox['args']['options'];
        print $this->tabbed_metabox( $metabox['id'], array ('Alternate 404 Page' => $this->alt_404_panel($options)));
        if ($keys = $this->get_keys()) {
			$keys = is_array($keys) ? implode(',', $keys) : $keys;
			printf('<input type="hidden" name="page_options" value="%1$s" />', $keys);
        }      
        echo $this->submit_button();
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
   
 	function intro_panel($post,$metabox){		
		print <<< INTRO_PANEL
<p>Here you can replace the default Genesis 404 page. You might want to do this for a period after a site move or a major site restructuring exercise where there are a lot of 404 errors.</p>
INTRO_PANEL;
	}
}

