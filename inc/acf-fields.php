<?php
/**
 * VAXX · ACF Field Groups (registrados via PHP)
 *
 * Garante que os campos existam mesmo se o cliente não configurar
 * manualmente. Dados ACF são editáveis pelo admin.
 *
 * @package VAXX
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Field group: Specs de produto (Woo product CPT).
 */
function vaxx_register_acf_fields() {
	if ( ! function_exists( 'acf_add_local_field_group' ) ) return;

	// ═══════════════════════════════════════════════════════════
	// GRUPO: Specs do produto (PDP)
	// ═══════════════════════════════════════════════════════════
	acf_add_local_field_group( array(
		'key'    => 'group_vaxx_product_specs',
		'title'  => '📐 Specs Técnicas VAXX',
		'fields' => array(
			array( 'key' => 'fld_vaxx_dimensoes',     'label' => 'Dimensões (L × A × P)', 'name' => 'vaxx_dimensoes',     'type' => 'text', 'instructions' => 'Ex: 160 × 125 × 140 cm' ),
			array( 'key' => 'fld_vaxx_peso',          'label' => 'Peso do equipamento',   'name' => 'vaxx_peso',          'type' => 'text', 'instructions' => 'Ex: 148 kg' ),
			array( 'key' => 'fld_vaxx_carga_max',     'label' => 'Carga máxima',          'name' => 'vaxx_carga_max',     'type' => 'text', 'instructions' => 'Ex: 250 kg em anilhas' ),
			array( 'key' => 'fld_vaxx_regulagem',     'label' => 'Regulagem de altura',   'name' => 'vaxx_regulagem',     'type' => 'text', 'instructions' => 'Ex: 1,55 – 1,95 m' ),
			array( 'key' => 'fld_vaxx_material',      'label' => 'Material da estrutura', 'name' => 'vaxx_material',      'type' => 'text', 'instructions' => 'Ex: Aço SAE 1020 · espessura 3,0 mm' ),
			array( 'key' => 'fld_vaxx_acabamento',    'label' => 'Acabamento',             'name' => 'vaxx_acabamento',    'type' => 'text', 'instructions' => 'Ex: Pintura epóxi preta fosca' ),
			array( 'key' => 'fld_vaxx_estofado',      'label' => 'Estofado',               'name' => 'vaxx_estofado',      'type' => 'text', 'instructions' => 'Ex: Courvim VAXX · resistência 60 kg/cm²' ),
			array( 'key' => 'fld_vaxx_sistema_carga', 'label' => 'Sistema de carga',        'name' => 'vaxx_sistema_carga', 'type' => 'text', 'instructions' => 'Ex: Plate-loaded · eixo 50 mm olímpico' ),
			array( 'key' => 'fld_vaxx_normas',        'label' => 'Normas aplicadas',        'name' => 'vaxx_normas',        'type' => 'text', 'instructions' => 'Ex: ABNT NBR 16535 + EN 957' ),
			array(
				'key'   => 'fld_vaxx_aluguel_preco',
				'label' => 'Preço de aluguel mensal (opcional)',
				'name'  => 'vaxx_aluguel_preco',
				'type'  => 'number',
				'prepend' => 'R$',
				'append'  => '/mês',
				'instructions' => 'Deixe vazio se aluguel não disponível para este produto.',
			),
			array(
				'key'   => 'fld_vaxx_regulagem_real',
				'label' => 'Tem regulagem real 1,55–1,95?',
				'name'  => 'vaxx_regulagem_real',
				'type'  => 'true_false',
				'ui'    => 1,
				'default_value' => 0,
			),
		),
		'location' => array(
			array( array( 'param' => 'post_type', 'operator' => '==', 'value' => 'product' ) ),
		),
		'position' => 'normal',
		'style'    => 'default',
		'menu_order' => 5,
	) );

	// ═══════════════════════════════════════════════════════════
	// GRUPO: Rent Lead (dados do lead de aluguel)
	// ═══════════════════════════════════════════════════════════
	acf_add_local_field_group( array(
		'key'    => 'group_vaxx_rent_lead',
		'title'  => '📋 Dados do Lead',
		'fields' => array(
			array( 'key' => 'fld_rl_produto',       'label' => 'Produto de interesse', 'name' => 'produto',      'type' => 'text', 'required' => 1 ),
			array( 'key' => 'fld_rl_nome',          'label' => 'Nome',                 'name' => 'nome',         'type' => 'text', 'required' => 1 ),
			array( 'key' => 'fld_rl_telefone',      'label' => 'Telefone/WhatsApp',    'name' => 'telefone',     'type' => 'text', 'required' => 1 ),
			array( 'key' => 'fld_rl_email',         'label' => 'E-mail',               'name' => 'email',        'type' => 'email', 'required' => 1 ),
			array(
				'key'   => 'fld_rl_status',
				'label' => 'Status do lead',
				'name'  => 'status_lead',
				'type'  => 'select',
				'choices' => array(
					'Aguardando contato' => 'Aguardando contato',
					'Em negociação'      => 'Em negociação',
					'Contrato ativo'     => 'Contrato ativo',
					'Finalizado'         => 'Finalizado',
					'Descartado'         => 'Descartado',
				),
				'default_value' => 'Aguardando contato',
			),
			array( 'key' => 'fld_rl_obs', 'label' => 'Observações internas', 'name' => 'observacoes', 'type' => 'textarea', 'rows' => 4 ),
		),
		'location' => array(
			array( array( 'param' => 'post_type', 'operator' => '==', 'value' => 'rent_lead' ) ),
		),
	) );

	// ═══════════════════════════════════════════════════════════
	// GRUPO: Depoimento
	// ═══════════════════════════════════════════════════════════
	acf_add_local_field_group( array(
		'key'    => 'group_vaxx_depoimento',
		'title'  => '✍️ Dados do Depoimento',
		'fields' => array(
			array( 'key' => 'fld_dep_categoria', 'label' => 'Categoria', 'name' => 'categoria', 'type' => 'select',
				'choices' => array(
					'academias'     => 'Academia',
					'condominios'   => 'Condomínio',
					'construtoras'  => 'Construtora',
					'empresas'      => 'Empresa',
					'pf'            => 'Pessoa Física · Home Gym',
					'revendedores'  => 'Revendedor',
				),
			),
			array( 'key' => 'fld_dep_autor_cargo', 'label' => 'Cargo do autor', 'name' => 'autor_cargo', 'type' => 'text' ),
			array( 'key' => 'fld_dep_localizacao', 'label' => 'Localização', 'name' => 'localizacao', 'type' => 'text', 'instructions' => 'Ex: Joinville / SC' ),
			array( 'key' => 'fld_dep_video_url', 'label' => 'URL do vídeo (opcional)', 'name' => 'video_url', 'type' => 'url' ),
			array( 'key' => 'fld_dep_estrelas', 'label' => 'Estrelas', 'name' => 'estrelas', 'type' => 'number', 'default_value' => 5, 'min' => 1, 'max' => 5 ),
		),
		'location' => array(
			array( array( 'param' => 'post_type', 'operator' => '==', 'value' => 'depoimento' ) ),
		),
	) );

	// ═══════════════════════════════════════════════════════════
	// GRUPO: Case Study
	// ═══════════════════════════════════════════════════════════
	acf_add_local_field_group( array(
		'key'    => 'group_vaxx_case',
		'title'  => '🏆 Dados do Case',
		'fields' => array(
			array( 'key' => 'fld_case_localizacao', 'label' => 'Localização', 'name' => 'localizacao', 'type' => 'text', 'instructions' => 'Ex: Joinville / SC' ),
			array( 'key' => 'fld_case_autor',       'label' => 'Nome do autor da citação', 'name' => 'autor_nome',  'type' => 'text' ),
			array( 'key' => 'fld_case_autor_cargo', 'label' => 'Cargo do autor',           'name' => 'autor_cargo', 'type' => 'text' ),
			array( 'key' => 'fld_case_meta',        'label' => 'Metadados do case',        'name' => 'meta_linha',  'type' => 'text', 'instructions' => 'Ex: 45 máquinas instaladas em 3 meses' ),
			array( 'key' => 'fld_case_quote',       'label' => 'Citação',                  'name' => 'citacao',     'type' => 'textarea', 'rows' => 4 ),
			array( 'key' => 'fld_case_persona',     'label' => 'Persona alvo',             'name' => 'persona',     'type' => 'select',
				'choices' => array(
					'academias'    => 'Academias',
					'condominios'  => 'Condomínios',
					'construtoras' => 'Construtoras',
					'empresas'     => 'Empresas',
					'pf'           => 'Pessoa Física',
					'revendedores' => 'Revendedores',
				),
			),
		),
		'location' => array(
			array( array( 'param' => 'post_type', 'operator' => '==', 'value' => 'case_study' ) ),
		),
	) );
}
add_action( 'acf/init', 'vaxx_register_acf_fields' );
