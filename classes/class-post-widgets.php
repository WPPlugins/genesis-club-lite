<?php
class Genesis_Club_Post_Specific_Widget extends Genesis_Club_Widget {

   const  WIDGET_CONTENT_META_KEY = '_genesis_club_widget_content';
	private $post;
 	private $tips = array(
			'text' => array('heading' => 'Text', 'tip' => 'Widget Content'),
			'autop' =>  array('heading' => 'Auto-paragraph', 'tip' => 'Click to convert automatically convert new lines to paragraph breaks.'),
			'background' =>  array('heading' => 'Background', 'tip' => 'This can be a background color or an background image, or both'),
			'border' =>  array('heading' => 'Border', 'tip' => 'For example, to put a thin box around the widget: 1px solid gray'),
			'margin' =>  array('heading' => 'Margin', 'tip' => 'For example, to put no margin on each side and 20px above and 25px below use: 20px 0 25px 0'),
			'padding' =>  array('heading' => 'Padding', 'tip' => 'For example, for 10px padding use: 10px'),
			'class' =>  array('heading' => 'Custom class', 'tip' => 'Add custom classes if you require more complicated styling'),
			);
 
   static private $widget_defaults = array ('html_title' => '', 'text' => '', 'autop' => false,
         'background' => '', 'border' => '', 'margin' => '', 'padding' => '', 'class' => '') ;

   static function get_widget_defaults() {
      return self::$widget_defaults;
   }

	function __construct() {
	   $control_ops = array();
		$widget_ops = array('description' => __('Widget that gets its content from the current post', GENESIS_CLUB_DOMAIN) );
		parent::__construct('genesis-club-post-specific', __('Genesis Club Post Specific', GENESIS_CLUB_DOMAIN), $widget_ops, $control_ops );
		$this->set_defaults(self::$widget_defaults);
		$this->post = $this->plugin->get_module('post');
		add_action('wp_enqueue_scripts', array($this, 'enqueue_styles'));
	}

	function widget( $args, $instance ) {
		if ( is_singular()
		&& ($post_id = $this->utils->get_post_id())
		&& ($content = $this->utils->get_post_meta($post_id, self::WIDGET_CONTENT_META_KEY) )
		&& !empty($content['text'])) {
         $instance['html_title'] = $content['html_title'] ; 
         $args = $this->override_args($args, $instance) ;
         $text = do_shortcode($content['text']);
         extract( $args );
         echo $before_widget;
         printf('<div class="textwidget">%1$s</div>', $content['autop']  ?  wpautop($text) : html_entity_decode($text) );
         echo $after_widget;
      }
	}

	function update( $new_instance, $old_instance ) {
		$instance = $this->update_instance( $new_instance, $old_instance );
		$instance['background'] = trim($new_instance['background']);
		$instance['border'] = trim($new_instance['border']);
		$instance['margin'] = trim($new_instance['margin']);
		$instance['padding'] = trim($new_instance['padding']);
		$instance['class'] = trim($new_instance['class']);
		return $instance;
	}

	function form( $instance ) {
		$this->form_init ($instance, $this->tips);	
		printf ('<h4>%1$s</h4>', __('Styling', GENESIS_CLUB_DOMAIN));
		$this->print_form_field('background', 'text', array(), array('class' => 'widefat' ));
		$this->print_form_field('border', 'text', array(), array('class' => 'widefat' ));
		$this->print_form_field('margin', 'text', array(), array('class' => 'widefat'));
		$this->print_form_field('padding', 'text', array(), array('class' => 'widefat'));			
		$this->print_form_field('class', 'text', array(), array('class' => 'widefat'));
	}

   function enqueue_styles() {
		if ($this->is_widget_instance_active()) {
			$this->inline_styles();
		}
	}

	function inline_styles() { 
            $style = '';
        if (isset($this->instance['background']) && $this->instance['background']) $style .= sprintf('background: %1$s;',$this->instance['background']);
        if (isset($this->instance['border']) && $this->instance['border']) $style .= sprintf('border: %1$s;',$this->instance['border']);
        if (isset($this->instance['margin']) && $this->instance['margin']) $style .= sprintf('margin: %1$s;',$this->instance['margin']);			
        if (isset($this->instance['padding']) && $this->instance['padding']) $style .= sprintf('padding: %1$s;',$this->instance['padding']);	
            if (!empty($style)) {
            $element = sprintf('#%1$s .widget-wrap', $this->widget_id);
               $css = sprintf('%1$s {%2$s}', $element, $style); 			
            $this->post->add_styles($css);
         }
      }

    function override_args($args, &$instance) {	
        $classes = array();
        $args = parent::override_args($args, $instance);
        if (isset($instance['class']) && !empty($instance['class'])) 
            $args['before_widget'] = preg_replace('/class="/', 'class="'. $instance['class']. ' ', $args['before_widget'], 1);
        return $args;
	}

}

class Genesis_Club_Posts_Widget extends Genesis_Club_Post_Widget {

	private $tips = array(
		'show_title' => array('heading' => 'Show Title', 'tip' => 'Show Title'),
		'show_excerpt' => array('heading' => 'Show Excerpt', 'tip' => 'Show official excerpt of post'),
		'show_content' => array('heading' => 'Show Content', 'tip' => 'Show some content from the post'), 
		'show_limit' => array('heading' => 'Character Limit', 'tip' => 'Number of characters to show from content of the post'), 
		'content_class' => array('heading' => 'Content Classes', 'tip' => 'Optionally add some classes to the entry content. Examples are aligncenter or alignjustify'),
		'show_more' => array('heading' => 'Show More Link', 'tip' => 'Show more link'), 
		'more_link' => array('heading' => 'More Link Text', 'tip' => 'Supply text to go on the more link or leave blank to not have a link'), 
		'more_class' => array('heading' => 'More Link Classes', 'tip' => 'Optionally add some classes to the more link for styling purposes'), 		
		'no_links' => array('heading' => 'No Links', 'tip' => 'Do not link back to post from either the title or the image'),
		'show_image' => array('heading' => 'Show Image', 'tip' => 'Show Image'),
		'image_size' => array('heading' => 'Image Size', 'tip' => 'Size of image to show above the post'),
		'image_align' => array('heading' => 'Image Alignment', 'tip' => 'Horizontal alignment of image'),
		'source_type' => array('heading' => 'Source', 'tip' => 'Choosing <i>Specific term</i> means that the selection can be based on a specific category, tag or other taxonomy term,<br/><i>Specific list of posts</i> is  choice based on post ID,<br/>,<i>Current context</i> means they are chosen based on the current page so on a category archive page the selection is based on posts in that category <br/><i>Current top parent</i> means that posts are chosen based the same top level category as the current post'),
		'post_type' => array('heading' => 'Post Type', 'tip' => 'Would you like to use posts or pages?'),
		'posts_term' => array('heading' => 'Terms To Include', 'tip' => 'Select the posts for inclusion'),
		'exclude_terms' => array('heading' => 'Terms To Exclude', 'tip' => 'List which category, tag or other taxonomy IDs to exclude. (1,2,3,4 for example)'),
		'include_exclude' => array('heading' => 'Include/Exclude', 'tip' => 'Choose whether to include or exclude the posts below from your selection'),
		'exclude_displayed' => array('heading' => 'Exclude Previous Posts', 'tip' => 'Choose whether to exclude posts earlier on the page to avoid repetition'),
		'post_id' => array('heading' => 'Post IDs', 'tip' => 'List which post IDs to include / exclude. (1,2,3,4 for example'),
		'posts_num' => array('heading' => '# of posts to show', 'tip' => 'Show up to a maximum of this number of posts'),
		'posts_offset' => array('heading' => '# of posts to offset', 'tip' => 'Return content from posts not from the first post but with an offset'),
		'orderby' => array('heading' => 'Order by', 'tip' => 'Choose the order in which you wants the posts to be displayed'),
		'direction' => array('heading' => 'Order', 'tip' => 'Ascending or descending order'),
	);
	
    private $defaults = array('title' => '',
        'show_title' => false,
        'show_excerpt' => false,
        'show_content' => false, 
        'show_limit' => false, 
        'content_class' => '',
        'show_more' => false, 
        'more_link' => '', 
        'more_class' => '',
 		'no_links' => false,
        'show_image' => false,
        'image_size' => 'thumbnail', 
        'image_align' => 'none',
        'source_type' => '',
        'post_type' => 'post',
        'posts_term' => '',
        'exclude_terms' => '',
        'include_exclude' => '',
		'exclude_displayed' => false,
        'posts_num' => 5,
        'posts_per_row' => 1,
        'posts_offset' => 0,
        'post_id' => '',
        'orderby' => 'date',
        'direction' => 'desc'
	) ;

	function __construct() {
		$widget_ops = array(
         'classname'   => 'featured-content genesis-club-posts',
         'description' => __( "Display selected featured posts/custom posts using post excerpts/content with featured images", GENESIS_CLUB_DOMAIN ) );
		$control_ops = array('width' => 420, 'height' => 500);
		parent::__construct('genesis-club-posts', __('Genesis Club Posts', GENESIS_CLUB_DOMAIN), $widget_ops, $control_ops);
		$this->set_defaults($this->defaults);
		add_action('wp_enqueue_scripts', array($this, 'enqueue_styles'));
	}

    function enqueue_styles() {
        if (is_active_widget( false, false, $this->id_base, true )) {
           wp_enqueue_style( 'genesis-club-post-widgets', plugins_url( 'styles/post-widgets.css', dirname(__FILE__)), array(), GENESIS_CLUB_VERSION );
        }
	}

	function widget( $args, $instance ) {
        global $_genesis_displayed_ids;
		$instance = wp_parse_args( (array) $instance, $this->get_defaults() );
        $args = $this->override_args($args, $instance) ;
        $titles_only = $instance['show_title'] 
            && !(isset($instance['show_image']) && $instance['show_image'])
            && !(isset($instance['show_excerpt']) && $instance['show_excerpt'])
            && !(isset($instance['show_content']) && $instance['show_content']);
		extract( $args );
		$post_list = '';
		$posts = $this->get_selected_posts($instance);
		while ( $posts->have_posts()) : $posts->the_post();
            $post_id = get_the_ID();
            $_genesis_displayed_ids[] = $post_id;
            $permalink = get_permalink($post_id);
            $title = $this->get_post_title_text($post_id);
            $post_title = $this->get_post_title($instance, $title, $permalink);
            if ($titles_only)
                $post_list .= sprintf('<li>%1$s</li>', $post_title);
            else {
                $post_image = $this->get_post_image($instance, $title, $permalink);
                if ($post_excerpt = $this->get_post_excerpt($instance))
                    $post_list .= $this->format_post_excerpt($post_title, $post_image, $post_excerpt, $post_id) ;
                else
                    $post_list .= $this->format_post_image($post_title, $post_image, $instance['image_size'], $post_id) ;
            }
		endwhile; 
		wp_reset_query();
        if (!empty($post_list)) {
            $format = $titles_only ? '%1$s<ul>%2$s</ul>%3$s': '%1$s%2$s%3$s';
            printf($format, $before_widget, $post_list, $after_widget);
        }
	}
		
	function update( $new_instance, $old_instance ) {
		$instance = $this->update_instance( $new_instance, $old_instance );
		$instance['show_title'] = !empty($new_instance['show_title']);
		$instance['show_excerpt'] = !empty($new_instance['show_excerpt']);
		$instance['show_content'] = !empty($new_instance['show_content']);
		$instance['show_limit'] = $new_instance['show_limit'];		
		$instance['content_class'] = $this->utils->clean_css_classes($new_instance['content_class']);
		$instance['show_more'] = !empty($new_instance['show_more']);
		$instance['more_link'] = trim($new_instance['more_link']);
		$instance['more_class'] = $this->utils->clean_css_classes($new_instance['more_class']);
		$instance['no_links'] = !empty($new_instance['no_links']);		
		$instance['show_image'] = !empty($new_instance['show_image']);		
		$instance['image_size'] = strip_tags( $new_instance['image_size']);
		$instance['image_align'] = $new_instance['image_align'] ;
		$instance['source_type'] = $new_instance['source_type'];
		$instance['post_type'] = $new_instance['post_type'] ;
		$instance['posts_term'] = $new_instance['posts_term'] ;
		$instance['exclude_terms'] = $new_instance['exclude_terms'] ;
		$instance['exclude_displayed'] = !empty($new_instance['exclude_displayed']) ;
		$instance['include_exclude'] = $new_instance['include_exclude'] ;
		$instance['posts_num'] = !empty($new_instance['posts_num']) ? $new_instance['posts_num'] : 5 ;
		$instance['posts_per_row'] = !empty($new_instance['posts_per_row']) ? $new_instance['posts_per_row'] : 1 ;
		$instance['posts_offset'] = !empty($new_instance['posts_offset']) ? $new_instance['posts_offset'] :0;
		$instance['post_id'] = $new_instance['post_id'] ;
		$instance['orderby'] = $new_instance['orderby'] ;
		$instance['direction'] = $new_instance['direction'] ;
		return $instance;
	}
	
	function form( $instance ) {
		$this->form_init ($instance, $this->tips);
		print('<h4>General Settings</h4>');
		$this->print_form_field('show_title', 'checkbox');
		$this->print_form_field('show_excerpt', 'checkbox');
		$this->print_form_field('show_content', 'checkbox');
		$this->print_form_field('show_limit', 'text', array(), array('size' => 5));
		$this->print_form_field('content_class', 'text', array(), array('size' => 20));	
		$this->print_form_field('show_more', 'checkbox');
		$this->print_form_field('more_link', 'text', array(), array('size' => 20));
		$this->print_form_field('more_class', 'text', array(), array('size' => 20));	
		$this->print_form_field('no_links', 'checkbox');
		print ('<hr/><h4>Image Settings</h4>');
		$this->print_form_field('show_image', 'checkbox');
		$this->print_form_field('image_size', 'select', $this->size_options());
		$this->print_form_field('image_align', 'select', $this->align_options());
		print ('<hr/><h4>Post Selection</h4>');
		$this->print_form_field('post_type', 'select', $this->post_type_options());
		$this->print_form_field('source_type', 'select', $this->source_type_options());
		print ('<hr/>Include or Exclude by Terms');
		$this->print_form_field('posts_term','select', $this->taxonomy_options('posts_term'), array('multiple' => true));
		$this->print_form_field('exclude_terms', 'text', array(), array('size' => 10));		
		print ('<hr/>Include or Exclude by Post ID');
		$this->print_form_field('include_exclude', 'select', array('include' => 'Include', 'exclude' => 'Exclude'));
		$this->print_form_field('post_id', 'text', array(), array('size' => 10));		
		$this->print_form_field('exclude_displayed', 'checkbox');
		print ('<hr/><h4>Numbers And Ordering</h4>');
		$this->print_form_field('posts_num', 'text', array(), array('size' => 4));		
		$this->print_form_field('posts_per_row', 'radio', array('1' => 1, '2' => '2', '3' => 3, '4' => 4 ));
		$this->print_form_field('posts_offset', 'text', array(), array('size' => 4));		
		$this->print_form_field('orderby', 'select', $this->sort_options());
		$this->print_form_field('direction', 'select', array('asc' => 'Ascending', 'desc' => 'Descending'));
	}

	}

class Genesis_Club_Post_Image_Gallery_Widget extends Genesis_Club_Widget {

	private $tips = array(
			'size' => array('heading' => 'Size', 'tip' => 'Image size'),
			'posts_per_page' => array('heading' => 'Images', 'tip' => 'Maximum number of images to show in the sidebar'),
			'lightbox' => array('heading' => 'Show In Thickbox', 'tip' => 'Click to show larger photo in  lightbox'),
			'hide_featured' => array('heading' => 'Hide Featured Image', 'tip' => 'Hide featured image to avoid duplication.'),
			);
	
    private	$defaults = array('title' => 'Gallery', 
    	'size' => 'medium', 'hide_featured' => false, 'lightbox' => false,
    	'posts_per_page' => 3); //# of visible images);
	
	function __construct() {
		$widget_ops = array('description' => __('Displays a Post Image Gallery in a sidebar widget with optional lightbox', GENESIS_CLUB_DOMAIN) );
		$control_ops = array('width' => 400, 'height' => 350);
		parent::__construct('genesis-club-post-image-gallery', __('Genesis Club Post Images', GENESIS_CLUB_DOMAIN), $widget_ops, $control_ops );
		$this->set_defaults($this->defaults);
	}

	function widget( $args, $instance ) {
		if (!is_singular()) return;  //only run on single post/page/custom post_type pages
		$post = get_post();
		if (is_null($post)) return; //we have a post to work with
		$gargs = array('columns'=>1, 'link'=>'file', 'orderby' => 'rand', 'size' => $instance['size']);
		if ($instance['hide_featured']
		&& ($featured_image = get_post_thumbnail_id($post->ID))) 
			$gargs['exclude'] = $featured_image;	
		$gallery = gallery_shortcode($gargs);
		if (empty($gallery)) return; //no gallery so do not display an empty widget
        $args = $this->override_args($args, $instance) ;
        extract($args);
        echo $before_widget;
		echo $gallery;
		echo $after_widget;
		self::limit_images($post->ID, $instance['posts_per_page'],$instance['lightbox']);
	}

	function update( $new_instance, $old_instance ) {
		$instance = $this->update_instance( $new_instance, $old_instance );
		$instance['size'] = strip_tags( $new_instance['size'] );
		$instance['hide_featured'] = empty($new_instance['hide_featured']) ? 0 : 1;
		$instance['lightbox'] = empty($new_instance['lightbox']) ? 0 : 1;
		$instance['posts_per_page'] = $new_instance['posts_per_page'];	
		return $instance;
	}

	function form( $instance ) {
		$this->form_init ($instance, $this->tips);		
		$sizes = array_keys(genesis_get_image_sizes());
		$this->print_form_field('size', 'select', array_combine($sizes,$sizes));
		$this->print_form_field('posts_per_page',  'text', array(), array('size' => 3 ,'maxlength' => 3));
		$this->print_form_field('lightbox', 'checkbox');
		$this->print_form_field('hide_featured', 'checkbox');
	}

	function limit_images($id, $number, $lightbox) {
		$gid = '.galleryid-'.$id;
		$lbox = 'thickbox-'.$id;		
		$add_lbox = '';
		if ($lightbox) {
			$add_lbox .= sprintf('jQuery("%1$s").find("a").addClass("thickbox").attr("rel","%2$s");',$gid,$lbox); 
			$add_lbox .= 'jQuery("<style type=\"text/css\">#TB_caption {height: auto;}</style>").appendTo("head");';
		}
		print <<< SCRIPT
<script type="text/javascript"> {$add_lbox}
	jQuery('{$gid}').find('br').slice({$number}).hide();
	jQuery('{$gid}').find('.gallery-item').slice({$number}).hide();
</script>
SCRIPT;
	}

}
