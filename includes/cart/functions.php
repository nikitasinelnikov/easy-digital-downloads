<?php
/**
 * Cart Functions
 *
 * @package     EDD
 * @subpackage  Cart
 * @copyright   Copyright (c) 2015, Pippin Williamson
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Get the contents of the cart
 *
 * @since 1.0
 * @return array Returns an array of cart contents, or an empty array if no items in the cart
 */
function edd_get_cart_contents() {
	return EDD()->cart->get_contents();
}

/**
 * Retrieve the Cart Content Details
 *
 * Includes prices, tax, etc of all items.
 *
 * @since 1.0
 * @return array $details Cart content details
 */
function edd_get_cart_content_details() {
	return EDD()->cart->get_contents_details();
}

/**
 * Get Cart Quantity
 *
 * @since 1.0
 * @return int Sum quantity of items in the cart
 */
function edd_get_cart_quantity() {
	return EDD()->cart->get_quantity();
}

/**
 * Add To Cart
 *
 * Adds a download ID to the shopping cart.
 *
 * @since 1.0
 *
 * @param int $download_id Download IDs to be added to the cart
 * @param array $options Array of options, such as variable price
 *
 * @return string Cart key of the new item
 */
function edd_add_to_cart( $download_id, $options = array() ) {
	return EDD()->cart->add( $download_id, $options );
}

/**
 * Removes a Download from the Cart
 *
 * @since 1.0
 * @param int $cart_key the cart key to remove. This key is the numerical index of the item contained within the cart array.
 * @return array Updated cart items
 */
function edd_remove_from_cart( $cart_key ) {
	return EDD()->cart->remove( $cart_key );
}

/**
 * Checks to see if an item is already in the cart and returns a boolean
 *
 * @since 1.0
 *
 * @param int   $download_id ID of the download to remove
 * @param array $options
 * @return bool Item in the cart or not?
 */
function edd_item_in_cart( $download_id = 0, $options = array() ) {
	return EDD()->cart->is_item_in_cart( $download_id, $options );
}

/**
 * Get the Item Position in Cart
 *
 * @since 1.0.7.2
 *
 * @param int   $download_id ID of the download to get position of
 * @param array $options array of price options
 * @return bool|int|string false if empty cart |  position of the item in the cart
 */
function edd_get_item_position_in_cart( $download_id = 0, $options = array() ) {
	$cart_items = edd_get_cart_contents();
	if ( ! is_array( $cart_items ) ) {
		return false; // Empty cart
	} else {
		foreach ( $cart_items as $position => $item ) {
			if ( $item['id'] == $download_id ) {
				if ( isset( $options['price_id'] ) && isset( $item['options']['price_id'] ) ) {
					if ( (int) $options['price_id'] == (int) $item['options']['price_id'] ) {
						return $position;
					}
				} else {
					return $position;
				}
			}
		}
	}
	return false; // Not found
}


/**
 * Check if quantities are enabled
 *
 * @since 1.7
 * @return bool
 */
function edd_item_quantities_enabled() {
	$ret = edd_get_option( 'item_quantities', false );
	return (bool) apply_filters( 'edd_item_quantities_enabled', $ret );
}

/**
 * Set Cart Item Quantity
 *
 * @since 1.7
 *
 * @param int   $download_id Download (cart item) ID number
 * @param int   $quantity
 * @param array $options Download options, such as price ID
 * @return mixed New Cart array
 */
function edd_set_cart_item_quantity( $download_id = 0, $quantity = 1, $options = array() ) {
	$cart = edd_get_cart_contents();
	$key  = edd_get_item_position_in_cart( $download_id, $options );

	if( $quantity < 1 ) {
		$quantity = 1;
	}

	$cart[ $key ]['quantity'] = $quantity;
	EDD()->session->set( 'edd_cart', $cart );

	do_action( 'edd_after_set_cart_item_quantity', $download_id, $quantity, $options, $cart );

	return $cart;

}


/**
 * Get Cart Item Quantity
 *
 * @since 1.0
 * @param int $download_id Download (cart item) ID number
 * @param array $options Download options, such as price ID
 * @return int $quantity Cart item quantity
 */
function edd_get_cart_item_quantity( $download_id = 0, $options = array() ) {
	$cart     = edd_get_cart_contents();
	$key      = edd_get_item_position_in_cart( $download_id, $options );
	$quantity = isset( $cart[ $key ]['quantity'] ) && edd_item_quantities_enabled() ? $cart[ $key ]['quantity'] : 1;
	if( $quantity < 1 )
		$quantity = 1;
	return apply_filters( 'edd_get_cart_item_quantity', $quantity, $download_id, $options );
}

/**
 * Get Cart Item Price
 *
 * @since 1.0
 *
 * @param int   $item_id Download (cart item) ID number
 * @param array $options Optional parameters, used for defining variable prices
 * @return string Fully formatted price
 */
function edd_cart_item_price( $item_id = 0, $options = array() ) {
	$price = edd_get_cart_item_price( $item_id, $options );
	$label = '';

	$price_id = isset( $options['price_id'] ) ? $options['price_id'] : false;

	if ( ! edd_is_free_download( $item_id, $price_id ) && ! edd_download_is_tax_exclusive( $item_id ) ) {

		if( edd_prices_show_tax_on_checkout() && ! edd_prices_include_tax() ) {

			$price += edd_get_cart_item_tax( $item_id, $options, $price );

		} if( ! edd_prices_show_tax_on_checkout() && edd_prices_include_tax() ) {

			$price -= edd_get_cart_item_tax( $item_id, $options, $price );

		}

		if( edd_display_tax_rate() ) {

			$label = '&nbsp;&ndash;&nbsp;';

			if( edd_prices_show_tax_on_checkout() ) {
				$label .= sprintf( __( 'includes %s tax', 'easy-digital-downloads' ), edd_get_formatted_tax_rate() );
			} else {
				$label .= sprintf( __( 'excludes %s tax', 'easy-digital-downloads' ), edd_get_formatted_tax_rate() );
			}

			$label = apply_filters( 'edd_cart_item_tax_description', $label, $item_id, $options );

		}
	}

	$price = edd_currency_filter( edd_format_amount( $price ) );

	return apply_filters( 'edd_cart_item_price_label', $price . $label, $item_id, $options );
}

/**
 * Get Cart Item Price
 *
 * Gets the price of the cart item. Always exclusive of taxes
 *
 * Do not use this for getting the final price (with taxes and discounts) of an item.
 * Use edd_get_cart_item_final_price()
 *
 * @since 1.0
 * @param int   $download_id Download ID number
 * @param array $options Optional parameters, used for defining variable prices
 * @param bool  $remove_tax_from_inclusive Remove the tax amount from tax inclusive priced products.
 * @return float|bool Price for this item
 */
function edd_get_cart_item_price( $download_id = 0, $options = array(), $remove_tax_from_inclusive = false ) {

	$price = 0;
	$variable_prices = edd_has_variable_prices( $download_id );

	if ( $variable_prices ) {

		$prices = edd_get_variable_prices( $download_id );

		if ( $prices ) {

			if( ! empty( $options ) ) {

				$price = isset( $prices[ $options['price_id'] ] ) ? $prices[ $options['price_id'] ]['amount'] : false;

			} else {

				$price = false;

			}

		}

	}

	if( ! $variable_prices || false === $price ) {
		// Get the standard Download price if not using variable prices
		$price = edd_get_download_price( $download_id );
	}

	if ( $remove_tax_from_inclusive && edd_prices_include_tax() ) {

		$price -= edd_get_cart_item_tax( $download_id, $options, $price );
	}

	return apply_filters( 'edd_cart_item_price', $price, $download_id, $options );
}

/**
 * Get cart item's final price
 *
 * Gets the amount after taxes and discounts
 *
 * @since 1.9
 * @param int    $item_key Cart item key
 * @return float Final price for the item
 */
function edd_get_cart_item_final_price( $item_key = 0 ) {
	$items = edd_get_cart_content_details();
	$final = $items[ $item_key ]['price'];
	return apply_filters( 'edd_cart_item_final_price', $final, $item_key );
}

/**
 * Get cart item tax
 *
 * @since 1.9
 * @param array $download_id Download ID
 * @param array $options Cart item options
 * @param float $subtotal Cart item subtotal
 * @return float Tax amount
 */
function edd_get_cart_item_tax( $download_id = 0, $options = array(), $subtotal = '' ) {

	$tax = 0;
	if( ! edd_download_is_tax_exclusive( $download_id ) ) {

		$country = ! empty( $_POST['billing_country'] ) ? $_POST['billing_country'] : false;
		$state   = ! empty( $_POST['card_state'] )      ? $_POST['card_state']      : false;

		$tax = edd_calculate_tax( $subtotal, $country, $state );

	}

	$tax = max( $tax, 0 );

	return apply_filters( 'edd_get_cart_item_tax', $tax, $download_id, $options, $subtotal );
}

/**
 * Get Price Name
 *
 * Gets the name of the specified price option,
 * for variable pricing only.
 *
 * @since 1.0
 *
 * @param       $download_id Download ID number
 * @param array $options Optional parameters, used for defining variable prices
 * @return mixed|void Name of the price option
 */
function edd_get_price_name( $download_id = 0, $options = array() ) {
	$return = false;
	if( edd_has_variable_prices( $download_id ) && ! empty( $options ) ) {
		$prices = edd_get_variable_prices( $download_id );
		$name   = false;
		if( $prices ) {
			if( isset( $prices[ $options['price_id'] ] ) )
				$name = $prices[ $options['price_id'] ]['name'];
		}
		$return = $name;
	}
	return apply_filters( 'edd_get_price_name', $return, $download_id, $options );
}

/**
 * Get cart item price id
 *
 * @since 1.0
 *
 * @param array $item Cart item array
 * @return int Price id
 */
function edd_get_cart_item_price_id( $item = array() ) {
	if( isset( $item['item_number'] ) ) {
		$price_id = isset( $item['item_number']['options']['price_id'] ) ? $item['item_number']['options']['price_id'] : null;
	} else {
		$price_id = isset( $item['options']['price_id'] ) ? $item['options']['price_id'] : null;
	}
	return $price_id;
}

/**
 * Get cart item price name
 *
 * @since 1.8
 * @param int $item Cart item array
 * @return string Price name
 */
function edd_get_cart_item_price_name( $item = array() ) {
	$price_id = (int) edd_get_cart_item_price_id( $item );
	$prices   = edd_get_variable_prices( $item['id'] );
	$name     = ! empty( $prices[ $price_id ] ) ? $prices[ $price_id ]['name'] : '';
	return apply_filters( 'edd_get_cart_item_price_name', $name, $item['id'], $price_id, $item );
}

/**
 * Get cart item title
 *
 * @since 2.4.3
 * @param int $item Cart item array
 * @return string item title
 */
function edd_get_cart_item_name( $item = array() ) {

	$item_title = get_the_title( $item['id'] );

	if( empty( $item_title ) ) {
		$item_title = $item['id'];
	}

	if ( edd_has_variable_prices( $item['id'] ) && false !== edd_get_cart_item_price_id( $item ) ) {

		$item_title .= ' - ' . edd_get_cart_item_price_name( $item );
	}

	return apply_filters( 'edd_get_cart_item_name', $item_title, $item['id'], $item );
}

/**
 * Cart Subtotal
 *
 * Shows the subtotal for the shopping cart (no taxes)
 *
 * @since 1.4
 * @return float Total amount before taxes fully formatted
 */
function edd_cart_subtotal() {
	$price = esc_html( edd_currency_filter( edd_format_amount( edd_get_cart_subtotal() ) ) );

	// Todo - Show tax labels here (if needed)

	return $price;
}

/**
 * Get Cart Subtotal
 *
 * Gets the total price amount in the cart before taxes and before any discounts
 * uses edd_get_cart_contents().
 *
 * @since 1.3.3
 * @return float Total amount before taxes
 */
function edd_get_cart_subtotal() {

	$items    = edd_get_cart_content_details();
	$subtotal = edd_get_cart_items_subtotal( $items );

	return apply_filters( 'edd_get_cart_subtotal', $subtotal );
}

/**
 * Get Cart Discountable Subtotal.
 *
 * @return float Total discountable amount before taxes
 */
function edd_get_cart_discountable_subtotal( $code_id ) {

	$cart_items = edd_get_cart_content_details();
	$items      = array();

	$excluded_products = edd_get_discount_excluded_products( $code_id );

	if( $cart_items ) {

		foreach( $cart_items as $item ) {

			if( ! in_array( $item['id'], $excluded_products ) ) {
				$items[] =  $item;
			}
		}
	}

	$subtotal = edd_get_cart_items_subtotal( $items );

	return apply_filters( 'edd_get_cart_discountable_subtotal', $subtotal );
}

/**
 * Get cart items subtotal
 * @param array $items Cart items array
 *
 * @return float items subtotal
 */
function edd_get_cart_items_subtotal( $items ) {
	$subtotal = 0.00;

	if( is_array( $items ) && ! empty( $items ) ) {

		$prices = wp_list_pluck( $items, 'subtotal' );

		if( is_array( $prices ) ) {
			$subtotal = array_sum( $prices );
		} else {
			$subtotal = 0.00;
		}

		if( $subtotal < 0 ) {
			$subtotal = 0.00;
		}

	}

	return apply_filters( 'edd_get_cart_items_subtotal', $subtotal );
}
/**
 * Get Total Cart Amount
 *
 * Returns amount after taxes and discounts
 *
 * @since 1.4.1
 * @param bool $discounts Array of discounts to apply (needed during AJAX calls)
 * @return float Cart amount
 */
function edd_get_cart_total( $discounts = false ) {
	$subtotal     = (float) edd_get_cart_subtotal();
	$discounts    = (float) edd_get_cart_discounted_amount();
	$fees         = (float) edd_get_cart_fee_total();
	$cart_tax     = (float) edd_get_cart_tax();
	$total_wo_tax = $subtotal - $discounts + $fees;
	$total        = $subtotal - $discounts + $cart_tax + $fees;

	if( $total < 0 || ! $total_wo_tax > 0 ) {
		$total = 0.00;
	}

	return (float) apply_filters( 'edd_get_cart_total', $total );
}


/**
 * Get Total Cart Amount
 *
 * Gets the fully formatted total price amount in the cart.
 * uses edd_get_cart_amount().
 *
 * @since 1.3.3
 *
 * @param bool $echo
 * @return mixed|string|void
 */
function edd_cart_total( $echo = true ) {
	$total = apply_filters( 'edd_cart_total', edd_currency_filter( edd_format_amount( edd_get_cart_total() ) ) );

	// Todo - Show tax labels here (if needed)

	if ( ! $echo ) {
		return $total;
	}

	echo $total;
}

/**
 * Check if cart has fees applied
 *
 * Just a simple wrapper function for EDD_Fees::has_fees()
 *
 * @since 1.5
 * @param string $type
 * @uses EDD()->fees->has_fees()
 * @return bool Whether the cart has fees applied or not
 */
function edd_cart_has_fees( $type = 'all' ) {
	return EDD()->fees->has_fees( $type );
}

/**
 * Get Cart Fees
 *
 * Just a simple wrapper function for EDD_Fees::get_fees()
 *
 * @since 1.5
 * @param string $type
 * @param int $download_id
 * @uses EDD()->fees->get_fees()
 * @return array All the cart fees that have been applied
 */
function edd_get_cart_fees( $type = 'all', $download_id = 0, $price_id = NULL ) {
	return EDD()->cart->get_fees( $type, $download_id, $price_id );
}

/**
 * Get Cart Fee Total
 *
 * Just a simple wrapper function for EDD_Fees::total()
 *
 * @since 1.5
 * @uses EDD()->fees->total()
 * @return float Total Cart Fees
 */
function edd_get_cart_fee_total() {
	return EDD()->cart->get_total_fees();
}

/**
 * Get cart tax on Fees
 *
 * @since 2.0
 * @uses EDD()->fees->get_fees()
 * @return float Total Cart tax on Fees
 */
function edd_get_cart_fee_tax() {

	$tax  = 0;
	$fees = edd_get_cart_fees();

	if( $fees ) {

		foreach ( $fees as $fee_id => $fee ) {

			if( ! empty( $fee['no_tax'] ) || $fee['amount'] < 0 ) {
				continue;
			}

			// Fees must (at this time) be exclusive of tax
			add_filter( 'edd_prices_include_tax', '__return_false' );

			$tax += edd_calculate_tax( $fee['amount'] );

			remove_filter( 'edd_prices_include_tax', '__return_false' );

		}
	}

	return apply_filters( 'edd_get_cart_fee_tax', $tax );
}

/**
 * Get Purchase Summary
 *
 * Retrieves the purchase summary.
 *
 * @since       1.0
 *
 * @param      $purchase_data
 * @param bool $email
 * @return string
 */
function edd_get_purchase_summary( $purchase_data, $email = true ) {
	$summary = '';

	if ( $email ) {
		$summary .= $purchase_data['user_email'] . ' - ';
	}

	if ( ! empty( $purchase_data['downloads'] ) ) {
		foreach ( $purchase_data['downloads'] as $download ) {
			$summary .= get_the_title( $download['id'] ) . ', ';
		}

		$summary = substr( $summary, 0, -2 );
	}

	return apply_filters( 'edd_get_purchase_summary', $summary, $purchase_data, $email );
}

/**
 * Gets the total tax amount for the cart contents
 *
 * @since 1.2.3
 *
 * @return mixed|void Total tax amount
 */
function edd_get_cart_tax() {

	$cart_tax     = 0;
	$items        = edd_get_cart_content_details();

	if( $items ) {

		$taxes = wp_list_pluck( $items, 'tax' );

		if( is_array( $taxes ) ) {
			$cart_tax = array_sum( $taxes );
		}

	}

	$cart_tax += edd_get_cart_fee_tax();

	return apply_filters( 'edd_get_cart_tax', edd_sanitize_amount( $cart_tax ) );
}

/**
 * Gets the total tax amount for the cart contents in a fully formatted way
 *
 * @since 1.2.3
 * @param bool $echo Whether to echo the tax amount or not (default: false)
 * @return string Total tax amount (if $echo is set to true)
 */
function edd_cart_tax( $echo = false ) {
	$cart_tax = 0;

	if ( edd_is_cart_taxed() ) {
		$cart_tax = edd_get_cart_tax();
		$cart_tax = edd_currency_filter( edd_format_amount( $cart_tax ) );
	}

	$tax = max( $cart_tax, 0 );

	$tax = apply_filters( 'edd_cart_tax', $cart_tax );

	if ( ! $echo ) {
		return $tax;
	}

	echo $tax;
}

/**
 * Add Collection to Cart
 *
 * Adds all downloads within a taxonomy term to the cart.
 *
 * @since 1.0.6
 * @param string $taxonomy Name of the taxonomy
 * @param mixed $terms Slug or ID of the term from which to add ites | An array of terms
 * @return array Array of IDs for each item added to the cart
 */
function edd_add_collection_to_cart( $taxonomy, $terms ) {
	if ( ! is_string( $taxonomy ) ) return false;

	if( is_numeric( $terms ) ) {
		$terms = get_term( $terms, $taxonomy );
		$terms = $terms->slug;
	}

	$cart_item_ids = array();

	$args = array(
		'post_type' => 'download',
		'posts_per_page' => -1,
		$taxonomy => $terms
	);

	$items = get_posts( $args );
	if ( $items ) {
		foreach ( $items as $item ) {
			edd_add_to_cart( $item->ID );
			$cart_item_ids[] = $item->ID;
		}
	}
	return $cart_item_ids;
}

/**
 * Returns the URL to remove an item from the cart
 *
 * @since 1.0
 * @global $post
 * @param int $cart_key Cart item key
 * @return string $remove_url URL to remove the cart item
 */
function edd_remove_item_url( $cart_key ) {

	global $wp_query;

	if ( defined( 'DOING_AJAX' ) ) {
		$current_page = edd_get_checkout_uri();
	} else {
		$current_page = edd_get_current_page_url();
	}

	$remove_url = edd_add_cache_busting( add_query_arg( array( 'cart_item' => $cart_key, 'edd_action' => 'remove' ), $current_page ) );

	return apply_filters( 'edd_remove_item_url', $remove_url );
}

/**
 * Returns the URL to remove an item from the cart
 *
 * @since 1.0
 * @global $post
 * @param string $fee_id Fee ID
 * @return string $remove_url URL to remove the cart item
 */
function edd_remove_cart_fee_url( $fee_id = '') {
	global $post;

	if ( defined('DOING_AJAX') ) {
		$current_page = edd_get_checkout_uri();
	} else {
		$current_page = edd_get_current_page_url();
	}

	$remove_url = add_query_arg( array( 'fee' => $fee_id, 'edd_action' => 'remove_fee', 'nocache' => 'true' ), $current_page );

	return apply_filters( 'edd_remove_fee_url', $remove_url );
}

/**
 * Empties the Cart
 *
 * @since 1.0
 * @uses EDD()->session->set()
 * @return void
 */
function edd_empty_cart() {
	EDD()->cart->empty();
}

/**
 * Store Purchase Data in Sessions
 *
 * Used for storing info about purchase
 *
 * @since 1.1.5
 *
 * @param $purchase_data
 *
 * @uses EDD()->session->set()
 */
function edd_set_purchase_session( $purchase_data = array() ) {
	EDD()->session->set( 'edd_purchase', $purchase_data );
}

/**
 * Retrieve Purchase Data from Session
 *
 * Used for retrieving info about purchase
 * after completing a purchase
 *
 * @since 1.1.5
 * @uses EDD()->session->get()
 * @return mixed array | false
 */
function edd_get_purchase_session() {
	return EDD()->session->get( 'edd_purchase' );
}

/**
 * Checks if cart saving has been disabled
 *
 * @since 1.8
 * @return bool Whether or not cart saving has been disabled
 */
function edd_is_cart_saving_disabled() {
	return ! EDD()->cart->is_saving_enabled();
}

/**
 * Checks if a cart has been saved
 *
 * @since 1.8
 * @return bool
 */
function edd_is_cart_saved() {
	return EDD_Cart()->is_saved();
}

/**
 * Process the Cart Save
 *
 * @since 1.8
 * @return bool
 */
function edd_save_cart() {
	EDD()->cart->save_cart();
}


/**
 * Process the Cart Restoration
 *
 * @since 1.8
 * @return mixed || false Returns false if cart saving is disabled
 */
function edd_restore_cart() {
	return EDD()->cart->restore();
}

/**
 * Retrieve a saved cart token. Used in validating saved carts
 *
 * @since 1.8
 * @return int
 */
function edd_get_cart_token() {
	return EDD()->cart->get_token();
}

/**
 * Delete Saved Carts after one week
 *
 * @since 1.8
 * @global $wpdb
 * @return void
 */
function edd_delete_saved_carts() {
	global $wpdb;

	$start = date( 'Y-m-d', strtotime( '-7 days' ) );
	$carts = $wpdb->get_results(
		"
		SELECT user_id, meta_key, FROM_UNIXTIME(meta_value, '%Y-%m-%d') AS date
		FROM {$wpdb->usermeta}
		WHERE meta_key = 'edd_cart_token'
		", ARRAY_A
	);

	if ( $carts ) {
		foreach ( $carts as $cart ) {
			$user_id    = $cart['user_id'];
			$meta_value = $cart['date'];

			if ( strtotime( $meta_value ) < strtotime( '-1 week' ) ) {
				$wpdb->delete(
					$wpdb->usermeta,
					array(
						'user_id'  => $user_id,
						'meta_key' => 'edd_cart_token'
					)
				);

				$wpdb->delete(
					$wpdb->usermeta,
					array(
						'user_id'  => $user_id,
						'meta_key' => 'edd_saved_cart'
					)
				);
			}
		}
	}
}
add_action( 'edd_weekly_scheduled_events', 'edd_delete_saved_carts' );

/**
 * Generate URL token to restore the cart via a URL
 *
 * @since 1.8
 * @return string UNIX timestamp
 */
function edd_generate_cart_token() {
	return EDD()->cart->generate_token();
}
