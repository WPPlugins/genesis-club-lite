<?php
class Genesis_Club_Calendar extends Genesis_Club_Module {
	const COOKIE_NAME = 'google_calendar_timezone';
	const CALENDAR_OPTION = 'genesis_club_calendar';
	const OPTION_NAME = 'calendar';
    private $palette = array(
        'A32929' => 'Dark Red', 'B1365F' => 'Dark Pink',  '7A367A' => 'Purple', '5229A3' => 'Indigo', '29527A' => 'Sea Blue', '2952A3' => 'Dark Blue', '1B887A' => 'Turquoise',
        '28754E' => 'Teal', '0D7813' => 'Green', '528800' => 'Lime', '88880E' => 'Mustard', 'AB8B00' => 'Yellow', 'BE6D00' => 'Orange', 'B1440E' => 'Tomato',
        '865A5A' => 'Chestnut', '705770' => 'Plum', '4E5D6C' => 'Charcoal', '5A6986' => 'Dark Grayish Blue', '4A716C' => 'Slate', '6E6E41' => 'Olive', '8D6F47' => 'Brown',
        '853104' => 'Dark Orange Brown', '691426' => 'Dark Maroon', '5C1158' => 'Dark Magenta', '23164E' => 'Vary Dark Blue', '182C57' => 'Very Dark Azure', '060D5E' => 'Vivid Azure', '125A12' => 'Dark Green',
        '2F6213' => 'Very Dark Green', '2F6309' => 'Deep Green', '5F6B02' => 'Vivid Olive', '8C500B' => 'Dark Rust', '754916' => 'Chocolate Orange', '6B3304' => 'Vivid Brown',
        '5B123B' => 'Deep Purple', '42104A' => 'Dark Purple', '113F47' => 'Dark Cyan', '333333' => 'Gray', '0F4B38' => 'Deep Teal', '856508' => 'French Mustard', '711616' => 'Bordeaux'
    );

	protected $defaults  = array(
		'src' => '',
		'mode' => 'MONTH',
		'wkst' => 2,  //week start Monday ISO standard
		'height' => 800,
		'width' => 1400,
		'color' => '#6B3304' , 
		'bgcolor' => '#FFFFFF',
		'border' => 'none',	
		'show_title' => false,
		'show_nav' => false,
		'show_print' => false,
		'show_tabs' => false,
		'show_calendars' => false,
		'show_date' => false,
		'show_tz' => false,
		'timezone_locator' => 'below',
		'label' => 'Choose Your Timezone',
		'timezone' => 'Europe/London',
		'iframe' => '' //this parameter is deprecated, now iframe is constructed from the above parameters
	);

    function get_defaults() {
        return $this->defaults; 
    }	

	function get_options_name() {
        return self::OPTION_NAME; 
	}		

	function init() {
		add_shortcode('genesis_club_calendar', array($this,'display'));
		add_shortcode('genesis-club-calendar', array($this,'display'));
    }

    function get_default($option_name) {
        return array_key_exists($option_name, $this->defaults) ?  $this->defaults[$option_name] : false;
    }
	
	public function enqueue_scripts() {
		wp_enqueue_script('genesis-club-calendar', plugins_url('scripts/jquery.calendar.js',dirname(__FILE__)), array('jquery'), GENESIS_CLUB_VERSION, true);
	}

	function display($attr) {
		$this->enqueue_scripts();
		$params = shortcode_atts($this->get_options(), $attr);
		$ctz = $this->get_timezone($params['timezone']); //override timezone with cookie 
		$format = 'above'==$params['timezone_locator'] ? '%2$s%1$s' : '%1$s%2$s';
		return sprintf( '<form class="gcal">'.$format.'</form>',  
			$this->calendar($params, $ctz), 
			$this->timezone_listbox( $params['label'], 'ctz', $ctz)
		);
	}

	function get_timezone($timezone) {
		if (is_array($_COOKIE)
		&& array_key_exists(self::COOKIE_NAME, $_COOKIE))
			return $_COOKIE[self::COOKIE_NAME];
		else
			return $timezone;
	}

   function check_color($color) {

   }

	function calendar($params, $ctz) {
      return sprintf('<iframe src="https://www.google.com/calendar/embed?src=%1$s&mode=%2$s&wkst=%3$s&height=%4$s&width=%5$s&color=%7$s&bgcolor=%8$s&showTitle=%9$s&showNav=%10$s&showPrint=%11$s&showTabs=%12$s&showCalendars=%13$s&showDate=%14$s&showTz=%15$s&ctz=%16$s" height="%4$s" width="%5$s" style="border: %6$s" frameborder="0" scrolling="no"></iframe>',
         urlencode($params['src']),
	      strtoupper($params['mode']),
         $params['wkst'] ? $params['wkst'] : $this->get_default('wkst'),
         $params['height'] ? $params['height'] : $this->get_default('height'), 
         $params['width'] ? $params['width'] : $this->get_default('width'),
         $params['border'], 	
         urlencode($this->color_in_palette($params['color'])),  
         urlencode($params['bgcolor']), 
         $params['show_title'] ? 1: 0,
         $params['show_nav'] ? 1: 0,   
         $params['show_print'] ? 1: 0,
         $params['show_tabs'] ? 1: 0,
         $params['show_calendars'] ? 1: 0,
         $params['show_date'] ? 1: 0,
	     $params['show_tz'] ? 1: 0,
         urlencode($ctz)
         ) ;
	}

	function dropdown_listbox($fld_id, $fld_name, $label, $value, $options) {
		$label = sprintf('<label class="diy-label" for="%1$s">%2$s</label>', $fld_id, __($label));
		$input = '';
		if (is_array($options)) {
			foreach ($options as $optkey => $optlabel)
				$input .= sprintf('<option%1$s value="%2$s">%3$s</option>',
					selected($optkey, $value, false), $optkey, $optlabel); 
		} else {
			$input = $options;
		}
		return sprintf('<p>%4$s<select id="%1$s" name="%2$s">%3$s</select></p>', $fld_id, $fld_name, $input, $label);							
	}

	function timezone_listbox ( $label, $fld, $value ) {	
		 return $this->dropdown_listbox($fld, $fld, $label, $value, $this->timezones());	
	}

	function timezones() {
		return array(
"Pacific/Midway" => "(GMT-11:00) Midway", "Pacific/Niue" => "(GMT-11:00) Niue", "Pacific/Pago_Pago" => "(GMT-11:00) Pago Pago", "Pacific/Honolulu" => "(GMT-10:00) Hawaii Time", "Pacific/Rarotonga" => "(GMT-10:00) Rarotonga", "Pacific/Tahiti" => "(GMT-10:00) Tahiti", "Pacific/Marquesas" => "(GMT-09:30) Marquesas", "America/Anchorage" => "(GMT-09:00) Alaska Time", "Pacific/Gambier" => "(GMT-09:00) Gambier", "America/Los_Angeles" => "(GMT-08:00) Pacific Time", "America/Tijuana" => "(GMT-08:00) Pacific Time - Tijuana", "America/Vancouver" => "(GMT-08:00) Pacific Time - Vancouver", "America/Whitehorse" => "(GMT-08:00) Pacific Time - Whitehorse", "Pacific/Pitcairn" => "(GMT-08:00) Pitcairn", "America/Dawson_Creek" => "(GMT-07:00) Mountain Time - Dawson Creek", "America/Denver" => "(GMT-07:00) Mountain Time", "America/Edmonton" => "(GMT-07:00) Mountain Time - Edmonton", "America/Hermosillo" => "(GMT-07:00) Mountain Time - Hermosillo", "America/Mazatlan" => "(GMT-07:00) Mountain Time - Chihuahua, Mazatlan", "America/Phoenix" => "(GMT-07:00) Mountain Time - Arizona", "America/Yellowknife" => "(GMT-07:00) Mountain Time - Yellowknife", "America/Belize" => "(GMT-06:00) Belize", "America/Chicago" => "(GMT-06:00) Central Time", "America/Costa_Rica" => "(GMT-06:00) Costa Rica", "America/El_Salvador" => "(GMT-06:00) El Salvador", "America/Guatemala" => "(GMT-06:00) Guatemala", "America/Managua" => "(GMT-06:00) Managua", "America/Mexico_City" => "(GMT-06:00) Central Time - Mexico City", "America/Regina" => "(GMT-06:00) Central Time - Regina", "America/Tegucigalpa" => "(GMT-06:00) Central Time - Tegucigalpa", "America/Winnipeg" => "(GMT-06:00) Central Time - Winnipeg", "Pacific/Easter" => "(GMT-06:00) Easter Island", "Pacific/Galapagos" => "(GMT-06:00) Galapagos", "America/Bogota" => "(GMT-05:00) Bogota", "America/Cayman" => "(GMT-05:00) Cayman", "America/Grand_Turk" => "(GMT-05:00) Grand Turk", "America/Guayaquil" => "(GMT-05:00) Guayaquil", "America/Havana" => "(GMT-05:00) Havana", "America/Iqaluit" => "(GMT-05:00) Eastern Time - Iqaluit", "America/Jamaica" => "(GMT-05:00) Jamaica", "America/Lima" => "(GMT-05:00) Lima", "America/Montreal" => "(GMT-05:00) Eastern Time - Montreal", "America/Nassau" => "(GMT-05:00) Nassau", "America/New_York" => "(GMT-05:00) Eastern Time", "America/Panama" => "(GMT-05:00) Panama", "America/Port-au-Prince" => "(GMT-05:00) Port-au-Prince", "America/Rio_Branco" => "(GMT-05:00) Rio Branco", "America/Toronto" => "(GMT-05:00) Eastern Time - Toronto", "America/Caracas" => "(GMT-04:30) Caracas", "America/Antigua" => "(GMT-04:00) Antigua", "America/Asuncion" => "(GMT-04:00) Asuncion", "America/Barbados" => "(GMT-04:00) Barbados", "America/Boa_Vista" => "(GMT-04:00) Boa Vista", "America/Campo_Grande" => "(GMT-04:00) Campo Grande", "America/Cuiaba" => "(GMT-04:00) Cuiaba", "America/Curacao" => "(GMT-04:00) Curacao", "America/Guyana" => "(GMT-04:00) Guyana", "America/Halifax" => "(GMT-04:00) Atlantic Time - Halifax", "America/La_Paz" => "(GMT-04:00) La Paz", "America/Manaus" => "(GMT-04:00) Manaus", "America/Martinique" => "(GMT-04:00) Martinique", "America/Port_of_Spain" => "(GMT-04:00) Port of Spain", "America/Porto_Velho" => "(GMT-04:00) Porto Velho", "America/Puerto_Rico" => "(GMT-04:00) Puerto Rico", "America/Santiago" => "(GMT-04:00) Santiago", "America/Santo_Domingo" => "(GMT-04:00) Santo Domingo", "America/Thule" => "(GMT-04:00) Thule", "Antarctica/Palmer" => "(GMT-04:00) Palmer", "Atlantic/Bermuda" => "(GMT-04:00) Bermuda", "America/St_Johns" => "(GMT-03:30) Newfoundland Time - St. Johns", "America/Araguaina" => "(GMT-03:00) Araguaina", "America/Argentina/Buenos_Aires" => "(GMT-03:00) Buenos Aires", "America/Bahia" => "(GMT-03:00) Salvador", "America/Belem" => "(GMT-03:00) Belem", "America/Cayenne" => "(GMT-03:00) Cayenne", "America/Fortaleza" => "(GMT-03:00) Fortaleza", "America/Godthab" => "(GMT-03:00) Godthab", "America/Maceio" => "(GMT-03:00) Maceio", "America/Miquelon" => "(GMT-03:00) Miquelon", "America/Montevideo" => "(GMT-03:00) Montevideo", "America/Paramaribo" => "(GMT-03:00) Paramaribo", "America/Recife" => "(GMT-03:00) Recife", "America/Sao_Paulo" => "(GMT-03:00) Sao Paulo", "Antarctica/Rothera" => "(GMT-03:00) Rothera", "Atlantic/Stanley" => "(GMT-03:00) Stanley", "America/Noronha" => "(GMT-02:00) Noronha", "Atlantic/South_Georgia" => "(GMT-02:00) South Georgia", "America/Scoresbysund" => "(GMT-01:00) Scoresbysund", "Atlantic/Azores" => "(GMT-01:00) Azores", "Atlantic/Cape_Verde" => "(GMT-01:00) Cape Verde", "Africa/Abidjan" => "(GMT+00:00) Abidjan", "Africa/Accra" => "(GMT+00:00) Accra", "Africa/Bamako" => "(GMT+00:00) Bamako", "Africa/Banjul" => "(GMT+00:00) Banjul", "Africa/Bissau" => "(GMT+00:00) Bissau", "Africa/Casablanca" => "(GMT+00:00) Casablanca", "Africa/Conakry" => "(GMT+00:00) Conakry", "Africa/Dakar" => "(GMT+00:00) Dakar", "Africa/El_Aaiun" => "(GMT+00:00) El Aaiun", "Africa/Freetown" => "(GMT+00:00) Freetown", "Africa/Lome" => "(GMT+00:00) Lome", "Africa/Monrovia" => "(GMT+00:00) Monrovia", "Africa/Nouakchott" => "(GMT+00:00) Nouakchott", "Africa/Ouagadougou" => "(GMT+00:00) Ouagadougou", "Africa/Sao_Tome" => "(GMT+00:00) Sao Tome", "America/Danmarkshavn" => "(GMT+00:00) Danmarkshavn", "Atlantic/Canary" => "(GMT+00:00) Canary Islands", "Atlantic/Faroe" => "(GMT+00:00) Faeroe", "Atlantic/Reykjavik" => "(GMT+00:00) Reykjavik", "Atlantic/St_Helena" => "(GMT+00:00) St Helena", "Etc/GMT" => "(GMT+00:00) GMT (no daylight saving)", "Europe/Dublin" => "(GMT+00:00) Dublin", "Europe/Lisbon" => "(GMT+00:00) Lisbon", "Europe/London" => "(GMT+00:00) London", "Africa/Algiers" => "(GMT+01:00) Algiers", "Africa/Bangui" => "(GMT+01:00) Bangui", "Africa/Brazzaville" => "(GMT+01:00) Brazzaville", "Africa/Ceuta" => "(GMT+01:00) Ceuta", "Africa/Douala" => "(GMT+01:00) Douala", "Africa/Kinshasa" => "(GMT+01:00) Kinshasa", "Africa/Lagos" => "(GMT+01:00) Lagos", "Africa/Libreville" => "(GMT+01:00) Libreville", "Africa/Luanda" => "(GMT+01:00) Luanda", "Africa/Malabo" => "(GMT+01:00) Malabo", "Africa/Ndjamena" => "(GMT+01:00) Ndjamena", "Africa/Niamey" => "(GMT+01:00) Niamey", "Africa/Porto-Novo" => "(GMT+01:00) Porto-Novo", "Africa/Tunis" => "(GMT+01:00) Tunis", "Africa/Windhoek" => "(GMT+01:00) Windhoek", "Europe/Amsterdam" => "(GMT+01:00) Amsterdam", "Europe/Andorra" => "(GMT+01:00) Andorra", "Europe/Belgrade" => "(GMT+01:00) Central European Time - Belgrade", "Europe/Berlin" => "(GMT+01:00) Berlin", "Europe/Brussels" => "(GMT+01:00) Brussels", "Europe/Budapest" => "(GMT+01:00) Budapest", "Europe/Copenhagen" => "(GMT+01:00) Copenhagen", "Europe/Gibraltar" => "(GMT+01:00) Gibraltar", "Europe/Luxembourg" => "(GMT+01:00) Luxembourg", "Europe/Madrid" => "(GMT+01:00) Madrid", "Europe/Malta" => "(GMT+01:00) Malta", "Europe/Monaco" => "(GMT+01:00) Monaco", "Europe/Oslo" => "(GMT+01:00) Oslo", "Europe/Paris" => "(GMT+01:00) Paris", "Europe/Prague" => "(GMT+01:00) Central European Time - Prague", "Europe/Rome" => "(GMT+01:00) Rome", "Europe/Stockholm" => "(GMT+01:00) Stockholm", "Europe/Tirane" => "(GMT+01:00) Tirane", "Europe/Vienna" => "(GMT+01:00) Vienna", "Europe/Warsaw" => "(GMT+01:00) Warsaw", "Europe/Zurich" => "(GMT+01:00) Zurich", "Africa/Blantyre" => "(GMT+02:00) Blantyre", "Africa/Bujumbura" => "(GMT+02:00) Bujumbura", "Africa/Cairo" => "(GMT+02:00) Cairo", "Africa/Gaborone" => "(GMT+02:00) Gaborone", "Africa/Harare" => "(GMT+02:00) Harare", "Africa/Johannesburg" => "(GMT+02:00) Johannesburg", "Africa/Kigali" => "(GMT+02:00) Kigali", "Africa/Lubumbashi" => "(GMT+02:00) Lubumbashi", "Africa/Lusaka" => "(GMT+02:00) Lusaka", "Africa/Maputo" => "(GMT+02:00) Maputo", "Africa/Maseru" => "(GMT+02:00) Maseru", "Africa/Mbabane" => "(GMT+02:00) Mbabane", "Africa/Tripoli" => "(GMT+02:00) Tripoli", "Asia/Amman" => "(GMT+02:00) Amman", "Asia/Beirut" => "(GMT+02:00) Beirut", "Asia/Damascus" => "(GMT+02:00) Damascus", "Asia/Gaza" => "(GMT+02:00) Gaza", "Asia/Jerusalem" => "(GMT+02:00) Jerusalem", "Asia/Nicosia" => "(GMT+02:00) Nicosia", "Europe/Athens" => "(GMT+02:00) Athens", "Europe/Bucharest" => "(GMT+02:00) Bucharest", "Europe/Chisinau" => "(GMT+02:00) Chisinau", "Europe/Helsinki" => "(GMT+02:00) Helsinki", "Europe/Istanbul" => "(GMT+02:00) Istanbul", "Europe/Kiev" => "(GMT+02:00) Kiev", "Europe/Riga" => "(GMT+02:00) Riga", "Europe/Sofia" => "(GMT+02:00) Sofia", "Europe/Tallinn" => "(GMT+02:00) Tallinn", "Europe/Vilnius" => "(GMT+02:00) Vilnius", "Africa/Addis_Ababa" => "(GMT+03:00) Addis Ababa", "Africa/Asmara" => "(GMT+03:00) Asmera", "Africa/Dar_es_Salaam" => "(GMT+03:00) Dar es Salaam", "Africa/Djibouti" => "(GMT+03:00) Djibouti", "Africa/Kampala" => "(GMT+03:00) Kampala", "Africa/Khartoum" => "(GMT+03:00) Khartoum", "Africa/Mogadishu" => "(GMT+03:00) Mogadishu", "Africa/Nairobi" => "(GMT+03:00) Nairobi", "Antarctica/Syowa" => "(GMT+03:00) Syowa", "Asia/Aden" => "(GMT+03:00) Aden", "Asia/Baghdad" => "(GMT+03:00) Baghdad", "Asia/Bahrain" => "(GMT+03:00) Bahrain", "Asia/Kuwait" => "(GMT+03:00) Kuwait", "Asia/Qatar" => "(GMT+03:00) Qatar", "Asia/Riyadh" => "(GMT+03:00) Riyadh", "Europe/Kaliningrad" => "(GMT+03:00) Moscow-01 - Kaliningrad", "Europe/Minsk" => "(GMT+03:00) Minsk", "Indian/Antananarivo" => "(GMT+03:00) Antananarivo", "Indian/Comoro" => "(GMT+03:00) Comoro", "Indian/Mayotte" => "(GMT+03:00) Mayotte", "Asia/Tehran" => "(GMT+03:30) Tehran", "Asia/Baku" => "(GMT+04:00) Baku", "Asia/Dubai" => "(GMT+04:00) Dubai", "Asia/Muscat" => "(GMT+04:00) Muscat", "Asia/Tbilisi" => "(GMT+04:00) Tbilisi", "Asia/Yerevan" => "(GMT+04:00) Yerevan", "Europe/Moscow" => "(GMT+04:00) Moscow+00", "Europe/Samara" => "(GMT+04:00) Moscow+00 - Samara", "Indian/Mahe" => "(GMT+04:00) Mahe", "Indian/Mauritius" => "(GMT+04:00) Mauritius", "Indian/Reunion" => "(GMT+04:00) Reunion", "Asia/Kabul" => "(GMT+04:30) Kabul", "Antarctica/Mawson" => "(GMT+05:00) Mawson", "Asia/Aqtau" => "(GMT+05:00) Aqtau", "Asia/Aqtobe" => "(GMT+05:00) Aqtobe", "Asia/Ashgabat" => "(GMT+05:00) Ashgabat", "Asia/Dushanbe" => "(GMT+05:00) Dushanbe", "Asia/Karachi" => "(GMT+05:00) Karachi", "Asia/Tashkent" => "(GMT+05:00) Tashkent", "Indian/Kerguelen" => "(GMT+05:00) Kerguelen", "Indian/Maldives" => "(GMT+05:00) Maldives", "Asia/Calcutta" => "(GMT+05:30) India Standard Time", "Asia/Colombo" => "(GMT+05:30) Colombo", "Asia/Katmandu" => "(GMT+05:45) Katmandu", "Antarctica/Vostok" => "(GMT+06:00) Vostok", "Asia/Almaty" => "(GMT+06:00) Almaty", "Asia/Bishkek" => "(GMT+06:00) Bishkek", "Asia/Dhaka" => "(GMT+06:00) Dhaka", "Asia/Thimphu" => "(GMT+06:00) Thimphu", "Asia/Yekaterinburg" => "(GMT+06:00) Moscow+02 - Yekaterinburg", "Indian/Chagos" => "(GMT+06:00) Chagos", "Asia/Rangoon" => "(GMT+06:30) Rangoon", "Indian/Cocos" => "(GMT+06:30) Cocos", "Antarctica/Davis" => "(GMT+07:00) Davis", "Asia/Bangkok" => "(GMT+07:00) Bangkok", "Asia/Hovd" => "(GMT+07:00) Hovd", "Asia/Jakarta" => "(GMT+07:00) Jakarta", "Asia/Omsk" => "(GMT+07:00) Moscow+03 - Omsk, Novosibirsk", "Asia/Phnom_Penh" => "(GMT+07:00) Phnom Penh", "Asia/Saigon" => "(GMT+07:00) Hanoi", "Asia/Vientiane" => "(GMT+07:00) Vientiane", "Indian/Christmas" => "(GMT+07:00) Christmas", "Antarctica/Casey" => "(GMT+08:00) Casey", "Asia/Brunei" => "(GMT+08:00) Brunei", "Asia/Choibalsan" => "(GMT+08:00) Choibalsan", "Asia/Hong_Kong" => "(GMT+08:00) Hong Kong", "Asia/Krasnoyarsk" => "(GMT+08:00) Moscow+04 - Krasnoyarsk", "Asia/Kuala_Lumpur" => "(GMT+08:00) Kuala Lumpur", "Asia/Macau" => "(GMT+08:00) Macau", "Asia/Makassar" => "(GMT+08:00) Makassar", "Asia/Manila" => "(GMT+08:00) Manila", "Asia/Shanghai" => "(GMT+08:00) China Time - Beijing", "Asia/Singapore" => "(GMT+08:00) Singapore", "Asia/Taipei" => "(GMT+08:00) Taipei", "Asia/Ulaanbaatar" => "(GMT+08:00) Ulaanbaatar", "Australia/Perth" => "(GMT+08:00) Western Time - Perth", "Asia/Dili" => "(GMT+09:00) Dili", "Asia/Irkutsk" => "(GMT+09:00) Moscow+05 - Irkutsk", "Asia/Jayapura" => "(GMT+09:00) Jayapura", "Asia/Pyongyang" => "(GMT+09:00) Pyongyang", "Asia/Seoul" => "(GMT+09:00) Seoul", "Asia/Tokyo" => "(GMT+09:00) Tokyo", "Pacific/Palau" => "(GMT+09:00) Palau", "Australia/Adelaide" => "(GMT+09:30) Central Time - Adelaide", "Australia/Darwin" => "(GMT+09:30) Central Time - Darwin", "Antarctica/DumontDUrville" => "(GMT+10:00) Dumont D&#39;Urville", "Asia/Yakutsk" => "(GMT+10:00) Moscow+06 - Yakutsk", "Australia/Brisbane" => "(GMT+10:00) Eastern Time - Brisbane", "Australia/Hobart" => "(GMT+10:00) Eastern Time - Hobart", "Australia/Sydney" => "(GMT+10:00) Eastern Time - Melbourne, Sydney", "Pacific/Chuuk" => "(GMT+10:00) Truk", "Pacific/Guam" => "(GMT+10:00) Guam", "Pacific/Port_Moresby" => "(GMT+10:00) Port Moresby", "Pacific/Saipan" => "(GMT+10:00) Saipan", "Asia/Vladivostok" => "(GMT+11:00) Moscow+07 - Yuzhno-Sakhalinsk", "Pacific/Efate" => "(GMT+11:00) Efate", "Pacific/Guadalcanal" => "(GMT+11:00) Guadalcanal", "Pacific/Kosrae" => "(GMT+11:00) Kosrae", "Pacific/Noumea" => "(GMT+11:00) Noumea", "Pacific/Pohnpei" => "(GMT+11:00) Ponape", "Pacific/Norfolk" => "(GMT+11:30) Norfolk", "Asia/Kamchatka" => "(GMT+12:00) Moscow+08 - Petropavlovsk-Kamchatskiy", "Asia/Magadan" => "(GMT+12:00) Moscow+08 - Magadan", "Pacific/Auckland" => "(GMT+12:00) Auckland", "Pacific/Fiji" => "(GMT+12:00) Fiji", "Pacific/Funafuti" => "(GMT+12:00) Funafuti", "Pacific/Kwajalein" => "(GMT+12:00) Kwajalein", "Pacific/Majuro" => "(GMT+12:00) Majuro", "Pacific/Nauru" => "(GMT+12:00) Nauru", "Pacific/Tarawa" => "(GMT+12:00) Tarawa", "Pacific/Wake" => "(GMT+12:00) Wake", "Pacific/Wallis" => "(GMT+12:00) Wallis", "Pacific/Apia" => "(GMT+13:00) Apia", "Pacific/Enderbury" => "(GMT+13:00) Enderbury", "Pacific/Fakaofo" => "(GMT+13:00) Fakaofo", "Pacific/Tongatapu" => "(GMT+13:00) Tongatapu", "Pacific/Kiritimati" => "(GMT+14:00) Kiritimati");
	}	

	function timezone_locations() {
		return array( 'below' => 'Beneath the calendar', 'above' => 'Above the calendar');
	}	

	function weekdays() {
		return array( 1 => 'Sunday', 2 => 'Monday', 7 => 'Saturday');
	}	

	function modes() {
		return array( 'WEEK' => 'Weekly View', 'MONTH' => 'Monthly View', 'AGENDA' => 'Agenda');
	}
	
   function text_colors() {
      $palette = $this->palette;
      asort($palette); //sort by color name
      return $palette;      
   }

   function color_in_palette( $color ) {
      if ($color) {
         if  (substr($color,0,1) == '#') 
            $color = substr($color,1);
         if(array_key_exists($color, $this->palette)) 
            return '#'. $color;     
      } 
      return $this->get_default('color');
   }


}