<?php
class Genesis_Club_Signature_Admin extends Genesis_Club_Admin {
    const TOGGLE_SIGNATURE = 'genesis_club_toggle_signature';
    private $signature;

	function init() {		
	    $this->signature = $this->plugin->get_module('signature');
		$this->slug = 'user';
		add_action('load-profile.php', array($this, 'load_profile'));	
		add_action('load-user-edit.php', array($this, 'load_profile'));	
		add_action('personal_options_update', array($this, 'save_profile'));		
		add_action('edit_user_profile_update', array($this, 'save_user'));
		add_action('genesis_club_hiding_settings_show', array($this, 'add_page_visibility'), 20, 2);
		add_action('genesis_club_hiding_settings_save', array($this, 'save_page_visibility'), 20, 1);
		add_action('admin_menu',array($this, 'admin_menu'));
		//add_action('user_register', array($this, 'maybe_fix_user_nicename'), 10, 1);
		//add_action('profile_update', array($this, 'maybe_fix_user_nicename'), 10, 2);		
	}
	
	function admin_menu() {
		$this->screen_id = add_submenu_page($this->get_parent_slug(), __('Genesis Club Signatures'), __('Signatures'), 'manage_options', 
			$this->get_slug(), array($this,'page_content'));
 		add_action('load-'.$this->get_screen_id(), array($this, 'load_page')); 		
	}
	
	function page_content() {
 		$title =  $this->admin_heading('Genesis Club Signatures');				
		$this->print_admin_form($title, __CLASS__); 
	} 	
	
	function load_page() {
		$this->add_meta_box('intro', __('Instructions',GENESIS_CLUB_DOMAIN),  'intro_panel');
		$this->add_meta_box('signature', __('Signatures',GENESIS_CLUB_DOMAIN), 'signature_panel');
		$this->add_meta_box('news', 'Genesis Club News', 'news_panel',null, 'advanced');
		add_action ('admin_enqueue_scripts',array($this, 'enqueue_admin_styles'));
		add_action ('admin_enqueue_scripts',array($this, 'enqueue_metabox_scripts'));		
		add_action ('admin_enqueue_scripts',array($this, 'enqueue_postbox_scripts'));
	}
 		
	function get_user() {
		global $user_id;
		wp_reset_vars(array('user_id'));  //get ID of user being edited if not editing own profile 
		return (isset($user_id) && ($user_id > 0)) ? new WP_User((int) $user_id) : wp_get_current_user();	        
	}

	function add_form_type () {
		echo ' enctype="multipart/form-data"';
	}

	function load_profile() {
		add_action('user_edit_form_tag', array($this,'add_form_type'));
		$profile = $this->get_user();
		if (!$this->is_profile() || current_user_can('manage_options')) {
			add_action($this->is_profile() ? 'show_user_profile' :'edit_user_profile', array($this,'show_authors_panel'),12,2);
			$current_screen = get_current_screen();
			if (method_exists($current_screen,'add_help_tab')) 
    		   $current_screen->add_help_tab( array(
        		'id'	=> 'genesis_club_instructions_tab',
        		'title'	=> __('Genesis Club Instructions'),
        		'content'	=> '<h3>Genesis Club Instructions For Authors</h3>
<ol>
<li>Upload your signature file below. The image size should typically be about 500 by 200px.</li>
<li>You can choose whether or not you want it to appear at the foot of each post by default: Signatures on Posts is ON</li>
<li>If you choose to have signatures on, then you can disable the signature on individual posts using a checkbox in the post editor</li>
<li>If you choose to have signatures off, then you can enable the signature on individual posts using a checkbox in the post editor</li>
<li>You can also use the short code [gc-signature] if you want to add a signature at a particular location on the page - for example if you want to add a PS after the signature.</li>
</ol>') );
		}
	}    
    
	function is_profile() {
		return defined('IS_PROFILE_PAGE') && IS_PROFILE_PAGE; 
	}
	
	function save_profile($user_id) {
		if ($this->is_profile()) $this->save_signature($user_id);
	}

	function save_user($user_id) {
		if ( ! $this->is_profile()) $this->save_signature($user_id);
	}

	function save_signature($user_id) {
		$key1 = Genesis_Club_Signature::SIGNATURE_URL_KEY;
  		foreach ($_FILES as $gcsig) {
      		$file = wp_handle_upload($gcsig, array( 'test_form' => false ));
       		if (isset($file['url'])) $_POST[$key1] = $file['url'];
      	}
		$old_val = get_user_option($key1, $user_id);		
		$new_val = isset($_POST[$key1]) ? $_POST[$key1] : '';
		if ($old_val != $new_val) update_usermeta( $user_id, $key1, $new_val);		
		
		$key2 = Genesis_Club_Signature::SIGNATURE_ON_POSTS_KEY;
		$old_val =  get_user_option($key2, $user_id);		
		$new_val = array_key_exists($key2,$_POST) ? $_POST[$key2] : '';
		if ($old_val != $new_val) update_usermeta( $user_id, $key2, $new_val );			

		$key3 = Genesis_Club_Signature::FIX_USER_NICENAME;
      	if (array_key_exists($key3,$_POST)) {
        	$this->maybe_fix_user_nicename($user_id);
      	}
	}	

   function maybe_fix_user_nicename ($user_id, $old_user_data = null) {
      global $wpdb;
      $user = new WP_User( $user_id );   
      $user_login = $user->user_login;
      $user_nicename = $user->user_nicename;  
      if ($user_nicename != $user_login) return; //nothing to fix
		$user_nicename = sanitize_user($user->display_name, true ) ;  //base the slug/nicename on display name
      $user_nicename = preg_replace( '|[ _@]|i', '', $user_nicename );
      $user_nicename = preg_replace( '|[^a-z\-]|i', '', strtolower($user_nicename ));

   	$user_nicename_check = $wpdb->get_var( $wpdb->prepare("SELECT ID FROM $wpdb->users WHERE user_nicename = %s OR user_login = %s LIMIT 1" , $user_nicename, $user_nicename));

      if ( $user_nicename_check ) {
         $suffix = 2;
         while ($user_nicename_check) {
			   $alt_user_nicename = $user_nicename . "-$suffix";
			   $user_nicename_check = $wpdb->get_var( $wpdb->prepare("SELECT ID FROM $wpdb->users WHERE user_nicename = %s OR user_login = %s LIMIT 1" , $alt_user_nicename, $alt_user_nicename));
			   $suffix++;
         }
         $user_nicename = $alt_user_nicename;
      }
      
      //update the user_nicename
      $compacted = compact( 'user_nicename' );
      $data = wp_unslash( $compacted );
      $ID = (int) $user_id;
		$wpdb->update( $wpdb->users, $data, compact( 'ID' ) );
      clean_user_cache( $user_id );  
   }

	function add_page_visibility($content, $post) {
      return $content . $this->fetch_page_visibility($post);
   }

	function fetch_page_visibility($post) {
		$meta_key = $this->signature->get_toggle_meta_key($post->post_type, $post->post_author);
		return $this->form_field(self::TOGGLE_SIGNATURE, self::TOGGLE_SIGNATURE, 
			__(strpos($meta_key, 'hide') !== FALSE ? 'Do not show the author signature on this page' : 'Show the author signature on this page'), 
			get_post_meta($post->ID, $meta_key, true),  'checkbox', array(), array(), 'br') ;
	}	

	function save_page_visibility($post_id) {
		$post_type = get_post_type( $post_id);
		$post_author = get_post_field( 'post_author', $post_id);	
		$key = self::TOGGLE_SIGNATURE;
		$meta_key = $this->signature->get_toggle_meta_key($post_type, $post_author);	
		update_post_meta( $post_id, $meta_key, array_key_exists($key, $_POST) ? $_POST[$key] : false);
	}


	function show_authors_panel($user) {
		$key1 = Genesis_Club_Signature::SIGNATURE_URL_KEY;
		$key2 = Genesis_Club_Signature::SIGNATURE_ON_POSTS_KEY;			
		$key3 = Genesis_Club_Signature::FIX_USER_NICENAME;
		$sig_url = get_user_option($key1, $user->ID);
		$show_sig = get_user_option($key2, $user->ID)   ? 'checked="checked"' : '';
		$sig_img = empty($sig_url) ? '' : sprintf('<img alt="Author Signature" src="%1$s" /><br/>',$sig_url);	
		$accept = 'image/' . '*';	
		print <<< SIGNATURE_PANEL
<h3 id="genesis-club-signature">Signature Settings</h3>
<table class="form-table">
<tr>
	<th><label for="gcsig">Author Signature</label></th>
	<td>{$sig_img}
	<input type="text" id="{$key1}" name="{$key1}" size="80" value="{$sig_url}" /><br/>
	<input id="gcsig" name="gcsig" type="file" accept="{$accept}" size="80"  value="{$sig_url}" /><br/>
	<span class="description">Enter the signature URL or upload a new image file of your signature with approximate dimensions of say, 400px by 200px.</span></td>
</tr>
<tr>
	<th><label for="{$key2}">Show Signature On Posts</label></th>
	<td><input id="{$key2}" name="{$key2}" type="checkbox" class="valinp" {$show_sig} value="1" /><br/>
	<span class="description">Check this box to have your signature appear at the foot of all posts by default. You can override this setting on a post by post basis.</span></td>
</tr>
SIGNATURE_PANEL;
   $nicename_fix = <<< NICENAME_FIX
<tr>
	<th><label for="{$key3}">Fix User Author URL</label></th>
	<td><input id="{$key3}" name="{$key3}" type="checkbox" class="valinp" value="1" /><br/>
	<span class="description">This option appears because your private user login and the public URL of your author page are the same and hence hackers will try using brute force login attempts using the correct user login. Check this box to make the author URL different from your user login and hence render the login attempts ineffective as they will be user the wrong user login when trying to hack your site.</span></td>
</tr>
NICENAME_FIX;
   if ($user->user_nicename == $user->user_login) print $nicename_fix;
   print ('</table>');
	}	
	
	function intro_panel() {
		$url = admin_url('profile.php#genesis-club-signature');
		print <<< INTRO
<p class="attention">There are no settings on this page.</p>
<p class="attention">However, a link is provided to where you need to go to add a signature, 
as well as some useful tips.</p>
<p class="bigger">To create or edit your signature go to your <a href="{$url}">Profile page</a> and upload a signature.</p>
INTRO;
	}

	function help_panel() {
		$screenshot = plugins_url('images/signature-settings.jpg',dirname(__FILE__));
		return <<< HELP
<p>On the <em>User Profile page</em> look for the section called <em>Signature Settings</em>:</p>
<p><img class="dashed-border" alt="User Profile signature settings" src="{$screenshot}"></p>
<p>Every author on your site can upload their own signature of whatever size they prefer.</p>
<p>And, of course, for reasons of security, rather than use your own real signature I suggest you create a stylized one at <a href="http://www.mylivesignature.com/">My Live Signature</a>.</p>
<p>HINT: Save your signature in the PNG format so it has a transparent background and hence looks fine on any of your sites.</p>
<p>You can find more detailed instructions on creating and using author signatures <a href="http://www.diywebmastery.com/3098/how-to-add-an-author-signature-at-the-foot-of-a-post" target="_blank">here</a>.</p>
HELP;
	}	


	function faq_panel() {
		return <<< FAQ
<h4>Where Does The Author Signature Appear?</h4>
<p>At the foot of your post.</p>
<h4>Does A Signature Appear On All Posts Automatically??</h4>
<p>That depends of your settings! You can have the signature appear by default at the foot of all your posts, and switch the featured off on individual posts, or 
alternatively you can have the feature inactive by default and then only enable it on individual posts</p>
<h4>What If I Want A P.S. After the Signature?</h4> 
<p>Disable the automatic signature on that page and use the [<i>genesis_club_signature</i>] shortcode.</p>
FAQ;
	}	

	function example_panel() {
		$usersig = $this->signature->get_author_signature(get_current_user_id());
		return sprintf('<p><img src="%1$s" alt="Author Signature"/></p>', 
			!empty($usersig) ? $usersig :  plugins_url('images/signature-example.png',dirname(__FILE__)));		
	}	
	

	function signature_panel($post,$metabox) {
       print $this->tabbed_metabox( $metabox['id'], array(
         'Help' => $this->help_panel(),
         'FAQ' => $this->faq_panel(),
         'Example Signature' => $this->example_panel($post)
		));
	}		

}
