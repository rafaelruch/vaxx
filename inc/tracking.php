<?php
/**
 * VAXX · Rastreamento — Google Ads (gtag.js) + Meta Pixel (fbq)
 *
 * Conforme tracking-vaxx.md:
 *   Google Ads AW-18024792599
 *     - tag base em todas as páginas
 *     - Lead     → orçamento enviado (tela de obrigado)
 *     - WhatsApp → clique em wa.me / api.whatsapp.com
 *     - Telefone → clique em tel: (usa o rótulo de Lead; decisão pendente de
 *                  criar conversão dedicada pra separar ligação de formulário)
 *
 *   Meta Pixel 1671573694263877
 *     - PageView         → todas as páginas
 *     - ViewContent      → PDP
 *     - AddToCart        → produto incluído no orçamento
 *     - InitiateCheckout → abriu /orcamento/ com itens
 *     - Lead             → orçamento enviado (eventID = nº do pedido)
 *     - Contact          → clique em WhatsApp / telefone
 *
 * ⛔ Só IDs da VAXX neste arquivo. A Delva é outra empresa, com conta e pixel
 * próprios — misturar contamina os dados das duas e faz o algoritmo otimizar
 * errado. Os IDs da Delva não aparecem aqui de propósito, nem em comentário:
 * a auditoria do tracking é feita por grep, e literal em comentário vira
 * falso positivo (ou pior, alvo de copy/paste). Ver tracking-vaxx.md.
 *
 * Não dispara em ambiente local nem para quem edita o site, pra não sujar as
 * contas reais com teste e navegação da equipe.
 *
 * @package VAXX
 */

if ( ! defined( 'ABSPATH' ) ) exit;

const VAXX_ADS_ID         = 'AW-18024792599';
const VAXX_ADS_LABEL_LEAD = 'AW-18024792599/OOJYCKn3n44cEJeE8pJD';
const VAXX_ADS_LABEL_ZAP  = 'AW-18024792599/z9OKCKz3n44cEJeE8pJD';
const VAXX_META_PIXEL_ID  = '1671573694263877';

/**
 * Se deve carregar o rastreamento nesta requisição.
 *
 * Vale para gtag e fbq: os dois usam o mesmo guard.
 */
function vaxx_ads_ativo() {
	if ( is_admin() || wp_doing_ajax() || is_customize_preview() ) {
		return false;
	}
	if ( current_user_can( 'edit_posts' ) ) {
		return false;
	}
	if ( defined( 'VAXX_ADS_FORCAR' ) && VAXX_ADS_FORCAR ) {
		return true;
	}
	return 'production' === wp_get_environment_type();
}

/**
 * Pedido da tela de obrigado, se a tela realmente vai renderizar.
 *
 * Centraliza a checagem usada pelo Lead do Google e do Meta: a conversão só
 * pode disparar quando o cliente de fato vê a confirmação.
 */
function vaxx_ads_pedido_obrigado() {
	$slug = defined( 'VAXX_ORCAMENTO_SLUG' ) ? VAXX_ORCAMENTO_SLUG : 'orcamento';
	if ( ! isset( $_GET['orcamento'] ) || ! is_page( $slug ) ) {
		return null;
	}
	$order_id = absint( $_GET['orcamento'] );
	if ( ! $order_id || ! function_exists( 'wc_get_order' ) ) {
		return null;
	}
	$order = wc_get_order( $order_id );
	if ( ! function_exists( 'vaxx_pode_ver_orcamento' ) || ! vaxx_pode_ver_orcamento( $order ) ) {
		return null;
	}
	return $order;
}

/**
 * Tag base do Google no <head>.
 */
function vaxx_ads_tag_base() {
	if ( ! vaxx_ads_ativo() ) {
		return;
	}
	$id = VAXX_ADS_ID;
	?>
<!-- Google tag (gtag.js) — VAXX -->
<script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo esc_attr( $id ); ?>"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());
  gtag('config', <?php echo wp_json_encode( $id ); ?>);
</script>
	<?php
}
add_action( 'wp_head', 'vaxx_ads_tag_base', 1 );

/**
 * Pixel base do Meta no <head>.
 */
function vaxx_meta_pixel_base() {
	if ( ! vaxx_ads_ativo() ) {
		return;
	}
	$id = VAXX_META_PIXEL_ID;
	?>
<!-- Meta Pixel — VAXX -->
<script>
!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?
n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;
n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;
t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,
document,'script','https://connect.facebook.net/en_US/fbevents.js');
fbq('init', <?php echo wp_json_encode( $id ); ?>);
fbq('track', 'PageView');
</script>
<noscript><img height="1" width="1" style="display:none"
  src="https://www.facebook.com/tr?id=<?php echo rawurlencode( $id ); ?>&ev=PageView&noscript=1" alt=""/></noscript>
	<?php
}
add_action( 'wp_head', 'vaxx_meta_pixel_base', 2 );

/**
 * Guarda o AddToCart pra emitir no carregamento seguinte.
 *
 * O PDP inclui no orçamento por POST de formulário, não por AJAX: a página
 * navega logo em seguida e um fbq disparado no submit corre o risco de não
 * sair a tempo. Estacionar na sessão e emitir no próximo render é confiável.
 */
function vaxx_meta_marca_add_to_cart( $cart_item_key, $product_id, $quantity ) {
	if ( ! function_exists( 'WC' ) || ! WC()->session ) {
		return;
	}
	WC()->session->set( 'vaxx_meta_add_to_cart', array(
		'id'  => (int) $product_id,
		'qty' => (int) $quantity,
	) );
}
add_action( 'woocommerce_add_to_cart', 'vaxx_meta_marca_add_to_cart', 10, 3 );

/**
 * Eventos de conversão — gtag e fbq no mesmo lugar.
 */
function vaxx_ads_conversoes() {
	if ( ! vaxx_ads_ativo() ) {
		return;
	}

	$order = vaxx_ads_pedido_obrigado();

	// ─── Lead ───
	// Google: transaction_id · Meta: eventID — os dois com o nº do pedido, pra
	// que um F5 na tela de obrigado não conte a mesma conversão de novo.
	if ( $order ) {
		$order_id = (string) $order->get_id();
		?>
<script>
  gtag('event', 'conversion', {
    'send_to': <?php echo wp_json_encode( VAXX_ADS_LABEL_LEAD ); ?>,
    'transaction_id': <?php echo wp_json_encode( $order_id ); ?>
  });
  fbq('track', 'Lead',
    { content_name: 'Orcamento', value: <?php echo (float) $order->get_total(); ?>, currency: 'BRL' },
    { eventID: <?php echo wp_json_encode( $order_id ); ?> }
  );
</script>
		<?php
	}

	// ─── ViewContent: PDP ───
	if ( function_exists( 'is_product' ) && is_product() ) {
		$p = wc_get_product( get_the_ID() );
		if ( $p ) {
			?>
<script>
  fbq('track', 'ViewContent', {
    content_ids: [<?php echo wp_json_encode( (string) $p->get_id() ); ?>],
    content_name: <?php echo wp_json_encode( $p->get_name() ); ?>,
    content_type: 'product',
    value: <?php echo (float) $p->get_price(); ?>,
    currency: 'BRL'
  });
</script>
			<?php
		}
	}

	// ─── AddToCart: emitido no render seguinte ao POST ───
	if ( function_exists( 'WC' ) && WC()->session ) {
		$add = WC()->session->get( 'vaxx_meta_add_to_cart' );
		if ( $add ) {
			WC()->session->set( 'vaxx_meta_add_to_cart', null );
			$p = wc_get_product( $add['id'] );
			if ( $p ) {
				?>
<script>
  fbq('track', 'AddToCart', {
    content_ids: [<?php echo wp_json_encode( (string) $p->get_id() ); ?>],
    content_name: <?php echo wp_json_encode( $p->get_name() ); ?>,
    content_type: 'product',
    value: <?php echo (float) $p->get_price() * (int) $add['qty']; ?>,
    currency: 'BRL'
  });
</script>
				<?php
			}
		}
	}

	// ─── InitiateCheckout: abriu /orcamento/ com itens (e não é a tela de obrigado) ───
	$slug = defined( 'VAXX_ORCAMENTO_SLUG' ) ? VAXX_ORCAMENTO_SLUG : 'orcamento';
	if ( ! $order && is_page( $slug ) && function_exists( 'WC' ) && WC()->cart && ! WC()->cart->is_empty() ) {
		?>
<script>
  fbq('track', 'InitiateCheckout', {
    num_items: <?php echo (int) WC()->cart->get_cart_contents_count(); ?>,
    value: <?php echo (float) WC()->cart->get_cart_contents_total(); ?>,
    currency: 'BRL'
  });
</script>
		<?php
	}

	// ─── Contact / WhatsApp / Telefone ───
	// Um listener só: os seletores do Google e do Meta são os mesmos.
	?>
<script>
document.addEventListener('DOMContentLoaded', function () {
  var ZAP = 'a[href*="wa.me"], a[href*="api.whatsapp.com"]';
  var TEL = 'a[href^="tel:"]';

  function aoClicar(seletor, fn) {
    document.querySelectorAll(seletor).forEach(function (el) {
      el.addEventListener('click', fn);
    });
  }

  aoClicar(ZAP, function () {
    gtag('event', 'conversion', { 'send_to': <?php echo wp_json_encode( VAXX_ADS_LABEL_ZAP ); ?> });
    fbq('track', 'Contact', { content_name: 'WhatsApp' });
  });

  // Telefone: no Google usa o rótulo de Lead enquanto não houver conversão dedicada.
  aoClicar(TEL, function () {
    gtag('event', 'conversion', { 'send_to': <?php echo wp_json_encode( VAXX_ADS_LABEL_LEAD ); ?> });
    fbq('track', 'Contact', { content_name: 'Telefone' });
  });
});
</script>
	<?php
}
add_action( 'wp_footer', 'vaxx_ads_conversoes', 20 );
