# Changelog

Todas as mudanças relevantes deste projeto são documentadas neste arquivo.

O formato segue o [Keep a Changelog](https://keepachangelog.com/pt-BR/1.0.0/)
e o projeto adota o [Versionamento Semântico](https://semver.org/lang/pt-BR/).

## [1.4.0] - 2026-09-24

### Adicionado
- **Imagem do produto** em cada link: envie uma foto pela biblioteca de mídia ou informe a URL de uma imagem (por exemplo, a imagem do SiteStripe da Amazon).
- **Descrição curta do produto** em cada link.
- Colunas de imagem (miniatura) e descrição na lista de links.
- **Card do produto** no shortcode: `[recomenda id="..." estilo="card"]Ver na loja[/recomenda]` mostra imagem, título, descrição e botão. Aceita os atributos `titulo`, `descricao`, `imagem="off"` e `classe`, e é personalizável por variáveis CSS. Os cliques continuam sendo contados.
- Botão "copiar card" na lista e na tela de edição.
- Colunas `descricao` e `imagem` na exportação e na importação CSV.

## [1.3.0] - 2026-09-24

### Adicionado
- **Tipos de link** (Recomenda Links → Tipos), como Serras, Brocas ou Plainas. Aceitam subtipos e funcionam como as categorias dos posts: coluna "Tipos" e filtro "Todos os tipos" na lista, e definição do tipo na edição rápida e na edição em massa.
- **Relatórios** (Recomenda Links → Relatórios): cliques por dia nos últimos 7, 30 ou 90 dias, links mais clicados, artigos que mais geram cliques e cliques por tipo, com filtros por link e por tipo.
- **Cliques por artigo de origem**, identificados pelo endereço de onde o visitante veio. O formato dos links `/recomenda/apelido` não muda.
- Quadro "Cliques nos últimos 30 dias" na tela de edição de cada link.
- **Verificador de links quebrados**: roda uma vez por semana em segundo plano, com a coluna "Situação", o filtro "Com problema", um aviso na lista e as opções "Verificar destino" e "Verificar todos agora". Pode ser desligado em Ferramentas.
- Links da Amazon (amazon.*, amzn.to, link.amazon, a.co) não são verificados, porque a Amazon responde 404 a qualquer verificação automática. Eles aparecem como "não verificável". O filtro `recomenda_skip_check` permite incluir outras lojas.
- **Troca em massa** de trechos nas URLs de destino, por exemplo o código de afiliado, com pré-visualização antes de aplicar (Recomenda Links → Ferramentas).
- **Exportar e importar CSV** com as colunas `slug;titulo;destino;cliques;status;tipo`. A importação nunca apaga links e não altera o contador de links que já existem.
- Botões "copiar link" e "copiar shortcode" na lista e na tela de edição.
- Validação da URL de destino ao salvar e avisos de destino duplicado ou de redirecionamento em círculo.
- Atualização automática do banco na primeira execução, mesmo substituindo o arquivo sem desativar o plugin.

### Corrigido
- Ordenar por cliques não esconde mais os links que ainda não tiveram nenhum clique.
- A contagem de cliques ignora robôs, pré-visualizações de redes sociais e WhatsApp, pré-carregamento do navegador e usuários logados que editam o site.
- A contagem é atômica: acessos simultâneos não se perdem.
- A coluna "Destino" mostra a URL completa, quebrando a linha em vez de tentar cortá-la.
- O CSS do botão só é carregado nas páginas que usam o shortcode.
- Shortcode com `id` inexistente ou sem destino mostra só o texto ao visitante e um aviso para editores.

### Compatibilidade
- Os links, destinos e cliques já cadastrados são mantidos: o tipo de conteúdo e os campos continuam os mesmos desde a 1.0.0. Desativar o plugin não apaga nenhum dado.

## [1.1.1] - 2026-08-19

### Alterado
- Autoria do plugin atualizada para Marcelo Andrade — cabeçalho do plugin (`Plugin URI`, `Author`, `Author URI`), crédito no README e `Contributors` do readme.txt.
- `Tested up to` do readme.txt atualizado para o WordPress 7.0.4.

## [1.1.0] - 2026-08-19

### Adicionado
- Shortcode `[recomenda]` para inserir links ou botões nos artigos.
- Botão personalizável (`estilo="botao"`) com variáveis CSS e suporte a classes do tema.
- Atributos `rel="nofollow sponsored"` automáticos nos links do shortcode.

## [1.0.0] - 2026-08-19

### Adicionado
- Tipo de conteúdo para cadastrar links de afiliado.
- Redirecionamento centralizado `seusite.com/recomenda/apelido` → URL de destino.
- Contagem de cliques por link, com coluna ordenável na listagem.
- Redirecionamento 302 (temporário).

[1.4.0]: https://github.com/marcelovianaandrade/recomenda-links/releases/tag/v1.4.0
[1.3.0]: https://github.com/marcelovianaandrade/recomenda-links/releases/tag/v1.3.0
[1.1.1]: https://github.com/marcelovianaandrade/recomenda-links/releases/tag/v1.1.1
[1.1.0]: https://github.com/marcelovianaandrade/recomenda-links/releases/tag/v1.1.0
[1.0.0]: https://github.com/marcelovianaandrade/recomenda-links/releases/tag/v1.0.0
