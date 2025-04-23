<?php
/**
 * Admin settings + manual “Report IP” form
 * for Wilcosky Stop Forum Spam API plugin.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register the API key setting.
 */
function wilcosky_stop_forum_spam_register_settings() {
    register_setting(
        'wilcosky_stop_forum_spam_settings',
        'wilcosky_stop_forum_spam_api_key',
        array( 'sanitize_callback' => 'sanitize_text_field' )
    );
}
add_action( 'admin_init', 'wilcosky_stop_forum_spam_register_settings' );

/**
 * Add the plugin page under Settings.
 */
function wilcosky_stop_forum_spam_admin_menu() {
    add_options_page(
        __( 'Stop Forum Spam', 'wilcosky-stop-forum-spam' ),
        __( 'Stop Forum Spam', 'wilcosky-stop-forum-spam' ),
        'manage_options',
        'wilcosky-stop-forum-spam',
        'wilcosky_stop_forum_spam_settings_page'
    );
}
add_action( 'admin_menu', 'wilcosky_stop_forum_spam_admin_menu' );

/**
 * Render the settings & report form.
 */
function wilcosky_stop_forum_spam_settings_page() {
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'Stop Forum Spam Settings', 'wilcosky-stop-forum-spam' ); ?></h1>

        <?php
        settings_errors( 'wilcosky_stop_forum_spam_settings' );
        settings_errors( 'wilcosky_sfs_report' );
        ?>

        <form method="post" action="options.php">
            <?php
            settings_fields( 'wilcosky_stop_forum_spam_settings' );
            $api_key = get_option( 'wilcosky_stop_forum_spam_api_key', '' );
            ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">
                        <label for="wilcosky_stop_forum_spam_api_key">
                            <?php esc_html_e( 'API Key (optional)', 'wilcosky-stop-forum-spam' ); ?>
                        </label>
                    </th>
                    <td>
                        <input
                            type="text"
                            id="wilcosky_stop_forum_spam_api_key"
                            name="wilcosky_stop_forum_spam_api_key"
                            value="<?php echo esc_attr( $api_key ); ?>"
                            class="regular-text"
                        />
                    </td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>

        <hr>

        <h2><?php esc_html_e( 'Report an IP to StopForumSpam', 'wilcosky-stop-forum-spam' ); ?></h2>
        <form method="post">
            <?php wp_nonce_field( 'wilcosky_sfs_report_ip_action', 'wilcosky_sfs_report_ip_nonce' ); ?>
            <table class="form-table">
                <tr>
                    <th>
                        <label for="wilcosky_sfs_report_ip_addr">
                            <?php esc_html_e( 'IP Address', 'wilcosky-stop-forum-spam' ); ?>
                        </label>
                    </th>
                    <td>
                        <input
                            type="text"
                            id="wilcosky_sfs_report_ip_addr"
                            name="wilcosky_sfs_report_ip_addr"
                            class="regular-text"
                        />
                    </td>
                </tr>
                <tr>
                    <th>
                        <label for="wilcosky_sfs_report_email">
                            <?php esc_html_e( 'Your Email', 'wilcosky-stop-forum-spam' ); ?>
                        </label>
                    </th>
                    <td>
                        <input
                            type="email"
                            id="wilcosky_sfs_report_email"
                            name="wilcosky_sfs_report_email"
                            value="<?php echo esc_attr( get_option( 'admin_email' ) ); ?>"
                            class="regular-text"
                        />
                    </td>
                </tr>
                <tr>
                    <th>
                        <label for="wilcosky_sfs_report_evidence">
                            <?php esc_html_e( 'Evidence (optional)', 'wilcosky-stop-forum-spam' ); ?>
                        </label>
                    </th>
                    <td>
                        <input
                            type="text"
                            id="wilcosky_sfs_report_evidence"
                            name="wilcosky_sfs_report_evidence"
                            class="regular-text"
                        />
                        <p class="description">
                            <?php esc_html_e( 'Any notes or context for this report.', 'wilcosky-stop-forum-spam' ); ?>
                        </p>
                    </td>
                </tr>
            </table>
            <?php submit_button( __( 'Report IP to StopForumSpam', 'wilcosky-stop-forum-spam' ), 'secondary', 'wilcosky_sfs_report_submit' ); ?>
        </form>
    </div>
    <?php
}

/**
 * Process manual “Report IP” submissions.
 */
function wilcosky_sfs_handle_report_submission() {
    if ( empty( $_POST['wilcosky_sfs_report_submit'] ) ) {
        return;
    }
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
    check_admin_referer( 'wilcosky_sfs_report_ip_action', 'wilcosky_sfs_report_ip_nonce' );

    $ip       = sanitize_text_field( $_POST['wilcosky_sfs_report_ip_addr'] );
    $email    = sanitize_email(     $_POST['wilcosky_sfs_report_email']   );
    $evidence = sanitize_text_field( $_POST['wilcosky_sfs_report_evidence'] );
    $api_key  = get_option( 'wilcosky_stop_forum_spam_api_key', '' );

    if ( empty( $ip ) || empty( $email ) || empty( $api_key ) ) {
        add_settings_error(
            'wilcosky_sfs_report',
            'missing_fields',
            __( 'IP, Email, and API Key are required to report.', 'wilcosky-stop-forum-spam' ),
            'error'
        );
        return;
    }

    $response = wp_remote_post(
        'https://api.stopforumspam.org/add',
        array(
            'body' => array(
                'ip_addr'  => $ip,
                'email'    => $email,
                'evidence' => $evidence,
                'api_key'  => $api_key,
            ),
        )
    );

    if ( is_wp_error( $response ) ) {
        add_settings_error(
            'wilcosky_sfs_report',
            'request_failed',
            $response->get_error_message(),
            'error'
        );
    } else {
        $code = wp_remote_retrieve_response_code( $response );
        if ( 200 === $code ) {
            add_settings_error(
                'wilcosky_sfs_report',
                'report_success',
                __( 'IP successfully reported to StopForumSpam.', 'wilcosky-stop-forum-spam' ),
                'updated'
            );
        } else {
            add_settings_error(
                'wilcosky_sfs_report',
                'unexpected_response',
                sprintf(
                    __( 'Unexpected response code: %d', 'wilcosky-stop-forum-spam' ),
                    $code
                ),
                'error'
            );
        }
    }
}
add_action( 'admin_init', 'wilcosky_sfs_handle_report_submission' );
