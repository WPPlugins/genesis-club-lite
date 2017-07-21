<?php
/*
 * Plugin Name: Genesis Club Lite
 * Plugin URI: http://www.diywebmastery.com/plugins/genesis-club-lite/
 * Description: Over 80 powerful features in 11 pluggable modules for Genesis child themes.
 * Version: 1.17
 * Author: Russell Jamieson
 * Author URI: http://www.diywebmastery.com/about
 * License: GPLv2+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 */
 
if (!defined('GENESIS_CLUB_VERSION')) define('GENESIS_CLUB_VERSION','1.17');
if (!defined('GENESIS_CLUB_FRIENDLY_NAME')) define('GENESIS_CLUB_FRIENDLY_NAME', 'Genesis Club Lite') ;
if (!defined('GENESIS_CLUB_PLUGIN_NAME')) define('GENESIS_CLUB_PLUGIN_NAME', plugin_basename(dirname(__FILE__))) ;
if (!defined('GENESIS_CLUB_PLUGIN_PATH')) define('GENESIS_CLUB_PLUGIN_PATH', GENESIS_CLUB_PLUGIN_NAME.'/main.php');
if (!defined('GENESIS_CLUB_ICON')) define('GENESIS_CLUB_ICON', 'dashicons-welcome-widgets-menus');
if (!defined('GENESIS_CLUB_DOMAIN')) define('GENESIS_CLUB_DOMAIN', 'GENESIS_CLUB_DOMAIN') ;  //text domain
if (!defined('GENESIS_CLUB_PRO_URL')) define('GENESIS_CLUB_PRO_URL','https://genesisclubpro.com');
if (!defined('GENESIS_CLUB_SUPPORT_URL')) define('GENESIS_CLUB_SUPPORT_URL','https://genesisclubpro.com/support/');
if (!defined('GENESIS_CLUB_NEWS')) define('GENESIS_CLUB_NEWS', 'http://www.diywebmastery.com/tags/genesis-newsfeed/feed/?images=1&featured_only=1');
if (!defined('DIYWEBMASTERY_NEWS')) define('DIYWEBMASTERY_NEWS', 'http://www.diywebmastery.com/tags/newsfeed/feed/?images=1&featured_only=1');
require_once(dirname(__FILE__) . '/classes/class-plugin.php');
Genesis_Club_Plugin::get_instance();
