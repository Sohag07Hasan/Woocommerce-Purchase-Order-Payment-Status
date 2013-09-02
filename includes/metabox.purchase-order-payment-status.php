<?php 
	$status = get_post_meta($post->ID, '_purchase_order_payment_status', true);
	$status = ($status == "1") ? 1 : 0;
?>
<input <?php checked(1, $status); ?> type="checkbox" id="purchase_order_payment_status" name="purchase_order_payment_status" value="1" /> 
&nbsp; &nbsp;
<label for="purchase_order_payment_status"> Paid </label>