<?php
class Genesis_Club_Calendar_Admin extends Genesis_Club_Admin {
   private $calendar;
	private $tips = array(
		'src' => array('heading' => 'Calendar URL', 'tip' => 'URL of the calendar. This is the src parameter in the iframe querystring and typically ends in calendar.google.com or gmail.com'),
		'mode' => array('heading' => 'Mode', 'tip' => 'Show calendar by week, by month or as an agenda.'),
		'wkst' => array('heading' => 'Start Of Week', 'tip' => 'Saturday, Sunday or Monday'),
		'height' => array('heading' => 'Height of Calendar', 'tip' => 'Height of calendar in pixels'),
		'width' => array('heading' => 'Width Of Calendar', 'tip' => 'Width of calendar in pixels'),
		'border' => array('heading' => 'Border Style', 'tip' => 'Required valid CSS. For example, 1px solid red'),		
		'color' => array('heading' => 'Text Color', 'tip' => 'Choose the color of the text on the calendar.'),
		'bgcolor' => array('heading' => 'Title Background Color', 'tip' => 'Choose the color of the background of the title of calendar.'),
		'show_title' => array('heading' => 'Show Title', 'tip' => 'Show the calendar title above the calendar'),
		'show_nav' =>  array('heading' => 'Show Nav Buttons', 'tip' => 'Show Next and Prev arrows in the header above the calendar'),
		'show_print' => array('heading' => 'Show Print Button', 'tip' => 'Show a print icon in the header above the calendar'),
		'show_tabs' => array('heading' => 'Show Tabs', 'tip' => 'Show Tabs that allows to have different views of the calendar: Weekly, Monthly or Agenda'),
		'show_calendars' => array('heading' => 'Show Calendars', 'tip' => 'Add a drowdown listbox at the end of the header that lists all the calendars that source events shown of this calendar'),
		'show_date' => array('heading' => 'Show Date', 'tip' => 'Show the date at the top of the calendar'),
		'show_tz' => array('heading' => 'Show Timezone', 'tip' => 'Show the currently selected timezone at the bottom of the calendar'),
		'timezone_locator' => array('heading' => 'Timezone Locator', 'tip' => 'Locate the timezone selector either above or below the calendar.'),
		'label' => array('heading' => 'Timezone Label', 'tip' => 'For example, Choose Your Timezone to see the meetings in your local time.'),
		'timezone' => array('heading' => 'Timezone', 'tip' => 'For example, Europe/London'),
		'iframe' => array('heading' => 'Google Calendar Iframe', 'tip' => 'Paste the embed code for your Google Calendar.'),
	);
	
	function init() {
	  $this->calendar = $this->plugin->get_module('calendar');
		add_action('admin_menu',array($this, 'admin_menu'));
	}
	
	function admin_menu() {
		$this->screen_id = add_submenu_page($this->get_parent_slug(), __('Genesis Club Calendar'), __('Calendar'), 'manage_options', 
			$this->get_slug(), array($this,'page_content'));
		add_action('load-'.$this->get_screen_id(), array($this, 'load_page'));
	}
	
	function page_content() {
		$title = $this->admin_heading('Genesis Club Calendar Settings');		
		$this->print_admin_form($title,  __CLASS__, $this->get_keys()); 
	}  

	function load_page() {
 		if ( isset($_POST['options_update']) ) $this->save_calendar();	
		$this->add_meta_box('intro', 'Intro', 'intro_panel' );
		$this->add_meta_box('menu', 'Google Calendar Defaults', 'calendar_panel', array ('options' => $this->calendar->get_options()) );
		$this->add_meta_box('news', 'Genesis Club News', 'news_panel', null, 'advanced');
		$this->set_tooltips($this->tips);
		add_action('admin_enqueue_scripts',array($this, 'enqueue_admin_styles'));
		add_action('admin_enqueue_scripts',array($this, 'enqueue_metabox_scripts'));
		add_action('admin_enqueue_scripts',array($this, 'enqueue_postbox_scripts'));
	}

	function save_calendar() {
		check_admin_referer(__CLASS__);
		$_POST['src'] = rawurldecode($_POST['src']);
		return $this->save_options($this->calendar,'Calendar Settings');
	}
	 
	function calendar_panel($post,$metabox) {
      $options = $metabox['args']['options'];
      print $this->tabbed_metabox( $metabox['id'], array(
         'Calendar' => $this->source_panel($options),
         'Size' => $this->size_panel($options),
         'Colors' => $this->colors_panel($options),
         'Options' => $this->show_panel($options),
         'Timezone' => $this->timezone_panel($options)
		));
   }	
	 
	function source_panel($options){	
	  return
		$this->fetch_form_field("src", $options['src'], 'text', array(), array('size' => 80)) .	
		$this->fetch_form_field("mode", $options['mode'], 'radio', $this->calendar->modes()).
        $this->fetch_form_field("wkst", $options['wkst'], 'radio', $this->calendar->weekdays()) ;
	}

	function size_panel($options){	
		return 
         $this->fetch_form_field("height", $options['height'], 'text', array(), array('size' => 4)) .
         $this->fetch_form_field("width", $options['width'], 'text', array(), array('size' => 4)) ;
   }
   
	function colors_panel($options){	   
      return
         $this->fetch_form_field("border", $options['border'], 'text', array(), array('size' => 30)) .
         $this->fetch_form_field("color", $options['color'], 'select', $this->calendar->text_colors()) .
         $this->fetch_form_field("bgcolor", $options['bgcolor'], 'text', array(), array('size' => 7, 'class' => 'color-picker')) ;
   }
   
	function show_panel($options){	   
	  return
         $this->fetch_form_field("show_title", $options['show_title'], 'checkbox') .
         $this->fetch_form_field("show_nav", $options['show_nav'], 'checkbox') .
         $this->fetch_form_field("show_print", $options['show_print'], 'checkbox') .
         $this->fetch_form_field("show_tabs", $options['show_tabs'], 'checkbox') .
         $this->fetch_form_field("show_calendars", $options['show_calendars'], 'checkbox') .
         $this->fetch_form_field("show_date", $options['show_date'], 'checkbox') .
         $this->fetch_form_field("show_tz", $options['show_tz'], 'checkbox') ;
	}
	
	function timezone_panel($options){	
      return
         $this->fetch_form_field("timezone_locator", $options['timezone_locator'], 'radio', $this->calendar->timezone_locations()) .
         $this->fetch_form_field("label", $options['label'], 'textarea', array(), array('rows' => 3, 'cols' => 80)) .
         $this->fetch_form_field("timezone", $options['timezone'], 'select', $this->calendar->timezones()) ;
	}	
  
  function upgrade() {
      $options = $this->calendar->get_options();
      if (is_array($options) && !empty($options['iframe']) && (! array_key_exists('src', $options) || empty($options['src']) ) ) {
         $options['src'] = self::get_qryitem_from_iframe($options['iframe'], 'src');
         $options['mode'] = self::get_qryitem_from_iframe($options['iframe'], 'mode');
         $options['wkst'] = self::get_qryitem_from_iframe($options['iframe'], 'wkst');
         $options['height'] = self::get_height_from_iframe($options['iframe']);
         $options['width'] = self::get_width_from_iframe($options['iframe']);
         $options['color'] = self::get_qryitem_from_iframe($options['iframe'], 'color');
         $options['bgcolor'] = self::get_qryitem_from_iframe($options['iframe'], 'bgcolor');
         $options['show_title'] = self::get_qryitem_from_iframe($options['iframe'], 'showTitle');
         $options['show_print'] = self::get_qryitem_from_iframe($options['iframe'], 'showPrint');
         $options['show_tabs'] = self::get_qryitem_from_iframe($options['iframe'], 'showTabs');
         $options['show_nav'] = self::get_qryitem_from_iframe($options['iframe'], 'showNav');
         $options['show_calendars'] = self::get_qryitem_from_iframe($options['iframe'], 'showCalendars');
         $options['show_date'] = self::get_qryitem_from_iframe($options['iframe'], 'showDate');
         $options['show_tz'] = self::get_qryitem_from_iframe($options['iframe'], 'showTz');
         $options['timezone_locator'] = 'below';
         $options['iframe'] = '';
         return $this->calendar->save_options($options);
      }
   }
  
   function get_qryitem_from_iframe($iframe, $item) {
      $val = false;
      $len = strlen($item);
      $needle1 = '&amp;'.$item.'=';
      $needle2 = '&'.$item.'=';

      if (strpos($iframe, $needle1 ) !==FALSE)
             $val = substr($iframe, 
               strpos($iframe, $needle1 )+ strlen($needle1),
               strpos($iframe, '&amp;', strpos($iframe, $needle1)+strlen($needle1)+1)-strpos($iframe, $needle1)- strlen($needle1));   
      elseif (strpos($iframe, '&src=') !==FALSE)
             $val = substr($iframe, 
               strpos($iframe, $needle2)+strlen($needle2),
               strpos($iframe, '&',strpos($iframe, $needle2)+strlen($needle2)+1)-strpos($iframe, $needle2)-strlen($needle2));  
      return $val ? urldecode($val) : $this->calendar->get_default($item);
  }

   function get_height_from_iframe($iframe) {
      $height = false;
      if (strpos($iframe, 'height="') !==FALSE)
             $height = substr($iframe, 
               strpos($iframe, 'height="')+8,
               strpos($iframe, '"', strpos($iframe, 'height="')+9)-strpos($iframe, 'height="')-8);   
      return $height ? $height : $this->calendar->get_default('height');
  }

   function get_width_from_iframe($iframe) {
      $width = false;
      if (strpos($iframe, 'width="') !==FALSE)
             $width = substr($iframe, 
               strpos($iframe, 'width="')+7,
               strpos($iframe, '"', strpos($iframe, 'width="')+8)-strpos($iframe, 'width="')-7);   
      return $width ? $width : $this->calendar->get_default('width');
  }  

 	function intro_panel($post,$metabox){	
		print <<< INTRO_PANEL
<p>The following section allows you to set up a Google Calendar which shows your events in your visitor's timezone.
This is particularly useful if you are delivering webinars to a global audience.</p>
<p>You add the calendar to the page by using the shortcode [<i>genesis_club_calendar</i>].</p>
<p>If you are operating more than one Google calendar, then you will need to pass parameters to the shortcode to specify which Google Calendar to use, height, width, text color, background color, etc.</p>
<p>Please see <a target="_blank" href="http://www.genesisclubpro.com/9393/how-to-display-more-than-one-google-calendar/">How To Display More Than One Google Calendar</a></p> 
INTRO_PANEL;
	}
  
}
