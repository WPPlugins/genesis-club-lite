<?php
class Genesis_Club_Display_Admin extends Genesis_Club_Admin {
    const INDICATOR = 'genesis_club_hiding';
    const HIDE_FROM_SEARCH = 'genesis_club_hide_from_search';
    const DISABLE_BREADCRUMBS = 'genesis_club_disable_breadcrumbs';

   private $display;
   
	protected $archive_tips = array(
		'archive_sorting' => array('heading' => 'Override Sort Order', 'tip' => 'Click to override the sort order of the posts on this archive.'),
		'archive_orderby' => array('heading' => 'Order By', 'tip' => 'Select the field to sort by.'),
		'archive_order' => array('heading' => 'Order', 'tip' => 'Ascending or descending.'),
		'archive_excerpt_image' => array('heading' => 'Excerpt Image', 'tip' => 'URL of image to use as the archive excerpt image for all posts in this archive. The image is used as is, so you need to provide the image at the exact size you want to display it.'),
		'archive_excerpt_images_on_front_page' => array('heading' => 'Use On Home Page', 'tip' => 'Use category image rather than individual featured images in post excepts on the home page.'),
		'archive_disable_breadcrumbs' => array('heading' => 'Disable Breadcrumbs', 'tip' => 'Click to disable breadcrumbs on this archive.'),
		'archive_postinfo_shortcodes' => array('heading' => 'Post Info Shortcodes', 'tip' => 'Here you can set Post Info of this specific term which will override the global setting. Use [] to remove Post Info completely.'),
		'archive_postmeta_shortcodes' => array('heading' => 'Post Meta Shortcodes', 'tip' => 'Here you can set Post Meta of this specific term which will override the global setting. Use [] to remove Post Meta completely.'),
	);
                
	protected $tips = array(
		'remove_blog_title' => array('heading' => 'Remove Blog Title Text', 'tip' => 'Click to remove text and h1 tags from the title on the home page. This feature allows you to place h1 tags elsewhere on the home page and just use a clickable responsive logo image in the title element.'),
		'logo' => array('heading' => 'Logo URL', 'tip' => 'Enter the full URL of a logo image that will appear in the title element on the left hand side of the header. Consult the theme instructions for recommendation on the logo dimensions, normally a size of around 400px by 200px is okay. The image file can be located in your media library, on Amazon S3 or a CDN.'),
		'logo_alt' => array('heading' => 'Logo Alt Text', 'tip' => 'Enter the ALT attribute for your logo.'),
		'logo_nopin' => array('heading' => 'Exclude From Pinterest', 'tip' => 'Click to exclude the logo from Pinterest sharing'),
		'comment_invitation' => array('heading' => 'Invitation To Comment', 'tip' => 'Enter your enticement to comment. This will replace "Leave A Reply" in HTML5 sites or "Speak Your Mind" in XHTML sites.'),
		'comment_notes_hide' => array('heading' => 'Hide Comment Notes', 'tip' => 'The commment note before the comment box refers to the email address not being displayed; the comment note after the comment box refers to the HTML tags which are permitted in the comment. <br/>Here you can decide to suppress one or both of these comment notes.'),
		'read_more_text' => array('heading' => 'Read More Text', 'tip' => 'Hide the text that appears before and after the comment box.'),
		'read_more_class' => array('heading' => 'Read More Class', 'tip' => 'Optional class that is added to the read more link. This is typically used to style the link as a button.'),
		'read_more_prefix' => array('heading' => 'Prefix', 'tip' => 'By default WordPress uses an ellipsis (...) before the read more link. Here you can replace this within something else.'),
		'breadcrumb_prefix' => array('heading' => 'Breadcrumb Prefix', 'tip' => 'Enter the text that prefixes the breadcrumb. This will replace "You are here:".'),
		'breadcrumb_archive' => array('heading' => 'Breadcrumb Archives', 'tip' => 'Enter the text that appears at the start of the archive breadcrumb. This will replace "Archives for".'),
		'before_content' => array('heading' => 'Before Content', 'tip' => 'Click to add a wide widget area below the header which stretches above the content, and any primary and secondary sidebar sections.'),
		'before_archive' => array('heading' => 'Before Archive', 'tip' => 'Click to add a widget area before the list of entries on an archive page. This can be used to add an introductory paragraph, video or slider at the top of the archive page that both provides unique content and incites interest in the topic with the goal of reducing the bounce rate and hence improving the page ranking in the SERPs.'),
		'before_entry' => array('heading' => 'Before Entry', 'tip' => 'Click to add a widget area immediately above the post title. This is typically used for ads or calls to action.'),
		'before_entry_content' => array('heading' => 'Before Entry Content', 'tip' => 'Click to add a widget area immediately before the post content. This is typically used to add social media icons for sharing the content.'),
		'after_entry_content' => array('heading' => 'After Entry Content', 'tip' => 'Click to add a widget area immediately after the post content. If your child theme already has this area then there is no need to create another one. This area is typically used to add social media icons for sharing the content.'),
		'after_entry' => array('heading' => 'After Entry', 'tip' => 'Click to add a widget area immediately after the entry on single pages and posts. This area is typically used for ads or calls to action.'),
		'after_archive' => array('heading' => 'After Archive', 'tip' => 'Click to add a widget area after all the entries on an archive page. This can be used to add a call to action or maybe an ad.'),
		'after_content' => array('heading' => 'After Content', 'tip' => 'Click to add a widget area immediately after the content just before the footer. The widget area will be under the content area and any sidebars. This area will typically be used for ads or calls to action'),
		'postinfo_shortcodes' => array('heading' => 'Post Info Short Codes', 'tip' => 'Content of the byline that is placed typically below or above the post title. Leave blank to use the child theme defaults or enter here to override. <br/>For example: <br/><code>[post_date format=\'M j, Y\'] by [post_author_posts_link] [post_comments] [post_edit]</code><br/>or to hide Post Info entirely use <code>[]</code>'),
		'postmeta_shortcodes' => array('heading' => 'Post Meta Short Codes', 'tip' => 'Content of the line that is placed typically after the post content. <br/> Leave blank to use the child theme defaults or enter here to override. <br/> For example: <br/><code>[post_categories before=\'More Articles About \'] [post_tags]</code><br/>or to hide Post Meta entirely use <code>[]</code>'),
		'no_page_postmeta' => array('heading' => 'Remove On Pages', 'tip' => 'Strip any post info from standard pages and also from excerpts on the front page.'),
		'no_archive_postmeta' => array('heading' => 'Remove On Archives', 'tip' => 'Strip any post info and post meta from the top and bottom of post excerpts on archive pages.'),
		'css_hacks' => array('heading' => 'Add Helper Classes', 'tip' => 'Add useful classes such as clearfix (for clearing floats) and dropcaps (for capitalizing the first letter of the first paragraph.'),
		'screen_reader' => array('heading' => 'Add Screen Reader', 'tip' => 'Add classes for screen reader text in labels, menus, search forms and read more links. Useful for older themes that do not have screen reader text classes defined.'),
		'disable_emojis' => array('heading' => 'Disable Emojis', 'tip' => 'Remove Emojis if you do not intend to use them.'),
		'custom_login_enabled' => array('heading' => 'Enable Custom Login', 'tip' => 'Enable Login Page Customizations.'),
		'custom_login_background' => array('heading' => 'Page Background URL', 'tip' => 'URL of image to use as the login page background - around 1600 x 1000 px in size'),
		'custom_login_logo' => array('heading' => 'Logo Background URL', 'tip' => 'URL of image to use as the logo. Recommended height is 160px. Width is in the range 100px to 400px'),
		'custom_login_user_label' => array('heading' => 'User Login Label', 'tip' => 'Label for the user/member login / email.'),
		'custom_login_button_color' => array('heading' => 'Button  Color', 'tip' => 'Choose color of the login button.'),
		'custom_login_button_text_color' => array('heading' => 'Button Text Color', 'tip' => 'Choose color of the text on the login button.'),
		'custom_login_labels_color' => array('heading' => 'Labels Color', 'tip' => 'Choose color of the login form labels.'),
		'custom_login_form_bgcolor' => array('heading' => 'Form Background Color', 'tip' => 'Background color of the login form'),
		'custom_login_form_opacity' => array('heading' => 'Form Opacity', 'tip' => 'Opacity of the login form background. The lower the number, the more transparent the login form is.'),
		'disable_breadcrumbs' => array('heading' => 'Disable Breadcrumbs', 'tip' => 'Click to disable breadcrumbs on this page.'),
		);

	function init() {
        $this->display = $this->plugin->get_module('display');	
		add_action('save_post', array($this, 'save_post'));
		add_action('do_meta_boxes', array($this, 'do_meta_boxes'), 30, 2 );		
		add_action('load-edit-tags.php', array($this, 'load_archive_page'));	
		add_action('load-term.php', array($this, 'load_archive_page'));	
		add_action('edit_term', array($this, 'save_archive'), 10, 2 );	
		add_action('admin_menu',array($this, 'admin_menu'));
	}
	
	function admin_menu() {
		$this->screen_id = add_submenu_page($this->get_parent_slug(), __('Display'), __('Display'), 'manage_options', 
			$this->get_slug(), array($this,'page_content'));
		add_action('load-'.$this->get_screen_id(), array($this, 'load_page'));
	}

	function page_content() {
		$title = $this->admin_heading('Genesis Club Display Settings');		
		$this->print_admin_form($title, __CLASS__, $this->get_keys()); 		
	}  
	
	function load_page() {
 		if (isset($_POST['options_update']) ) $this->save_display();
		$callback_params = array ('options' => $this->display->get_options(false));
		$this->add_meta_box('intro', 'Intro',  'intro_panel');
		$this->add_meta_box('display', 'Display Settings', 'display_panel', $callback_params);
		$this->add_meta_box('news', 'Genesis Club News', 'news_panel', null, 'advanced');
		$this->set_tooltips($this->tips);
		add_action ('admin_enqueue_scripts',array($this, 'enqueue_admin_styles'));
		add_action ('admin_enqueue_scripts',array($this, 'enqueue_metabox_scripts'));
		add_action ('admin_enqueue_scripts',array($this, 'enqueue_postbox_scripts'));
	}
	 
 	function load_archive_page() {
        if (isset($_GET['post_type']) && $this->plugin->is_post_type_enabled($_GET['post_type'])) {
            $this->set_tooltips($this->archive_tips);
            add_action( Genesis_Club_Dashboard::ARCHIVE_HOOK_ID, array($this, 'add_archive_panels'), 10, 3 );	
        }
	}

	function save_archive($term_id, $tt_id) {
		return isset( $_POST['archive'] ) ?
			$this->display->save_archive($term_id, (array) $_POST['archive']) : false;
	}	

	function save_display() {
		check_admin_referer(__CLASS__);
		return $this->save_options($this->display, __('Display',GENESIS_CLUB_DOMAIN ));
	}
    
	function save_post($post_id) {
        if ( wp_is_post_revision( $post_id ) ) return;
		if (array_key_exists(self::INDICATOR, $_POST)) {
            $post_type = get_post_type($post_id);
			$checkboxes = array_merge($this->display->get_toggles($post_type), $this->display->get_disables());	
			foreach ($checkboxes as $metakey)
				update_post_meta( $post_id, $metakey, array_key_exists($metakey, $_POST) ? $_POST[$metakey] : false);			
			do_action('genesis_club_hiding_settings_save',$post_id);
		}
	}

	function do_meta_boxes( $post_type, $context) {
		if ($this->is_metabox_active($post_type, $context)) {
            add_filter( Genesis_Club_Dashboard::POST_HOOK_ID, array($this, 'add_post_panel'), 5, 2);	//add to plugin metabox
			$current_screen = get_current_screen();
			if (method_exists($current_screen,'add_help_tab'))
	    		$current_screen->add_help_tab( array(
			        'id'	=> 'genesis_club_help_tab',
    			    'title'	=> __('Genesis Club'),
        			'content'	=> __('
<p>In the <b>Genesis Club Posts Settings - Hiding</b> section below you can choose NOT to show this page in site search page results, and control whether certain other elements shoudl apear on this page.</p>')) );
		}
	}

	function widget_area_visibility_checkbox($sidebar) {
        global $post;
		return $this->visibility_checkbox($post->ID, 'post'==$post->post_type ? 'hide' : 'show', $sidebar, '%1$s the %2$s widget area on this page');
    } 

	function add_post_panel($content, $post) {
		$this->set_tooltips($this->tips);
		return $content + array ('Display' => $this->hiding_panel($post));
    }

	function hiding_panel($post) {
		$options = $this->display->get_options();
      	$s = '';
		$s .= $this->form_field(self::INDICATOR, self::INDICATOR, '', 1, 'hidden'); 
		$s .= $this->visibility_checkbox($post->ID,'hide', 'title', '%1$s the %2$s on this page');
		$s .= $this->disable_checkbox($post->ID, 'disable', 'breadcrumbs', '%1$s breadcrumbs on this page');
		$s .= $this->disable_checkbox($post->ID, 'disable', 'autop', '%1$s auto-paragraphing of the page content');											
		if ($options['before_content']) $s .= $this->widget_area_visibility_checkbox('before_content');		
		if ($options['before_entry']) $s .= $this->widget_area_visibility_checkbox('before_entry');		
		if ($options['before_entry_content']) $s .= $this->widget_area_visibility_checkbox('before_entry_content');
		if ($options['after_entry_content']) $s .= $this->widget_area_visibility_checkbox('after_entry_content');
		if ($options['after_entry']) $s .= $this->widget_area_visibility_checkbox('after_entry');
		if ($options['after_content']) $s .= $this->widget_area_visibility_checkbox('after_content');
		$s = apply_filters('genesis_club_hiding_settings_show', $s, $post);
		
		return $s;
    }

 	function intro_panel(){		
		print('<p>The following sections allow you to tweak some Genesis settings you want to change on most sites without having to delve into PHP.</p>');
	}

	function display_panel($post, $metabox) {
      $options = $metabox['args']['options'];
      print $this->tabbed_metabox($metabox['id'],  array (
         'Logo' => $this->logo_panel($options),
         'Labels' => $this->labelling_panel($options),
         'Read More' => $this->read_more_panel($options),
         'PostInfo/Meta' => $this->meta_panel($options),
         'Extra Widget Areas' => $this->extras_panel($options),
         'Custom Login' => $this->custom_login_panel($options),
         'Misc' => $this->misc_panel($options),
      ));
   }	

	function logo_panel($options){
	  return	 	
         $this->fetch_form_field('remove_blog_title', $options['remove_blog_title'], 'checkbox') .
         $this->fetch_text_field('logo', $options['logo'], array('size' => 50, 'class' => 'large-text')) .
         $this->fetch_text_field('logo_alt', $options['logo_alt'], array('size' => 50, 'class' => 'large-text')) . 
         $this->fetch_form_field('logo_nopin', $options['logo_nopin'], 'checkbox') ;
	}

	
	function extras_panel($options){
      return
         $this->fetch_form_field('before_content', $options['before_content'], 'checkbox') .
         $this->fetch_form_field('before_archive', $options['before_archive'], 'checkbox') .
         $this->fetch_form_field('before_entry', $options['before_entry'], 'checkbox') .
         $this->fetch_form_field('before_entry_content', $options['before_entry_content'], 'checkbox') .
         $this->fetch_form_field('after_entry_content', $options['after_entry_content'], 'checkbox') .
         $this->fetch_form_field('after_entry', $options['after_entry'], 'checkbox') .
         $this->fetch_form_field('after_archive', $options['after_archive'], 'checkbox') .
         $this->fetch_form_field('after_content', $options['after_content'], 'checkbox');
	}	

	function meta_panel($options){
      return
         $this->fetch_form_field('no_archive_postmeta', $options['no_archive_postmeta'],  'checkbox') .
         $this->fetch_form_field('no_page_postmeta', $options['no_page_postmeta'],  'checkbox') .
         $this->fetch_form_field('postinfo_shortcodes', $options['postinfo_shortcodes'], 'textarea', array(), array('cols' => 30, 'rows' => 3, 'class' => 'large-text')) .
         $this->fetch_form_field('postmeta_shortcodes', $options['postmeta_shortcodes'], 'textarea', array(), array('cols' => 30, 'rows' => 3, 'class' => 'large-text'));
	}

	function labelling_panel($options){		 	
      return
         $this->fetch_text_field('comment_invitation', $options['comment_invitation'], array('size' => 40)) .
         $this->fetch_form_field('comment_notes_hide', $options['comment_notes_hide'], 'radio', 
			array(0 => 'hide neither', 'before' => 'hide note before', 'after' => 'hide note after', 'both' => 'hide both' )) .
         $this->fetch_text_field('breadcrumb_prefix', $options['breadcrumb_prefix'],  array('size' => 40)) .
         $this->fetch_text_field('breadcrumb_archive', $options['breadcrumb_archive'], array('size' => 40));
	}


	function read_more_panel($options){		 	
      return
         $this->fetch_text_field('read_more_text', $options['read_more_text'], array('size' => 40)) .
         $this->fetch_text_field('read_more_class', $options['read_more_class'], array('size' => 40)) .
         $this->fetch_text_field('read_more_prefix', $options['read_more_prefix'],  array('size' => 5));
	}

	function misc_panel($options){
	  return
         $this->fetch_form_field('css_hacks', $options['css_hacks'], 'checkbox') .
         $this->fetch_form_field('screen_reader', $options['screen_reader'], 'checkbox') .
         $this->fetch_form_field('disable_emojis', $options['disable_emojis'], 'checkbox');
	}	  

	function custom_login_panel($options){	
      return	 	
         $this->fetch_form_field('custom_login_enabled', $options['custom_login_enabled'], 'checkbox') .
         $this->fetch_text_field('custom_login_background', $options['custom_login_background'], array('class' => 'large-text')) .
         $this->fetch_text_field('custom_login_logo', $options['custom_login_logo'], array('class' => 'large-text')) .
         $this->fetch_text_field('custom_login_user_label', $options['custom_login_user_label'], array('size' => 30)) .
         $this->fetch_text_field('custom_login_form_opacity', $options['custom_login_form_opacity'], array('size' => 4, 'suffix' => 'valid range is 0.1 to 0.99')) .
         $this->fetch_text_field('custom_login_form_bgcolor', $options['custom_login_form_bgcolor'], array('size' => 8, 'class' => 'color-picker')) .         
         $this->fetch_text_field('custom_login_labels_color', $options['custom_login_labels_color'], array('size' => 8, 'class' => 'color-picker')).
         $this->fetch_text_field('custom_login_button_color', $options['custom_login_button_color'], array('size' => 8, 'class' => 'color-picker')).
         $this->fetch_text_field('custom_login_button_text_color', $options['custom_login_button_text_color'], array('size' => 8, 'class' => 'color-picker'));
	}	  

   
	function add_archive_panels($content, $term, $slug) {
        $archive = $this->display->get_archive($term->term_id) ;
 		$defaults = array('sorting' => false, 'orderby' => 'date', 'order' => 'DESC');
		$archive = is_array($archive) ?  array_merge($defaults,$archive) : $defaults;
        return $content + array(
            'Display' => sprintf('<table class="form-table">%1$s%2$s%3$s</table><p class="meta-options"><input type="hidden" name="genesis_club_archive_display" value="1" /></p>', 
                $this->archive_headings_panel($archive),
                $this->archive_excerpt_panel($archive), 
                $this->archive_sort_panel($archive)));
   }

	private function archive_sort_panel($archive) {
      $sort_options = array( '' => 'Select order', 'post_date' => 'Date first published', 'post_modified_gmt' => 'Date last updated', 
         'comment_count' => 'Number of comments', 'post_author' => 'Post Author Name',  'ID' => 'Post ID',
         'post_title' => 'Post Title', 'rand' => 'Random'  );

        return 
            $this->grouped_form_field($archive, 'archive', 'sorting', 'checkbox') .
            $this->grouped_form_field($archive, 'archive', 'orderby', 'select', $sort_options) .
            $this->grouped_form_field($archive, 'archive', 'order', 'radio', array('ASC' => 'Ascending', 'DESC' => 'Descending')) ;
	}

	private function archive_excerpt_panel($archive) {
		return 	
		   $this->grouped_form_field($archive, 'archive', 'excerpt_image', 'textarea', array(), array('cols' => 50, 'rows' => 2, 'class' => 'large-text')) .
		   $this->grouped_form_field($archive, 'archive', 'excerpt_images_on_front_page', 'checkbox');
	}
	
	private function archive_headings_panel($archive) {
		return 	
            $this->grouped_form_field($archive, 'archive', 'disable_breadcrumbs', 'checkbox').   
            $this->grouped_form_field($archive, 'archive', 'postinfo_shortcodes', 'textarea', array(), array('cols' => 30, 'rows' => 3, 'class' => 'large-text')).
            $this->grouped_form_field($archive, 'archive', 'postmeta_shortcodes', 'textarea', array(), array('cols' => 30, 'rows' => 3, 'class' => 'large-text'));
	}
	
}

