<?php
/**
 * Plugin Name: WooCommerce Rich Reviews Lite
 * Description: Enables segmented ratings and rich text editor (WYSIWYG) in WooCommerce reviews.
 * Author: Nazmul Ahsan
 * Author URI: http://nazmulahsan.me
 * Plugin URI: http://medhabi.com
 * Version: 1.0.0
 */

// if accessed directly, exit.
if( ! defined( 'ABSPATH' ) ) exit();

global $wrr_pro;
$wrr_pro = 'http://medhabi.com/product/wc-rich-reviews';

/**
 * @package WooCommerce
 * @subpackage WC_Rich_Reviews_Lite
 * @author Nazmul Ahsan <n.mukto@gmail.com>
 */
require_once dirname( __FILE__ ) . '/admin/wc-rich-reviews-settings.php';

if( ! class_exists( 'WC_Rich_Reviews_Lite' ) && ! class_exists( 'WC_Rich_Reviews' ) ) :
class WC_Rich_Reviews_Lite {
	
	/**
	 * @var int $out_of scale of rating
	 * ignore. maybe for future version releases.
	 */
	public $out_of = 5;

	/**
	 * @var boolean $is_segmented_rating either the segmented rating is enabled.
	 */
	public $is_segmented_rating = true;

	/**
	 * @var boolean $is_segmented_rating either the rich editor is enabled.
	 */
	public $is_rich_editor = false;

	/**
	 * @var boolean $average_by_segment_count If segment average is calculated by this specific segment count.
	 * ignore. maybe for future version releases.
	 */
	public $average_by_segment_count = false;

	public function __construct(){
		add_action( 'init', array( $this, 'define' ) );
		add_filter( 'woocommerce_product_review_comment_form_args', array( $this, 'review_form' ), 99 );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), array($this, 'add_action_links') );
		add_filter( 'admin_notices', array($this, 'admin_notice') );
		if( get_option( 'woocommerce_enable_review_rating' ) === 'yes' ) $this->rating_hooks();
	}

	public function rating_hooks()	{
		add_action( 'plugins_loaded', array( $this, 'copy_template' ) );
		add_action( 'comment_post', array( $this, 'store_fields' ), 99 );
		add_action( 'woocommerce_review_before_comment_text', array( $this, 'show_fields' ) );
		add_action( 'woocommerce_single_product_summary', array( $this, 'show_segmented_ratings' ) );
	}

	/**
	 * re-define some values
	 */
	public function define(){

		if( mdc_get_option( 'enable', 'wc_segmented_ratings' ) != 'on' || ( mdc_get_option( 'member_only', 'wc_segmented_ratings' ) == 'on' && ! $this->is_verified_buyer() ) ){
			echo $this->is_segmented_rating = false;
		}
	}

	/**
	 * add JavaScripts and Stylesheets
	 * @return void
	 */
	public function enqueue_scripts(){
		wp_enqueue_style( 'woo-rich-rating', plugin_dir_url( __FILE__ ) . 'assets/css/style.css' );
		wp_enqueue_script( 'woo-rich-rating', plugin_dir_url( __FILE__ ) . 'assets/js/script.js', array('jquery'), '1.0.0', true );
	}

	/**
	 * Retrieves rating params of a product
	 * @param int $post_id product/post ID
	 * @return associative array of params
	 */
	public function get_params( $post_id ){
		$default = 'price|Price'.PHP_EOL.'quality|Quality';

		$params = ( mdc_get_option( 'fields', 'wc_segmented_ratings' ) != null ) ?mdc_get_option( 'fields', 'wc_segmented_ratings' ) : $default;

		$exploded = explode( PHP_EOL, $params );

		foreach ( $exploded as $field ) {
			$key = explode( '|', $field )[0];
			$label = ( explode('|', $field )[1]) ? ( explode('|', $field )[1] ) : $key;
			$fields[$key] = trim( $label );
		}
		return apply_filters( 'wc_rating_params', $fields );
	}

	/**
	 * add rating fields in the comment form
	 * @param mix $comment_form existing HTML form
	 * @uses template/single-product-reviews.php
	 * @return modified $comment_form
	 */
	public function review_form( $comment_form ) {
		global $post;
		$comment_form['comment_field'] = '<input type="hidden" name="rating" id="overall-rating">';

		// segmented ratings
		if( get_option( 'woocommerce_enable_review_rating' ) === 'yes' ){
			if( $this->is_segmented_rating ){
				foreach ( $this->get_params( $post->ID ) as $key=>$label ) {

					$comment_form['comment_field'] .= '
						<div class="woo-rich-rating comment-form-' . $key . '">
							<label for="' . $key . '">' . __($label) . '<!--span class="required">*</span--></label>
							<p class="stars"><span>';

							for( $i = 1; $i <= $this->out_of; $i++ ){
								$comment_form['comment_field'] .= '<a href="#" class="star-' . $i . '">' . $i . '</a>';
							}

					$comment_form['comment_field'] .= '</span></p>
							<input id="' . $key . '" class="single-rating" name="' . $key . '" type="hidden" />
						</div>';

				}
			} else{
				$comment_form['comment_field'] = '<p class="comment-form-rating"><label for="rating">' . __( 'Your Rating', 'WooCommerce' ) .'</label><select name="rating" id="rating">
							<option value="">' . __( 'Rate&hellip;', 'WooCommerce' ) . '</option>
							<option value="5">' . __( 'Perfect', 'WooCommerce' ) . '</option>
							<option value="4">' . __( 'Good', 'WooCommerce' ) . '</option>
							<option value="3">' . __( 'Average', 'WooCommerce' ) . '</option>
							<option value="2">' . __( 'Not that bad', 'WooCommerce' ) . '</option>
							<option value="1">' . __( 'Very Poor', 'WooCommerce' ) . '</option>
						</select></p>';
			}
		}
		$comment_form['comment_field'] .= '<p class="comment-form-comment"><label for="comment">' . __( 'Your Review', 'WooCommerce' ) . '</label><textarea id="comment" name="comment" cols="45" rows="8" aria-required="true"></textarea></p>';
		
		return $comment_form;
	}

	/**
	 * store rating fields' value to the database
	 * @param int $comment_id ID of the comment just been published
	 * @return void
	 */
	public function store_fields( $comment_id ) {
		if ( 'product' === get_post_type( $_POST['comment_post_ID'] ) ) {

			if ( ! count( $this->get_params( $_POST['comment_post_ID'] ) ) ) {
				return;
			}

			$segment_count = 0;
			$segment_rating = 0;
			foreach ( $this->get_params( $_POST['comment_post_ID'] ) as $key=>$label ) {
				if ( isset( $_POST[$key] ) ) {
					add_comment_meta( $comment_id, $key, $_POST[$key], true );
					$segment_count++;
					$segment_rating += $_POST[$key];
				}
			}
			update_comment_meta( $comment_id, 'rating', $segment_rating / $segment_count );
		}
	}

	/**
	 * shows rating segments in published comments' segments
	 * @param object $comment the comment we are dealing with
	 * @return void
	 */
	public function show_fields( $comment ){
		global $post;
		foreach ( $this->get_params( $post->ID ) as $key=>$label ) {
			if ( get_comment_meta($comment->comment_ID, $key, true ) != null ) {
				$rating = get_comment_meta($comment->comment_ID, $key, true );
				?>
				<div class="single-rating-segment">
					<label><?php echo $label; ?>: </label>
					<div itemprop="reviewRating" itemscope itemtype="http://schema.org/Rating" class="star-rating" title="<?php echo sprintf( __( 'Rated %d out of 5', 'WooCommerce' ), $rating ) ?>">
						<span style="width:<?php echo ( $rating / 5 ) * 100; ?>%"><strong itemprop="ratingValue"><?php echo $rating; ?></strong> <?php _e( 'out of 5', 'WooCommerce' ); ?></span>
					</div>
				</div>
				<?php
			}
		}
	}

	/**
	 * shows segmented ratings along with main product rating
	 * @return void
	 */
	public function show_segmented_ratings(){
		global $post;
		$product_id = $post->ID;

		$ratings = array();
		$rating_counter = array();

		$args = array(
			'post_id'	=>	$product_id
			);
		$comments = get_comments( $args );
		
		foreach ( $comments as $comment ) {
			foreach ( $this->get_params( $product_id ) as $key=>$label ) {
				$segment_value = get_comment_meta( $comment->comment_ID, $key, true );
				if( ! isset( $ratings[$key] ) ) $ratings[$key] = 0;
				$ratings[$key] += $segment_value;
				if( $segment_value > 0 ){
					if( ! isset( $rating_counter[$key] ) ) $rating_counter[$key] = 0;
					$rating_counter[$key] ++;
				}
			}
		}

		foreach ( $ratings as $key => $rating ) {

			if( $this->average_by_segment_count && isset( $rating_counter[$key] ) ){
				$single_rating = $rating / $rating_counter[$key];
			}
			else{
				$single_rating = $rating / count( $comments );
			}
			?>

			<div class="single-rating-segment">
				<label><?php echo $this->get_params( $product_id )[$key]; ?>: </label>
				<div class="star-rating" title="<?php echo sprintf( __( 'Rated %0.2f out of 5', 'WooCommerce' ), $single_rating ) ?>">
					<span style="width:<?php echo ( $single_rating / 5 ) * 100; ?>%"><strong><?php echo $single_rating; ?></strong> <?php _e( 'out of 5', 'WooCommerce' ); ?></span>
				</div>
			</div>

			<?php
		}
	}

	/**
	 * copy modified WC template to theme directory
	 * @uses plugins/WooCommerce/templates/single-product/review.php
	 */
	public function copy_template(){
		$dir = get_stylesheet_directory() . '/WooCommerce/single-product';
		if( ! is_dir( $dir ) ){
			@wp_mkdir_p( $dir );
		}

		/**
		 * a 'week check'. if the files are not same, then copy
		 */
		if( @md5_file( dirname( __FILE__ ) . '/templates/review.php' ) != @md5_file( $dir . '/review.php' ) ){
			copy( dirname( __FILE__ ) . '/templates/review.php' , $dir . '/review.php' );
		}
	}

	/**
	 * Add settings link in plugin list
	 */
	public function add_action_links ( $links ) {
		global $wrr_pro;
		$menu_link = array(
			'<a href="' . admin_url( 'admin.php?page=wc-rich-reviews-settings' ) . '">Settings</a>'
		);

		if( function_exists( 'WC' ) ){
			$links = array_merge( $menu_link, $links );
		}

		$pro_link = array(
			'<a href="' . $wrr_pro . '" target="_blank" style="color: red"><span class="dashicons dashicons-shield"></span> Pro Version</a>'
		);
		$links = array_merge( $pro_link, $links );
		
		return $links;
	}

	/**
	 * check a user if he is a verified buyer
	 * @return boolean
	 * @issue $product_id won't work for default permalink settings
	 */
	public function is_verified_buyer()	{
		$product_id = url_to_postid( get_bloginfo( 'url' ) . "$_SERVER[REQUEST_URI]" );
		if( ! function_exists( 'wc_customer_bought_product' ) ) return false;
		return wc_customer_bought_product( '', get_current_user_id(), $product_id );
	}

	/**
	 * Show admin notice if WooCommerce is not acivated
	 */
	public function admin_notice() {
		if( ! function_exists( 'WC' ) ){ ?>
	    <div class="notice notice-warning is-dismissible">
			<p><strong>WooCommerce Rich Reviews</strong> is an add-on of Woocommrce. It requires WooCommerce to be activated!</p>
		</div>
	    <?php }
	}

}
endif;

new WC_Rich_Reviews_Lite;