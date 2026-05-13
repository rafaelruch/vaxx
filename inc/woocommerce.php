<?php
/**
 * VAXX · WooCommerce integration e overrides
 *
 * @package VAXX
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Forca WooCommerce a usar classic PHP templates (archive-product.php, single-product.php)
 * em vez dos block templates internos. Sem isso, o tema woocommerce/*.php é ignorado
 * porque o WC trata block themes diferente.
 */
function vaxx_disable_woo_block_templates( $return, $template_name ) {
	return false;
}
add_filter( 'woocommerce_has_block_template', '__return_false' );
add_filter( 'should_load_block_editor_scripts_and_styles', function( $should ) {
	return $should;
}, 10 );

/**
 * Declara compatibilidade com High-Performance Order Storage (HPOS)
 * e Cart/Checkout Blocks.
 */
function vaxx_woo_declare_compat() {
	if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
	}
}
add_action( 'before_woocommerce_init', 'vaxx_woo_declare_compat' );

/**
 * Remove alguns defaults visuais do WooCommerce que não queremos.
 * Temos nosso próprio design; os elementos padrão do Woo poluem.
 */
function vaxx_woo_remove_defaults() {
	// Sidebar padrão dos archives/PDP
	remove_action( 'woocommerce_sidebar', 'woocommerce_get_sidebar', 10 );

	// Breadcrumbs padrão do Woo (usaremos o canônico VAXX)
	remove_action( 'woocommerce_before_main_content', 'woocommerce_breadcrumb', 20 );

	// Result count e ordering (integrado na toolbar do design VAXX)
	remove_action( 'woocommerce_before_shop_loop', 'woocommerce_result_count', 20 );
	remove_action( 'woocommerce_before_shop_loop', 'woocommerce_catalog_ordering', 30 );

	// Esconde o "ship to different address" checkbox — PF = billing = shipping
	add_filter( 'woocommerce_ship_to_different_address_checked', '__return_false' );
	add_filter( 'woocommerce_cart_needs_shipping_address', '__return_false' );
}

/**
 * Remove o submit button padrao do Woo no checkout — usamos o proprio do sidebar.
 */
function vaxx_remove_woo_checkout_submit() {
	remove_action( 'woocommerce_review_order_after_submit', 'wc_checkout_place_order', 10 );
}
add_action( 'wp', 'vaxx_remove_woo_checkout_submit' );

/**
 * Reativa cupons (desabilitados anteriormente por engano) E remove o banner
 * auto-injetado no topo do checkout. Cupom agora mora DENTRO do bloco pagamento.
 */
add_action( 'template_redirect', function() {
	if ( ! is_checkout() ) return;
	// Remove banner default do topo + notices auto (renderizamos manualmente apos breadcrumb)
	remove_action( 'woocommerce_before_checkout_form', 'woocommerce_checkout_coupon_form', 10 );
	remove_action( 'woocommerce_before_checkout_form', 'woocommerce_checkout_login_form', 10 );
	remove_action( 'woocommerce_before_checkout_form_cart_notices', 'wc_print_notices', 10 );
	// Notices auto injetados por Woo/WP em varias posicoes — removemos para controlar manualmente
	remove_action( 'woocommerce_before_cart', 'wc_print_notices', 10 );
	remove_action( 'woocommerce_before_single_product', 'wc_print_notices', 10 );
	remove_action( 'woocommerce_before_shop_loop', 'wc_print_notices', 10 );
} );

/**
 * Remove qualquer print de notices auto-injetado fora das posicoes VAXX.
 * O unico lugar que imprime notices e o nosso form-checkout.php (logo apos a .bc)
 * e/ou via JS vaxx-notice.
 */
add_action( 'wp_print_footer_scripts', function() {
	// Nada — marker para evitar chamadas multiplas em loops
}, 1 );

/**
 * No single-product e archive, remove o notices wrapper auto-impresso
 * pelo tema default do Woo (loop-start/loop-end).
 */
add_filter( 'woocommerce_show_messages', '__return_true' );

/**
 * Customiza campos do checkout BR: adiciona Bairro e Numero separado,
 * reordena pra CEP vir primeiro (fluxo natural ViaCEP autofill).
 */
function vaxx_customize_checkout_fields( $fields ) {
	// CPF, Bairro, Numero ja sao adicionados pelo plugin
	// "Brazilian Market on WooCommerce" (billing_cpf, billing_neighborhood, billing_number).
	// Aqui so ajustamos a PRIORITY pra ordenar na nossa sequencia VAXX.

	if ( isset( $fields['billing']['billing_cpf'] ) ) {
		$fields['billing']['billing_cpf']['priority'] = 22;
		$fields['billing']['billing_cpf']['class']    = array( 'form-row-wide' );
	}
	if ( isset( $fields['billing']['billing_number'] ) ) {
		$fields['billing']['billing_number']['priority'] = 51;
		$fields['billing']['billing_number']['class']    = array( 'form-row-first' );
	}
	if ( isset( $fields['billing']['billing_neighborhood'] ) ) {
		$fields['billing']['billing_neighborhood']['priority'] = 61;
		$fields['billing']['billing_neighborhood']['class']    = array( 'form-row-wide' );
	}
	// PF/PJ selector do plugin — deixa em cima
	if ( isset( $fields['billing']['billing_persontype'] ) ) {
		$fields['billing']['billing_persontype']['priority'] = 5;
		$fields['billing']['billing_persontype']['class']    = array( 'form-row-wide' );
	}
	// CNPJ, RG, IE — escondidos por default (plugin controla via JS)
	if ( isset( $fields['billing']['billing_cnpj'] ) ) {
		$fields['billing']['billing_cnpj']['priority'] = 23;
		$fields['billing']['billing_cnpj']['class']    = array( 'form-row-wide' );
	}
	if ( isset( $fields['billing']['billing_rg'] ) ) {
		$fields['billing']['billing_rg']['priority']   = 24;
		$fields['billing']['billing_rg']['class']      = array( 'form-row-wide' );
	}
	// Cellphone do plugin — subst. Woo phone se existir
	if ( isset( $fields['billing']['billing_cellphone'] ) ) {
		$fields['billing']['billing_cellphone']['priority']    = 30;
		$fields['billing']['billing_cellphone']['class']       = array( 'form-row-wide' );
		$fields['billing']['billing_cellphone']['placeholder'] = '(47) 99999-9999';
	}

	// Reordena via priority (menor = primeiro)
	if ( isset( $fields['billing']['billing_postcode'] ) ) {
		$fields['billing']['billing_postcode']['priority']   = 45;
		$fields['billing']['billing_postcode']['placeholder'] = '00000-000';
		$fields['billing']['billing_postcode']['label']      = 'CEP';
		$fields['billing']['billing_postcode']['class']      = array( 'form-row-wide' );
	}
	if ( isset( $fields['billing']['billing_first_name'] ) ) {
		$fields['billing']['billing_first_name']['priority'] = 10;
		$fields['billing']['billing_first_name']['label']    = 'Nome';
	}
	if ( isset( $fields['billing']['billing_last_name'] ) ) {
		$fields['billing']['billing_last_name']['priority'] = 20;
		$fields['billing']['billing_last_name']['label']    = 'Sobrenome';
	}
	if ( isset( $fields['billing']['billing_email'] ) ) {
		$fields['billing']['billing_email']['priority'] = 25;
		$fields['billing']['billing_email']['placeholder'] = 'seu@email.com';
	}
	if ( isset( $fields['billing']['billing_phone'] ) ) {
		$fields['billing']['billing_phone']['priority'] = 30;
		$fields['billing']['billing_phone']['placeholder'] = '(47) 99999-9999';
		$fields['billing']['billing_phone']['label'] = 'Telefone / WhatsApp';
	}
	if ( isset( $fields['billing']['billing_country'] ) ) {
		$fields['billing']['billing_country']['priority'] = 40;
	}
	if ( isset( $fields['billing']['billing_address_1'] ) ) {
		$fields['billing']['billing_address_1']['priority']   = 50;
		$fields['billing']['billing_address_1']['label']      = 'Rua / Logradouro';
		$fields['billing']['billing_address_1']['placeholder']= 'Rua, avenida...';
		$fields['billing']['billing_address_1']['class']      = array( 'form-row-last' );
	}
	if ( isset( $fields['billing']['billing_address_2'] ) ) {
		$fields['billing']['billing_address_2']['priority']   = 60;
		$fields['billing']['billing_address_2']['label']      = 'Complemento';
		$fields['billing']['billing_address_2']['placeholder']= 'Apto, bloco, sala... (opcional)';
		$fields['billing']['billing_address_2']['class']      = array( 'form-row-wide' );
	}
	if ( isset( $fields['billing']['billing_city'] ) ) {
		$fields['billing']['billing_city']['priority'] = 70;
		$fields['billing']['billing_city']['class']    = array( 'form-row-first' );
	}
	if ( isset( $fields['billing']['billing_state'] ) ) {
		$fields['billing']['billing_state']['priority'] = 80;
		$fields['billing']['billing_state']['class']    = array( 'form-row-last' );
		$fields['billing']['billing_state']['label']    = 'UF';
	}
	// Esconde company por default
	if ( isset( $fields['billing']['billing_company'] ) ) {
		unset( $fields['billing']['billing_company'] );
	}

	// Forca sort por priority (Woo nao re-ordena automaticamente em todos os fluxos)
	uasort( $fields['billing'], function( $a, $b ) {
		$pa = isset( $a['priority'] ) ? (int) $a['priority'] : 999;
		$pb = isset( $b['priority'] ) ? (int) $b['priority'] : 999;
		return $pa - $pb;
	} );

	return $fields;
}
add_filter( 'woocommerce_checkout_fields', 'vaxx_customize_checkout_fields', 9999 );

/**
 * Defensivo: garante que o billing_postcode default do locale BR
 * nao herde prioridade alta. Rodado tarde pra vencer outros plugins.
 */
add_filter( 'woocommerce_get_country_locale_default', function( $address_fields ) {
	if ( isset( $address_fields['postcode'] ) ) {
		$address_fields['postcode']['priority'] = 45;
	}
	return $address_fields;
}, 9999 );
add_filter( 'woocommerce_get_country_locale', function( $locale ) {
	if ( isset( $locale['BR']['postcode'] ) ) {
		$locale['BR']['postcode']['priority'] = 45;
	}
	if ( isset( $locale['default']['postcode'] ) ) {
		$locale['default']['postcode']['priority'] = 45;
	}
	return $locale;
}, 9999 );

/**
 * Remove a coluna 'Marcas' (product_brand, WC 9+) do admin de produtos —
 * VAXX e marca unica, nao precisa.
 */
add_filter( 'manage_edit-product_columns', function( $cols ) {
	unset( $cols['taxonomy-product_brand'] );
	return $cols;
}, 99 );

/**
 * Esconde menu submenu "Marcas" + filtro de marca no admin.
 */
add_action( 'admin_menu', function() {
	remove_submenu_page( 'edit.php?post_type=product', 'edit-tags.php?taxonomy=product_brand&post_type=product' );
}, 99 );

add_action( 'admin_head-edit.php', function() {
	global $typenow;
	if ( $typenow !== 'product' ) return;
	echo '<style>
		select[name="product_brand"] { display: none !important; }
		.wp-list-table .column-taxonomy-product_line { min-width: 140px !important; width: 140px !important; }
	</style>';
} );

/**
 * Persiste o billing_neighborhood e billing_number no pedido.
 */
// CPF/CNPJ/RG/Bairro/Numero: validacao + persistencia agora sao do plugin
// "Brazilian Market on WooCommerce" (billing_cpf etc). Removido codigo custom.
function vaxx_save_custom_checkout_fields( $order_id ) {
	// Hook reservado caso precisemos adicionar meta custom do tema no futuro.
	return $order_id;
}
add_action( 'woocommerce_checkout_update_order_meta', 'vaxx_save_custom_checkout_fields' );

/**
 * Substitui o <input type="submit"> Woo auto-renderizado pelo nosso custom
 * (ou oculta se estiver fora do sidebar). O template form-checkout.php
 * ja tem o botao dentro de .checkout-summary__cta.
 */
add_filter( 'woocommerce_order_button_html', function( $html ) {
	return ''; // zera — nosso form-checkout.php renderiza o proprio
}, 20 );
add_action( 'init', 'vaxx_woo_remove_defaults' );

/**
 * Aumenta produtos por pagina no archive para que o filtro
 * por grupo muscular funcione sem paginar (client-side).
 */
function vaxx_woo_products_per_page( $cols ) {
	return 200;
}
add_filter( 'loop_shop_per_page', 'vaxx_woo_products_per_page', 20 );

/**
 * Endpoint AJAX: retorna contagem atual do carrinho (qty total + unique items)
 * Usado pelo JS apos add-to-cart para atualizar badge com valor real.
 */
function vaxx_ajax_cart_count() {
	if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
		wp_send_json( array( 'count' => 0, 'unique' => 0 ) );
	}
	wp_send_json( array(
		'count'  => WC()->cart->get_cart_contents_count(),
		'unique' => count( WC()->cart->get_cart() ),
	) );
}
add_action( 'wp_ajax_vaxx_cart_count',        'vaxx_ajax_cart_count' );
add_action( 'wp_ajax_nopriv_vaxx_cart_count', 'vaxx_ajax_cart_count' );

/**
 * Endpoint AJAX: retorna o HTML completo do drawer (mini-cart) para
 * substituir no DOM apos add-to-cart — sem reload da pagina.
 */
function vaxx_ajax_cart_html() {
	if ( ! function_exists( 'WC' ) ) wp_send_json( array( 'html' => '' ) );
	// Garante que a cart esteja carregada na sessao deste request
	if ( ! WC()->cart ) {
		wc_load_cart();
	}
	$html = do_shortcode( '[vaxx_mini_cart]' );
	wp_send_json( array(
		'html'  => $html,
		'count' => WC()->cart->get_cart_contents_count(),
	) );
}
add_action( 'wp_ajax_vaxx_cart_html',        'vaxx_ajax_cart_html' );
add_action( 'wp_ajax_nopriv_vaxx_cart_html', 'vaxx_ajax_cart_html' );

/**
 * Renderiza N produtos em destaque como .prod-card (mesma estrutura do archive).
 * Usado via shortcode e via marker <!-- VAXX_PRODUTOS_DESTAQUE --> em the_content.
 */
function vaxx_render_produtos_destaque( $args = array() ) {
	$args = wp_parse_args( $args, array(
		'count'   => 8,
		'orderby' => 'rand', // variedade por carregamento; 'menu_order' pra ordem fixa
	) );

	$query = new WP_Query( array(
		'post_type'      => 'product',
		'posts_per_page' => intval( $args['count'] ),
		'orderby'        => $args['orderby'],
		'post_status'    => 'publish',
		'meta_query'     => array(
			array(
				'key'     => '_stock_status',
				'value'   => 'instock',
			),
		),
	) );

	if ( ! $query->have_posts() ) return '';

	ob_start();
	while ( $query->have_posts() ) {
		$query->the_post();
		global $product;
		$product = wc_get_product( get_the_ID() );
		if ( ! $product ) continue;
		wc_get_template_part( 'content', 'product' );
	}
	wp_reset_postdata();
	return ob_get_clean();
}

/**
 * Shortcode [vaxx_produtos_destaque count="8"]
 */
function vaxx_shortcode_produtos_destaque( $atts ) {
	$atts = shortcode_atts( array( 'count' => 8 ), $atts );
	return vaxx_render_produtos_destaque( $atts );
}
add_shortcode( 'vaxx_produtos_destaque', 'vaxx_shortcode_produtos_destaque' );

/**
 * Filter em the_content: substitui marker HTML <!-- VAXX_PRODUTOS_DESTAQUE -->
 * por produtos reais dinamicos. Marker sobrevive ao wpautop e blocos wp:html.
 */
function vaxx_render_produtos_marker( $content ) {
	if ( strpos( $content, 'VAXX_PRODUTOS_DESTAQUE' ) === false ) return $content;
	$html = vaxx_render_produtos_destaque( array( 'count' => 8 ) );
	return preg_replace( '/<!--\s*VAXX_PRODUTOS_DESTAQUE(?:\s+count="(\d+)")?\s*-->/', $html, $content, 1 );
}
add_filter( 'the_content', 'vaxx_render_produtos_marker', 5 );

/**
 * Força Classic Cart/Checkout independente do que está no post_content.
 *
 * Por que: o tema VAXX foi todo construído sobre os templates clássicos
 * (woocommerce/checkout/form-checkout.php, review-order.php, mini-cart drawer
 * custom). O Cart Block (Gutenberg) traz por padrão o "Empty Cart Block" com
 * uma seção "New in store" que injeta produtos sugeridos, ignora os overrides
 * PHP do tema e quebra o flow do checkout custom.
 *
 * O `[woocommerce_checkout]` shortcode internamente roteia order-received,
 * order-pay e thankyou pelos templates corretos — não precisa condicionar.
 */
function vaxx_force_classic_cart_checkout( $content ) {
	if ( ! function_exists( 'is_cart' ) || ! function_exists( 'is_checkout' ) ) return $content;
	if ( ! in_the_loop() || ! is_main_query() ) return $content;

	if ( is_cart() )     return do_shortcode( '[woocommerce_cart]' );
	if ( is_checkout() ) return do_shortcode( '[woocommerce_checkout]' );
	return $content;
}
// priority 5 — força classic ANTES dos filtros que prepend breadcrumb (10) e
// substituem ★ por SVG (10), pra esses filtros operarem em cima do output
// do shortcode em vez de serem sobrescritos.
add_filter( 'the_content', 'vaxx_force_classic_cart_checkout', 5 );

/**
 * Cria atributo global "pa_regulagem-real" (sim/não) no Woo.
 * Executado uma vez via hook after_switch_theme.
 */
function vaxx_woo_setup_attributes() {
	if ( ! function_exists( 'wc_create_attribute' ) ) return;
	global $wpdb;
	$exists = $wpdb->get_var( "SELECT attribute_id FROM {$wpdb->prefix}woocommerce_attribute_taxonomies WHERE attribute_name = 'regulagem-real'" );
	if ( ! $exists ) {
		wc_create_attribute( array(
			'name'         => 'Regulagem Real',
			'slug'         => 'regulagem-real',
			'type'         => 'select',
			'order_by'     => 'menu_order',
			'has_archives' => false,
		) );
		// Força refresh das tax
		delete_transient( 'wc_attribute_taxonomies' );
	}
}
add_action( 'after_switch_theme', 'vaxx_woo_setup_attributes' );

/**
 * Seed das linhas (taxonomy terms) no after_switch_theme.
 */
function vaxx_woo_seed_taxonomies() {
	$linhas = array(
		'Articulados'       => 'articulados',
		'Bateria de Pesos'  => 'bateria-de-pesos',
		'Linha Cardio'      => 'linha-cardio',
		'Acessórios'        => 'acessorios',
	);
	foreach ( $linhas as $label => $slug ) {
		if ( ! term_exists( $slug, 'product_line' ) ) {
			wp_insert_term( $label, 'product_line', array( 'slug' => $slug ) );
		}
	}

	$grupos = array(
		'Peito'   => 'peito',
		'Costas'  => 'costas',
		'Ombros'  => 'ombros',
		'Pernas'  => 'pernas',
		'Glúteos' => 'gluteos',
		'Braços'  => 'bracos',
		'Core'    => 'core',
	);
	foreach ( $grupos as $label => $slug ) {
		if ( ! term_exists( $slug, 'muscle_group' ) ) {
			wp_insert_term( $label, 'muscle_group', array( 'slug' => $slug ) );
		}
	}
}
add_action( 'after_switch_theme', 'vaxx_woo_seed_taxonomies' );

/**
 * Handler AJAX para salvar leads de aluguel vindos do modal da PDP.
 * Cria post rent_lead, seta ACF fields, retorna sucesso.
 */
function vaxx_handle_rent_lead_submission() {
	// Verifica nonce (gerado na pattern do modal)
	if ( ! isset( $_POST['vaxx_rent_nonce'] ) || ! wp_verify_nonce( $_POST['vaxx_rent_nonce'], 'vaxx_rent_lead' ) ) {
		wp_send_json_error( array( 'message' => 'Sessão expirada. Atualize a página.' ), 403 );
	}

	$nome     = sanitize_text_field( $_POST['nome']     ?? '' );
	$telefone = sanitize_text_field( $_POST['telefone'] ?? '' );
	$email    = sanitize_email(       $_POST['email']    ?? '' );
	$produto  = sanitize_text_field( $_POST['produto']  ?? '' );

	if ( ! $nome || ! $telefone || ! $email || ! $produto ) {
		wp_send_json_error( array( 'message' => 'Campos obrigatórios faltando.' ), 400 );
	}

	$post_id = wp_insert_post( array(
		'post_type'   => 'rent_lead',
		'post_title'  => $nome . ' · ' . $produto,
		'post_status' => 'publish',
		'meta_input'  => array(
			'nome'        => $nome,
			'telefone'    => $telefone,
			'email'       => $email,
			'produto'     => $produto,
			'status_lead' => 'Aguardando contato',
		),
	) );

	if ( is_wp_error( $post_id ) || ! $post_id ) {
		wp_send_json_error( array( 'message' => 'Erro ao salvar. Tente novamente.' ), 500 );
	}

	// Notifica o comercial por email (pode personalizar depois)
	$to = vaxx_get_option( 'email_comercial', get_option( 'admin_email' ) );
	wp_mail(
		$to,
		'[VAXX] Novo lead de aluguel: ' . $produto,
		sprintf(
			"Novo lead recebido.\n\nProduto: %s\nNome: %s\nTelefone: %s\nE-mail: %s\n\nVeja no admin: %s",
			$produto, $nome, $telefone, $email,
			admin_url( 'post.php?post=' . $post_id . '&action=edit' )
		)
	);

	wp_send_json_success( array( 'message' => 'Solicitação enviada. Comercial responde em 1 dia útil.' ) );
}
add_action( 'wp_ajax_vaxx_rent_lead',        'vaxx_handle_rent_lead_submission' );
add_action( 'wp_ajax_nopriv_vaxx_rent_lead', 'vaxx_handle_rent_lead_submission' );

/**
 * Expõe ajax_url + nonce no JS global.
 */
function vaxx_localize_ajax_data() {
	wp_add_inline_script( 'vaxx-global', 'window.vaxxAjax = ' . wp_json_encode( array(
		'url'    => admin_url( 'admin-ajax.php' ),
		'nonce'  => wp_create_nonce( 'vaxx_rent_lead' ),
	) ) . ';', 'before' );
}
add_action( 'wp_enqueue_scripts', 'vaxx_localize_ajax_data', 20 );

/**
 * AJAX · Remove item do carrinho WC (sincroniza sessão) — chamado pelo mini-cart JS.
 * POST: action=vaxx_cart_remove, cart_key=...
 * Returns: { count: <int> }
 */
function vaxx_ajax_cart_remove() {
	if ( ! class_exists( 'WooCommerce' ) || ! WC()->cart ) {
		wp_send_json_error( array( 'message' => 'WC not available' ), 503 );
	}
	$key = isset( $_POST['cart_key'] ) ? sanitize_text_field( wp_unslash( $_POST['cart_key'] ) ) : '';
	if ( ! $key ) {
		wp_send_json_error( array( 'message' => 'Missing cart_key' ), 400 );
	}
	$ok = WC()->cart->remove_cart_item( $key );
	WC()->cart->calculate_totals();
	wp_send_json_success( array(
		'count'   => WC()->cart->get_cart_contents_count(),
		'removed' => (bool) $ok,
	) );
}
add_action( 'wp_ajax_vaxx_cart_remove',        'vaxx_ajax_cart_remove' );
add_action( 'wp_ajax_nopriv_vaxx_cart_remove', 'vaxx_ajax_cart_remove' );

/**
 * AJAX · Atualiza quantidade de um item (clamp min 1). Sincroniza sessão.
 * POST: action=vaxx_cart_set_qty, cart_key=..., qty=<int>
 * Returns: { count: <int>, qty: <int> }
 */
function vaxx_ajax_cart_set_qty() {
	if ( ! class_exists( 'WooCommerce' ) || ! WC()->cart ) {
		wp_send_json_error( array( 'message' => 'WC not available' ), 503 );
	}
	$key = isset( $_POST['cart_key'] ) ? sanitize_text_field( wp_unslash( $_POST['cart_key'] ) ) : '';
	$qty = isset( $_POST['qty'] ) ? max( 1, (int) $_POST['qty'] ) : 1;
	if ( ! $key ) {
		wp_send_json_error( array( 'message' => 'Missing cart_key' ), 400 );
	}
	WC()->cart->set_quantity( $key, $qty, true );
	WC()->cart->calculate_totals();
	wp_send_json_success( array(
		'count' => WC()->cart->get_cart_contents_count(),
		'qty'   => $qty,
	) );
}
add_action( 'wp_ajax_vaxx_cart_set_qty',        'vaxx_ajax_cart_set_qty' );
add_action( 'wp_ajax_nopriv_vaxx_cart_set_qty', 'vaxx_ajax_cart_set_qty' );
