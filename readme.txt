=== Recomenda Links ===
Contributors: marceloandrade
Tags: afiliado, affiliate, links, redirect, cloaking
Requires at least: 5.0
Tested up to: 7.0.4
Requires PHP: 7.0
Stable tag: 1.3.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Gerenciador de links de afiliado centralizado: troque o destino em um lugar e aplique em todos os artigos. Com contagem de cliques, relatórios, verificador de links quebrados e botão personalizável.

== Description ==

Em vez de colar o link de afiliado direto em cada artigo, você cria um link "apelido" do seu próprio site — por exemplo seusite.com/recomenda/furadeira-bosch — que redireciona para o link de afiliado real. Se o afiliado cair ou mudar, você altera o destino em um só lugar e todos os artigos passam a apontar para o novo link automaticamente.

Recursos:

* Links centralizados com o prefixo /recomenda/.
* Contagem de cliques por link, com coluna ordenável.
* Shortcode [recomenda] para link ou botão.
* Botão personalizável para a identidade visual de qualquer site.
* rel="nofollow sponsored" automático.
* Redirecionamento 302 (temporário).
* Relatórios de cliques por dia e por artigo de origem.
* Tipos de link (Serras, Brocas...) com filtro na lista e nos relatórios.
* Verificador semanal de links quebrados.
* Troca em massa do código de afiliado nas URLs de destino.
* Exportação e importação em CSV (também serve de backup).
* Botões para copiar o link e o shortcode.

== Installation ==

1. Envie o plugin em Plugins > Adicionar novo > Enviar plugin, ou copie a pasta para wp-content/plugins/recomenda-links/.
2. Ative o plugin.
3. Se algum link retornar "página não encontrada", vá em Configurações > Links permanentes e clique em Salvar uma vez.

== Frequently Asked Questions ==

= Preciso usar o shortcode? =

Não. Trocar o link em um lugar só funciona igual colando a URL /recomenda/... direto no texto. O shortcode é uma comodidade para padronizar o visual.

= Como personalizo o botão? =

Sobrescreva as variáveis CSS (--recomenda-bg, --recomenda-cor, --recomenda-radius) em Aparência > Personalizar > CSS adicional, ou passe as classes do seu tema no atributo classe="" do shortcode.

= Vou perder meus links ao atualizar? =

Não. Os links, destinos e cliques ficam no banco do WordPress e continuam os mesmos. Desativar o plugin também não apaga nada. Recomendamos fazer um backup do banco antes de qualquer atualização.

= Por que meus próprios cliques não aparecem? =

A contagem ignora usuários logados que editam o site, além de robôs e pré-visualizações. Para testar a contagem, use uma janela anônima.

= Meus links da Amazon aparecem como quebrados =

A Amazon responde erro a qualquer verificação automática, então os links dela aparecem como "não verificável". Se preferir, desligue o verificador em Recomenda Links > Ferramentas.

== Changelog ==

= 1.3.0 =
* Tipos de link (Serras, Brocas...) com filtro na lista, edição rápida e em massa.
* Relatórios de cliques por dia, por artigo de origem e por tipo.
* Verificador semanal de links quebrados (links da Amazon aparecem como "não verificável"), que pode ser desligado.
* Troca em massa nas URLs de destino e exportação/importação CSV.
* Botões para copiar o link e o shortcode, validação e aviso de destino duplicado.
* Correções: ordenação por cliques, contagem sem robôs e atômica, CSS carregado só quando necessário, aviso de shortcode com link inexistente.

= 1.1.1 =
* Autoria do plugin atualizada para Marcelo Andrade (projetowebstudio.com.br).
* Tested up to atualizado para o WordPress 7.0.4.

= 1.1.0 =
* Shortcode [recomenda] com link ou botão personalizável.
* Atributos rel="nofollow sponsored" automáticos.

= 1.0.0 =
* Versão inicial: redirecionamento centralizado /recomenda/ e contagem de cliques.

== Upgrade Notice ==

= 1.3.0 =
Tipos de link, relatórios, verificador de links, troca em massa e CSV. Os links e cliques já cadastrados são mantidos.

= 1.1.1 =
Apenas metadados e autoria. Nenhuma mudança de funcionalidade.

= 1.1.0 =
Adiciona shortcode e botão personalizável. Atualização recomendada.
