<?php
/**
 * Plugin Name:       Recomenda Links
 * Plugin URI:        https://projetowebstudio.com.br
 * Description:       Gerenciador de links de afiliado centralizado. Crie links do tipo seusite.com/recomenda/apelido que redirecionam para o link de afiliado real. Se o afiliado mudar, você altera o destino em um só lugar e aplica em todos os artigos. Inclui contagem de cliques, relatórios por período e por artigo, tipos de ferramenta com filtro, verificador de links quebrados, troca em massa, importação/exportação CSV e shortcode com botão ou card de produto (imagem, descrição e botão).
 * Version:           1.4.0
 * Author:            Marcelo Andrade
 * Author URI:        https://projetowebstudio.com.br
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       recomenda-links
 */

// Bloqueia acesso direto ao arquivo.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Versão do plugin (usada para rodar a atualização automática do banco).
define( 'RECOMENDA_VERSION', '1.4.0' );

// Slug base usado nas URLs: seusite.com/recomenda/apelido
if ( ! defined( 'RECOMENDA_BASE' ) ) {
	define( 'RECOMENDA_BASE', 'recomenda' );
}

/**
 * IMPORTANTE — compatibilidade com links já cadastrados:
 * o tipo de conteúdo "recomenda_link" e os campos "_recomenda_target" (destino)
 * e "_recomenda_clicks" (total de cliques) são os mesmos desde a versão 1.0.0.
 * Não renomeie nenhum deles, ou os links existentes deixam de ser encontrados.
 */

/**
 * ============================================================
 * 1. REGISTRO DO TIPO DE CONTEÚDO (onde os links ficam salvos)
 * ============================================================
 */
function recomenda_register_cpt() {
	$labels = array(
		'name'               => 'Links de Afiliado',
		'singular_name'      => 'Link de Afiliado',
		'menu_name'          => 'Recomenda Links',
		'add_new'            => 'Adicionar novo',
		'add_new_item'       => 'Adicionar novo link',
		'edit_item'          => 'Editar link',
		'new_item'           => 'Novo link',
		'view_item'          => 'Ver link',
		'search_items'       => 'Buscar links',
		'not_found'          => 'Nenhum link encontrado',
		'not_found_in_trash' => 'Nenhum link na lixeira',
		'all_items'          => 'Todos os links',
		'featured_image'        => 'Imagem do produto',
		'set_featured_image'    => 'Definir imagem do produto',
		'remove_featured_image' => 'Remover imagem do produto',
		'use_featured_image'    => 'Usar como imagem do produto',
	);

	$args = array(
		'labels'              => $labels,
		'public'              => false,      // Não cria página pública própria.
		'publicly_queryable'  => false,
		'show_ui'             => true,       // Aparece no painel admin.
		'show_in_menu'        => true,
		'menu_icon'           => 'dashicons-admin-links',
		'menu_position'       => 30,
		'supports'            => array( 'title', 'thumbnail' ), // Título (nome do produto) e imagem do produto.
		'capability_type'     => 'post',
		'has_archive'         => false,
		'rewrite'             => false,      // Nós criamos a regra de URL manualmente.
		'query_var'           => false,
	);

	register_post_type( 'recomenda_link', $args );
}
add_action( 'init', 'recomenda_register_cpt' );

// Garante o quadro "Imagem do produto" mesmo em temas sem imagem destacada.
function recomenda_thumbnail_support() {
	add_theme_support( 'post-thumbnails', array( 'recomenda_link' ) );
}
add_action( 'after_setup_theme', 'recomenda_thumbnail_support', 99 );

/**
 * Imagem do link: a "Imagem do produto" enviada para a biblioteca de mídia
 * tem prioridade; se não houver, usa a URL de imagem informada.
 * Retorna a tag <img> pronta ou ''.
 */
function recomenda_image_html( $link_id, $size = 'medium', $class = '' ) {
	$alt = get_the_title( $link_id );
	if ( has_post_thumbnail( $link_id ) ) {
		return get_the_post_thumbnail( $link_id, $size, array(
			'class'   => $class,
			'alt'     => $alt,
			'loading' => 'lazy',
		) );
	}
	$url = get_post_meta( $link_id, '_recomenda_imagem_url', true );
	if ( $url ) {
		return '<img src="' . esc_url( $url ) . '" alt="' . esc_attr( $alt ) . '" class="' . esc_attr( $class ) . '" loading="lazy" decoding="async">';
	}
	return '';
}

// URL da imagem do link (para a exportação CSV).
function recomenda_image_url( $link_id ) {
	if ( has_post_thumbnail( $link_id ) ) {
		return (string) get_the_post_thumbnail_url( $link_id, 'full' );
	}
	return (string) get_post_meta( $link_id, '_recomenda_imagem_url', true );
}

/**
 * Tipos de link (ex.: Serras, Brocas, Plainas). Funciona como as categorias
 * dos posts: aceita subtipos, aparece como coluna e como filtro na lista.
 * Os tipos são só internos: não criam páginas públicas no site.
 */
function recomenda_register_tipo() {
	register_taxonomy( 'recomenda_tipo', 'recomenda_link', array(
		'labels'             => array(
			'name'              => 'Tipos',
			'singular_name'     => 'Tipo',
			'menu_name'         => 'Tipos',
			'all_items'         => 'Todos os tipos',
			'edit_item'         => 'Editar tipo',
			'view_item'         => 'Ver tipo',
			'update_item'       => 'Atualizar tipo',
			'add_new_item'      => 'Adicionar novo tipo',
			'new_item_name'     => 'Nome do novo tipo',
			'parent_item'       => 'Tipo pai',
			'parent_item_colon' => 'Tipo pai:',
			'search_items'      => 'Buscar tipos',
			'not_found'         => 'Nenhum tipo encontrado',
			'no_terms'          => 'Sem tipo',
			'back_to_items'     => '&larr; Voltar para os tipos',
		),
		'hierarchical'       => true,
		'public'             => false,
		'publicly_queryable' => false,
		'show_ui'            => true,
		'show_in_menu'       => true,
		'show_admin_column'  => true,  // Coluna "Tipos" na lista, clicável para filtrar.
		'show_in_quick_edit' => true,  // Permite definir o tipo na edição rápida e em massa.
		'show_in_nav_menus'  => false,
		'show_tagcloud'      => false,
		'show_in_rest'       => false,
		'rewrite'            => false,
		'query_var'          => 'recomenda_tipo', // Usado só no painel, para filtrar a lista.
	) );
}
add_action( 'init', 'recomenda_register_tipo' );

// Filtro "Todos os tipos" acima da lista de links.
function recomenda_tipo_filter_dropdown( $post_type ) {
	if ( 'recomenda_link' !== $post_type ) {
		return;
	}
	wp_dropdown_categories( array(
		'taxonomy'        => 'recomenda_tipo',
		'name'            => 'recomenda_tipo',
		'value_field'     => 'slug',
		'selected'        => isset( $_GET['recomenda_tipo'] ) ? sanitize_title( wp_unslash( $_GET['recomenda_tipo'] ) ) : '',
		'show_option_all' => 'Todos os tipos',
		'hide_empty'      => false,
		'hierarchical'    => true,
		'show_count'      => true,
		'orderby'         => 'name',
	) );
}
add_action( 'restrict_manage_posts', 'recomenda_tipo_filter_dropdown' );

// Aplica tipos a partir de um texto "Serras | Brocas" (usado na importação CSV).
function recomenda_set_tipos( $link_id, $raw ) {
	$names = array_filter( array_map( 'trim', preg_split( '/[|,]/', (string) $raw ) ), 'strlen' );
	if ( $names ) {
		wp_set_object_terms( $link_id, array_values( $names ), 'recomenda_tipo' );
	}
}

// Nomes dos tipos de um link, separados por " | " (usado na exportação CSV).
function recomenda_get_tipos_text( $link_id ) {
	$terms = get_the_terms( $link_id, 'recomenda_tipo' );
	if ( ! $terms || is_wp_error( $terms ) ) {
		return '';
	}
	return implode( ' | ', wp_list_pluck( $terms, 'name' ) );
}

/**
 * ============================================================
 * 2. REGRA DE URL: /recomenda/apelido -> handler de redirect
 * ============================================================
 */
function recomenda_add_rewrite() {
	add_rewrite_tag( '%recomenda_slug%', '([^&/]+)' );
	add_rewrite_rule(
		'^' . RECOMENDA_BASE . '/([^/]+)/?$',
		'index.php?recomenda_slug=$matches[1]',
		'top'
	);
}
add_action( 'init', 'recomenda_add_rewrite' );

// Garante que o WordPress reconheça a variável recomenda_slug.
function recomenda_query_vars( $vars ) {
	$vars[] = 'recomenda_slug';
	return $vars;
}
add_filter( 'query_vars', 'recomenda_query_vars' );

/**
 * ============================================================
 * 3. INSTALAÇÃO / ATUALIZAÇÃO AUTOMÁTICA
 * ============================================================
 *
 * Roda uma única vez quando a versão muda (inclusive ao substituir o arquivo
 * do plugin sem desativá-lo). Só CRIA coisas novas — nunca apaga nem altera
 * os links e cliques já cadastrados.
 */
function recomenda_stats_table() {
	global $wpdb;
	return $wpdb->prefix . 'recomenda_stats';
}

function recomenda_install() {
	global $wpdb;
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';

	// Tabela de estatísticas: um registro por link + artigo de origem + dia.
	$table   = recomenda_stats_table();
	$charset = $wpdb->get_charset_collate();
	dbDelta( "CREATE TABLE {$table} (
		link_id bigint(20) unsigned NOT NULL,
		post_id bigint(20) unsigned NOT NULL DEFAULT 0,
		dia date NOT NULL,
		clicks int(10) unsigned NOT NULL DEFAULT 0,
		PRIMARY KEY  (link_id,post_id,dia),
		KEY dia (dia)
	) {$charset};" );

	// Links que nunca foram clicados não têm o campo de cliques salvo, e por
	// isso sumiam da lista ao ordenar por cliques. Grava 0 para eles.
	$ids = $wpdb->get_col( $wpdb->prepare(
		"SELECT p.ID FROM {$wpdb->posts} p
		LEFT JOIN {$wpdb->postmeta} m ON m.post_id = p.ID AND m.meta_key = %s
		WHERE p.post_type = %s AND m.meta_id IS NULL",
		'_recomenda_clicks',
		'recomenda_link'
	) );
	foreach ( $ids as $id ) {
		add_post_meta( (int) $id, '_recomenda_clicks', 0, true );
	}

	// Links da Amazon marcados como quebrados por engano (versão 1.2.0) passam a
	// "não verificável". Não faz nenhuma requisição externa.
	$marked = get_posts( array(
		'post_type'      => 'recomenda_link',
		'post_status'    => 'any',
		'posts_per_page' => -1,
		'fields'         => 'ids',
		'meta_key'       => '_recomenda_health',
	) );
	foreach ( $marked as $id ) {
		if ( recomenda_is_uncheckable( get_post_meta( $id, '_recomenda_target', true ) ) ) {
			recomenda_check_link( $id );
		}
	}

	if ( ! wp_next_scheduled( 'recomenda_check_links' ) ) {
		wp_schedule_event( time() + HOUR_IN_SECONDS, 'recomenda_weekly', 'recomenda_check_links' );
	}

	flush_rewrite_rules();
	update_option( 'recomenda_version', RECOMENDA_VERSION );
}

function recomenda_maybe_upgrade() {
	if ( get_option( 'recomenda_version' ) !== RECOMENDA_VERSION ) {
		recomenda_install();
	}
}
// Prioridade alta: roda depois do registro do tipo de conteúdo e da regra de URL.
add_action( 'init', 'recomenda_maybe_upgrade', 99 );

/**
 * ============================================================
 * 4. FUNÇÕES AUXILIARES
 * ============================================================
 */

// Busca um link publicado pelo apelido (com cache por requisição).
function recomenda_get_link( $slug ) {
	static $cache = array();

	if ( ! array_key_exists( $slug, $cache ) ) {
		$posts = get_posts( array(
			'name'           => $slug,
			'post_type'      => 'recomenda_link',
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			'no_found_rows'  => true,
		) );
		$cache[ $slug ] = $posts ? $posts[0] : null;
	}

	return $cache[ $slug ];
}

// Endereço público do link, no formato seusite.com/recomenda/apelido.
function recomenda_pretty_url( $post ) {
	return home_url( '/' . RECOMENDA_BASE . '/' . $post->post_name );
}

// Apelido legível (decodifica acentos que o WordPress guarda codificados).
function recomenda_display_slug( $post ) {
	return urldecode( $post->post_name );
}

// Data (Y-m-d) no fuso do site, N dias atrás.
function recomenda_local_date( $days_ago = 0 ) {
	$ts = time() - ( (int) $days_ago * DAY_IN_SECONDS );
	if ( function_exists( 'wp_date' ) ) {
		return wp_date( 'Y-m-d', $ts );
	}
	return gmdate( 'Y-m-d', $ts + (int) ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS ) );
}

// Host sem "www." para comparar domínios.
function recomenda_host( $url ) {
	$host = wp_parse_url( $url, PHP_URL_HOST );
	return $host ? preg_replace( '/^www\./i', '', strtolower( $host ) ) : '';
}

/**
 * Valida a URL de destino.
 * Retorna a URL limpa, '' se vier vazia, ou false se for inválida.
 */
function recomenda_validate_url( $url ) {
	$url = trim( (string) $url );
	if ( '' === $url ) {
		return '';
	}

	$clean = esc_url_raw( $url, array( 'http', 'https' ) );
	if ( ! $clean || ! preg_match( '#^https?://#i', $clean ) ) {
		return false;
	}

	$host = wp_parse_url( $clean, PHP_URL_HOST );
	if ( ! $host || false === strpos( $host, '.' ) ) {
		return false;
	}

	return $clean;
}

// Outros links (fora da lixeira) que já usam exatamente este destino.
function recomenda_find_duplicates( $target, $exclude_id = 0 ) {
	global $wpdb;
	if ( '' === $target ) {
		return array();
	}
	return array_map( 'intval', $wpdb->get_col( $wpdb->prepare(
		"SELECT p.ID FROM {$wpdb->posts} p
		INNER JOIN {$wpdb->postmeta} m ON m.post_id = p.ID AND m.meta_key = %s
		WHERE p.post_type = %s AND p.post_status NOT IN ('trash','auto-draft')
		AND m.meta_value = %s AND p.ID <> %d",
		'_recomenda_target',
		'recomenda_link',
		$target,
		(int) $exclude_id
	) ) );
}

// Avisos no painel que sobrevivem ao redirecionamento após salvar.
function recomenda_add_notice( $type, $message ) {
	$key     = 'recomenda_notices_' . get_current_user_id();
	$notices = get_transient( $key );
	$notices = is_array( $notices ) ? $notices : array();
	$notices[] = array( 'type' => $type, 'message' => $message );
	set_transient( $key, $notices, 5 * MINUTE_IN_SECONDS );
}

function recomenda_show_notices() {
	$key     = 'recomenda_notices_' . get_current_user_id();
	$notices = get_transient( $key );
	if ( ! is_array( $notices ) || ! $notices ) {
		return;
	}
	delete_transient( $key );
	foreach ( $notices as $n ) {
		printf(
			'<div class="notice notice-%s is-dismissible"><p>%s</p></div>',
			esc_attr( $n['type'] ),
			wp_kses_post( $n['message'] )
		);
	}
}
add_action( 'admin_notices', 'recomenda_show_notices' );

/**
 * ============================================================
 * 5. HANDLER: intercepta a URL, conta o clique e redireciona
 * ============================================================
 */
function recomenda_handle_redirect() {
	$slug = get_query_var( 'recomenda_slug' );

	if ( empty( $slug ) ) {
		return; // Não é uma URL /recomenda/... , segue o fluxo normal.
	}

	$link = recomenda_get_link( sanitize_title( $slug ) );

	if ( ! $link ) {
		status_header( 404 );
		wp_die( 'Link não encontrado.', 'Link não encontrado', array( 'response' => 404 ) );
	}

	$target = get_post_meta( $link->ID, '_recomenda_target', true );

	if ( empty( $target ) ) {
		status_header( 404 );
		wp_die( 'Este link ainda não tem um destino configurado.', 'Destino ausente', array( 'response' => 404 ) );
	}

	if ( recomenda_should_count() ) {
		recomenda_register_click( $link->ID, recomenda_source_post_id() );
	}

	// Redirecionamento 302 (temporário) — ideal para afiliado, já que o destino pode mudar.
	nocache_headers();
	wp_redirect( esc_url_raw( $target ), 302 );
	exit;
}
add_action( 'template_redirect', 'recomenda_handle_redirect' );

/**
 * Decide se o acesso conta como clique.
 * Não conta: robôs e pré-visualizações (Google, WhatsApp, Facebook...),
 * pré-carregamento do navegador, requisições HEAD e quem edita o site logado.
 * Outros plugins podem ajustar pelo filtro "recomenda_should_count".
 */
function recomenda_should_count() {
	$count = true;

	$method = isset( $_SERVER['REQUEST_METHOD'] ) ? strtoupper( (string) $_SERVER['REQUEST_METHOD'] ) : 'GET';
	$ua     = isset( $_SERVER['HTTP_USER_AGENT'] ) ? (string) wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) : '';

	if ( 'HEAD' === $method ) {
		$count = false;
	} elseif ( is_user_logged_in() && current_user_can( 'edit_posts' ) ) {
		$count = false;
	} elseif ( '' === trim( $ua ) || preg_match( '/bot|crawl|spider|slurp|scan|preview|monitor|facebookexternalhit|whatsapp|telegram|discord|slack|embedly|curl|wget|python|java\/|go-http|headless|lighthouse|pingdom|uptime/i', $ua ) ) {
		$count = false;
	} else {
		foreach ( array( 'HTTP_PURPOSE', 'HTTP_SEC_PURPOSE', 'HTTP_X_PURPOSE', 'HTTP_X_MOZ' ) as $header ) {
			if ( ! empty( $_SERVER[ $header ] ) && preg_match( '/prefetch|prerender|preview/i', (string) $_SERVER[ $header ] ) ) {
				$count = false;
				break;
			}
		}
	}

	return (bool) apply_filters( 'recomenda_should_count', $count );
}

// Descobre de qual artigo do próprio site veio o clique (0 = direto/externo/não identificado).
function recomenda_source_post_id() {
	$ref = wp_get_raw_referer();
	if ( ! $ref || recomenda_host( $ref ) !== recomenda_host( home_url() ) ) {
		return 0;
	}
	$post_id = url_to_postid( $ref );
	return $post_id > 0 ? (int) $post_id : 0;
}

// Soma o clique no total do link e na estatística do dia (operações atômicas).
function recomenda_register_click( $link_id, $source_id ) {
	global $wpdb;

	$updated = $wpdb->query( $wpdb->prepare(
		"UPDATE {$wpdb->postmeta} SET meta_value = CAST(meta_value AS UNSIGNED) + 1 WHERE post_id = %d AND meta_key = %s",
		$link_id,
		'_recomenda_clicks'
	) );
	if ( ! $updated && ! add_post_meta( $link_id, '_recomenda_clicks', 1, true ) ) {
		// Outro acesso simultâneo criou o campo neste meio-tempo: soma nele.
		$wpdb->query( $wpdb->prepare(
			"UPDATE {$wpdb->postmeta} SET meta_value = CAST(meta_value AS UNSIGNED) + 1 WHERE post_id = %d AND meta_key = %s",
			$link_id,
			'_recomenda_clicks'
		) );
	}
	wp_cache_delete( $link_id, 'post_meta' );

	$table = recomenda_stats_table();
	$wpdb->query( $wpdb->prepare(
		"INSERT INTO {$table} (link_id, post_id, dia, clicks) VALUES (%d, %d, %s, 1)
		ON DUPLICATE KEY UPDATE clicks = clicks + 1",
		$link_id,
		$source_id,
		recomenda_local_date()
	) );
}

// Ao excluir um link definitivamente, remove também suas estatísticas diárias.
function recomenda_delete_stats( $post_id ) {
	global $wpdb;
	if ( 'recomenda_link' !== get_post_type( $post_id ) ) {
		return;
	}
	$wpdb->delete( recomenda_stats_table(), array( 'link_id' => (int) $post_id ), array( '%d' ) );
}
add_action( 'before_delete_post', 'recomenda_delete_stats' );

/**
 * ============================================================
 * 6. CAMPO "URL DE DESTINO" na tela de edição do link
 * ============================================================
 */
function recomenda_add_meta_box() {
	add_meta_box(
		'recomenda_target_box',
		'Destino do link de afiliado',
		'recomenda_render_meta_box',
		'recomenda_link',
		'normal',
		'high'
	);
	add_meta_box(
		'recomenda_stats_box',
		'Cliques nos últimos 30 dias',
		'recomenda_render_stats_box',
		'recomenda_link',
		'normal',
		'default'
	);
}
add_action( 'add_meta_boxes', 'recomenda_add_meta_box' );

function recomenda_render_meta_box( $post ) {
	wp_nonce_field( 'recomenda_save_meta', 'recomenda_meta_nonce' );

	$target = get_post_meta( $post->ID, '_recomenda_target', true );
	$clicks = (int) get_post_meta( $post->ID, '_recomenda_clicks', true );
	?>
	<p>
		<label for="recomenda_target"><strong>URL de destino (link de afiliado real):</strong></label><br>
		<input type="url" id="recomenda_target" name="recomenda_target"
			value="<?php echo esc_attr( $target ); ?>"
			placeholder="https://loja.com/produto?ref=seucodigo"
			style="width:100%; max-width:640px;" />
	</p>

	<?php $descricao = get_post_meta( $post->ID, '_recomenda_descricao', true ); ?>
	<p>
		<label for="recomenda_descricao"><strong>Descrição curta do produto:</strong></label><br>
		<textarea id="recomenda_descricao" name="recomenda_descricao" rows="3"
			style="width:100%; max-width:640px;"
			placeholder="Ex.: Plaina 710 W com regulagem de profundidade, ideal para desbaste de madeira."><?php echo esc_textarea( $descricao ); ?></textarea><br>
		<span style="color:#666;">Aparece no card do produto nos artigos e na lista de links. Recomendado: até 200 caracteres.</span>
	</p>

	<?php $imagem_url = get_post_meta( $post->ID, '_recomenda_imagem_url', true ); ?>
	<p>
		<label for="recomenda_imagem_url"><strong>URL da imagem (opcional):</strong></label><br>
		<input type="url" id="recomenda_imagem_url" name="recomenda_imagem_url"
			value="<?php echo esc_attr( $imagem_url ); ?>"
			placeholder="https://..."
			style="width:100%; max-width:640px;" /><br>
		<span style="color:#666;">Use o quadro <strong>Imagem do produto</strong> (ao lado) para enviar uma foto sua, ou cole aqui o endereço de uma imagem. Se as duas existirem, vale a Imagem do produto.
		Para produtos da Amazon, use o link de imagem gerado pela barra SiteStripe da própria Amazon.</span>
	</p>
	<?php $img = recomenda_image_html( $post->ID, 'thumbnail' ); ?>
	<?php if ( $img ) : ?>
		<p class="recomenda-img-preview"><?php echo $img; // phpcs:ignore WordPress.Security.EscapeOutput -- já escapado. ?></p>
	<?php endif; ?>

	<?php if ( $post->post_name ) : ?>
		<?php $pretty = recomenda_pretty_url( $post ); ?>
	<p>
		<strong>Seu link para usar nos artigos:</strong><br>
		<code><?php echo esc_html( urldecode( $pretty ) ); ?></code>
		<?php recomenda_copy_buttons( $post ); ?>
	</p>
	<p>
		<strong>Cliques registrados (total):</strong> <?php echo esc_html( $clicks ); ?>
		&nbsp; <label style="font-weight:normal;">
			<input type="checkbox" name="recomenda_reset_clicks" value="1" /> zerar contador ao salvar
		</label>
	</p>
	<?php if ( recomenda_checker_enabled() ) : ?>
	<p>
		<strong>Situação do destino:</strong> <?php echo recomenda_health_badge( $post->ID ); // phpcs:ignore WordPress.Security.EscapeOutput -- já escapado. ?>
		&nbsp; <a href="<?php echo esc_url( recomenda_check_url( $post->ID ) ); ?>">verificar agora</a>
	</p>
	<?php endif; ?>
	<?php else : ?>
		<p><em>Salve o link uma vez para gerar o endereço /<?php echo esc_html( RECOMENDA_BASE ); ?>/...</em></p>
	<?php endif; ?>
	<p style="color:#666;">
		Dica: o "Título" do link (campo lá em cima) é só o nome interno. O apelido que aparece na URL
		vem do <em>slug</em> — você pode ajustá-lo em "Editar" ao lado do título.
	</p>
	<?php
}

function recomenda_render_stats_box( $post ) {
	global $wpdb;
	$table = recomenda_stats_table();
	$since = recomenda_local_date( 29 );

	$total = (int) $wpdb->get_var( $wpdb->prepare(
		"SELECT SUM(clicks) FROM {$table} WHERE link_id = %d AND dia >= %s",
		$post->ID,
		$since
	) );

	$rows = $wpdb->get_results( $wpdb->prepare(
		"SELECT post_id, SUM(clicks) AS total FROM {$table}
		WHERE link_id = %d AND dia >= %s GROUP BY post_id ORDER BY total DESC LIMIT 5",
		$post->ID,
		$since
	) );

	echo '<p><strong>' . esc_html( number_format_i18n( $total ) ) . '</strong> cliques nos últimos 30 dias.</p>';

	if ( $rows ) {
		echo '<p><strong>Artigos que mais geraram cliques neste link:</strong></p><ol>';
		foreach ( $rows as $row ) {
			echo '<li>' . recomenda_source_label( (int) $row->post_id ) . ' — ' . esc_html( number_format_i18n( (int) $row->total ) ) . '</li>'; // phpcs:ignore WordPress.Security.EscapeOutput -- já escapado.
		}
		echo '</ol>';
	}

	$report = add_query_arg(
		array( 'post_type' => 'recomenda_link', 'page' => 'recomenda-relatorios', 'link' => $post->ID ),
		admin_url( 'edit.php' )
	);
	echo '<p><a href="' . esc_url( $report ) . '">Ver relatório completo deste link &raquo;</a></p>';
}

/**
 * ============================================================
 * 7. SALVAR o destino e (opcional) zerar cliques
 * ============================================================
 */
function recomenda_save_meta( $post_id ) {
	if ( ! isset( $_POST['recomenda_meta_nonce'] ) ||
		! wp_verify_nonce( $_POST['recomenda_meta_nonce'], 'recomenda_save_meta' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	if ( isset( $_POST['recomenda_target'] ) ) {
		$old    = get_post_meta( $post_id, '_recomenda_target', true );
		$target = recomenda_validate_url( wp_unslash( $_POST['recomenda_target'] ) );

		if ( false === $target ) {
			recomenda_add_notice( 'error', '<strong>URL de destino inválida.</strong> Use um endereço completo começando com http:// ou https://. O destino anterior foi mantido.' );
		} else {
			update_post_meta( $post_id, '_recomenda_target', $target );

			if ( $target !== $old ) {
				recomenda_clear_health( $post_id ); // Destino novo: precisa ser verificado de novo.
			}

			$dupes = recomenda_find_duplicates( $target, $post_id );
			if ( $dupes ) {
				$names = array();
				foreach ( $dupes as $id ) {
					$names[] = '<a href="' . esc_url( get_edit_post_link( $id ) ) . '">' . esc_html( get_the_title( $id ) ) . '</a>';
				}
				recomenda_add_notice( 'warning', '<strong>Atenção:</strong> este destino já é usado por: ' . implode( ', ', $names ) . '. O link foi salvo mesmo assim.' );
			}

			if ( $target && recomenda_host( $target ) === recomenda_host( home_url() ) &&
				0 === strpos( (string) wp_parse_url( $target, PHP_URL_PATH ), '/' . RECOMENDA_BASE . '/' ) ) {
				recomenda_add_notice( 'warning', '<strong>Atenção:</strong> o destino aponta para outro link /' . esc_html( RECOMENDA_BASE ) . '/ do próprio site. Confira para não criar um redirecionamento em círculo.' );
			}
		}
	}

	if ( isset( $_POST['recomenda_descricao'] ) ) {
		update_post_meta( $post_id, '_recomenda_descricao', sanitize_textarea_field( wp_unslash( $_POST['recomenda_descricao'] ) ) );
	}

	if ( isset( $_POST['recomenda_imagem_url'] ) ) {
		$imagem = recomenda_validate_url( wp_unslash( $_POST['recomenda_imagem_url'] ) );
		if ( false === $imagem ) {
			recomenda_add_notice( 'error', '<strong>URL da imagem inválida.</strong> Use um endereço completo começando com http:// ou https://. A imagem anterior foi mantida.' );
		} else {
			update_post_meta( $post_id, '_recomenda_imagem_url', $imagem );
		}
	}

	if ( ! empty( $_POST['recomenda_reset_clicks'] ) ) {
		update_post_meta( $post_id, '_recomenda_clicks', 0 );
	}
}
add_action( 'save_post_recomenda_link', 'recomenda_save_meta' );

// Todo link nasce com o contador em 0 (necessário para a ordenação por cliques).
function recomenda_ensure_clicks_meta( $post_id ) {
	add_post_meta( $post_id, '_recomenda_clicks', 0, true );
}
add_action( 'save_post_recomenda_link', 'recomenda_ensure_clicks_meta', 5 );

/**
 * ============================================================
 * 8. COLUNAS na lista de links: URL amigável, destino, cliques e situação
 * ============================================================
 */
function recomenda_columns( $columns ) {
	$new = array();
	foreach ( $columns as $key => $label ) {
		if ( 'title' === $key ) {
			$new['recomenda_img'] = '<span class="screen-reader-text">Imagem</span>';
		}
		$new[ $key ] = $label;
		if ( 'title' === $key ) {
			$new['recomenda_desc']   = 'Descrição';
			$new['recomenda_url']    = 'Link (/' . RECOMENDA_BASE . '/)';
			$new['recomenda_target'] = 'Destino';
			$new['recomenda_clicks'] = 'Cliques';
			if ( recomenda_checker_enabled() ) {
				$new['recomenda_health'] = 'Situação';
			}
		}
	}
	return $new;
}
add_filter( 'manage_recomenda_link_posts_columns', 'recomenda_columns' );

function recomenda_render_columns( $column, $post_id ) {
	if ( 'recomenda_img' === $column ) {
		$img = recomenda_image_html( $post_id, 'thumbnail', 'recomenda-thumb' );
		echo $img ? $img : '<span class="recomenda-thumb recomenda-thumb--vazio" aria-hidden="true"></span>'; // phpcs:ignore WordPress.Security.EscapeOutput -- já escapado.
	}

	if ( 'recomenda_desc' === $column ) {
		$descricao = get_post_meta( $post_id, '_recomenda_descricao', true );
		echo $descricao
			? '<span style="font-size:12px;">' . esc_html( wp_html_excerpt( $descricao, 120, '…' ) ) . '</span>'
			: '<span style="color:#a7aaad;">—</span>';
	}

	if ( 'recomenda_url' === $column ) {
		$post   = get_post( $post_id );
		$pretty = recomenda_pretty_url( $post );
		echo '<code>' . esc_html( '/' . RECOMENDA_BASE . '/' . recomenda_display_slug( $post ) ) . '</code>';
		echo '<br><a href="' . esc_url( $pretty ) . '" target="_blank" rel="noopener" style="font-size:11px;">testar &raquo;</a> ';
		recomenda_copy_buttons( $post );
	}

	if ( 'recomenda_target' === $column ) {
		$target = get_post_meta( $post_id, '_recomenda_target', true );
		if ( $target ) {
			// URL completa, quebrando a linha quando for longa (sem cortar).
			echo '<a href="' . esc_url( $target ) . '" target="_blank" rel="noopener nofollow" title="' . esc_attr( $target ) . '" style="word-break:break-all;font-size:12px;">'
				. esc_html( $target ) . '</a>';
		} else {
			echo '<span style="color:#c00;">— sem destino —</span>';
		}
	}

	if ( 'recomenda_clicks' === $column ) {
		echo '<strong>' . (int) get_post_meta( $post_id, '_recomenda_clicks', true ) . '</strong>';
	}

	if ( 'recomenda_health' === $column ) {
		echo recomenda_health_badge( $post_id ); // phpcs:ignore WordPress.Security.EscapeOutput -- já escapado.
	}
}
add_action( 'manage_recomenda_link_posts_custom_column', 'recomenda_render_columns', 10, 2 );

// Permite ordenar pela coluna de cliques.
function recomenda_sortable_columns( $columns ) {
	$columns['recomenda_clicks'] = 'recomenda_clicks';
	return $columns;
}
add_filter( 'manage_edit-recomenda_link_sortable_columns', 'recomenda_sortable_columns' );

function recomenda_admin_list_query( $query ) {
	if ( ! is_admin() || ! $query->is_main_query() || 'recomenda_link' !== $query->get( 'post_type' ) ) {
		return;
	}
	if ( 'recomenda_clicks' === $query->get( 'orderby' ) ) {
		$query->set( 'meta_key', '_recomenda_clicks' );
		$query->set( 'orderby', 'meta_value_num' );
	}
	if ( isset( $_GET['recomenda_saude'] ) && 'broken' === $_GET['recomenda_saude'] ) {
		$query->set( 'meta_query', array(
			array(
				'key'   => '_recomenda_health',
				'value' => 'broken',
			),
		) );
	}
}
add_action( 'pre_get_posts', 'recomenda_admin_list_query' );

// Atalho "Com problema (N)" no topo da lista.
function recomenda_list_views( $views ) {
	$count = recomenda_count_broken();
	if ( $count ) {
		$url     = add_query_arg( array( 'post_type' => 'recomenda_link', 'recomenda_saude' => 'broken' ), admin_url( 'edit.php' ) );
		$current = ( isset( $_GET['recomenda_saude'] ) && 'broken' === $_GET['recomenda_saude'] ) ? ' class="current" aria-current="page"' : '';
		$views['recomenda_broken'] = '<a href="' . esc_url( $url ) . '"' . $current . ' style="color:#b32d2e;">Com problema <span class="count">(' . (int) $count . ')</span></a>';
	}
	return $views;
}
add_filter( 'views_edit-recomenda_link', 'recomenda_list_views' );

// Ação "Verificar destino" ao passar o mouse sobre o link na lista.
function recomenda_row_actions( $actions, $post ) {
	if ( 'recomenda_link' === $post->post_type && recomenda_checker_enabled() && current_user_can( 'edit_post', $post->ID ) ) {
		$actions['recomenda_check'] = '<a href="' . esc_url( recomenda_check_url( $post->ID ) ) . '">Verificar destino</a>';
	}
	return $actions;
}
add_filter( 'post_row_actions', 'recomenda_row_actions', 10, 2 );

// Aviso no topo da lista quando há destinos quebrados.
function recomenda_broken_notice() {
	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	if ( ! $screen || 'edit-recomenda_link' !== $screen->id || isset( $_GET['recomenda_saude'] ) ) {
		return;
	}
	$count = recomenda_count_broken();
	if ( $count ) {
		$url = add_query_arg( array( 'post_type' => 'recomenda_link', 'recomenda_saude' => 'broken' ), admin_url( 'edit.php' ) );
		printf(
			'<div class="notice notice-error"><p><strong>%s</strong> <a href="%s">Ver links com problema &raquo;</a></p></div>',
			esc_html( sprintf( 1 === $count ? '%d link está com o destino quebrado.' : '%d links estão com o destino quebrado.', $count ) ),
			esc_url( $url )
		);
	}
}
add_action( 'admin_notices', 'recomenda_broken_notice' );

/**
 * ============================================================
 * 9. BOTÕES "COPIAR LINK" E "COPIAR SHORTCODE"
 * ============================================================
 */
function recomenda_copy_buttons( $post ) {
	$shortcode = '[recomenda id="' . $post->post_name . '"]Ver oferta[/recomenda]';
	$card      = '[recomenda id="' . $post->post_name . '" estilo="card"]Ver na loja[/recomenda]';
	printf(
		'<span class="recomenda-copy-group"><button type="button" class="button button-small recomenda-copy" data-copy="%s">copiar link</button> <button type="button" class="button button-small recomenda-copy" data-copy="%s">copiar shortcode</button> <button type="button" class="button button-small recomenda-copy" data-copy="%s">copiar card</button></span>',
		esc_attr( urldecode( recomenda_pretty_url( $post ) ) ),
		esc_attr( $shortcode ),
		esc_attr( $card )
	);
}

function recomenda_admin_footer_js() {
	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	if ( ! $screen || 'recomenda_link' !== $screen->post_type ) {
		return;
	}
	?>
	<style>
		.recomenda-copy-group{white-space:nowrap}.recomenda-copy-group .button-small{margin-top:3px}
		.column-recomenda_img{width:56px}
		.recomenda-thumb{display:block;width:48px;height:48px;object-fit:contain;background:#fff;border:1px solid #dcdcde;border-radius:4px}
		.recomenda-thumb--vazio{background:#f6f7f7}
		.column-recomenda_desc{width:18%}
		.recomenda-img-preview img{max-width:120px;height:auto;border:1px solid #dcdcde;border-radius:4px;background:#fff}
	</style>
	<script>
	(function () {
		function fallback(text) {
			var t = document.createElement('textarea');
			t.value = text;
			t.style.position = 'fixed';
			t.style.opacity = '0';
			document.body.appendChild(t);
			t.select();
			try { document.execCommand('copy'); } catch (e) {}
			document.body.removeChild(t);
		}
		document.addEventListener('click', function (e) {
			var btn = e.target.closest ? e.target.closest('.recomenda-copy') : null;
			if (!btn) { return; }
			e.preventDefault();
			var text = btn.getAttribute('data-copy');
			var label = btn.textContent;
			var done = function () {
				btn.textContent = 'copiado!';
				setTimeout(function () { btn.textContent = label; }, 1500);
			};
			if (navigator.clipboard && window.isSecureContext) {
				navigator.clipboard.writeText(text).then(done, function () { fallback(text); done(); });
			} else {
				fallback(text);
				done();
			}
		});
	})();
	</script>
	<?php
}
add_action( 'admin_footer', 'recomenda_admin_footer_js' );

/**
 * ============================================================
 * 10. VERIFICADOR DE LINKS QUEBRADOS
 * ============================================================
 *
 * Uma vez por semana, verifica o destino de todos os links publicados, em
 * lotes pequenos para não pesar no servidor. Situações:
 *   ok      -> destino respondeu normalmente
 *   broken  -> página não existe (404/410) ou o site não respondeu duas vezes seguidas
 *   warn    -> a loja bloqueou a verificação automática; não significa que o link esteja quebrado
 *   skip    -> loja que responde 404 a qualquer acesso automático (Amazon): não é verificada
 *
 * O verificador pode ser desligado em Recomenda Links -> Ferramentas.
 */
function recomenda_checker_enabled() {
	return '0' !== get_option( 'recomenda_checker_enabled', '1' );
}

/**
 * Destinos que não dá para verificar automaticamente: a Amazon (amazon.com.br,
 * amzn.to, link.amazon, a.co...) responde 404 a servidores mesmo quando o link
 * funciona no navegador. Outras lojas podem ser incluídas pelo filtro
 * "recomenda_skip_check".
 */
function recomenda_is_uncheckable( $url ) {
	$host = recomenda_host( $url );
	$skip = '' !== $host && (bool) preg_match( '/(^|\.)(amazon(\.[a-z]{2,3}){0,2}|amzn\.[a-z]{2,3}|a\.co)$/', $host );
	return (bool) apply_filters( 'recomenda_skip_check', $skip, $url );
}

function recomenda_cron_schedules( $schedules ) {
	$schedules['recomenda_weekly'] = array(
		'interval' => WEEK_IN_SECONDS,
		'display'  => 'Uma vez por semana (Recomenda Links)',
	);
	return $schedules;
}
add_filter( 'cron_schedules', 'recomenda_cron_schedules' );

function recomenda_check_link( $link_id ) {
	$target = get_post_meta( $link_id, '_recomenda_target', true );
	$now    = time();

	if ( ! $target ) {
		update_post_meta( $link_id, '_recomenda_health', 'broken' );
		update_post_meta( $link_id, '_recomenda_health_detail', 'sem destino' );
		update_post_meta( $link_id, '_recomenda_checked', $now );
		return 'broken';
	}

	if ( recomenda_is_uncheckable( $target ) ) {
		update_post_meta( $link_id, '_recomenda_health', 'skip' );
		update_post_meta( $link_id, '_recomenda_health_detail', 'a loja bloqueia verificação automática' );
		update_post_meta( $link_id, '_recomenda_checked', $now );
		return 'skip';
	}

	$args = array(
		'timeout'     => 8,
		'redirection' => 5,
		'user-agent'  => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0 Safari/537.36',
	);

	$response = wp_remote_head( $target, $args );
	$code     = is_wp_error( $response ) ? 0 : (int) wp_remote_retrieve_response_code( $response );

	// Muitas lojas não aceitam HEAD: tenta de novo com GET (baixando só o começo da página).
	// (Alguns servidores também respondem 404 só para HEAD.)
	if ( 0 === $code || in_array( $code, array( 403, 404, 405, 410 ), true ) || $code >= 500 ) {
		$args['limit_response_size'] = 8192;
		$response = wp_remote_get( $target, $args );
		$code     = is_wp_error( $response ) ? 0 : (int) wp_remote_retrieve_response_code( $response );
	}

	$previous = get_post_meta( $link_id, '_recomenda_health_detail', true );

	if ( is_wp_error( $response ) ) {
		$detail = 'sem resposta: ' . $response->get_error_message();
		// Uma falha isolada pode ser instabilidade; duas seguidas marcam como quebrado.
		$health = ( 0 === strpos( (string) $previous, 'sem resposta' ) ) ? 'broken' : 'warn';
	} elseif ( $code >= 200 && $code < 400 ) {
		$health = 'ok';
		$detail = (string) $code;
	} elseif ( 404 === $code || 410 === $code ) {
		$health = 'broken';
		$detail = (string) $code;
	} else {
		$health = 'warn';
		$detail = (string) $code;
	}

	update_post_meta( $link_id, '_recomenda_health', $health );
	update_post_meta( $link_id, '_recomenda_health_detail', $detail );
	update_post_meta( $link_id, '_recomenda_checked', $now );

	return $health;
}

function recomenda_clear_health( $link_id ) {
	delete_post_meta( $link_id, '_recomenda_health' );
	delete_post_meta( $link_id, '_recomenda_health_detail' );
	delete_post_meta( $link_id, '_recomenda_checked' );
}

// Início do ciclo semanal.
function recomenda_cron_start() {
	if ( ! recomenda_checker_enabled() ) {
		return;
	}
	update_option( 'recomenda_check_cycle', time(), false );
	recomenda_cron_batch();
}
add_action( 'recomenda_check_links', 'recomenda_cron_start' );

// Verifica um lote e agenda o próximo até terminar.
function recomenda_cron_batch() {
	$cycle = (int) get_option( 'recomenda_check_cycle' );
	$batch = 5;

	$ids = get_posts( array(
		'post_type'      => 'recomenda_link',
		'post_status'    => 'publish',
		'posts_per_page' => $batch,
		'fields'         => 'ids',
		'no_found_rows'  => true,
		'meta_query'     => array(
			'relation' => 'OR',
			array(
				'key'     => '_recomenda_checked',
				'compare' => 'NOT EXISTS',
			),
			array(
				'key'     => '_recomenda_checked',
				'value'   => $cycle,
				'compare' => '<',
				'type'    => 'NUMERIC',
			),
		),
	) );

	if ( function_exists( 'set_time_limit' ) ) {
		@set_time_limit( 120 ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
	}

	foreach ( $ids as $id ) {
		recomenda_check_link( $id );
	}

	if ( count( $ids ) === $batch && ! wp_next_scheduled( 'recomenda_check_links_batch' ) ) {
		wp_schedule_single_event( time() + MINUTE_IN_SECONDS, 'recomenda_check_links_batch' );
	}
}
add_action( 'recomenda_check_links_batch', 'recomenda_cron_batch' );

function recomenda_count_broken() {
	static $count = null;
	if ( ! recomenda_checker_enabled() ) {
		return 0;
	}
	if ( null === $count ) {
		global $wpdb;
		$count = (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->posts} p
			INNER JOIN {$wpdb->postmeta} m ON m.post_id = p.ID AND m.meta_key = %s
			WHERE p.post_type = %s AND p.post_status = 'publish' AND m.meta_value = 'broken'",
			'_recomenda_health',
			'recomenda_link'
		) );
	}
	return $count;
}

function recomenda_health_badge( $link_id ) {
	$health  = get_post_meta( $link_id, '_recomenda_health', true );
	$detail  = get_post_meta( $link_id, '_recomenda_health_detail', true );
	$checked = (int) get_post_meta( $link_id, '_recomenda_checked', true );
	$when    = $checked ? ' em ' . date_i18n( 'd/m/Y', $checked + (int) ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS ) ) : '';

	if ( 'ok' === $health ) {
		return '<span style="color:#00a32a;">&#10003; OK</span><br><small style="color:#646970;">verificado' . esc_html( $when ) . '</small>';
	}
	if ( 'broken' === $health ) {
		return '<span style="color:#b32d2e;font-weight:600;">&#10007; Quebrado</span><br><small style="color:#646970;">' . esc_html( $detail . $when ) . '</small>';
	}
	if ( 'skip' === $health ) {
		return '<span style="color:#646970;">— não verificável</span><br><small style="color:#646970;" title="A Amazon responde erro a verificações automáticas mesmo quando o link funciona. Teste o link manualmente.">' . esc_html( $detail ) . '</small>';
	}
	if ( 'warn' === $health ) {
		return '<span style="color:#996800;">! Não confirmado</span><br><small style="color:#646970;" title="A loja bloqueou a verificação automática ou não respondeu. Teste o link manualmente.">' . esc_html( $detail . $when ) . '</small>';
	}
	return '<span style="color:#646970;">— ainda não verificado</span>';
}

function recomenda_check_url( $link_id ) {
	return wp_nonce_url(
		add_query_arg( array( 'action' => 'recomenda_check', 'post' => (int) $link_id ), admin_url( 'admin-post.php' ) ),
		'recomenda_check_' . (int) $link_id
	);
}

// Verificação manual de um link.
function recomenda_handle_check() {
	$link_id = isset( $_GET['post'] ) ? absint( $_GET['post'] ) : 0;
	check_admin_referer( 'recomenda_check_' . $link_id );
	if ( ! $link_id || 'recomenda_link' !== get_post_type( $link_id ) || ! current_user_can( 'edit_post', $link_id ) ) {
		wp_die( 'Sem permissão.' );
	}

	$health = recomenda_check_link( $link_id );
	$labels = array(
		'ok'     => array( 'success', 'Destino verificado: está funcionando.' ),
		'broken' => array( 'error', 'Destino verificado: o link está quebrado.' ),
		'warn'   => array( 'warning', 'A loja não permitiu a verificação automática. Teste o link manualmente.' ),
		'skip'   => array( 'info', 'Este destino é da Amazon, que bloqueia verificações automáticas. Ele não é verificado; teste o link manualmente.' ),
	);
	recomenda_add_notice( $labels[ $health ][0], '<strong>' . esc_html( get_the_title( $link_id ) ) . ':</strong> ' . $labels[ $health ][1] );

	$back = wp_get_referer();
	wp_safe_redirect( $back ? $back : admin_url( 'edit.php?post_type=recomenda_link' ) );
	exit;
}
add_action( 'admin_post_recomenda_check', 'recomenda_handle_check' );

/**
 * ============================================================
 * 11. SHORTCODE: [recomenda] — link ou botão personalizável
 * ============================================================
 *
 * Exemplos de uso dentro dos artigos:
 *
 *   Link simples de texto:
 *     [recomenda id="furadeira-bosch"]Ver preço na loja[/recomenda]
 *
 *   Botão com o estilo padrão do plugin:
 *     [recomenda id="furadeira-bosch" estilo="botao"]Comprar agora[/recomenda]
 *
 *   Botão usando as classes de botão do SEU tema (identidade visual do site):
 *     [recomenda id="furadeira-bosch" estilo="botao" classe="wp-block-button__link"]Comprar[/recomenda]
 *
 *   Card do produto (imagem, título, descrição e botão):
 *     [recomenda id="furadeira-bosch" estilo="card"]Ver na loja[/recomenda]
 *
 * Atributos:
 *   id        -> apelido do link (obrigatório)
 *   estilo    -> "link" (padrão), "botao" ou "card"
 *   titulo    -> (card) título exibido; padrão: o título do link
 *   descricao -> (card) descrição exibida; padrão: a descrição do link
 *   imagem    -> (card) "off" para esconder a imagem
 *   classe  -> classes CSS extras, separadas por espaço (aplique as do seu tema)
 *   rel     -> "on" (padrão, adiciona nofollow sponsored) ou "off"
 *   target  -> "_blank" (padrão, nova aba) ou "" para abrir na mesma aba
 */
function recomenda_shortcode( $atts, $content = null ) {
	$atts = shortcode_atts(
		array(
			'id'     => '',
			'estilo'    => 'link',
			'classe'    => '',
			'titulo'    => '',
			'descricao' => '',
			'imagem'    => 'on',
			'rel'    => 'on',
			'target' => '_blank',
		),
		$atts,
		'recomenda'
	);

	$slug = sanitize_title( $atts['id'] );
	if ( empty( $slug ) ) {
		return '';
	}

	$texto = ! empty( $content ) ? do_shortcode( $content ) : 'Ver oferta';

	// Link inexistente: o visitante vê só o texto; quem edita o site vê um aviso.
	$link = recomenda_get_link( $slug );
	if ( ! $link || ! get_post_meta( $link->ID, '_recomenda_target', true ) ) {
		$out = wp_kses_post( $texto );
		if ( current_user_can( 'edit_posts' ) ) {
			$motivo = $link ? 'está sem URL de destino' : 'não existe ou não está publicado';
			$out   .= ' <span style="color:#b32d2e;font-weight:600;">[Recomenda Links: o link "' . esc_html( $atts['id'] ) . '" ' . $motivo . ' — aviso visível só para editores]</span>';
		}
		return $out;
	}

	$url = home_url( '/' . RECOMENDA_BASE . '/' . $slug );

	// Monta a lista de classes: base + estilo escolhido + classes do tema.
	$classes = array( 'recomenda-link' );
	if ( 'botao' === $atts['estilo'] ) {
		$classes[] = 'recomenda-btn';
		wp_enqueue_style( 'recomenda-links' ); // Carrega o CSS do botão só quando é usado.
	}
	if ( ! empty( $atts['classe'] ) ) {
		foreach ( explode( ' ', $atts['classe'] ) as $c ) {
			$c = sanitize_html_class( $c );
			if ( $c ) {
				$classes[] = $c;
			}
		}
	}

	$rel_values = array();
	if ( 'off' !== strtolower( $atts['rel'] ) ) {
		$rel_values[] = 'nofollow';
		$rel_values[] = 'sponsored';
	}
	if ( '_blank' === $atts['target'] ) {
		$rel_values[] = 'noopener';
	}
	$rel    = $rel_values ? ' rel="' . esc_attr( implode( ' ', $rel_values ) ) . '"' : '';
	$target = $atts['target'] ? ' target="' . esc_attr( $atts['target'] ) . '"' : '';

	if ( 'card' === $atts['estilo'] ) {
		return recomenda_card_html( $link, $atts, $url, $classes, $target . $rel, $content );
	}

	return sprintf(
		'<a href="%s" class="%s"%s%s>%s</a>',
		esc_url( $url ),
		esc_attr( implode( ' ', $classes ) ),
		$target,
		$rel,
		wp_kses_post( $texto )
	);
}

/**
 * Card do produto: imagem, título, descrição e botão. Todos os links do card
 * passam pelo /recomenda/apelido, então os cliques continuam sendo contados.
 *
 * Personalize em Aparência > Personalizar > CSS adicional, por exemplo:
 *   .recomenda-card{ --recomenda-card-borda:#e11d48; --recomenda-card-radius:4px; }
 */
function recomenda_card_html( $link, $atts, $url, $classes, $link_attrs, $content ) {
	wp_enqueue_style( 'recomenda-links' );

	$titulo    = '' !== $atts['titulo'] ? $atts['titulo'] : get_the_title( $link );
	$descricao = '' !== $atts['descricao'] ? $atts['descricao'] : get_post_meta( $link->ID, '_recomenda_descricao', true );
	$botao     = ! empty( $content ) ? do_shortcode( $content ) : 'Ver na loja';
	$imagem    = 'off' !== strtolower( $atts['imagem'] ) ? recomenda_image_html( $link->ID, 'medium' ) : '';

	// O botão do card usa o estilo .recomenda-btn, a menos que o tema forneça as classes.
	$classes   = array_diff( $classes, array( 'recomenda-link' ) );
	$btn_class = array_merge( array( 'recomenda-link', 'recomenda-card__btn' ), $atts['classe'] ? array() : array( 'recomenda-btn' ), $classes );

	$href = esc_url( $url );
	$out  = '<div class="recomenda-card' . ( $imagem ? '' : ' recomenda-card--sem-imagem' ) . '">';
	if ( $imagem ) {
		$out .= '<a class="recomenda-card__img" href="' . $href . '"' . $link_attrs . ' tabindex="-1" aria-hidden="true">' . $imagem . '</a>';
	}
	$out .= '<div class="recomenda-card__corpo">';
	$out .= '<p class="recomenda-card__titulo"><a href="' . $href . '"' . $link_attrs . '>' . esc_html( $titulo ) . '</a></p>';
	if ( $descricao ) {
		$out .= '<p class="recomenda-card__desc">' . esc_html( $descricao ) . '</p>';
	}
	$out .= '<a href="' . $href . '" class="' . esc_attr( implode( ' ', array_unique( $btn_class ) ) ) . '"' . $link_attrs . '>' . wp_kses_post( $botao ) . '</a>';
	$out .= '</div></div>';

	return $out;
}
add_shortcode( 'recomenda', 'recomenda_shortcode' );

/**
 * Estilo padrão do botão (.recomenda-btn).
 *
 * Totalmente personalizável para a identidade visual de qualquer site:
 *   - Troque as cores rapidamente sobrescrevendo as variáveis CSS em
 *     Aparência > Personalizar > CSS adicional, por exemplo:
 *
 *       .recomenda-btn{
 *           --recomenda-bg:#e11d48;      (cor de fundo)
 *           --recomenda-cor:#ffffff;     (cor do texto)
 *           --recomenda-radius:4px;      (arredondamento)
 *       }
 *
 *   - Ou ignore este estilo e use as classes de botão do seu tema pelo
 *     atributo classe="" no shortcode.
 *
 * O CSS só é carregado nas páginas que usam o shortcode.
 */
function recomenda_button_css() {
	return '.recomenda-btn{'
		. '--recomenda-bg:#2563eb;'
		. '--recomenda-cor:#ffffff;'
		. '--recomenda-radius:8px;'
		. '--recomenda-padding:12px 24px;'
		. 'display:inline-block;'
		. 'padding:var(--recomenda-padding);'
		. 'background:var(--recomenda-bg);'
		. 'color:var(--recomenda-cor);'
		. 'border-radius:var(--recomenda-radius);'
		. 'text-decoration:none;'
		. 'font-weight:600;'
		. 'line-height:1.2;'
		. 'transition:opacity .15s ease;'
		. '}'
		. '.recomenda-btn:hover{opacity:.88;color:var(--recomenda-cor);}'
		. '.recomenda-card{'
		. '--recomenda-card-borda:rgba(0,0,0,.12);'
		. '--recomenda-card-fundo:transparent;'
		. '--recomenda-card-radius:12px;'
		. '--recomenda-card-img:160px;'
		. 'display:flex;flex-wrap:wrap;gap:20px;align-items:center;'
		. 'margin:24px 0;padding:20px;'
		. 'border:1px solid var(--recomenda-card-borda);'
		. 'border-radius:var(--recomenda-card-radius);'
		. 'background:var(--recomenda-card-fundo);'
		. '}'
		. '.recomenda-card__img{flex:0 0 var(--recomenda-card-img);max-width:100%;display:block;text-align:center;}'
		. '.recomenda-card__img img{display:block;width:100%;height:var(--recomenda-card-img);object-fit:contain;margin:0 auto;border-radius:8px;background:#fff;}'
		. '.recomenda-card__corpo{flex:1 1 240px;min-width:0;}'
		. '.recomenda-card__titulo{margin:0 0 8px;font-size:1.15em;font-weight:700;line-height:1.3;}'
		. '.recomenda-card__titulo a{color:inherit;text-decoration:none;}'
		. '.recomenda-card__titulo a:hover{text-decoration:underline;}'
		. '.recomenda-card__desc{margin:0 0 16px;opacity:.85;line-height:1.5;}'
		. '.recomenda-card .recomenda-card__btn{margin:0;}'
		. '@media (max-width:480px){.recomenda-card{padding:16px;}.recomenda-card__img{flex-basis:100%;}}';
}

function recomenda_register_assets() {
	wp_register_style( 'recomenda-links', false, array(), RECOMENDA_VERSION );
	wp_add_inline_style( 'recomenda-links', recomenda_button_css() );

	// Se o artigo usa o shortcode, já carrega no <head> (evita o botão "piscar" sem estilo).
	if ( is_singular() ) {
		$post = get_queried_object();
		if ( $post && isset( $post->post_content ) && has_shortcode( $post->post_content, 'recomenda' ) ) {
			wp_enqueue_style( 'recomenda-links' );
		}
	}
}
add_action( 'wp_enqueue_scripts', 'recomenda_register_assets' );

/**
 * ============================================================
 * 12. RELATÓRIOS E FERRAMENTAS (submenus do Recomenda Links)
 * ============================================================
 */
function recomenda_admin_menu() {
	add_submenu_page(
		'edit.php?post_type=recomenda_link',
		'Relatórios de cliques',
		'Relatórios',
		'edit_posts',
		'recomenda-relatorios',
		'recomenda_render_reports'
	);
	add_submenu_page(
		'edit.php?post_type=recomenda_link',
		'Ferramentas do Recomenda Links',
		'Ferramentas',
		'edit_others_posts',
		'recomenda-ferramentas',
		'recomenda_render_tools'
	);
}
add_action( 'admin_menu', 'recomenda_admin_menu' );

// Nome do artigo de origem para exibir nos relatórios.
function recomenda_source_label( $post_id ) {
	if ( ! $post_id ) {
		return '<em>Direto, externo ou página sem artigo</em>';
	}
	$post = get_post( $post_id );
	if ( ! $post ) {
		return '<em>Artigo removido (#' . (int) $post_id . ')</em>';
	}
	$title = get_the_title( $post ) ? get_the_title( $post ) : '(sem título)';
	return '<a href="' . esc_url( get_permalink( $post ) ) . '" target="_blank" rel="noopener">' . esc_html( $title ) . '</a>';
}

function recomenda_render_reports() {
	global $wpdb;
	$table   = recomenda_stats_table();
	$allowed = array( 7, 30, 90 );
	$dias    = isset( $_GET['dias'] ) ? absint( $_GET['dias'] ) : 30;
	$dias    = in_array( $dias, $allowed, true ) ? $dias : 30;
	$link_id = isset( $_GET['link'] ) ? absint( $_GET['link'] ) : 0;
	$tipo_id = isset( $_GET['tipo'] ) ? absint( $_GET['tipo'] ) : 0;
	$since   = recomenda_local_date( $dias - 1 );
	$base    = add_query_arg( array( 'post_type' => 'recomenda_link', 'page' => 'recomenda-relatorios' ), admin_url( 'edit.php' ) );

	$where = $wpdb->prepare( 'dia >= %s', $since );
	if ( $link_id ) {
		$where .= $wpdb->prepare( ' AND link_id = %d', $link_id );
	}
	if ( $tipo_id ) {
		// Links do tipo escolhido, incluindo os subtipos.
		$tipo_ids = array_merge( array( $tipo_id ), (array) get_term_children( $tipo_id, 'recomenda_tipo' ) );
		$in_tipo  = get_objects_in_term( $tipo_ids, 'recomenda_tipo' );
		$in_tipo  = is_wp_error( $in_tipo ) ? array() : array_map( 'intval', $in_tipo );
		$where   .= $in_tipo ? ' AND link_id IN (' . implode( ',', $in_tipo ) . ')' : ' AND 1 = 0';
	}

	// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $where já preparado acima.
	$daily_rows = $wpdb->get_results( "SELECT dia, SUM(clicks) AS total FROM {$table} WHERE {$where} GROUP BY dia", OBJECT_K );
	$top_links  = $wpdb->get_results( "SELECT link_id, SUM(clicks) AS total FROM {$table} WHERE {$where} GROUP BY link_id ORDER BY total DESC LIMIT 50" );
	$top_posts  = $wpdb->get_results( "SELECT post_id, SUM(clicks) AS total FROM {$table} WHERE {$where} GROUP BY post_id ORDER BY total DESC LIMIT 50" );
	$all_links  = $wpdb->get_results( "SELECT link_id, SUM(clicks) AS total FROM {$table} WHERE {$where} GROUP BY link_id" );
	// phpcs:enable

	// Soma os cliques por tipo (um link com dois tipos conta nos dois).
	$by_tipo = array();
	if ( $all_links ) {
		update_object_term_cache( wp_list_pluck( $all_links, 'link_id' ), 'recomenda_link' );
	}
	foreach ( $all_links as $row ) {
		$terms = get_the_terms( (int) $row->link_id, 'recomenda_tipo' );
		$names = ( $terms && ! is_wp_error( $terms ) ) ? wp_list_pluck( $terms, 'name' ) : array( 'Sem tipo' );
		foreach ( $names as $name ) {
			$by_tipo[ $name ] = ( isset( $by_tipo[ $name ] ) ? $by_tipo[ $name ] : 0 ) + (int) $row->total;
		}
	}
	arsort( $by_tipo );

	// Série diária completa (dias sem clique = 0).
	$daily = array();
	for ( $i = $dias - 1; $i >= 0; $i-- ) {
		$d           = recomenda_local_date( $i );
		$daily[ $d ] = isset( $daily_rows[ $d ] ) ? (int) $daily_rows[ $d ]->total : 0;
	}
	$total = array_sum( $daily );
	$max   = max( 1, max( $daily ) );

	$links = get_posts( array(
		'post_type'      => 'recomenda_link',
		'post_status'    => array( 'publish', 'draft', 'pending', 'private' ),
		'posts_per_page' => -1,
		'orderby'        => 'title',
		'order'          => 'ASC',
	) );
	?>
	<div class="wrap">
		<h1>Relatórios de cliques</h1>

		<style>
			.recomenda-filters{display:flex;flex-wrap:wrap;gap:12px;align-items:center;margin:16px 0}
			.recomenda-cards{display:flex;flex-wrap:wrap;gap:12px;margin:16px 0}
			.recomenda-card{background:#fff;border:1px solid #dcdcde;border-radius:4px;padding:12px 16px;min-width:160px}
			.recomenda-card .num{font-size:24px;font-weight:600;color:#1d2327;line-height:1.3}
			.recomenda-card .lbl{color:#646970}
			.recomenda-chart{background:#fff;border:1px solid #dcdcde;border-radius:4px;padding:16px;margin:16px 0}
			.recomenda-bars{display:flex;align-items:flex-end;gap:2px;height:180px;border-bottom:1px solid #c3c4c7}
			.recomenda-bar{flex:1 1 0;height:100%;display:flex;align-items:flex-end;position:relative;cursor:default}
			.recomenda-bar span{display:block;width:100%;background:#2271b1;border-radius:4px 4px 0 0;min-height:0}
			.recomenda-bar:hover span{background:#135e96}
			.recomenda-bar .tip{display:none;position:absolute;bottom:100%;left:50%;transform:translateX(-50%);margin-bottom:6px;background:#1d2327;color:#fff;padding:4px 8px;border-radius:3px;white-space:nowrap;font-size:12px;z-index:2}
			.recomenda-bar:hover .tip,.recomenda-bar:focus .tip{display:block}
			.recomenda-axis{display:flex;justify-content:space-between;color:#646970;font-size:12px;margin-top:6px}
			.recomenda-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(360px,1fr));gap:16px}
		</style>

		<form method="get" class="recomenda-filters">
			<input type="hidden" name="post_type" value="recomenda_link">
			<input type="hidden" name="page" value="recomenda-relatorios">
			<label>Período:
				<select name="dias">
					<?php foreach ( $allowed as $opt ) : ?>
						<option value="<?php echo (int) $opt; ?>" <?php selected( $dias, $opt ); ?>>Últimos <?php echo (int) $opt; ?> dias</option>
					<?php endforeach; ?>
				</select>
			</label>
			<label>Link:
				<select name="link">
					<option value="0">Todos os links</option>
					<?php foreach ( $links as $l ) : ?>
						<option value="<?php echo (int) $l->ID; ?>" <?php selected( $link_id, $l->ID ); ?>><?php echo esc_html( get_the_title( $l ) ); ?></option>
					<?php endforeach; ?>
				</select>
			</label>
			<label>Tipo:
				<?php
				wp_dropdown_categories( array(
					'taxonomy'        => 'recomenda_tipo',
					'name'            => 'tipo',
					'selected'        => $tipo_id,
					'show_option_all' => 'Todos os tipos',
					'hide_empty'      => false,
					'hierarchical'    => true,
					'orderby'         => 'name',
				) );
				?>
			</label>
			<?php submit_button( 'Filtrar', 'secondary', '', false ); ?>
		</form>

		<div class="recomenda-cards">
			<div class="recomenda-card"><div class="num"><?php echo esc_html( number_format_i18n( $total ) ); ?></div><div class="lbl">cliques no período</div></div>
			<div class="recomenda-card"><div class="num"><?php echo esc_html( number_format_i18n( $total / $dias, 1 ) ); ?></div><div class="lbl">média por dia</div></div>
			<div class="recomenda-card"><div class="num"><?php echo esc_html( number_format_i18n( count( $all_links ) ) ); ?></div><div class="lbl">links com clique</div></div>
		</div>

		<div class="recomenda-chart">
			<strong>Cliques por dia</strong>
			<div class="recomenda-bars" role="img" aria-label="<?php echo esc_attr( sprintf( 'Cliques por dia nos últimos %d dias. Total: %d.', $dias, $total ) ); ?>">
				<?php foreach ( $daily as $d => $n ) : ?>
					<?php $label = date_i18n( 'd/m', strtotime( $d ) ) . ': ' . number_format_i18n( $n ) . ( 1 === $n ? ' clique' : ' cliques' ); ?>
					<div class="recomenda-bar" tabindex="0">
						<span style="height:<?php echo esc_attr( round( $n / $max * 100, 2 ) ); ?>%"></span>
						<div class="tip"><?php echo esc_html( $label ); ?></div>
					</div>
				<?php endforeach; ?>
			</div>
			<div class="recomenda-axis">
				<span><?php echo esc_html( date_i18n( 'd/m', strtotime( $since ) ) ); ?></span>
				<span>máximo: <?php echo esc_html( number_format_i18n( max( $daily ) ) ); ?> cliques/dia</span>
				<span><?php echo esc_html( date_i18n( 'd/m', strtotime( recomenda_local_date() ) ) ); ?></span>
			</div>
			<p class="description">Os cliques por dia e por artigo são registrados a partir da versão 1.3.0. O total geral de cada link continua na lista de links.</p>
		</div>

		<div class="recomenda-grid">
			<div>
				<h2>Cliques por tipo</h2>
				<table class="widefat striped">
					<thead><tr><th>Tipo</th><th style="width:90px;text-align:right;">Cliques</th></tr></thead>
					<tbody>
					<?php if ( ! $by_tipo ) : ?>
						<tr><td colspan="2">Nenhum clique no período.</td></tr>
					<?php endif; ?>
					<?php foreach ( $by_tipo as $name => $n ) : ?>
						<tr>
							<td><?php echo esc_html( $name ); ?></td>
							<td style="text-align:right;"><strong><?php echo esc_html( number_format_i18n( $n ) ); ?></strong></td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
			</div>

			<div>
				<h2>Links mais clicados</h2>
				<table class="widefat striped">
					<thead><tr><th>Link</th><th style="width:90px;text-align:right;">Cliques</th></tr></thead>
					<tbody>
					<?php if ( ! $top_links ) : ?>
						<tr><td colspan="2">Nenhum clique no período.</td></tr>
					<?php endif; ?>
					<?php foreach ( $top_links as $row ) : ?>
						<?php $l = get_post( (int) $row->link_id ); ?>
						<tr>
							<td>
								<?php if ( $l ) : ?>
									<a href="<?php echo esc_url( add_query_arg( array( 'dias' => $dias, 'link' => $l->ID ), $base ) ); ?>"><?php echo esc_html( get_the_title( $l ) ); ?></a><br>
									<code>/<?php echo esc_html( RECOMENDA_BASE . '/' . recomenda_display_slug( $l ) ); ?></code>
								<?php else : ?>
									<em>Link removido (#<?php echo (int) $row->link_id; ?>)</em>
								<?php endif; ?>
							</td>
							<td style="text-align:right;"><strong><?php echo esc_html( number_format_i18n( (int) $row->total ) ); ?></strong></td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
			</div>

			<div>
				<h2>Artigos que mais geram cliques<?php echo $link_id ? ' neste link' : ''; ?></h2>
				<table class="widefat striped">
					<thead><tr><th>Artigo</th><th style="width:90px;text-align:right;">Cliques</th></tr></thead>
					<tbody>
					<?php if ( ! $top_posts ) : ?>
						<tr><td colspan="2">Nenhum clique no período.</td></tr>
					<?php endif; ?>
					<?php foreach ( $top_posts as $row ) : ?>
						<tr>
							<td><?php echo recomenda_source_label( (int) $row->post_id ); // phpcs:ignore WordPress.Security.EscapeOutput -- já escapado. ?></td>
							<td style="text-align:right;"><strong><?php echo esc_html( number_format_i18n( (int) $row->total ) ); ?></strong></td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		</div>

		<details style="margin-top:16px;">
			<summary>Ver cliques dia a dia em tabela</summary>
			<table class="widefat striped" style="max-width:360px;margin-top:8px;">
				<thead><tr><th>Dia</th><th style="text-align:right;">Cliques</th></tr></thead>
				<tbody>
				<?php foreach ( array_reverse( $daily, true ) as $d => $n ) : ?>
					<tr><td><?php echo esc_html( date_i18n( 'd/m/Y', strtotime( $d ) ) ); ?></td><td style="text-align:right;"><?php echo esc_html( number_format_i18n( $n ) ); ?></td></tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</details>
	</div>
	<?php
}

/**
 * Página "Ferramentas": troca em massa, exportar/importar CSV e verificação.
 */
function recomenda_render_tools() {
	if ( ! current_user_can( 'edit_others_posts' ) ) {
		wp_die( 'Sem permissão.' );
	}

	$buscar    = '';
	$substituir = '';
	$preview   = array();

	// Troca em massa: pré-visualizar ou aplicar.
	if ( isset( $_POST['recomenda_replace_action'] ) ) {
		check_admin_referer( 'recomenda_replace' );
		$buscar     = isset( $_POST['buscar'] ) ? trim( (string) wp_unslash( $_POST['buscar'] ) ) : '';
		$substituir = isset( $_POST['substituir'] ) ? trim( (string) wp_unslash( $_POST['substituir'] ) ) : '';

		if ( '' === $buscar ) {
			echo '<div class="notice notice-error"><p>Informe o texto a buscar.</p></div>';
		} else {
			$preview = recomenda_replace_candidates( $buscar, $substituir );

			if ( 'apply' === $_POST['recomenda_replace_action'] ) {
				$done   = 0;
				$failed = array();
				foreach ( $preview as $item ) {
					if ( false === $item['new'] ) {
						$failed[] = esc_html( $item['title'] );
						continue;
					}
					update_post_meta( $item['id'], '_recomenda_target', $item['new'] );
					recomenda_clear_health( $item['id'] );
					$done++;
				}
				echo '<div class="notice notice-success"><p><strong>' . (int) $done . ' link(s) atualizado(s).</strong></p></div>';
				if ( $failed ) {
					echo '<div class="notice notice-warning"><p>Não alterados porque o resultado seria uma URL inválida: ' . implode( ', ', $failed ) . '</p></div>'; // phpcs:ignore WordPress.Security.EscapeOutput -- já escapado.
				}
				$preview = array();
			}
		}
	}

	$import = get_transient( 'recomenda_import_' . get_current_user_id() );
	if ( is_array( $import ) ) {
		delete_transient( 'recomenda_import_' . get_current_user_id() );
		printf(
			'<div class="notice notice-success is-dismissible"><p><strong>Importação concluída:</strong> %d criado(s), %d atualizado(s), %d ignorado(s) por já existirem, %d com erro.</p></div>',
			(int) $import['created'],
			(int) $import['updated'],
			(int) $import['skipped'],
			count( $import['errors'] )
		);
		if ( $import['errors'] ) {
			echo '<div class="notice notice-warning"><p><strong>Linhas com erro:</strong><br>' . implode( '<br>', array_map( 'esc_html', array_slice( $import['errors'], 0, 50 ) ) ) . '</p></div>'; // phpcs:ignore WordPress.Security.EscapeOutput -- já escapado.
		}
	}

	if ( isset( $_GET['verificacao'] ) ) {
		echo '<div class="notice notice-success is-dismissible"><p>Verificação iniciada. Ela roda em segundo plano, 5 links por minuto; acompanhe a coluna "Situação" na lista de links.</p></div>';
	}

	$cycle = (int) get_option( 'recomenda_check_cycle' );
	$next  = wp_next_scheduled( 'recomenda_check_links' );
	$gmt   = (int) ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS );
	?>
	<div class="wrap">
		<h1>Ferramentas do Recomenda Links</h1>

		<div class="card" style="max-width:900px;">
			<h2>Trocar código de afiliado em massa</h2>
			<p>Substitui um trecho em <strong>todas</strong> as URLs de destino. Exemplo: buscar <code>tag=antigo-20</code> e substituir por <code>tag=novo-20</code>. Diferencia maiúsculas de minúsculas. Primeiro você vê a prévia, depois confirma.</p>
			<form method="post">
				<?php wp_nonce_field( 'recomenda_replace' ); ?>
				<p>
					<label>Buscar:<br><input type="text" name="buscar" class="regular-text" value="<?php echo esc_attr( $buscar ); ?>" required></label>
				</p>
				<p>
					<label>Substituir por:<br><input type="text" name="substituir" class="regular-text" value="<?php echo esc_attr( $substituir ); ?>"></label>
				</p>
				<p><button type="submit" name="recomenda_replace_action" value="preview" class="button">Pré-visualizar</button></p>

				<?php if ( $preview ) : ?>
					<table class="widefat striped" style="margin:12px 0;">
						<thead><tr><th>Link</th><th>Destino atual</th><th>Novo destino</th></tr></thead>
						<tbody>
						<?php foreach ( $preview as $item ) : ?>
							<tr>
								<td><?php echo esc_html( $item['title'] ); ?></td>
								<td style="word-break:break-all;"><?php echo esc_html( $item['old'] ); ?></td>
								<td style="word-break:break-all;"><?php echo false === $item['new'] ? '<span style="color:#b32d2e;">URL inválida — não será alterado</span>' : esc_html( $item['new'] ); ?></td>
							</tr>
						<?php endforeach; ?>
						</tbody>
					</table>
					<p><button type="submit" name="recomenda_replace_action" value="apply" class="button button-primary" onclick="return confirm('Aplicar a troca em <?php echo (int) count( $preview ); ?> link(s)?');">Aplicar em <?php echo (int) count( $preview ); ?> link(s)</button></p>
				<?php elseif ( isset( $_POST['recomenda_replace_action'] ) && 'preview' === $_POST['recomenda_replace_action'] && '' !== $buscar ) : ?>
					<p><em>Nenhum destino contém esse texto.</em></p>
				<?php endif; ?>
			</form>
		</div>

		<div class="card" style="max-width:900px;">
			<h2>Exportar links (CSV)</h2>
			<p>Baixa todos os links com apelido, título, destino, total de cliques e status. Serve também como <strong>backup</strong>. Abre direto no Excel.</p>
			<p><a class="button" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=recomenda_export' ), 'recomenda_export' ) ); ?>">Baixar CSV</a></p>
		</div>

		<div class="card" style="max-width:900px;">
			<h2>Importar links (CSV)</h2>
			<p>Colunas aceitas (com cabeçalho): <code>slug</code>, <code>titulo</code>, <code>destino</code>, <code>cliques</code>, <code>status</code>, <code>tipo</code> (vários tipos separados por <code>|</code>; tipos que não existem são criados), <code>descricao</code>, <code>imagem</code> (URL). Separador vírgula ou ponto e vírgula. O mesmo formato da exportação.</p>
			<p><strong>Nenhum link é apagado.</strong> O total de cliques só é usado para links novos; nos que já existem, o contador não é alterado.</p>
			<form method="post" enctype="multipart/form-data" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="recomenda_import">
				<?php wp_nonce_field( 'recomenda_import' ); ?>
				<p><input type="file" name="arquivo" accept=".csv,text/csv" required></p>
				<p>
					Quando o apelido já existir:<br>
					<label><input type="radio" name="existentes" value="ignorar" checked> manter como está (ignorar a linha)</label><br>
					<label><input type="radio" name="existentes" value="atualizar"> atualizar título e destino</label>
				</p>
				<p><button type="submit" class="button button-primary">Importar</button></p>
			</form>
		</div>

		<div class="card" style="max-width:900px;">
			<h2>Verificação de links quebrados</h2>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="recomenda_checker_toggle">
				<?php wp_nonce_field( 'recomenda_checker_toggle' ); ?>
				<p>
					<label><input type="checkbox" name="ativo" value="1" <?php checked( recomenda_checker_enabled() ); ?>> Verificar os destinos automaticamente uma vez por semana</label>
					<button type="submit" class="button button-small">Salvar</button>
				</p>
			</form>
			<?php if ( recomenda_checker_enabled() ) : ?>
			<p>
				<?php if ( $cycle ) : ?>
					Última verificação iniciada em <?php echo esc_html( date_i18n( 'd/m/Y H:i', $cycle + $gmt ) ); ?>.
				<?php endif; ?>
				<?php if ( $next ) : ?>
					Próxima: <?php echo esc_html( date_i18n( 'd/m/Y H:i', $next + $gmt ) ); ?>.
				<?php endif; ?>
			</p>
			<p class="description">Links da Amazon (amazon.com.br, amzn.to, link.amazon…) não são verificados, porque a Amazon responde erro a qualquer verificação automática mesmo com o link funcionando. Eles aparecem como "não verificável". Outras lojas que bloquearem a verificação aparecem como "Não confirmado", e não como quebrado.</p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="recomenda_check_all">
				<?php wp_nonce_field( 'recomenda_check_all' ); ?>
				<p><button type="submit" class="button">Verificar todos agora</button></p>
			</form>
			<?php else : ?>
			<p class="description">Verificação desligada: a coluna "Situação" e os avisos de link quebrado ficam ocultos.</p>
			<?php endif; ?>
		</div>
	</div>
	<?php
}

// Links cujo destino contém o texto buscado, com o destino antes/depois.
function recomenda_replace_candidates( $buscar, $substituir ) {
	global $wpdb;
	$rows = $wpdb->get_results( $wpdb->prepare(
		"SELECT p.ID, p.post_title, m.meta_value FROM {$wpdb->posts} p
		INNER JOIN {$wpdb->postmeta} m ON m.post_id = p.ID AND m.meta_key = %s
		WHERE p.post_type = %s AND p.post_status NOT IN ('trash','auto-draft')
		AND m.meta_value LIKE %s ORDER BY p.post_title ASC",
		'_recomenda_target',
		'recomenda_link',
		'%' . $wpdb->esc_like( $buscar ) . '%'
	) );

	$items = array();
	foreach ( $rows as $row ) {
		// LIKE não diferencia maiúsculas; confirma com comparação exata.
		if ( false === strpos( $row->meta_value, $buscar ) ) {
			continue;
		}
		$items[] = array(
			'id'    => (int) $row->ID,
			'title' => $row->post_title,
			'old'   => $row->meta_value,
			'new'   => recomenda_validate_url( str_replace( $buscar, $substituir, $row->meta_value ) ),
		);
	}
	return $items;
}

// Inicia a verificação de todos os links em segundo plano.
function recomenda_handle_check_all() {
	check_admin_referer( 'recomenda_check_all' );
	if ( ! current_user_can( 'edit_others_posts' ) || ! recomenda_checker_enabled() ) {
		wp_die( 'Sem permissão.' );
	}
	update_option( 'recomenda_check_cycle', time(), false );
	wp_clear_scheduled_hook( 'recomenda_check_links_batch' );
	wp_schedule_single_event( time(), 'recomenda_check_links_batch' );
	if ( function_exists( 'spawn_cron' ) ) {
		spawn_cron();
	}
	wp_safe_redirect( add_query_arg( array( 'post_type' => 'recomenda_link', 'page' => 'recomenda-ferramentas', 'verificacao' => 1 ), admin_url( 'edit.php' ) ) );
	exit;
}
add_action( 'admin_post_recomenda_check_all', 'recomenda_handle_check_all' );

// Liga/desliga o verificador de links quebrados.
function recomenda_handle_checker_toggle() {
	check_admin_referer( 'recomenda_checker_toggle' );
	if ( ! current_user_can( 'edit_others_posts' ) ) {
		wp_die( 'Sem permissão.' );
	}
	$on = ! empty( $_POST['ativo'] );
	update_option( 'recomenda_checker_enabled', $on ? '1' : '0' );
	if ( ! $on ) {
		wp_clear_scheduled_hook( 'recomenda_check_links_batch' );
	}
	recomenda_add_notice( 'success', $on ? 'Verificação de links ligada.' : 'Verificação de links desligada.' );
	wp_safe_redirect( add_query_arg( array( 'post_type' => 'recomenda_link', 'page' => 'recomenda-ferramentas' ), admin_url( 'edit.php' ) ) );
	exit;
}
add_action( 'admin_post_recomenda_checker_toggle', 'recomenda_handle_checker_toggle' );

/**
 * ============================================================
 * 13. EXPORTAR / IMPORTAR CSV
 * ============================================================
 */

// Evita que o Excel interprete títulos começando com = + - @ como fórmula.
function recomenda_csv_safe( $value ) {
	return preg_match( '/^[=+\-@]/', $value ) ? "'" . $value : $value;
}

function recomenda_handle_export() {
	check_admin_referer( 'recomenda_export' );
	if ( ! current_user_can( 'edit_others_posts' ) ) {
		wp_die( 'Sem permissão.' );
	}

	$posts = get_posts( array(
		'post_type'      => 'recomenda_link',
		'post_status'    => array( 'publish', 'draft', 'pending', 'private', 'future' ),
		'posts_per_page' => -1,
		'orderby'        => 'title',
		'order'          => 'ASC',
	) );

	nocache_headers();
	header( 'Content-Type: text/csv; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename=recomenda-links-' . recomenda_local_date() . '.csv' );

	$out = fopen( 'php://output', 'w' );
	fwrite( $out, "\xEF\xBB\xBF" ); // BOM: o Excel reconhece os acentos.
	fputcsv( $out, array( 'slug', 'titulo', 'destino', 'cliques', 'status', 'tipo', 'descricao', 'imagem' ), ';' );
	foreach ( $posts as $p ) {
		fputcsv( $out, array(
			$p->post_name,
			recomenda_csv_safe( $p->post_title ),
			get_post_meta( $p->ID, '_recomenda_target', true ),
			(int) get_post_meta( $p->ID, '_recomenda_clicks', true ),
			$p->post_status,
			recomenda_get_tipos_text( $p->ID ),
			recomenda_csv_safe( (string) get_post_meta( $p->ID, '_recomenda_descricao', true ) ),
			recomenda_image_url( $p->ID ),
		), ';' );
	}
	fclose( $out );
	exit;
}
add_action( 'admin_post_recomenda_export', 'recomenda_handle_export' );

function recomenda_handle_import() {
	check_admin_referer( 'recomenda_import' );
	if ( ! current_user_can( 'edit_others_posts' ) ) {
		wp_die( 'Sem permissão.' );
	}

	$back   = add_query_arg( array( 'post_type' => 'recomenda_link', 'page' => 'recomenda-ferramentas' ), admin_url( 'edit.php' ) );
	$result = array( 'created' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => array() );

	if ( empty( $_FILES['arquivo']['tmp_name'] ) || ! is_uploaded_file( $_FILES['arquivo']['tmp_name'] ) ) {
		$result['errors'][] = 'Nenhum arquivo enviado.';
	} elseif ( $_FILES['arquivo']['size'] > 5 * MB_IN_BYTES ) {
		$result['errors'][] = 'Arquivo maior que 5 MB.';
	} else {
		$csv = file_get_contents( $_FILES['arquivo']['tmp_name'] ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		$csv = preg_replace( '/^\xEF\xBB\xBF/', '', (string) $csv );

		// Planilhas salvas no Excel em "CSV (separado por vírgulas)" vêm em Windows-1252.
		if ( function_exists( 'mb_check_encoding' ) && ! mb_check_encoding( $csv, 'UTF-8' ) ) {
			$csv = mb_convert_encoding( $csv, 'UTF-8', 'Windows-1252' );
		}

		$result = recomenda_import_csv( $csv, isset( $_POST['existentes'] ) && 'atualizar' === $_POST['existentes'] );
	}

	set_transient( 'recomenda_import_' . get_current_user_id(), $result, 5 * MINUTE_IN_SECONDS );
	wp_safe_redirect( $back );
	exit;
}
add_action( 'admin_post_recomenda_import', 'recomenda_handle_import' );

// Descrição e URL da imagem vindas do CSV (vazio = mantém o que já existe).
function recomenda_import_extras( $link_id, $descricao, $imagem ) {
	$descricao = preg_replace( "/^'(?=[=+\-@])/", '', $descricao );
	if ( '' !== $descricao ) {
		update_post_meta( $link_id, '_recomenda_descricao', sanitize_textarea_field( $descricao ) );
	}
	$imagem = recomenda_validate_url( $imagem );
	// Não duplica a imagem do produto já enviada para a biblioteca de mídia.
	if ( $imagem && ! has_post_thumbnail( $link_id ) ) {
		update_post_meta( $link_id, '_recomenda_imagem_url', $imagem );
	}
}

function recomenda_import_csv( $csv, $update_existing ) {
	$result = array( 'created' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => array() );

	$first_line = strtok( $csv, "\n" );
	$delimiter  = substr_count( (string) $first_line, ';' ) >= substr_count( (string) $first_line, ',' ) ? ';' : ',';

	$fh = fopen( 'php://temp', 'r+' );
	fwrite( $fh, $csv );
	rewind( $fh );

	$map  = array( 'slug' => 0, 'titulo' => 1, 'destino' => 2, 'cliques' => 3, 'status' => 4, 'tipo' => 5, 'descricao' => 6, 'imagem' => 7 );
	$line = 0;

	while ( false !== ( $row = fgetcsv( $fh, 0, $delimiter ) ) ) {
		$line++;
		if ( array( null ) === $row || '' === trim( implode( '', $row ) ) ) {
			continue; // Linha vazia.
		}

		// Cabeçalho: mapeia as colunas pelo nome.
		if ( 1 === $line ) {
			$header = array_map( function ( $h ) {
				return sanitize_key( remove_accents( trim( $h ) ) );
			}, $row );
			if ( array_intersect( array( 'slug', 'destino', 'titulo' ), $header ) ) {
				$map = array();
				foreach ( $header as $i => $h ) {
					$map[ $h ] = $i;
				}
				continue;
			}
		}

		$get = function ( $col ) use ( $row, $map ) {
			return ( isset( $map[ $col ], $row[ $map[ $col ] ] ) ) ? trim( (string) $row[ $map[ $col ] ] ) : '';
		};

		$titulo = preg_replace( "/^'(?=[=+\-@])/", '', $get( 'titulo' ) );
		$slug   = sanitize_title( '' !== $get( 'slug' ) ? $get( 'slug' ) : $titulo );
		$dest   = recomenda_validate_url( $get( 'destino' ) );
		$status = in_array( $get( 'status' ), array( 'publish', 'draft', 'pending', 'private' ), true ) ? $get( 'status' ) : 'publish';

		if ( '' === $slug ) {
			$result['errors'][] = "Linha {$line}: sem apelido (slug) nem título.";
			continue;
		}
		if ( false === $dest ) {
			$result['errors'][] = "Linha {$line} ({$slug}): URL de destino inválida.";
			continue;
		}

		$existing = get_posts( array(
			'name'           => $slug,
			'post_type'      => 'recomenda_link',
			'post_status'    => array( 'publish', 'draft', 'pending', 'private', 'future' ),
			'posts_per_page' => 1,
			'no_found_rows'  => true,
		) );

		if ( $existing ) {
			if ( ! $update_existing ) {
				$result['skipped']++;
				continue;
			}
			$id = $existing[0]->ID;
			if ( '' !== $titulo && $titulo !== $existing[0]->post_title ) {
				wp_update_post( array( 'ID' => $id, 'post_title' => $titulo ) );
			}
			if ( '' !== $dest && get_post_meta( $id, '_recomenda_target', true ) !== $dest ) {
				update_post_meta( $id, '_recomenda_target', $dest );
				recomenda_clear_health( $id );
			}
			recomenda_set_tipos( $id, $get( 'tipo' ) ); // Vazio = mantém os tipos atuais.
			recomenda_import_extras( $id, $get( 'descricao' ), $get( 'imagem' ) );
			$result['updated']++;
			continue;
		}

		$id = wp_insert_post( array(
			'post_type'   => 'recomenda_link',
			'post_title'  => '' !== $titulo ? $titulo : $slug,
			'post_name'   => $slug,
			'post_status' => $status,
		), true );

		if ( is_wp_error( $id ) ) {
			$result['errors'][] = "Linha {$line} ({$slug}): " . $id->get_error_message();
			continue;
		}

		update_post_meta( $id, '_recomenda_target', $dest );
		update_post_meta( $id, '_recomenda_clicks', max( 0, (int) $get( 'cliques' ) ) );
		recomenda_set_tipos( $id, $get( 'tipo' ) );
		recomenda_import_extras( $id, $get( 'descricao' ), $get( 'imagem' ) );
		$result['created']++;
	}

	fclose( $fh );
	return $result;
}

/**
 * ============================================================
 * 14. ATIVAÇÃO / DESATIVAÇÃO
 * ============================================================
 *
 * Desativar o plugin NÃO apaga links, cliques nem estatísticas.
 */
function recomenda_activate() {
	recomenda_register_cpt();
	recomenda_register_tipo();
	recomenda_add_rewrite();
	recomenda_install();
}
register_activation_hook( __FILE__, 'recomenda_activate' );

function recomenda_deactivate() {
	wp_clear_scheduled_hook( 'recomenda_check_links' );
	wp_clear_scheduled_hook( 'recomenda_check_links_batch' );
	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'recomenda_deactivate' );
