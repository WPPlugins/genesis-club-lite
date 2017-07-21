<?php
class Genesis_Club_Display extends Genesis_Club_Module {
    const OPTION_NAME = 'display';
	const BEFORE_CONTENT_SIDEBAR_ID = 'genesis-before-content-sidebar-wrap';
	const BEFORE_ARCHIVE_SIDEBAR_ID = 'genesis-before-archive';
	const BEFORE_ENTRY_SIDEBAR_ID = 'genesis-before-entry';
	const BEFORE_ENTRY_CONTENT_SIDEBAR_ID = 'genesis-before-entry-content';
	const AFTER_ENTRY_CONTENT_SIDEBAR_ID = 'genesis-after-entry-content';
	const AFTER_ENTRY_SIDEBAR_ID = 'genesis-after-entry';
	const AFTER_ARCHIVE_SIDEBAR_ID = 'genesis-after-archive';
	const AFTER_CONTENT_SIDEBAR_ID = 'genesis-after-content-sidebar-wrap';
	const HIDE_TITLE_METAKEY = '_genesis_club_hide_title';
    const DISABLE_AUTOP_METAKEY = '_genesis_club_disable_autop';
    const DISABLE_BREADCRUMBS = '_genesis_club_disable_breadcrumbs';
    const CUSTOM_LOGIN_FORM_BGCOLOR = '#242010';
    const CUSTOM_LOGIN_FORM_OPACITY = 0.8;
    
	protected $is_html5 = false;
	protected $is_landing = false;
	protected $post_id = false;
	protected $og_title = false;
	protected $og_desc = false;
	protected $og_image = false;	
	protected $term_featured_image = false;	
	protected $postinfo_shortcodes = false;	
	protected $postmeta_shortcodes = false;	
	
	protected $defaults  = array(
		'remove_blog_title' => false,
		'logo' => '',
		'logo_alt' => 'Logo',
		'logo_nopin' => '',
		'read_more_text' => '',
		'read_more_class' => '',	
		'read_more_prefix' => '&hellip;',
		'comment_invitation' => '',
		'comment_notes_hide' => 0,
		'breadcrumb_prefix' => 'You are here: ',
		'breadcrumb_archive' => 'Archives for ',
		'postinfo_shortcodes' => '',
		'postmeta_shortcodes' => '',
		'no_page_postmeta' => false,
		'no_archive_postmeta' => false,
		'before_archive' => false,
		'after_archive' => false,
		'before_entry_content' => false,
		'after_entry_content' => false,
		'before_entry' => false,
		'after_entry' => false,
		'before_content' => false,
		'after_content' => false,
		'css_hacks' => false,
		'screen_reader' => false,
		'disable_emojis' => false,
		'custom_login_enabled' => false, 
		'custom_login_background' => '', 
		'custom_login_form_bgcolor' => '', 
		'custom_login_form_opacity' => '', 
		'custom_login_logo' => '', 
		'custom_login_button_color' => '', 
		'custom_login_button_text_color' => '#FFF',
		'custom_login_labels_color' => '#FFF',
		'custom_login_user_label' => 'User Login',
		'excerpt_images_on_front_page' => false,
		'archives' => false,
	);
	
	function get_defaults() {
       return $this->defaults; 
   }	

	function get_options_name() {
       return self::OPTION_NAME;
   }
		
	function init() {
		add_action('widgets_init', array($this,'register_sidebars'));
				
		if (!is_admin()) {
			add_action('pre_get_posts', array($this,'customize_archive'), 15 );			
			add_action('wp', array($this,'prepare'));
		}
		$this->custom_login();
	}	

    function register_sidebars() {
    	if ($this->get_option('before_content'))
			genesis_register_sidebar( array(
				'id' => self::BEFORE_CONTENT_SIDEBAR_ID,
				'name'	=> __( 'Before Content After Header', GENESIS_CLUB_DOMAIN ),
				'description' => __( 'Full width area below the header and above the content and any primary and secondary sidebars.', GENESIS_CLUB_DOMAIN)
			) );
    	if ($this->get_option('before_archive'))
			genesis_register_sidebar( array(
				'id' => self::BEFORE_ARCHIVE_SIDEBAR_ID,
				'name'	=> __( 'Before Archive', GENESIS_CLUB_DOMAIN ),
				'description' => __( 'Area at the top of the archive for adding an introductory slider', GENESIS_CLUB_DOMAIN)
			) );
    	if ($this->get_option('before_entry'))
			genesis_register_sidebar( array(
				'id' => self::BEFORE_ENTRY_SIDEBAR_ID,
				'name'	=> __( 'Before Entry', GENESIS_CLUB_DOMAIN ),
				'description' => __( 'Area before the entry for adding calls to action or ads', GENESIS_CLUB_DOMAIN)
			) );
    	if ($this->get_option('before_entry_content'))
			genesis_register_sidebar( array(
				'id' => self::BEFORE_ENTRY_CONTENT_SIDEBAR_ID,
				'name'	=> __( 'Before Entry Content', GENESIS_CLUB_DOMAIN ),
				'description' => __( 'Area before the post content for things like adding social media icons for likes and shares', GENESIS_CLUB_DOMAIN)
			) );
    	if ($this->get_option('after_entry_content'))
			genesis_register_sidebar( array(
				'id' => self::AFTER_ENTRY_CONTENT_SIDEBAR_ID,
				'name'	=> __( 'After Entry Content', GENESIS_CLUB_DOMAIN ),
				'description' => __( 'Area after the post content for adding things like social media icons for likes and shares', GENESIS_CLUB_DOMAIN),	
			) );
    	if ($this->get_option('after_entry'))
			genesis_register_sidebar( array(
				'id' => self::AFTER_ENTRY_SIDEBAR_ID,
				'name'	=> __( 'After Entry', GENESIS_CLUB_DOMAIN ),
				'description' => __( 'Area after the entry for adding calls to action or ads', GENESIS_CLUB_DOMAIN),	
			) );
    	if ($this->get_option('after_archive'))
			genesis_register_sidebar( array(
				'id' => self::AFTER_ARCHIVE_SIDEBAR_ID,
				'name'	=> __( 'After Archive', GENESIS_CLUB_DOMAIN ),
				'description' => __( 'Area at the end of the archive for adding things like call to actions or ads', GENESIS_CLUB_DOMAIN),	
			) );
    	if ($this->get_option('after_content'))
			genesis_register_sidebar( array(
				'id' => self::AFTER_CONTENT_SIDEBAR_ID,
				'name'	=> __( 'After Content Before Footer', GENESIS_CLUB_DOMAIN ),
				'description' => __( 'Full width area just above the footer and below the content and any primary and secondary sidebars.', GENESIS_CLUB_DOMAIN)
			) );

    }

	function prepare() {
		$this->is_html5 = $this->utils->is_html5();
		$this->post_id = $this->utils->get_post_id(); //get post/page id
		$this->is_landing = $this->utils->is_landing_page();

		if ($this->get_option('remove_blog_title')) {
			remove_all_actions('genesis_site_description');
			add_filter ('genesis_seo_title', array($this,'blog_title_notext'), 11, 3);
		}
			
		if ($this->get_option('read_more_text')) { 
			add_filter('excerpt_more', array($this,'read_more_link') );
			add_filter('get_the_content_more_link', array($this,'read_more_link' ));
			add_filter('the_content_more_link', array($this,'read_more_link') );			
			add_filter('genesis_grid_loop_args', array($this,'set_read_more_text'));
			//Add a Read More for hand crafted excerpts
			if (is_archive()) add_filter('the_excerpt', array($this,'add_read_more_link'),30); 
		}

	 	if ($this->get_option('comment_invitation')) {
	 		add_filter($this->is_html5 ? 'comment_form_defaults' :'genesis_comment_form_args', 
	 			array($this,'comment_form_args'), 20 );	
		}

	 	if ($this->get_option('comment_notes_hide')) {
	 		add_filter($this->is_html5 ? 'comment_form_defaults' :'genesis_comment_form_args', 
	 			array($this,'comment_notes_hide'), 20 );	
		}
			
		if (is_archive() || is_singular() || is_front_page() || is_home()) {
			add_filter( 'genesis_breadcrumb_args', array($this, 'filter_breadcrumb_args' ) );
		}

        if ($this->should_show_sidebar('before_content', true) )
			add_action( 'genesis_before_content_sidebar_wrap', array($this, 'show_before_content_sidebar')); 

		if ($this->should_show_sidebar('after_content', true) )
			add_action( 'genesis_after_content_sidebar_wrap', array($this, 'show_after_content_sidebar')); 
								
		if (is_singular()
		&& ($post_type = get_post_type())
		&& $this->plugin->is_post_type_enabled($post_type)) {  //insert widgets before and after entries or entry content 

			if (get_post_meta($this->post_id, self::HIDE_TITLE_METAKEY, true))
				add_filter('genesis_post_title_text', '__return_empty_string', 100);

			if (get_post_meta($this->post_id, self::DISABLE_AUTOP_METAKEY, true))
				remove_filter('the_content', 'wpautop');

			if (get_post_meta($this->post_id, self::DISABLE_BREADCRUMBS, true))
				add_filter( 'genesis_pre_get_option_breadcrumb_' .(is_page() ? 'page':'single'),  '__return_false', 10, 2);

         if ($this->should_show_sidebar('before_entry'))
            add_action( $this->is_html5 ? 'genesis_before_entry' :'genesis_before_post', array($this, 'show_before_entry_sidebar'));

         if ($this->should_show_sidebar('after_entry'))
			   add_action( $this->is_html5 ? 'genesis_after_entry' :'genesis_after_post',  array($this, 'show_after_entry_sidebar'));

         if ($this->should_show_sidebar('before_entry_content'))
			   add_action( $this->is_html5 ? 'genesis_before_entry_content' :'genesis_after_post_title',  array($this, 'show_before_entry_content_sidebar'));

         if ($this->should_show_sidebar('after_entry_content'))
			   add_action( $this->is_html5 ? 'genesis_after_entry_content' :'genesis_after_post_content', array($this, 'show_after_entry_content_sidebar'));
		}
			
		if (is_archive()) { //insert widgets before and after entry archives 
			add_filter ('genesis_term_intro_text_output','do_shortcode',11); //convert shortcode in toppers
			if ($this->get_option('before_archive'))  
				add_action(  'genesis_before_loop' , 
					array($this, 'show_before_archive_sidebar')); 
			if ($this->get_option('after_archive'))  
				add_action('genesis_after_loop', 
					array($this, 'show_after_archive_sidebar'));
		}

		if (is_front_page()) {
			add_action( $this->is_html5 ? 'genesis_before_entry' :'genesis_before_post', array($this, 'maybe_replace_category_images')); 
		}

		if ($this->get_option('no_page_postmeta') && (is_page() || is_front_page())) {  //remove postinfo and postmeta on pages
			$this->replace_postinfo(false);
			$this->replace_postmeta(false);
		} elseif (($postinfo = $this->get_option('postinfo_shortcodes')) && (is_single() || ( is_page() && !$this->is_landing)))  {//replace postinfo 
			$this->replace_postinfo($postinfo);
		}
		 	
		if (($postmeta = $this->get_option('postmeta_shortcodes')) && is_single()) { //replace postmeta on posts 
			$this->replace_postmeta($postmeta);
		}

		if (is_single() && is_active_widget( false, false, 'genesis-club-post-image-gallery', false )) {
			add_thickbox();
		}

		
		if ( $this->is_landing )  {//disable breadcrumbs on landing pages
			add_filter('genesis_pre_get_option_breadcrumb_page', '__return_false');
		}

    	if ($this->get_option('disable_emojis')) $this->disable_emojis();

		add_action('wp_enqueue_scripts', array($this,'enqueue_styles'));
	}

	function enqueue_styles() {
		wp_enqueue_style('dashicons');
		if ($this->get_option('css_hacks')) 
			wp_enqueue_style('genesis-club-display', plugins_url('styles/display.css', dirname(__FILE__)), array(), GENESIS_CLUB_VERSION);
		if ($this->get_option('screen_reader')) 
			wp_enqueue_style('genesis-club-screen-reader', plugins_url('styles/screen-reader.css', dirname(__FILE__)), array(), GENESIS_CLUB_VERSION);
 	}

	function replace_postinfo($post_info = false) {
		if ($post_info && ($post_info != '[]')) {
			$this->postinfo_shortcodes = $post_info;
			add_filter ('genesis_post_info', array($this,'post_info')); 	 						
		} else {
			add_action('loop_start', array($this, 'delete_postinfo'));     
		}
	}

	function delete_postinfo($query) {
      if ($this->is_html5) 
			remove_action( 'genesis_entry_header', 'genesis_post_info', 12 );
		else 
			remove_action( 'genesis_before_post_content', 'genesis_post_info' );		 			
	}

	function replace_postmeta($post_meta = false) {
		if ($post_meta && ($post_meta != '[]')) {
         $this->postmeta_shortcodes = $post_meta;
 			add_filter ('genesis_post_meta', array($this,'post_meta')); 		 			
      } else {
         add_action('loop_start', array($this, 'delete_postmeta'));         
      }
	}
	
	function delete_postmeta($query) {
		if ($this->is_html5) 
            	remove_action( 'genesis_entry_footer', 'genesis_post_meta');
 			else 
				remove_action( 'genesis_after_post_content', 'genesis_post_meta' );		 			 			
	}

	function blog_title_notext($title, $inside, $wrap) {
		$logo = $this->get_option('logo');
		$logo_alt = $this->get_option('logo_alt');
		$logo_nopin = $this->get_option('logo_nopin');
		$data_nopin = empty( $logo_nopin) ? '' : 'data-pin-nopin="true" ';
		$url = ($logo && (substr($logo,0,2) == '//')  ? (is_ssl() ? 'https:':'http:') : '') . $logo;        
		$logo = filter_var($url, FILTER_VALIDATE_URL) ? sprintf('<img src="%1$s" alt="%2$s" %3$s/>', $logo, $logo_alt, $data_nopin) : $logo;
		if ($logo)
			if (strpos($logo, '[') === FALSE) /* Logo image URL gets wrapped as a clickable link */
				$inside = sprintf( '<a href="%1$s" title="%2$s" style="text-indent:0;background:none">%3$s</a>',
					trailingslashit( home_url() ), esc_attr( get_bloginfo('name')), $logo ) ;
			else  
				$inside = do_shortcode($logo); /* alternatively specify a slider shortcode for a dynamic logo */
		else
			$inside = '';
		$xtml = sprintf( '<div id="title">%1$s</div>', $inside);
		$html5 = sprintf( '<div class="site-title">%1$s</div>', $inside);
		return function_exists('genesis_html5') ?
			genesis_markup( array(
				'html5'   => $html5,
				'xhtml'   => $xtml,
				'context' => '',
				'echo'    => false) ) :
			genesis_markup($html5, $xtml, false ) ;
	}  
	

	
	function strip_rel_author ($content, $args) { return str_replace(' rel="author"','',$content); }
	function show_before_content_sidebar() { $this->show_sidebar(self::BEFORE_CONTENT_SIDEBAR_ID); }
	function show_before_archive_sidebar() { $this->show_sidebar(self::BEFORE_ARCHIVE_SIDEBAR_ID); }
	function show_before_entry_sidebar() { $this->show_sidebar(self::BEFORE_ENTRY_SIDEBAR_ID); }
	function show_before_entry_content_sidebar() { $this->show_sidebar(self::BEFORE_ENTRY_CONTENT_SIDEBAR_ID); }
	function show_after_entry_content_sidebar() { $this->show_sidebar(self::AFTER_ENTRY_CONTENT_SIDEBAR_ID); }
	function show_after_entry_sidebar() { $this->show_sidebar(self::AFTER_ENTRY_SIDEBAR_ID); }
	function show_after_archive_sidebar() { $this->show_sidebar(self::AFTER_ARCHIVE_SIDEBAR_ID); }
	function show_after_content_sidebar() { $this->show_sidebar(self::AFTER_CONTENT_SIDEBAR_ID); }
	
	function get_toggles($post_type) {
        $action = 'post'==$post_type ? 'hide' : 'show';
        $sidebars = array('after_entry', 'after_entry_content', 'after_content', 'before_content', 'before_entry', 'before_entry_content');
        $metakeys = array();
        foreach ($sidebars as $sidebar) $metakeys[] = $this->utils->get_toggle_post_meta_key($action, $sidebar);
        return $metakeys;
	}
	
	function get_disables() {
        return array(self::HIDE_TITLE_METAKEY, self::DISABLE_AUTOP_METAKEY, self::DISABLE_BREADCRUMBS) ;
	}	

	private function should_show_sidebar($sidebar, $archive_action = false) {
        if ($this->get_option($sidebar)) {
            if (is_archive())
                return  $archive_action;
			if (is_singular() && (! $this->is_landing) && $this->get_option($sidebar) && ($post_type = get_post_type())) {
            	$action = 'post'==$post_type ?  'hide' : 'show'; 
            	$toggle = $this->utils->get_post_meta_value(get_queried_object_id(), $this->utils->get_toggle_post_meta_key($action, $sidebar));
            	return 'hide'==$action ? !$toggle : $toggle; 
            }
        }
		return false;
	}

	private function show_sidebar($sidebar) {
		if ( is_active_sidebar( $sidebar) ) {
			$tag = $this->is_html5 ? 'aside' : 'div';
			printf ('<%1$s class="widget-area custom-post-sidebar %2$s">',$tag,$sidebar);
			dynamic_sidebar( $sidebar );
			printf ('</%1$s>',$tag);
		}
	}

	function add_read_more_link($content) {
		if (strpos($content, 'more-link') === FALSE) $content .=  $this->read_more_link();
		return $content; 
 	}

	function read_more_link() {
 		return $this->utils->read_more_link( $this->get_option('read_more_text'), $this->get_option('read_more_class') , $this->get_option('read_more_prefix') );
 	}

	function set_read_more_text($args) {
		$args['more'] = $this->get_option('read_more_text');
		return $args;
	}

	function comment_form_args($args) {
		$args['title_reply'] = $this->get_option('comment_invitation');
		return $args;
	}	
	
	function comment_notes_hide($args) {
		$hide = $this->get_option('comment_notes_hide');
		if (($hide == 'before') || ($hide == 'both')) $args['comment_notes_before'] = '';
		if (($hide == 'after') || ($hide == 'both')) $args['comment_notes_after'] = '';
		return $args;
	}

 	function post_info() {
 		return do_shortcode(stripslashes($this->postinfo_shortcodes));
 	}

 	function post_meta() {
 		return do_shortcode(stripslashes($this->postmeta_shortcodes));
 	}

	function filter_breadcrumb_args( $args ) {
		$prefix = trim($this->get_option('breadcrumb_prefix'));
		if (!empty($prefix)) $prefix .= '&nbsp;';
		$label = trim($this->get_option('breadcrumb_archive'));
		if (!empty($label)) $label .= '&nbsp;';
		$args['labels']['author']        = $label;
	   $args['labels']['category']      = $label;
	   $args['labels']['tag']           = $label;
	   $args['labels']['date']          = $label;
	   $args['labels']['tax']           = $label;
	   $args['labels']['post_type']     = $label; 
	   $args['labels']['prefix']        = $prefix;
		return $args;
	} 

	private function empty_archive($archive) {
		return ! ($archive && is_array($archive) 		
		&& (array_key_exists('sorting',$archive) 
		|| (array_key_exists('orderby',$archive) && !empty($archive['orderby'])) 
		|| (array_key_exists('order',$archive) && !empty($archive['order'])) ));
	}

	private function get_archives() {
 		return $this->get_option('archives');
	}

	private function save_archives($archives) {
      $display_options = $this->get_options(false);
      $display_options['archives'] = $archives;
 		return $this->save_options($display_options);
	}

	function save_archive($term_id, $new_archive) {
 		$archives = $this->get_archives();
		if ($this->empty_archive($new_archive)) {
			if (is_array($archives)
			&& array_key_exists($term_id,$archives))
				unset($archives[$term_id]); //delete it if it is present
		} else {
				$archives[$term_id] = $new_archive ;
		}
		return $this->save_archives($archives);
	}

 	function get_archive($term_id, $archives = false ) {
		if (!$archives) $archives = $this->get_archives();
		if ($term_id
		&& is_array($archives) 
		&& array_key_exists($term_id, $archives))
			return $archives[$term_id];
		else
			return array();	
 	}

	private function get_current_archive() {
		if ($term = $this->utils->get_current_term())
         return $this->get_archive($term->term_id) ;
			else
         return false;
	}

	function customize_archive( $query ) {
      if ($query->is_archive && ($archive = $this->get_current_archive())) {
         $this->maybe_sort_archive( $query, $archive);   
         $this->maybe_disable_breadcrumbs( $archive);   
         $this->maybe_override_terms_archive_image($archive);
         $this->maybe_override_post_info($archive);
         $this->maybe_override_post_meta($archive);
      }
 	}


	function maybe_disable_breadcrumbs($archive ) {
      if (array_key_exists('disable_breadcrumbs', $archive)
      && $archive['disable_breadcrumbs']) {
         add_filter( 'genesis_pre_get_option_breadcrumb_archive', '__return_false', 10, 2);  
	    }    		 
	}

	function maybe_override_post_info($archive ) {
      if (array_key_exists('postinfo_shortcodes', $archive)
      && ($postinfo = $archive['postinfo_shortcodes'])) {
  			$this->replace_postinfo($postinfo);
	   } elseif ($this->get_option('no_archive_postmeta'))  {
         $this->replace_postinfo();
	   }   		 
	}

	function maybe_override_post_meta($archive ) {
      if (array_key_exists('postmeta_shortcodes', $archive)
      && ($postmeta = $archive['postmeta_shortcodes'])) {
  			$this->replace_postmeta($postmeta);
	   } elseif ($this->get_option('no_archive_postmeta'))  {
         $this->replace_postmeta();
	   }    		 
	}

	function maybe_sort_archive( $query, $archive ) {
      if (array_key_exists('sorting', $archive)
      && array_key_exists('orderby', $archive)
      && array_key_exists('order', $archive)
      && $archive['sorting']
      && $query->is_main_query()) {
         $query->set( 'orderby', $archive['orderby'] );
         $query->set( 'order', $archive['order']);          
	    }    		 
	}

   function maybe_replace_category_images() {
		if (($post_id = $this->utils->get_post_id())
		&& ($terms = wp_get_post_terms( $post_id, 'category'))
		&& is_array($terms)
		&& (count($terms) > 0)
		&& ($term = $terms[0])
		&& ($archive = $this->get_archive($term->term_id))) {
			$this->maybe_override_terms_archive_image($archive, true);
		}
   }

   /* This function can be called on the home page or on archive pages */
   function maybe_override_terms_archive_image($archive, $is_front_page = false) {
 		if (isset($archive['excerpt_image']) 
 		&& ( ! $is_front_page  ||   
 			(isset($archive['excerpt_images_on_front_page']) && $archive['excerpt_images_on_front_page'])))
         	$this->term_featured_image = $archive['excerpt_image'];
		else 
         	$this->term_featured_image = '';
		add_filter('genesis_pre_get_image', array( $this,'get_featured_thumbnail'), 20, 3);         
   }

   function get_featured_thumbnail($content, $args, $post) {
      if ($this->term_featured_image) {
         $defaults= array('folder' => 'thumbnails','size' => 'thumbnail', 'attr' => array(), 'format' => 'html');
         $args = wp_parse_args ($args, $defaults); 
         return 'url'==$args['format'] ? $this->term_featured_image:
            sprintf('<img src="%1$s" alt="" %2$s/>' ,
               $this->term_featured_image, 
               (is_array($args['attr']) && array_key_exists('class',$args['attr'])) ? sprintf('class="%1$s"',$args['attr']['class']) : '');        
      } else {
         return $content; //otherwise use post featured image  
      }
   } 


	function disable_emojis() {
	  remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
	  remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
	  remove_action( 'wp_print_styles', 'print_emoji_styles' );
	  remove_action( 'admin_print_styles', 'print_emoji_styles' );	
	  remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
	  remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );	
	  remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
	  add_filter( 'tiny_mce_plugins', array($this,'disable_emojis_tinymce') );
   }

	function disable_emojis_tinymce( $plugins ) {
	  return is_array( $plugins ) ? array_diff( $plugins, array( 'wpemoji' ) ) : array();
	}

    function custom_login() {
 	  if ($this->get_option('custom_login_enabled')) {      
      add_action('login_head', array($this,'custom_login_header'));
      add_action('login_footer', array($this, 'custom_login_footer'));
 	  }     
   }

    function hex2rgb($hex, $opacity) {    
        $hex = str_replace("#", "", $hex);

        if (empty($hex)) $hex = self::CUSTOM_LOGIN_FORM_BGCOLOR;
        
        if(strlen($hex) == 3) {
            $r = hexdec(substr($hex,0,1).substr($hex,0,1));
            $g = hexdec(substr($hex,1,1).substr($hex,1,1));
            $b = hexdec(substr($hex,2,1).substr($hex,2,1));
        } else {
            $r = hexdec(substr($hex,0,2));
            $g = hexdec(substr($hex,2,2));
            $b = hexdec(substr($hex,4,2));
        }
        $opacity = floatval($opacity);
        if (empty($opacity)) $opacity = self::CUSTOM_LOGIN_FORM_OPACITY;
        
        return sprintf('rgba(%1$s, %2$s, %3$s, %4$s )', $r, $g, $b,  $opacity); 
    }

    function custom_login_header() {
      printf ('<link rel="stylesheet" href="%1$s" type="text/css" media="screen" />', plugins_url('styles/login.css', dirname(__FILE__)));
   }

	function custom_login_footer() {
	  $url = site_url();
	  $login = $this->get_option('login');
	  $login_background = $this->get_option('custom_login_background');	  
	  if (!empty($login_background)) $login_background = sprintf('url(\"%1$s\")', $login_background) ;  
	  $login_logo = $this->get_option('custom_login_logo');
	  $login_logo = empty($login_logo) ? 'none' :  sprintf('url(\"%1$s\")', $login_logo) ;  
	  $login_form_bgcolor = $this->hex2rgb($this->get_option('custom_login_form_bgcolor'), $this->get_option('custom_login_form_opacity')) ;  
	  $login_button = $this->get_option('custom_login_button_color');	 
	  $login_button_text_color = $this->get_option('custom_login_button_text_color');	
	  $login_reminder = __('Enter your email address. You will receive a password reminder via e-mail.');   
	  $login_user_label = $this->get_option('custom_login_user_label');
	  $login_labels_color = $this->get_option('custom_login_labels_color');
	  $jquery = site_url('wp-includes/js/jquery/jquery.js'); 
	  print <<< SCRIPT
<script type="text/javascript" src="{$jquery}"></script>
<script type="text/javascript">
//<![CDATA[
jQuery(document).ready(function(){
   var lf = jQuery('form#loginform');
   lf.prepend("<h2>Login</h2>");
    lf.css("background-color","{$login_form_bgcolor}");
    lf.find("p:first").replaceWith('<p><label>{$login_user_label}</label><input type="text" name="log" id="user_login" class="input" value="" size="20" tabindex="10"></p>');
    jQuery("h1 a").attr("href","{$url}").attr("title","Home Page").css("background-image","{$login_logo}");
   jQuery('#nav').appendTo(lf);
   jQuery("body").css("background-image","{$login_background}");
   jQuery("#wp-submit").css("background-color","{$login_button}");
    jQuery("#wp-submit").css("color","{$login_button_text_color}");
    jQuery("#loginform h2, #loginform label").css("color","{$login_labels_color}");
   jQuery("form#lostpasswordform").prepend("<h2>{$login_reminder}</h2>");
   jQuery("p.message,#login_error").filter(function() { return jQuery.trim(jQuery(this).text()).length == 0;}).remove(); 
});
//]]>
</script>
SCRIPT;
   }

}
