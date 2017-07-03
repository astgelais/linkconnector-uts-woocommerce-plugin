<?php
/*
Plugin Name: LinkConnector UTS - WooCommerce
Description: LinkConnector Universal Tracking Solution code for WooCommerce
Version:     3.0
Author:      Aaron St. Gelais
*/

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

/* Start LinkConnector WordPress Menu Code */
add_action( 'admin_menu', 'linkconnector_uts_menu' );

function linkconnector_uts_menu() {
	
	//Menu items
  $page_title = 'LinkConnector UTS Plugin';
  $menu_title = 'LinkConnector UTS Plugin';
  $capability = 'manage_options';
  $menu_slug  = 'linkconnector-uts';
  $function   = 'linkconnector_uts_page';
  $icon_url   = 'dashicons-media-code';

	//Add LinkConnector UTS to the WP Dashboard Menu
  add_menu_page( $page_title, $menu_title,  $capability,  $menu_slug,  $function,  $icon_url,  $position );

}

function linkconnector_uts_page() {
	
	//Create HTML form to hold LinkConnector UTS option values
	?>
  <h1>LinkConnector Universal Tracking Solution (UTS)</h1>
  <p>Do not change the Campaign Group ID or Event ID values unless specified by your LinkConnector Merchant Representative.</p>
  <p>If you need help, please contact Merchant Relations - 9194685150 ext. 1</p>
  <form method="post" action="options.php">
    <?php settings_fields( 'linkconnector-uts-settings' ); ?>
    <?php do_settings_sections( 'linkconnector-uts-settings' ); ?>
    <table class="form-table">
      <tr valign="top">
      <th scope="row">Event ID:</th>
      <td><input type="text" name="linkconnector_uts_eid" value="<?php echo get_option( 'linkconnector_uts_eid' ); ?>"/></td>
      </tr>
      <tr valign="top">
      <th scope="row">Campaign Group ID:</th>
      <td><input type="text" name="linkconnector_uts_cgid" value="<?php echo get_option( 'linkconnector_uts_cgid' ); ?>"/></td>
      </tr>
    </table>
    <?php submit_button(); ?>
  </form> 
<?php

}

add_action( 'admin_init', 'linkconnector_uts_settings' );

function linkconnector_uts_settings() {
	
  register_setting( 'linkconnector-uts-settings', 'linkconnector_uts_cgid' );
  register_setting( 'linkconnector-uts-settings', 'linkconnector_uts_eid' );
  
}

add_action( 'admin_notices', 'admin_notices' );

function admin_notices() {
	//Check settings for Event ID and Campaign Group ID
	$settings[cgid] = get_option( 'linkconnector_uts_cgid' );
	$settings[eid] = get_option( 'linkconnector_uts_eid' );
	if ( ! isset( $settings[cgid] ) || empty( $settings[cgid] ) ) {
		echo ( '<div class="error"><p>' . __( 'LinkConnector UTS WooCommerce Merchant Tracking requires your Campaign Group ID before it can start tracking sales. <a href="admin.php?page=linkconnector-uts">Do this now</a>', 'linkconnector-uts' ) . '</p></div>' );
	}
	if ( ! isset( $settings[eid] ) || empty( $settings[eid] ) ) {
		echo ( '<div class="error"><p>' . __( 'LinkConnector UTS WooCommerce Merchant Tracking requires your Event ID before it can start tracking sales. <a href="admin.php?page=linkconnector-uts">Do this now</a>', 'linkconnector-uts' ) . '</p></div>' );
	}
	
}   
/* End LinkConnector WordPress Menu Code */

/* Start UTS Landing Code */
add_action( 'wp_footer', 'linkconnector_uts_landing' );

function linkconnector_uts_landing( ) {

//Get the cgid
$cgid = get_option('linkconnector_uts_cgid');
	
$lc_lp_call = <<<LCCALL
<script type="text/javascript" src="//www.linkconnector.com/uts_lp.php?cgid=$cgid"></script>
LCCALL;

echo $lc_lp_call;

}
/* End UTS Landing Code */

/* Start UTS Confirm Code */
add_action( 'woocommerce_thankyou', 'linkconnector_uts_confirm' );

function linkconnector_uts_confirm( $order_id ) {

//Get the cgid and eid
$cgid = get_option('linkconnector_uts_cgid');
$eid = get_option('linkconnector_uts_eid');

//Get the order data
$order = new WC_Order( $order_id );

$order_base_total = $order->get_total() - $order->get_total_shipping() - $order->get_total_tax();;
$order_coupon = $order->get_used_coupons();
$order_coupon = implode("|", $order_coupon);
$order_currency = $order->get_order_currency();
$order_discount = $order->get_total_discount();
$order_email = $order->billing_email;
$order_userid = $order->user_id;

$customer_orders = get_posts( array(
        'numberposts' => -1,
        'meta_key'    => '_billing_email',
        'meta_value'  => $order_email,
        'post_type'   => wc_get_order_types(),
        'post_status' => array_keys( wc_get_order_statuses() ),
    ) );

if(count($customer_orders) > 1) {$order_customer = 0;} else { $order_customer = 1;}

$ordervars = <<<ORDERVARS
<script type="text/javascript">
var uts_orderid = "$order_id"; // Enter the Order ID
var uts_saleamount = "$order_base_total"; // Enter the Order total after discounts
var uts_coupon = "$order_coupon"; // Enter Coupon Code
var uts_discount = "$order_discount"; // Enter Discount amount
var uts_currency = "$order_currency"; // Enter the Currency Code
var uts_customerstatus = "$order_customer"; // Enter 1 for new customer and 0 for existing customer
var uts_email = "$order_email"; // Enter the customer's email
var uts_lcpid_set = "$order_userid"; // Enter the customer's userID
var uts_eventid = "$eid"; // Enter LinkConnector EventID
</script>
ORDERVARS;

echo $ordervars;


/* Set Product Variables */
$items = $order->get_items();

/****** initialize javascript array ********/

$order_items = <<<ORDER_ITEMS
<script type="text/javascript">
var uts_products = new Array();
ORDER_ITEMS;

/****** loop thru cart ********/
$j = 0;
foreach($items as $itemID) {
$itemDetails = new WC_Product( $itemID[product_id] );
$sku = $itemDetails->get_sku();
$category = strip_tags($itemDetails->get_categories());
$price = $itemDetails->get_price();
$order_items .= <<<ORDER_ITEMS
uts_products[$j] = new Array(); 
uts_products[$j][0] = "$sku";
uts_products[$j][1] = "$itemID[name]";
uts_products[$j][2] = "$itemID[qty]";
uts_products[$j][3] = "$price";
uts_products[$j][4] = "$category";
ORDER_ITEMS;
$j++;
}

/****** close javascript array declaration *****/
$order_items .= <<<ORDER_ITEMS
</script>
ORDER_ITEMS;

echo $order_items;

$lc_call = <<<LCCALL
<script type="text/javascript" src="//www.linkconnector.com/uts_tm.php?cgid=$cgid"></script>
LCCALL;

echo $lc_call;

}
/* End UTS Confirm Code */

?>