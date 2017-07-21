<?php
class Genesis_Club_Fonts extends Genesis_Club_Module {
	const GOOGLE_FONTS_API_KEY = 'AIzaSyCU14DFkcbylw3t2kVmk89PblbmV5GPR7E';
	const OPTION_NAME = 'fonts';
	const ALL_FONTS_OPTION_NAME = 'genesis_club_fonts';

	protected $effects = array('anaglyph','brick-sign','canvas-print','crackle','decaying','destruction','distressed','distressed-wood',
         'fire','fire-animation','fragile','grass','ice','mitosis','neon','outline','putting-green','scuffed-steel', 'shadow-multiple',
         'splintered','static','stonewash','3d', '3d-float','vintage', 'wallpaper');

  	protected $subsets = array('latin','latin-ext','menu','arabic','bengali','cyrillic','cyrillic-ext','greek','greek-ext','hindi','khmer','korean','lao','tamil','vietnamese');

	protected $defaults  = array(
		'families' => array(),
		'subsets' => array('latin'),
		'effects' => array(),
		'fv' => array()
	);

	function get_defaults() {
       return $this->defaults; 
    }
   	
	function get_options_name() {
       return self::OPTION_NAME; 
	}		

	function init() {
		if (!is_admin()) {
			add_action('wp', array($this,'prepare'));
   }
   }

	function get_effects() {
    	return $this->effects;		
   }

	function get_subsets() {
    	return $this->subsets;		
    }

	function get_all_fonts() {
    	return get_option(self::ALL_FONTS_OPTION_NAME, array());
    }

	function get_default($option_name) {
      return array_key_exists($option_name, $this->defaults) ?  $this->defaults[$option_name] : false;
   }


   function family_exists($font_id) {
      return array_key_exists($font_id, $this->get_families());
   }
      
   function get_families() {
      if ($families = $this->get_option('families'))
         return (array)$families;
      else
         return array();
   }

   function save_families($families) {
      $options = $this->get_options(false);
      array_walk($families, array($this, 'add_fv'));
      ksort($families);
      $options['families'] = $families;
      return $this->save_options($options);
   }

   function delete_families($font_ids) {
		$font_ids = (array)$font_ids;
		$deleted= 0;
	  $families = $this->get_families();
		foreach ($font_ids as $font_id) {
			$font_id = strtolower(trim($font_id));
         	if (array_key_exists($font_id, $families)) {
			   unset($families[$font_id]);
            	$deleted++;            
         	}
      }
		if($deleted)
		  $this->save_families($families);

		return $deleted;
    }

	 function add_fv(&$item, $key) {
      $variants = isset($item['variants']) ? (array)$item['variants'] : false;
      if (!$variants || ((count($variants) == 1) && ('regular' == $variants[0])))
         $item['fv'] =  urlencode($item['family']);
      else
         $item['fv'] = urlencode($item['family']) . ':' . implode(',', $variants);      
   }

	public function prepare() {
      add_action('wp_enqueue_scripts', array($this, 'enqueue_styles'));
	}

	public function enqueue_styles() {
      if ($families = $this->get_option('families')) {
         $fv = array();
         foreach ($families as $key => $values) $fv[] = $values['fv'];                 
         $args['family'] = implode('%7C', $fv);
         
         if ($subsets = $this->get_option('subsets')) 
            $args['subset'] = implode('%2C', (array)$subsets);

         if ($effects = $this->get_option('effects')) 
            $args['effect'] = implode('%7C', (array)$effects);
            
         $url = add_query_arg($args, sprintf('http%1$s://fonts.googleapis.com/css', is_ssl() ? 's' : '' ));

         wp_enqueue_style('genesis-club-fonts', $url, array(), null);
      }
	}

}