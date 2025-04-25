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

            // Check if the 'testsfs' query parameter is set
            $ip = isset( $_GET['testsfs'] ) ? sanitize_text_field( $_GET['testsfs'] ) : ( $_SERVER['REMOTE_ADDR'] ?? '' );

            if ( ! $ip ) {
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
                    return; // API down? Fail open.
                }

                $body = wp_remote_retrieve_body( $response );
                $data = json_decode( $body, true );

                $result = array(
                    'appears'    => $data['ip']['appears']    ?? 0,
                    'confidence' => $data['ip']['confidence'] ?? 0,
                );

                // Cache for 1 hour
                set_transient( $transient_key, $result, HOUR_IN_SECONDS );
            }

            // If marked in database & high confidence → block
            if ( intval( $result['appears'] ) === 1 && floatval( $result['confidence'] ) >= 60.0 ) {

                // If Wordfence is active, use their built-in logger
                if ( class_exists( 'wfLog' ) ) {
                    $wfLog = new wfLog();
                    $reason = 'Wilcosky StopForumSpam: Confidence score above 60';
                    $wfLog->blockIP( $ip, $reason );
                    $wfLog->do503( 60, 'You have been blocked due to suspicious activity.' );
                    exit;
                }

                // Otherwise, fall back to a generic block
                wp_die(
                    esc_html__( 'Access denied: Your IP has been flagged as suspicious.', 'wilcosky-stop-forum-spam' ),
                    esc_html__( 'Blocked', 'wilcosky-stop-forum-spam' ),
                    array( 'response' => 403 )
                );
            }
        }
    }
}
