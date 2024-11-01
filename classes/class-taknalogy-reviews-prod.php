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
final class Taknalogy_Reviews_Prod {
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
		add_action( 'rest_api_init', array( $this, 'tak_rest_api_init_prod' ) );
		add_action( 'rest_api_init', array( $this, 'tak_rest_api_init_update' ) );
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
	public function tak_rest_api_init_update() {
		register_rest_route(
			'tak/v1',
			'meta',
			array(
				'methods'  => 'POST',
				'callback' => array( $this, 'tak_prod_update_meta' ),
			)
		);
	}
	public function tak_prod_update_meta() {

		$data    = json_decode( file_get_contents( 'php://input' ), true );
		$options = get_option( 'tak_review_settings' );
		if ( isset( $options ) && isset( $options['tak_review_shopkey'] ) ) {
			if ( $data['shopkey'] !== $options['tak_review_shopkey'] ) {
				return array( 'error' => 'auth failure.' );
			}
		}
		$tak_review_sup = '';
		if ( strpos( $data['_tak_review_url'], 'aliexpress.com' ) !== false ) {
			$tak_review_sup = 'aliexpress';
		}
		update_post_meta( $data['pid'], '_tak_review_sup', $tak_review_sup );
		update_post_meta( $data['pid'], '_tak_review_url', $data['_tak_review_url'] );
		update_post_meta( $data['pid'], '_tak_review_active', 'yes' );
		update_post_meta( $data['pid'], '_tak_review_widget', 'yes' );
		return array( 'success' => 'updated.' );
	}

	/**
	 * Registers new products to the server
	 */
	public function tak_rest_api_init_prod() {
		register_rest_route(
			'tak/v1',
			'prod',
			array(
				'methods'  => 'GET',
				'callback' => array( $this, 'tak_prod_list' ),
			)
		);
	}
	public function tak_prod_list() {

		$pagec       = isset( $_GET['page'] ) ? $_GET['page'] : 1;
		$page_length = isset( $_GET['pagelength'] ) ? $_GET['pagelength'] : 10;
		switch ( $_REQUEST['command'] ) {
			case 'one':
				if ( isset( $_GET['userid'] ) && isset( $_GET['shopkey'] ) ) {
					$options = get_option( 'tak_review_settings' );
					if ( isset( $options ) && isset( $options['tak_review_shopkey'] ) ) {
						if ( $_GET['shopkey'] !== $options['tak_review_shopkey'] ) {
							return array( 'error' => 'Shopkey didnot match.' );
						}
					} else {
						return array( 'error' => 'Taknalogy Reviews not found on your shop' );
					}
				} else {
					return array(
						'outcome' => 'failed',
						'message' => 'query parameter is missing',
						'count'   => 0,
					);
				}
				$product_title_in = '';
				$tak_product_url  = ( explode( '//', $_GET['activeURL'] ) );
				$tak_product_url  = explode( '?', $tak_product_url[1] );
				$flag             = false;
				if ( strpos( $tak_product_url[0], 'aliexpress' ) !== false ) {
					$url_string       = preg_split( '#/#', $tak_product_url[0] );
					$product_title_in = explode( '.', end( $url_string ) )[0];
					$flag             = true;
				}
				$selected_product = null;
				if ( $flag ) {
					$cc_args  = array(
						'posts_per_page' => 1,
						'post_type'      => 'product',
						'meta_query'     => array(
							array(
								'key'     => '_tak_review_url',
								'value'   => $product_title_in,
								'compare' => 'LIKE',
							),
						),
					);
					$cc_query = new WP_Query( $cc_args );
					if ( $cc_query->have_posts() ) {
						$cc_query->the_post();
						$selected_product = array(
							// '_tak_review_active' => get_post_meta( $post->ID, '_tak_review_active', true ),
							'_tak_review_url' => get_post_meta( get_the_ID(), '_tak_review_url', true ),
							'_tak_review_sup' => get_post_meta( get_the_ID(), '_tak_review_sup', true ),
							'post_url'        => get_permalink( get_the_ID() ),
							'id'              => 0,
							'value'           => get_the_title(),
							'label'           => get_the_title(),
							'post_id'         => get_the_ID(),
							'csku'            => wc_get_product( get_the_ID() )->get_sku(),
						);
					}
					return array(
						'outcome' => 'success',
						'data'    => $selected_product,
					);
				}
				break;
			case 'list':
				if ( isset( $_GET['userid'] ) && isset( $_GET['shopkey'] ) ) {
					$options = get_option( 'tak_review_settings' );
					if ( isset( $options ) && isset( $options['tak_review_shopkey'] ) ) {
						if ( $_GET['shopkey'] !== $options['tak_review_shopkey'] ) {
							return array( 'error' => 'Shopkey didnot match.' );
						}
					} else {
						return array( 'error' => 'Taknalogy Reviews not found on your shop' );
					}
					$query = new WP_Query(
						array(
							'post_type'       => 'product',
							'posts_per_page'  => $page_length,
							'paged'           => $pagec,
							'post_status'     => 'publish',
							'orderby'         => 'publish_date',
							'order'           => 'DESC',
							'post_title_like' => isset( $_GET['s'] ) ? $_GET['s'] : '',
							// 's'              => isset( $_GET['s'] ) ? $_GET['s'] : '',
						)
					);
					$result_products            = array();
					$result_products['outcome'] = 'success';
					$result_products['data']    = array();
					$result_products['count']   = $query->found_posts;
					$counter                    = 0;
					foreach ( $query->posts as $post ) {
						$result_products['data'][ $counter ] = array(
							// '_tak_review_active' => get_post_meta( $post->ID, '_tak_review_active', true ),
							'_tak_review_url' => get_post_meta( $post->ID, '_tak_review_url', true ),
							'_tak_review_sup' => get_post_meta( $post->ID, '_tak_review_sup', true ),
							'post_url'        => get_permalink( $post->ID ),
							'id'              => $counter,
							'value'           => $post->post_title,
							'label'           => $post->post_title,
							'post_id'         => $post->ID,
							'csku'            => wc_get_product( $post->ID )->get_sku(),
						);
						$counter                             = $counter + 1;
					}
					return array(
						'outcome' => 'success',
						'data'    => $result_products,
					);
				} else {
					return array(
						'outcome' => 'failed',
						'message' => 'query parameter is missing',
						'count'   => 0,
					);
				}
				break;
			default:
		}
	}
} // End Class
