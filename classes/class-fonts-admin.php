<?php
class Genesis_Club_Fonts_Admin extends Genesis_Club_Admin {
    protected $fonts;
   
	protected $tips = array(
		'fonts' => array('heading' => 'Fonts', 'tip' => 'Choose the Google Fonts you want to use on this site'),
        'subsets' => array('heading' => 'Character Sets', 'tip' => 'Choose any additional characters sets'),
        'effects' => array('heading' => 'Effects', 'tip' => 'Choose any font effects you would like to apply'),
	);

	private $font_tips = array(
		'font_name' => array('heading' => 'Font Name', 'tip' => 'Enter the first few characters if the font you require then press the Search button.'),
		'variant' => array('heading' => 'Variants', 'tip' => 'Font variants'),
		);

	private $add_or_edit = false;
	private $font = '';
	private $list;
	
	function get_list() {
		return $this->list;
	}

	function set_list($list) {
		$this->list = $list;
	}
	
	function init() {
        $this->fonts = $this->plugin->get_module('fonts');
		add_action('admin_menu',array($this, 'admin_menu'));
	}
	
	function admin_menu() {
		$this->screen_id = add_submenu_page($this->get_parent_slug(), __('Genesis Club Fonts'), __('Fonts'), 'manage_options', 
			$this->get_slug(), array($this,'page_content'));
		add_action('load-'.$this->get_screen_id(), array($this, 'load_page'));
	}

	function load_page() {
      if (isset($_POST['options_update'] ) ) $this->save_families();
      $this->fetch_message();
      if ($this->add_or_edit = $this->handle_actions()) {  //Individual Font - add or edit
		 $this->add_meta_box('fonts', 'Font Search', 'search_panel');
         $this->set_tooltips($this->font_tips);
		} else {       
         require_once (dirname(__FILE__).'/class-fonts-table.php');
			$this->set_list(new Genesis_Club_Fonts_Table($this->get_url(), $this->fonts));
 			$callback_params = array ( 'options' => $this->fonts->get_options());
			$this->add_meta_box('intro', 'Intro', 'intro_panel');
			$this->add_meta_box('families', 'Installed Google Fonts', 'families_panel', $callback_params);
			$this->add_meta_box('fonts', 'Available Google Fonts', 'fonts_panel', $callback_params, 'advanced');
			do_action('genesis_club_fonts_settings'); //add in any extras here
         $this->add_meta_box('news', 'Genesis Club News', 'news_panel', null, 'advanced');			
		   $this->set_tooltips($this->tips);
		}		
		add_action('admin_enqueue_scripts', array($this, 'enqueue_fonts_styles'));		
		add_action('admin_enqueue_scripts',array($this, 'enqueue_admin_styles'));
		add_action('admin_enqueue_scripts',array($this, 'enqueue_metabox_scripts'));
		add_action('admin_enqueue_scripts',array($this, 'enqueue_postbox_scripts'));
	}

	function enqueue_fonts_styles() {
		wp_enqueue_style($this->get_code('fonts'), plugins_url('styles/fonts.css', dirname(__FILE__)), array(),$this->get_version());		
}	

	function page_content() {
      $url = remove_query_arg( array( 'action','noheader', 'message', 'lastaction'), $_SERVER['REQUEST_URI']);
		$add_new = $this->add_or_edit ? '' : '<a href="'.$url.'&action=add'.'" class="add-new-h2">Add Font</a>' ;
		$title = $this->add_or_edit ? ucwords($_GET['action']. ' font') : 'Genesis Club Fonts';		
		$this->print_admin_form ($this->admin_heading($title.$add_new), __CLASS__);
 	}

	function handle_actions() {
      $add_or_edit = false; 
      $this->font = '';
      $action = $this->fetch_action();
		$font_id = array_key_exists('font_id', $_GET)  ? $_GET['font_id'] : 0;
		if ($font_id) {
 		   if ($this->fonts->family_exists($font_id)) 	
			   switch ($action) {		
				  case 'edit' : $this->font = $font_id; $add_or_edit = true; break; 
				  case 'delete' : $this->delete($font_id); break; 
				  default: { 	wp_die(__('Unknown/invalid action for font.'));  } 
			   }
         else
			   wp_die ($font_id.' font not found');        
		} else {	
         switch ($action) {
            case 'add': $add_or_edit = true; break;
            case 'delete': {
               if (isset( $_REQUEST['cb'] ) ) {
			         check_admin_referer( 'bulk-fonts' );
			         $checked =  (array) $_REQUEST['cb'];
                  $this->delete_rows($checked); 
               }
               break;    
            }
			   default: $this->advanced_actions($action);
			}
		}
      return $add_or_edit;
	}

	function advanced_actions($action) {
        switch ($action) {
            case 'subsets' : $this->save_subsets(); break;
            case 'effects' : $this->save_effects(); break;
            case 'refresh' : $this->refresh_fonts(); break;
 			default: {}
        }
	}	
	
	function fetch_action() {
      $action =  array_key_exists("action",$_REQUEST) ? $_REQUEST['action'] : -1 ;
		$action2 =  array_key_exists("action2",$_REQUEST) ? $_REQUEST['action2'] : -1;
		if ($action == -1) $action = $action2;
      return $action;
	}
	 	
	function list_fonts($title) {
		printf('<div class="wrap nosubsub">%1$s<div id="poststuff"><form id="genesis-club-pro-fonts" method="post" action="%2$s">', $title,  $this->get_url());
      $this->get_list()->prepare_items();
      $this->get_list()->display();
      print('<div id="ajax-response"></div></form>');
      do_meta_boxes($this->get_screen_id(), 'normal', null); 
      print('</div></div>');
	}	

	function delete_rows($font_ids) {
      $redir = wp_get_referer();
      $deleted = $this->fonts->delete_families($font_ids);
		$message = sprintf(__('%d Fonts have been deleted. '), $deleted);	
		$redir = add_query_arg( array('message' => urlencode($message), 'lastaction' => 'delete'), $redir ); //add the message 
    	wp_redirect( $redir ); 
    	exit;
	}

	function delete($font_id) {
		$referer = 'delete_font_' . $font_id; 
		check_admin_referer($referer);
		$redir = wp_get_referer();
		$redir =  remove_query_arg( array( 'action','noheader','font_id'), $redir) ; //clear params
      	$updated = $this->fonts->delete_families($font_id); 
      	$message = sprintf($updated ?
			__( 'Font %s was removed successfully.', GENESIS_CLUB_DOMAIN) : 
			__( 'Font %s could not be removed.', GENESIS_CLUB_DOMAIN),$font_id);
		$redir = add_query_arg( array('message' => urlencode($message), 'lastaction' => 'delete'), $redir ); //add the message 
    	wp_redirect( $redir ); 
    	exit;
	}

 	function intro_panel($post,$metabox){	 	
		_e('<p>This feature makes it easy to install Google Fonts on your site.</p>');
		_e('<p>Firstly, go to <a href="http://www.google.com/fonts/" rel="external" target="_blank">Google Fonts</a> to see what fonts you like. Then come back here to install them here.</p><p>Click the "Add Font" button at the top of the page to get started.</p>', GENESIS_CLUB_DOMAIN);
	}

	function fonts_panel($post,$metabox) {
	  $options = $metabox['args']['options'];		
        print $this->tabbed_metabox( $metabox['id'], array(
         'Refresh Fonts' => $this->refresh_panel(),
         'Character Sets' => $this->subsets_panel($options),
         'Font Effects' => $this->effects_panel($options),
		));
   }	

	function search_panel(){	
		$font_name = isset($_REQUEST['font_name']) ? trim($_REQUEST['font_name']) : ''; //override if manual entry
	  $s = $this->fetch_form_field('font_name', $font_name, 'text', array(), array('size' => 20, 'suffix' => '<button class="button">Search</button>'));
      if ($font_name) {
         $this->font = $this->get_font_key($font_name);
         $s .= '<hr/>';
         $families = $this->fonts->get_families();
         $all_fonts = $this->fonts->get_all_fonts();
         $matching_fonts = array_intersect_key($all_fonts, array_flip(array_filter(array_keys($all_fonts),array($this, 'font_match'))));
         foreach ($matching_fonts as $key => $font) {
            $s .= $this->fetch_font($key, $font, isset($_POST['family-'.$key]) ? array($_POST['family-'.$key]) : (array_key_exists($key, $families) ? $families[$key]['variants'] : array()));
         }
         $s .= $this->submit_button('Update Fonts', 'options_update') ;
      }
      print $s;
   }	 

   function fetch_font($key, $font, $values) {
      $variants = array_combine($font['variants'], $font['variants']);
      array_walk($variants, array($this,'font_variant'));
      return $this->utils->form_field($key, 'family-'.$key, $font['family'], $values, 'checkboxes', $variants, array('class' => 'families')) ;  
   }
 
   function font_variant(&$item, $key) {
      $item = ucwords($key);
   }

   function font_match($key) {
      return stripos($key, $this->font) !== FALSE  ;
   }

	function refresh_panel(){	
      $all_fonts = $this->fonts->get_all_fonts();
		return sprintf('<form id="%1$s" method="post" action="%2$s">%3$s%4$s%5$s%6$s</form>',
         $this->get_code('refresh'),
         $_SERVER['REQUEST_URI'] .'&action=refresh&noheader ',
         wp_nonce_field('refresh','nonce_refresh', true, false),
         sprintf ('<p>%1$s %2$s</p>', count($all_fonts), __('Google Fonts are available')),
         __('Google adds more fonts every week or so. To stay up to date with the full set of Google Fonts click the Refresh button below at least once every few weeks.'),		
		   $this->submit_button(__('Refresh Google Fonts', GENESIS_CLUB_DOMAIN),'refresh_fonts'));  
	}

	function effects_panel($options){	
		$all_effects = $this->fonts->get_effects();	
		return sprintf('%6$s<form id="%1$s" method="post" action="%2$s">%3$s%4$s%5$s</form>', 
         $this->get_code('effects'), 
         $_SERVER['REQUEST_URI'] .'&action=effects&noheader ',
         wp_nonce_field('effects','nonce_effects', true, false), 
         $this->fetch_form_field('effects', $options['effects'], 'checkboxes',  array_combine($all_effects, $all_effects), array('class' =>'effects')),	
         $this->submit_button('Update Effects','update_effects'),
         __('<p><a href="https://developers.google.com/fonts/docs/getting_started#Effects" rel="external" target="_blank">Google Font effects</a> are a Google beta feature.</p><p>To use a font effect on a specific element, add a class with the prefix <i>font-effect-</i></p><p>So for example, apply <code>&lt;span class="font-effect-fire-animation">Fire!&lt;/span></code> to use the fire animation font effect.</p>'));
	}

	function subsets_panel($options){	
		$all_subsets = $this->fonts->get_subsets();
		return sprintf('<form id="%1$s" method="post" action="%2$s">%3$s%4$s%5$s</form>', 
         $this->get_code('subsets'), 
         $_SERVER['REQUEST_URI'] .'&action=subsets&noheader ',
         wp_nonce_field('subsets', 'nonce_subsets', true, false), 
         $this->fetch_form_field('subsets', $options['subsets'], 'checkboxes',  array_combine($all_subsets, $all_subsets), array('class' =>'subsets')),
         $this->submit_button('Update Character Sets','update_subsets'));	
   }

   function families_panel($post, $metabox){
      $this->get_list()->prepare_items();
      $this->get_list()->display();
      print('<div id="ajax-response"></div>');
	}

	function save_subsets() {
      check_admin_referer('subsets', 'nonce_subsets');	
		$options = $this->fonts->get_options(false);
      $options['subsets'] = $_POST['subsets'];
		$saved = $this->fonts->save_options($options) ;
        $message = $saved ? __('Characters Sets were updated successfully', GENESIS_CLUB_DOMAIN) : __('Characters Sets were not updated', GENESIS_CLUB_DOMAIN);		
      $redir = wp_get_referer(); //get the referer
      $redir = remove_query_arg( array( 'action','noheader','font_id'), $redir) ; //remove the action
      $redir = add_query_arg( array('message' => urlencode($message), 'lastaction' => 'subsets'), $redir ); //update the URL    	
      wp_redirect( $redir ); 
      exit;   
	}

	function save_effects() {
		check_admin_referer('effects','nonce_effects');	
		$options = $this->fonts->get_options(false);
      $options['effects'] = $_POST['effects'];
		$saved = $this->fonts->save_options($options) ;
        $message = $saved ? __('Font Effects were updated successfully', GENESIS_CLUB_DOMAIN) : __('Font Effects were not updated', GENESIS_CLUB_DOMAIN);
		$redir = wp_get_referer(); //get the referer
		$redir = remove_query_arg( array( 'action','noheader','font_id'), $redir) ; //remove the action
		$redir = add_query_arg( array('message' => urlencode($message), 'lastaction' => 'effects'), $redir ); //update the URL    	
    	wp_redirect( $redir ); 
    	exit;   
	}

	function save_families() {
		check_admin_referer(__CLASS__);
		$all_fonts = $this->fonts->get_all_fonts();
      $new_fonts = array();
      foreach ($_POST as $key => $values) {
         if ('family-'==substr($key,0,7)) {
            $font_id = substr($key,7);
            $new_fonts[$font_id] = array('family' => $all_fonts[$font_id]['family'], 'variants' => $values);
         }
      }
		$updated = $this->fonts->save_families(array_merge($this->fonts->get_families(), $new_fonts));
      $message = $updated ? __('Font Families were updated successfully', GENESIS_CLUB_DOMAIN) : __('Font Families were not updated', GENESIS_CLUB_DOMAIN);	
		$redir = wp_get_referer(); //get the referer
		$redir =  remove_query_arg( array( 'action','noheader', 'font_id'), $redir) ; //remove the action
		$redir = add_query_arg( array('message' => urlencode($message), 'lastaction' => 'fonts'), $redir ); //update the URL    	
    	wp_redirect( $redir ); 
    	exit;   
	}

	function refresh_fonts() {
		check_admin_referer('refresh','nonce_refresh');	
        $message = $this->upgrade() ? __('Google Fonts were updated successfully', GENESIS_CLUB_DOMAIN) : __('Google Fonts have not changed', GENESIS_CLUB_DOMAIN);		
		$redir = wp_get_referer(); //get the referer
		$redir = remove_query_arg( array( 'action','noheader','font_id'), $redir) ; //remove the action
		$redir = add_query_arg( array('message' => urlencode($message), 'lastaction' => 'refresh'), $redir ); //update the URL    	
    	wp_redirect( $redir ); 
    	exit;   
	}

  function upgrade() {
      $raw_response = wp_remote_request('https://www.googleapis.com/webfonts/v1/webfonts?key=' . Genesis_Club_Fonts::GOOGLE_FONTS_API_KEY);
      if ( is_wp_error( $raw_response ) || (200 != $raw_response['response']['code']) || empty($raw_response)) return false; 
     	$values = (is_array($raw_response) && array_key_exists('body',$raw_response)) ? $raw_response['body'] : false;          
      $arr = json_decode($values, true);
      $fonts = array();
      foreach ($arr['items'] as $font) {
         $family = $font['family'];
         $key = $this->get_font_key($family);
         $fonts[$key] = array('family' => $family, 'variants' => $font['variants']);
      }
      return update_option(Genesis_Club_Fonts::ALL_FONTS_OPTION_NAME, $fonts);  //Refresh List Of All Google Fonts
   }

   function get_font_key($family) {
      return preg_replace('/[^a-z0-9]/','_', strtolower($family));      
   }

}
