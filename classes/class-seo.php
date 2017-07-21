<?php
class Genesis_Club_Seo extends Genesis_Club_Module { 
    const OPTION_NAME = 'redirects';
    const HIDE_FROM_SEARCH_METAKEY = '_genesis_club_hide_from_search'; //postmeta

	protected $defaults = array(
	   'alt_404_page' => 0, 
	   'alt_404_status' => 404,
	   'gtm_container_id' => '', 
	   'gtm_track_members' => false,
	   'clicky_id' => false,  
	   'home_script' => false, 
	   'remove_versions' => false,
	   'has_redirects' => false, 
	   'posts' => array(), 
	   'terms' => array(),
	) ;
	   
    private $alt_404_page = 0;

	protected $og_title = false;
	protected $og_desc = false;
	protected $og_image = false;
	private $clicky_id = false;

	function get_defaults() { return $this->defaults; }
	function get_options_name() { return self::OPTION_NAME; }
	
   function redirect_defaults() {
      return array('url' => '', 'status' => 301) ;      
   }

	function redirect_options() {
		return array(
			'301' => 'Redirect Permanent (301)',
			'302' => 'Redirect Found (302)',
			'307' => 'Redirect Temporary (307)');
	}	

   function init() {
		if (!is_admin()) {
            add_action('parse_query', array($this,'parse_query'));		
            add_action('wp', array($this,'prepare'));
        }
    }
	
	function parse_query() {
			if (is_404()) { 
				add_filter('wp_list_pages_excludes', array($this,'excluded_pages'));	
				add_filter('getarchives_where', array($this,'excluded_posts'),10,2);	
			}

			if (is_search()) {
				add_filter( 'posts_where' , array($this,'excluded_posts'),10,2 );
			}								
	}	
	
	function excluded_pages($content) {
		global $wpdb;
		$post_ids = $wpdb->get_col( $wpdb->prepare(
			"SELECT post_id FROM $wpdb->postmeta WHERE meta_key = %s AND meta_value = '1'",self::HIDE_FROM_SEARCH_METAKEY));
        if ($post_ids && is_array($post_ids)) return (array)$content + $post_ids; 
		return $content;
	} 
	
	function excluded_posts($content, $args) {
		global $wpdb;
        $post_ids = $wpdb->get_col( $wpdb->prepare(
        	"SELECT post_id FROM $wpdb->postmeta WHERE meta_key = %s AND meta_value = '1'",self::HIDE_FROM_SEARCH_METAKEY));
        if ($post_ids && is_array($post_ids)) $content .= sprintf (' AND ID NOT IN (%1$s)', implode(',',$post_ids));
		return $content;
	}

    function prepare() {
		if ($this->get_option('has_redirects')) 
            add_action('template_redirect', array($this,'maybe_redirect'), 20);    
		if ($this->get_option('alt_404_page')) 
			add_action('template_redirect', array($this,'maybe_redirect_404'),20);	
		if (!$this->utils->is_yoast_installed() && $this->utils->is_seo_framework_installed()) 
            $this->maybe_do_opengraph();					
        $this->maybe_add_clicky();
        $this->maybe_add_tag_manager();
        $this->maybe_add_home_script_in_footer();
		if ($this->get_option('remove_versions')) 
            $this->remove_loader_querystring();
    }

	function maybe_redirect_404($destination) {
	    if (is_404()
		&& ! is_robots() 
		&& ! is_feed() 
		&& ! is_trackback() 
		&& ( $page_id = $this->get_option('alt_404_page'))) {
            if ('home' == $page_id)
                $url = site_url();
            else {
                if ( ($page_id == get_query_var( 'page')) || (get_post_status($page_id ) != 'publish') ) return;
                $url = get_permalink($page_id);
            }
            $status = $this->get_option('alt_404_status') ;
            if (!$status) $status = 404;
            if (($status==404) && ('home' == $page_id)) $status = 302; 

            if ($status==404) {               
                $this->genesis_404($page_id); //replace the default 404 content  
            } else {           
                wp_redirect	($url, $status);
                exit;                
			} 
      }
	}	

	function get_redirects() {
 		return $this->options->validate_options($this->get_defaults(), $this->get_options());
   }

 	public function get_redirect($type, $id, $redirects = false ) {
		if (!$redirects) $redirects = $this->get_redirects();
		if (is_array($redirects)
		&& array_key_exists($type, $redirects)
		&& is_array($redirects[$type])
		&& array_key_exists($id, $redirects[$type]))
			return $redirects[$type][$id];
		else
			return $this->redirect_defaults();	
 	}
	
	public function save_redirects($type = 'posts', $id = false, $new_redirect = false) {
 		$redirects = $this->get_redirects();
        if ($type && $id) {
		if ($this->empty_redirect($new_redirect)) {
			if (is_array($redirects)
			&& array_key_exists($type,$redirects) 
			&& array_key_exists($id,$redirects[$type]))
				unset($redirects[$type][$id]); //delete it if it is present
		} else {
				$redirects[$type][$id] = $new_redirect ;
		}
        }
		$redirects['has_redirects'] = (count($redirects['posts']) > 0) || (count($redirects['terms']) > 0); 
		return $this->save_options($redirects);
	}
	
	public function delete_redirects() {
 		$redirects = $this->get_redirects();
 		$redirects['posts'] = array(); 
 		$redirects['terms'] = array(); 
 		$redirects['has_redirects'] = false; 
        return $this->save_options($redirects);
	}

	private function empty_redirect($redirect) {
		return ! ($redirect 
         && is_array($redirect) 		
		   && array_key_exists('url',$redirect) 
		   && !empty($redirect['url'])) ;
	}	

	function maybe_redirect() {
      if (($redirects = $this->get_redirects())
		&& ($id = get_queried_object_id()) 
		&& ((is_singular() && ($redirect = $this->get_redirect ('posts', $id, $redirects)))	
			 || (is_archive() && ($redirect  = $this->get_redirect ('terms', $id, $redirects))))
		&& ($redirect = $this->options->validate_options($this->redirect_defaults(), $redirect))
		&& $redirect['url']) {
         	wp_redirect( $redirect['url'], $redirect['status'] );
			exit;
		}
		return false;
	}

    function genesis_404($post_id) {
        $this->alt_404_page = $post_id;
        add_filter ('genesis_404_entry_title', array($this, 'genesis_404_entry_title'));
        add_filter ('genesis_404_entry_content', array($this, 'genesis_404_entry_content'));
    }
	
    function genesis_404_entry_title($title) {
        if( $post = get_post($this->alt_404_page)) $title = $post->post_title;
        return $title;
    }

    function genesis_404_entry_content($content) {
        if( $post = get_post($this->alt_404_page)) $content = apply_filters( 'the_content', $post->post_content );
        return $content;
    }
    
	function get_opengraph() {
        $opengraph = false;
        if ($social = $this->plugin->get_module('social')) {
            if (is_singular()) $opengraph = $social->get_opengraph( 'posts', get_queried_object_id());
            if (is_archive()) $opengraph = $social->get_opengraph( 'terms', get_queried_object_id());
            if (is_front_page()) $opengraph = $social->get_opengraph( 'home' );            
            }
        return $opengraph;
 	}  

	function maybe_do_opengraph() {
        if ($og = $this->get_opengraph()) {  //got values so save them in instance variable 
        if (array_key_exists('og_title', $og)) $this->og_title = $og['og_title'];
        if (array_key_exists('og_desc', $og)) $this->og_desc = $og['og_desc'];
        if (array_key_exists('og_image', $og)) $this->og_image = $og['og_image'];
            add_action('the_seo_framework_pre', array($this, 'override_opengraph') );    		 
            if (is_front_page()) add_filter( 'the_seo_framework_current_object_id', array($this, 'override_id'), 10, 2 );  		 
		}
	}

	function override_id( $id, $can_cache ) {
        return $this->og_image ? 'home' : $id; 
    }

	function override_opengraph($opengraph) {
        if ($this->og_title) add_filter('the_seo_framework_ogtitle_output', array($this, 'override_opengraph_title'));    		 
        if ($this->og_desc) add_filter('the_seo_framework_ogdescription_output', array($this, 'override_opengraph_desc'));    
        if ($this->og_image) add_filter('the_seo_framework_og_image_after_featured', array($this, 'override_opengraph_image'));    
        return $opengraph;
	}

	function override_opengraph_title( $title ) {
      return $this->og_title ? $this->og_title : $title; 
   }

	function override_opengraph_desc ( $desc ) {
      return $this->og_desc ? $this->og_desc : $desc; 
   }

	function override_opengraph_image( $image ) {
      return $this->og_image ? $this->og_image : $image; 
   }

    function maybe_add_tag_manager() {
        if ($container_id = $this->get_option('gtm_container_id')) {
            if (!is_user_logged_in()
            || ($this->get_option('gtm_track_members') && !(current_user_can('administrator') || current_user_can('editor')  || current_user_can('author')) )) {
                add_action('genesis_title', array($this,'add_tag_manager_script'));
                add_action('genesis_before', array($this,'add_tag_manager_noscript'));                            
            }
        }
    }
 
    function maybe_add_home_script_in_footer() {
        if ((is_home() || is_front_page()) && ($this->home_script = $this->get_option('home_script'))) {
            add_action('genesis_after', array($this, 'add_home_script') );
        }
    }
 
    function add_home_script() {
        print $this->home_script;
    }
    
    function remove_loader_querystring(){
        add_filter( 'style_loader_src', array($this,'remove_version'), 15, 1 );
        add_filter( 'script_loader_src', array($this,'remove_version'), 15, 1 );
    }

    function remove_version($src) {
        $parts = explode('?ver', $src);
        $uri = explode('&ver=', $parts[0]);
        return $uri[0];
    }

    function maybe_add_clicky() {
        if ($this->clicky_id = $this->get_option('clicky_id')) {
            if (!is_user_logged_in() ) {
                add_action('wp_footer', array($this,'add_clicky'));                           
            }
        }
    }

    function add_tag_manager_script() {
        $container_id = $this->get_option('gtm_container_id');
        print <<< SCRIPT

<!-- Google Tag Manager by GCPP -->
<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
})(window,document,'script','dataLayer','{$container_id}');</script>
<!-- End Google Tag Manager -->        

SCRIPT;
   }

    function add_tag_manager_noscript() {
        $container_id = $this->get_option('gtm_container_id');
        print <<< SCRIPT
        
<!-- Google Tag Manager (noscript)  by GCPP -->
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id={$container_id}"
height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
<!-- End Google Tag Manager (noscript) -->

SCRIPT;
   }
   
    function add_clicky() {
        $clicky_site_id = $this->clicky_id;
        print <<<SCRIPT

<!-- Clicky -->
<script type="text/javascript">
var clicky_site_id = {$clicky_site_id};
(function() {
  var s = document.createElement('script');
  s.type = 'text/javascript';
  s.async = true;
  s.src = '//static.getclicky.com/js';
  ( document.getElementsByTagName('head')[0] || document.getElementsByTagName('body')[0] ).appendChild( s );
})();
</script>
<noscript><p><img alt="Clicky" width="1" height="1" src="//in.getclicky.com/{$clicky_site_id}ns.gif" /></p></noscript>
<!-- End Clicky -->

SCRIPT;
    } 
   
}