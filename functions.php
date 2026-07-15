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
	// Card e galeria sem crop: as fotos de produto vêm em retrato e paisagem,
	// o enquadramento 1:1 fica a cargo do object-fit: contain no CSS.
	add_image_size( 'vaxx-prod-thumb',   400, 400, true );
	add_image_size( 'vaxx-prod-card',    900, 900, false );
	add_image_size( 'vaxx-prod-gallery', 1600, 1600, false );
	add_image_size( 'vaxx-hero',         1920, 1080, true );
}
add_action( 'after_setup_theme', 'vaxx_theme_setup' );

/**
 * Helper de versionamento: usa filemtime() do asset como query string,
 * garantindo cache-bust automático em qualquer mudança do arquivo. Cai
 * pro VAXX_THEME_VERSION se o arquivo não existir. Em WP_DEBUG retorna
 * time() pra atualizar a cada page load.
 */
function vaxx_asset_ver( $relpath ) {
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) return time();
	$abs = VAXX_THEME_DIR . '/' . ltrim( $relpath, '/' );
	$mtime = file_exists( $abs ) ? filemtime( $abs ) : false;
	return $mtime ? (string) $mtime : VAXX_THEME_VERSION;
}

/**
 * Enqueue styles + scripts.
 */
function vaxx_theme_assets() {
	// Fonts self-hosted — primeiro, pra @font-face rules estarem disponiveis
	// antes de qualquer CSS que referencie as families.
	wp_enqueue_style( 'vaxx-fonts',  VAXX_THEME_URI . '/assets/css/fonts.css',  array(),               vaxx_asset_ver( 'assets/css/fonts.css' ) );
	wp_enqueue_style( 'vaxx-global', VAXX_THEME_URI . '/assets/css/global.css', array( 'vaxx-fonts' ), vaxx_asset_ver( 'assets/css/global.css' ) );

	// Pages CSS consolidado — extraido dos <style> inline das paginas populadas.
	if ( file_exists( VAXX_THEME_DIR . '/assets/css/pages.css' ) ) {
		wp_enqueue_style( 'vaxx-pages', VAXX_THEME_URI . '/assets/css/pages.css', array( 'vaxx-global' ), vaxx_asset_ver( 'assets/css/pages.css' ) );
	}

	// Scripts globais (header scroll, mini-cart, etc.)
	wp_enqueue_script( 'vaxx-global',        VAXX_THEME_URI . '/assets/js/global.js',        array(), vaxx_asset_ver( 'assets/js/global.js' ),        true );
	wp_enqueue_script( 'vaxx-header-search', VAXX_THEME_URI . '/assets/js/header-search.js', array(), vaxx_asset_ver( 'assets/js/header-search.js' ), true );

	// WooCommerce · CSS condicional por contexto
	if ( function_exists( 'is_woocommerce' ) ) {
		if ( is_shop() || is_product_taxonomy() || is_product_category() || is_tax( 'product_line' ) || is_tax( 'muscle_group' ) ) {
			wp_enqueue_style( 'vaxx-woo-archive', VAXX_THEME_URI . '/assets/css/woo-archive.css', array( 'vaxx-global' ), vaxx_asset_ver( 'assets/css/woo-archive.css' ) );
		}
		if ( is_product() ) {
			wp_enqueue_style(  'vaxx-woo-single',  VAXX_THEME_URI . '/assets/css/woo-single.css', array( 'vaxx-global' ), vaxx_asset_ver( 'assets/css/woo-single.css' ) );
			wp_enqueue_script( 'vaxx-woo-gallery', VAXX_THEME_URI . '/assets/js/woo-gallery.js',  array(),                vaxx_asset_ver( 'assets/js/woo-gallery.js' ), true );
		}
		if ( is_cart() && file_exists( VAXX_THEME_DIR . '/assets/css/woo-cart.css' ) ) {
			wp_enqueue_style( 'vaxx-woo-cart', VAXX_THEME_URI . '/assets/css/woo-cart.css', array( 'vaxx-global' ), vaxx_asset_ver( 'assets/css/woo-cart.css' ) );
		}
		if ( is_checkout() ) {
			wp_enqueue_style( 'vaxx-woo-checkout', VAXX_THEME_URI . '/assets/css/woo-checkout.css', array( 'vaxx-global' ), vaxx_asset_ver( 'assets/css/woo-checkout.css' ) );
		}
		if ( function_exists( 'is_account_page' ) && is_account_page() ) {
			wp_enqueue_style(  'vaxx-woo-myaccount', VAXX_THEME_URI . '/assets/css/woo-myaccount.css', array( 'vaxx-global' ), vaxx_asset_ver( 'assets/css/woo-myaccount.css' ) );
			wp_enqueue_script( 'vaxx-woo-myaccount', VAXX_THEME_URI . '/assets/js/woo-myaccount.js',  array(),                vaxx_asset_ver( 'assets/js/woo-myaccount.js' ),  true );
		}
	}

	// Página de orçamento
	if ( is_page( defined( 'VAXX_ORCAMENTO_SLUG' ) ? VAXX_ORCAMENTO_SLUG : 'orcamento' ) ) {
		wp_enqueue_style(  'vaxx-orcamento', VAXX_THEME_URI . '/assets/css/orcamento.css', array( 'vaxx-global' ), vaxx_asset_ver( 'assets/css/orcamento.css' ) );
		wp_enqueue_script( 'vaxx-orcamento', VAXX_THEME_URI . '/assets/js/orcamento.js',  array(),                vaxx_asset_ver( 'assets/js/orcamento.js' ),  true );
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
	'inc/orcamento-uazapi.php',  // Disparo WhatsApp via Uazapi no submit do orçamento
	'inc/tracking.php',          // Google Ads (gtag) + Meta Pixel (fbq): tag base + conversões
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
