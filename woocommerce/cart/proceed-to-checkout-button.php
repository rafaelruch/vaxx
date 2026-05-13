<?php
/**
 * VAXX · Override: Proceed to Checkout button
 *
 * O carrinho NÃO leva pra checkout de pagamento. Em vez disso, aponta para
 * /orcamento/ onde o cliente preenche dados e solicita proposta comercial.
 *
 * @package VAXX
 */

defined( 'ABSPATH' ) || exit;

$orc_url = function_exists( 'vaxx_orcamento_url' ) ? vaxx_orcamento_url() : home_url( '/orcamento/' );
?>
<a href="<?php echo esc_url( $orc_url ); ?>" class="checkout-button button alt wc-forward vx-orc-cart-cta">
	<?php esc_html_e( 'Fazer orçamento', 'vaxx' ); ?>
	<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" aria-hidden="true"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
</a>
