<?php
class Genesis_Club_Social extends Genesis_Club_Module {
    const OPTION_NAME = 'social';
   	
	const FACEBOOK_IMAGE_SCALE_FACTOR = 1.91;
	const FACEBOOK_FEATURED_IMAGE = 'fb-featured-image';
	const FACEBOOK_ARCHIVE_IMAGE = 'fb-archive-image';
    
    private $opengraph_metakey = Genesis_Club_Plugin::FACEBOOK_OPENGRAPH_METAKEY;

	protected $defaults  = array(
    'facebook' => array(
		  'app_id' => '',
		  'locale' => 'en_US',
		  'featured_images' => false,
		  'all_images' => false,
		  'og_title' => '',
		  'og_desc' => '', 
		  'og_image' => ''
		)
	);

	protected $opengraph_defaults  = array(
	   'og_title' => '',
	   'og_desc' => '', 
	   'og_image' => ''
	);

	function get_defaults() {
       return $this->defaults; 
    }
	
	function get_options_name() {
       return self::OPTION_NAME; 
   }
	
	function init() {
		add_action('widgets_init', array($this,'register_widgets'));				
    	if ($facebook = $this->get_option('facebook')) {
            if ( isset($facebook['featured_images']) && $facebook['featured_images']) {
                $this->set_facebook_featured_image_size();           
            }
        }
	}

	function register_widgets() {
		register_widget( 'Genesis_Club_Facebook_Likebox_Widget' );		
		register_widget( 'Genesis_Club_Facebook_Comments_Widget' );
	}	

	function get_opengraph($type, $id = 0) {
        switch ($type) {
            case 'home' : $values = $this->get_option('facebook'); break;
            case 'posts': $values = $this->utils->get_post_meta( $id, $this->opengraph_metakey) ; break;
            case 'terms' : $values = $this->utils->get_term_meta( $id, $this->opengraph_metakey); break;
            default: return false;
        }      
        return $values ? $this->sanitize_opengraph($values) : false;
 	}    

	function sanitize_opengraph($opengraph) {
        $values = shortcode_atts($this->opengraph_defaults, $opengraph);
        if ($this->has_opengraph($values))
            return $values;
        else
            return false;
	}

	function has_opengraph($opengraph) {
        return is_array($opengraph)
        &&  ((isset($opengraph['og_title']) && !empty($opengraph['og_title'])) 
        ||  (isset($opengraph['og_desc']) && !empty($opengraph['og_desc'])) 
        ||  (isset($opengraph['og_image']) && !empty($opengraph['og_image']))) ;
	}

	function save_opengraph($type, $id, $opengraph) {
        if ($values = $this->sanitize_opengraph($opengraph))
            switch ($type) {
                case 'posts': return $id ? update_post_meta( $id, $this->opengraph_metakey, $values) : false; break;
                case 'terms' : return $id ? update_term_meta( $id, $this->opengraph_metakey, $values) : false; break;
                default: return false;
            }
        else
            switch ($type) {
                case 'posts': return $id ? delete_post_meta( $id, $this->opengraph_metakey) : false; break;
                case 'terms' : return $id ? delete_term_meta( $id, $this->opengraph_metakey) : false; break;
                default: return false;
            }
 	}  

	function set_facebook_featured_image_size() {
      /* set up up Facebook friendly image sizes for your featured image, and your archive image */
	       
      $image_width = apply_filters('genesis-club-fb-featured-image-width', 470, self::FACEBOOK_FEATURED_IMAGE); //available to override if you want to
      $image_height = apply_filters('genesis-club-fb-featured-image-height',round($image_width / self::FACEBOOK_IMAGE_SCALE_FACTOR), self::FACEBOOK_FEATURED_IMAGE);
      add_image_size( self::FACEBOOK_FEATURED_IMAGE, $image_width, $image_height, true );

      $image_width = apply_filters('genesis-club-fb-archive-image-width', 240, self::FACEBOOK_ARCHIVE_IMAGE); //thumbnail size for archive pages and widgets
      $image_height = apply_filters('genesis-club-fb-archive-image-height', round($image_width / self::FACEBOOK_IMAGE_SCALE_FACTOR), self::FACEBOOK_ARCHIVE_IMAGE);
      add_image_size(self::FACEBOOK_ARCHIVE_IMAGE, $image_width, $image_height, true ); //in proportion to Facebook image

      if ($this->utils->is_yoast_installed()) { //Yoast WordPress SEO plugin sets up the featured image for Facebook - so get it to use the correct image size
         add_filter('wpseo_opengraph_image_size', array($this, 'set_opengraph_image_size') ) ;  
      }      
	}

   function set_opengraph_image_size ($size) {
      return self::FACEBOOK_FEATURED_IMAGE;
   }	
	
	function add_fb_root() {
        $option = $this->get_option('facebook');
		$locale = isset($option['locale']) ? $option['locale'] : '';
		if (empty($locale)) $locale = 'en_US';
		$app_id = isset($option['app_id']) ? $option['app_id'] : '';
		if ($app_id) $app_id = sprintf('&appId=%1$s',$app_id);
		print <<< SCRIPT
<div id="fb-root"></div>
<script type="text/javascript">(function(d, s, id) {
  var js, fjs = d.getElementsByTagName(s)[0];
  if (d.getElementById(id)) return;
  js = d.createElement(s); js.id = id;
  js.src = "//connect.facebook.net/{$locale}/sdk.js#xfbml=1&version=v2.8{$app_id}";
  fjs.parentNode.insertBefore(js, fjs);
}(document, 'script', 'facebook-jssdk'));
</script>
SCRIPT;
	}
	
	
}
