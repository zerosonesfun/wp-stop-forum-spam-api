<?php
/**
 * Plugin Name:     Wilcosky Stop Forum Spam API
 * Description:     Blocks visitors whose IPs appear in the StopForumSpam database (with confidence scoring), reports blocks to Wordfence Live Traffic, and offers a manual “report IP” form.
 * Version:         1.0
 * Author:          Billy Wilcosky
 * Text Domain:     wilcosky-stop-forum-spam
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'WILCOSKY_SFS_VERSION' ) ) {
    define( 'WILCOSKY_SFS_VERSION', '1.0' );
}

// Core logic
require_once plugin_dir_path( __FILE__ ) . 'includes/class-checker.php';

// Admin UI + manual report
require_once plugin_dir_path( __FILE__ ) . 'admin/settings-page.php';

// Front-end check on every request (priority 1)
add_action( 'init', function() {
    $checker = new Wilcosky_Stop_Forum_Spam_Checker();
    $checker->maybe_block_ip();
}, 1 );
