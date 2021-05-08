<?php
/**
 * Tista Rocket class.
 *
 * @package Tista_Rocket
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if( class_exists( 'Woocommerce' ) ):
	if ( ! class_exists( 'Tista_Rocket' ) ) :

	/**
	 * It's the main class that does all the things.
	 *
	 * @class Tista_Rocket
	 * @version 4.2.1
	 * @since 1.0.0
	 */
	class Tista_Rocket extends WC_Payment_Gateway {
			
			/**
			 * The single class instance.
			 *
			 * @since 1.0.0
			 * @access public
			 *
			 * @var object
			 */
			public $rocket_number;
			public $number_type;
			public $order_status;
			public $instructions;
			public $rocket_charge;
			/**
			 * class constructor
			 * @since 1.0.0
			 * @access public
			 * @codeCoverageIgnore
			 */
			public function __construct(){
				$this->id 					= 'tista_rocket';
				$this->title 				= $this->get_option('title', 'Rocket P2P Gateway');
				$this->description 			= $this->get_option('description', 'Rocket payment Gateway');
				$this->method_title 		= esc_html__("Rocket", "tista");
				$this->method_description 	= esc_html__("Rocket Payment Gateway Options", "tista" );
				$this->icon 				= plugins_url('images/rocket.png', __FILE__);
				$this->has_fields 			= true;

				$this->tista_rocket_options_fields();
				$this->init_settings();
				
				$this->rocket_number = $this->get_option('rocket_number');
				$this->number_type 	= $this->get_option('number_type');
				$this->order_status = $this->get_option('order_status');
				$this->instructions = $this->get_option('instructions');
				$this->rocket_charge = $this->get_option('rocket_charge');

				add_action( 'woocommerce_update_options_payment_gateways_'.$this->id, array( $this, 'process_admin_options' ) );
	            add_filter( 'woocommerce_thankyou_order_received_text', array( $this, 'tista_rocket_thankyou_page' ) );
	            add_action( 'woocommerce_email_before_order_table', array( $this, 'tista_rocket_number_instructions' ), 10, 3 );
			}
			/**
			 * Options fields
			 *
			 *@access  public
			*/
			public function tista_rocket_options_fields(){
				$this->form_fields = array(
					'enabled' 	=>	array(
						'title'		=> esc_html__( 'Enable/Disable', "tista" ),
						'type' 		=> 'checkbox',
						'label'		=> esc_html__( 'Rocket Payment', "tista" ),
						'default'	=> 'yes'
					),
					'title' 	=> array(
						'title' 	=> esc_html__( 'Title', "tista" ),
						'type' 		=> 'text',
						'default'	=> esc_html__( 'Rocket', "tista" )
					),
					'description' => array(
						'title'		=> esc_html__( 'Description', "tista" ),
						'type' 		=> 'textarea',
						'default'	=> esc_html__( 'Please complete your rocket payment at first, then fill up the form below.', "tista" ),
						'desc_tip'    => true
					),
	                'order_status' => array(
	                    'title'       => esc_html__( 'Order Status', "tista" ),
	                    'type'        => 'select',
	                    'class'       => 'wc-enhanced-select',
	                    'description' => esc_html__( 'Choose whether status you wish after checkout.', "tista" ),
	                    'default'     => 'wc-on-hold',
	                    'desc_tip'    => true,
	                    'options'     => wc_get_order_statuses()
	                ),				
					'rocket_number'	=> array(
						'title'			=> esc_html__( 'Rocket Number', "tista" ),
						'description' 	=> esc_html__( 'Add a rocket Number which will be shown in checkout page', "tista" ),
						'type'			=> 'number',
						'desc_tip'      => true
					),
					'number_type'	=> array(
						'title'			=> esc_html__( 'Rocket Account Type', "tista" ),
						'type'			=> 'select',
						'class'       	=> 'wc-enhanced-select',
						'description' 	=> esc_html__( 'Select rocket account type', "tista" ),
						'options'	=> array(
							'Agent'		=> esc_html__( 'Agent', "tista" ),
							'Marchent'	=> esc_html__( 'Marchent', "tista" ),
							'Personal'	=> esc_html__( 'Personal', "tista" )
						),
						'desc_tip'      => true
					),
					'rocket_charge' 	=>	array(
						'title'			=> esc_html__( 'Enable Rocket Charge', "tista" ),
						'type' 			=> 'checkbox',
						'label'			=> esc_html__( 'Add 2% Rocket "Payment" charge to net price', "tista" ),
						'description' 	=> esc_html__( 'If a product price is 1000 then customer have to pay ( 1000 + 20 ) = 1020. Here 20 is rocket charge', "tista" ),
						'default'		=> 'no',
						'desc_tip'    	=> true
					),						
	                'instructions' => array(
	                    'title'       	=> esc_html__( 'Instructions', "tista" ),
	                    'type'        	=> 'textarea',
	                    'description' 	=> esc_html__( 'Instructions that will be added to the thank you page and emails.', "tista" ),
	                    'default'     	=> esc_html__( 'Thanks for purchasing through rocket. We will check and give you update as soon as possible.', "tista" ),
	                    'desc_tip'    	=> true
	                ),								
				);
			}
			/**
			 * Payment fields
			 *
			 *@access  public
			*/
			public function payment_fields(){

				global $woocommerce;
				$rocket_charge = ($this->rocket_charge == 'yes') ? esc_html__(' Also note that 2% rocket "Payment" cost will be added with net price. Total amount you need to send us at', "tista" ). ' ' . get_woocommerce_currency_symbol() . $woocommerce->cart->total : '';
				echo wpautop( wptexturize( esc_html__( $this->description, "tista" ) ) . $rocket_charge  );
				echo wpautop( wptexturize( "Rocket ".$this->number_type." Number : ".$this->rocket_number ) );

				?>
					<p>
						<label for="rocket_number"><?php esc_html_e( 'Rocket Number', "tista" );?></label>
						<input type="text" name="rocket_number" id="Rocket_number" placeholder="017XXXXXXXX">
					</p>
					<p>
						<label for="rocket_transaction_id"><?php esc_html_e( 'Rocket Transaction ID', "tista" );?></label>
						<input type="text" name="rocket_transaction_id" id="rocket_transaction_id" placeholder="8N7A6D5EE7M">
					</p>
				<?php 
			}
			
			/**
			 * Payment processing
			 *
			 *@access  public
			*/
			public function process_payment( $order_id ) {
				global $woocommerce;
				$order = new WC_Order( $order_id );
				
				$status = 'wc-' === substr( $this->order_status, 0, 3 ) ? substr( $this->order_status, 3 ) : $this->order_status;
				// Mark as on-hold (we're awaiting the rocket)
				$order->update_status( $status, esc_html__( 'Checkout with rocket payment. ', "tista" ) );

				// Reduce stock levels
				$order->reduce_order_stock();

				// Remove cart
				$woocommerce->cart->empty_cart();

				// Return thankyou redirect
				return array(
					'result' => 'success',
					'redirect' => $this->get_return_url( $order )
				);
			}	
			/**
			 * Thank you page
			 *
			 *@access  public
			*/
	        public function tista_rocket_thankyou_page() {
			    $order_id = get_query_var('order-received');
			    $order = new WC_Order( $order_id );
			    if( $order->payment_method == $this->id ){
		            $thankyou = $this->instructions;
		            return $thankyou;		        
			    } else {
			    	return esc_html__( 'Thank you. Your order has been received.', "tista" );
			    }

	        }

			/**
			 * Instruction 
			 *
			 *@access  public
			*/
	        public function tista_rocket_number_instructions( $order, $sent_to_admin, $plain_text = false ) {
			    if( $order->payment_method != $this->id )
			        return;        	
	            if ( $this->instructions && ! $sent_to_admin && $this->id === $order->payment_method ) {
	                echo wpautop( wptexturize( $this->instructions ) ) . PHP_EOL;
	            }
	        }		
	}
	endif;
endif;