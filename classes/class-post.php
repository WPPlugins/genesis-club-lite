<?php
class Genesis_Club_Post extends Genesis_Club_Module { 
	
	protected $css='';
 
 	function get_options_name() {
       return false; // no global options 
	}	

	function get_defaults() {
       return false; // no defaults 
   }
 
   function init() {
		add_shortcode('genesis_club_more', array($this,'more'));
		add_shortcode('genesis_club_post_dates', array($this,'post_dates'));
		add_shortcode('genesis_club_recent_update', array($this,'recent_update'));
		add_action('widgets_init', array($this,'register_widgets'));	
	}

	function more($attr) {
		$defaults = array('prefix' => '', 'class' => '', 'link_text' => __('Read More'));
   		$params = shortcode_atts( $defaults, $attr ); 
        return $this->utils->read_more_link($params['link_text'], $params['class'], $params['prefix']);        
	}

	function post_dates($attr) {
		$defaults = array('published' => __('First published on '), 'updated' => __('Last updated on '),'before' => '<span class="postmeta-date">', 'after' => '</span>', 'format' => get_option( 'date_format' ), 'separator' => '&nbsp;&middot&nbsp;', 'interval' => 3, 'first' => 'published');
   		$params = shortcode_atts( $defaults, $attr ); 
		$updated = genesis_post_modified_date_shortcode (array ('format' => $params['format'], 'label' => $params['updated'], 'before' => $params['before'],  'after' => $params['after']  ));
		$pub_date =  new DateTime(get_the_time( 'c' ));
		$mod_date = new DateTime(get_the_modified_time( 'c' ));
		$interval = round(($mod_date->format('U') - $pub_date->format('U')) / (60*60*24));
		if ($interval > $params['interval']) {
			$published = genesis_post_date_shortcode (array ('format' => $params['format'], 'label' => $params['published'], 'before' => $params['before'], 'after' => $params['after']  ));
            if ($params['first'] == 'published')
			return $published . $params['separator'] . $updated;   
            else
                return $updated . $params['separator'] . $published;   
		} else {
			return $updated;
		}
	}

	function recent_update($attr) {
		$defaults = array('before' => '<span class="postmeta-date">', 'after' => '</span>', 'label' => __('Last updated on '), 'format' => get_option( 'date_format' ), 'interval' => 90);
		$params = shortcode_atts( $defaults, $attr ); 
		$now_date =  new DateTime();
		$mod_date = new DateTime(get_the_modified_time( 'c' ));
		$interval = round(($now_date->format('U') - $mod_date->format('U')) / (60*60*24));
		if ($interval <= $params['interval']) { //it is recent
			unset($params['interval']);
			return genesis_post_modified_date_shortcode ( $params );  
		} 
	}	

	function register_widgets() {
		register_widget( 'Genesis_Club_Posts_Widget' );
		register_widget( 'Genesis_Club_Post_Specific_Widget' );
		register_widget( 'Genesis_Club_Post_Image_Gallery_Widget' );
		add_action('wp_print_styles', array($this,'print_styles'), 20); 	
	}

	function add_styles($css) {     
         $this->css .=  $css; //append CSS for output later
	}

	function print_styles() { //output any custom CSS
		if (!empty($this->css)) printf ('<style type="text/css">%1$s</style>',$this->css);
		$this->css = ''; //clear
	}

}