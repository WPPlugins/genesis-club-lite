<?php
class Genesis_Club_Seo_Redirects_Admin extends Genesis_Club_Seo_Admin {
   const CODE = 'genesis-club-redirects'; //prefix ID of CSS elements
    const REDIRECT_KEY = '_genesis_club_redirect'; //field name - any redirects held in options for performance reasons
    const YOAST_REDIRECT_METAKEY = '_yoast_wpseo_redirect'; //field name and post meta key

	private $redirect_tips = array(
        '_genesis_club_redirect_url' => array('heading' => 'Redirect URL', 'tip' => 'Specify the full URL where you this page to be redirected'),
		'_genesis_club_redirect_status' => array('heading' => 'Redirect Status', 'tip' => 'Choose the redirect status'),
	);
	
	private $tips = array(
		'copy_seo_redirects' => array('heading' => 'Synchronize SEO Redirects', 'tip' => 'Click the button to synchronize Redirects between Yoast SEO and Genesis Club.')
	);

	function init() {
	    $this->seo = $this->plugin->get_module('seo'); 
		add_action('do_meta_boxes', array( $this, 'do_meta_boxes'), 20, 2 );
		add_action('save_post', array( $this, 'save_post'));
		add_action('load-edit-tags.php', array($this, 'load_archive_page'));	
		add_action('load-term.php', array($this, 'load_archive_page'));	
		add_action('edit_term', array($this, 'save_archive'), 10, 2 );	
	}

	function load_archive_page() {
      if (isset($_GET['post_type'])
      && $this->plugin->is_post_type_enabled($_GET['post_type'])) {
            add_filter(Genesis_Club_Dashboard::ARCHIVE_HOOK_ID, array($this, 'add_archive_panel'), 10, 3 );	
         $this->set_tooltips($this->redirect_tips);
            }
			}	

	function save_archive($term_id, $tt_id) {
	   $key = self::REDIRECT_KEY;
		return isset( $_POST[$key] ) ?
			$this->seo->save_redirects('terms', $term_id, (array) $_POST[$key]) : false;
	}	

	function save_post($post_id) {
        if ( wp_is_post_revision( $post_id ) ) return;	
	   $key = self::REDIRECT_KEY;
		return isset( $_POST[$key] ) ?  
			$this->seo->save_redirects('posts', $post_id, (array) $_POST[$key]) : false;
	}


	function do_meta_boxes( $post_type, $context) {
		if ( $this->is_metabox_active($post_type, $context)) {
         add_filter( 'genesis_club_post_settings', array($this, 'add_post_panel'), 10, 2);	//add to plugin metabox		    	
		}
	}

	function add_post_panel($content, $post) {
		return $content + array ( 'Redirect' => $this->redirect_panel($post)) ;
	}	 
 
	function redirect_panel($post) {
	   $key = self::REDIRECT_KEY;
	   $redirect = $this->seo->get_redirect('posts', $post->ID);
		$this->set_tooltips($this->redirect_tips);
		return sprintf ('<div class="diy-wrap">%1$s%2$s<p class="meta-options"><input type="hidden" name="genesis_club_redirect" value="1" /></p></div>',
			$this->form_field($key.'_url', $key.'[url]', false, $redirect['url'], 'text', array(), array('size' => 50, 'class' => 'large-text')),
			$this->form_field($key.'_status', $key.'[status]', false, $redirect['status'], 'radio', $this->seo->redirect_options()));      
    }

	function add_archive_panel($content, $term, $tt_id) {
		return $content + array ('Redirect' => $this->archive_redirect_panel($this->seo->get_redirect('terms', $term->term_id))) ;
	}	

	private function archive_redirect_panel($redirect) {
		return sprintf('<table class="form-table">%1$s%2$s</table>',	
		   $this->grouped_form_field($redirect, self::REDIRECT_KEY, 'url', 'text', array(), array('size' => 50, 'class' => 'large-text')),
		   $this->grouped_form_field($redirect, self::REDIRECT_KEY, 'status', 'radio', $this->seo->redirect_options())
			);
	}

	public function load_page() {
 		$message = isset($_POST['options_update']) ? $this->copy_meta() : ''; 
		if (isset($_REQUEST['redirects']) && isset($_REQUEST['act']) && ($_REQUEST['act'] == 'delete')) {
         $message = $this->delete_redirects($_REQUEST['redirects']);
		}
		$callback_params = array ('message' => $message);
		$this->add_meta_box('redirects-intro','Introduction', 'intro_panel', $callback_params);
		$this->add_meta_box('redirects-copy','SEO Redirects', 'copy_panel', $callback_params);
		if (isset($_REQUEST['redirects']) && isset($_REQUEST['act']) && ($_REQUEST['act'] == 'export')) {
         $this->add_meta_box('redirects-export','Export Redirects', 'export_panel', $callback_params);
		}
		$this->add_meta_box('news', 'Genesis Club News', 'news_panel',$callback_params, 'advanced');	
		$this->set_tooltips($this->tips);
	}

	private function copy_meta($reverse = false) {
		$yoast = $this->get_yoast_redirects();
		$gc = $this->get_gc_redirects();
		return $this->copy_post_meta($yoast, $gc, $reverse) ;
	}	

   private function get_yoast_redirects() {
         global $wpdb;
         $redirects = array();
			$select = sprintf('SELECT id, post_name, meta_value FROM %1$spostmeta pm, %1$sposts p WHERE p.id = pm.post_id AND meta_key = \'%2$s\' AND meta_value != \'\';',
				$wpdb->prefix, self::YOAST_REDIRECT_METAKEY); 
			$results = $wpdb->get_results($select);
			foreach ( $results as $result ) {
               $redirects['p'.$result->id] = array('post_id' => $result->id, 'slug' => get_permalink($result->id), 'urly' => $result->meta_value, 'status' => 301); 
			}
         return $redirects;
   }

   private function get_gc_redirects() {
         $redirects = array();
         $all_redirects = $this->seo->get_redirects();
         $post_redirects = (array) $all_redirects['posts'];
			foreach ( $post_redirects as $post_id => $redirect ) {
			   if (is_array($redirect)
			   && array_key_exists('url', $redirect)
			   && $redirect['url'])
               $redirects['p'.$post_id] = array('post_id' => $post_id, 'slug' => $this->get_post_slug($post_id), 'url' => $redirect['url'], 'status' => isset($redirect['status']) ? $redirect['status']: 301); 
			}
        $term_redirects = (array) $all_redirects['terms'];
        foreach ( $term_redirects as $term_id => $redirect ) {
            if (is_array($redirect)
			&& array_key_exists('url', $redirect)
			&& $redirect['url'])
               $redirects['t'.$term_id] = array('term_id' => $term_id, 'slug' => $this->get_term_slug($term_id), 'url' => $redirect['url'], 'status' => isset($redirect['status']) ? $redirect['status']: 301); 
		}

         return $redirects;
   }

   private function get_post_slug($post_id){
      $permalink = get_permalink($post_id);
      $url = parse_url($permalink);
      $ret = $url['path'];
      if (isset($url['query'])) $ret .= "?{$url[query]}";
      if (isset($url['fragment'])) $ret .= "#{$url[fragment]}";      
      return $ret;
   }

   private function get_term_slug($term_id){
      $permalink = get_term_link($term_id);
      $url = parse_url($permalink);
      $ret = $url['path'];
      if (isset($url['query'])) $ret .= "?{$url[query]}";
      if (isset($url['fragment'])) $ret .= "#{$url[fragment]}";      
      return $ret;
   }

   private function delete_redirects($target) {
         if ($target == 'gc') {
            $deletions = $this->seo->delete_redirects() ? 'All' : 'No';
         } else {
         global $wpdb;
         $table = sprintf('%1$spostmeta', $wpdb->prefix);
			   $deletions = $wpdb->delete($table, array('meta_key' => $target == 'gco' ? self::REDIRECT_KEY : self::YOAST_REDIRECT_METAKEY));
         }
         return $deletions ? sprintf('<div class="updated"><p>%1$s %2$s</p></div>', $deletions, __('redirects have been deleted',GENESIS_CLUB_DOMAIN)):'';
   }

	private function copy_post_meta($yoast, $gc, $reverse = false) {
		$updates = 0;	
      if ($reverse) {
         foreach ($gc as $redirect) {
            if (update_post_meta( $redirect['post_id'], self::YOAST_REDIRECT_METAKEY, $redirect['url'] )) $updates++;	            
         }
      } else {
         foreach ($yoast as $redirect) {
            if ($this->seo->save_redirects('posts', $redirect['post_id'], array('url' => $redirect['urly'], 'status' => 301)) ) $updates++;	            
         }         
      }
      return sprintf('%1$s %2$s', $updates, __('redirects updated', GENESIS_CLUB_DOMAIN));  
	}

	private function get_button_label($reverse = false) {
		return __($reverse ? 'Copy Redirects From Genesis Club to Yoast' : 'Copy Redirects From Yoast to Genesis Club' , GENESIS_CLUB_DOMAIN);	
	}

	public function copy_panel() {
	   $this_url = $_SERVER['REQUEST_URI'];
		$reverse = isset($_REQUEST['reverse']);	
		$yoast = $this->get_yoast_redirects();
		$gc = $this->get_gc_redirects();
		$all = array_replace_recursive($yoast, $gc);
      if (count($all) > 0){
         $matches = $copy = 0;
         print('<table class="seo-tags" summary="List of Yoast and Genesis Club Redirects"><thead><tr><th>Post URL</th><th>Yoast Redirect</th><th>Genesis Club Redirect</th><th>Match</th></tr></thead><tbody>');
         foreach ($all as $redirect) {
            $urly = isset($redirect['urly']) ? $redirect['urly']: '';
            $url = isset($redirect['url']) ? $redirect['url'] : '';
            $status = ($url && isset($redirect['status'])) ? (' ('.$redirect['status'].')') : '';
            $match = $urly==$url ? 'yes' : 'no';
            if ($match=='yes') 
               $matches++;
            elseif (($reverse && !empty($url)) || (!$reverse && !empty($urly)))
               $copy++;
            printf('<tr><td>%1$s</td><td>%2$s</td><td>%3$s%4$s</td><td><span class="dashicons dashicons-%5$s"></span></td></tr>', $redirect['slug'], $urly , $url, $status, $match);
         }	
         print('</tbody></table>');
         printf('<p>%1$s %2$s to copy</p>',  $copy == 0 ? 'No' : $copy , $copy==1 ? 'redirect' : 'redirects');
         print $this->fetch_form_field('reverse', isset($_GET['reverse'])? 1 : 0, 'hidden');
         if ($copy > 0) {
            print $this->submit_button($this->get_button_label());
         } else {
            print('<p>&nbsp;</p>');
         }
         printf ('<p>');
         if ((count($yoast) > 0) || (count($gc) > 0) ) printf('<h4>%1$s</h4>', __('Other Functions'));
         if (count($yoast) > 0) {
            $redirects = __('Yoast Redirects', GENESIS_CLUB_DOMAIN);
            printf ('<p><a class="button-primary" href="%1$s">%2$s %3$s</a><span class="note">%4$s</span></p>', 
               add_query_arg( array('act' => 'export', 'redirects' => 'yoast'), $this_url), __('Export', GENESIS_CLUB_DOMAIN), $redirects, 
               __('Export redirects if you plan to implement them in your .htaccess file', GENESIS_CLUB_DOMAIN));
            printf ('<p><a class="button-primary" href="%1$s" onclick="return genesis_club_confirm_delete(\'%3$s\')">%2$s %3$s</a><span class="note">%4$s</span></p>', 
               add_query_arg( array('act' => 'delete', 'redirects' => 'yoast'), $this_url), __('Delete', GENESIS_CLUB_DOMAIN), $redirects, 
               __('Delete redirects once you have copied them into your .htaccess file', GENESIS_CLUB_DOMAIN));
         }
         if (count($gc) > 0) {
            $redirects = __('Genesis Club Redirects', GENESIS_CLUB_DOMAIN);
            printf ('<p><a class="button-primary" href="%1$s">%2$s %3$s</a><span class="note">%4$s</span></p>', 
               add_query_arg( array('act' => 'export', 'redirects' => 'gc'), $this_url), __('Export', GENESIS_CLUB_DOMAIN), $redirects, 
               __('Export redirects if you plan to implement them in your .htaccess file', GENESIS_CLUB_DOMAIN));
            printf ('<p><a class="button-primary" href="%1$s" onclick="return genesis_club_confirm_delete(\'%3$s\')">%2$s %3$s</a><span class="note">%4$s</span></p>', 
               add_query_arg( array('act' => 'delete', 'redirects' => 'gc'), $this_url), __('Delete', GENESIS_CLUB_DOMAIN), $redirects,
               __('Delete redirects once you have copied them into your .htaccess file', GENESIS_CLUB_DOMAIN));
         }
         printf ('</p>');
      } else {
         _e('No SEO redirects found', GENESIS_CLUB_DOMAIN);   
      }
   } 

	public function export_panel() {
      printf('<p>%1$s</p>', __('Copy the redirects below and paste them at the top of your .htaccess file.'));
      $redirects = ($_REQUEST['redirects'] == 'gc') ? $this->get_gc_redirects() : $this->get_yoast_redirects() ;
      $url = ($_REQUEST['redirects'] == 'gc') ? 'url' : 'urly' ;
      $s = '';
      foreach ($redirects as $redirect) {
         $s .= sprintf('Redirect %1$s %2$s %3$s', $redirect['status'], $redirect['slug'], $redirect[$url] ) . "\n";
      }
      printf('<form><textarea rows="20" cols="80" readonly="readonly">%1$s</textarea></form>', $s);
	}
		   
 	public function intro_panel($post,$metabox){		
		print <<< INTRO_PANEL
<p>The 301 redirect facility was partially removed from the free version of the Yoast WordPress SEO plugin in v2.3 for reasons of performance and visibility. Redirects still take place for existing posts and pages, however you cannot add new redirects.</p>
<p>The feature below allows you to migrate any page 301 redirects from Yoast into this plugin. You can also export the redirects in a format suitable for inclusion in a <i>.htaccess</i> file and then delete them from the Yoast WordPress SEO configuration. </p>
<ol>
<li>Either COPY your 301 redirects from Yoast into this plugin or EXPORT them and add them to your .htaccess file </li>
<li>Delete the 301 redirects from the Yoast configuration</li>
</ol>
<p>IMPORTANT: Note that if you want to use Genesis Club redirects then you need to operate with this module, i.e. the SEO module, permanently enabled.</p>
INTRO_PANEL;
	}
		   
}
