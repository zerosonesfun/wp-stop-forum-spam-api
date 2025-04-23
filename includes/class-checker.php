<?php
if ( ! class_exists( 'Wilcosky_Stop_Forum_Spam_Checker' ) ) {
    class Wilcosky_Stop_Forum_Spam_Checker {
        /**
         * Query StopForumSpam + confidence;
         * if ≥60%, report to Wordfence and block.
         */
        public function maybe_block_ip() {
            $ip = $_SERVER['REMOTE_ADDR'] ?? '';
            if ( ! $ip ) {
                return;
            }

            $url = sprintf(
                'http://api.stopforumspam.org/api?ip=%s&json&confidence',
                rawurlencode( $ip )
            );
            $response = wp_remote_get( $url );

            if ( is_wp_error( $response ) ) {
                return;
            }

            $data = json_decode( wp_remote_retrieve_body( $response ), true );
            if ( empty( $data['ip']['confidence'] ) ) {
                return;
            }

            $confidence = floatval( $data['ip']['confidence'] );

            // Threshold: 60%
            if ( $confidence >= 60.0 ) {

                // Report it to Wordfence Live Traffic
                if ( class_exists( 'wfActivityReport' ) ) {
                    wfActivityReport::logBlockedIP( $ip );
                }

                // Kill the request
                wp_die(
                    esc_html__( 'Access denied: Your IP has been flagged as suspicious.', 'wilcosky-stop-forum-spam' ),
                    esc_html__( 'Blocked', 'wilcosky-stop-forum-spam' ),
                    [ 'response' => 403 ]
                );
            }
        }
    }
}
