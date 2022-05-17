<?php 
// Add Payment Gateway Setting option
class WC_Settings_As_Payment_Gateways {

    public static function init() {
        add_filter( 'woocommerce_settings_tabs_array', __CLASS__ . '::add_settings_tab', 50 );
        add_action( 'woocommerce_settings_tabs_as_payment_discount', __CLASS__ . '::settings_tab' );
        add_action( 'woocommerce_update_options_as_payment_discount', __CLASS__ . '::update_settings' );
    }

    public static function add_settings_tab( $settings_tabs ) {
        $settings_tabs['as_payment_discount'] = __( 'Payment Gateways Discount', 'woocommerce' );
        return $settings_tabs;
    }

  
    public static function settings_tab() {
        woocommerce_admin_fields( self::get_settings() );
    }



    public static function update_settings() {
        woocommerce_update_options( self::get_settings() );
    }

    public static function get_settings() {

    	$installed_payment_methods = WC()->payment_gateways->payment_gateways();


    	$get_settings = array();

    	$get_settings[] = array(
    		'name'     => 'Enter the discount percentage of your payment gateways!',
            'type'     => 'title',
            'desc'     => '',
            'id'       => 'wc_settings_tab_as_payment_discount_title'
    	);

    	foreach( $installed_payment_methods as $method_id => $method ) {
			$get_settings[] = array(
	                'name'     => $method->title,
	                'type'     => 'number',
	                'desc'     => '',
	                'id'       => 'wc_settings_discount_' . $method_id, 
	                'default' => 0
	            
			);
		}

		$get_settings[] = array(
            'type'     => 'sectionend',
            'desc'     => '',
            'id'       => 'wc_settings_tab_as_payment_discount_section_end'
    	);

        return apply_filters( 'wc_settings_as_payment_discount', $get_settings );
    }
}
WC_Settings_As_Payment_Gateways::init();


// Calculate Fees based on gateways
add_action( 'woocommerce_cart_calculate_fees', 'as_add_fee_discounter_per_payment_gateways', 25 );
function as_add_fee_discounter_per_payment_gateways( $cart ) {

	$current_method = WC()->session->get( 'chosen_payment_method' );
	$installed_payment_methods = WC()->payment_gateways->payment_gateways();
	$available_methods = array();
	$available_methods_title = array();
	$current_method_title = WC()->session->get( 'payment_method_title' );
	$subtotal = WC()->cart->get_subtotal();

	foreach( $installed_payment_methods as $method_id => $method ) {
		$available_methods[] = $method_id;
		$available_methods_title[$method_id] = $method->title;
	}

	if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
		return; 
	}

	if (in_array($current_method, $available_methods)){

		$opt_title = $available_methods_title[$current_method];
		$opt_name = 'wc_settings_discount_' . $current_method;
		$opt_val = get_option($opt_name);

		if($opt_val != 0){

			if($opt_val < 0){
				$dis_text = 'Discount';
			}else{
				$dis_text = 'Fee';
			}

			$calculate_discount = ($subtotal * $opt_val) / 100;
			WC()->cart->add_fee($opt_title . ' ' . $dis_text, $calculate_discount);
		}
		
	}


}

// Update Checkout on Gateway Selection 
add_action( 'woocommerce_checkout_init', 'as_checkout_refresh_on_payment_method_selection' );
function as_checkout_refresh_on_payment_method_selection() {
    wc_enqueue_js( "jQuery( function( $ ){
        $( 'form.checkout' ).on( 'change', 'input[name^=\"payment_method\"]', function(){
            $( 'body' ).trigger( 'update_checkout' );
        });
    });");
}
