<?php
if( !defined( 'ABSPATH') && !defined('WP_UNINSTALL_PLUGIN') ) {
   exit();
}

// clean up the databes before the uninstall plugin

delete_option('ssb_googleplus');
delete_option('ssb_fblike');
delete_option('ssb_twitter');

delete_option('ssb_beforepost');
delete_option('ssb_afterpost');

delete_option('ssb_showfront');
delete_option('ssb_showcategory');
delete_option('ssb_showarchive');
delete_option('ssb_showtag');

delete_option('ssb_beforearchive');
delete_option('ssb_afterarchive');

?>