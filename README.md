# Recomenda Links

Gerenciador de links de afiliado **centralizado** para WordPress.

Em vez de colar o link de afiliado direto em cada artigo, você cria um link "apelido" do seu próprio site — por exemplo `seusite.com/recomenda/furadeira-bosch` — que redireciona para o link de afiliado real. Se o afiliado cair ou mudar, você altera o destino **em um só lugar** e todos os artigos passam a apontar para o novo link automaticamente.

## Recursos

- Links centralizados com o prefixo `/recomenda/` (troca o destino em um lugar, aplica em todos os artigos).
- **Contagem de cliques** por link, com coluna ordenável na lista.
- Redirecionamento **302 (temporário)**, recomendado para links de afiliado.
- Sem dependências externas — um único arquivo PHP.

## Instalação

1. Baixe este repositório como `.zip` (botão **Code → Download ZIP**) ou clone com Git.
2. No painel do WordPress, vá em **Plugins → Adicionar novo → Enviar plugin** e envie o `.zip`.
   - Alternativa: copie a pasta para `wp-content/plugins/recomenda-links/`.
3. **Ative** o plugin.
4. Se algum link retornar "página não encontrada", vá em **Configurações → Links permanentes** e clique em **Salvar** uma vez.

## Como usar

1. No menu **Recomenda Links → Adicionar novo**.
2. Dê um **título** (nome interno, ex.: "Furadeira Bosch").
3. Cole o link de afiliado real no campo **URL de destino** e salve.
4. Use o endereço gerado (`seusite.com/recomenda/furadeira-bosch`) nos seus artigos.
5. Quando o afiliado mudar, edite a **URL de destino** e salve — pronto, aplicou em todos os artigos.

O apelido na URL vem do **slug** do link (editável ao lado do título).

## Requisitos

- WordPress 5.0+
- PHP 7.0+

## Licença

[GPL-2.0-or-later](LICENSE) — mesma licença do WordPress.
