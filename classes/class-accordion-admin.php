<?php
class Genesis_Club_Accordion_Admin extends Genesis_Club_Admin {
   private $accordion;
	protected $tips = array(
		'accordion_enabled' => array('heading' => 'Accordion Enabled', 'tip' => 'Click to enable the accordion on this page'),
		'accordion_header_class' => array('heading' => 'Header Class', 'tip' => 'Enter a custom class if you want to override the accordion header styling.'),
		'accordion_content_class' => array('heading' => 'Content Class', 'tip' => 'Enter a custom class if you want to override the accordion content styling'),
		'accordion_container_class' => array('heading' => 'Container Class', 'tip' => 'Enter a custom class if you want to override the accordion container styling'),
		'accordion_nopaging' => array('heading' => 'No Paging', 'tip' => 'Click to checkbox to disable paging and hence show all the FAQ posts in the accordion'),
		'accordion_open_first' => array('heading' => 'Open First Entry', 'tip' => 'Open the first entry in the accordion automatically on loading.'),
		'accordion_scroll_top' => array('heading' => 'Scroll To Top', 'tip' => 'Click to checkbox to make the accordion scroll to the top of the page - this is useful when you have long answers to short questions.'),
		'accordion_header_depth' => array('heading' => 'Header Depth', 'tip' => 'If you have a fixed header then supply its height and also set the scrolling option.'),
		);
	
	function init() {		
        $this->accordion = $this->plugin->get_module('accordion');
		add_action('admin_menu',array($this, 'admin_menu'));
		add_action('load-post.php', array($this, 'load_post_page'));	
		add_action('load-post-new.php', array($this, 'load_post_page'));	
		add_action('load-edit-tags.php', array($this, 'load_archive_page'));	
        add_action('load-term.php', array($this, 'load_archive_page'));	
		add_action('edit_term', array($this, 'save_archive'), 10, 2 );	
		add_action('do_meta_boxes', array($this, 'do_meta_boxes'), 20, 2 );
		add_action('save_post', array($this, 'save_post'));
	}
	
	function admin_menu() {
		$this->screen_id = add_submenu_page($this->get_parent_slug(), __('Accordion'), __('Accordion'), 'manage_options', 
			$this->get_slug(), array($this,'page_content'));
		add_action('load-'.$this->get_screen_id(), array($this, 'load_page'));
	}

	function page_content() {
 		$title = $this->admin_heading('Genesis Club Accordion Settings');				
		$this->print_admin_form($title, __CLASS__); 
	} 	
	
	function load_page() {
		$this->add_meta_box('intro', 'Instructions',  'intro_panel');
		$this->add_meta_box('accordion', 'Accordion',  'accordion_panel');
		$this->add_meta_box('news', 'Genesis Club News', 'news_panel', null, 'advanced');
		add_action ('admin_enqueue_scripts',array($this, 'enqueue_admin_styles'));
		add_action ('admin_enqueue_scripts',array($this, 'enqueue_metabox_scripts'));
		add_action ('admin_enqueue_scripts',array($this, 'enqueue_postbox_scripts'));
	}

	function load_post_page() {
		$this->set_tooltips($this->tips);
	}

	function load_archive_page() {
      if (isset($_GET['post_type'])
      && $this->plugin->is_post_type_enabled($_GET['post_type'])) {
         add_filter( Genesis_Club_Dashboard::ARCHIVE_HOOK_ID, array($this, 'add_archive_panel'), 10, 3 );	
         $this->set_tooltips($this->tips);
      }
	}
	
	function do_meta_boxes( $post_type, $context) {
	  if ($this->is_metabox_active($post_type, $context)) {
		    add_filter( 'genesis_club_post_settings', array($this, 'add_post_panel'), 10, 2);	//add to plugin metabox
	  }
	}
	
	function intro_panel() {
		$url = admin_url('edit.php');
		$url2 = admin_url('edit-tags.php?taxonomy=category');
		print <<< INTRO
<p class="attention">There are no settings on this page.</p>
<p class="attention">However, links are provided to where you can set up accordions for your FAQs.</p>
<p class="bigger">For a short single page FAQ go to the <a href="{$url}>?post_type=page">Page Editor</a>, click to edit your FAQ page, and then scroll down and edit the Accordion section.</p>
<p class="bigger">For a FAQ that is made up of individual posts in the FAQ category go to the <a href="{$url2}">Category Editor</a>, choose you FAQ category and then scroll down and edit the Accordion section.</p>
INTRO;
	}
	
 	function accordion_panel($post,$metabox) {
      print $this->tabbed_metabox( $metabox['id'], array(
         'Help' => $this->help_panel(),
         'Tips' => $this->tips_panel(),
         'Example' => $this->example_panel(),
		));
   }
	
	function help_panel() {
 		$screenshot = plugins_url('images/accordion.jpg',dirname(__FILE__));
		return <<< HELP
<p>Below is a annotated screenshot of the <i>Accordion Settings</i>.</p>
<p>Simply click the checkbox to enable the accordion</p>
<p>If you want to add your own styling you have the option to override the CSS class for the header (the question), and the CSS class for the content (the answer). </p>
<p><img src="{$screenshot}" alt="Screenshot on Accordion Settings" /></p>
<p>If the section does not appear in the Post or Category Editor then click the <i>Screen Options</i> at the top of the page and make sure you click the checkboxes to show the Accordion Settings.</p>
<p>For an accordion made from posts in a category, then the plugin uses each post title as the question, and the post content as the answer.<p>
<p>For an accordion made on a single page then the page should consist of a &lt;h3&gt; heading for each question with a single paragraph beneath it for each answer. You can have multi-paragraph answers but in this case to you need to wrap all the paragraphs that hold the answer in a DIV element.</p>
HELP;
	}

	function tips_panel() {
		return <<< TIPS
<p>Use a single page for your FAQ when you have relatively few questions and short answers and you do not envisage your FAQ changing much over time.</p>
<p>Conversely, if you have many questions, long answers or frequent updates then choosing the FAQ category approach will be better from the point of view of SEO, the user experience, and from  administration of the FAQ.</p>
TIPS;
	}
	
	function example_panel() {
		$this->accordion->add_accordion (array('enabled' => true, 'type' => 'demo', 'header_class' => '', 'content_class' => '.accordion-content no-margin'));
		$support_url = GENESIS_CLUB_SUPPORT_URL;
		return <<< EXAMPLE
<p>Click the questions below to see an accordion FAQ in action.</p>
<div class="accordion">
<h3>What Is A FAQ?</h3>
<p>A FAQ is a list of frequently asked questions, and the answers.</p>
<h3>What Is An Accordion FAQ?</h3>
<p>It is a way of just displaying the questions, and only exposing each answer when the user clicks the question.</p>
<h3>What Are The Two Ways Of Creating FAQs?</h3>
<ul>
<li>Use a Single Page - Create a page with a title of FAQ, on something similar, and add questions and answers where the question is a h3 header and the answer is a single paragraph.</li>
<li>Use a FAQ Category - create a post category called FAQ, or something similar, and write a post every time you have a question. The post title should be the question, and the content of the post is the answer.</li>
</ul>
<h3>But What If I Want Multiple FAQs?</h3>
<p>Not a problem. Either use different categories or sub-categories for each FAQ. You can put FAQ as an entry on the menu, and use sub menu options for each sub-category FAQ. It is all good!</p>
<h3>I Have Set Up A FAQ But It Is Not FAQing Working!!!</h3>
<p>Calm down dear! Help is at hand. Head over to the <a target="_blank" href="https://www.facebook.com/GenesisClubbers">DIYWebmastery page on Facebook</a> and ask your question.  Or, if you have an up to date licence you can get priority support at  <a href="{$support_url}">Genesis Club Pro Support</a>.</p>  
</div>
EXAMPLE;
	}
	
	function save_archive($term_id, $tt_id) {
		return isset( $_POST['accordion'] ) ?
			$this->accordion->save_accordion('terms', $term_id, (array) $_POST['accordion']) : false;
	}	

	function save_post($post_id) {
        if ( wp_is_post_revision( $post_id ) ) return;
		return isset( $_POST['accordion'] ) ?  
			$this->accordion->save_accordion('posts', $post_id, (array) $_POST['accordion']) : false;
	}

	function add_post_panel($content, $post) {
		return $content + array ('Accordion' => $this->accordion_section($this->accordion->get_accordion('posts', $post->ID), false)) ;
   }	

	function add_archive_panel($content, $term, $tt_id) {
		return array ('Accordion' => $this->accordion_section($this->accordion->get_accordion('terms', $term->term_id), true)) + $content ;
	}	

	private function accordion_section($accordion, $is_archive){
		$defaults = $this->accordion->get_accordion_defaults();
		$accordion = is_array($accordion) ?  shortcode_atts($defaults,$accordion) : $defaults;
		if ($is_archive) {  //use table on archive pages
			$start_wrap = '<table class="form-table">';
			$end_wrap = '</table>';
			$wrap = 'tr';
		} else {  //use div on page editor
			$start_wrap = '<div class="diy-wrap">';			
			$end_wrap ='</div>';		
			$wrap = 'div';
		}
		$s = '';	
		$s .= $this->accordion_form_field('enabled', $accordion['enabled'], 'checkbox', array(), $wrap);
		$s .= $this->accordion_form_field('header_class', $accordion['header_class'], 'text', array('size' => 20), $wrap);
		$s .= $this->accordion_form_field('content_class', $accordion['content_class'], 'text', array('size' => 20), $wrap);
		$s .= $this->accordion_form_field('container_class', $accordion['container_class'], 'text', array('size' => 20), $wrap);
		if ($is_archive) {           
			$s.= $this->accordion_form_field('nopaging', $accordion['nopaging'], 'checkbox', array(), $wrap);
		}
		$s .= $this->accordion_form_field('open_first', $accordion['open_first'], 'checkbox', array(), $wrap);
		$s .= $this->accordion_form_field('scroll_top', $accordion['scroll_top'], 'checkbox', array(), $wrap);
		$s .= $this->accordion_form_field('header_depth', $accordion['header_depth'], 'text', array('size' => 3, 'suffix' => 'px'), $wrap);
		return $start_wrap . $s . $end_wrap;
	}

	private function accordion_form_field($fld, $value, $type, $args, $wrap) {
		$id = 'accordion_'.$fld;
		$name = 'accordion['.$fld.']';	
		$options = array();
		return $this->form_field($id, $name, false, $value, $type, $options, $args, $wrap);
	}

}
