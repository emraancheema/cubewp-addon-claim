<?php
/* CUbeWP Claim functions */

/**
 * Method cubewp_claim_settings_sections
 *
 * @param array $sections
 *
 * @return string
 * @since  1.0.0
 */
if (!function_exists('cubewp_claim_settings_sections')) {
    function cubewp_claim_settings_sections($sections)
    {
        $claim_settings['claim'] = array(
            'title'  => __('Claims', 'cubewp-claim'),
            'id'     => 'claim',
            'icon'   => 'dashicons-clipboard',
            'fields' => array(
                array(
                    'id'       => 'paid_claim',
                    'type'     => 'switch',
                    'title'    => __('Paid Claim Submission', 'cubewp-claim'),
                    'default' => '0',
                    'desc'    => __('Enable users to submit paid claims by turning on this feature and allowing them to pay a fee or deductible when submitting their claims.', 'cubewp-claim'),
                )
            ),
        );
        return array_merge($sections, $claim_settings);
    }
    add_filter('cubewp/options/sections', 'cubewp_claim_settings_sections', 11, 1);
}

/**
 * Method cube_reviews_rating
 *
 * @param array $args
 *
 * @return string
 * @since  1.0.0
 */
if (!function_exists('cube_claim_form')) {
    function cube_claim_form($args)
    {
        wp_enqueue_style('cubewp-plans');
        wp_enqueue_style('select2');
        wp_enqueue_script('select2');
        wp_enqueue_style('cwp-timepicker');
        wp_enqueue_script('cwp-timepicker');
        wp_enqueue_script('jquery-ui-datepicker');
        wp_enqueue_script('cwp-submit-post');
        wp_enqueue_script('cwp-form-validation');
        wp_enqueue_style('frontend-fields');
        wp_enqueue_script('cwp-frontend-fields');
        wp_enqueue_style('cwp-claim-frontend');
        wp_enqueue_script('cwp-claim-frontend');


        global $post, $cwpOptions;
        $paid_claim = isset($cwpOptions['paid_claim']) ? $cwpOptions['paid_claim'] : '';
        $post_id = get_queried_object_id();
        $post_type = 'cwp_claim';
        $output = '';
        if (!is_claimed($post_id) && is_user_logged_in()) {
            $output .= '<div class="cwp-claim-form-container ' . $args['container_class'] . '">';
            $output .= '<button id="cwp-claim-form-btn" data-pid="' . $post_id . '">' . esc_html__('Claim Now', 'cubewp-claim') . '</button>';
            $output .= '<div class="cwp-claim-form-modal">';
            $output .= '<span class="cwp-claim-form-close"><span class="dashicons dashicons-no"></span></span>';
            if ($paid_claim == 1) {
                $query_args       = array(
                    'post_type'      => 'price_plan',
                    'post_status'    => 'publish',
                    'posts_per_page' => -1,
                    'fields'         => 'ids',
                    'meta_key'       => 'plan_price',
                    'orderby'        => 'meta_value_num',
                    'order'          => 'ASC',
                    'meta_query'     => array(
                        array(
                            'key'     => 'plan_post_type',
                            'value'   => $post_type,
                            'compare' => '=',
                        )
                    ),
                );
                $plan_options = array();
                $price_plans      = get_posts($query_args);
                if (isset($price_plans) && !empty($price_plans) && class_exists('CubeWp_Payments_Load')) {
                    $submit_page = '';
                    $atts = '';
                    $plan_options = (new CubeWp_Payments_Price_Plans)->cubewp_get_plan_options_list($price_plans, $post_type);
                    $output       .= '<div class="cwp-container cwp-plans-container">
			                            <div class="cwp-row">';
                    foreach ($price_plans as $price_plan_id) {
                        $output .= apply_filters("cubewp/frontend/single/pricing_plan", '', $price_plan_id, $submit_page, $plan_options, $atts);
                    }
                    $output .= '</div>
							</div>';
                }
                // else{
                //     $output .= '<h6>'.__('Something went wrong with plans.','cubewp-claim').'</h6>';
                // }
                $plan_id  =  isset($_POST['plan_id']) ? sanitize_text_field($_POST['plan_id']) : 0;
                $output .= '<div class="cwp-claim-paid-form"></div>';
            } else {
                $output .= '<div class="cwp-claim-free-form">' . do_shortcode('[cwpForm type="cwp_claim"]') . '</div>';
            }
            $output .= '</div></div>';
        }
        return apply_filters('cubewp_claim_form', $output, $args);
    }
}

/**
 * Method cwp_claim_plan_associated_form
 *
 * @return JSON
 * @since  1.0.0
 */
if (!function_exists('cwp_claim_plan_associated_form')) {
    function cwp_claim_plan_associated_form()
    {
        global $wpdb;
        ob_start();
        echo do_shortcode('[cwpForm type="cwp_claim"]');
        wp_send_json(array('output' => ob_get_clean()));
    }
    add_action('wp_ajax_cwp_claim_plan_associated_form', 'cwp_claim_plan_associated_form');
}

/**
 * Method cubewp_claimed_class
 *
 * @param array $classes
 *
 * @return array
 * @since  1.0.0
 */
if (!function_exists('cubewp_claimed_class')) {
    function cubewp_claimed_class($classes)
    {
        if (is_claimed()) :
            $classes[] = 'cwp_claimed';
            $classes[] = 'claimed-id-' . get_the_ID();
            return $classes;
        endif;
        return $classes;
    }
    add_filter('post_class', 'cubewp_claimed_class');
}

/**
 * Method claim_post
 *
 * @param int $post_id
 *
 * @return JSON
 * @since  1.0.0
 */
if (!function_exists('claim_post')) {
    function claim_post($claim_request_id)
    {
        global $wpdb;
        $claimed_post_id = get_post_meta($claim_request_id, 'cwp_claimed_post', true);
        $user_id = get_post_field('post_author', $claim_request_id);
        if (!current_user_can('edit_post', $claim_request_id)) {
            wp_send_json_error('You are not allowed to approve claim request.');
        }
        $args = array(
            'ID'            => $claim_request_id,
            'post_status'   => 'approved',
        );
        $updated = wp_update_post(array(
            'ID'            => $claimed_post_id,
            'post_author'   => $user_id,
        ), true);
        $claimed_update = wp_update_post($args, true);
        $user_info = get_userdata($user_id);
        $user_name = $user_info->display_name;
        $email = $user_info->user_email;
        $post_title = get_the_title( $claimed_post_id );
        $post_title = '<h2 style="font-size: 24px; font-weight: bold; margin-top: 20px; margin-bottom: 20px;">'.$post_title.'</h2>';
        if ($updated) {
            $subject = esc_html__('Claim Request Approved', 'cubewp-claim');
            $message           = '<div style="font-family: Arial, sans-serif; font-size: 16px; line-height: 1.5;">
                <div style="margin: 0 auto;">
                    <p>' . esc_html__('Hello,', 'cubewp-claim') . '</p>
                    <p>' . sprintf( __( 'Dear %s, your claim request for %s has been approved.', 'cubewp-claim' ), $user_name,$post_title ) . '</p>
                    <p>' . esc_html__('Thank you for using our service!', 'cubewp-claim') . '</p>
                    <p style="margin-top: 40px;">' . esc_html__('Best regards,', 'cubewp-claim') . '</p>
                    <p>' . get_bloginfo('name') . '</p>
                </div>
            </div>';
            cubewp_send_mail($email, $subject, $message);
            update_post_meta($claimed_post_id, 'cwp_claim_status', 'verified');
            echo update_review_post_user($claimed_post_id, $user_id);
            $leads_table = $wpdb->prefix . "cwp_forms_leads";
            if ($wpdb->get_var("SHOW TABLES LIKE '$leads_table'") == $leads_table) {
                $claimed_post_leads = $wpdb->get_results("SELECT * FROM {$leads_table} WHERE single_post ={$claimed_post_id}", ARRAY_A);
            } else {
                $claimed_post_leads = array();
            }
            if (is_array($claimed_post_leads) && !empty($claimed_post_leads)) {
                foreach ($claimed_post_leads as $claimed_post_lead) {
                    $wpdb->query(
                        $wpdb->prepare("UPDATE {$wpdb->prefix}cwp_forms_leads
                        SET post_author = %s
                        WHERE id = %s", $user_id, $claimed_post_lead["id"])
                    );
                }
            }
            $inbox_table = $wpdb->prefix . "cwp_inbox_messages";
            if ($wpdb->get_var("SHOW TABLES LIKE '$inbox_table'") == $inbox_table) {
                $claimed_post_msgs = array($wpdb->get_row("SELECT * FROM {$inbox_table} WHERE msg_post_id ={$claimed_post_id}", ARRAY_A));
            } else {
                $claimed_post_msgs = array();
            }
            if (is_array($claimed_post_msgs) && !empty($claimed_post_msgs)) {
                foreach ($claimed_post_msgs as $claimed_post_msg) {
                    $wpdb->delete("{$wpdb->prefix}cwp_inbox_messages", array('id' => $claimed_post_msg["id"]));
                }
            }
            return wp_send_json_success('Post Claim Request Approved Successfully.');
        } else {
            return wp_send_json_error('Something Went Wrong.');
        }
    }
}

/**
 * Method update_review_post_user
 *
 * @param int $post_id
 * @param int $user_id
 *
 * @return null
 * @since  1.0.0
 */
if (!function_exists('update_review_post_user')) {
    function update_review_post_user($post_id, $user_id)
    {
        $post_id = (int) $post_id;
        $args = array(
            'post_type' => 'cwp_reviews',
            'post_status' => array('pending', 'publish'),
        );
        $args['meta_query'] = array(
            'relation' => 'AND',
            array(
                'key' => 'cwp_review_associated',
                'value' => $post_id,
                'compare' => '='
            ),
            array(
                'key' => 'cwp_review_type',
                'value' => 'post',
                'compare' => '='
            )
        );
        $post_reviews = new WP_Query($args);
        if ($post_reviews->have_posts()) {
            while ($post_reviews->have_posts()) : $post_reviews->the_post();
                $review_id = get_the_id();
                update_post_meta($review_id, 'cwp_review_associated_post_user', $user_id);
            endwhile;
        }
    }
}

/**
 * Method remove_claimed_post
 *
 * @param int $post_id
 *
 * @return null
 * @since  1.0.0
 */
if (!function_exists('remove_claimed_post')) {
    function remove_claimed_post($post_id)
    {
        $post_id = (int) $post_id;
        if (is_claimed($post_id)) {
            update_post_meta($post_id, 'cwp_claim_status', '');
        }
    }
}

/**
 * Method is_claimed
 *
 * @param int $post_id
 *
 * @return string
 * @since  1.0.0
 */
if (!function_exists('is_claimed')) {
    function is_claimed($post_id = 0)
    {
        $post_id = absint($post_id);

        if (!$post_id) {
            $post_id = get_the_ID();
        }

        $claimed = get_post_meta($post_id, 'cwp_claim_status', true);

        if ($claimed == 'verified') {
            return true;
        } else {
            return false;
        }
    }
}
