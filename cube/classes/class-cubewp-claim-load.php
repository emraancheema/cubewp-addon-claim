<?php
/**
 * CubeWP Claim Initialization.
 *
 * @package cubewp/cube/classes
 * @version 1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * CubeWp Claim load Class.
 *
 * @class CubeWp_Claim_Load
 */
class CubeWp_Claim_Load {

    /**
     * Wordpress required version.
     *
     * @var string
     */
    public static $CubeWp_claim_version = '1.0';
    /**
	 * The single instance of the class.
	 *
	 * @var CubeWp_Load
	 */
    protected static $Load = null;
    public $admin_notices;

    public static function instance() {
		if ( is_null( self::$Load ) ) {
			self::$Load = new self();
		}
		return self::$Load;
	}
    /**
     * CubeWp_Load Constructor.
     */
    public function __construct() {
        /* CUBEWP_VERSION is defined for current cubewp version */
        if (!defined('CUBEWP_CLAIM_VERSION')) {
            define('CUBEWP_CLAIM_VERSION', self::$CubeWp_claim_version);
        }

        self::includes();
		self::init_hooks();
    }
    
    /**
     * Method init_hooks
     *
     * @since  1.0.0
     */
    private function init_hooks() {
        add_action('plugins_loaded', array($this, 'on_plugins_loaded'), -1);
        add_action('init', array($this, 'init'), 0);
        add_action('init', array('CubeWp_Claim_Setup', 'init'), 9);
        add_action('init', array('CubeWp_Claim_Columns', 'init'), 9);
    }

    /**
     * Include required core files used in admin and on the frontend.
     */
    public function includes() {
        require_once CUBEWP_CLAIM_PLUGIN_DIR . 'cube/functions/functions.php';
        if (CWP()->is_request('frontend')) {
            self::frontend_includes();
        }
    }
    /**
     * Init CubeWp when WordPress Initialises.
     */
    public function init() {
        // Set up localisation.
        self::load_plugin_textdomain();

    }

    /**
     * cubewp_plugins_loaded action hook to load something on plugin_loaded action.
     */
    public function on_plugins_loaded() {
        do_action('cubewp_claim_loaded');
    }

    /**
     * Include required frontend files.
     */
    public function frontend_includes() {
        add_action('init', array('CubeWp_Claim_Processing', 'init'), 9);
    }

    /**
     * Load Localisation files.
     *
     * Note: the first-loaded translation file overrides any following ones if the same translation is present.
     *
     * Locales found in:
     * - WP_LANG_DIR/cubewp/cubewp-LOCALE.mo
     * - WP_LANG_DIR/plugins/cubewp-LOCALE.mo
     */
    public function load_plugin_textdomain() {
        if (function_exists('determine_locale')) {
            $locale = determine_locale();
        } else {
            // @todo Remove when start supporting WP 5.0 or later.
            $locale = is_admin() ? get_user_locale() : get_locale();
        }

        $locale = apply_filters('plugin_locale', $locale, 'cubewp-claim');

        unload_textdomain('cubewp-claim');
        load_textdomain('cubewp-claim', WP_LANG_DIR . '/cubewp-addon-claim/cubewp-claim-' . $locale . '.mo');
        load_plugin_textdomain('cubewp-claim', false, plugin_basename(dirname(CUBEWP_CLAIM_PLUGIN_FILE)) . '/languages');
    }

}
