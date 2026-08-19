=== Recomenda Links ===
Contributors: cmosdrake
Tags: afiliado, affiliate, links, redirect, cloaking
Requires at least: 5.0
Tested up to: 7.0
Requires PHP: 7.0
Stable tag: 1.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Gerenciador de links de afiliado centralizado: troque o destino em um lugar e aplique em todos os artigos. Com contagem de cliques e botão personalizável.

== Description ==

Em vez de colar o link de afiliado direto em cada artigo, você cria um link "apelido" do seu próprio site — por exemplo seusite.com/recomenda/furadeira-bosch — que redireciona para o link de afiliado real. Se o afiliado cair ou mudar, você altera o destino em um só lugar e todos os artigos passam a apontar para o novo link automaticamente.

Recursos:

* Links centralizados com o prefixo /recomenda/.
* Contagem de cliques por link, com coluna ordenável.
* Shortcode [recomenda] para link ou botão.
* Botão personalizável para a identidade visual de qualquer site.
* rel="nofollow sponsored" automático.
* Redirecionamento 302 (temporário).

== Installation ==

1. Envie o plugin em Plugins > Adicionar novo > Enviar plugin, ou copie a pasta para wp-content/plugins/recomenda-links/.
2. Ative o plugin.
3. Se algum link retornar "página não encontrada", vá em Configurações > Links permanentes e clique em Salvar uma vez.

== Frequently Asked Questions ==

= Preciso usar o shortcode? =

Não. Trocar o link em um lugar só funciona igual colando a URL /recomenda/... direto no texto. O shortcode é uma comodidade para padronizar o visual.

= Como personalizo o botão? =

Sobrescreva as variáveis CSS (--recomenda-bg, --recomenda-cor, --recomenda-radius) em Aparência > Personalizar > CSS adicional, ou passe as classes do seu tema no atributo classe="" do shortcode.

== Changelog ==

= 1.1.0 =
* Shortcode [recomenda] com link ou botão personalizável.
* Atributos rel="nofollow sponsored" automáticos.

= 1.0.0 =
* Versão inicial: redirecionamento centralizado /recomenda/ e contagem de cliques.

== Upgrade Notice ==

= 1.1.0 =
Adiciona shortcode e botão personalizável. Atualização recomendada.
