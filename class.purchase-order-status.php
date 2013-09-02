<?php
/*
 * Plugin Name: Woocommerce Purchase Order Payment Status
 * Author: Mahibul Hasan Sohag
 * Author Uri: http://sohag07hasan.elance.com
 * */

class WooPurchaseOrderPaymentStatus{
	
	function __construct(){
		//add a metabox in order details page
		add_action( 'add_meta_boxes', array(&$this, 'woocommerce_meta_boxes' ));
		
		//save the payment status for purchase order
		add_action('woocommerce_process_shop_order_meta', array(&$this, 'woocommerce_process_shop_order_meta'), 100, 2);
		
		//add new column for shop order table
		add_filter('manage_edit-shop_order_columns', array(&$this, 'woocommerce_edit_order_columns'), 100);
		
		//manage the custom column
		add_action('manage_shop_order_posts_custom_column', array(&$this, 'manage_newly_added_column'), 100);
		
		//assign the auto paid option
		add_action('init', array(&$this, 'auto_change_the_paid_status'), 1000);	

		//making the paid column sortable
		add_filter('manage_edit-shop_order_sortable_columns', array(&$this, 'making_the_paid_button_sortable'), 1000);		
		add_filter('request', array(&$this, 'process_the_sorting'), 1000);
		
		
	}	
	
	function woocommerce_meta_boxes(){
		add_meta_box( 'woocommerce-purchase-order-pyament-status', __( 'Purchase Order Payment Status', 'woocommerce' ), array(&$this, 'woocommerce_purchae_order_payemnt_satus'), 'shop_order', 'side', 'high' );
	}	
	
	function woocommerce_purchae_order_payemnt_satus($post){
		include dirname(__FILE__) . '/includes/metabox.purchase-order-payment-status.php';
	}	
	
	function woocommerce_process_shop_order_meta( $post_id, $post ){
		if(isset($_POST['purchase_order_payment_status'])){
			update_post_meta($post_id, '_purchase_order_payment_status', '1');
		}
		else{
			update_post_meta($post_id, '_purchase_order_payment_status', '0');
		}	
	}	
	
	function woocommerce_edit_order_columns($columns){
		$columns['purchase_order_status'] = __('Paid?', 'woocommerce');
		return $columns;
	}
	
	function manage_newly_added_column($column){
		global $post, $woocommerce, $the_order;
				
		if($column == 'purchase_order_status'){
			$status = get_post_meta($post->ID, '_purchase_order_payment_status', true);
			echo ($status == "1") ? "Yes" : "No"; 
		}
	}
		
	function auto_change_the_paid_status(){
		if(isset($_GET['order']) && isset($_GET['key'])){
			$order_id = $_GET['order'];
			$payment_status = get_post_meta($order_id, "_delayed_payment_status", true);
			if($payment_status == "complete"){
				update_post_meta($order_id, '_purchase_order_payment_status', '1');
			}
		
		}
	}
		
	function making_the_paid_button_sortable($columns){
		$columns['purchase_order_status'] = 'purchase_order_status';
		return $columns;
	}
	
	function process_the_sorting($vars){
		global $typenow, $wp_query;
		if ( $typenow != 'shop_order' )
			return $vars;	
		
		
		if($vars['orderby'] == 'purchase_order_status'){
			$vars = array_merge( $vars, array(
					'meta_key' 	=> '_purchase_order_payment_status',
					'orderby' 	=> 'meta_value'
			) );
		}
				
		return $vars;
	}
	
}


return new WooPurchaseOrderPaymentStatus();