<?php
/**
 * VAXX · Taxonomias custom (product_line, muscle_group)
 *
 * @package VAXX
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Registra taxonomias hierárquicas para produtos WooCommerce.
 */
function vaxx_register_taxonomies() {

	// ───── LINHAS (Articulados, Bateria, Cardio, Acessórios) ─────
	register_taxonomy( 'product_line', array( 'product' ), array(
		'labels' => array(
			'name'              => __( 'Linhas', 'vaxx' ),
			'singular_name'     => __( 'Linha', 'vaxx' ),
			'search_items'      => __( 'Buscar linha', 'vaxx' ),
			'all_items'         => __( 'Todas as linhas', 'vaxx' ),
			'parent_item'       => __( 'Linha pai', 'vaxx' ),
			'parent_item_colon' => __( 'Linha pai:', 'vaxx' ),
			'edit_item'         => __( 'Editar linha', 'vaxx' ),
			'update_item'       => __( 'Atualizar linha', 'vaxx' ),
			'add_new_item'      => __( 'Nova linha', 'vaxx' ),
			'new_item_name'     => __( 'Nome da nova linha', 'vaxx' ),
			'menu_name'         => __( 'Linhas', 'vaxx' ),
		),
		'hierarchical'      => true,
		'public'            => true,
		'show_ui'           => true,
		'show_admin_column' => true,
		'show_in_menu'      => true,
		'show_in_nav_menus' => true,
		'show_in_rest'      => true,
		'rewrite'           => array( 'slug' => 'linha', 'with_front' => false ),
		'query_var'         => true,
	) );

	// ───── GRUPO MUSCULAR (Peito, Costas, Pernas, Ombros, Braços, Core) ─────
	register_taxonomy( 'muscle_group', array( 'product' ), array(
		'labels' => array(
			'name'              => __( 'Grupos musculares', 'vaxx' ),
			'singular_name'     => __( 'Grupo muscular', 'vaxx' ),
			'search_items'      => __( 'Buscar grupo', 'vaxx' ),
			'all_items'         => __( 'Todos os grupos', 'vaxx' ),
			'edit_item'         => __( 'Editar grupo', 'vaxx' ),
			'update_item'       => __( 'Atualizar grupo', 'vaxx' ),
			'add_new_item'      => __( 'Novo grupo muscular', 'vaxx' ),
			'new_item_name'     => __( 'Nome do grupo', 'vaxx' ),
			'menu_name'         => __( 'Grupos musculares', 'vaxx' ),
		),
		'hierarchical'      => false,
		'public'            => true,
		'show_ui'           => true,
		'show_admin_column' => false,
		'show_in_rest'      => true,
		'rewrite'           => array( 'slug' => 'grupo', 'with_front' => false ),
	) );
}
add_action( 'init', 'vaxx_register_taxonomies', 5 );
