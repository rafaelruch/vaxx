<?php
/**
 * VAXX · Review Order (sidebar do checkout) · Yampi-style
 * Usa classes do preview (co-sum-item, checkout-summary__*)
 *
 * @package VAXX
 */
defined( 'ABSPATH' ) || exit;

$item_count = WC()->cart ? WC()->cart->get_cart_contents_count() : 0;
?>
<h2 class="checkout-summary__title">
	Seu pedido
	<small><?php echo intval( $item_count ); ?> <?php echo $item_count === 1 ? 'item' : 'itens'; ?></small>
</h2>

<div class="checkout-summary__items">
	<?php foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) :
		$_product = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );
		if ( ! $_product || ! $_product->exists() || $cart_item['quantity'] <= 0 ) continue;

		$thumb_url = get_the_post_thumbnail_url( $_product->get_id(), 'vaxx-prod-thumb' );
		if ( ! $thumb_url ) $thumb_url = wc_placeholder_img_src( 'vaxx-prod-thumb' );

		// Meta: linha + regulagem (matching preview format "Articulados · 1,55–1,95")
		$lines  = wp_get_post_terms( $_product->get_id(), 'product_line', array( 'fields' => 'names' ) );
		$line   = ! empty( $lines ) ? $lines[0] : '';
		$hasReg = (bool) get_post_meta( $_product->get_id(), 'vaxx_regulagem_real', true );
		$regVal = get_post_meta( $_product->get_id(), 'vaxx_regulagem', true );
		$meta_parts = array();
		if ( $line ) $meta_parts[] = $line;
		if ( $hasReg ) $meta_parts[] = $regVal ?: '1,55–1,95';
		$meta_str = implode( ' · ', $meta_parts );
	?>
		<article class="co-sum-item">
			<div class="co-sum-item__thumb">
				<img src="<?php echo esc_url( $thumb_url ); ?>" alt="<?php echo esc_attr( $_product->get_name() ); ?>">
				<span class="co-sum-item__qty"><?php echo intval( $cart_item['quantity'] ); ?></span>
			</div>
			<div class="co-sum-item__body">
				<span class="co-sum-item__name"><?php echo esc_html( $_product->get_name() ); ?></span>
				<?php if ( $meta_str ) : ?>
					<span class="co-sum-item__meta"><?php echo esc_html( $meta_str ); ?></span>
				<?php endif; ?>
			</div>
			<span class="co-sum-item__price"><?php echo wp_kses_post( WC()->cart->get_product_subtotal( $_product, $cart_item['quantity'] ) ); ?></span>
		</article>
	<?php endforeach; ?>
</div>

<div class="checkout-summary__rows">
	<div class="checkout-summary__row">
		<span>Subtotal</span>
		<span><?php wc_cart_totals_subtotal_html(); ?></span>
	</div>

	<?php foreach ( WC()->cart->get_coupons() as $code => $coupon ) : ?>
		<div class="checkout-summary__row checkout-summary__row--discount">
			<span><?php wc_cart_totals_coupon_label( $coupon ); ?></span>
			<span>− <?php wc_cart_totals_coupon_html( $coupon ); ?></span>
		</div>
	<?php endforeach; ?>

	<?php if ( WC()->cart->needs_shipping() && WC()->cart->show_shipping() ) :
		$chosen = WC()->session->get( 'chosen_shipping_methods' );
		foreach ( WC()->cart->calculate_shipping() as $pkg_key => $package ) : ?>
			<div class="checkout-summary__row">
				<span>Frete</span>
				<?php
				if ( ! empty( $package['rates'] ) ) {
					$first = reset( $package['rates'] );
					$cost = $first->cost;
					echo '<span' . ( $cost == 0 ? ' class="free"' : '' ) . '>' . ( $cost == 0 ? 'Grátis' : wc_price( $cost ) ) . '</span>';
				} else {
					echo '<span class="free">Grátis</span>';
				} ?>
			</div>
		<?php endforeach;
	else : ?>
		<div class="checkout-summary__row">
			<span>Frete</span>
			<span class="free">Grátis</span>
		</div>
	<?php endif; ?>

	<?php foreach ( WC()->cart->get_fees() as $fee ) : ?>
		<div class="checkout-summary__row">
			<span><?php echo esc_html( $fee->name ); ?></span>
			<span><?php echo wc_price( $fee->total ); ?></span>
		</div>
	<?php endforeach; ?>
</div>

<div class="checkout-summary__total">
	<div class="checkout-summary__total-row">
		<span class="checkout-summary__total-label">Total</span>
		<span class="checkout-summary__total-value"><?php wc_cart_totals_order_total_html(); ?></span>
	</div>
	<p class="checkout-summary__note">Pagamento em Pix · Cartão até 10× · ou Boleto bancário</p>
</div>

<button type="submit" class="checkout-summary__cta" name="woocommerce_checkout_place_order" id="place_order" value="Finalizar compra" data-value="Finalizar compra">
	<span>Finalizar compra</span>
	<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
</button>

<div class="co-seals">
	<div class="co-seal">
		<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
			<rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>
		</svg>
		<strong>SSL</strong>
		<small>256-bit</small>
	</div>
	<div class="co-seal">
		<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
			<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><polyline points="9 12 11 14 15 10"/>
		</svg>
		<strong>Site seguro</strong>
		<small>Dados cripto</small>
	</div>
	<div class="co-seal">
		<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
			<polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
		</svg>
		<strong>LGPD</strong>
		<small>Dados protegidos</small>
	</div>
</div>
