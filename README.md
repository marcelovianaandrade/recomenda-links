# Recomenda Links

![Version](https://img.shields.io/badge/version-1.1.0-2563eb)
![WordPress](https://img.shields.io/badge/WordPress-5.0%2B-21759b)
![PHP](https://img.shields.io/badge/PHP-7.0%2B-777bb4)
![License](https://img.shields.io/badge/license-GPL--2.0--or--later-3da639)

Gerenciador de **links de afiliado centralizado** para WordPress.

Em vez de colar o link de afiliado direto em cada artigo, você cria um link "apelido" do seu próprio site — por exemplo `seusite.com/recomenda/furadeira-bosch` — que redireciona para o link de afiliado real. Se o afiliado cair ou mudar, você altera o destino **em um só lugar** e todos os artigos passam a apontar para o novo link automaticamente.

---

## ✨ Recursos

- **Links centralizados** com o prefixo `/recomenda/` — troca o destino em um lugar, aplica em todos os artigos.
- **Contagem de cliques** por link, com coluna ordenável na listagem.
- **Shortcode** `[recomenda]` para inserir link ou botão nos artigos.
- **Botão personalizável** para a identidade visual de qualquer site (variáveis CSS ou classes do seu tema).
- Atributos `rel="nofollow sponsored"` automáticos (recomendação do Google para afiliados).
- Redirecionamento **302 (temporário)**, ideal para links de afiliado.
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

## 🔗 Shortcode e botão

```text
Link de texto:
[recomenda id="furadeira-bosch"]Ver preço na loja[/recomenda]

Botão com o estilo padrão:
[recomenda id="furadeira-bosch" estilo="botao"]Comprar agora[/recomenda]

Botão com as classes de botão do seu tema:
[recomenda id="furadeira-bosch" estilo="botao" classe="wp-block-button__link"]Comprar[/recomenda]
```

| Atributo | Valores | Padrão | Descrição |
|----------|---------|--------|-----------|
| `id`     | apelido do link | — | **Obrigatório.** O slug do link. |
| `estilo` | `link` \| `botao` | `link` | Renderiza como texto ou botão. |
| `classe` | classes CSS | — | Classes extras (ex.: as do seu tema). |
| `rel`    | `on` \| `off` | `on` | Adiciona `rel="nofollow sponsored"`. |
| `target` | `_blank` \| vazio | `_blank` | Abre em nova aba. |

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
O contador incrementa a cada acesso à URL `/recomenda/...`. Para métricas de campanha, use em conjunto com sua ferramenta de analytics.

## 🗺️ Roadmap

- [ ] Relatório de cliques por período
- [ ] Importação/exportação de links em CSV
- [ ] Grupos/categorias de links

## 📜 Changelog

Veja [CHANGELOG.md](CHANGELOG.md).

## 📄 Licença

[GPL-2.0-or-later](LICENSE) — mesma licença do WordPress.

## 👤 Autor

Desenvolvido por **Cmosdrake** — [cmosdrake.com.br](https://cmosdrake.com.br)
