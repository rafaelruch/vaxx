<?php
/**
 * VAXX · header.php (classico)
 *
 * Minimalista. O header visual VAXX ([vaxx_header]) e injetado via
 * wp_body_open hook em inc/shortcodes-shell.php. Aqui apenas:
 * doctype + <head> + abrir <body> + disparar wp_body_open.
 *
 * Evita o fallback do theme-compat (wp-includes/theme-compat/header.php)
 * que injetava <div id="page"><div id="header"><h1>Site Title</h1></div><hr>
 *
 * @package VAXX
 */
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>" />
<meta name="viewport" content="width=device-width, initial-scale=1" />

<!-- Fonts self-hosted · preload dos 2 weights criticos (body 400 + display 900) -->
<link rel="preload" as="font" type="font/woff2" href="<?php echo esc_url( get_template_directory_uri() ); ?>/assets/fonts/barlow-400.woff2" crossorigin>
<link rel="preload" as="font" type="font/woff2" href="<?php echo esc_url( get_template_directory_uri() ); ?>/assets/fonts/barlow-cond-900.woff2" crossorigin>

<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
