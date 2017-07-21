<?php
class Genesis_Club_Social_Admin extends Genesis_Club_Admin {
    const TOGGLE_SOCIAL = 'genesis_club_toggle_social';    
    const OPENGRAPH_KEY = 'facebook'; 
    const OPENGRAPH_FOOTNOTE = '<p style="font-size:smaller">This feature allows you to customize the title, description and image used for this page on a Facebook post.</p><p style="font-size:smaller">However to use these values to override the default Facebook Opengraph tags, you must have enabled the Genesis Club SEO module and also have the <a hef="https://wordpress.org/plugins/autodescription/">SEO Framework plugin</a> installed and activated.</p>';
            
    protected $social;
    
	private $tips = array(
		'facebook_app_id' => array('heading' => 'Facebook App ID', 'tip' => 'Enter your Facebook App ID (15 characters) as found at https://developers.facebook.com/apps'),
		'facebook_locale' => array('heading' => 'Facebook Locale', 'tip' => 'Set the locale of the Facebook page plugin'),
		'facebook_featured_images' => array('heading' => 'Featured Images', 'tip' => 'Click to set up featured image sizes for use on Facebook. Two image sizes are created: one is 470 by 246px for use on Facebook, and the other is 200 by 105px for use alongside post excerpts on your archive pages.'),
		'facebook_all_images' => array('heading' => 'All Images', 'tip' => 'Click to set up the standard WordPress large, medium and thumbnail sizes to be appropriately sized for use on Facebook. Only do this when you are setting up the site and have decided that all your uploaded images will have a width to height ratio of 1.91:1.'),
   );

	private $opengraph_tips = array(
		'facebook_og_title' => array('heading' => 'Facebook Title', 'tip' => 'Default title to use on Facebook.'),
		'facebook_og_desc' => array('heading' => 'Facebook Description', 'tip' => 'Defalt description to use on Facebook.'),
		'facebook_og_image' => array('heading' => 'Facebook Image', 'tip' => 'URL of default image to use on Facebook. It is recommended you provide an image of size 470 by 246px.'),
	);

	function get_opengraph_tips() { return $this->opengraph_tips; }

	function get_tips() { return $this->tips + $this->opengraph_tips; }

	function init() {
        $this->social = $this->plugin->get_module('social');
		add_action('admin_menu',array($this, 'admin_menu'));
		add_action('do_meta_boxes', array( $this, 'do_meta_boxes'), 20, 2 );
		add_action('load-post.php', array($this, 'load_post_page'));	
		add_action('load-post-new.php', array($this, 'load_post_page'));	
		add_action('load-term.php', array($this, 'load_archive_page'));	
		add_action('load-edit-tags.php', array($this, 'load_archive_page'));
		add_action('save_post', array( $this, 'save_post'));
		add_action('edit_term', array($this, 'save_archive'), 10, 2 );	
	}

	function admin_menu() {
		$this->screen_id = add_submenu_page($this->get_parent_slug(), __('Social'), __('Social'), 'manage_options', 
			$this->get_slug(), array($this,'page_content'));
		add_action('load-'.$this->get_screen_id(), array($this, 'load_page'));
	}

	function page_content() {
		$title = $this->admin_heading('Genesis Club Social Settings');				
		$this->print_admin_form($title, __CLASS__, $this->get_keys());		 
	} 
	
	function load_page() {
 		if (isset($_POST['options_update'])) $this->save_social();	
    	$options = $this->social->get_options();
		$this->add_meta_box('intro', 'Intro',  'intro_panel');
		$this->add_meta_box('facebook', 'Facebook', 'facebook_panel', array ('options' => $options));
		do_action('genesis_club_social_settings'); //add in any extras here
		$this->add_meta_box('news', 'Genesis Club News', 'news_panel',null, 'advanced');
		$this->set_tooltips($this->get_tips());
		add_action ('admin_enqueue_scripts',array($this, 'enqueue_admin_styles'));
		add_action ('admin_enqueue_scripts',array($this, 'enqueue_metabox_scripts'));
		add_action ('admin_enqueue_scripts',array($this, 'enqueue_postbox_scripts'));
	}

 	function facebook_panel($post, $metabox) {
      $options = $metabox['args']['options'];
      print $this->tabbed_metabox( $metabox['id'], array (
         'Facebook Settings' => $this->facebook_settings_panel($options),
         'Facebook Image Sizes' => $this->image_sizes_panel($options),
            'Facebook OpenGraph' => $this->opengraph_panel($options) )
        );
    }	
 
	function facebook_settings_panel($options){
        $vals = $options['facebook'];
        return	 
         $this->facebook_field($vals, 'app_id',  'text', array(),  array('size' => 20)) .
         $this->facebook_field($vals, 'locale', 'select', $this->get_facebook_locales()) ;
	}

	function image_sizes_panel($options){
        $vals = $options['facebook'];
        return	 
         $this->facebook_field($vals, 'featured_images', 'checkbox') .
         $this->facebook_field($vals, 'all_images', 'checkbox');
	}
		
	function do_meta_boxes( $post_type, $context) {
		if ( $this->is_metabox_active($post_type, $context)) {
            add_filter( 'genesis_club_post_settings', array($this, 'add_post_panel'), 10, 2);	//add to plugin metabox		    	
		}
	}

	function load_post_page() {
		$this->set_tooltips($this->get_opengraph_tips());
	}

	function load_archive_page() {
        if (isset($_GET['post_type'])
        && $this->plugin->is_post_type_enabled($_GET['post_type'])) {
            add_filter( Genesis_Club_Dashboard::ARCHIVE_HOOK_ID, array($this, 'add_archive_panel'), 10, 3 );	
            $this->set_tooltips($this->get_opengraph_tips());         
        }
	}

	function add_post_panel($content, $post) {
		return $content + array ( 'Facebook OpenGraph' => $this->opengraph_post_panel($this->social->get_opengraph('posts', $post->ID))) ;
	}	 

	function add_archive_panel($content, $term, $tt_id) {
		return $content + array ('Social' => $this->opengraph_archive_panel($this->social->get_opengraph('terms', $term->term_id))) ;
	}	

	function facebook_field($facebook_values, $fld, $type, $options=array(), $args=array() ) {
	    if (!isset($facebook_values[$fld])) $facebook_values[$fld] = '';
        return $this->form_field('facebook_'.$fld, 'facebook['.$fld.']', false, $facebook_values[$fld], $type, $options, $args) ;
	}	

	function opengraph_panel($options) {
        $vals = $options['facebook'];
		return
		   $this->facebook_field($vals, 'og_title', 'text', array(), array('size' => 50, 'class' => 'large-text')) .
		   $this->facebook_field($vals, 'og_desc', 'text', array(), array('size' => 50, 'class' => 'large-text')) .
		   $this->facebook_field($vals, 'og_image', 'text', array(), array('size' => 50, 'class' => 'large-text')).
		   self::OPENGRAPH_FOOTNOTE;
    }

	private function opengraph_form_field($opengraph, $wrap, $fld, $type, $options = array(), $args = array()) {
        $key = self::OPENGRAPH_KEY;
		$id = $key.'_'.$fld;
		$name = $key.'['.$fld.']';	
		$value = isset($opengraph[$fld]) ? $opengraph[$fld] : '';
		return $this->form_field($id, $name, false, $value, $type, $options, $args, $wrap);
	}

	function opengraph_post_panel($opengraph) {
        $key = self::OPENGRAPH_KEY;
		return sprintf ('<div class="diy-wrap">%1$s%2$s%3$s%4$s<p class="meta-options"><input type="hidden" name="genesis_club_facebook" value="1" /></p></div>',
		   $this->opengraph_form_field($opengraph, 'div', 'og_title', 'text', array(), array('size' => 50, 'class' => 'large-text')),
		   $this->opengraph_form_field($opengraph, 'div', 'og_desc', 'text', array(), array('size' => 50, 'class' => 'large-text')),
		   $this->opengraph_form_field($opengraph, 'div', 'og_image', 'text', array(), array('size' => 50, 'class' => 'large-text')),
		   self::OPENGRAPH_FOOTNOTE);
    }

	private function opengraph_archive_panel($opengraph) {
        $key = self::OPENGRAPH_KEY;
		return sprintf('<table class="form-table">%1$s%2$s%3$s</table>%4$s',	
		   $this->opengraph_form_field($opengraph, 'tr', 'og_title', 'text', array(), array('size' => 50, 'class' => 'large-text')),
		   $this->opengraph_form_field($opengraph, 'tr', 'og_desc', 'text', array(), array('size' => 50, 'class' => 'large-text')),
		   $this->opengraph_form_field($opengraph, 'tr', 'og_image', 'text', array(), array('size' => 50, 'class' => 'large-text')),
		   self::OPENGRAPH_FOOTNOTE);
	}

	function save_archive($term_id, $tt_id) {
        $key = self::OPENGRAPH_KEY;
		return isset( $_POST[$key] ) ?
			$this->social->save_opengraph('terms', $term_id, (array) $_POST[$key]) : false;
	}	

	function save_post($post_id) {
        if ( wp_is_post_revision( $post_id ) ) return;
        $key = self::OPENGRAPH_KEY;
		return isset( $_POST[$key] ) ?  
			$this->social->save_opengraph('posts', $post_id, (array) $_POST[$key]) : false;
	}
  
	function save_social() {
        check_admin_referer(__CLASS__);
		$options = $this->social->get_options(false);
		$options['facebook'] = $_POST['facebook'];
 		$options = apply_filters('genesis_club_save_social_settings', $options); //add in any extras here
		$subject = 'Social settings'; 
		if ($saved = $this->social->save_options($options))
			$this->add_admin_notice ($subject, ' saved successfully.');
		else 
			$this->add_admin_notice ($subject, ' have not been changed.', true);
        $this->maybe_make_standard_image_sizes_facebook_ready();
	}	

    function upgrade() {
        $old_options = get_option($this->options->get_option_name());
        $options = $this->social->get_options(false);
        if (isset($old_options['display']['facebook_appid'])) $options['facebook']['appid'] = $old_options['display']['facebook_appid'];
        if (isset($old_options['display']['facebook_locale'])) $options['facebook']['locale'] = $old_options['display']['facebook_locale'];
        if (isset($old_options['display']['facebook_featured_images'])) $options['facebook']['featured_images'] = $old_options['display']['facebook_featured_images'];
        if (isset($old_options['display']['facebook_sized_images'])) $options['facebook']['all_images'] = $old_options['display']['facebook_sized_images'];
        return $this->social->save_options($options);
    }

	private function maybe_make_standard_image_sizes_facebook_ready() {
        $old_facebook = $this->options->get_option('facebook');
        $old_val = isset($old_facebook['all_images']) ? $old_facebook['all_images'] : false;        
        $new_facebook = isset($_POST['facebook']) ? $_POST['facebook'] : false;
        $new_val = isset($new_facebook['all_images']) ? $new_facebook['all_images'] : false;        

		if (!$old_val && $new_val) {
         $image_width = apply_filters('genesis-club-large-image-width', 960); //large size for post images
         $image_height = apply_filters('genesis-club-large-image-height',round($image_width / Genesis_Club_Social::FACEBOOK_IMAGE_SCALE_FACTOR));
         update_option( 'large_size_w', $image_width);
         update_option( 'large_size_h', $image_height); 
         $image_width = apply_filters('genesis-club-medium-image-width', 320); //medium size for post images
         $image_height = apply_filters('genesis-club-medium-image-height',round($image_width / Genesis_Club_Social::FACEBOOK_IMAGE_SCALE_FACTOR));
         update_option( 'medium_size_w', $image_width);
         update_option( 'medium_size_h', $image_height); 
         $image_width = apply_filters('genesis-club-thumbnail-image-width', 160); //thumbnail size for archives
         $image_height = apply_filters('genesis-club-thumbnail-image-height',round($image_width / Genesis_Club_Social::FACEBOOK_IMAGE_SCALE_FACTOR));
         update_option( 'thumbnail_size_w', $image_width);
         update_option( 'thumbnail_size_h', $image_height);
      }
	}
   
    private function get_facebook_locales() {
      return array (
'af_ZA' => 'Afrikaans',
'ak_GH' => 'Akan',
'am_ET' => 'Amharic',
'ar_AR' => 'Arabic',
'as_IN' => 'Assamese',
'ay_BO' => 'Aymara',
'az_AZ' => 'Azerbaijani',
'be_BY' => 'Belarusian',
'bg_BG' => 'Bulgarian',
'bn_IN' => 'Bengali',
'br_FR' => 'Breton',
'bs_BA' => 'Bosnian',
'ca_ES' => 'Catalan',
'cb_IQ' => 'Sorani Kurdish',
'ck_US' => 'Cherokee',
'co_FR' => 'Corsican',
'cs_CZ' => 'Czech',
'cx_PH' => 'Cebuano',
'cy_GB' => 'Welsh',
'da_DK' => 'Danish',
'de_DE' => 'German',
'el_GR' => 'Greek',
'en_GB' => 'English (UK)',
'en_IN' => 'English (India)',
'en_PI' => 'English (Pirate)',
'en_UD' => 'English (Upside Down)',
'en_US' => 'English (US)',
'eo_EO' => 'Esperanto',
'es_CL' => 'Spanish (Chile)',
'es_CO' => 'Spanish (Colombia)',
'es_ES' => 'Spanish (Spain)',
'es_LA' => 'Spanish',
'es_MX' => 'Spanish (Mexico)',
'es_VE' => 'Spanish (Venezuela)',
'et_EE' => 'Estonian',
'eu_ES' => 'Basque',
'fa_IR' => 'Persian',
'fb_LT' => 'Leet Speak',
'ff_NG' => 'Fulah',
'fi_FI' => 'Finnish',
'fo_FO' => 'Faroese',
'fr_CA' => 'French (Canada)',
'fr_FR' => 'French (France)',
'fy_NL' => 'Frisian',
'ga_IE' => 'Irish',
'gl_ES' => 'Galician',
'gn_PY' => 'Guarani',
'gu_IN' => 'Gujarati',
'gx_GR' => 'Classical Greek',
'ha_NG' => 'Hausa',
'he_IL' => 'Hebrew',
'hi_IN' => 'Hindi',
'hr_HR' => 'Croatian',
'hu_HU' => 'Hungarian',
'hy_AM' => 'Armenian',
'id_ID' => 'Indonesian',
'ig_NG' => 'Igbo',
'is_IS' => 'Icelandic',
'it_IT' => 'Italian',
'ja_JP' => 'Japanese',
'ja_KS' => 'Japanese (Kansai)',
'jv_ID' => 'Javanese',
'ka_GE' => 'Georgian',
'kk_KZ' => 'Kazakh',
'km_KH' => 'Khmer',
'kn_IN' => 'Kannada',
'ko_KR' => 'Korean',
'ku_TR' => 'Kurdish (Kurmanji)',
'la_VA' => 'Latin',
'lg_UG' => 'Ganda',
'li_NL' => 'Limburgish',
'ln_CD' => 'Lingala',
'lo_LA' => 'Lao',
'lt_LT' => 'Lithuanian',
'lv_LV' => 'Latvian',
'mg_MG' => 'Malagasy',
'mk_MK' => 'Macedonian',
'ml_IN' => 'Malayalam',
'mn_MN' => 'Mongolian',
'mr_IN' => 'Marathi',
'ms_MY' => 'Malay',
'mt_MT' => 'Maltese',
'my_MM' => 'Burmese',
'nb_NO' => 'Norwegian (bokmal)',
'nd_ZW' => 'Ndebele',
'ne_NP' => 'Nepali',
'nl_BE' => 'Dutch (Belgie)',
'nl_NL' => 'Dutch',
'nn_NO' => 'Norwegian (nynorsk)',
'ny_MW' => 'Chewa',
'or_IN' => 'Oriya',
'pa_IN' => 'Punjabi',
'pl_PL' => 'Polish',
'ps_AF' => 'Pashto',
'pt_BR' => 'Portuguese (Brazil)',
'pt_PT' => 'Portuguese (Portugal)',
'qu_PE' => 'Quechua',
'rm_CH' => 'Romansh',
'ro_RO' => 'Romanian',
'ru_RU' => 'Russian',
'rw_RW' => 'Kinyarwanda',
'sa_IN' => 'Sanskrit',
'sc_IT' => 'Sardinian',
'se_NO' => 'Northern Sami',
'si_LK' => 'Sinhala',
'sk_SK' => 'Slovak',
'sl_SI' => 'Slovenian',
'sn_ZW' => 'Shona',
'so_SO' => 'Somali',
'sq_AL' => 'Albanian',
'sr_RS' => 'Serbian',
'sv_SE' => 'Swedish',
'sw_KE' => 'Swahili',
'sy_SY' => 'Syriac',
'sz_PL' => 'Silesian',
'ta_IN' => 'Tamil',
'te_IN' => 'Telugu',
'tg_TJ' => 'Tajik',
'th_TH' => 'Thai',
'tk_TM' => 'Turkmen',
'tl_PH' => 'Filipino',
'tl_ST' => 'Klingon',
'tr_TR' => 'Turkish',
'tt_RU' => 'Tatar',
'tz_MA' => 'Tamazight',
'uk_UA' => 'Ukrainian',
'ur_PK' => 'Urdu',
'uz_UZ' => 'Uzbek',
'vi_VN' => 'Vietnamese',
'wo_SN' => 'Wolof',
'xh_ZA' => 'Xhosa',
'yi_DE' => 'Yiddish',
'yo_NG' => 'Yoruba',
'zh_CN' => 'Simplified Chinese (China)',
'zh_HK' => 'Traditional Chinese (Hong Kong)',
'zh_TW' => 'Traditional Chinese (Taiwan)',
'zu_ZA' => 'Zulu',
'zz_TR' => 'Zazaki',
);
	}

 	function intro_panel(){		
		print <<< INTRO_PANEL
<p>Here you can set up your Facebook AppID and Locale and also set up image sizes for your Featured Images that are suitable for posting on Facebook</p>
INTRO_PANEL;
	}
	
}
