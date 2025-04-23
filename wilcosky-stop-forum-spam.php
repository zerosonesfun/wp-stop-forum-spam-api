<?php
/**
 * Plugin Name:     Wilcosky Stop Forum Spam API
 * Description:     Blocks visitors whose IPs appear in the StopForumSpam database (with confidence scoring), reports to Wordfence Live Traffic, and offers a manual “report IP” form.
 * Version:         1.1
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

/**
 * Clean up on uninstall: remove options and transients.
 */
function wilcosky_stop_forum_spam_uninstall() {
    // Remove stored API key
    delete_option( 'wilcosky_stop_forum_spam_api_key' );

    // Delete all our transients (both value and timeout)
    global $wpdb;
    $options_table = $wpdb->options;
    $like_value   = $wpdb->esc_like( '_transient_wilcosky_sfs_' ) . '%';
    $like_timeout = $wpdb->esc_like( '_transient_timeout_wilcosky_sfs_' ) . '%';

    $wpdb->query(
        $wpdb->prepare(
            "DELETE FROM {$options_table} WHERE option_name LIKE %s OR option_name LIKE %s",
            $like_value,
            $like_timeout
        )
    );
}

// Register the uninstall hook
register_uninstall_hook( __FILE__, 'wilcosky_stop_forum_spam_uninstall' );
