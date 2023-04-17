<?php

/**
 * CubeWp Claim
 *
 * @package cubewp-addon-claim/cube/classes
 * @version 1.0
 *
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * CubeWp Claim
 *
 * @class CubeWp_Claim
 */
class CubeWp_Claim_Processing
{

    public static $star_field = false;

    public function __construct()
    {
        add_action('cubewp/frontend/form/cwp_claim', array($this, 'cwp_claim_form'), 10, 2);
        add_filter('cubewp/cwp_claim/form/fields', array($this, 'cwp_claim_associated_fields'), 11);
        add_filter('cubewp/frontend/form/cwp_claim/button', array($this, 'cwp_claim_submit_button'), 8, 4);
        add_filter('cubewp/cwp_claim/before/submit/actions', array($this, 'cwp_claim_before_submit_actions'), 8, 2);
        add_action('cubewp/cwp_claim/after/post/create', array($this, 'cwp_meta_update_after_claim_submit'), 10, 2);
        add_filter('cubewp/cwp_claim/after/submit/actions', array($this, 'cwp_claim_after_submit_actions'), 8, 2);
    }

    /**
     * Method cwp_claim_form
     *
     * @param string $type
     * @param array $from
     *
     * @return string
     * @since  1.0.0
     */
    public static function cwp_claim_form($output = '', $args = array())
    {
        $post_id = get_the_ID();
        if (is_claimed($post_id)) {
            return '';
        } else {
            return $output;
        }
    }

    /**
     * Method cwp_claim_associated_fields
     *
     * @param string $output
     *
     * @return string
     * @since  1.0.0
     */
    public function cwp_claim_associated_fields($output)
    {
        
        if (is_single() && !is_page()) {
            global $post;
            if (!is_wp_error($post)) {
                $associate_id = $post->ID;
            }
        }
        if(empty($associate_id)){
            $associate_id = $_POST['id'];
        }
        return !empty($associate_id) ? '<input type="hidden" id="cwp_claimed_post" name="cwp_user_form[cwp_meta][cwp_claimed_post]" value="' . $associate_id . '">' : '';
    }

    /**
     * Method cwp_claim_submit_button
     *
     * @param string $submitBTN
     * @param string $button_title
     * @param string $button_class
     *
     * @return string
     * @since  1.0.0
     */
    public function cwp_claim_submit_button($submitBTN, $button_title, $button_class)
    {
        $button_title = esc_html__('Claim', 'cubewp-claim');
        $submitBTN  = '<input class="cwp-claim-submitBTN ' . esc_attr($button_class) . '" type="submit" value="'.esc_attr($button_title).'">';
        return $submitBTN;
    }

    /**
     * Method cwp_claim_before_submit_actions
     *
     * @param array $POST
     *
     * @return json
     * @since  1.0.0
     */
    public function cwp_claim_before_submit_actions($POST)
    {
        if (isset($POST['cwp_user_form']['cwp_meta']['cwp_claimed_post']) && !empty($POST['cwp_user_form']['cwp_meta']['cwp_claimed_post'])) {
            $current_user_id = get_current_user_id();
            $cwp_claimed_post = $POST['cwp_user_form']['cwp_meta']['cwp_claimed_post'];
            $post_claimed_author  = get_post_field('post_author', $cwp_claimed_post);
            if ($current_user_id == $post_claimed_author) {
                $response['type'] = 'info';
                $response['msg']  = esc_html__("You can't submit claim request on your own post", "cubewp-claim");
                wp_send_json($response);
            }
        }
    }

    /**
     * Method cwp_meta_update_after_claim_submit
     *
     * @param int $claimid
     * @param array $POST
     *
     * @return void
     * @since  1.0.0
     */
    public function cwp_meta_update_after_claim_submit($claimid = 0, $POST = array())
    {
        if (isset($POST['cwp_user_form']['cwp_meta']['cwp_claimed_post']) && !empty($POST['cwp_user_form']['cwp_meta']['cwp_claimed_post'])) {
            $cwp_claimed_post = $POST['cwp_user_form']['cwp_meta']['cwp_claimed_post'];
            update_post_meta($claimid, 'cwp_claimed_post', $cwp_claimed_post);
        }
    }

    /**
     * Method cwp_claim_after_submit_actions
     *
     * @param array $data
     * @param array $POST
     *
     * @return json
     * @since  1.0.0
     */
    public function cwp_claim_after_submit_actions($data, $post)
    {
        global $cwpOptions;
        $post_id = $post['post_id'];
        $claim_meta = get_post_meta($post['post_id']);
        $cwp_claim_type = 'free';
        $paid_claim = isset($cwpOptions['paid_claim']) ? $cwpOptions['paid_claim'] : '';
        $cwp_claimed_post = isset($claim_meta['cwp_claimed_post'][0]) ? $claim_meta['cwp_claimed_post'][0] : '';
        wp_update_post(
            array(
                'ID' => $post['post_id'],
                'post_status' => 'pending',
            )
        );
        if ($post['post_type'] == 'cwp_claim' && $paid_claim == 0) {
            $data['msg'] = esc_html__('Claim request for this post has submitted.', 'cubewp-claim');
            if ($cwp_claim_type == 'free') {
                $data['redirectURL'] = get_permalink($cwp_claimed_post);
            } elseif ($cwp_claim_type == 'paid') {
                $data['redirectURL'] = get_permalink(get_the_ID());
            }
        }elseif($post['post_type'] == 'cwp_claim' && $paid_claim == 1){
            $plan_id      =  get_post_meta($post_id, 'plan_id', true);
            $plan_price   =  get_post_meta($plan_id, 'plan_price', true);
            if( isset($plan_price) && $plan_price > 0 ){
                $payment_args = array(
                    'post_id'    =>    $post_id,
                    'plan_id'    =>    $plan_id,
                    'plan_name'  =>    esc_html(get_the_title($plan_id)),
                    'price'      =>    $plan_price
                );
                $cubwp_payments = new CubeWp_Payments();
                $checkout_url = $cubwp_payments->process_payment( $payment_args );
                wp_send_json(
                    array(
                        'type'        =>  'success',
                        'msg'         =>  __('Success! Post is added successfully in the cart.', 'cubewp-claim'),
                        'redirectURL' =>  $checkout_url
                    )
                );
            }
        }

        return $data;
    }

    public static function init()
    {
        $CubeClass = __CLASS__;
        new $CubeClass;
    }
}
