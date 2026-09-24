# Recomenda Links

![Version](https://img.shields.io/badge/version-1.4.0-2563eb)
![WordPress](https://img.shields.io/badge/WordPress-testado%207.0.4-21759b)
![PHP](https://img.shields.io/badge/PHP-7.0%2B-777bb4)
![License](https://img.shields.io/badge/license-GPL--2.0--or--later-3da639)

Gerenciador de **links de afiliado centralizado** para WordPress.

Em vez de colar o link de afiliado direto em cada artigo, você cria um link "apelido" do seu próprio site — por exemplo `seusite.com/recomenda/furadeira-bosch` — que redireciona para o link de afiliado real. Se o afiliado cair ou mudar, você altera o destino **em um só lugar** e todos os artigos passam a apontar para o novo link automaticamente.

---

## ✨ Recursos

- **Links centralizados** com o prefixo `/recomenda/` — troca o destino em um lugar, aplica em todos os artigos.
- **Contagem de cliques** por link, com coluna ordenável na listagem.
- **Shortcode** `[recomenda]` para inserir link, botão ou **card do produto** (imagem, título, descrição e botão) nos artigos.
- **Imagem e descrição** em cada link, com miniatura na lista.
- **Botão personalizável** para a identidade visual de qualquer site (variáveis CSS ou classes do seu tema).
- Atributos `rel="nofollow sponsored"` automáticos (recomendação do Google para afiliados).
- Redirecionamento **302 (temporário)**, ideal para links de afiliado.
- **Relatórios** de cliques por dia (7, 30 ou 90 dias) e por **artigo de origem**.
- **Tipos de link** (Serras, Brocas, Plainas…) com filtro na lista e nos relatórios.
- **Verificador de links quebrados** semanal, com coluna "Situação" e filtro "Com problema".
- **Troca em massa** do código de afiliado em todas as URLs de destino, com pré-visualização.
- **Exportar e importar CSV**, que também serve de backup.
- Botões **copiar link**, **copiar shortcode** e **copiar card**.
- Contagem que ignora robôs, pré-visualizações e quem edita o site.
- Leve e sem dependências externas — um único arquivo PHP.

## 📦 Instalação

1. Baixe a versão mais recente em [Releases](../../releases) ou clique em **Code → Download ZIP**.
2. No painel do WordPress, vá em **Plugins → Adicionar novo → Enviar plugin** e envie o `.zip`.
   - Alternativa: copie a pasta para `wp-content/plugins/recomenda-links/`.
3. **Ative** o plugin.
4. Se algum link retornar "página não encontrada", vá em **Configurações → Links permanentes** e clique em **Salvar** uma vez.

## 🚀 Como usar

1. Vá em **Recomenda Links → Adicionar novo**.
2. Dê um **título** (nome interno, ex.: "Furadeira Bosch").
3. Cole o link de afiliado real no campo **URL de destino** e salve.
4. Use o endereço gerado (`seusite.com/recomenda/furadeira-bosch`) nos seus artigos.
5. Quando o afiliado mudar, edite a **URL de destino** e salve — pronto, aplicou em todos os artigos.

O apelido na URL vem do **slug** do link (editável ao lado do título).

## 🗂️ Tipos de link

Em **Recomenda Links → Tipos**, crie os tipos (por exemplo Serras, Brocas, Plainas). Eles aceitam subtipos, como "Serras › Serra circular". Depois:

- marque o tipo na tela do link, ou use **Edição rápida** ou **Editar em massa** para vários links de uma vez;
- filtre a lista pelo seletor **Todos os tipos**, ou clique no nome do tipo na coluna "Tipos";
- veja os **Cliques por tipo** e filtre o relatório por tipo em **Relatórios**.

Para classificar muitos links de uma vez, exporte o CSV, preencha a coluna `tipo` e importe com a opção **atualizar título e destino**.

## 📊 Relatórios e ferramentas

- **Recomenda Links → Relatórios**: gráfico de cliques por dia, links mais clicados e artigos que mais geram cliques. Clique num link para ver de quais artigos vêm os cliques dele.
- **Recomenda Links → Ferramentas**:
  - *Trocar código de afiliado em massa*: por exemplo, trocar `tag=antigo-20` por `tag=novo-20` em todos os destinos.
  - *Exportar/Importar CSV*: colunas `slug;titulo;destino;cliques;status;tipo;descricao;imagem`. A importação nunca apaga links.
  - *Verificar todos agora*: dispara a verificação de links quebrados sem esperar a rotina semanal.

> Os cliques por dia e por artigo começam a ser registrados a partir da versão 1.3.0. O total de cada link, contado antes, é mantido.

## 🔗 Shortcode e botão

```text
Link de texto:
[recomenda id="furadeira-bosch"]Ver preço na loja[/recomenda]

Botão com o estilo padrão:
[recomenda id="furadeira-bosch" estilo="botao"]Comprar agora[/recomenda]

Botão com as classes de botão do seu tema:
[recomenda id="furadeira-bosch" estilo="botao" classe="wp-block-button__link"]Comprar[/recomenda]

Card do produto (imagem, título, descrição e botão):
[recomenda id="furadeira-bosch" estilo="card"]Ver na loja[/recomenda]
```

| Atributo | Valores | Padrão | Descrição |
|----------|---------|--------|-----------|
| `id`     | apelido do link | — | **Obrigatório.** O slug do link. |
| `estilo` | `link` \| `botao` \| `card` | `link` | Renderiza como texto, botão ou card do produto. |
| `titulo` | texto | título do link | *(card)* Título exibido no card. |
| `descricao` | texto | descrição do link | *(card)* Descrição exibida no card. |
| `imagem` | `on` \| `off` | `on` | *(card)* Mostra ou esconde a imagem. |
| `classe` | classes CSS | — | Classes extras (ex.: as do seu tema). |
| `rel`    | `on` \| `off` | `on` | Adiciona `rel="nofollow sponsored"`. |
| `target` | `_blank` \| vazio | `_blank` | Abre em nova aba. |

### 🖼️ Imagem, descrição e card do produto

Na tela de cada link:
- **Imagem do produto** (quadro ao lado): envie uma foto pela biblioteca de mídia.
- **URL da imagem**: alternativa sem enviar arquivo. Para produtos da Amazon, use o link de imagem gerado pela barra **SiteStripe**. Se as duas existirem, vale a Imagem do produto.
- **Descrição curta**: até cerca de 200 caracteres.

O card se adapta ao celular: a imagem vai para cima e o texto fica embaixo. Para mudar o visual, use o *CSS adicional*:

```css
.recomenda-card{
    --recomenda-card-borda:#e5e7eb;   /* cor da borda */
    --recomenda-card-fundo:#ffffff;   /* fundo do card */
    --recomenda-card-radius:12px;     /* arredondamento */
    --recomenda-card-img:160px;       /* tamanho da imagem */
}
```

O botão do card usa o mesmo estilo `.recomenda-btn`, ou as classes do seu tema via `classe="..."`.

### 🎨 Personalizar o visual do botão

Três formas, da mais simples à mais completa:

1. **Trocar as cores** em *Aparência → Personalizar → CSS adicional*:

   ```css
   .recomenda-btn{
       --recomenda-bg:#e11d48;      /* cor de fundo */
       --recomenda-cor:#ffffff;     /* cor do texto */
       --recomenda-radius:4px;      /* arredondamento */
       --recomenda-padding:14px 28px;
   }
   ```

2. **Reescrever o estilo inteiro** mirando `.recomenda-btn` no CSS do tema.

3. **Usar as classes do próprio tema** pelo atributo `classe="..."`, para o botão herdar exatamente a identidade visual do site.

## ❓ FAQ

**Preciso usar o shortcode?**
Não. O benefício central — trocar o link em um lugar só — funciona igual colando a URL `/recomenda/...` direto no texto. O shortcode é uma comodidade para padronizar o visual.

**O redirecionamento afeta o SEO?**
O link que aparece no artigo é interno (do seu domínio). O destino de afiliado fica no redirecionamento 302, que não é indexado. O shortcode ainda aplica `rel="nofollow sponsored"` por padrão.

**Os cliques contam visitas de robôs?**
Não. A contagem ignora robôs de busca, pré-visualizações (WhatsApp, Facebook, Telegram…), pré-carregamento do navegador e usuários logados que editam o site. Para testar, use uma janela anônima.

**Vou perder meus links ao atualizar?**
Não. Os links, destinos e cliques continuam no banco do WordPress com os mesmos nomes de campos. Atualize substituindo o plugin (sem desinstalar) e faça um backup do banco antes, por segurança.

**Meus links da Amazon aparecem como quebrados. O que fazer?**
A Amazon responde erro 404 a qualquer verificação automática, mesmo com o link funcionando. Por isso, links `amazon.*`, `amzn.to`, `link.amazon` e `a.co` não são verificados e aparecem como "não verificável". Se todos os seus links forem da Amazon, você pode desligar o verificador em **Ferramentas**.

**Um link aparece como "Não confirmado". Está quebrado?**
Não necessariamente. Algumas lojas, como a Amazon, bloqueiam verificações automáticas. Teste o link manualmente. "Quebrado" só aparece para página inexistente (404/410) ou site fora do ar em duas verificações seguidas.

## 🗺️ Roadmap

- [x] Relatório de cliques por período
- [x] Importação/exportação de links em CSV
- [x] Grupos/categorias de links (tipos)
- [ ] URL reserva para ofertas expiradas

## 📜 Changelog

Veja [CHANGELOG.md](CHANGELOG.md).

## 📄 Licença

[GPL-2.0-or-later](LICENSE) — mesma licença do WordPress.

## 👤 Autor

Desenvolvido por **Marcelo Andrade** — [projetowebstudio.com.br](https://projetowebstudio.com.br)
