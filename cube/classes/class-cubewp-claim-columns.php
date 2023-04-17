<?php

/**
 * Create Columns for Claim.
 *
 * @package cubewp-addon-forms/cube/classes
 * @version 1.0
 *
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * CubeWp Claim Columns
 *
 * @class CubeWp_Claim_Columns
 */
if (!class_exists('CubeWp_Claim_Columns')) {
    class CubeWp_Claim_Columns
    {

        public function __construct()
        {
            add_filter("manage_cwp_claim_posts_columns", array($this, 'cubewp_filter_claim_columns'));
            add_action("manage_cwp_claim_posts_custom_column", array($this, 'cubewp_claim_column'), 20, 2);
        }

        /**
         * Method cubewp_filter_claim_columns
         *
         * @param array $columns
         *
         * @return string
         * @since  1.0.0
         */
        public function cubewp_filter_claim_columns($columns)
        {
            $new_columns = array();
            foreach ($columns as $key => $column) {
                $new_columns[$key]  =  $column;
                if ($key == 'title') {
                    $new_columns['claimed_post']   =  __('Claimed Post', 'cubewp-claim');
                    $new_columns['claimed_by']        =  __('Claimed By', 'cubewp-claim');
                    $new_columns['claim_type']   =  __('Claim Type', 'cubewp-claim');
                }
                if( $key == 'date' ){
                    unset($new_columns['date']);
                    $new_columns['plan']             =  __( 'Associated Plan', 'cubewp-claim' );
                    $new_columns['payment_status']   =  __( 'Payment Status', 'cubewp-claim' );
                    $new_columns['published_date']   =  __( 'Published Date', 'cubewp-claim' );
                }
            }
            return $new_columns;
        }

        /**
         * Method cubewp_claim_column
         *
         * @param array $column
         * @param int $post_id
         *
         * @return string
         * @since  1.0.0
         */
        public function cubewp_claim_column($column, $post_id)
        {
            $associated_id = self::cubewp_claimed_post($post_id);
            $association = '<a href="' . get_the_permalink($associated_id) . '" target="_blank">' . get_the_title($associated_id) . '</a>';
            if ('claimed_post' == $column) {
                echo !empty($association) ? $association : esc_html_e('No post associated', 'cubewp-claim');
            } elseif ('claimed_by' == $column) {
                if (get_the_author_meta('display_name')) {
                    echo get_the_author_meta('display_name');
                } else {
                    esc_html_e('User no longer exists', 'cubewp-claim');
                }
            } elseif ('claim_type' == $column) {
                $payment_status = get_post_meta($post_id, 'payment_status', true);
                if (isset($payment_status) && !empty($payment_status)) {
                    esc_html_e('Paid', 'cubewp-claim');
                } else {
                    esc_html_e('Free', 'cubewp-claim');
                }
            }
            switch($column) {
                case 'published_date':
                    echo get_the_date('Y/m/d H:i a', $post_id);
                break;
                case 'plan':
                    $plan_id = get_post_meta($post_id, 'plan_id', true);
                    if( $plan_id > 0 ){
                        echo esc_html(get_the_title($plan_id));
                    }else{
                        esc_html_e('N/A', 'cubewp-claim');
                    }
                break;
                case 'payment_status':
                    $payment_status = get_post_meta($post_id, 'payment_status', true);
                    if( isset($payment_status) && !empty($payment_status) ){
                        echo cubewp_payment_status_label($payment_status);
                    }else{
                        esc_html_e('Free', 'cubewp-claim');
                    }
                break;
            }
        }

        /**
         * Method cubewp_claimed_post
         *
         * @param int $claimID
         *
         * @return string
         * @since  1.0.0
         */
        public function cubewp_claimed_post($claimID)
        {
            return get_post_meta($claimID, 'cwp_claimed_post', true);
        }

        public static function init()
        {
            $CubeClass = __CLASS__;
            new $CubeClass;
        }
    }
}
