<?php
/**
 * VAXX · Override completo do template Cart
 *
 * Substitui a table.shop_table default do WC por um layout 100% VAXX:
 * lista de cards de produto (esquerda) + summary sticky (direita), igual
 * ao layout da /orcamento/. Sem cálculo de frete (desligado no tema).
 *
 * @package VAXX
 */

defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_before_cart' ); ?>

<section class="vx-cart">
	<div class="vx-cart__inner">

		<header class="vx-cart__hero">
			<span class="vx-cart__eyebrow">Etapa 1 · Orçamento</span>
			<h1 class="vx-cart__title">Seu orçamento</h1>
			<p class="vx-cart__lead">Confira os equipamentos incluídos. Quando estiver tudo certo, siga para preencher seus dados — nosso time devolve a proposta em até 1 dia útil.</p>
		</header>

		<div class="vx-cart__layout">

			<!-- ─── COLUNA ESQUERDA: items ─── -->
			<form class="vx-cart__items-form" action="<?php echo esc_url( wc_get_cart_url() ); ?>" method="post">
				<?php do_action( 'woocommerce_before_cart_table' ); ?>

				<ul class="vx-cart__items" id="vxCartItems">
					<?php do_action( 'woocommerce_before_cart_contents' ); ?>

					<?php
					foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
						$_product   = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );
						$product_id = apply_filters( 'woocommerce_cart_item_product_id', $cart_item['product_id'], $cart_item, $cart_item_key );

						if ( $_product && $_product->exists() && $cart_item['quantity'] > 0 && apply_filters( 'woocommerce_cart_item_visible', true, $cart_item, $cart_item_key ) ) {
							$product_permalink = apply_filters( 'woocommerce_cart_item_permalink', $_product->is_visible() ? $_product->get_permalink( $cart_item ) : '', $cart_item, $cart_item_key );
							$thumb = $_product->get_image_id() ? wp_get_attachment_image_url( $_product->get_image_id(), 'vaxx-prod-thumb' ) : wc_placeholder_img_src( 'vaxx-prod-thumb' );
							$name  = $_product->get_name();
							$price_html = WC()->cart->get_product_price( $_product );
							$subtotal_html = WC()->cart->get_product_subtotal( $_product, $cart_item['quantity'] );
							?>
							<li class="vx-cart__item <?php echo esc_attr( apply_filters( 'woocommerce_cart_item_class', 'cart_item', $cart_item, $cart_item_key ) ); ?>" data-cart-key="<?php echo esc_attr( $cart_item_key ); ?>">

								<div class="vx-cart__item-media">
									<?php if ( $product_permalink ) : ?>
										<a href="<?php echo esc_url( $product_permalink ); ?>">
											<img src="<?php echo esc_url( $thumb ); ?>" alt="<?php echo esc_attr( $name ); ?>" loading="lazy">
										</a>
									<?php else : ?>
										<img src="<?php echo esc_url( $thumb ); ?>" alt="<?php echo esc_attr( $name ); ?>" loading="lazy">
									<?php endif; ?>
								</div>

								<div class="vx-cart__item-body">
									<h3 class="vx-cart__item-name">
										<?php if ( $product_permalink ) : ?>
											<a href="<?php echo esc_url( $product_permalink ); ?>"><?php echo wp_kses_post( $name ); ?></a>
										<?php else : ?>
											<?php echo wp_kses_post( $name ); ?>
										<?php endif; ?>
									</h3>

									<?php
									// Meta (variações, addons)
									echo wc_get_formatted_cart_item_data( $cart_item );
									?>

									<div class="vx-cart__item-price">
										<span class="vx-cart__item-price-label">Unitário:</span>
										<span class="vx-cart__item-price-value"><?php echo wp_kses_post( $price_html ); ?></span>
									</div>
								</div>

								<div class="vx-cart__item-actions">
									<div class="vx-cart__qty" role="group" aria-label="Quantidade">
										<?php
										$max_value  = $_product->get_max_purchase_quantity();
										$min_value  = apply_filters( 'woocommerce_quantity_input_min', $_product->get_min_purchase_quantity(), $_product );
										echo apply_filters(
											'woocommerce_cart_item_quantity',
											woocommerce_quantity_input(
												array(
													'input_name'   => "cart[{$cart_item_key}][qty]",
													'input_value'  => $cart_item['quantity'],
													'max_value'    => $max_value > 0 ? $max_value : '',
													'min_value'    => $min_value,
													'product_name' => $name,
												),
												$_product,
												false
											),
											$cart_item_key,
											$cart_item
										);
										?>
									</div>

									<div class="vx-cart__item-subtotal">
										<span class="vx-cart__item-subtotal-label">Subtotal</span>
										<strong class="vx-cart__item-subtotal-value"><?php echo wp_kses_post( $subtotal_html ); ?></strong>
									</div>

									<a href="<?php echo esc_url( wc_get_cart_remove_url( $cart_item_key ) ); ?>" class="vx-cart__item-remove" aria-label="<?php echo esc_attr( sprintf( 'Remover %s do orçamento', $name ) ); ?>">
										<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
											<polyline points="3 6 5 6 21 6"/>
											<path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>
											<path d="M10 11v6M14 11v6"/>
											<path d="M9 6V4a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2"/>
										</svg>
										<span>Remover</span>
									</a>
								</div>

							</li>
							<?php
						}
					}
					?>
					<?php do_action( 'woocommerce_cart_contents' ); ?>
				</ul>

				<div class="vx-cart__actions-row">
					<div class="vx-cart__coupon">
						<?php if ( wc_coupons_enabled() ) : ?>
							<label for="coupon_code" class="vx-cart__coupon-label">Cupom de desconto</label>
							<div class="vx-cart__coupon-group">
								<input type="text" name="coupon_code" class="vx-cart__coupon-input" id="coupon_code" value="" placeholder="DIGITE O CUPOM">
								<button type="submit" class="vx-cart__coupon-submit" name="apply_coupon" value="Aplicar cupom">Aplicar</button>
							</div>
						<?php endif; ?>
					</div>

					<button type="submit" class="vx-cart__update" name="update_cart" value="Atualizar orçamento">
						<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
							<polyline points="23 4 23 10 17 10"/>
							<polyline points="1 20 1 14 7 14"/>
							<path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/>
						</svg>
						Atualizar
					</button>

					<?php wp_nonce_field( 'woocommerce-cart', 'woocommerce-cart-nonce' ); ?>
				</div>

				<?php do_action( 'woocommerce_after_cart_contents' ); ?>
				<?php do_action( 'woocommerce_after_cart_table' ); ?>
			</form>

			<!-- ─── COLUNA DIREITA: summary ─── -->
			<aside class="vx-cart__summary" aria-label="Resumo do orçamento">
				<div class="vx-cart__summary-inner">

					<h2 class="vx-cart__summary-title">Resumo</h2>

					<?php do_action( 'woocommerce_before_cart_totals' ); ?>

					<dl class="vx-cart__totals">
						<dt>Subtotal</dt>
						<dd><?php wc_cart_totals_subtotal_html(); ?></dd>

						<?php foreach ( WC()->cart->get_coupons() as $code => $coupon ) : ?>
							<dt class="vx-cart__totals-coupon">Cupom: <?php echo esc_html( wc_cart_totals_coupon_label( $coupon, false ) ); ?></dt>
							<dd><?php wc_cart_totals_coupon_html( $coupon ); ?></dd>
						<?php endforeach; ?>

						<?php foreach ( WC()->cart->get_fees() as $fee ) : ?>
							<dt><?php echo esc_html( $fee->name ); ?></dt>
							<dd><?php wc_cart_totals_fee_html( $fee ); ?></dd>
						<?php endforeach; ?>

						<dt class="vx-cart__totals-grand">Total estimado</dt>
						<dd class="vx-cart__totals-grand-value"><?php wc_cart_totals_order_total_html(); ?></dd>
					</dl>

					<a href="<?php echo esc_url( function_exists( 'vaxx_orcamento_url' ) ? vaxx_orcamento_url() : home_url( '/orcamento/' ) ); ?>" class="vx-cart__cta">
						<span>Fazer orçamento</span>
						<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" aria-hidden="true"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
					</a>

					<p class="vx-cart__summary-note">Valor final pode variar conforme frete, montagem e condições negociadas direto com a fábrica.</p>

					<?php do_action( 'woocommerce_after_cart_totals' ); ?>
				</div>
			</aside>

		</div>

	</div>
</section>

<?php do_action( 'woocommerce_after_cart' ); ?>
