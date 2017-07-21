<?php
if (!class_exists('Genesis_Club_Plugin')) { 
 class Genesis_Club_Plugin {

   const OPTIONS_NAME = 'genesis_club_options';
    const FACEBOOK_OPENGRAPH_METAKEY = '_genesis_club_facebook_opengraph'; //termmeta and postmeta used by SEO and Social modules

 	private $name = GENESIS_CLUB_FRIENDLY_NAME;
 	private $path = GENESIS_CLUB_PLUGIN_PATH;
 	private $slug = GENESIS_CLUB_PLUGIN_NAME;
 	private $version = GENESIS_CLUB_VERSION;

	private $modules = array(
		//'api' => array('class'=> 'Genesis_Club_API','heading' => 'API Keys', 'tip' => 'Check your Genesis Club Pro license is up to date and if a new version of the plugin is available.'),
		'accordion' => array('class'=> 'Genesis_Club_Accordion', 'heading' => 'Accordion', 'tip' => 'Create one or more accordions to display your frequently answered questions.'),
		'background' => array('class'=> 'Genesis_Club_Background','heading' => 'Background', 'tip' => 'Add stylish single images, slideshows or video backgrounds to specific sections or entire pages.'),
		'bar' => array('class'=> 'Genesis_Club_Bar','heading' => 'Bar', 'tip' => 'Add an animated top bar to grab visitors attention and promote for your calls to action.'),
		'calendar' => array('class'=> 'Genesis_Club_Calendar','heading' => 'Calendar', 'tip' => 'Add an Google Calendar that can show your events in your visitors local time.'),
		'display' => array('class'=> 'Genesis_Club_Display','heading' => 'Display', 'tip' => 'Extra widget areas and widgets, page specific hiding, custom login, and many more features.'),
		'fonts' => array('class'=> 'Genesis_Club_Fonts','heading' => 'Fonts', 'tip' => 'Add Google Fonts and Google Font Effects to add variety to your titles and landing pages.'),
		'footer' => array('class'=> 'Genesis_Club_Footer','heading' => 'Footer', 'tip' => 'Boost your site credibility using footer credits and trademark widgets.'),
		'icons' => array('class'=> 'Genesis_Club_Icons','heading' => 'Icons', 'tip' => 'Enhanced Simple Social Icons allowing different sizes for different sets of icons on the same page.'),
		'landing' => array('class'=> 'Genesis_Club_Landing','heading' => 'Landing Pages', 'tip' => 'Easy lead capture forms. Integrates with Aweber, MailChimp, SendReach and Infusionsoft.'),
		'media' => array('class'=> 'Genesis_Club_Media','heading' => 'Media', 'tip' => 'Must have features if you are <em>not</em> hosting all your media files in the Media Library.'),
		'menu' => array('class'=> 'Genesis_Club_Menu','heading' => 'Menus', 'tip' => 'Mobile responsive hamburger and search box to your primary, secondary and header right menus.'),
		'post' => array('class'=> 'Genesis_Club_Post','heading' => 'Post Widgets', 'tip' => 'Enhanced widgets for displaying post specific information and image galleries.'),
		'seo' => array('class'=> 'Genesis_Club_Seo','heading' => 'SEO ', 'tip' => 'Page redirects and SEO migration tools for Genesis, Yoast and The SEO Framework.'),
		'signature' => array('class'=> 'Genesis_Club_Signature','heading' => 'Signatures', 'tip' => 'Add an author signature to personalize your posts. Can be used with a PS or PPS.'),
		'slider' => array('class'=> 'Genesis_Club_Slider','heading' => 'Slider', 'tip' => 'Deliver your messages in animated words and images using a mobile responsive multi-layer slider.'),
		'social' => array('class'=> 'Genesis_Club_Social','heading' => 'Social', 'tip' => 'Facebook sharing (in Lite) and vertical and horizontal floating social counts panels (in Pro).'),
		);

	private $defaults = array( 
		'display_disabled' => false, 
		'accordion_disabled' => false, 'background_disabled' => false, 'bar_disabled' => false, 
		'calendar_disabled' => false, 'footer_disabled' => false, 'fonts_disabled' => false, 'icons_disabled' => false, 
		'landing_disabled' => false, 'media_disabled' => false, 'menu_disabled' => false, 'post_disabled' => false, 
		'seo_disabled' => false, 'signature_disabled' => false, 'slider_disabled' => false, 
		'social_disabled' => false, 'api_disabled' => false, 'custom_post_types' => array(), 
	); 
	private $admin_modules = array();
	private $public_modules = array();
	
   private $options;
   private $utils;
   private $news;

   static function get_instance() {
        static $instance = null;
        if (null === $instance) {
            // $instance = new static(); //use self instead of static to support 5.2 - not the same but okay as the plugin class is not extended 
            $instance = new self(); 
            register_activation_hook($instance->path, array($instance, 'activate'));            
            add_action('init', array($instance, 'init'),0);
            if (is_admin()) add_action('init', array($instance, 'admin_init'),0);

	}
        return $instance;
	}

   protected function __construct() {}

   private function __clone() {}

  	private function __wakeup() {}

	public function init() {
		$d = dirname(__FILE__) . '/';
		require_once ($d . 'class-options.php');
		require_once ($d . 'class-utils.php');
		require_once ($d . 'class-module.php'); 
		require_once ($d . 'class-tooltip.php');
		require_once ($d . 'class-widget.php');
		add_action('widgets_init', array($this,'register_widgets'));
		$this->utils = new Genesis_Club_Utils();
		$this->options = new Genesis_Club_Options( self::OPTIONS_NAME, $this->defaults);
		if ($this->is_genesis_loaded()) {
			$modules = array_keys($this->modules);
			foreach ($modules as $module) 
				if ($this->is_module_enabled($module))
					$this->init_module($module);
            add_action('wp', array($this,'maybe_enqueue_tooltip_styles' ));
		}
	}

	public function admin_init() {
		if ($this->is_genesis_loaded()) {
			$d = dirname(__FILE__) . '/';		
			require_once ($d . 'class-news.php');
			require_once ($d . 'class-admin.php');
			require_once ($d . 'class-dashboard.php');
			$this->news = new Genesis_Club_News($this->version);
			new Genesis_Club_Dashboard($this->version, $this->path, $this->slug);
			$modules = array_keys($this->modules);		
			foreach ($modules as $module) 
				if ($this->is_module_enabled($module))
					$this->init_module($module, true);
			add_action('admin_init',array($this, 'check_multiple_versions'));		
 			if ($this->get_activation_key()) add_action('admin_init',array($this, 'upgrade'));  
		    }
		}

	public function get_news(){
		return $this->news;
	}

	public function get_options(){
		return $this->options;
	}

	public function get_utils(){
		return $this->utils;
	}
	
	public function get_name(){
		return $this->name;
	}

   public function get_path(){
		return $this->path;
	}
	
   public function get_slug(){
		return $this->slug;
	}

	public function get_version(){
		return $this->version;
	}

	public function get_modules(){
		return $this->modules;
	}

	public function activate() { //called on plugin activation
    	if ( $this->is_genesis_present() ) 
    	    $this->set_activation_key();
		else 
         $this->abort();
	}
	
	public function upgrade() { //apply any upgrades
		$modules = array_keys($this->modules);
		foreach ($modules as $module) 
			if ($this->is_module_enabled($module))
				$this->upgrade_module($module);
		$this->options->upgrade_options();
		$this->unset_activation_key();
	}


	private function upgrade_module($module) {	
		if (array_key_exists($module, $this->modules)
		&& ($class = $this->modules[$module]['class'])) {
			if (array_key_exists($module, $this->admin_modules)
			&& is_callable(array( $this->admin_modules[$module],'upgrade'))) 
				call_user_func(array($this->admin_modules[$module], 'upgrade'));
		}
	}
	
	private function deactivate($path ='') {
		if (empty($path)) $path = $this->path;
		if (is_plugin_active($path)) deactivate_plugins( $path );
	}

    private function get_activation_key() { 
    	return get_option($this->activation_key_name()); 
    }

   private function set_activation_key() { 
    	return update_option($this->activation_key_name(), true); 
    }

    private function unset_activation_key() { 
    	return delete_option($this->activation_key_name(), true); 
    }

   private function activation_key_name() { 
    	return strtolower(__CLASS__) . '_activation'; 
    }

	function is_post_type_enabled($post_type){
		return in_array($post_type, array('post', 'page')) || $this->is_custom_post_type_enabled($post_type);
	}

	function is_custom_post_type_enabled($post_type){
		return in_array($post_type, (array)$this->options->get_option('custom_post_types'));
	}
	
	function custom_post_types_exist() {
       $cpt = get_post_types(array('public' => true, '_builtin' => false));
       return is_array($cpt) && (count($cpt) > 0);
	}

   public function get_module($module, $is_admin = false) {
	   $modules = $is_admin ? $this->admin_modules: $this->public_modules;
      return array_key_exists($module, $modules) ? $modules[$module] : false;
   }

    function get_modules_present(){
    	$modules = array();
    	$module_names = array_keys($this->modules);
		foreach ($module_names as $module_name) 
			if ($this->module_exists($module_name)) 
				$modules[$module_name] = $this->modules[$module_name];  	
		return $modules;
	}

	function module_exists($module) {
		return file_exists( dirname(__FILE__) .'/class-'. $module . '.php');
	}

	function is_module_enabled($module) {
		return ! $this->options->get_option($this->get_disabled_key($module));
	}

	private function init_module($module, $admin=false) {
		if (array_key_exists($module, $this->modules)
		&& ($class = $this->modules[$module]['class'])) {
			$prefix =  dirname(__FILE__) .'/class-'. $module;
			if ($admin) {
				$pro_class = $class .'_Pro_Admin';
				$class = $class .'_Admin';
				$file = $prefix . '-admin.php';
				$pro_file = $prefix . '-pro-admin.php';
				if (!class_exists($class) && file_exists($file)) {
					require_once($file);
					if (file_exists($pro_file)) {
       					require_once($pro_file);                 
                        $this->admin_modules[$module] = new $pro_class($this->version, $this->path, $this->slug, $module);   
					}
					else {
						$this->admin_modules[$module] = new $class($this->version, $this->path, $this->slug, $module);
 					}
 				}
			} else {
				$file = $prefix . '.php';
				$widgets = $prefix . '-widgets.php';
				$pro = $prefix . '-pro.php';
				if (!class_exists($class) && file_exists($file)) {
					require_once($file);
					if (file_exists($widgets)) require_once($widgets);
					if (file_exists($pro)) {
					   require_once($pro);
                       $pro_class = $class . '_Pro';
                       $this->public_modules[$module] = new $pro_class(); 
                    } else {
					$this->public_modules[$module] = new $class();
				}
			} 
		}
	}
	}
	
    function get_disabled_key($module) { 
    	return $module . '_disabled'; 
	}
	
	function is_genesis_present() {
		return substr(basename( TEMPLATEPATH ), 0,7) == 'genesis' ; //is genesis the current parent theme
	}

	function is_genesis_loaded() {
		return defined('GENESIS_LIB_DIR'); //is genesis actually loaded? (ie not been nobbled by another plugin) 
    }

	function check_multiple_versions() {
		if (is_plugin_active('genesis-club-pro/main.php') 
		&& is_plugin_active('genesis-club-lite/main.php')) {
			$this->deactivate('genesis-club-pro/main.php'); 
			$this->deactivate('genesis-club-lite/main.php'); 
       		 wp_die(  __( sprintf('You cannot run both Genesis Club Lite and Genesis Club Pro at the same time.<br/><strong>Both have been deactivated</strong>.<br/>Now go to the WordPress <a href="%1$s" style="text-decoration:underline"><em>Plugins page</em></a> and activate the one you want to use.',
        		 get_admin_url(null, 'plugins.php?s=genesis%20club')), GENESIS_CLUB_DOMAIN ));			 
		}
	}

	private function abort() {
      $this->deactivate(); //deactivate this plugin
      wp_die(  __( sprintf('Sorry, you cannot use %1$s unless you are using a child theme based on the StudioPress Genesis theme framework. The %1$s plugin has been deactivated. Go to the WordPress <a href="%2$s"><em>Plugins page</em></a>.',
        		$this->name, get_admin_url(null, 'plugins.php')), GENESIS_CLUB_DOMAIN ));       
	}	

	function register_widgets() {
		register_widget( 'Genesis_Club_Text_Widget' );			
	}
	function maybe_enqueue_tooltip_styles() {
	   /* Add Genesis Club Widgets Tooltip CSS for Beaver Builder Editor */
      if ( class_exists('FLBuilderModel')
      && is_callable(array('FLBuilderModel', 'is_builder_active')) 
      && FLBuilderModel::is_builder_active() ) {
         add_action('wp_enqueue_scripts', array($this->utils, 'register_tooltip_styles'));
         add_action('wp_enqueue_scripts', array($this->utils, 'enqueue_tooltip_styles'));
      }
	}		
 }
}