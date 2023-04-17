<?php

/**
 * Script and styles enqueue for CubeWP Claim.
 *
 * @package cubewp-addon-claim/cube/classes
 * @version 1.0
 *
 */

if (!defined('ABSPATH')) {
	exit;
}

/**
 * CubeWp Claim Enqueue
 *
 * @class CubeWp_Reviews_Enqueue
 */
class CubeWp_Claim_Enqueue
{

	public function __construct()
	{
		add_filter('admin/style/register', array($this, 'register_admin_styles'));
		add_filter('frontend/style/register', array($this, 'register_frontend_styles'));
		add_filter('frontend/script/register', array($this, 'register_frontend_scripts'));
		add_filter('admin/script/register', array($this, 'register_admin_scripts'));
		add_filter('admin/script/enqueue', array($this, 'load_admin_scripts'));
		add_filter('get_frontend_script_data', array($this, 'get_frontend_script_data'), 10, 2);
	}

	/**
	 * Method register_admin_styles
	 *
	 * @param array $styles
	 *
	 * @return array
	 * @since  1.0.0
	 */
	public static function register_admin_styles($styles)
	{
		$register_styles = array(
			'cwp-claim-admin' => array(
				'src'     => CUBEWP_CLAIM_PLUGIN_URL . 'cube/assets/css/cwp-claim-admin.css',
				'deps'    => array(),
				'version' => CUBEWP_CLAIM_VERSION,
				'has_rtl' => false,
			),
		);

		return array_merge($register_styles, $styles);
	}

	/**
	 * Method register_frontend_styles
	 *
	 * @param array $styles
	 *
	 * @return array
	 * @since  1.0.0
	 */
	public static function register_frontend_styles($styles)
	{
		$register_styles = array(
			'cwp-claim-frontend'   => array(
				'src'     => CUBEWP_CLAIM_PLUGIN_URL . 'cube/assets/css/cwp-claim-frontend.css',
				'deps'    => array(),
				'version' => CUBEWP_CLAIM_VERSION,
				'has_rtl' => false,
			)
		);
		return array_merge($register_styles, $styles);
	}

	/**
	 * Method register_frontend_scripts
	 *
	 * @param array $script
	 *
	 * @return array
	 * @since  1.0.0
	 */
	public static function register_frontend_scripts($script)
	{
		$register_scripts = array(
			'cwp-claim-frontend' => array(
				'src'     => CUBEWP_CLAIM_PLUGIN_URL . 'cube/assets/js/cubewp-claim-frontend.js',
				'deps'    => array(),
				'version' => CUBEWP_CLAIM_VERSION,
				'has_rtl' => false,
			),
		);

		return array_merge($register_scripts, $script);
	}

	/**
	 * Method register_admin_scripts
	 *
	 * @param array $script
	 *
	 * @return array
	 * @since  1.0.0
	 */
	public static function register_admin_scripts($script)
	{
		$register_scripts = array(
			'cwp-claim-admin' => array(
				'src'     => CUBEWP_CLAIM_PLUGIN_URL . 'cube/assets/js/cubewp-claim-admin.js',
				'deps'    => array(),
				'version' => CUBEWP_CLAIM_VERSION,
				'has_rtl' => false,
			),
		);

		return array_merge($register_scripts, $script);
	}

	/**
	 * Method load_admin_scripts
	 *
	 * @param array $data
	 *
	 * @return void
	 * @since  1.0.0
	 */
	public static function load_admin_scripts($data)
	{
		global $post;
		if (is_object($post) && isset($post->ID)) {
			wp_enqueue_style('cwp-claim-admin');
			wp_enqueue_script('cwp-claim-admin');
		}
	}

	/**
	 * Return data for script handles.
	 *
	 * @param string $handle Script handle the data will be attached to.
	 * @param array $data 
	 *
	 * @return array|bool
	 */
	public static function get_frontend_script_data($data, $handle)
	{
		global $wp;
		if ($handle == 'cwp-claim-frontend') {
			$params = array(
				'ajax_url'   => admin_url('admin-ajax.php'),
				'nonce' => wp_create_nonce("cwp-claim-frontend"),
			);

			return $params;
		}

		return $data;
	}

	public static function init()
	{
		$CubeClass = __CLASS__;
		new $CubeClass;
	}
}
new CubeWp_Claim_Enqueue();
