<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Taknalogy_Reviews_Tab Class
 *
 * @class Taknalogy_Reviews_Tab
 * @version 1.0.0
 * @since 1.0.0
 * @package Taknalogy_Reviews
 * @author Rab Nawaz
 */
final class Taknalogy_Reviews_Tab {
	/**
	 * Single instance of Taknalogy_Reviews_Tab.
	 *
	 * @var     object
	 * @access  private
	 * @since   1.0.0
	 */
	private static $_instance = null;

	/**
	 * The string containing the dynamically generated hook token.
	 *
	 * @var     string
	 * @access  private
	 * @since   1.0.0
	 */
	private $_hook;

	/**
	 * Constructor function.
	 *
	 * @access  public
	 * @since   1.0.0
	 */
	public function __construct() {
		add_action( 'woocommerce_product_data_panels', array( $this, 'tak_woocommerce_product_data_panels' ) );
		add_action( 'woocommerce_process_product_meta', array( $this, 'tak_woocommerce_process_product_meta' ) );
		add_filter( 'woocommerce_product_data_tabs', array( $this, 'tak_woocommerce_product_data_tabs' ) );
	} // End __construct()

	/**
	 * Main Taknalogy_Reviews_Tab Instance
	 *
	 * Ensures only one instance of Taknalogy_Reviews_Tab is loaded or can be loaded.
	 *
	 * @since 1.0.0
	 * @static
	 * @return Main Taknalogy_Reviews_Tab instance
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	} // End instance()

	/**
	 * Registers new products to the server
	 */
	public function get_review_status_rest( $arg ) {
		$url      = TAK_WOO_REST_URL;
		$body     = json_encode(
			array(
				'_tak_review_client_url' => get_site_url(),
				'_tak_review_url'        => $arg,
				'_action'                => 'productadd',
			)
		);
		$headers  = array(
			'Content-type' => 'application/json',
			'sslverify'    => TAK_WOO_SSLVERIFY,
		);
		$response = wp_remote_post(
			$url,
			array(
				'method'      => 'POST',
				'timeout'     => 10,
				'redirection' => 5,
				'httpversion' => '1.0',
				'blocking'    => true,
				'headers'     => $headers,
				'body'        => $body, // array( 'username' => 'bob', 'password' => '1234xyz' ),
				'cookies'     => array(),
			)
		);
		// Retrieve information
		$response_code    = wp_remote_retrieve_response_code( $response );
		$response_message = wp_remote_retrieve_response_message( $response );
		$response_body    = wp_remote_retrieve_body( $response );
		if ( ! is_wp_error( $response ) ) {
			return new WP_REST_Response(
				array(
					'status'        => $response_code,
					'response'      => $response_message,
					'body_response' => $response_body,
				)
			);
		} else {
			return new WP_Error( $response_code, $response_message, $response_body );
		}
	}
	public function tak_woocommerce_product_data_tabs( $tabs ) {
		$tabs['tak_review_custom_tab'] = array(
			'label'  => __( 'Tak Reviews', 'taknalogy-reviews' ),
			'target' => 'tak_review_product_panel',
			'class'  => 'tak_review_product_panel',
		);
		return $tabs;
	}
	public function tak_woocommerce_product_data_panels() {
		global $woocommerce, $post; ?>
	<div id="tak_review_product_panel" class="panel woocommerce_options_panel">
		<div class='tak_review_product_panel'>
		<div class="row">
		<p>Please copy shopurl and shopkey below to <a href="https://taknalogy.com/taknalogy-reviews-manager/" target="_blank">Taknalogy Reviews Manager.</a> You only need to do it once.</p>
	
	</div>
		<?php
		$options     = get_option( 'tak_review_settings' );
		$tak_shopurl = array(
			'label'             => __( 'ShopURL', 'taknalogy-reviews' ),
			'value'             => $options['tak_review_shopurl'],
			'id'                => '_tak_shopurl',
			'desc_tip'          => false,
			'custom_attributes' => array( 'readonly' => 'readonly' ),
		);
		woocommerce_wp_text_input( $tak_shopurl );
		$tak_shopkey = array(
			'label'             => __( 'ShopKey', 'taknalogy-reviews' ),
			'value'             => $options['tak_review_shopkey'],
			'id'                => '_tak_shopkey',
			'desc_tip'          => false,
			'custom_attributes' => array( 'readonly' => 'readonly' ),
		);
		woocommerce_wp_text_input( $tak_shopkey );
		$tak_review_sup = array(
			'id'          => '_tak_review_sup',
			'desc_tip'    => true,
			'description' => __( 'Select Supplier', 'taknalogy-reviews' ),
			'label'       => __( 'Product Supplier', 'taknalogy-reviews' ),
			'value'       => get_post_meta( get_the_id(), '_tak_review_sup', true ),
			'options'     => array(
				'aliexpress' => __( 'AliExpress', 'languages' ),
				// 'amazon'   => __('Amazon', 'woocommerce'),
			),
		);
		woocommerce_wp_select( $tak_review_sup );
		$tak_review_url = array(
			'label'       => __( 'Product Address', 'taknalogy-reviews' ),
			'value'       => get_post_meta( get_the_id(), '_tak_review_url', true ),
			'id'          => '_tak_review_url',
			'desc_tip'    => true,
			'description' => __( 'Copy and paste product web address.', 'taknalogy-reviews' ),
		);
		woocommerce_wp_text_input( $tak_review_url );
		$tak_review_active = array(
			'label'       => __( 'Enable Tak Reviews', 'taknalogy-reviews' ),
			'value'       => get_post_meta( get_the_id(), '_tak_review_active', true ),
			'id'          => '_tak_review_active',
			'desc_tip'    => true,
			'description' => __( 'Please select checkbox to replace default reviews by Tak Reviews for this product.', 'taknalogy-reviews' ),
		);
		woocommerce_wp_checkbox( $tak_review_active );
		$tak_review_widget = array(
			'label'       => __( 'Enable Tak Widget', 'taknalogy-reviews' ),
			'value'       => get_post_meta( get_the_id(), '_tak_review_widget', true ),
			'id'          => '_tak_review_widget',
			'desc_tip'    => true,
			'description' => __( 'Please select checkbox to enable Tak Reviews Widget for this product.', 'taknalogy-reviews' ),
		);
		woocommerce_wp_checkbox( $tak_review_widget );
		$tak_review_state = array(
			'class' => '',
			'id'    => '_tak_review_state',
			'value' => get_post_meta( get_the_id(), '_tak_review_state', true ),
		);
		woocommerce_wp_hidden_input( $tak_review_state );
		?>
			</div>
		</div>
		<?php
	}
	public function tak_woocommerce_process_product_meta( $post_id ) {
		$_tak_review_sup    = isset( $_POST['_tak_review_sup'] ) ? sanitize_text_field( $_POST['_tak_review_sup'] ) : '';
		$_tak_review_url    =  explode("?", ( isset( $_POST['_tak_review_url'] ) ? sanitize_text_field( $_POST['_tak_review_url'] ) : '' ) )[0];
		$_tak_review_active = isset( $_POST['_tak_review_active'] ) ? sanitize_text_field( $_POST['_tak_review_active'] ) : '';
		$_tak_review_widget = isset( $_POST['_tak_review_widget'] ) ? sanitize_text_field( $_POST['_tak_review_widget'] ) : '';
		$_tak_review_state  = isset( $_POST['_tak_review_state'] ) ? sanitize_text_field( $_POST['_tak_review_state'] ) : '';
		update_post_meta( $post_id, '_tak_review_sup', $_tak_review_sup );
		update_post_meta( $post_id, '_tak_review_url', $_tak_review_url );
		update_post_meta( $post_id, '_tak_review_widget', $_tak_review_widget );
		update_post_meta( $post_id, '_tak_review_active', $_tak_review_active );
		if ( isset( $_tak_review_active ) && $_tak_review_active !== '' ) {
			if ( isset( $_tak_review_url ) && $_tak_review_url !== '' ) {
				if ( isset( $_tak_review_state ) && $_tak_review_state === '' ) {
					try {
						$outcome      = $this->get_review_status_rest( $_tak_review_url );
						$json_outcome = json_decode( $outcome->data['body_response'], true );
						if ( $json_outcome['ping'] === '1' ) {
							update_post_meta( $post_id, '_tak_review_state', '1' );
						}
					} catch ( Exception $e ) {
						error_log(
							'Taknalogy Review host is not reachable, I will try again -- ' . 'Caught exception: ' . $e->getMessage()
						);
					}
				}
			}
		} else {
			do_action( 'wp_update_comment_count', get_the_ID() );
		}
	}
} // End Class
