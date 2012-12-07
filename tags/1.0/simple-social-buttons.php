<?php
 /* 
    Plugin Name: Simple Social Buttons 
    Plugin URI: http://blog.rabinek.pl/ 
    Description: Insert social buttons into posts and archives: Facebook "Like it", Google Plus One "+1" and Twitter share. 
    Author: Paweł Rabinek 
    Version: 1.0 
    Author URI: http://blog.rabinek.pl/simple-social-buttons/
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

function ssb_include_social_js() { ?>

<!-- Simple Social Buttons plugin -->
<script type="text/javascript" src="https://apis.google.com/js/plusone.js"></script>
<script type="text/javascript" src="http://platform.twitter.com/widgets.js"></script>
<script type="text/javascript" src="http://connect.facebook.net/en_US/all.js#xfbml=1"></script>
<!-- End of Simple Social Buttons -->

<?php }


add_action('wp_footer', 'ssb_include_social_js');

function ssb_include_css() { ?>

<!-- Simple Social Buttons style sheet -->
<style type="text/css">
   div.simplesocialbuttons { height: 20px; margin: 10px auto 10px 0; text-align: center; clear: left; } 
   div.simplesocialbutton { float: left; text-align: center; } 
</style>
<!-- End of Simple Social Buttons -->

<?php }

add_action('wp_head', 'ssb_include_css');


//insert the buttons after post contents
function ssb_insert_buttons($content) {

   if(!ssb_where_to_insert()) {
      return $content;
   }
   
   // get post permalink
   $permalink = get_permalink();
   $title = get_the_title();
   
   // get settings from database
   $ssb_googleplus = get_option('ssb_googleplus'); 
   $ssb_fblike = get_option('ssb_fblike'); 
   $ssb_twitter = get_option('ssb_twitter'); 
   
   $ssb_beforepost = get_option('ssb_beforepost'); 
   $ssb_afterpost = get_option('ssb_afterpost'); 

   $ssb_beforepage = get_option('ssb_beforepage'); 
   $ssb_afterpage = get_option('ssb_afterpage'); 
      
   $ssb_beforearchive = get_option('ssb_beforearchive'); 
   $ssb_afterarchive = get_option('ssb_afterarchive'); 


   $ssb_buttonscode = '<div class="simplesocialbuttons">'."\n";
   if($ssb_googleplus) { 
      $ssb_buttonscode .= '<div class="simplesocialbutton"><!-- Google Plus One--><g:plusone size="medium" count="true" href="'.$permalink.'"></g:plusone></div>'."\n";
   }
   if($ssb_fblike) { 
      $ssb_buttonscode .= '<div class="simplesocialbutton"><!-- Facebook like--><div id="fb-root"></div><fb:like href="'.$permalink.'" send="false" layout="button_count" width="100" show_faces="false" action="like" font=""></fb:like></div>'."\n";
   } 
   if($ssb_twitter) { 
      $ssb_buttonscode .= '<div class="simplesocialbutton"><!-- Twitter--><a name="twitter_share" data-count="horizontal" href="http://twitter.com/share" data-text="'.$title.'" data-url="'.$permalink.'" class="twitter-share-button" rel="nofollow"></a></div>'."\n";
   }
   $ssb_buttonscode .= '</div>'."\n";
   
   
   if(is_single()) {
      if($ssb_beforepost) {
         $content = $ssb_buttonscode.$content;
      }
      if($ssb_afterpost) {
         $content = $content.$ssb_buttonscode;
      }
   }else if(is_page()) { 
      if($ssb_beforepage) {
         $content = $ssb_buttonscode.$content;
      }
      if($ssb_afterpage) {
         $content = $content.$ssb_buttonscode;
      }   
   }else{
      if($ssb_beforearchive) {
         $content = $ssb_buttonscode.$content;
      }
      if($ssb_afterarchive) {
         $content = $content.$ssb_buttonscode;
      }   
   }
      
   return $content;

   
}

function ssb_where_to_insert()
{

   // display on single post?
   $ssb_beforepost = get_option('ssb_beforepost'); 
   $ssb_afterpost = get_option('ssb_afterpost'); 
   if(is_single() && ($ssb_beforepost || $ssb_afterpost))
   {
      return true;  
   }

   // display on single page?
   $ssb_beforepage = get_option('ssb_beforepage'); 
   $ssb_afterpage = get_option('ssb_afterpage'); 
   if(is_page() && ($ssb_beforepage || $ssb_afterpage))
   {
      return true;  
   }
   
   // display on frontpage, categories, archives, tags?
   $ssb_showfront = get_option('ssb_showfront'); 
   $ssb_showcategory = get_option('ssb_showcategory'); 
   $ssb_showarchive = get_option('ssb_showarchive'); 
   $ssb_showtag = get_option('ssb_showtag'); 

   if(is_front_page() && $ssb_showfront)
   {
      return true;  
   }   

   if(is_category() && $ssb_showcategory)
   {
      return true;  
   }  

   if(is_date() && $ssb_showarchive)
   {
      return true;  
   }     

   if(is_tag() && $ssb_showtag)
   {
      return true;  
   }
   return false;         
}

add_filter ('the_content', 'ssb_insert_buttons');  
add_filter ('the_excerpt', 'ssb_insert_buttons');  

function ssb_menu()
{
   global $wpdb;
   include 'ssb-admin.php';
}

function ssb_admin_actions()
{
    add_options_page("Simple Social Buttons ", "Simple Social Buttons ", 1, "simple-social-buttons", "ssb_menu");
}
 
add_action('admin_menu', 'ssb_admin_actions');



add_filter('plugin_action_links', 'ssb_plugin_action_links', 10, 2);
 
function ssb_plugin_action_links($links, $file) {
    static $this_plugin;
 
    if (!$this_plugin) {
        $this_plugin = plugin_basename(__FILE__);
    }
 
    if ($file == $this_plugin) {
        // The "page" query string value must be equal to the slug
        // of the Settings admin page we defined earlier, which in
        // this case equals "myplugin-settings".
        $settings_link = '<a href="' . get_bloginfo('wpurl') . '/wp-admin/admin.php?page=simple-social-buttons">'.__("Settings", "simplesocialbuttons").'</a>';
        array_unshift($links, $settings_link);
    }
 
    return $links;
}

// install and default settings
function ssb_set_defaults()
{
   add_option( "ssb_googleplus", "1", "", "yes" );
   add_option( "ssb_fblike", "1", "", "yes" );
   add_option( "ssb_twitter", "1", "", "yes" );
   add_option( "ssb_beforepost", "1", "", "yes" );     
}
register_activation_hook( __FILE__, 'ssb_set_defaults' );

function ssb_init()
{
   load_plugin_textdomain( 'simplesocialbuttons', '', dirname(plugin_basename( __FILE__ ))."/lang" );
}
add_action( 'init', 'ssb_init' );

?>