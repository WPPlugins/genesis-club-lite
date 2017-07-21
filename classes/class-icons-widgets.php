<?php
if (class_exists('Simple_Social_Icons_Widget'))	{
	class Genesis_Club_Icons_Widget extends Simple_Social_Icons_Widget	{
		function css() {  //improved CSS to support multiple widgets per page and shortcode and avoid using !important
			$plugin = Genesis_Club_Plugin::get_instance();
			$icons = $plugin->get_module('icons');
        	$all_instances = $this->get_settings();
        	foreach ( $all_instances as $id => $inst) {
        		$widget_id = $this->id_base.'-'.$id;
        		
        		if (is_active_widget( false, $widget_id, $this->id_base, true )) {
        			$instance = wp_parse_args( $inst, $this->defaults );
					$instance['id'] = $widget_id;
					$icons->add_css($instance);
				}
        	}
		}
		
	}
}