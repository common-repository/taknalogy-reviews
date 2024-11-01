<?php
	$tak_product_url = get_post_meta( get_the_ID(), '_tak_review_url', true );
	$tak_product_url = ( explode( '//', $tak_product_url ) );
	$tak_product_url = explode( '?', $tak_product_url[1] );
if ( strpos( $tak_product_url[0], 'aliexpress' ) !== false ) {
	$url_string       = preg_split( '#/#', $tak_product_url[0] );
	$product_title_in = explode( '.', end( $url_string ) )[0];
}
?>
<div id="comments" class="comments-area">
	<div class="video-container iframe-container" id="takvideocontainer">
		<iframe id="takIframeId"  src="<?php echo TAK_WOO_REVIEWS_URL; ?>/?q=<?php echo $product_title_in; ?>&shop=<?php echo get_home_url(); ?>&v=2&url=<?php echo get_permalink( get_the_ID() ); ?>&pid=<?php echo get_the_ID(); ?>&title=<?php echo get_the_title( get_the_ID() ); ?>" scrolling="no" frameborder="0"   allowfullscreen></iframe>
	</div>
</div>
<div class="clearfix"> </div>
