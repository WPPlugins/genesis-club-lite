<?php
class Genesis_Club_Post_Admin extends Genesis_Club_Admin {

	private $widget_tips = array(
			'widget_html_title' => array('heading' => 'Widget Title', 'tip' => 'The title is placed above the widget content and maybe contain some HTML (links, spans, breaks, etc)'),
			'widget_text' => array('heading' => 'Widget Content', 'tip' => 'This information only will be displayed alongside this post, in a widget area if the area contains a Genesis Club Post Specific Widget'),
			'widget_autop' => array('heading' => 'Auto-paragraph', 'tip' => 'Click to have a paragraph break added automatically for each line break.'),
	);
    
	function init() {
		add_action('do_meta_boxes', array( $this, 'do_meta_boxes'), 20, 2 );
		add_action('save_post', array( $this, 'save_post'));
		add_action('post_submitbox_misc_actions', array( $this, 'republish_link') );
	}

 	function admin_menu() {}		

	function page_content() {}   

	function load_page() {}
	
	function do_meta_boxes( $post_type, $context) {
		if ($this->is_metabox_active($post_type, $context)) {
         add_filter( 'genesis_club_post_settings', array($this, 'add_post_panel'), 10, 2);	//add to plugin metabox		    	
		}
	}

	function save_post($post_id) {
        if ( wp_is_post_revision( $post_id ) ) return;
        $this->save_postmeta($post_id,  'genesis_club_widget', Genesis_Club_Post_Specific_Widget::WIDGET_CONTENT_META_KEY, Genesis_Club_Post_Specific_Widget::get_widget_defaults());
            }

	function add_post_panel($content, $post) {
		return $content + array ( 'Widget Content' => $this->widget_panel($post)) ;
	}	 
 
	function widget_panel($post) {
		$form_data = $this->get_meta_form_data(Genesis_Club_Post_Specific_Widget::WIDGET_CONTENT_META_KEY, 'widget_', Genesis_Club_Post_Specific_Widget::get_widget_defaults());
		$this->set_tooltips($this->widget_tips);
		return sprintf ('<div class="diy-wrap">%1$s%2$s%3$s<p class="meta-options"><input type="hidden" name="genesis_club_widget" value="1" /></p></div>',
			$this->meta_form_field($form_data, 'html_title', 'textarea', array(), array('cols' => 40, 'rows' => 2, 'class' => 'large-text')),
			$this->meta_form_field($form_data, 'text', 'textarea', array(), array('cols' => 40, 'rows' => 10, 'class' => 'large-text')),
			$this->meta_form_field($form_data, 'autop', 'checkbox'));      
    }  

   function republish_link() {
      global $post;
      $post_type_object = get_post_type_object($post->post_type); 
      $can_publish = current_user_can($post_type_object->cap->publish_posts);
      if (! ($can_publish && ('publish'==$post->post_status))) return;
      print <<< SCRIPT
<script>
jQuery(document).ready( function($) {
   $('.cancel-timestamp').after('<a href="#republish" class="republish hide-if-no-js button-cancel">Set publish date to now</a>');
   $('.republish').click( function( event ) {
      $('#aa').val($('#cur_aa').val()); 
      $('#mm').val($('#cur_mm').val()); 
      $('#jj').val($('#cur_jj').val());
      $('#hh').val($('#cur_hh').val());
      $('#mn').val($('#cur_mn').val());    
	   $('.save-timestamp').click();
	   pub = $('#timestamp').find('b').html();
	   pub = pub.replace(' : ',':');
	   pub = pub.replace('@ ','@');
	   if($.isNumeric(pub.charAt(0))) pub = pub.substring(3)
	   $('#timestamp').find('b').html(pub);
      });
   });
</script>
SCRIPT;
    
   }

}
