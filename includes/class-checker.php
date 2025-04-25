<?php
if ( ! class_exists( 'Wilcosky_Stop_Forum_Spam_Checker' ) ) {
    class Wilcosky_Stop_Forum_Spam_Checker {

        /**
         * Check visitor IP vs. StopForumSpam (with caching).
         * Skip logged-in users and admin pages.
         */
        public function maybe_block_ip() {
            if ( is_user_logged_in() || is_admin() ) {
                return;
            }

            // Allow testing via ?testsfs=IP
            $ip = isset( $_GET['testsfs'] )
                ? sanitize_text_field( $_GET['testsfs'] )
                : ( $_SERVER['REMOTE_ADDR'] ?? '' );

            if ( ! $ip ) {
                error_log( 'No IP address found to check.' );
                if ( WP_DEBUG ) {
                    echo 'Error: No IP address found to check.';
                }
                return;
            }

            // Cache key for this IP
            $transient_key = 'wilcosky_sfs_' . md5( $ip );
            $result = get_transient( $transient_key );

            if ( false === $result ) {
                // First check: call the API with &confidence
                $url = sprintf(
                    'http://api.stopforumspam.org/api?ip=%s&json&confidence',
                    rawurlencode( $ip )
                );
                $response = wp_remote_get( $url );

                if ( is_wp_error( $response ) ) {
                    $error_message = $response->get_error_message();
                    error_log( 'StopForumSpam API error: ' . $error_message );
                    if ( WP_DEBUG ) {
                        echo 'API error: ' . esc_html( $error_message );
                    }
                    return; // Fail open
                }

                $body = wp_remote_retrieve_body( $response );
                $data = json_decode( $body, true );

                if ( json_last_error() !== JSON_ERROR_NONE ) {
                    $msg = json_last_error_msg();
                    error_log( 'JSON decode error: ' . $msg );
                    if ( WP_DEBUG ) {
                        echo 'JSON decode error: ' . esc_html( $msg );
                    }
                    return;
                }

                $result = array(
                    'appears'    => $data['ip']['appears']    ?? 0,
                    'confidence' => $data['ip']['confidence'] ?? 0,
                );

                set_transient( $transient_key, $result, HOUR_IN_SECONDS );
            }

            // Block if SFS says IP appears and confidence is high
            if ( intval( $result['appears'] ) === 1 && floatval( $result['confidence'] ) >= 60.0 ) {
                wp_die(
                    esc_html__( 'Access denied: Your IP has been flagged as suspicious.', 'wilcosky-stop-forum-spam' ),
                    esc_html__( 'Blocked', 'wilcosky-stop-forum-spam' ),
                    array( 'response' => 403 )
                );
            }
        }
    }
}
