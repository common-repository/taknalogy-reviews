<?php
/**
 * @wordpress-plugin
 * Plugin Name: Taknalogy Reviews
 * Plugin URI: https://taknalogy.com/blog/2019/11/28/taknalogy-reviews-platform-documentation/
 * Description: Manages and displays reviews for woocommerce product pages. It uses reviews service from https://taknalogy.com to fully automate review management tasks. This plugin separates reviews management from store management.
 * Version: 1.2.4
 * Author: Rab Nawaz
 * Author URI: https://taknalogy.com/author/rnawaz/
 * Requires at least: 4.0.0
 * Tested up to: 5.3.2
 * License: GPL-3.0+
 * License URI: https://www.gnu.org/licenses/gpl-3.0.txt
 * Text Domain: taknalogy-reviews
 * Domain Path: /languages
 *
 * @package Taknalogy_Reviews
 * @category Core
 * @author Rab Nawaz
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
if ( ! defined( 'TAK_WOO_SSLVERIFY' ) ) {
	define( 'TAK_WOO_SSLVERIFY', false );
}
define( 'TAK_WOO_REVIEWS_VERSION', '1.1.0' );
define( 'TAK_DIR_PATH', plugin_dir_path( __FILE__ ) );

define( 'TAK_WOO_REVIEWS_URL', '//taknalogy.com/taknalogy-reviews' );
define( 'TAK_WOO_REST_URL', 'https://taknalogy.com/wp-json/tak/v1/status' );

require_once 'functions.php';
add_action( 'plugins_loaded', array( 'Taknalogy_Reviews', 'instance' ) );
register_activation_hook( __FILE__, array( 'Taknalogy_Reviews', 'activate' ) );
register_uninstall_hook( __FILE__, array( 'Taknalogy_Reviews', 'uninstall' ) );

/**
 * Returns the main instance of Taknalogy_Reviews to prevent the need to use globals.
 *
 * @since  1.0.0
 * @return object Taknalogy_Reviews
 */
function Taknalogy_Reviews() {
	return Taknalogy_Reviews::instance();
} // End Taknalogy_Reviews()

/**
 * Main Taknalogy_Reviews Class
 *
 * @class Taknalogy_Reviews
 * @version 1.0.0
 * @since 1.0.0
 * @package Taknalogy_Reviews
 * @author Rab Nawaz
 */
final class Taknalogy_Reviews {
	/**
	 * Taknalogy_Reviews The single instance of Taknalogy_Reviews.
	 *
	 * @var     object
	 * @access  private
	 * @since   1.0.0
	 */
	private static $_instance = null;

	/**
	 * The token.
	 *
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public static $token = 'tak_review';

	/**
	 * The version number.
	 *
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public static $version = TAK_WOO_REVIEWS_VERSION;

	/**
	 * The plugin directory URL.
	 *
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $plugin_url;

	/**
	 * The plugin directory path.
	 *
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $plugin_path;

	// Admin - Start
	/**
	 * The admin object.
	 *
	 * @var     object
	 * @access  public
	 * @since   1.0.0
	 */
	public $admin;

	/**
	 * The settings object.
	 *
	 * @var     object
	 * @access  public
	 * @since   1.0.0
	 */
	public $settings;
	// Admin - End

	// Post Types - Start
	/**
	 * The post types we're registering.
	 *
	 * @var     array
	 * @access  public
	 * @since   1.0.0
	 */
	public $post_types = array();
	// Post Types - End
	/**
	 * Constructor function.
	 *
	 * @access  public
	 * @since   1.0.0
	 */
	public function __construct() {
		$this->plugin_url  = plugin_dir_url( __FILE__ );
		$this->plugin_path = plugin_dir_path( __FILE__ );
		require_once 'classes/class-taknalogy-reviews-tab.php';
		Taknalogy_Reviews_Tab::instance();
		require_once 'classes/class-taknalogy-reviews-prod.php';
		Taknalogy_Reviews_Prod::instance();
		register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );
		add_action( 'init', array( $this, 'load_plugin_textdomain' ) );
	} // End __construct()

	/**
	 * Main Taknalogy_Reviews Instance
	 *
	 * Ensures only one instance of Taknalogy_Reviews is loaded or can be loaded.
	 *
	 * @since 1.0.0
	 * @static
	 * @see Taknalogy_Reviews()
	 * @return Main Taknalogy_Reviews instance
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	} // End instance()

	/**
	 * Load the localisation file.
	 *
	 * @access  public
	 * @since   1.0.0
	 */
	public function load_plugin_textdomain() {
		load_plugin_textdomain( 'taknalogy-reviews', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	} // End load_plugin_textdomain()

	/**
	 * Cloning is forbidden.
	 *
	 * @access public
	 * @since 1.0.0
	 */
	public function __clone() {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?' ), '1.0.0' );
	} // End __clone()

	/**
	 * Unserializing instances of this class is forbidden.
	 *
	 * @access public
	 * @since 1.0.0
	 */
	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?' ), '1.0.0' );
	} // End __wakeup()

	/**
	 * Installation. Runs on activation.
	 *
	 * @access  public
	 * @since   1.0.0
	 */
	public static function activate() {

		$plugin = isset( $_REQUEST['plugin'] ) ? sanitize_text_field( $_REQUEST['plugin'] ) : '';
		if ( ! check_admin_referer( "activate-plugin_{$plugin}" ) ) {
			return;
		}
		if ( current_user_can( 'activate_plugins' ) && ! class_exists( 'WooCommerce' ) ) {
			deactivate_plugins( plugin_basename( __FILE__ ) );
			$error_message = '<p style="font-family:-apple-system,BlinkMacSystemFont,\'Segoe UI\',Roboto,Oxygen-Sans,Ubuntu,Cantarell,\'Helvetica Neue\',sans-serif;font-size: 13px;line-height: 1.5;color:#444;">' . esc_html__( 'This plugin requires ', 'taknalogy-reviews' ) . '<a href="' . esc_url( 'https://wordpress.org/plugins/woocommerce/' ) . '">WooCommerce</a>' . esc_html__( ' plugin to be active.', 'taknalogy-reviews' ) . '</p>';
			die( $error_message );
		}
		$options     = get_option( 'tak_review_settings' );
		$default_opt = array(
			'tak_review_active'       => 1,
			self::$token . '-version' => self::$version,
			'tak_review_shopurl'      => get_site_url(),
			'tak_review_shoptitle'    => get_bloginfo(), // get_site_url(),
			'tak_review_shopkey'      => md5( uniqid( rand(), true ) ),
		);
		$options     = array_merge( $default_opt, $options );
		if ( is_array( $options ) && $options ) {
			update_option( 'tak_review_settings', $options );
		} else {
			add_option( 'tak_review_settings', $default_opt );
		}
		inform_remote_host( 'activate' );
	} // End activate()
	public function deactivate() {

		$plugin = isset( $_REQUEST['plugin'] ) ? sanitize_text_field( $_REQUEST['plugin'] ) : '';
		if ( ! check_admin_referer( "deactivate-plugin_{$plugin}" ) ) {
			return;
		}
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}
		reset_comment_count();
		inform_remote_host( 'deactivate' );
	}
	public static function uninstall() {
		if ( current_user_can( 'delete_plugins' ) ) {
			reset_comment_count();
			inform_remote_host( 'uninstall' );
		}
	}
} // End Class
