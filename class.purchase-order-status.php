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
		
		//more bulk actions
		add_action('admin_footer', array(&$this, 'add_more_bulk_actions'), 5);
		add_action('load-edit.php', array(&$this, 'process_more_bulk_actions'), 100);
		add_action( 'admin_notices', array(&$this, 'show_bulk_admin_notices' ), 100);
		
		
	}	
	
	function woocommerce_meta_boxes(){
		add_meta_box( 'woocommerce-purchase-order-pyament-status', __( 'Purchase Order Payment Status', 'woocommerce' ), array(&$this, 'woocommerce_purchae_order_payemnt_satus'), 'shop_order', 'side', 'high' );
	}	
	
	function woocommerce_purchae_order_payemnt_satus($post){
		include dirname(__FILE__) . '/includes/metabox.purchase-order-payment-status.php';
	}	
	
	function woocommerce_process_shop_order_meta( $post_id, $post ){		
		$new_status_slug = trim($_POST['order_status']);		
		$new_status = get_term_by( 'slug', sanitize_title( $new_status_slug ), 'shop_order_status' );
		
		//var_dump($new_status);
		
		if(in_array($new_status->slug, array('processing', 'completed'))){
			update_post_meta($post_id, '_purchase_order_payment_status', '1');
		}		
		elseif(isset($_POST['purchase_order_payment_status'])){
			update_post_meta($post_id, '_purchase_order_payment_status', '1');
		}
		else{
			update_post_meta($post_id, '_purchase_order_payment_status', '0');
		}	
	}	
	
	function woocommerce_edit_order_columns($columns){
		$new_columns = array();
		if(is_array($columns)){
			foreach($columns as $key => $column){
				$new_columns[$key] = $column;
				if($key == 'total_cost'){
					$new_columns['purchase_order_status'] = __('Paid?', 'woocommerce');
				}
			}
		}
		return $new_columns;
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
	
	
	//add new bulk actions in shop orders table
	function add_new_bulk_actions($actions){
		
		$actions['paid_status'] = "Change to Paid";
		$actions['unpaid_status'] = "Change to Unpaid";
		return $actions;
	}
	
	//add more bulk actions in shop page 
	//currently it only support javacript
	function add_more_bulk_actions(){
		global $post_type;
		if ( 'shop_order' == $post_type ) {
			?>
		      <script type="text/javascript">
		      jQuery(document).ready(function() {
		        jQuery('<option>').val('order_paid').text('<?php _e( 'Mark Paid', 'woocommerce' )?>').appendTo("select[name='action']");
		        jQuery('<option>').val('order_unpaid').text('<?php _e( 'Mark Unpaid', 'woocommerce' )?>').appendTo("select[name='action']");
		
		        jQuery('<option>').val('order_paid').text('<?php _e( 'Mark Paid', 'woocommerce' )?>').appendTo("select[name='action2']");
		        jQuery('<option>').val('order_unpaid').text('<?php _e( 'Mark Unpaid', 'woocommerce' )?>').appendTo("select[name='action2']");
		      });
		      </script>
		      <?php
		    }
	}
	
	//process more bulk actions
	function process_more_bulk_actions(){
		$wp_list_table = _get_list_table('WP_Posts_List_Table');
		$action = $wp_list_table->current_action();
		
		switch($action){
			case 'order_paid':
				$new_status = '1';
				$report_action = 'payment_completed';
				break;
			case 'order_unpaid':
				$new_status = '0';
				$report_action = 'payment_incompleted';
				break;
			default:
				return;
		}
		
		$changed = 0;		
		$post_ids = array_map( 'absint', (array) $_REQUEST['post'] );

		foreach( $post_ids as $post_id ) {
			update_post_meta($post_id, '_purchase_order_payment_status', $new_status);
			$changed++;
		}
		
		$sendback = add_query_arg( array( 'post_type' => 'shop_order', $report_action => $changed, 'ids' => join( ',', $post_ids ) ), '' );
		wp_redirect( $sendback );
		exit();
		
	}
	
	//showing the bulk actions notices
	function show_bulk_admin_notices(){
		global $post_type, $pagenow;

		if ( isset( $_REQUEST['payment_incompleted'] ) || isset( $_REQUEST['payment_completed'] ) ) {
			$number = isset( $_REQUEST['payment_completed'] ) ? absint( $_REQUEST['payment_completed'] ) : absint( $_REQUEST['payment_incompleted'] );
			
			$status = isset( $_REQUEST['payment_completed'] ) ? 'paid' : 'not paid';
			
			if ( 'edit.php' == $pagenow && 'shop_order' == $post_type ) {
				$message = sprintf( _n( 'Purchase Order status changed.', '%s order are marked as %s.', $number ), number_format_i18n( $number ), $status );
				echo '<div class="updated"><p>' . $message . '</p></div>';
			}
		}
	}
	
	
	
	
}


return new WooPurchaseOrderPaymentStatus();