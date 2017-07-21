<?php
if (!class_exists('Genesis_Club_Widget')) {
 abstract class Genesis_Club_Widget extends WP_Widget {

   const ALLOWED_TAGS = '<a>,<img>,<span>,<i>,<em>,<br>';
   
	protected $plugin;
	protected $utils;   
    protected $instance; 
    protected $key; 
    protected $widget_id; 
	private $tooltips;
	private $defaults = array('title' => '', 'html_title' => '', 'class' => '');
	private $tips = array('title' => array('heading' => 'Label', 'tip' => 'Label appears only in the Widget Dashboard to make widget identification easier'),
                        'html_title' => array('heading' => 'Widget Title', 'tip' => 'Enhanced widget title can contain some HTML such as links, spans and breaks'),
                        'class' => array('heading' => 'Widget Class', 'tip' => 'Class to place on widget instance to make CSS customization easier')
                        );

	public function __construct( $id_base, $name, $widget_options = array(), $control_options = array()) {
        $this->plugin = Genesis_Club_Plugin::get_instance();
        $this->utils = $this->plugin->get_utils();
        parent::__construct($id_base, $name, $widget_options, $control_options);
   }

	function get_defaults() {
		return $this->defaults;
	}

	function set_defaults($defaults) {
        if (is_array($defaults) && (count($defaults) > 0))
            $this->defaults = array_merge($this->defaults, $defaults);
	}

	public function override_args($args, &$instance) {	
        $this->instance = wp_parse_args( (array) $instance, $this->get_defaults() );
        $title = isset($instance['html_title']) ?  $instance['html_title'] : ''; 
        $instance = $this->instance;
        $class = isset($instance['class']) ?  $instance['class'] : ''; 
        if ( ! empty( $class ) ) $args['before_widget'] = str_replace('"widget ', '"widget '.$class.' ', $args['before_widget']);       
        if ( ! empty( $title ) ) $args['before_widget'] .= sprintf('%1$s%2$s%3$s',  $args['before_title'], $title, $args['after_title']);
        return $args;
   }

    function get_active_instances() {
        $active = array();
         if ($instances = $this->get_settings())
            foreach ($instances as $key => $instance) 
                if (is_array($instance) 
                && (count($instance) > 0) 
                && is_active_widget( false, $this->id_base.'-'.$key, $this->id_base, true )) {   
                    $inst = clone $this;
                    $inst->key = $key;    
                    $inst->widget_id = $this->id_base.'-'.$key;    
                    $inst->instance = wp_parse_args( (array) $instance, $this->get_defaults() );                   
                    $active[] = $inst;	
                }
         return $active;
    }

    function is_widget_instance_active() {
         if ($instances = $this->get_settings())
            foreach ($instances as $key => $instance) 
                if (is_array($instance) 
                && (count($instance) > 0) 
                && is_active_widget( false, $this->id_base.'-'.$key, $this->id_base, true )) {   
                    $this->key = $key;    
                    $this->widget_id = $this->id_base.'-'.$this->key;    
                    $this->instance = wp_parse_args( (array) $instance, $this->get_defaults() );                   
                    return true;		
                }
         return false;
    }

	public function update_instance($new_instance,  $old_instance) {
		$instance = wp_parse_args( (array) $old_instance, $this->get_defaults() );
		$instance['title'] = strip_tags($new_instance['title']);		
		$instance['html_title'] = strip_tags( $new_instance['html_title'],  self::ALLOWED_TAGS );	
		$instance['class'] = strip_tags( $new_instance['class'] );
      return $instance;
   }

	function form_init( $instance, $tips = false, $html_title = true) {
        if (is_array($tips) && (count($tips) > 0)) $this->tips = array_merge($this->tips, $tips);
        $this->tooltips = new Genesis_Club_Tooltip($this->tips);
        $this->instance = wp_parse_args( (array) $instance, $this->get_defaults() );      
 		print('<h4>Title</h4>');
        $this->print_form_field('title', 'text', array(), array('size' => 20));
        if ($html_title) $this->print_form_field('html_title', 'textarea', array(), array( 'class' => 'widefat' ));
        $this->print_form_field('class', 'text', array(), array( 'size' => 20 ));
        print ('<hr />');	  
	}
   
	public function print_form_field($fld, $type, $options = array(), $args = array()) {
		print $this->utils->form_field( 
			$this->get_field_id($fld), $this->get_field_name($fld), 
			$this->tooltips->tip($fld), 
			isset($this->instance[$fld]) ? $this->instance[$fld] : false,
			$type, $options, $args);
	}

	function print_text_field($fld, $value, $args = array()) {
 		$this->print_form_field($fld, $value, 'text', array(), $args);
 	}

	function taxonomy_options ($fld) {
        $selected = array_key_exists($fld, $this->instance) ? $this->instance[$fld] : '';
		$s = sprintf('<option %1$s value="%2$s">%3$s</option>', 
			selected('', $selected, false ), '', __('All Taxonomies and Terms', GENESIS_CLUB_DOMAIN ));
		$taxonomies = get_taxonomies( array('public' => true ), 'objects');
		foreach ( $taxonomies as $taxonomy ) {
			if ($taxonomy->name !== 'nav_menu') {
				$query_label = $taxonomy->name;
				$s .= sprintf('optgroup label="%1$s">', esc_attr( $taxonomy->labels->name ));
				$s .= sprintf('<option style="margin-left: 5px; padding-right:10px;" %1$s value="%2$s">%3$s</option>',
					selected( $query_label , $selected, false), 
					$query_label, $taxonomy->labels->all_items) ;
				$terms = get_terms( $taxonomy->name, 'orderby=name&hide_empty=1');
				foreach ( $terms as $term ) 
					$s .= sprintf('<option %1$s value="%2$s">%3$s</option>',
						selected($query_label. ',' . $term->slug, $selected, false),
						$query_label. ',' . $term->slug, '-' . esc_attr( $term->name )) ;
				$s .= '</optgroup>';
			}
		}
		return  $s;
	}

    function get_visibility_options(){
		return array(
			'' => 'Show on all pages', 
			'hide_landing' => 'Hide on landing pages', 
			'show_landing' => 'Show only on landing pages');
	}

	function hide_widget($visibility ) {
		$hide = false;
		$is_landing = $this->utils->is_landing_page();
		switch ($visibility) {
			case 'hide_landing' : $hide = $is_landing; break; //hide only on landing pages
			case 'show_landing' : $hide = ! $is_landing; break; //hide except on landing pages
		}
		return $hide;
	}

 } 
}

if (!class_exists('Genesis_Club_Post_Widget')) {
  abstract class Genesis_Club_Post_Widget extends Genesis_Club_Widget {

    function align_options() {
        return array(
            'alignnone' => __('None', GENESIS_CLUB_DOMAIN ),
            'aligncenter' => __('Center', GENESIS_CLUB_DOMAIN ),
			'alignleft' => __('Left', GENESIS_CLUB_DOMAIN ),
			'alignright' => __('Right', GENESIS_CLUB_DOMAIN ),
       );
	}
		
	function size_options() {
		$options = array();
		$sizes = genesis_get_image_sizes();	
		foreach ($sizes as $size => $dims) $options[$size] = $size . '('.$dims['width'].'x'.$dims['height'].')';
		return $options;
	}
	
	function post_type_options() {
		$options = array();
		$post_types = get_post_types( array('public' => true ), 'names', 'and');
		foreach ( $post_types as $post_type ) $options[$post_type] = $post_type;
		return $options;
	}

	function widget_width_classes ($posts_per_row) {
        $classes = array('','','halves', 'thirds', 'fourths', 'fifths', 'sixths');
        return array_key_exists($posts_per_row, $classes) ? $classes[$posts_per_row] : '';
	}

	function source_type_options() {
		return array(
            'term' => __('Specific term', GENESIS_CLUB_DOMAIN ),
			'post' => __('Specific list of posts', GENESIS_CLUB_DOMAIN ),
			'current' => __('Current context', GENESIS_CLUB_DOMAIN ),
			'parent' => __('Current top parent', GENESIS_CLUB_DOMAIN ),
        );
	}
	
    function sort_options() {
        return array(
            'date' => __('Date Published', GENESIS_CLUB_DOMAIN ), 
            'modified' => __('Date Modified', GENESIS_CLUB_DOMAIN ),  
            'ID' => __('ID', GENESIS_CLUB_DOMAIN ), 
            'rand' => __('Random', GENESIS_CLUB_DOMAIN ), 
            'title' => __('Title', GENESIS_CLUB_DOMAIN )
        );
    }	

	function get_top_term_suffixed($term,$suffix) {
        if ($top_term = $this->get_top_term($term))
            return get_term_by('slug',$top_term->slug.$suffix,$top_term->taxonomy);
		else
            return false;
		}
		
	function get_top_term($term) {
        if ($term) {
            $term_tree = get_category_parents($term->term_id, FALSE, ':', true);
			$terms = explode(':',$term_tree);
			return get_term_by('slug',$terms[0],$term->taxonomy);					
		}
		return false;
	}
		
	function get_post_top_cat_slug() {
        $category = get_the_category(); 
		if (count($category) >0) {
            $term =  $this->get_top_term($category[0]);
			if ($term) return $term->slug;
		}
		return '';			
	}

	function get_current_term() {
			global $wp_query;
			if (is_tax() || is_category() || is_tag()) {					
				return $this->utils->get_current_term();
			} elseif (is_single('post')) {
				global $post;
				$myCategories = array();
				$postCategories = get_the_category($post->ID);
				foreach ( $postCategories as $postCategory ) {
					$myCategories[] = get_term_by('id', $postCategory->cat_ID, 'category');
				}
				if (count($myCategories) > 0) return $myCategories[0];
			}
			return false;
		}

	function get_instance_term($fld) {
		$posts_term = explode(',', $fld );
		return count($posts_term) == 2 ?  get_term_by('slug',$posts_term['1'],$posts_term['0']) : false;
	}

    function get_selected_posts($instance) {
        global $_genesis_displayed_ids;
		$term_args = array( );
		$terms = array();
		$term = $this->get_current_term();
		if ('page' != $instance['post_type'] ) {
			switch ($instance['source_type'])  {
					case "parent":	{ $term = $this->get_top_term($term); break;}	
					case "current": { break; }
					default: $term = $this->get_instance_term($instance['posts_term']);
			}
			if ($term)  {
				$term_args['tax_query'] = array(
					array('taxonomy' => $term->taxonomy, 'field' => 'id', 
						'terms' => count($terms) > 0 ? $terms : $term->term_id)
					);				
				if ( $instance['exclude_terms'] ) {
					$exclude_terms = explode(',', str_replace(' ', '', $instance['exclude_terms' ] ) );
						$term_args[$term->taxonomy . '__not_in'] = $exclude_terms;
				}
			}
		}
		if ( $instance['post_id'] ) {
			$IDs = explode(',', str_replace(' ', '', $instance['post_id'] ) );
			if ('include' == $instance['include_exclude'])
				$term_args['post__in'] = $IDs;
			else
				$term_args['post__not_in'] = $IDs;
		}

		if ( isset($instance['exclude_displayed']) && $instance['exclude_displayed'] && !empty($_genesis_displayed_ids))
            if (isset($term_args['post__in']) || !isset($IDs))
                $term_args['post__not_in'] = (array) $_genesis_displayed_ids;
            else
                $term_args['post__not_in'] = array_merge((array) $_genesis_displayed_ids, (array)$IDs);
            
		if ( $instance['posts_offset']) {
			$my_offset = $instance['posts_offset'];
			$term_args['offset'] = $my_offset;
		}
		$query_args = array_merge( $term_args, array(
			'post_type' => $instance[ 'post_type'],
			'posts_per_page' => $instance['posts_num'],
			'orderby' => $instance['orderby'],
			'order' => $instance['direction']
		) );
		$posts = new WP_Query( $query_args );
      return $posts;	
    }

    function strip_more_link($excerpt) {
        if (strpos($excerpt, 'more-link') !== FALSE) 
            return trim(substr($excerpt, 0, strrpos($excerpt, '<a ')));
        else
            return $excerpt;
    }

    function get_post_title_text($post_id) {
        return strip_tags(apply_filters('genesis_club_post_title', get_the_title($post_id), $post_id));
    }

    function get_post_title($instance, $title_text, $permalink) {
        if ($instance['show_title']) {
            $tclass = ((isset($instance['icon_align']) && ($instance['icon_align']=='aligncenter')) || (isset($instance['image_align']) && ($instance['image_align']=='aligncenter'))) ? ' class="aligncenter"' : '';     
            if ($instance['no_links'])
                return sprintf('<div%1$s>%2$s</div>', $tclass, $title_text); 
            else
                return sprintf('<a rel="bookmark" href="%1$s"%2$s>%3$s</a>', $permalink, $tclass, esc_html($title_text)) ;            
        } else {
            return '';            
        }
    }

    function get_post_content_limit( $max_characters ) {
        $content = do_shortcode(get_the_content( '', false));
        $content = strip_tags( $content , apply_filters( 'get_the_content_limit_allowedtags', '<script>,<style>' ) );
        $content = trim( preg_replace( '#<(s(cript|tyle)).*?</\1>#si', '', $content ) );
        $content = genesis_truncate_phrase( $content, $max_characters );
        return empty($content) ? '' : ($content . '&#x02026;' );
    }

    function get_post_excerpt($instance) {
        if ($instance['show_content']) {
            if ( $instance['show_limit']) {
                $post_excerpt = $this->get_post_content_limit( (int)$instance['show_limit'] );
            } else {            
                $post_excerpt = get_the_content('');
            }
        } elseif ( $instance['show_excerpt'] ) 
            $post_excerpt = get_the_excerpt();
        else 
            $post_excerpt = '';   

        if (! empty($post_excerpt)) $post_excerpt = $this->strip_more_link($post_excerpt);
        $more_link = ($instance['show_more'] && $instance['more_link']) ?  $this->utils->read_more_link($instance['more_link'], $instance['more_class'], '') : ''; 
        $content_class = (isset($instance['content_class']) && $instance['content_class']) ? sprintf(' class="%1$s"', $instance['content_class']) : ''; 
        if (empty($post_excerpt) && empty($more_link)) 
            return false;
        else
            return apply_filters('genesis_club_post_excerpt', sprintf('<p%1$s>%2$s%3$s</p>', $content_class, $post_excerpt, $more_link), $post_excerpt, $more_link) ;
    }

    function get_post_image($instance, $title, $permalink ) {
        if ( $instance['show_image'] 
        && ($post_image = genesis_get_image( array(
				  'format'  => 'html',
				  'size'    => $instance['image_size'],
				  'context' => 'featured-post-widget',
				  'attr'    => genesis_parse_attr( 'entry-image-widget' )) ))
		&& ($format = $instance['no_links'] ? '<span title="%1$s" class="%2$s">%3$s</span>' : '<a href="%4$s" title="%1$s" class="%2$s">%3$s</a>'))
            return sprintf( $format, $title, esc_attr( $instance['image_align'] ), $post_image, $permalink );
  
    }

    function get_post_icon($instance, $title, $permalink, $icon ) {
        $icon_align = $instance['icon_align'];
        $icon_class = $instance['icon_class'];
        $icon_color = $instance['icon_color'];		
        $icon_custom = $instance['icon_custom'];		
        $icon_alt = '' ;
		if ($icon) {
            if (!empty($icon['title'])) $title = $icon['title'];
            if (!empty($icon['class'])) $icon_class = $icon['class'];
            if (!empty($icon['color'])) $icon_color = $icon['color'];
            if (!empty($icon['alt'])) $icon_alt = $icon['alt'];
            if (!empty($icon['custom'])) $icon_custom = $icon['custom'];
		}
        if ( $icon_custom && ($post_icon = do_shortcode($icon_custom) )) {
            //use custom icon
        } else {
            $screen_reader_text = empty($icon_alt) ? '' : sprintf('<span class="screen-reader-text">%1$s</span>', $icon_alt);
            $post_icon = sprintf('<span class="fa %1$s %2$s" style="color: %3$s"></span>%4$s',  $instance['icon_size'], $icon_class, $icon_color, $screen_reader_text );      
        }
        $format = $instance['no_links'] ? '<span title="%1$s" class="%2$s">%3$s</span>' : '<a href="%4$s" title="%1$s" class="%2$s">%3$s</a>';
		return sprintf($format, $title, $icon_align, $post_icon, $permalink);         
    }

    function format_post_image($title, $image, $image_size='', $post_id=0) {
        return apply_filters( 'genesis_club_format_post_image', 
            sprintf('<div class="entry">%1$s%2$s</div>', $image, $title), $title, $image, $image_size, $post_id);
    }

    function format_post_icon($title, $image, $image_size='', $post_id=0) {
        return apply_filters( 'genesis_club_format_post_icon', 
            sprintf('<div class="entry">%1$s%2$s</div>', $image, $title), $title, $image, $image_size, $post_id);
    }

    function format_list_icon($title, $image, $image_size='', $post_id=0) {
        return apply_filters( 'genesis_club_format_list_icon', 
            sprintf('<li>%1$s%2$s</li>', $image, $title), $title, $image, $image_size, $post_id);
    }
   
    function format_post_excerpt( $title, $image, $excerpt, $post_id=0) {
        return apply_filters( 'genesis_club_format_post_excerpt', 
            genesis_markup( array(
				  'html5'   => '<article %s>',
				  'xhtml'   => sprintf( '<div class="%s">', implode( ' ', get_post_class() ) ),
				  'context' => 'entry',
				  'echo' => false) ) .
            (empty( $title) ? '' :  sprintf( genesis_html5() ? '<header class="entry-header"><h2 class="entry-title">%1$s</h2></header>' : '<h2>%1$s</h2>', $title) ) .
            sprintf( genesis_html5() ? '<div class="entry-content">%1$s%2$s</div>' : '%1$s%2$s', $image, $excerpt ) .
            genesis_markup( array( 'html5' => '</article>', 'xhtml' => '</div>', 'echo' => false)), 
            $title, $image, $excerpt, $post_id);  
    }

    function override_args($args, &$instance) {	
        $classes = array();
        $args = parent::override_args($args, $instance);
        if (isset($instance['posts_per_row'])) $classes[] = $this->widget_width_classes($instance['posts_per_row']);
        if (isset($instance['post_widget_class'])) $classes[] = $instance['post_widget_class'];
        $args['before_widget'] = preg_replace('/class="/', 'class="'. implode(' ', $classes). ' ', $args['before_widget'], 1);
        return $args;
   }
   
 }
}

if (!class_exists('Genesis_Club_Text_Widget')) {
  class Genesis_Club_Text_Widget extends Genesis_Club_Widget {

	private $tips = array(
			'text' => array('heading' => 'Text', 'tip' => 'Widget Content'),
			'autop' =>  array('heading' => 'Auto-paragraph', 'tip' => 'Click to convert automatically convert new lines to paragraph breaks.'),
			);
	
    private	$defaults = array('title' => '', 'html_title' => '', 'text' => '', 'autop' => false);

	
	function __construct() {
		$widget_ops = array('description' => __('Displays a Text widget with enhanced Title', GENESIS_CLUB_DOMAIN) );
		$control_ops = array();
		parent::__construct('genesis-club-text', __('Genesis Club Text', GENESIS_CLUB_DOMAIN), $widget_ops, $control_ops);
		$this->set_defaults($this->defaults);
	}

	function widget( $args, $instance ) {
      $args = $this->override_args($args, $instance) ;
      extract($args);
      echo $before_widget;
      $text = apply_filters( 'widget_text', empty( $instance['text'] ) ? '' : $instance['text'], $instance, $this );
      printf('<div class="textwidget">%1$s</div>', empty( $instance['autop'] ) ? $text : wpautop( $text ) );
      echo $after_widget;
	}

	function update( $new_instance, $old_instance ) {
		$instance = $this->update_instance( $new_instance, $old_instance );
		if (current_user_can('unfiltered_html') )
			$instance['text'] = $new_instance['text'];
		else
			$instance['text'] = wp_kses_post( $new_instance['text']); 
		$instance['autop'] = isset($new_instance['autop']);
		return $instance;
	}

	function form( $instance ) {
		$this->form_init ($instance, $this->tips);
		$this->print_form_field('text', 'textarea', array(), array('rows' => 16, 'cols' => 30, 'class' => 'widefat' ));
		$this->print_form_field('autop', 'checkbox');		
	}
  }
}