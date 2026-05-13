<?php
/**
 * VAXX Theme · funções e setup
 *
 * @package VAXX
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'VAXX_THEME_VERSION', '1.0.0' );
define( 'VAXX_THEME_DIR', get_template_directory() );
define( 'VAXX_THEME_URI', get_template_directory_uri() );

/**
 * Setup básico do tema.
 */
function vaxx_theme_setup() {
	load_theme_textdomain( 'vaxx', VAXX_THEME_DIR . '/languages' );

	add_theme_support( 'wp-block-styles' );
	add_theme_support( 'editor-styles' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'align-wide' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'title-tag' );
	add_theme_support( 'html5', array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script' ) );
	add_theme_support( 'custom-logo', array(
		'height'      => 60,
		'width'       => 200,
		'flex-height' => true,
		'flex-width'  => true,
	) );
	add_theme_support( 'woocommerce' );

	// Image sizes custom VAXX
	add_image_size( 'vaxx-prod-thumb',   400, 400, true );
	add_image_size( 'vaxx-prod-card',    900, 675, true );
	add_image_size( 'vaxx-prod-gallery', 1600, 1200, true );
	add_image_size( 'vaxx-hero',         1920, 1080, true );
}
add_action( 'after_setup_theme', 'vaxx_theme_setup' );

/**
 * Enqueue styles + scripts.
 */
function vaxx_theme_assets() {
	$ver = defined( 'WP_DEBUG' ) && WP_DEBUG ? time() : VAXX_THEME_VERSION;

	// Fonts self-hosted — primeiro, pra @font-face rules estarem disponiveis
	// antes de qualquer CSS que referencie as families.
	wp_enqueue_style(
		'vaxx-fonts',
		VAXX_THEME_URI . '/assets/css/fonts.css',
		array(),
		$ver
	);

	// Global design tokens + componentes
	wp_enqueue_style(
		'vaxx-global',
		VAXX_THEME_URI . '/assets/css/global.css',
		array( 'vaxx-fonts' ),
		$ver
	);

	// Pages CSS consolidado — extraido dos <style> inline das paginas populadas.
	// Browser cacheia entre navegacoes. Evita re-parsear 80-130KB por pagina.
	if ( file_exists( VAXX_THEME_DIR . '/assets/css/pages.css' ) ) {
		wp_enqueue_style(
			'vaxx-pages',
			VAXX_THEME_URI . '/assets/css/pages.css',
			array( 'vaxx-global' ),
			$ver
		);
	}

	// Scripts globais (header scroll, mini-cart, etc.)
	wp_enqueue_script(
		'vaxx-global',
		VAXX_THEME_URI . '/assets/js/global.js',
		array(),
		$ver,
		true
	);

	// Header search overlay (abre/fecha/ESC/submit)
	wp_enqueue_script(
		'vaxx-header-search',
		VAXX_THEME_URI . '/assets/js/header-search.js',
		array(),
		$ver,
		true
	);

	// WooCommerce · CSS condicional por contexto
	if ( function_exists( 'is_woocommerce' ) ) {
		if ( is_shop() || is_product_taxonomy() || is_product_category() || is_tax( 'product_line' ) || is_tax( 'muscle_group' ) ) {
			wp_enqueue_style( 'vaxx-woo-archive', VAXX_THEME_URI . '/assets/css/woo-archive.css', array( 'vaxx-global' ), $ver );
		}
		if ( is_product() ) {
			wp_enqueue_style( 'vaxx-woo-single', VAXX_THEME_URI . '/assets/css/woo-single.css', array( 'vaxx-global' ), $ver );
		}
		if ( is_checkout() ) {
			wp_enqueue_style( 'vaxx-woo-checkout', VAXX_THEME_URI . '/assets/css/woo-checkout.css', array( 'vaxx-global' ), $ver );
		}
		if ( function_exists( 'is_account_page' ) && is_account_page() ) {
			wp_enqueue_style( 'vaxx-woo-myaccount', VAXX_THEME_URI . '/assets/css/woo-myaccount.css', array( 'vaxx-global' ), $ver );
			wp_enqueue_script( 'vaxx-woo-myaccount', VAXX_THEME_URI . '/assets/js/woo-myaccount.js', array(), $ver, true );
		}
	}

	// Página de orçamento (slug 'orcamento') — pode ou não ter WC ativo
	if ( is_page( defined( 'VAXX_ORCAMENTO_SLUG' ) ? VAXX_ORCAMENTO_SLUG : 'orcamento' ) ) {
		wp_enqueue_style( 'vaxx-orcamento', VAXX_THEME_URI . '/assets/css/orcamento.css', array( 'vaxx-global' ), $ver );
		wp_enqueue_script( 'vaxx-orcamento', VAXX_THEME_URI . '/assets/js/orcamento.js', array(), $ver, true );
	}
}
add_action( 'wp_enqueue_scripts', 'vaxx_theme_assets' );

/**
 * Editor assets (pra que tokens também apareçam no Gutenberg).
 */
function vaxx_editor_assets() {
	add_editor_style( 'assets/css/global.css' );
}
add_action( 'after_setup_theme', 'vaxx_editor_assets' );

/**
 * Inclui módulos do tema (CPTs, taxonomias, ACF, WooCommerce overrides).
 */
$vaxx_includes = array(
	'inc/theme-options.php',     // Customizer: WhatsApp, CNPJ, endereço, redes sociais
	'inc/shortcodes-shell.php',  // [vaxx_header], [vaxx_footer], [vaxx_mini_cart]
	'inc/woo-myaccount-helpers.php', // renderers dinâmicos das views de Pedidos e Aluguéis
	'inc/post-types.php',        // CPTs: rent_lead, case_study, depoimento
	'inc/taxonomies.php',        // product_line, muscle_group
	'inc/acf-fields.php',        // Field groups registrados via PHP
	'inc/woocommerce.php',       // Overrides + hooks
	'inc/block-patterns.php',    // Registro de patterns
	'inc/content-filters.php',   // Sanitiza ★ + injeta breadcrumb canônico + dequeue MP off-checkout
	'inc/orcamento.php',         // Fluxo de orçamento (substitui checkout de pagamento)
);
foreach ( $vaxx_includes as $file ) {
	$path = VAXX_THEME_DIR . '/' . $file;
	if ( file_exists( $path ) ) {
		require_once $path;
	}
}

/**
 * Helper global: recupera opção editável do tema (theme_mod).
 * Uso em templates pra strings globais (telefone, endereço, etc.)
 */
function vaxx_get_option( $key, $fallback = '' ) {
	$defaults = function_exists( 'vaxx_option_defaults' ) ? vaxx_option_defaults() : array();
	$default  = isset( $defaults[ $key ] ) ? $defaults[ $key ] : $fallback;
	$value    = get_theme_mod( 'vaxx_' . $key, $default );
	return $value !== '' ? $value : $default;
}
