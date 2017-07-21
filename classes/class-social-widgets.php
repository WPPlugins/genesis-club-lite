<?php
class Genesis_Club_Facebook_Likebox_Widget extends Genesis_Club_Widget {
    private	$defaults = array('title' => 'Like Us', 'href' => 'https://www.facebook.com/DIYWebMastery', 
    		'header' => true, 'small_header' => false, 'faces' => true,  'stream' => false,
    		'adaptive' => 'true', 'width' => 290, 'height' => '');

	private $tips = array(
			'href' => array('heading' => 'Facebook URL', 'tip' => 'URL of Facebook page. For example,  https://www,facebook.com/yourpage/'),
			'header' => array('heading' => 'Show Cover Photo', 'tip' => 'Show Cover Photo.'),
			'small_header' => array('heading' => 'Small Header', 'tip' => 'Use the small header'),
			'faces' => array('heading' => 'Show Faces', 'tip' => 'Show faces of those who liked this site'),
			'stream' => array('heading' => 'Show Posts', 'tip' => 'Show recent posts.'),
			'adaptive' => array('heading' => 'Autofit', 'tip' => 'Autofit Facebook social plugin into container'),
			'width' => array('heading' => 'Width', 'tip' => 'Set the width in pixels according to the width of your sidebar. Around 280px is typical.'),
			'height' => array('heading' => 'Height', 'tip' => 'Set the height in pixels based upon how many rows of face you want to display. Around 400px is good.'),
			);
			
    private $social;

	function __construct() {
		$widget_ops = array('description' => __('Displays a Facebook Page Socal Plugin Widget (replaces deprecated Facebook LikeBox)', 'GenesisClub') );
		$control_ops = array();
		parent::__construct('genesis-club-likebox', __('Genesis Club Facebook Page', 'GenesisClub'), $widget_ops, $control_ops );
        $this->social = $this->plugin->get_module('social');
		$this->set_defaults($this->defaults);
		add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
	}

    function enqueue_scripts() {
    	if ($this->is_widget_instance_active()) add_action('genesis_before', array($this->social,'add_fb_root') );			
    }
    
	function widget( $args, $instance ) {
      $args = $this->override_args($args, $instance) ;
      extract($args);
		echo $before_widget;
		printf( '<div class="fb-page" data-href="%1$s" data-hide-cover="%2$s" data-small-header="%3$s" data-show-facepile="%4$s" data-show-posts="%5$s" data-adapt-container-width="%6$s" data-width="%7$s" %8$s><div class="fb-xfbml-parse-ignore"><blockquote cite="%1$s"><a href="%1$s">Facebook</a></blockquote></div></div>', 
			$instance['href'], 
			$instance['header'] ? 'false' : 'true', 
			$instance['small_header'] ? 'true' : 'false', 
			$instance['faces'] ? 'true' : 'false', 
			$instance['stream'] ? 'true' : 'false', 
			$instance['adaptive'] ? 'true' : 'false', 
			empty($instance['width']) ? '' : $instance['width'],
			empty($instance['height']) ? '' : sprintf(' data-height="%1$s"', $instance['height'])); 
		echo $after_widget;
	}

	function update( $new_instance, $old_instance ) {
		$instance = $this->update_instance( $new_instance, $old_instance );
		$instance['href'] = strip_tags( $new_instance['href'] );
		$instance['width'] = $new_instance['width'];	
		$instance['height'] = $new_instance['height'];	
		$instance['adaptive'] = empty($new_instance['adaptive']) ? 0 : 1;
		$instance['header'] = empty($new_instance['header']) ? 0 : 1;
		$instance['small_header'] = empty($new_instance['small_header']) ? 0 : 1;
		$instance['faces'] = empty($new_instance['faces']) ? 0 : 1;
		$instance['stream'] = empty($new_instance['stream']) ? 0 : 1;	
		return $instance;
	}

	function form( $instance ) {
		$this->form_init ($instance, $this->tips);
		$this->print_form_field('href', 'textarea', array(), array('class' => 'widefat'));
		$this->print_form_field('width', 'text',array(), array('size' => 4 ,'maxlength' => 4, 'suffix' => 'px'));
		$this->print_form_field('height', 'text',array(), array('size' => 4 ,'maxlength' => 4, 'suffix' => 'px'));
		$this->print_form_field('adaptive', 'checkbox');
		$this->print_form_field('header','checkbox');
		$this->print_form_field('small_header', 'checkbox');
		$this->print_form_field('faces', 'checkbox');
		$this->print_form_field('stream', 'checkbox');
	}

}

class Genesis_Club_Facebook_Comments_Widget extends Genesis_Club_Widget {

    private	$defaults = array('title' => 'Comments', 'href' => '', 'color_scheme' => 'light', 'num_posts' => 5, 'order_by' => 'social', 'width' => '');

	private $tips = array(
			'color_scheme' => array('heading' => 'Color Scheme', 'tip' => 'Light or dark'),
			'num_posts' => array('heading' => 'Number of comments', 'tip' => 'The number of comments to show by default. The minimum value is 1.'),
			'order_by' => array('heading' => 'Order', 'tip' => 'The order to use when displaying comments. Can be "social", "reverse_time", or "time".'),
			'width' => array('heading' => 'Width', 'tip' => 'Set the width in pixels according to the width of your sidebar or leave blank to ste automatically.'),
			);
			
    private $social;

	function __construct() {
		$widget_ops = array('description' => __('Displays a Facebook Comments Socal Plugin Widget', 'GenesisClub') );
		$control_ops = array();
		parent::__construct('genesis-club-commentbox', __('Genesis Club Facebook Comments', 'GenesisClub'), $widget_ops, $control_ops );
        $this->social = $this->plugin->get_module('social');
		$this->set_defaults($this->defaults);
		add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
	}

    function enqueue_scripts() {
    	if ($this->is_widget_instance_active()) add_action('genesis_before', array($this->social,'add_fb_root') );			
    }
    
	function widget( $args, $instance ) {
        global $wp;
        $args = $this->override_args($args, $instance) ;
        extract($args);
		echo $before_widget;
        $instance['href'] = home_url(add_query_arg(array(),$wp->request));
		printf( '<div class="fb-comments" data-href="%1$s" data-numposts="%2$s" data-order-by="%3$s" data-colorscheme="%4$s" data-width="%5$s"></div>', 
			$instance['href'], 
			$instance['num_posts'], 
			$instance['order_by'], 
			$instance['color_scheme'], 
			empty($instance['width']) ? '' : $instance['width']); 
		echo $after_widget;
	}

	function update( $new_instance, $old_instance ) {
		$instance = $this->update_instance( $new_instance, $old_instance );
		$instance['width'] = $new_instance['width'];	
		$instance['color_scheme'] = $new_instance['color_scheme'];	
		$instance['num_posts'] = $new_instance['num_posts'];
		$instance['order_by'] = $new_instance['order_by'];
		return $instance;
	}

	function form( $instance ) {
		$this->form_init ($instance, $this->tips);
		$this->print_form_field('num_posts', 'text',array(), array('size' => 4 ,'maxlength' => 4));
		$this->print_form_field('order_by', 'select', array('social' => 'Social', 'reverse_time' =>'reverse_time', 'time' => 'time'));
		$this->print_form_field('color_scheme','select', array('light' => 'Light', 'dark' => 'Dark'));
		$this->print_form_field('width', 'text',array(), array('size' => 4 ,'maxlength' => 4, 'suffix' => 'px'));
	}

}