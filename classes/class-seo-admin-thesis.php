<?php
class Genesis_Club_Seo_Thesis_Admin extends Genesis_Club_Seo_Admin {

	private $seo_headings = array('Title','Description','NoIndex','NoFollow','NoArchive','Headline','Intro');
	
	private $home_seo_option = GENESIS_SEO_SETTINGS_FIELD;

	private $home_seo_keys = array(
		'home_doctitle' => 'title',
		'home_description' => 'meta->description',
		'home_noindex' => 'meta->robots->noindex',
		'home_nofollow' => 'meta->robots->nofollow',
		'home_noarchive' => 'meta->robots->noarchive'		
		);
		
	private $post_seo_keys = array(
		'_genesis_title' => 'thesis_title',
		'_genesis_description' => 'thesis_description',
		'_genesis_noindex' => 'thesis_robots->noindex',
		'_genesis_nofollow' => 'thesis_robots->nofollow',
		'_genesis_noarchive' => 'thesis_robots->noarchive'
		);	

	private $archive_seo_option = 'genesis-term-meta';
		
	private $archive_seo_keys = array(
		'doctitle' => 'title',
		'description' => 'description',
		'noindex' => 'noindex',
		'nofollow' => 'nofollow',
		'noarchive' => 'noarchive',
		'headline' => 'headline',
		'intro_text' => 'intro'
		);		

	private $tips = array(
		'copy_seo_meta' => array(
			'heading' => 'Migrate Thesis SEO', 
			'tip' => 'Migrate Titles, Meta Descriptions and Archive content between Thesis and Genesis.')
	);
	
	private function get_terms_table_name() {
		global $wpdb;
		return $wpdb->prefix.'thesis_terms';
	}
	
	public function load_page() {
		remove_all_filters( 'pre_option_' . GENESIS_SEO_SETTINGS_FIELD);	
		if ($this->check_for_thesis()) {
			$this->add_meta_box('thesis-intro', 'Introduction', 'intro_panel');
			$this->add_meta_box('thesis-copy','Migrate SEO From Thesis To Genesis',  'copy_panel');
		} else {
			$this->add_meta_box('thesis-intro', 'Thesis Not Present', 'missing_panel');
		}
		$this->add_meta_box('news', 'Genesis Club News', 'news_panel', null, 'advanced');	
		$this->set_tooltips($this->tips);
	}
	
	public function check_for_thesis() {
		global $wpdb;
		$table_name = $this->get_terms_table_name();
		return $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;
	}

	private function fetch_terms_from_db() {
		global $wpdb;
		$table_name = $this->get_terms_table_name();
		$sql = "select * from $table_name";
		if ( false === $wpdb->query($sql) ) 
			return new WP_Error( 'fetch_terms_failed', __( 'Could not fetch terms from the database' ), $wpdb->last_error );
		else {    
			if ($wpdb->num_rows > 0) 
				return $wpdb->last_result;
			else
			   	return false;
		}
	}

	private function convert_to_class($class, $object) {
  		return unserialize(preg_replace('/^O:\d+:"[^"]++"/', 'O:' . strlen($class) . ':"' . $class . '"', serialize($object)));
	}

	private function get_thesis_home_meta() {
		$meta = array(); //flatten hierachical object to simple array
		$options = $this->convert_to_class('stdClass',get_option('thesis_options'));
		$head= $options->home['head'];
		$head = json_decode(json_encode($head), FALSE); //turn into an object
		foreach ($this->home_seo_keys as $genesis => $thesis) {
			eval("\$meta[\$genesis] = \$head->$thesis;");
		}	
		return $meta;
	}

	private function get_thesis_term_meta() {
		$meta = array();
		if ($terms = $this->fetch_terms_from_db())  foreach ($terms as $term) {
			$term_meta = array();
			$term_id = $term->term_id;
			$term_meta['title'] = $term->title;
			$term_meta['description'] = $term->description;
			$term_meta['keywords'] = $term->keywords;
			$term_meta['headline'] = $term->headline;
			$term_meta['intro'] = $term->content;
			if ($robots = unserialize($term->robots)) {
				$term_meta['noindex'] = $robots['noindex'];		
				$term_meta['nofollow'] = $robots['nofollow'];
				$term_meta['noarchive'] = $robots['noarchive'];
			}
			$term_meta['layout'] = '';
			$meta[$term_id] = $term_meta;	
		}
		return $meta;
	}

	private function count_home_meta() {
		$counts = array();	
		$thesis = $this->get_thesis_home_meta(); //already converted to use Genesis Keys
		$genesis = get_option($this->home_seo_option);
		$keys = $this->home_seo_keys;
		$i=0;
		foreach ($keys as $k => $v) {
			$counts[0][$i] = (is_array($genesis) && array_key_exists($k,$genesis) && !empty($genesis[$k])) ? '1' : '0';
			$counts[1][$i] = (is_array($thesis) && array_key_exists($k,$thesis) && !empty($thesis[$k])) ? '1' : '0';
			$i++;
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
		$thesis = $this->get_thesis_term_meta(); 
		$genesis = get_option($this->archive_seo_option);
		$terms = get_terms($taxonomy);		
		foreach ($terms as $term) { 
			$term_id = $term->term_id;
			if (is_array($genesis) && array_key_exists($term_id, $genesis) && is_array($genesis[$term_id]))
				foreach ($keys as $k => $v) $counts[0][$k] += 
					array_key_exists($k,$genesis[$term_id]) && (!empty($genesis[$term_id][$k]));
			if (is_array($thesis) && array_key_exists($term_id, $thesis) && is_array($thesis[$term_id]))
				foreach ($keys as $k => $v) $counts[1][$v] += 
					array_key_exists($v,$thesis[$term_id]) && (!empty($thesis[$term_id][$v]));
		}
  		return $counts;
	}

	private function count_post_meta($post_type) {
		global $wpdb;
		$keys = $this->post_seo_keys;
		$counts = array();
		$i = 0;
		foreach ($keys as $genesis => $thesis) {
			$counts[0][$i] = 0;
			$counts[1][$i] = 0;
			if ($pos = strpos($thesis, '->')) {
				$key = substr($thesis,0,$pos);
				$thesis = substr($thesis,$pos+2);
				$select = sprintf('SELECT meta_key, COUNT(*) as tot FROM %3$spostmeta JOIN %3$sposts ON %3$sposts.ID = %3$spostmeta.post_ID WHERE post_status = \'publish\' AND post_type = \'%1$s\' AND meta_key = \'%2$s\'  AND meta_value != \'\' GROUP BY meta_key', $post_type, $genesis, $wpdb->prefix); 
				$select .= sprintf(' UNION SELECT \'%4$s\' as meta_key, COUNT(*) as tot FROM %5$spostmeta JOIN %5$sposts ON %5$sposts.ID = %5$spostmeta.post_ID WHERE post_status = \'publish\' AND post_type = \'%1$s\' AND meta_key = \'%3$s\' AND meta_value != \'\' AND (locate(\'%4$s\', meta_value) > 0) GROUP BY meta_key ;',$post_type, $genesis, $key, $thesis, $wpdb->prefix);
			} else {
				$select = sprintf('SELECT meta_key, COUNT(*) as tot FROM %4$spostmeta JOIN %4$sposts ON %4$sposts.ID = %4$spostmeta.post_ID WHERE post_status = \'publish\' AND post_type = \'%1$s\' AND meta_key IN (\'%2$s\',\'%3$s\')  AND meta_value != \'\' GROUP BY meta_key ;',
					$post_type, $genesis, $thesis, $wpdb->prefix); 
			}
			$results = $wpdb->get_results($select);
			foreach ( $results as $result )
				if ($result->meta_key == $genesis) 
					$counts[0][$i] = $result->tot;
				else
					$counts[1][$i] = $result->tot;
			$i++;
		}
  		return $counts;
	}


	private function get_button_label() {
		return __('Copy SEO From Thesis to Genesis',GENESIS_CLUB_DOMAIN);	
	}

	private function print_row ($title, $meta) {
		$genesis = array_values($meta[0]); $thesis = array_values($meta[1]);	
		printf('<tr><th>%1$s</th>', $title);
		for ($i=0; $i < count($this->seo_headings); $i++)
			printf( '<td><i>%1$s</i> / <b>%2$s</b></td>', 
				$i < count($thesis) ? $thesis[$i] : '-', 
				$i < count($genesis) ? $genesis[$i] : '-' );
		print('</tr>');		
	}

 	public function missing_panel($post,$metabox){	
 		$table_name = $this->get_terms_table_name();	
		print <<< MISSING_PANEL
<p>The table {$table_name} is missing from the database therefore this plugin cannot migrate the Thesis SEO data for archives.</p>
<p>For a full migration of Thesis SEO data, the plugin will be looking in this table, postmeta and options tables.</p>
<p>Please restore {$table_name} into the database in order that a migration of its contents can take place.</p>
MISSING_PANEL;
	}


 	public function intro_panel($post,$metabox){	
		print <<< INTRO_PANEL
<p><em>IMPORTANT: Migration is for Thesis 1.8.x and earlier. Not tested with Thesis 2.x</em></p>
<p>Genesis and Thesis use different records for SEO in the WordPress database.  This means if you swap theme from 
Thesis to Genesis, and you have a number of existing posts, pages, categories and tags where you have
set up SEO, then you will lose the benefit of your SEO settings when you make the switch.</p>  
<p>With Genesis Club Pro, you can move all your SEO settings at the click of a button.</p>
<p class="attention">This function must be used with caution as it will permanently over-write any existing values.</p>
<p>Please note that this feature cannot be used to delete SEO data. Only non-blank titles and meta descriptions are copied from Thesis to Genesis.</p>

INTRO_PANEL;
	}

	public function copy_panel() {
 		$this_url = $_SERVER['REQUEST_URI'];
		$button = $this->get_button_label();		
		$taxonomies=get_taxonomies('','names'); 
		print('<h4>Key: <i>Thesis Count</i>  / <b>Genesis Count</b> </h4>');
		print('<table class="seo-tags" summary="Count of Thesis and Genesis SEO tags"><thead><tr><th>Items</th>');
		foreach ($this->seo_headings as $heading) printf('<th>%1$s</th>',$heading);
		print('</tr></thead><tbody>');
		$this->print_row('Home Page', $this->count_home_meta());
		$this->print_row('Page', $this->count_post_meta('page'));
		$this->print_row('Post', $this->count_post_meta('post'));
		$custom_post_types = get_post_types(array('public' => true, '_builtin' => false));	
		foreach ($custom_post_types as $post_type) {
			$this->print_row(ucwords($post_type), $this->count_post_meta($post_type));
		}		
		foreach ($taxonomies as $taxonomy ) $this->print_row($taxonomy .' Archive', $this->count_tax_meta($taxonomy)); 	
		print('</tbody></table>');
		print('<p>Upgrade to <a class="genesis-club-button" href="http://www.genesisclubpro.com">Genesis Club Pro</a> for the Thesis to Genesis migration tool</p>');
	} 
	   
}
?>