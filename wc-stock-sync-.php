<?php

/**
 *
 * @link              https://wppool.dev
 * @since             1.0.0
 * @package           hoodslyhub
 *
 * @wordpress-plugin
 * Plugin Name: Product sync between websites
 * Plugin URI:  https://wppool.dev
 * Description: This plugin sync product between hoodsly and discountwoodhoods
 * Version:     1.0.6
 * Author:      Saiful Islam
 * Author URI:  https://wppool.dev
 * Text Domain: syn-hoodsly
 * Domain Path: /languages/
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 */

class SYNC_PRODUCT_HOODSLY {
	/**
	 * Initialize the class and set its properties.
	 *
	 * @param string $plugin_name The name of this plugin.
	 * @param string $version The version of this plugin.
	 *
	 * @since    1.0.0
	 *
	 */
	public function __construct() {
		add_action( 'woocommerce_thankyou', [$this, 'send_stock_update'], 10, 1 );
		add_action( 'rest_api_init', [$this, 'register_rest_route']);
		//add_action('admin_init', [$this, 'test_order_data']);
	}//end constructor

	public function register_rest_route(){
		register_rest_route( 'stock_sync/v1', '/hoodsly_discount/', array(
			'methods' => 'POST',
			'callback' => [$this, 'stock_sync_hoodsly_receive'],
			'permission_callback' => '__return_true',
		  ) );
	}

	public function stock_sync_hoodsly_receive($response){
		$body_data	= $response->get_body();
		$arr	= json_decode( $body_data, true );
		$product_title = preg_match_all('/#(\d+)/', $arr['product_title'], $matches);
		$args = [
			'post_type' => 'product',
			's' => $matches[0][0]
		];
		$product_post = new WP_Query($args);
		$product_id = $product_post->posts[0]->ID;
		$product = wc_get_product( $product_id );
		$product->set_manage_stock(true);
        $product->set_stock_quantity($arr['stock_quantity']);
		$product->save();
	}
	
	/**
	 * Test Order for metadata
	 * @since    1.0.0
	 */
	function test_order_data() {
		/* global $wpdb;
		$product_title = preg_match_all('/#(\d+)/', $arr['product_title'], $matches);
		$args = [
			'post_type' => 'product',
			's' => $matches[0][0]
		];
		$product_post = new WP_Query($args);
		$product_id = $product_post->posts[0]->ID;
		$product = wc_get_product( $product_id );
		$product->set_manage_stock(true);
        $product->set_stock_quantity($arr['stock_quantity']);
		$product->save();
		write_log($product); */
		$order_id = 26671;
		$order                        = wc_get_order( $order_id );
		$line_items                   = array();
		$data                         = $order->get_data();
		$order_date                   = $order->order_date;
		$order_status                 = $order->get_status();
		$order_status                 = wc_get_order_status_name( $order_status );
		$line_items['order_total']    = $order->get_total();
		$line_items['total_quantity'] = $order->get_item_count();
		$productName                  = array();
		// Get the WP_User roles and capabilities
		foreach ( $order->get_items() as $item_key => $item_values ) {
			$product           = wc_get_product( $item_values->get_product_id() );
			write_log($product->get_stock_quantity());
			$stock_quantity            = $product->get_stock_quantity();
			$product_name    = $item_values['name'];
			$product_pattern = '/[\s\S]*?(?=-)/i';
			preg_match_all( $product_pattern, $product_name, $product_matches );
			$productName = trim( $product_matches[0][0] );
		}
		$stock_update_url = 'http://discountwoodhoods.test/wp-json/stock_sync/v1/hoodsly_discount/';
		$data_string = json_encode(
			array(
				'product_title'		=> $productName,
				'stock_quantity'	=> $stock_quantity
			)
			);
		//write_log($data_string);
		$req = wp_remote_post($stock_update_url, array('body' => $data_string));
		write_log($req);
	}// End test_order_data

	public function send_stock_update($order_id){
		write_log($order_id);
		$order                        = wc_get_order( $order_id );
		$line_items                   = array();
		$data                         = $order->get_data();
		$order_date                   = $order->order_date;
		$order_status                 = $order->get_status();
		$order_status                 = wc_get_order_status_name( $order_status );
		$line_items['order_total']    = $order->get_total();
		$line_items['total_quantity'] = $order->get_item_count();
		$productName                  = array();
		// Get the WP_User roles and capabilities
		foreach ( $order->get_items() as $item_key => $item_values ) {
			$product           = wc_get_product( $item_values->get_product_id() );
			write_log($product->get_stock_quantity());
			$stock_quantity            = $product->get_stock_quantity();
			$product_name    = $item_values['name'];
			$product_pattern = '/[\s\S]*?(?=-)/i';
			preg_match_all( $product_pattern, $product_name, $product_matches );
			$productName = trim( $product_matches[0][0] );
		}
		$stock_update_url = 'http://discountwoodhoods.test/wp-json/stock_sync/v1/hoodsly_discount/';
		$data_string = json_encode(
			array(
				'product_title'                   => $productName,
				'stock_quantity'                => $stock_quantity
			)
			);
		write_log($data_string);
		wp_remote_post($stock_update_url, array('body' => $data_string));
	}
}//end class SYNC_PRODUCT_HOODSLY

new SYNC_PRODUCT_HOODSLY();