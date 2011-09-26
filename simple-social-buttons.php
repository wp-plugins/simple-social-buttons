<?php
 /*
    Plugin Name: Simple Social Buttons
    Plugin URI: http://blog.rabinek.pl/simple-social-buttons-wordpress/
    Description: Insert social buttons into posts and archives: Facebook "Like it", Google Plus One "+1" and Twitter share.
    Author: Paweł Rabinek
    Version: 1.3
    Author URI: http://blog.rabinek.pl/
*/

/*  Copyright 2011, Paweł Rabinek (xradar)  (email : pawel@rabinek.pl)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

/**
 * HACK: Converted to class, added buttons ordering, improve saving settings
 * @author Fabian Wolf
 * @link http://usability-idealist.de/
 * @since 1.3
 * @requires PHP 5
 */


class SimpleSocialButtonsPR {
   var $pluginName = 'Simple Social Buttons';
	var $pluginVersion = '1.3';
   var $pluginPrefix = 'ssb_pr_';
   
   // plugin default settings
   var $pluginDefaultSettings = array(
			'googleplus' => '1',
         'fblike' => '2',
			'twitter' => '3',
			'beforepost' => '1',
			'afterpost' => '0',
			'beforepage' => '1',
			'afterpage' => '0',
			'beforearchive' => '0',
			'afterarchive' => '0'
		);
   
   // defined buttons
   var $arrKnownButtons = array('fblike', 'googleplus', 'twitter');


	/**
	 * Constructor
	 */

	function __construct() {
		register_activation_hook( __FILE__, array(&$this, 'plugin_install') );
		register_deactivation_hook( __FILE__, array(&$this, 'plugin_uninstall') );

		/**
		 * Action hooks
		 */

		/**
		 * basic init
		 */
		add_action( 'init', 		array(&$this, 'plugin_init') );

		// get settings
		$currentSettings = $this->get_settings();

		// social JS + CSS data
		add_action( 'wp_footer', 	array(&$this, 'include_social_js') );
		if(!isset($currentSettings['override_css'])) {
			add_action( 'wp_head',		array(&$this, 'include_css') );
		}


		/**
		 * Filter hooks
		 */

		add_filter( 'the_content', 	array(&$this, 'insert_buttons') );
		add_filter( 'the_excerpt',	array(&$this, 'insert_buttons') );
	}

	function plugin_init() {
   		load_plugin_textdomain( 'simplesocialbuttons', '', dirname( plugin_basename( __FILE__ ) ).'/lang' );
	}

	/**
	 * Both avoids time-wasting https calls AND provides better SSL-protection if the current server is accessed using HTTPS
	 */

	public function get_current_http( $echo = true ) {
		$return = 'http' . (strtolower($_SERVER['HTTPS']) == 'on' ? 's' : '') . '://';

		if($echo != false) {
			echo $return;
			return;
		}

		return $return;
	}

	function include_social_js() {
		$lang = get_bloginfo('language');
		$lang_g = strtolower(substr($lang, 0, 2));
		$lang_fb = str_replace('-', '_', $lang);
?>

<!-- Simple Social Buttons plugin -->
<script type="text/javascript" src="<?php $this->get_current_http(); ?>apis.google.com/js/plusone.js">
//<![CDATA[
   {lang: '<?php echo $lang_g; ?>'}
//]]>
</script>
<script type="text/javascript" src="<?php $this->get_current_http(); ?>platform.twitter.com/widgets.js"></script>
<script type="text/javascript" src="<?php $this->get_current_http(); ?>connect.facebook.net/<?php echo $lang_fb; ?>/all.js#xfbml=1"></script>
<!-- /End of Simple Social Buttons -->

<?php
	}


	function include_css() {
?>

<!-- Simple Social Buttons style sheet -->
<style type="text/css">
   div.simplesocialbuttons { height: 20px; margin: 10px auto 10px 0; text-align: center; clear: left; }
   div.simplesocialbutton { float: left; text-align: center; }
</style>
<!-- End of Simple Social Buttons -->

<?php
	}

	/**
	 * Called when installing = activating the plugin
	 */

	function plugin_install() {
		$defaultSettings = $this->check_old_settings();

		/**
		 * @see http://codex.wordpress.org/Function_Reference/add_option
		 * @param string $name 			Name of the option to be added. Use underscores to separate words, and do not use uppercase - this is going to be placed into the database.
		 * @param mixed $value			Value for this option name. Limited to 2^32 bytes of data.
		 * @param string $deprecated	Deprecated in WordPress Version 2.3.
		 * @param string $autoload		Should this option be automatically loaded by the function wp_load_alloptions() (puts options into object cache on each page load)? Valid values: yes or no. Default: yes
		 */
		add_option( $this->pluginPrefix . 'settings', $defaultSettings, '', 'yes' );
		add_option( $this->pluginPrefix . 'version', $this->pluginVersion, '', 'yes' ); // for backward-compatiblity checks

	}

	/**
	 * Backward compatiblity for newer versions
	 */

	function check_old_settings() {
		$return = $this->pluginDefaultSettings;

		$oldSettings = get_option( $this->pluginPrefix . 'settings', array() );

		if( !empty($oldSettings) && is_array($oldSettings) != false) {
			$return = wp_parse_args( $oldSettings, $this->pluginDefaultSettings );
		}

		return $return;
	}

   /**
    * Plugin unistall and database clean up
    */
           
	function plugin_uninstall() {
		if( !defined( 'ABSPATH') && !defined('WP_UNINSTALL_PLUGIN') ) {
			exit();
		}

		delete_option( $this->pluginPrefix . 'settings' );
		delete_option( $this->pluginPrefix . 'version' );

	}


   /** 
    * Get settings from database
    */
           
	public function get_settings() {
		$return = get_option($this->pluginPrefix . 'settings' );
		if(empty($return) != false) {
			$return = $this->pluginDefaultSettings;
		}

		return $return;
	}

	/**
	 * Update settings 
	 */
   function update_settings( $newSettings = array() ) {
		$return = false;

		// compile settings
		$currentSettings = $this->get_settings();

		/**
		 * Compile settings array
		 * @see http://codex.wordpress.org/Function_Reference/wp_parse_args
		 * @param mixed $args
		 * @param mixed $defaults
		 */

		$updatedSettings = wp_parse_args( $newSettings, $currentSettings );

		if($currentSettings != $updatedSettings ) {
			$return = update_option( $this->pluginPrefix . 'settings', $newSettings );
		}

		return $return;
	}

   /**
    * Returns true on pages where buttons should be shown
    */
           
	function where_to_insert() {
		$return = false;
		// get settings from database
		$settings = $this->get_settings();

		extract( $settings, EXTR_PREFIX_ALL, 'ssb' );

		// display on single post?
		if(is_single() && ($ssb_beforepost || $ssb_afterpost)) {
			$return = true;
		}

		// display on single page?
		if(is_page() && ($ssb_beforepage || $ssb_afterpage)) {
			$return = true;
		}

		// display on frontpage?
		if(is_front_page() && $ssb_showfront) {
			$return = true;
		}

      // display on category archive?
		if(is_category() && $ssb_showcategory) {
			$return = true;
		}

      // display on date archive?
		if(is_date() && $ssb_showarchive)
		{
			$return = true;
		}

      // display on tag archive?
		if(is_tag() && $ssb_showtag) {
			$return = true;
		}
		return $return;
	}
	
   /**
	 * Insert the buttons to the content
	 */
	function insert_buttons($content) {
		
		// Insert or  not?
      if(!$this->where_to_insert() ) {
			return $content;
		}
		
		// define empty buttons code to use
		$ssb_buttonscode = ''; 

		// get post permalink and title
		$permalink = get_permalink();
		$title = get_the_title();

		// get settings from database
		$settings = $this->get_settings();

		extract( $settings, EXTR_PREFIX_ALL, 'ssb' );

		/**
		 * Sorting the buttons 
		 */

		foreach($this->arrKnownButtons as $button_name) {
			if(!empty($settings[$button_name]) ) {
				$arrButtons[$button_name] = $settings[$button_name];
			}
		}
		asort($arrButtons);

		foreach($arrButtons as $button_name => $button_sort) {
			switch($button_name) {
				case 'googleplus':
					$arrButtonsCode[] = '<div class="simplesocialbutton ssb-button-googleplus"><!-- Google Plus One--><g:plusone size="medium" count="true" href="'.$permalink.'"></g:plusone></div>';
					break;
				case 'fblike':
					$arrButtonsCode[] = '<div class="simplesocialbutton ssb-button-fblike"><!-- Facebook like--><div id="fb-root"></div><fb:like href="'.$permalink.'" send="false" layout="button_count" width="100" show_faces="false" action="like" font=""></fb:like></div>';
					break;
				case 'twitter':
					$arrButtonsCode[] = '<div class="simplesocialbutton ssb-buttom-twitter"><!-- Twitter--><a name="twitter_share" data-count="horizontal" href="http://twitter.com/share" data-text="'.$title.'" data-url="'.$permalink.'" class="twitter-share-button" rel="nofollow"></a></div>';
					break;
			}
		}


		if(isset($arrButtonsCode) != false) {
			$ssb_buttonscode = '<div class="simplesocialbuttons">'."\n";
			$ssb_buttonscode .= implode("\n", $arrButtonsCode) . "\n";
			$ssb_buttonscode .= '</div>'."\n";
		}

		if(is_single()) {
			if($ssb_beforepost) {
				$content = $ssb_buttonscode.$content;
			}
			if($ssb_afterpost) {
				$content = $content.$ssb_buttonscode;
			}
		} else if(is_page()) {
			if($ssb_beforepage) {
				$content = $ssb_buttonscode.$content;
			}
			if($ssb_afterpage) {
				$content = $content.$ssb_buttonscode;
			}
		} else {
			if($ssb_beforearchive) {
				$content = $ssb_buttonscode.$content;
			}
			if($ssb_afterarchive) {
				$content = $content.$ssb_buttonscode;
			}
		}

		return $content;

	}


} // end class


/**
 * Admin class
 *
 * Gets only initiated if this plugin is called inside the admin section ;)
 */

class SimpleSocialButtonsPR_Admin extends SimpleSocialButtonsPR {

	function __construct() {
		parent::__construct();

		add_action('admin_menu', array(&$this, 'admin_actions') );
		add_filter('plugin_action_links', array(&$this, 'plugin_action_links'), 10, 2 );
	}

	public function admin_actions() {
    	add_options_page('Simple Social Buttons ', 'Simple Social Buttons ', 1, 'simple-social-buttons', array(&$this, 'admin_page') );
	}

	public function admin_page() {
		global $wpdb;

		include dirname( __FILE__  ).'/ssb-admin.php';
	}



	public function plugin_action_links($links, $file) {
		static $this_plugin;

		if (!$this_plugin) {
			$this_plugin = plugin_basename(__FILE__);
		}

		if ($file == $this_plugin) {
			$settings_link = '<a href="' . get_bloginfo('wpurl') . '/wp-admin/options-general.php?page=simple-social-buttons">'.__('Settings').'</a>';
			array_unshift($links, $settings_link);
		}

		return $links;
	}
}

if(is_admin() ) {
	$_ssb_pr = new SimpleSocialButtonsPR_Admin();
} else {
	$_ssb_pr = new SimpleSocialButtonsPR();
}

?>