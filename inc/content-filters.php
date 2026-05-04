<?php
/**
 * VAXX · Filtros de conteúdo defensivos
 *
 * Corrige no render coisas que vivem no post_content do DB e não devem
 * estar lá:
 *   - ★ (estrela Unicode) → SVG inline (contrato VAXX é "zero emojis em UI")
 *   - Breadcrumb canônico ausente em páginas core (ex: /quem-somos/)
 *
 * Manter idempotente: cada filtro só age quando detecta a violação.
 *
 * @package VAXX
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Substitui ocorrências de ★ por SVG inline mantendo cor/tamanho do contexto.
 * O SVG herda currentColor e ocupa 1em — drop-in para qualquer texto.
 */
function vaxx_replace_star_with_svg( $content ) {
	if ( strpos( $content, '★' ) === false ) return $content;

	$svg = '<svg class="vx-star" aria-hidden="true" focusable="false" viewBox="0 0 24 24" width="1em" height="1em" fill="currentColor" style="display:inline-block;vertical-align:-0.125em"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>';

	return str_replace( '★', $svg, $content );
}
add_filter( 'the_content', 'vaxx_replace_star_with_svg', 20 );

/**
 * Mapa canônico de páginas que recebem breadcrumb injetada.
 * slug → label final do bc.
 */
function vaxx_pages_with_injected_breadcrumb() {
	return array(
		'quem-somos'              => 'Quem Somos',
		'depoimentos'             => 'Depoimentos',
		'contato'                 => 'Contato',
		'faq'                     => 'FAQ',
		'garantia'                => 'Garantia',
		'entrega-instalacao'      => 'Entrega e instalação',
		'trocas-e-devolucoes'     => 'Trocas e devoluções',
		'termos-de-uso'           => 'Termos de uso',
		'politica-de-privacidade' => 'Política de privacidade',
	);
}

/**
 * Adiciona classe `vx-has-injected-bc` ao body nas páginas que recebem o
 * breadcrumb injetado — pra o CSS conseguir empurrar o conteúdo abaixo do
 * header fixed (que normalmente é compensado pelo padding-top do main em
 * páginas com template próprio, como .page-linha).
 */
function vaxx_body_class_for_injected_bc( $classes ) {
	if ( ! is_singular( 'page' ) ) return $classes;
	$slug = get_post_field( 'post_name', get_the_ID() );
	$pages = vaxx_pages_with_injected_breadcrumb();
	if ( isset( $pages[ $slug ] ) ) {
		$classes[] = 'vx-has-injected-bc';
	}
	return $classes;
}
add_filter( 'body_class', 'vaxx_body_class_for_injected_bc' );

/**
 * Injeta o breadcrumb canônico antes do conteúdo de páginas que não trazem
 * `.bc` no markup. Páginas-chave que já têm breadcrumb embutido permanecem
 * intactas (idempotente: só age se faltar).
 *
 * Slugs cobertos: páginas institucionais e legais que devem sempre ter o bc.
 */
function vaxx_inject_canonical_breadcrumb( $content ) {
	if ( ! is_singular( 'page' ) ) return $content;

	$pages_with_bc = vaxx_pages_with_injected_breadcrumb();
	$slug = get_post_field( 'post_name', get_the_ID() );
	if ( ! isset( $pages_with_bc[ $slug ] ) ) return $content;

	// Já tem breadcrumb? não duplica.
	if ( strpos( $content, 'class="bc"' ) !== false || strpos( $content, "class='bc'" ) !== false ) return $content;
	if ( strpos( $content, 'class="vx-breadcrumb"' ) !== false ) return $content;

	$label = $pages_with_bc[ $slug ];
	$home  = esc_url( home_url( '/' ) );

	$bc = '<nav class="bc" aria-label="Breadcrumb"><div class="bc__inner">'
		. '<a href="' . $home . '">Início</a>'
		. '<span class="sep" aria-hidden="true">›</span>'
		. '<span class="is-current" aria-current="page">' . esc_html( $label ) . '</span>'
		. '</div></nav>';

	return $bc . $content;
}
add_filter( 'the_content', 'vaxx_inject_canonical_breadcrumb', 5 );

/**
 * Remove scripts do Mercado Pago em páginas que não são checkout.
 * Plugin enfileira em todo lugar e produz erros de console (formulário não
 * encontrado em /carrinho/, /minha-conta/, etc + crypto.randomUUID em http).
 *
 * Cobre todas as variantes do plugin (wc_mercadopago_*, mp_*, mercadopago_*,
 * melidata_*) e ainda remove scripts injetados via wp_print_footer_scripts
 * (melidata externo do mlstatic.com).
 */
function vaxx_dequeue_mercadopago_off_checkout() {
	// Mantém só em is_checkout() (inclui order-received endpoint).
	if ( function_exists( 'is_checkout' ) && is_checkout() ) return;

	$prefixes = array( 'wc_mercadopago_', 'mp_', 'mercadopago_', 'melidata_' );

	$wp_scripts = wp_scripts();
	if ( ! $wp_scripts ) return;

	foreach ( $wp_scripts->registered as $handle => $script ) {
		foreach ( $prefixes as $p ) {
			if ( strpos( $handle, $p ) === 0 ) {
				wp_dequeue_script( $handle );
				wp_deregister_script( $handle );
				break;
			}
		}
	}
}
add_action( 'wp_enqueue_scripts', 'vaxx_dequeue_mercadopago_off_checkout', 99 );

/**
 * Defesa segunda: remove ações de impressão de tags <script> do MP que
 * o plugin pendura via wp_head/wp_footer (fora do pipeline wp_enqueue).
 */
function vaxx_remove_mercadopago_print_actions() {
	if ( function_exists( 'is_checkout' ) && is_checkout() ) return;

	global $wp_filter;
	foreach ( array( 'wp_head', 'wp_footer', 'wp_print_footer_scripts', 'wp_print_scripts' ) as $hook ) {
		if ( ! isset( $wp_filter[ $hook ] ) ) continue;
		foreach ( $wp_filter[ $hook ]->callbacks as $priority => $cbs ) {
			foreach ( $cbs as $id => $cb ) {
				$func = $cb['function'] ?? null;
				if ( ! $func ) continue;
				$class = '';
				if ( is_array( $func ) ) {
					$class = is_object( $func[0] ) ? get_class( $func[0] ) : (string) $func[0];
				}
				if ( stripos( $class, 'MercadoPago' ) !== false || stripos( $class, 'Melidata' ) !== false ) {
					unset( $wp_filter[ $hook ]->callbacks[ $priority ][ $id ] );
				}
			}
		}
	}
}
add_action( 'wp', 'vaxx_remove_mercadopago_print_actions', 99 );
