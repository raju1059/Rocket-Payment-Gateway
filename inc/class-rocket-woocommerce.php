<?php
/**
 * Rocket Woocommerce class.
 *
 * @package Rocket_Woocommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}


if ( ! class_exists( 'Rocket_Woocommerce' ) ) :

	/**
	 * It's the main class that does all the things.
	 *
	 * @class Rocket_Woocommerce
	 * @version 4.2.1
	 * @since 1.0.0
	 */
	class Rocket_Woocommerce {
		
		/**
		 * class constructor
		 * @since 1.0.0
		 * @access public
		 * @codeCoverageIgnore
		 */
		public function __construct() {
			if( class_exists( 'Woocommerce' ) ){
				add_filter( 'woocommerce_payment_gateways', array( $this, 'tista_rocket_payment_gateways' ) );
				add_filter( 'plugin_action_links_'.plugin_basename(__FILE__), array( $this, 'tista_rocket_settings_link' ) );
				/**
				 * If rocket charge is activated
				 */
				$rocket_charge = get_option( 'woocommerce_tista_rocket_settings' );
				if( $rocket_charge['rocket_charge'] == 'yes' ){
					add_action( 'wp_enqueue_scripts', array( $this, 'tista_rocket_script' ), 10 );
					add_action( 'woocommerce_cart_calculate_fees', array( $this, 'tista_rocket_charge' ), 10 );	
				}
				add_action( 'woocommerce_checkout_process', array( $this, 'tista_rocket_payment_process' ), 10 );	
				add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'tista_rocket_additional_fields_update' ), 10,1 );	
				add_action( 'woocommerce_admin_order_data_after_billing_address', array( $this, 'tista_rocket_admin_order_data' ), 10,1 );	
				add_action( 'woocommerce_order_details_after_customer_details', array( $this, 'tista_rocket_additional_info_order_review_fields' ), 10,1 );	
				add_action( 'manage_shop_order_posts_custom_column', array( $this, 'tista_rocket_admin_column_value' ), 10,2 );	
				
				add_filter( 'manage_edit-shop_order_columns', array( $this, 'tista_rocket_admin_new_column' ) );
			
			}
		}
		/**
		* rocket gateway register
		*/
		public	function tista_rocket_payment_gateways( $gateways ){
			$gateways[] = 'tista_rocket';
			return $gateways;
		}
		/**
		* rocket script register
		*/
		public function tista_rocket_script(){
			wp_enqueue_script( 'tista-script',TRG_URI.'/js/scripts.js', array('jquery'), '1.0', true );
		}
		/**
		* rocket charge
		*/
		public function tista_rocket_charge(){

		    global $woocommerce;
		    $available_gateways = $woocommerce->payment_gateways->get_available_payment_gateways();
		    $current_gateway = '';

		    if ( !empty( $available_gateways ) ) {
		        if ( isset( $woocommerce->session->chosen_payment_method ) && isset( $available_gateways[ $woocommerce->session->chosen_payment_method ] ) ) {
		            $current_gateway = $available_gateways[ $woocommerce->session->chosen_payment_method ];
		        } 
		    }
		    
		    if( $current_gateway!='' ){
		        $current_gateway_id = $current_gateway->id;

				if ( is_admin() && ! defined( 'DOING_AJAX' ) )
					return;

				if ( $current_gateway_id =='tista_rocket' ) {
					$percentage = 0.02;
					$surcharge = ( $woocommerce->cart->cart_contents_total + $woocommerce->cart->shipping_total ) * $percentage;	
					$woocommerce->cart->add_fee( esc_html__('rocket Charge', 'tista'), $surcharge, true, '' ); 
				} 
		    }
		}
		/**
		 *  Add settings page link in plugins
		 *
		 *@access  public
		 */
		public function tista_rocket_settings_link( $links ) {
			
			$settings_links = array();
			$settings_links[] ='<a href="' . admin_url( 'admin.php?page=wc-settings&tab=checkout&section=tista_rocket' ) . '">' . esc_html__( 'Settings', 'stb' ) . '</a>';
			
			// add the links to the list of links already there
			foreach($settings_links as $link) {
				array_unshift($links, $link);
			}
			return $links;
		}
		/**
		 * Empty field validation
		 *
		 *@access  public
		 */
		public function tista_rocket_payment_process(){

			if($_POST['payment_method'] != 'tista_rocket')
				return;

			$rocket_number = sanitize_text_field( $_POST['rocket_number'] );
			$rocket_transaction_id = sanitize_text_field( $_POST['rocket_transaction_id'] );

			$match_number = isset($rocket_number) ? $rocket_number : '';
			$match_id = isset($rocket_transaction_id) ? $rocket_transaction_id : '';

			$validate_number = preg_match( '/^01[5-9]\d{8}$/', $match_number );
			$validate_id = preg_match( '/[a-zA-Z0-9]+/',  $match_id );

			if( !isset($rocket_number) || empty($rocket_number) )
				wc_add_notice( esc_html__( 'Please add Rocket Number', 'tista'), 'error' );

			if( !empty($rocket_number) && $validate_number == false )
				wc_add_notice( esc_html__( 'Rocket Number not valid', 'tista'), 'error' );

			if( !isset($rocket_transaction_id) || empty($rocket_transaction_id) )
				wc_add_notice( esc_html__( 'Please add your Rocket transaction ID', 'tista' ), 'error' );

			if( !empty($rocket_transaction_id) && $validate_id == false )
				wc_add_notice( esc_html__( 'Only number or letter is acceptable', 'tista'), 'error' );
		}
		
		/**
		 * Update rocket field to database
		 *
		 *@access  public
		 */
		public function tista_rocket_additional_fields_update( $order_id ){

			if($_POST['payment_method'] != 'tista_rocket' )
				return;

			$rocket_number = sanitize_text_field( $_POST['rocket_number'] );
			$rocket_transaction_id = sanitize_text_field( $_POST['rocket_transaction_id'] );

			$number = isset($rocket_number) ? $rocket_number : '';
			$transaction = isset($rocket_transaction_id) ? $rocket_transaction_id : '';

			update_post_meta($order_id, '_rocket_number', $number);
			update_post_meta($order_id, '_rocket_transaction', $transaction);

		}
		/**
		 * Admin order page rocket data output
		 *
		 *@access  public
		 */
		public function tista_rocket_admin_order_data( $order ){
			
			if( $order->payment_method != 'tista_rocket' )
				return;

			$number = (get_post_meta($order->id, '_rocket_number', true)) ? get_post_meta($order->id, '_rocket_number', true) : '';
			$transaction = (get_post_meta($order->id, '_rocket_transaction', true)) ? get_post_meta($order->id, '_rocket_transaction', true) : '';

			?>
			<div class="form-field form-field-wide">
				<img src='<?php echo plugins_url("images/rocket.png", __FILE__); ?>' alt="rocket">	
				<table class="wp-list-table widefat fixed striped posts">
					<tbody>
						<tr>
							<th><strong><?php esc_html_e('Rocket Number', 'tista') ;?></strong></th>
							<td>: <?php echo esc_attr( $number );?></td>
						</tr>
						<tr>
							<th><strong><?php esc_html_e('Transaction ID', 'tista') ;?></strong></th>
							<td>: <?php echo esc_attr( $transaction );?></td>
						</tr>
					</tbody>
				</table>
			</div>
			<?php 			
		}
		/**
		 * Order review page rocket data output
		 *
		 *@access  public
		 */
		public function tista_rocket_additional_info_order_review_fields( $order ){
			
			if( $order->payment_method != 'tista_rocket' )
				return;

			$number = (get_post_meta($order->id, '_rocket_number', true)) ? get_post_meta($order->id, '_rocket_number', true) : '';
			$transaction = (get_post_meta($order->id, '_rocket_transaction', true)) ? get_post_meta($order->id, '_rocket_transaction', true) : '';

			?>
				<tr>
					<th><?php esc_html_e('rocket Number:', 'tista');?></th>
					<td><?php echo esc_attr( $number );?></td>
				</tr>
				<tr>
					<th><?php esc_html_e('Transaction ID:', 'tista');?></th>
					<td><?php echo esc_attr( $transaction );?></td>
				</tr>
			<?php 			
		}
		/**
		 * Register new admin column
		 *
		 *@access  public
		 */
		public function tista_rocket_admin_new_column($columns){

			$new_columns = (is_array($columns)) ? $columns : array();
			unset( $new_columns['order_actions'] );
			$new_columns['mobile_no'] 	= esc_html__('Send From', 'stb');
			$new_columns['tran_id'] 	= esc_html__('Tran. ID', 'stb');

			$new_columns['order_actions'] = $columns['order_actions'];
			return $new_columns;

		}
		
		/**
		 *Load data in new column
		 *
		 *@access  public
		 */
		public function tista_rocket_admin_column_value($column){

			global $post;

			$mobile_no = (get_post_meta($post->ID, '_rocket_number', true)) ? get_post_meta($post->ID, '_rocket_number', true) : '';
			$tran_id = (get_post_meta($post->ID, '_rocket_transaction', true)) ? get_post_meta($post->ID, '_rocket_transaction', true) : '';

			if ( $column == 'mobile_no' ) {    
				echo esc_attr( $mobile_no );
			}
			if ( $column == 'tran_id' ) {    
				echo esc_attr( $tran_id );
			}
		}
	}
endif;