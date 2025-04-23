<?php
if ( ! function_exists( 'wilcosky_stop_forum_spam_register_settings' ) ) {

    /**
     * Handle manual “Report IP” submissions.
     */
    function wilcosky_sfs_handle_report_submission() {
        // Bail if not our form
        if ( empty( $_POST['wilcosky_sfs_report_submit'] ) ) {
            return;
        }
        // Permissions + security
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        check_admin_referer( 'wilcosky_sfs_report_ip_action', 'wilcosky_sfs_report_ip_nonce' );

        // Sanitize inputs
        $ip       = sanitize_text_field( $_POST['wilcosky_sfs_report_ip_addr'] );
        $email    = sanitize_email( $_POST['wilcosky_sfs_report_email'] );
        $evidence = sanitize_text_field( $_POST['wilcosky_sfs_report_evidence'] );
        $api_key  = get_option( 'wilcosky_stop_forum_spam_api_key', '' );

        // Must have IP, email, API key
        if ( empty( $ip ) || empty( $email ) || empty( $api_key ) ) {
            add_settings_error(
                'wilcosky_sfs_report',
                'missing_fields',
                __( 'IP, Email, and API Key are all required to report.', 'wilcosky-stop-forum-spam' ),
                'error'
            );
            return;
        }

        // Build query
        $params = [
            'ip_addr'  => $ip,
            'email'    => $email,
            'evidence' => $evidence,
            'api_key'  => $api_key,
        ];
        $url = 'https://www.stopforumspam.com/add?' . http_build_query( $params );

        $response = wp_remote_get( $url );
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
                    'unexpected_code',
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


    /**
     * Render the Settings page.
     */
    function wilcosky_stop_forum_spam_settings_page() {
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Stop Forum Spam Settings', 'wilcosky-stop-forum-spam' ); ?></h1>

            <?php
            // Show any settings or report notices
            settings_errors( 'wilcosky_stop_forum_spam_settings' );
            settings_errors( 'wilcosky_sfs_report' );
            ?>

            <form method="post" action="options.php">
                <?php
                // Standard API Key field
                settings_fields( 'wilcosky_stop_forum_spam_settings' );
                do_settings_sections( 'wilcosky_stop_forum_spam' );
                submit_button();
                ?>
            </form>

            <hr>

            <h2><?php esc_html_e( 'Report an IP to StopForumSpam', 'wilcosky-stop-forum-spam' ); ?></h2>
            <form method="post">
                <?php wp_nonce_field( 'wilcosky_sfs_report_ip_action', 'wilcosky_sfs_report_ip_nonce' ); ?>

                <table class="form-table">
                    <tr>
                        <th><label for="wilcosky_sfs_report_ip_addr"><?php esc_html_e( 'IP Address', 'wilcosky-stop-forum-spam' ); ?></label></th>
                        <td><input name="wilcosky_sfs_report_ip_addr" type="text" id="wilcosky_sfs_report_ip_addr" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><label for="wilcosky_sfs_report_email"><?php esc_html_e( 'Your Email', 'wilcosky-stop-forum-spam' ); ?></label></th>
                        <td>
                            <input name="wilcosky_sfs_report_email" type="email" id="wilcosky_sfs_report_email" 
                                value="<?php echo esc_attr( get_option( 'admin_email' ) ); ?>" class="regular-text">
                        </td>
                    </tr>
                    <tr>
                        <th><label for="wilcosky_sfs_report_evidence"><?php esc_html_e( 'Evidence (optional)', 'wilcosky-stop-forum-spam' ); ?></label></th>
                        <td>
                            <input name="wilcosky_sfs_report_evidence" type="text" id="wilcosky_sfs_report_evidence" class="regular-text">
                            <p class="description"><?php esc_html_e( 'Any notes or context for this report.', 'wilcosky-stop-forum-spam' ); ?></p>
                        </td>
                    </tr>
                </table>

                <?php submit_button( esc_html__( 'Report IP to StopForumSpam', 'wilcosky-stop-forum-spam' ), 'secondary', 'wilcosky_sfs_report_submit' ); ?>
            </form>
        </div>
        <?php
    }

    /**
     * Hook the page into Settings → Stop Forum Spam
     */
    function wilcosky_stop_forum_spam_admin_menu() {
        add_options_page(
            __( 'Stop Forum Spam', 'wilcosky-stop-forum-spam' ),
            __( 'Stop Forum Spam', 'wilcosky-stop-forum-spam' ),
            'manage_options',
            'wilcosky_stop_forum_spam',
            'wilcosky_stop_forum_spam_settings_page'
        );
    }
    add_action( 'admin_menu', 'wilcosky_stop_forum_spam_admin_menu' );


    /**
     * Register only the API key setting
     */
    function wilcosky_stop_forum_spam_register_settings() {
        register_setting(
            'wilcosky_stop_forum_spam_settings',
            'wilcosky_stop_forum_spam_api_key',
            [ 'sanitize_callback' => 'sanitize_text_field' ]
        );

        add_settings_section(
            'wilcosky_stop_forum_spam_main',
            __( 'Main Settings', 'wilcosky-stop-forum-spam' ),
            '__return_null',
            'wilcosky_stop_forum_spam'
        );

        add_settings_field(
            'wilcosky_stop_forum_spam_api_key',
            __( 'API Key (optional)', 'wilcosky-stop-forum-spam' ),
            function() {
                $v = get_option( 'wilcosky_stop_forum_spam_api_key', '' );
                printf( '<input type="text" name="wilcosky_stop_forum_spam_api_key" value="%s" class="regular-text">',
                    esc_attr( $v )
                );
            },
            'wilcosky_stop_forum_spam',
            'wilcosky_stop_forum_spam_main'
        );
    }
    add_action( 'admin_init', 'wilcosky_stop_forum_spam_register_settings' );
}
