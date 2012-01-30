Example usage: 

<?php
//in your functions.php or plugin loader 
include_once('wpcachedb.php');
$GLOBALS['wpdb'] = new WPCacheDB( DB_USER, DB_PASSWORD, DB_NAME, DB_HOST );

//elsewhere in you code.
global $wpdb;
$query = $wpdb->prepare("SELECT * FROM {$wpdb->prefix}posts WHERE ID = %d ",2);
//by running the cache method, you automatically check for cached results of this query and create a cache if one does not exist
$posts = $wpdb->cache('get_row',$query,'OBJECT','posts');


//Don't forget to clear the cache at the appropriate time
add_action('save_post','my_clearing_function');
function my_clearing_function($post_id) {
    WPCacheDB::clear('posts');
} 
?>