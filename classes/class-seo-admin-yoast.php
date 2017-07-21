<?php
class Genesis_Club_Seo_Yoast_Admin extends Genesis_Club_Seo_Admin {
    const CODE = 'genesis-club-yoast'; //prefix ID of CSS elements
    const YOAST_FACEBOOK = 'yoast-facebook'; //count index
    
    private $opengraph_metakey = Genesis_Club_Plugin::FACEBOOK_OPENGRAPH_METAKEY; 
	private $seo_headings = array('Title','Description','NoIndex','NoFollow', 'OpenGraph');    
	private $home_seo_option = array(
		GENESIS_SEO_SETTINGS_FIELD => 'wpseo_titles');
	private $home_seo_keys = array(
		'home_doctitle' => 'title-home-wpseo',
		'home_description' => 'metadesc-home-wpseo');
	private $post_seo_keys = array(
		'_genesis_title' => '_yoast_wpseo_title',
		'_genesis_description' => '_yoast_wpseo_metadesc',
		'_genesis_noindex' => '_yoast_wpseo_meta-robots-noindex',
		'_genesis_nofollow' => '_yoast_wpseo_meta-robots-nofollow'
		);
	private $post_facebook_keys = array(
		'og_title' => '_yoast_wpseo_opengraph-title',
		'og_desc' => '_yoast_wpseo_opengraph-description',
		'og_image' => '_yoast_wpseo_opengraph-image',
		);
	private $user_seo_keys = array(
		'doctitle' => 'wpseo_title',
		'meta_description' => 'wpseo_metadesc'
		);		
	private $archive_seo_option = array(
		'genesis-term-meta' => 'wpseo_taxonomy_meta');
	private $archive_seo_keys = array(
		'doctitle' => 'wpseo_title',
		'description' => 'wpseo_desc',
		'noindex' => 'wpseo_noindex'
		);		
	private $tips = array(
		'copy_seo_meta' => array('heading' => 'Synchronize SEO Post Meta Tags', 'tip' => 'Synchronize Titles and Meta Descriptions between WordPress SEO and Genesis.')
	);

	public function load_page() {
		remove_all_filters( 'pre_option_' . GENESIS_SEO_SETTINGS_FIELD);		
 		$message = isset($_POST['options_update']) ? $this->copy_meta() : ''; 
		$callback_params = array ('message' => $message);
		$this->add_meta_box('yoast-intro','Introduction', 'intro_panel', $callback_params);
		$this->add_meta_box('yoast-copy','Copy SEO Data', 'copy_panel', $callback_params);
		$this->add_meta_box('news', 'Genesis Club News', 'news_panel',$callback_params, 'advanced');	
		$this->set_tooltips($this->tips);
	}

	private function count_home_meta() {
		$counts = array();	
		$options = $this->home_seo_option;
		$keys = $this->home_seo_keys;
		foreach ($options as $f => $t) {
			$from = get_option($f); 
			$to = get_option($t);
			foreach ($keys as $k => $v) {
				$counts[0][$k] = (is_array($from) && array_key_exists($k,$from) && !empty($from[$k])) ? '1' : '0';
				$counts[1][$v] = (is_array($to) && array_key_exists($v,$to) && !empty($to[$v])) ? '1' : '0';
			}
		}
  		return $counts;
	}

	private function count_tax_meta($taxonomy) {
		$counts = array();	
		$keys = $this->archive_seo_keys;
		foreach ($keys as $k => $v) {
			$counts[0][$k] = 0;
			$counts[1][$v] = 0;
		}
		$options = $this->archive_seo_option;
		foreach ($options as $f => $t) {
			$from = get_option($f); 
			$to = get_option($t);
			$terms = get_terms($taxonomy);			
			foreach ($terms as $term) { 
				$term_id = $term->term_id;
				if (is_array($from) && array_key_exists($term_id, $from) && is_array($from[$term_id]))
					foreach ($keys as $k => $v) $counts[0][$k] += 
						array_key_exists($k,$from[$term_id]) && (!empty($from[$term_id][$k]));
				if (is_array($to) 
				&& array_key_exists($taxonomy, $to) && is_array($to[$taxonomy])
				&& array_key_exists($term_id, $to[$taxonomy]) && is_array($to[$taxonomy][$term_id]))
					foreach ($keys as $k => $v) $counts[1][$v] += 
						array_key_exists($v,$to[$taxonomy][$term_id]) && (!empty($to[$taxonomy][$term_id][$v]));
			}
		}
  		return $counts;
	}

	private function count_user_meta() {
		global $wpdb;
		$keys = $this->user_seo_keys;
		$counts = array();
		foreach ($keys as $k => $v) {
			$counts[0][$k] = 0;
			$counts[1][$v] = 0;
			$select = sprintf('SELECT meta_key, COUNT(*) as tot FROM %3$susermeta WHERE meta_key IN (\'%1$s\',\'%2$s\')  AND meta_value != \'\' GROUP BY meta_key ;',
				$k, $v, $wpdb->prefix); 
			$results = $wpdb->get_results($select);
			foreach ( $results as $result )
				if (array_key_exists($result->meta_key, $counts[0])) 
					$counts[0][$result->meta_key] = $result->tot;
				else
					$counts[1][$result->meta_key] = $result->tot;
		}
  		return $counts;
	}

	private function count_facebook_meta($post_type) {
		global $wpdb;
		$keys = $this->post_facebook_keys;
        $yoast_key_list = "'" . implode("','",array_values($this->post_facebook_keys)) ."'";
		$counts = array();
		$counts[$this->opengraph_metakey] = 0;
		$counts[self::YOAST_FACEBOOK] = 0;
		$select = sprintf('SELECT CASE meta_key WHEN \'%3$s\' THEN \'%3$s\' ELSE \'%4$s\' END as meta_key, COUNT(DISTINCT post_ID) as tot FROM %1$spostmeta JOIN %1$sposts ON %1$sposts.ID = %1$spostmeta.post_ID WHERE post_status = \'publish\' AND post_type = \'%2$s\' AND meta_key IN (\'%3$s\',%5$s)  AND meta_value != \'\' GROUP BY CASE meta_key WHEN \'%3$s\' THEN \'%3$s\' ELSE \'%4$s\' END ;',
				$wpdb->prefix, $post_type, $this->opengraph_metakey, self::YOAST_FACEBOOK, $yoast_key_list ); 
		$results = $wpdb->get_results($select);
		foreach ( $results as $result )
            $counts[$result->meta_key] = $result->tot;
  		return $counts;
	}

	private function count_post_meta($post_type) {
		global $wpdb;
		$facebook_counts = $this->count_facebook_meta($post_type);
		$keys = $this->post_seo_keys;
		$counts = array();
		foreach ($keys as $k => $v) {
			$counts[0][$k] = 0;
			$counts[1][$v] = 0;
			$select = sprintf('SELECT meta_key, COUNT(*) as tot FROM %4$spostmeta JOIN %4$sposts ON %4$sposts.ID = %4$spostmeta.post_ID WHERE post_status = \'publish\' AND post_type = \'%1$s\' AND meta_key IN (\'%2$s\',\'%3$s\')  AND meta_value != \'\' GROUP BY meta_key ;',
				$post_type, $k, $v, $wpdb->prefix); 
			$results = $wpdb->get_results($select);
			foreach ( $results as $result )
				if (array_key_exists($result->meta_key, $counts[0])) 
					$counts[0][$result->meta_key] = $result->tot;
				else
					$counts[1][$result->meta_key] = $result->tot;
		}
		if (isset($facebook_counts[$this->opengraph_metakey])) $counts[0][$this->opengraph_metakey] = $facebook_counts[$this->opengraph_metakey];
		if (isset($facebook_counts[self::YOAST_FACEBOOK])) $counts[1][self::YOAST_FACEBOOK] = $facebook_counts[self::YOAST_FACEBOOK];
  		return $counts;
	}

	private function get_button_label() {
		return __($this->is_yoast_installed() ?  'Copy SEO From Genesis to Yoast' : 'Copy SEO From Yoast to Genesis', GENESIS_CLUB_DOMAIN);	
	}

	private function print_row ($title, $meta) {
		$genesis = array_values($meta[0]); 
		$yoast = array_values($meta[1]);	
		$order = $this->is_yoast_installed() ? '<td><i>%1$s</i> / <b>%2$s</b></td>' : '<td><i>%2$s</i> / <b>%1$s</b></td>';
		printf('<tr><th>%1$s</th>', $title);
		for ($i=0; $i < count($this->seo_headings); $i++)
			printf( $order, $i < count($genesis) ? $genesis[$i] : '-', $i < count($yoast) ? $yoast[$i] : '-');
		print('</tr>');		
	}

 	public function intro_panel($post,$metabox){	
		$message = $metabox['args']['message'];	 	
		print <<< INTRO_PANEL
<p>You do not <em>need</em> to use an SEO plugin as Genesis comes with its own SEO capability built-in. 
However, due to its extra features you might want to install and activate Yoast’s WordPress SEO plugin. If you do this 
Genesis will suppress its SEO fields to avoid confusion.</p>
<p>Genesis and WordPress SEO use different records in the WordPress database.  This means if you swap from 
Genesis SEO to Yoast&#8217s WordPress SEO, or vice-versa, and you have a number of existing posts, pages, categories and tags where you have
set up SEO, then you will lose the benefit of your SEO settings when you make the switch.</p>  
<p>Here, you can move all your SEO settings at the click of a button.</p>
<p class="attention">Use this function with caution as it will permanently over-write any existing values.</p>
<p>Please note that this feature cannot be used to delete SEO data. Only non-blank titles and meta descriptions are copied between
Genesis and WordPress SEO or vice versa.</p>
{$message}
INTRO_PANEL;
	}

	public function copy_panel() {
		$taxonomies=get_taxonomies('','names'); 
 		foreach ($taxonomies as $t) if (('nav_menu'==$t) || ('link_category'==$t) || ('post_format'==$t)) unset ($taxonomies[$t]);
 		if (defined('GENESIS_SEO_SETTINGS_FIELD')) remove_filter( 'pre_option_' . GENESIS_SEO_SETTINGS_FIELD, '__return_empty_array' );		
	   $from = $this->is_yoast_installed() ? 'Genesis' : 'Yoast' ; 
	   $to = $this->is_yoast_installed() ? 'Yoast' : 'Genesis' ; 

		printf('<h4>Key: <i>%1$s Count</i> / <b>%2$s Count</b> </h4>', $from, $to);
		print('<table class="seo-tags" summary="Count of Genesis and Yoast SEO tags"><thead><tr><th>Items</th>');
		foreach ($this->seo_headings as $heading) printf('<th>%1$s</th>',$heading);
		print('</tr></thead><tbody>');
		$this->print_row('Home Page', $this->count_home_meta());
		$this->print_row('Page', $this->count_post_meta('page'));
		$this->print_row('Post', $this->count_post_meta('post'));
		$custom_post_types = get_post_types(array('public' => true, '_builtin' => false));	
		foreach ($custom_post_types as $post_type) {
			$this->print_row(ucwords($post_type), $this->count_post_meta($post_type));
		}
		$this->print_row('Author Archive', $this->count_user_meta());
		foreach ($taxonomies as $taxonomy ) $this->print_row($taxonomy .' Archive', $this->count_tax_meta($taxonomy)); 	
		print('</tbody></table>');
		print $this->fetch_form_field('copy_seo_meta',  $this->submit_button($this->get_button_label()), 'button', array(), array(), 'p');
	} 

	private function copy_archive_meta() {
		$updated = false;
		$taxonomies=get_taxonomies('','names'); 
		$options = $this->is_yoast_installed() ?  $this->archive_seo_option : array_flip($this->archive_seo_option);
		$keys = $this->is_yoast_installed() ? $this->archive_seo_keys: array_flip($this->archive_seo_keys) ;
		foreach ($options as $f => $t) {
			$from = get_option($f); 
			$to = get_option($t); 
			if (is_array($from)) foreach ($taxonomies as $taxonomy ) {		
				$terms = get_terms($taxonomy);	
			 	foreach ($terms as $term) { 
			 		$term_id = $term->term_id;
					if ($this->is_yoast_installed()) {
						if (array_key_exists($term_id, $from) && is_array($from[$term_id])) 
							foreach ($keys as $k => $v) 
								if (array_key_exists($k, $from[$term_id]) && !empty($from[$term_id][$k]))
									$to[$taxonomy][$term_id][$v] = $from[$term_id][$k];										
					} else {  
						if (array_key_exists($taxonomy, $from) 
						&& is_array($from[$taxonomy])
						&& array_key_exists($term_id, $from[$taxonomy])
						&& is_array($from[$taxonomy][$term_id]))
							foreach ($keys as $k => $v) 
								if (array_key_exists($k, $from[$taxonomy][$term_id])
								&& !empty($from[$taxonomy][$term_id][$k]))
									$to[$term_id][$v] = $from[$taxonomy][$term_id][$k];			
					}
				}
			}
			$updated = update_option($t,$to);
		}
  		return $updated ? 'Updated archive page SEO titles, descriptions, headlines and introductions. ' : '';
	}

	private function copy_home_meta() {
		$updated = false;
		$reverse = ! $this->is_yoast_installed() ; 
		$options = $reverse ? array_flip($this->home_seo_option) : $this->home_seo_option;
		$keys = $reverse ? array_flip($this->home_seo_keys) : $this->home_seo_keys;		
		foreach ($options as $f => $t) {
			$from = get_option($f); 
			$to = get_option($t); ;
			foreach ($keys as $k => $v) $to[$v] = $from[$k];
			$updated = update_option($t,$to);
		}
  		return $updated ? 'Updated Home page SEO titles and descriptions. ' : '';
	}

	private function copy_user_meta() {
		global $wpdb;
		$updated = false;
		$reverse = ! $this->is_yoast_installed() ; 
		$keys = $reverse ? array_flip($this->user_seo_keys) : $this->user_seo_keys;
		foreach ($keys as $k => $v) {
			$update = sprintf('UPDATE %3$susermeta AS t2 INNER JOIN (SELECT user_id, meta_key, meta_value FROM %3$susermeta WHERE meta_key = \'%1$s\') AS t1 SET t2.meta_value = t1.meta_value WHERE t1.user_id = t2.user_id and t2.meta_key =\'%2$s\' and t1.meta_key = \'%1$s\' AND t1.meta_value != \'\' AND t1.meta_value != t2.meta_value;',
				$k, $v, $wpdb->prefix); 
			if ( $wpdb->query($update)) $updated = true;
			$insert = sprintf('INSERT %3$susermeta SELECT null, user_id, \'%2$s\', meta_value FROM %3$susermeta WHERE meta_key = \'%1$s\' AND meta_value != \'\' AND user_id NOT IN (SELECT user_id FROM %3$susermeta WHERE meta_key = \'%2$s\');',
				$k, $v, $wpdb->prefix); 
			if ( $wpdb->query($insert)) $updated = true;
		}
  		return $updated ? 'Updated author SEO titles and descriptions. ' : '';
	}

	private function copy_post_meta() {
		global $wpdb;
		$reverse = ! $this->is_yoast_installed() ; 		
		$updated = false;
		$keys = $reverse ? array_flip($this->post_seo_keys) : $this->post_seo_keys;
		foreach ($keys as $k => $v) {
			$update = sprintf('UPDATE %3$spostmeta AS t2 INNER JOIN (SELECT post_id, meta_key, meta_value FROM %3$spostmeta WHERE meta_key = \'%1$s\') AS t1 SET t2.meta_value = t1.meta_value WHERE t1.post_id = t2.post_id and t2.meta_key =\'%2$s\' and t1.meta_key = \'%1$s\' AND t1.meta_value != \'\' AND t1.meta_value != t2.meta_value;',
				$k, $v, $wpdb->prefix); 
			if ($wpdb->query($update)) $updated = true;			
			$insert = sprintf('INSERT %3$spostmeta SELECT null, post_id, \'%2$s\', meta_value FROM %3$spostmeta WHERE meta_key = \'%1$s\' AND meta_value != \'\' AND post_id NOT IN (SELECT post_id FROM %3$spostmeta WHERE meta_key = \'%2$s\');',
				$k, $v, $wpdb->prefix); 
			if ($wpdb->query($insert)) $updated = true;
		}
  		return $updated ? 'Updated post SEO titles and descriptions. ' : '';
	}

	private function copy_facebook_meta() {
		global $wpdb;
		$reverse = ! $this->is_yoast_installed() ; 		
		$updated = false;
		if ($reverse) {
            $post_meta = array();
            $genesis_keys = array_flip($this->post_facebook_keys);
            $yoast_key_list = "'" . implode("','",array_values($this->post_facebook_keys)) ."'";
            $select = sprintf('SELECT %1$sposts.ID, meta_key, meta_value FROM %1$spostmeta JOIN %1$sposts ON %1$sposts.ID = %1$spostmeta.post_ID WHERE post_status = \'publish\' AND meta_key IN (%2$s) AND meta_value != \'\' ;',
				$wpdb->prefix, $yoast_key_list); 
            $results = $wpdb->get_results($select);
			foreach ( $results as $result ) $post_meta[$result->ID][$genesis_keys[$result->meta_key]] = $result->meta_value;
            foreach ( $post_meta as $key => $values) {
                update_post_meta ($key, $this->opengraph_metakey, (array) $values) ;
            }
		} else {
             $select = sprintf('SELECT %1$sposts.ID, meta_key, meta_value FROM %1$spostmeta JOIN %1$sposts ON %1$sposts.ID = %1$spostmeta.post_ID WHERE post_status = \'publish\' AND meta_key = \'%2$s\' AND meta_value != \'\' ;',
				$wpdb->prefix, $this->opengraph_metakey);            
            $results = $wpdb->get_results($select);
			foreach ( $results as $result ) 
                foreach ($this->post_facebook_keys as $k => $v) 
                    if (isset($result->meta_value[$k])) 
                        update_post_meta ($result->ID, $v, $result->meta_value[$k]);
		}
  		return $updated ? 'Updated post Facebook titles, descriptions and images. ' : '';
	}


	private function copy_meta() {
		$message = '';
		$message .= $this->copy_home_meta() ;
		$message .= $this->copy_post_meta() ;
		$message .= $this->copy_user_meta() ;
		$message .= $this->copy_archive_meta() ;
		$message .= $this->copy_facebook_meta() ;
		$subject = $this->is_yoast_installed() ? 'Migrate Genesis SEO To Yoast SEO:' : 'Migrate Yoast SEO To Genesis SEO:';
		if (empty($message)) 
			$this->add_admin_notice($subject, 'No updates took place', true);	         
		else
			$this->add_admin_notice($subject, $message);			
		return $message;
	}	
		   
}
?>