<?php
/**
 * Plugin Name:       Recomenda Links
 * Plugin URI:        https://projetowebstudio.com.br
 * Description:       Gerenciador de links de afiliado centralizado. Crie links do tipo seusite.com/recomenda/apelido que redirecionam para o link de afiliado real. Se o afiliado mudar, você altera o destino em um só lugar e aplica em todos os artigos. Inclui contagem de cliques e shortcode com botão personalizável.
 * Version:           1.1.0
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

// Slug base usado nas URLs: seusite.com/recomenda/apelido
if ( ! defined( 'RECOMENDA_BASE' ) ) {
	define( 'RECOMENDA_BASE', 'recomenda' );
}

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
	);

	$args = array(
		'labels'              => $labels,
		'public'              => false,      // Não cria página pública própria.
		'publicly_queryable'  => false,
		'show_ui'             => true,       // Aparece no painel admin.
		'show_in_menu'        => true,
		'menu_icon'           => 'dashicons-admin-links',
		'menu_position'       => 30,
		'supports'            => array( 'title' ), // Só o título (nome interno do link).
		'capability_type'     => 'post',
		'has_archive'         => false,
		'rewrite'             => false,      // Nós criamos a regra de URL manualmente.
		'query_var'           => false,
	);

	register_post_type( 'recomenda_link', $args );
}
add_action( 'init', 'recomenda_register_cpt' );

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
 * 3. HANDLER: intercepta a URL, conta o clique e redireciona
 * ============================================================
 */
function recomenda_handle_redirect() {
	$slug = get_query_var( 'recomenda_slug' );

	if ( empty( $slug ) ) {
		return; // Não é uma URL /recomenda/... , segue o fluxo normal.
	}

	$slug = sanitize_title( $slug );

	// Procura o link pelo apelido (post_name / slug do CPT).
	$posts = get_posts( array(
		'name'           => $slug,
		'post_type'      => 'recomenda_link',
		'post_status'    => 'publish',
		'posts_per_page' => 1,
	) );

	if ( empty( $posts ) ) {
		status_header( 404 );
		wp_die( 'Link não encontrado.', 'Link não encontrado', array( 'response' => 404 ) );
	}

	$link_id = $posts[0]->ID;
	$target  = get_post_meta( $link_id, '_recomenda_target', true );

	if ( empty( $target ) ) {
		status_header( 404 );
		wp_die( 'Este link ainda não tem um destino configurado.', 'Destino ausente', array( 'response' => 404 ) );
	}

	// Conta o clique (ignora pré-visualizações de bots simples e admins logados? -> conta todos por simplicidade).
	$clicks = (int) get_post_meta( $link_id, '_recomenda_clicks', true );
	update_post_meta( $link_id, '_recomenda_clicks', $clicks + 1 );

	// Redirecionamento 302 (temporário) — ideal para afiliado, já que o destino pode mudar.
	nocache_headers();
	wp_redirect( esc_url_raw( $target ), 302 );
	exit;
}
add_action( 'template_redirect', 'recomenda_handle_redirect' );

/**
 * ============================================================
 * 4. CAMPO "URL DE DESTINO" na tela de edição do link
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
}
add_action( 'add_meta_boxes', 'recomenda_add_meta_box' );

function recomenda_render_meta_box( $post ) {
	wp_nonce_field( 'recomenda_save_meta', 'recomenda_meta_nonce' );

	$target = get_post_meta( $post->ID, '_recomenda_target', true );
	$clicks = (int) get_post_meta( $post->ID, '_recomenda_clicks', true );

	$pretty = home_url( '/' . RECOMENDA_BASE . '/' . $post->post_name );
	?>
	<p>
		<label for="recomenda_target"><strong>URL de destino (link de afiliado real):</strong></label><br>
		<input type="url" id="recomenda_target" name="recomenda_target"
			value="<?php echo esc_attr( $target ); ?>"
			placeholder="https://loja.com/produto?ref=seucodigo"
			style="width:100%; max-width:640px;" />
	</p>

	<?php if ( $post->post_name ) : ?>
	<p>
		<strong>Seu link para usar nos artigos:</strong><br>
		<code><?php echo esc_html( $pretty ); ?></code>
	</p>
	<p>
		<strong>Cliques registrados:</strong> <?php echo esc_html( $clicks ); ?>
		&nbsp; <label style="font-weight:normal;">
			<input type="checkbox" name="recomenda_reset_clicks" value="1" /> zerar contador ao salvar
		</label>
	</p>
	<?php else : ?>
		<p><em>Salve o link uma vez para gerar o endereço /<?php echo esc_html( RECOMENDA_BASE ); ?>/...</em></p>
	<?php endif; ?>
	<p style="color:#666;">
		Dica: o "Título" do link (campo lá em cima) é só o nome interno. O apelido que aparece na URL
		vem do <em>slug</em> — você pode ajustá-lo em "Editar" ao lado do título.
	</p>
	<?php
}

/**
 * ============================================================
 * 5. SALVAR o destino e (opcional) zerar cliques
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
		$target = esc_url_raw( trim( wp_unslash( $_POST['recomenda_target'] ) ) );
		update_post_meta( $post_id, '_recomenda_target', $target );
	}

	if ( ! empty( $_POST['recomenda_reset_clicks'] ) ) {
		update_post_meta( $post_id, '_recomenda_clicks', 0 );
	}
}
add_action( 'save_post_recomenda_link', 'recomenda_save_meta' );

/**
 * ============================================================
 * 6. COLUNAS na lista de links: URL amigável, destino e cliques
 * ============================================================
 */
function recomenda_columns( $columns ) {
	$new = array();
	foreach ( $columns as $key => $label ) {
		$new[ $key ] = $label;
		if ( 'title' === $key ) {
			$new['recomenda_url']    = 'Link (/' . RECOMENDA_BASE . '/)';
			$new['recomenda_target'] = 'Destino';
			$new['recomenda_clicks'] = 'Cliques';
		}
	}
	return $new;
}
add_filter( 'manage_recomenda_link_posts_columns', 'recomenda_columns' );

function recomenda_render_columns( $column, $post_id ) {
	if ( 'recomenda_url' === $column ) {
		$post   = get_post( $post_id );
		$pretty = home_url( '/' . RECOMENDA_BASE . '/' . $post->post_name );
		echo '<code>' . esc_html( '/' . RECOMENDA_BASE . '/' . $post->post_name ) . '</code>';
		echo '<br><a href="' . esc_url( $pretty ) . '" target="_blank" rel="noopener" style="font-size:11px;">testar &raquo;</a>';
	}

	if ( 'recomenda_target' === $column ) {
		$target = get_post_meta( $post_id, '_recomenda_target', true );
		if ( $target ) {
			echo '<a href="' . esc_url( $target ) . '" target="_blank" rel="noopener nofollow">'
				. esc_html( wp_trim_words( $target, 8, '…' ) ) . '</a>';
		} else {
			echo '<span style="color:#c00;">— sem destino —</span>';
		}
	}

	if ( 'recomenda_clicks' === $column ) {
		echo '<strong>' . (int) get_post_meta( $post_id, '_recomenda_clicks', true ) . '</strong>';
	}
}
add_action( 'manage_recomenda_link_posts_custom_column', 'recomenda_render_columns', 10, 2 );

// Permite ordenar pela coluna de cliques.
function recomenda_sortable_columns( $columns ) {
	$columns['recomenda_clicks'] = 'recomenda_clicks';
	return $columns;
}
add_filter( 'manage_edit-recomenda_link_sortable_columns', 'recomenda_sortable_columns' );

function recomenda_orderby_clicks( $query ) {
	if ( ! is_admin() || ! $query->is_main_query() ) {
		return;
	}
	if ( 'recomenda_clicks' === $query->get( 'orderby' ) ) {
		$query->set( 'meta_key', '_recomenda_clicks' );
		$query->set( 'orderby', 'meta_value_num' );
	}
}
add_action( 'pre_get_posts', 'recomenda_orderby_clicks' );

/**
 * ============================================================
 * 7. SHORTCODE: [recomenda] — link ou botão personalizável
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
 * Atributos:
 *   id      -> apelido do link (obrigatório)
 *   estilo  -> "link" (padrão) ou "botao"
 *   classe  -> classes CSS extras, separadas por espaço (aplique as do seu tema)
 *   rel     -> "on" (padrão, adiciona nofollow sponsored) ou "off"
 *   target  -> "_blank" (padrão, nova aba) ou "" para abrir na mesma aba
 */
function recomenda_shortcode( $atts, $content = null ) {
	$atts = shortcode_atts(
		array(
			'id'     => '',
			'estilo' => 'link',
			'classe' => '',
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

	$url   = home_url( '/' . RECOMENDA_BASE . '/' . $slug );
	$texto = ! empty( $content ) ? do_shortcode( $content ) : 'Ver oferta';

	// Monta a lista de classes: base + estilo escolhido + classes do tema.
	$classes = array( 'recomenda-link' );
	if ( 'botao' === $atts['estilo'] ) {
		$classes[] = 'recomenda-btn';
	}
	if ( ! empty( $atts['classe'] ) ) {
		foreach ( explode( ' ', $atts['classe'] ) as $c ) {
			$c = sanitize_html_class( $c );
			if ( $c ) {
				$classes[] = $c;
			}
		}
	}

	$rel    = ( 'off' === strtolower( $atts['rel'] ) ) ? '' : ' rel="nofollow sponsored"';
	$target = $atts['target'] ? ' target="' . esc_attr( $atts['target'] ) . '"' : '';

	return sprintf(
		'<a href="%s" class="%s"%s%s>%s</a>',
		esc_url( $url ),
		esc_attr( implode( ' ', $classes ) ),
		$target,
		$rel,
		wp_kses_post( $texto )
	);
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
 */
function recomenda_button_css() {
	echo '<style id="recomenda-css">'
		. '.recomenda-btn{'
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
		. '</style>';
}
add_action( 'wp_head', 'recomenda_button_css' );

/**
 * ============================================================
 * 8. ATIVAÇÃO / DESATIVAÇÃO: recria as regras de URL
 * ============================================================
 */
function recomenda_activate() {
	recomenda_register_cpt();
	recomenda_add_rewrite();
	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'recomenda_activate' );

function recomenda_deactivate() {
	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'recomenda_deactivate' );
