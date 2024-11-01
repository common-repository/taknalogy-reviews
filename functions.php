<?php
/**
 * Usual functions file
 */
function url_origin( $s, $use_forwarded_host = false ) {
	$ssl      = ( ! empty( $s['HTTPS'] ) && $s['HTTPS'] == 'on' );
	$sp       = strtolower( $s['SERVER_PROTOCOL'] );
	$protocol = substr( $sp, 0, strpos( $sp, '/' ) ) . ( ( $ssl ) ? 's' : '' );
	$port     = $s['SERVER_PORT'];
	$port     = ( ( ! $ssl && $port == '80' ) || ( $ssl && $port == '443' ) ) ? '' : ':' . $port;
	$host     = ( $use_forwarded_host && isset( $s['HTTP_X_FORWARDED_HOST'] ) ) ? $s['HTTP_X_FORWARDED_HOST'] : ( isset( $s['HTTP_HOST'] ) ? $s['HTTP_HOST'] : null );
	$host     = isset( $host ) ? $host : $s['SERVER_NAME'] . $port;
	return $protocol . '://' . $host;
}
function full_url( $s, $use_forwarded_host = false ) {
	return url_origin( $s, $use_forwarded_host ) . $s['REQUEST_URI'];
}
$absolute_url = full_url( $_SERVER );
function remove_http( $url ) {
	$disallowed = array( 'http://', 'https://' );
	foreach ( $disallowed as $d ) {
		if ( strpos( $url, $d ) === 0 ) {
			return str_replace( $d, '', $url );
		}
	}
	return $url;
}
function tak_reviews_plugin_comment_template( $comment_template ) {

	if ( strcmp( get_post_meta( get_the_id(), '_tak_review_active', true ), 'yes' ) == 0 ) {
		// if( get_post_meta( get_the_id(), '_tak_review_url', true ) ) {
		return TAK_DIR_PATH . '/templates/single-product-reviews.php';
	} else {
		return $comment_template;
	}
}
add_filter( 'comments_template', 'tak_reviews_plugin_comment_template', 1000, 1 );
/**
 * Enqueue plugin style-file
 */
function tak_add_scripts() {
	wp_enqueue_style( 'taknalogy-reviews-style', plugins_url( 'assets/css/style.css', __FILE__ ) );
	wp_enqueue_script( 'taknalogy-reviews-script', plugins_url( 'assets/js/script.js', __FILE__ ), array( 'jquery' ) );
	wp_enqueue_script( 'font-awesome-script', plugins_url( 'assets/js/awesome/1fc0141d38.js', __FILE__ ) );
	wp_localize_script(
		'taknalogy-reviews-script',
		'tak_data',
		array(
			'tak_ajax_url'              => admin_url( 'admin-ajax.php' ),
			'tak_update_nonce'          => wp_create_nonce( 'tak_update_nonce_action' ),
			'tak_pid'                   => get_the_ID(),
			'_wc_review_count_client'   => get_post_meta( get_the_ID(), '_wc_review_count', true ),
			'_wc_rating_count_client'   => json_encode( get_post_meta( get_the_ID(), '_wc_rating_count', true ) ),
			'_wc_average_rating_client' => get_post_meta( get_the_ID(), '_wc_average_rating', true ),
			'styleoverrides'            => '',
		)
	);
}
add_action( 'wp_enqueue_scripts', 'tak_add_scripts', 10 );
/**
 * Ajax client to update ratings meta
 */
function tak_ajax_meta_update_process() {
	// extra referer checks
	if ( isset( $_POST['tak_update_nonce'] ) && ! empty( $_POST['tak_update_nonce'] ) ) {
		if ( wp_verify_nonce( $_POST['tak_update_nonce'], 'tak_update_nonce_action' ) && check_ajax_referer(
			'tak_update_nonce_action',
			'tak_update_nonce'
		) ) {
			if ( isset( $_POST['tak_pid'] ) && ! empty( $_POST['tak_pid'] ) ) {
				$post_id                 = sanitize_text_field( $_POST['tak_pid'] );
				$_wc_review_count_client = sanitize_text_field( $_POST['_wc_review_count_client'] );
				// $_wc_rating_count_client   = sanitize_text_field( $_POST['_wc_rating_count_client'] );
				$_wc_average_rating_client = sanitize_text_field( $_POST['_wc_average_rating_client'] );
				$str                       = preg_replace( '/\\\"/', '"', $_POST['_wc_rating_count_client'] );
				update_post_meta( $post_id, '_wc_review_count', $_wc_review_count_client );
				update_post_meta( $post_id, '_wc_rating_count', json_decode( $str, true ) );
				update_post_meta( $post_id, '_wc_average_rating', $_wc_average_rating_client );
				wp_send_json( 'success' );
			}
		}
		wp_send_json( 'failed' );
		wp_die();
	}
}
add_action( 'wp_ajax_nopriv_tak_ajax_meta_update', 'tak_ajax_meta_update_process' );
add_action( 'wp_ajax_tak_ajax_meta_update', 'tak_ajax_meta_update_process' );
function reset_comment_count() {
	if ( current_user_can( 'edit_posts' ) ) {
		$the_query = new WP_Query(
			array(
				'post_type'      => 'product',
				'posts_per_page' => -1,
				'meta_key'       => '_tak_review_active',
				'meta_value'     => 'yes',
			)
		);
		if ( $the_query->have_posts() ) {
			while ( $the_query->have_posts() ) {
				$the_query->the_post();
				do_action( 'wp_update_comment_count', get_the_ID() );
			}
		}
	}
}
function delete_tak_review_post_meta() {
	if ( current_user_can( 'edit_posts' ) ) {
		delete_post_meta_by_key( '_tak_review_sup' );
		delete_post_meta_by_key( '_tak_review_url' );
		delete_post_meta_by_key( '_tak_review_active' );
		delete_post_meta_by_key( '_tak_review_state' );
	}
}
function inform_remote_host( $command ) {

	$body = array(
		'options'                  => get_option( 'tak_review_settings' ),
		'_action'                  => $command,
		'_tak_review_client_url'   => get_home_url(),
		'_tak_review_client_title' => get_bloginfo( 'name' ),
	);
	$url  = TAK_WOO_REST_URL;
	$body = json_encode( $body );
	// error_log( print_r( $body, true ) );
	/*
		array(
			'_tak_review_client_url' => get_site_url(),
			'_action'                => 'uninstall',
		)
	);
	*/
	$headers = array(
		'Content-type' => 'application/json',
		'sslverify'    => TAK_WOO_SSLVERIFY,
	);
	wp_remote_post(
		$url,
		array(
			'method'      => 'POST',
			'timeout'     => 20,
			'redirection' => 5,
			'httpversion' => '1.0',
			'blocking'    => false,
			'headers'     => $headers,
			'body'        => $body, // array( 'username' => 'bob', 'password' => '1234xyz' ),
			'cookies'     => array(),
		)
	);
}

add_filter( 'woocommerce_structured_data_product', 'tak_woocommerce_structured_data_product', 10, 2 );
function tak_woocommerce_structured_data_product( $markup, $product ) {

	if ( ! is_product() ) {
		return $markup;
	}

	/*
	if ( $product->get_rating_count() && wc_review_ratings_enabled() ) {

			// Markup 5 most recent rating/review.
			$comments = get_comments(
				array(
					'number'      => 5,
					'post_id'     => $product->get_id(),
					'status'      => 'approve',
					'post_status' => 'publish',
					'post_type'   => 'product',
					'parent'      => 0,
					'meta_query'  => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
						array(
							'key'     => 'rating',
							'type'    => 'NUMERIC',
							'compare' => '>',
							'value'   => 0,
						),
					),
				)
			);

			if ( $comments ) {
				$markup['review'] = array();
				foreach ( $comments as $comment ) {
					$markup['review'][] = array(
						'@type'         => 'Review',
						'reviewRating'  => array(
							'@type'       => 'Rating',
							'ratingValue' => get_comment_meta( $comment->comment_ID, 'rating', true ),
						),
						'author'        => array(
							'@type' => 'Person',
							'name'  => get_comment_author( $comment ),
						),
						'reviewBody'    => get_comment_text( $comment ),
						'datePublished' => get_comment_date( 'c', $comment ),
					);
				}
			}
		}
	}
	*/
	return $markup;
}




add_filter( 'posts_where', 'tak_title_like_posts_where', 10, 2 );
function tak_title_like_posts_where( $where, $wp_query ) {

	global $wpdb;
	if ( $post_title_like = $wp_query->get( 'post_title_like' ) ) {
		$where .= ' AND ' . $wpdb->posts . '.post_title LIKE \'%' . esc_sql( $wpdb->esc_like( $post_title_like ) ) . '%\'';
	}
	return $where;
}
add_action( 'woocommerce_single_product_summary', 'tak_rating_insert_description', 8 );
function tak_rating_insert_description() {
	if ( get_post_meta( get_the_ID(), '_tak_review_widget', true ) ) {
		echo tak_get_rating_content();
	}
}
function tak_get_rating_content() {
	$comments_number = get_post_meta( get_the_ID(), '_wc_review_count', true );
	$rating          = get_post_meta( get_the_ID(), '_wc_average_rating', true );

	 return '<div id="tak-woocommerce-review-link">
		 <a href="#tab-title-reviews">
			 <div id="average-rating" class="tak-widget"> 
			 	<div class="ratings">
					<div class="empty-stars">
						<i class="fa star fa-star fa-sm"> </i>
						<i class="fa star fa-star fa-sm"> </i>
						<i class="fa star fa-star fa-sm"> </i>
						<i class="fa star fa-star fa-sm"> </i>
						<i class="fa star fa-star fa-sm"> </i>	
					</div>
					<div class="full-stars" style="width:' . ( $rating * 20 ) . '%">
						<i class="fa star fa-star fa-sm"> </i>
						<i class="fa star fa-star fa-sm"> </i>
						<i class="fa star fa-star fa-sm"> </i>
						<i class="fa star fa-star fa-sm"> </i>
						<i class="fa star fa-star fa-sm"> </i>
					</div>
				</div>
				<div style="display: inline-block; vertical-align: middle;">Rating&nbsp;' . $rating . '</div>
				<div style="display: inline-block; vertical-align: middle;">-&nbsp;' . $comments_number . '&nbsp;Reviews </div>
			</div>
			</a>
		</div>';
}

add_shortcode( 'takrating', 'tak_rating_insert_description_sc' );
function tak_rating_insert_description_sc( $atts ) {
	return tak_get_rating_content();
}
