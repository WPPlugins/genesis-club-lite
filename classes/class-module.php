<?php
if (!class_exists('Genesis_Club_Module')) {
abstract class Genesis_Club_Module {

    protected $plugin;
    protected $utils;
    protected $options;
    private $options_name;

    abstract function get_options_name();
    abstract function get_defaults();
    abstract function init();
    
    function __construct() {
      $this->plugin = Genesis_Club_Plugin::get_instance();
      $this->utils = $this->plugin->get_utils();
      $this->options = $this->plugin->get_options();
        $this->options_name = $this->get_options_name();
        if ($this->options_name) $this->options->add_defaults( array($this->options_name => $this->get_defaults()) ) ;
      $this->init();
    }
    
    function get_option($option_name, $cache = true) {
    	$options = $this->get_options($cache);
    	if ($option_name && $options && array_key_exists($option_name,$options))
        	   return $options[$option_name];
    	else
        	   return false;
    }

    function get_options($cache = true) {
      return $this->options->get_option($this->options_name, $cache);
    }
	
    function save_options($options) {
            return $this->options->save_options(array($this->options_name => $options)) ;
    }
 }
}