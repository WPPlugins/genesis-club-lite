<?php
if (!class_exists('Genesis_Club_Dashboard')) {
class Genesis_Club_Dashboard extends Genesis_Club_Admin {
	const ARCHIVE_HOOK_ID = 'genesis_club_archive_settings';
	const POST_HOOK_ID = 'genesis_club_post_settings';

	protected $tips = array(
		'custom_post_types' => array('heading' => 'Enable Plugin On', 'tip' => 'Click to enable the plugin to operate on the available custom post types.'),
   );

	function init() {
		add_action('admin_menu',array($this, 'admin_menu'));
	}

	function admin_menu() {
		$this->screen_id = add_menu_page($this->get_name(), $this->get_name(), 'manage_options', 
			$this->get_slug(), array($this,'page_content') );
		$intro = sprintf('Dashboard (v%1$s)', $this->version);				
		add_submenu_page($this->plugin->get_slug(), $this->get_name(), $intro, 'manage_options', $this->get_slug(), array($this, 'page_content') );
		add_action('admin_enqueue_scripts', array($this, 'register_admin_styles'));
		add_action('admin_enqueue_scripts', array($this, 'register_tooltip_styles'));
		add_action('load-widgets.php', array( $this, 'add_tooltip_support'));
		add_action('load-term.php', array($this, 'load_archive_page'));
		add_action('load-edit-tags.php', array($this, 'load_archive_page'));
 		add_action('load-edit.php', array( $this, 'load_post_page'));
 		add_action('load-post.php', array( $this, 'load_post_page'));
 		add_action('load-post-new.php', array( $this, 'load_post_page'));
		add_action('do_meta_boxes', array($this, 'do_meta_boxes'), 10, 2 );	 		
 		add_action('load-'.$this->get_screen_id(), array($this, 'load_page')); 		
	}

	function page_content() {
 		$title = $this->admin_heading($this->get_name().' '.__('Dashboard'));				
		$this->print_admin_form($title, __CLASS__, $this->get_keys()); 
	} 

	function do_meta_boxes( $post_type, $context) {
		if ($this->is_metabox_active($post_type, $context)) {
            $this->add_meta_box('post-settings', $this->get_name().' Post Settings', 'post_panel', null, 'normal', 'core', $post_type );
      }
	} 

	function load_page() {
 		if (isset($_POST['options_update'])) $this->save_dashboard();
		$this->fetch_message();
		$this->add_meta_box('controls','Control Panel', 'control_panel', array('options' => $this->options->get_options(false)));
		$this->add_meta_box('news', $this->get_name().' '.__('News'), 'news_panel', null, 'advanced');
		$this->set_tooltips($this->tips);
		add_action ('admin_enqueue_scripts',array($this, 'enqueue_admin_styles'));		
		add_action ('admin_enqueue_scripts',array($this, 'enqueue_styles'));		
  		add_action ('admin_enqueue_scripts',array($this, 'enqueue_scripts'));		
		add_action ('admin_enqueue_scripts',array($this, 'enqueue_postbox_scripts'));
		add_action ('admin_enqueue_scripts',array($this, 'enqueue_metabox_scripts'));
	}

	function load_post_page() {
		$this->add_tooltip_support();
		add_action ('admin_enqueue_scripts',array($this, 'enqueue_postbox_scripts'));
		add_action ('admin_enqueue_scripts',array($this, 'enqueue_metabox_scripts'));
	}
	
	function load_archive_page() {
		$this->add_tooltip_support();
		add_action( $_REQUEST['taxonomy'] . '_edit_form', array($this, 'archive_panel'), 10, 2 );	
		add_action ('admin_enqueue_scripts',array($this, 'enqueue_postbox_scripts'));
		add_action ('admin_enqueue_scripts',array($this, 'enqueue_metabox_scripts'));
	}

	function archive_panel($term, $slug) {
        printf('<div id="%1$s" class="termbox"><h3>%2$s</h3>%3$s</div>', 
            self::ARCHIVE_HOOK_ID, 
            __('Genesis Club Archive Settings', GENESIS_CLUB_DOMAIN), 
            $this->tabbed_metabox( self::ARCHIVE_HOOK_ID, apply_filters( self::ARCHIVE_HOOK_ID, array(), $term, $slug) ) );
    }
	
	function post_panel($post, $metabox) {
        print $this->tabbed_metabox( $metabox['id'], apply_filters( self::POST_HOOK_ID, array(), $post, $metabox) );
   }	

	function control_panel($post,$metabox) {
      $options = $metabox['args']['options'];
      $tabs = array('Modules' => $this->modules_panel() );
      if ($this->plugin->custom_post_types_exist()) $tabs += array('Custom Post Types' => $this->cpt_panel($options['custom_post_types']) );
      print $this->tabbed_metabox( $metabox['id'],  $tabs);
	}

	function save_dashboard() {
		check_admin_referer(__CLASS__);
		$modules = $this->plugin->get_modules_present();
		$new_options = array();
		$checked =  array_key_exists('checked_modules', $_POST) ? (array) $_POST['checked_modules'] : array();
		foreach ( $modules as $module => $info ) {
			$key = $this->plugin->get_disabled_key($module);
			$new_options[$key] = ! in_array($module, $checked); 
		}
		$new_options['custom_post_types'] = isset($_POST['custom_post_types']) ? $_POST['custom_post_types'] : array();
		$updates = $this->options->save_options($new_options); 
   		$message = sprintf($updates ? __('%1$s Settings saved.',GENESIS_CLUB_DOMAIN) : 
			__('No %1$s settings were changed since last update.', GENESIS_CLUB_DOMAIN), $this->get_name());
		$redir = add_query_arg( array('message' => urlencode($message), 'tabselect0' => isset($_POST['tabselect0']) ? $_POST['tabselect0'] : ''), $_SERVER['REQUEST_URI'] ); //add the message 
    	wp_redirect( $redir ); 
    	exit;
	}

	function enqueue_styles() {
		wp_enqueue_style($this->get_code('dashboard'), plugins_url('styles/dashboard.css',dirname(__FILE__)), array(), $this->get_version());
 	}	
 	
	function enqueue_scripts() {
		wp_enqueue_script('mixitup', plugins_url('scripts/jquery.mixitup.min.js',dirname(__FILE__)), array( 'jquery' ), $this->get_version() );
		add_action('admin_footer-'.$this->get_screen_id(), array($this, 'show_modules'));
 	}

	function cpt_panel($post_types){	
      $options = array();
		$all_custom_post_types = get_post_types(array('public' => true, '_builtin' => false), 'objects');	
		foreach ($all_custom_post_types as $post_type) $options[$post_type->name] = $post_type->labels->name;
      return $this->fetch_form_field('custom_post_types', $post_types, 'checkboxes', $options);
	}   

	function checkbox_helper($module, $checked, $disabled = false) {
		return sprintf ('<input type="checkbox" id="cb-select-%1$s" name="checked_modules[]" %2$svalue="%1$s" %3$s/>',
			$module, $checked ? 'checked="checked" ' : '', $disabled ? 'disabled="disabled" ' : '');			
	}

	function modules_panel() {
      	$list = '';
		$modules = $this->plugin->get_modules();
		foreach ( $modules as $module => $info ) {
			$present = $this->plugin->module_exists($module);
			$enabled = $present && $this->plugin->is_module_enabled($module);
			$verbose_status = $present ? ($enabled ? '' :  __('Inactive', GENESIS_CLUB_DOMAIN)) :  __('Pro', GENESIS_CLUB_DOMAIN);
			$list .= sprintf ('<li class="mix product-card"><div class="status-action clear"><span class="status">%1$s</span>%2$s</div><h2>%3$s</h2><div class="summary">%4$s</div></li>',
				$verbose_status, $this->checkbox_helper($module, $enabled, ! $present), $info['heading'], $info['tip']);
		}
  		$pro = sprintf('<a target="_blank" rel="external" href="%1$s">Genesis Club Pro</a>', GENESIS_CLUB_PRO_URL);
		$preamble = sprintf('<div class="actions"><input id="cb-select-all" type="checkbox" />Select/Deselect All or individually select the Genesis Club modules you need. Or click the link to find out more about %1$s features.</div>', $pro);
		return sprintf('%1$s<ul class="products_grid" class="wrap">%2$s</ul>', $preamble, $list);
	}

    function show_modules() {
    	print <<< SCRIPT
<script type="text/javascript">
//<![CDATA[
jQuery(document).ready( function($) {
	$('.products_grid').mixitup();

	$('#cb-select-all').click(function(){
        var checkboxes = $(".products_grid").find(':checkbox').not(':disabled');
        if($(this).prop('checked')) {
          checkboxes.prop('checked', true);
        } else {
          checkboxes.prop('checked', false);
        }
    });
});
//]]>
</script>
SCRIPT;
    }	
	
  }
}
