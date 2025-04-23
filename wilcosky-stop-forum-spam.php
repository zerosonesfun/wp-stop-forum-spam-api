<?php
/**
 * Plugin Name:     Wilcosky Stop Forum Spam API
 * Description:     Blocks visitors whose IPs are found in the StopForumSpam.com database, using confidence scoring and reporting to Wordfence Live Traffic. 
 * Version:         1.0
 * Author:          Billy Wilcosky
 * Text Domain:     wilcosky-stop-forum-spam
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'WILCOSKY_SFS_VERSION' ) ) {
    define( 'WILCOSKY_SFS_VERSION', '1.0' );
}

// Core checker class
require_once plugin_dir_path( __FILE__ ) . 'includes/class-checker.php';

// Admin settings + report page
require_once plugin_dir_path( __FILE__ ) . 'admin/settings-page.php';

// On every frontend hit (except admins), run the IP check
add_action( 'init', function() {
    if ( ! is_admin() && ! current_user_can( 'manage_options' ) ) {
        $checker = new Wilcosky_Stop_Forum_Spam_Checker();
        $checker->maybe_block_ip();
    }
} );
