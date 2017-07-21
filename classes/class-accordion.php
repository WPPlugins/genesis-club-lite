<?php
class Genesis_Club_Accordion extends Genesis_Club_Module {
	const OPTION_NAME = 'accordions';

    /* Because a site has relatively few accordions they are stored in a single array structure rather than individually */

	protected $defaults = array('posts' => array(), 'terms' => array()) ;

	protected $accordion_defaults = array(
	   'enabled' => '', 
	   'type' => '', 
	   'source' => '', 
	   'container' => '', 
	   'container_class' => '', 
	   'header_class' => '', 
	   'content_class' => '', 
	   'header_depth' => false, 
	   'scroll_top' => false, 
	   'open_first' => false, 
	   'nopaging' => false);


	function get_accordion_defaults() {
       return $this->accordion_defaults; 
	}

	function get_defaults() {
       return $this->defaults; 
	}

	function get_options_name() {
       return self::OPTION_NAME; 
   }

	protected $accordions = array(); 

	public function init() {
		add_action('widgets_init',array($this,'register_widgets'));
		if (!is_admin())  {
			add_action('pre_get_posts', array($this,'maybe_filter_archive'), 1 );
			add_action('wp',array($this,'prepare'));
		}
	}

	function register_widgets() {
		if (class_exists('Genesis_Club_Accordion_Widget')) register_widget( 'Genesis_Club_Accordion_Widget' );
		if (class_exists('Genesis_Club_Accordion_Term_Widget')) register_widget( 'Genesis_Club_Accordion_Term_Widget' );
	}

    public function prepare() {
        if (is_archive() || is_singular())				
            $this->maybe_add_accordion();				
	}

	private function empty_accordion($accordion) {
		return ! ($accordion && is_array($accordion) 		
		&& (array_key_exists('enabled',$accordion) 
		|| (array_key_exists('header_class',$accordion) && !empty($accordion['header_class'])) 
		|| (array_key_exists('content_class',$accordion) && !empty($accordion['content_class'])) 
		|| (array_key_exists('container_class',$accordion) && !empty($accordion['container_class'])) ));
	}

	private function get_accordions() {
 		return $this->get_options();
	}
	
	public function save_accordion($type, $id, $new_accordion) {
 		$accordions = $this->get_accordions();
		if ($this->empty_accordion($new_accordion)) {
			if (is_array($accordions)
			&& array_key_exists($type,$accordions) 
			&& array_key_exists($id,$accordions[$type]))
				unset($accordions[$type][$id]); //delete it if it is present
		} else {
				$accordions[$type][$id] = $new_accordion ;
		}
		return $this->save_options($accordions);
	}

 	public function get_accordion($type, $id, $accordions = false ) {
		if (!$accordions) $accordions = $this->get_accordions();
		if (is_array($accordions)
		&& array_key_exists($type, $accordions)
		&& is_array($accordions[$type])
		&& array_key_exists($id, $accordions[$type])) {
            $accordions[$type][$id]['type'] = $type;
            $accordions[$type][$id]['source'] = $id;
			return $accordions[$type][$id];            
		}
		else
			return false;	
 	}

 	private function maybe_add_accordion() {
			if (($accordions = $this->get_accordions())
			&& ($id = get_queried_object_id()) 
		    && ((is_singular() && ($accordion = $this->get_accordion('posts', $id, $accordions)))	
			 || (is_archive() && ($accordion = $this->get_accordion('terms', $id, $accordions))))
			&& array_key_exists('enabled',$accordion)) 
				$this->add_accordion($accordion);				
	}

	public function add_accordion ($accordion) {
        $this->accordions[] = $accordion; //save for later
        wp_enqueue_style('gc-accordion', plugins_url('styles/accordion.css', dirname(__FILE__)), array(), $this->plugin->get_version());		
        wp_enqueue_script('gc-accordion', plugins_url('scripts/jquery.accordion.js', dirname(__FILE__)), array('jquery'), $this->plugin->get_version(), true);			
		add_action(is_admin() ? 'admin_print_footer_scripts' : 'wp_print_footer_scripts', array($this, 'do_accordions'),20);		
        if ('terms'==$accordion['type']) {
            add_filter('genesis_pre_get_option_content_archive', array($this,'full_not_excerpt'));
            add_filter('genesis_pre_get_option_content_archive_limit', array($this,'no_content_limit'));
			if ($this->utils->is_html5())
                remove_action( 'genesis_entry_content', 'genesis_do_post_image', 8 );
			else
                remove_action( 'genesis_post_content', 'genesis_do_post_image' );				
		}
	}

    public function full_not_excerpt($content) {
        return 'full';
    }

    public function no_content_limit($content) {
        return 0; //do not limit the number of characters
    }

	private function get_current_archive_accordion() {
		$term = $this->utils->get_current_term();
		return $term ? $this->get_accordion('terms', $term->term_id) : false;
	}

	public function maybe_filter_archive( $query ) {
	    if ($query->is_archive 
	    && $query->is_main_query()
	    && ($accordion = $this->get_current_archive_accordion())
	    && $accordion['enabled']
	    && array_key_exists('nopaging', $accordion)
	    && $accordion['nopaging']) 	    		 
	        $query->set( 'nopaging', true );
	}

	function do_accordions(){
		foreach ($this->accordions as $accordion) {
		  $this->do_accordion($accordion);
		}
	}

    private function set_header($accordion) {
        if (is_admin()) 
			$header = 'h3';
		else
			if ($this->utils->is_html5())
				$header = (is_page() || is_single()) ? '.entry-content h3' : 'article header';
			else
				$header = (is_page() || is_single()) ? '.post h3, .page h3' : '.post > h2, .post .wrap > h2';
        return $header;
    }

    private function set_container($accordion) {
        return is_admin() ? '#wpcontent .accordion' : ( $this->utils->is_html5() ? 'main.content' : '#content');
    }

	private function clean_accordion($accordion) {
    	unset($accordion['enabled']);
		unset($accordion['source']);
		unset($accordion['container']);
		foreach ($accordion as $key => $val) if (empty($val)) unset($accordion[$key]);        
        return $accordion;
	}

	public function do_accordion($accordion) {
		if (is_archive()) $accordion['content_class'] .= ' entry-content';
            		
		if (!(isset($accordion['header']) && $accordion['header'])) 
            $accordion['header'] = $this->set_header($accordion);

        if (isset($accordion['container']) && $accordion['container'])
            $container = $accordion['container'];
        else
            $container = $this->set_container($accordion);
            
		$params = $this->utils->json_encode($this->clean_accordion($accordion));	
		print <<< SCRIPT
<script type="text/javascript">
//<![CDATA[
	jQuery(document).ready( function() { jQuery('{$container}').gcaccordion({$params}); });
//]]>
</script>	
SCRIPT;
	}

}
