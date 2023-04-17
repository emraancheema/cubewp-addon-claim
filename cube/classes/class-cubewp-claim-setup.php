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
class CubeWp_Claim_Setup
{

    public static $star_field = false;

    public function __construct()
    {
        add_filter('cubewp/posttypes/new', array($this, 'CWP_cpt'), 9);
        add_action('admin_menu', array($this, 'cwp_claim_disable_add_new'));
        add_filter('cubewp/builder/post_types', array($this, 'CWP_Claim_add_builder'), 9, 2);
        add_filter('cubewp/builder/single/custom/cubes', array($this, 'cwp_claim_custom_cube'), 9, 2);
        add_action('init', array($this, 'cwp_claim_post_status'));
        add_action('admin_head', array($this, 'remove_post_actions'));
        add_filter('display_post_states', array($this, 'cwp_claim_display_post_status'));
        add_action('add_meta_boxes', array($this, 'cwp_clain_custom_buttons_meta_box'));
        add_action('wp_ajax_cwp_claim_approve', array($this, 'cubewp_claim_approve'));
        add_action('wp_ajax_cwp_claim_reject', array($this, 'cubewp_claim_reject'));
    }

    /**
     * Method CWP_cpt
     * parse arguments to register post types.
     * make sure all argumanted passses properly
     * @since 1.0
     * @version 1.0
     */
    public static function CWP_cpt($default)
    {
        $claimCPT = array(
            'cwp_claim'            => array(
                'label'                  => __('Claims', 'cubewp-claim'),
                'singular'               => __('Claim', 'cubewp-claim'),
                'icon'                   => 'dashicons-clipboard',
                'slug'                   => 'cwp_claim',
                'description'            => '',
                'supports'               => array('title', 'editor'),
                'hierarchical'           => false,
                'public'                 => true,
                'show_ui'                => true,
                'menu_position'          => true,
                'show_in_menu'           => true,
                'show_in_nav_menus'      => true,
                'show_in_admin_bar'      => true,
                'can_export'             => true,
                'has_archive'            => true,
                'exclude_from_search'    => false,
                'publicly_queryable'     => true,
                'query_var'              => true,
                'rewrite'                => false,
                'rewrite_slug'           => '',
                'rewrite_withfront'      => true,
                'show_in_rest'           => true,
            )
        );

        return array_merge($default, $claimCPT);
    }

    /**
     * Method cwp_claim_disable_add_new
     * 
     * @since 1.0
     * @version 1.0
     */
    public function cwp_claim_disable_add_new()
    {
        global $submenu,$pagenow;
        unset($submenu['edit.php?post_type=cwp_claim'][10]);
        remove_submenu_page('edit.php?post_type=cwp_claim', 'post-new.php?post_type=cwp_claim');

        if ($pagenow == 'edit.php' && isset($_GET['post_type']) && $_GET['post_type'] == 'cwp_claim') {
            echo '<style>.page-title-action { display:none !important; }</style>';
        }
    }

    /**
     * Method remove_post_actions
     *
     * @return void
     * @since  1.0.0
     */
    public static function remove_post_actions()
    {
        global $post;
        if ($post && $post->post_type === 'cwp_claim') {
            // Remove edit, quick edit, and delete buttons
            if (!current_user_can('administrator')) { ?>
                <style>
                    .row-actions span.edit,
                    .row-actions span.view,
                    .row-actions span.inline,
                    .row-actions span.trash {
                        display: none;
                    }
                </style>
            <?php
            } else {
            ?>
                <style>
                    .row-actions span.view,
                    .row-actions span.inline,
                    .row-actions span.trash {
                        display: none;
                    }
                </style>
            <?php
            }
        }
    }

    /**
     * Method CWP_Claim_add_builder
     *
     * @param array $default
     * @param array $form
     *
     * @return array
     * @since  1.0.0
     */
    public static function CWP_Claim_add_builder($default, $form)
    {
        if ('post_types' == $form || 'price_plan' == $form) {
            $default['cwp_claim'] = 'Claim';
            
        }
        return $default;
    }

    /**
     * Method cwp_claim_custom_cube
     *
     * @param array $data
     * @param string $post_type
     *
     * @return array
     * @since  1.0.0
     */
    public function cwp_claim_custom_cube($data, $post_type = '')
    {
        global $cwpOptions;
        $data['claim_form']  =  array(
            'label'         =>  __("Claim Form", "cubewp-claim"),
            'name'          =>  'claim_form',
            'type'          =>  'claim_form',
        );
        return $data;
    }

    /**
     * Method cubewp_claim_approve
     *
     * @return json
     * @since  1.0.0
     */
    public function cubewp_claim_approve()
    {
        $post_id = $_POST['post_id'];
        claim_post($post_id);
    }

    /**
     * Method cubewp_claim_reject
     *
     * @return json
     * @since  1.0.0
     */
    public function cubewp_claim_reject()
    {
        $post_id = $_POST['post_id'];
        if (!current_user_can('edit_post', $post_id)) {
            wp_send_json_error('You are not allowed to reject claim request.');
        }

        $args = array(
            'ID'            => $post_id,
            'post_status'   => 'rejected',
        );
        $updated = wp_update_post($args, true);
        if ($updated) {
            wp_send_json_success('Post Claim Request Rejected.');
        } else {
            wp_send_json_error('Something Went Wrong');
        }
    }

    /**
     * Method cwp_claim_post_status
     *
     * @return array
     * @since  1.0.0
     */
    public function cwp_claim_post_status()
    {
        register_post_status('approved', array(
            'label'                     => _x('Approved', 'cubewp-claim'),
            'public'                    => true,
            'exclude_from_search'       => false,
            'show_in_admin_all_list'    => true,
            'show_in_admin_status_list' => true,
            'label_count'               => _n_noop('Approved <span class="count">(%s)</span>', 'Approved <span class="count">(%s)</span>'),
            'post_type'                 => 'cwp_claim'
        ));
        register_post_status('rejected', array(
            'label'                     => _x('Rejected', 'cubewp-claim'),
            'public'                    => true,
            'exclude_from_search'       => false,
            'show_in_admin_all_list'    => true,
            'show_in_admin_status_list' => true,
            'label_count'               => _n_noop('Rejected <span class="count">(%s)</span>', 'Rejected <span class="count">(%s)</span>'),
            'post_type'                 => 'cwp_claim'
        ));
    }

    /**
     * Method cwp_display_post_status
     *
     * @param array $statuses
     *
     * @return void
     * @since  1.0.0
     */
    function cwp_claim_display_post_status($statuses)
    {
        global $post;
        if (isset($post->post_status) && $post->post_status == 'approved') {
            return array('Approved');
        } elseif (isset($post->post_status) && $post->post_status == 'rejected') {
            return array('Rejected');
        }

        return $statuses;
    }

    /**
     * Method cwp_clain_custom_buttons_meta_box
     *
     * @return array
     * @since  1.0.0
     */
    public function cwp_clain_custom_buttons_meta_box()
    {
        add_meta_box('cwp_clain_custom_buttons_meta_box', __('Claim Request', 'cubewp-claim'), array($this, 'custom_buttons_meta_box_callback'), 'cwp_claim', 'side', 'high');
    }

    /**
     * Method custom_buttons_meta_box_callback
     *
     * @return string
     * @since  1.0.0
     */
    public function custom_buttons_meta_box_callback($post)
    {
        if (current_user_can('administrator')) {
            if ($post->post_status == 'pending') {
                echo '<div class="cwp-claim-request-actions"><button id="cwp-claim-approve" class="components-button is-primary button button-primary button-large" data-pid="' . get_the_ID() . '">' . __('Approve', 'cubewp-claim') . '</button>';
                echo '<button id="cwp-claim-reject" class="components-button is-secondary button button-secondary button-large" data-pid="' . get_the_ID() . '">' . __('Reject', 'cubewp-claim') . '</button></div>';
            } else {
            ?>
                <p><strong>
                        <?php
                        echo sprintf(esc_html__('Claim request %s.', 'cubewp-claim'), $post->post_status);
                        ?>
                    </strong>
                </p>
            <?php
            }
        } else { ?>
            <p><strong>
                    <?php
                    echo sprintf(esc_html__('Claim request is %s.', 'cubewp-claim'), $post->post_status);
                    ?>
                </strong>
            </p>
        <?php
        }
        ?>
        <style>
            #delete-action,
            #publishing-action {
                display: none;
            }

            button.editor-post-publish-button__button {
                display: none !important;
            }
        </style>
<?php
    }

    public static function init()
    {
        $CubeClass = __CLASS__;
        new $CubeClass;
    }
}
