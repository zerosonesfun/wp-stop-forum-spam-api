<?php
/**
 * Admin settings & manual “Report IP” form
 * for the Wilcosky Stop Forum Spam API plugin.
 */

defined( 'ABSPATH' ) || exit;

/** Register the API key setting. */
function wilcosky_stop_forum_spam_register_settings() {
    register_setting(
        'wilcosky_stop_forum_spam_settings',
        'wilcosky_stop_forum_spam_api_key',
        [ 'sanitize_callback' => 'sanitize_text_field' ]
    );
}
add_action( 'admin_init', 'wilcosky_stop_forum_spam_register_settings' );

/** Add the Settings → Stop Forum Spam page. */
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

/** Render the settings form and manual report form. */
function wilcosky_stop_forum_spam_settings_page() {
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'Stop Forum Spam Settings', 'wilcosky-stop-forum-spam' ); ?></h1>

        <?php
        settings_errors( 'wilcosky_stop_forum_spam_settings' );
        settings_errors( 'wilcosky_sfs_report' );
        ?>

        <!-- API Key Form -->
        <form method="post" action="options.php">
            <?php
            settings_fields( 'wilcosky_stop_forum_spam_settings' );
            $api_key = get_option( 'wilcosky_stop_forum_spam_api_key', '' );
            ?>
            <table class="form-table">
                <tr>
                    <th>
                        <label for="wilcosky_stop_forum_spam_api_key">
                            <?php esc_html_e( 'API Key (required)', 'wilcosky-stop-forum-spam' ); ?>
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
                        <p class="description">
                            <?php esc_html_e( 'Your StopForumSpam API key, required for reporting.', 'wilcosky-stop-forum-spam' ); ?>
                        </p>
                    </td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>

        <hr>

        <!-- Manual Report Form -->
        <h2><?php esc_html_e( 'Report a Spammer to StopForumSpam', 'wilcosky-stop-forum-spam' ); ?></h2>
        <form
            method="post"
            action="<?php echo esc_url( admin_url( 'options-general.php?page=wilcosky-stop-forum-spam' ) ); ?>"
        >
            <?php wp_nonce_field( 'wilcosky_sfs_report_ip_action', 'wilcosky_sfs_report_ip_nonce' ); ?>
            <table class="form-table">
                <tr>
                    <th>
                        <label for="wilcosky_sfs_report_username">
                            <?php esc_html_e( 'Spammer Username', 'wilcosky-stop-forum-spam' ); ?>
                        </label>
                    </th>
                    <td>
                        <input
                            name="wilcosky_sfs_report_username"
                            type="text"
                            id="wilcosky_sfs_report_username"
                            class="regular-text"
                        />
                    </td>
                </tr>
                <tr>
                    <th>
                        <label for="wilcosky_sfs_report_email">
                            <?php esc_html_e( 'Spammer Email', 'wilcosky-stop-forum-spam' ); ?>
                        </label>
                    </th>
                    <td>
                        <input
                            name="wilcosky_sfs_report_email"
                            type="email"
                            id="wilcosky_sfs_report_email"
                            class="regular-text"
                        />
                    </td>
                </tr>
                <tr>
                    <th>
                        <label for="wilcosky_sfs_report_ip_addr">
                            <?php esc_html_e( 'Spammer IP Address', 'wilcosky-stop-forum-spam' ); ?>
                        </label>
                    </th>
                    <td>
                        <input
                            name="wilcosky_sfs_report_ip_addr"
                            type="text"
                            id="wilcosky_sfs_report_ip_addr"
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
                            name="wilcosky_sfs_report_evidence"
                            type="text"
                            id="wilcosky_sfs_report_evidence"
                            class="regular-text"
                        />
                        <p class="description">
                            <?php esc_html_e( 'Context or URL proving the spammer activity.', 'wilcosky-stop-forum-spam' ); ?>
                        </p>
                    </td>
                </tr>
            </table>

            <?php submit_button( __( 'Report Spammer', 'wilcosky-stop-forum-spam' ), 'secondary', 'wilcosky_sfs_report_submit' ); ?>
        </form>
    </div>
    <?php
}

/** Handle the manual report submission including username. */
function wilcosky_sfs_handle_report_submission() {
    if ( empty( $_POST['wilcosky_sfs_report_submit'] ) ) {
        return;
    }
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
    check_admin_referer( 'wilcosky_sfs_report_ip_action', 'wilcosky_sfs_report_ip_nonce' );

    // Sanitize inputs
    $username = sanitize_text_field( $_POST['wilcosky_sfs_report_username'] );
    $email    = sanitize_email(     $_POST['wilcosky_sfs_report_email']    );
    $ip       = sanitize_text_field( $_POST['wilcosky_sfs_report_ip_addr']  );
    $evidence = sanitize_text_field( $_POST['wilcosky_sfs_report_evidence'] );
    $api_key  = get_option( 'wilcosky_stop_forum_spam_api_key', '' );

    // All five fields are required except evidence
    if ( empty( $username ) || empty( $email ) || empty( $ip ) || empty( $api_key ) ) {
        add_settings_error(
            'wilcosky_sfs_report',
            'missing_fields',
            __( 'Error: Username, Email, IP, and API Key are required.', 'wilcosky-stop-forum-spam' ),
            'error'
        );
        return;
    }

    // Build a RFC3986-encoded query string including username :contentReference[oaicite:1]{index=1}
    $query = http_build_query(
        [
            'username' => $username,
            'email'    => $email,
            'ip_addr'  => $ip,
            'evidence' => $evidence,
            'api_key'  => $api_key,
        ],
        '',
        '&',
        PHP_QUERY_RFC3986
    );

    // POST with proper headers
    $response = wp_remote_post(
        'https://www.stopforumspam.com/add.php',
        [
            'headers' => [
                'Content-Type' => 'application/x-www-form-urlencoded; charset=utf-8',
            ],
            'body'    => $query,
            'timeout' => 10,
        ]
    );

    // Network error?
    if ( is_wp_error( $response ) ) {
        add_settings_error(
            'wilcosky_sfs_report',
            'request_failed',
            sprintf(
                __( 'Network error: %s', 'wilcosky-stop-forum-spam' ),
                esc_html( $response->get_error_message() )
            ),
            'error'
        );
        return;
    }

    $code        = wp_remote_retrieve_response_code( $response );
    $body        = wp_remote_retrieve_body( $response );
    $retry_after = wp_remote_retrieve_header( $response, 'retry-after' );

    // Handle specific status codes with clear messages...
    if ( 403 === $code && stripos( $body, 'rate limit' ) !== false ) {
        add_settings_error(
            'wilcosky_sfs_report',
            'rate_limited',
            __( '403 Rate Limit Exceeded: Please wait 24 hours.', 'wilcosky-stop-forum-spam' ),
            'error'
        );
        return;
    }
    if ( 503 === $code ) {
        $msg = $retry_after
            ? sprintf(
                __( '503 Unavailable: Retry after %s.', 'wilcosky-stop-forum-spam' ),
                date_i18n( get_option('time_format'), time() + intval( $retry_after ) )
              )
            : __( '503 Unavailable: Service down, please try later.', 'wilcosky-stop-forum-spam' );
        add_settings_error( 'wilcosky_sfs_report', 'service_unavailable', $msg, 'error' );
        return;
    }
    if ( 200 === $code ) {
        add_settings_error(
            'wilcosky_sfs_report',
            'report_success',
            __( 'Success: Spammer reported to StopForumSpam.', 'wilcosky-stop-forum-spam' ),
            'updated'
        );
        return;
    }

    // Fallback for other unexpected responses
    add_settings_error(
        'wilcosky_sfs_report',
        'unexpected_response',
        sprintf(
            __( 'Unexpected %1$d: %2$s', 'wilcosky-stop-forum-spam' ),
            intval( $code ),
            esc_html( substr( $body, 0, 200 ) )
        ),
        'error'
    );
}
add_action( 'admin_init', 'wilcosky_sfs_handle_report_submission' );
