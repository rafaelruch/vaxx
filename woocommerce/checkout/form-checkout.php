<?php
/**
 * VAXX · Checkout Yampi-style (PF only: Pix, Cartao, Boleto)
 *
 * Override do template form-checkout.php do Woo. Renderiza a UI
 * do preview checkout.html adaptando para os hooks do WooCommerce.
 *
 * @package VAXX
 */

defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_before_checkout_form', $checkout );

// Checkout desabilitado (nenhum produto suportado, etc)
if ( ! $checkout->is_registration_enabled() && $checkout->is_registration_required() && ! is_user_logged_in() ) {
	echo esc_html( apply_filters( 'woocommerce_checkout_must_be_logged_in_message', __( 'You must be logged in to checkout.', 'woocommerce' ) ) );
	return;
}

$item_count = WC()->cart ? WC()->cart->get_cart_contents_count() : 0;
?>

<main class="page-checkout">

	<!-- Breadcrumb -->
	<nav class="bc" aria-label="Breadcrumb">
		<div class="bc__inner">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>">Início</a>
			<span class="sep" aria-hidden="true">›</span>
			<a href="<?php echo esc_url( wc_get_cart_url() ); ?>">Carrinho</a>
			<span class="sep" aria-hidden="true">›</span>
			<span class="is-current" aria-current="page">Checkout</span>
		</div>
	</nav>

	<!-- Notices (abaixo da breadcrumb — "produto adicionado ao carrinho" etc) -->
	<?php if ( function_exists( 'wc_print_notices' ) && function_exists( 'wc_notice_count' ) && wc_notice_count() > 0 ) : ?>
		<div class="page-checkout__notices"><?php wc_print_notices(); ?></div>
	<?php endif; ?>

	<!-- Trust bar -->
	<div class="co-trust-bar" aria-label="Segurança e confiança">
		<div class="co-trust-bar__inner">
			<span class="co-trust-bar__item">
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
					<rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>
				</svg>
				<strong>Compra 100% segura</strong> · SSL 256-bit
			</span>
			<span class="co-trust-bar__item">
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
					<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><polyline points="9 12 11 14 15 10"/>
				</svg>
				Dados <strong>protegidos pela LGPD</strong>
			</span>
			<span class="co-trust-bar__item">
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
					<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
				</svg>
				<strong>7 dias</strong> de garantia de arrependimento
			</span>
		</div>
	</div>

	<!-- Hero compacto -->
	<section class="checkout-hero" aria-label="Checkout">
		<div class="checkout-hero__bg" aria-hidden="true"></div>
		<div class="checkout-hero__inner">
			<span class="checkout-hero__eyebrow">CHECKOUT · <?php echo intval( $item_count ); ?> <?php echo $item_count === 1 ? 'item' : 'itens'; ?></span>
			<h1 class="checkout-hero__title">Quase <span class="lime">lá.</span></h1>
		</div>
	</section>

	<form name="checkout" method="post" class="checkout woocommerce-checkout" action="<?php echo esc_url( wc_get_checkout_url() ); ?>" enctype="multipart/form-data">

		<div class="checkout-layout">

			<!-- ───── COLUNA PRINCIPAL ───── -->
			<div class="checkout-main">

				<?php if ( $checkout->get_checkout_fields() ) : ?>

					<?php do_action( 'woocommerce_checkout_before_customer_details' ); ?>

					<!-- BLOCO 1 · Seus dados + Endereco (Woo billing) -->
					<section class="co-block" data-block="1">
						<header class="co-block__header">
							<span class="co-block__num" aria-hidden="true"><span class="co-block__num-text">1</span></span>
							<h2 class="co-block__title">Seus dados &amp; entrega</h2>
							<span class="co-block__hint">Enviamos o pedido pra seu <strong>e-mail</strong> e <strong>WhatsApp</strong></span>
						</header>
						<div class="co-block__body">
							<div class="woocommerce-billing-fields">
								<?php do_action( 'woocommerce_checkout_billing' ); ?>
							</div>
						</div>
					</section>

					<?php do_action( 'woocommerce_checkout_after_customer_details' ); ?>

				<?php endif; ?>

				<!-- BLOCO 2 · Pagamento -->
				<section class="co-block" data-block="2">
					<header class="co-block__header">
						<span class="co-block__num" aria-hidden="true"><span class="co-block__num-text">2</span></span>
						<h2 class="co-block__title">Pagamento</h2>
						<span class="co-block__hint">Pix (5% off) · Cartão até 10× · Boleto</span>
					</header>
					<div class="co-block__body">

						<!-- Campo de cupom integrado ao bloco (NAO no topo) -->
						<details class="co-coupon">
							<summary class="co-coupon__toggle">
								<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
									<path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/>
									<line x1="7" y1="7" x2="7.01" y2="7"/>
								</svg>
								Tem um cupom?
							</summary>
							<form class="co-coupon__form" method="post" action="<?php echo esc_url( wc_get_checkout_url() ); ?>">
								<label for="coupon_code" class="screen-reader-text">Código do cupom</label>
								<input type="text" name="coupon_code" id="coupon_code" class="input-text" placeholder="DIGITE SEU CUPOM" value="">
								<button type="submit" class="co-coupon__submit" name="apply_coupon" value="Aplicar">Aplicar</button>
							</form>
						</details>

						<div id="payment" class="woocommerce-checkout-payment">
							<?php if ( WC()->cart->needs_payment() ) : ?>
								<ul class="wc_payment_methods payment_methods methods">
									<?php
									$available_gateways = WC()->payment_gateways()->get_available_payment_gateways();
									if ( ! empty( $available_gateways ) ) {
										foreach ( $available_gateways as $gateway ) {
											wc_get_template( 'checkout/payment-method.php', array( 'gateway' => $gateway ) );
										}
									} else {
										echo '<li class="woocommerce-notice woocommerce-notice--info woocommerce-info">Ative um gateway de pagamento (Pix, Cartão ou Boleto) em <strong>WooCommerce → Configurações → Pagamentos</strong>.</li>';
									}
									?>
								</ul>
							<?php endif; ?>

							<?php do_action( 'woocommerce_review_order_before_submit' ); ?>

							<div class="form-row place-order">
								<noscript><?php esc_html_e( 'Since your browser does not support JavaScript, or it is disabled, please ensure you click the <em>Update Totals</em> button before placing your order.', 'woocommerce' ); ?><br/><button type="submit" class="button alt" name="woocommerce_checkout_update_totals" value="update totals">Update totals</button></noscript>
								<?php wp_nonce_field( 'woocommerce-process_checkout', 'woocommerce-process-checkout-nonce' ); ?>
							</div>

							<?php do_action( 'woocommerce_review_order_after_submit' ); ?>
						</div>
					</div>
				</section>

			</div>

			<!-- ───── SIDEBAR · RESUMO DO PEDIDO ───── -->
			<aside class="checkout-summary" aria-label="Resumo do pedido">
				<div id="order_review" class="woocommerce-checkout-review-order">
					<?php do_action( 'woocommerce_checkout_order_review' ); ?>
				</div>
			</aside>

		</div>

	</form>

</main>

<?php do_action( 'woocommerce_after_checkout_form', $checkout ); ?>
