# Como Usar o Plugin

Para criar uma página via código, o usuário deve seguir estes passos:

1.  No menu do WordPress, acesse **Páginas > Criar Página HTML**.
2.  Ele será levado para a tela de criação de página, que carregará o Editor Clássico (mesmo que o Gutenberg esteja ativo). O editor principal (o `textarea` grande) ainda estará visível, mas o ambiente é mais simples e focado.
3.  Abaixo do editor, o usuário encontrará os três campos principais do plugin:
    *   **HTML Completo:** Para inserir a estrutura base do documento.
    *   **CSS Adicional:** Para estilizações.
    *   **JS Adicional:** Para scripts.

---

# Lógica de Controle com a Flag `_wcpc_is_code_page`

Para diferenciar uma página de código das páginas normais do WordPress, o plugin utiliza uma "flag" (uma etiqueta) nos metadados.

1.  **Criação e Flag:** Ao clicar em **Páginas > Criar Página HTML**, uma nova página é iniciada e a flag `_wcpc_is_code_page` com o valor `true` é adicionada à tabela `wp_postmeta` para essa página.

2.  **Primeiro Salvamento:** No primeiro salvamento da página, o plugin realiza duas ações importantes:
    *   Define automaticamente o template de página para `template-final.php`. Isso garante que a página sempre será renderizada corretamente, sem que o usuário precise selecionar o template manualmente.
    *   Executa a lógica de "compilação", combinando HTML, CSS e JS no campo `seu_campo_html_montado`.

3.  **Edição Futura:** Ao abrir o editor para qualquer página, o plugin verifica a existência da flag:
    *   Se `_wcpc_is_code_page` for `true`, ele força o carregamento do Editor Clássico. Isso garante que o usuário sempre tenha a experiência de edição correta para as páginas de código, independentemente do template selecionado ou do editor padrão do site (Gutenberg).

---

# Armazenamento html_montado

Considerando que você criou uma nova página chamada **"Minha Página de Marketing"**, que no banco de dados recebeu o `ID = 250`, a estrutura nas tabelas do WordPress ficaria exatamente assim:

---

### Tabela: `wp_posts`

Primeiro, uma única linha é criada nesta tabela para representar a página em si.

| ID | post_author | post_title | post_name | post_status | post_type |
| --- | --- | --- | --- | --- | --- |
| **250** | 1 | Minha Página de Marketing | minha-pagina-marketing | publish | page |
- O `ID = 250` é a chave que conecta esta página a todos os seus metadados.

---

### Tabela: `wp_postmeta`

Em seguida, para esta única página, **cinco linhas** seriam criadas nesta tabela. Uma para a flag, três para a sua "fonte da verdade" (os campos que você edita) e uma para o resultado "compilado" (o cache).

| meta_id | post_id | meta_key | meta_value (Conteúdo Abreviado) |
| --- | --- | --- | --- |
| 700 | **250** | `_wcpc_is_code_page` | `true` |
| 701 | **250** | `seu_campo_html_completo` | `<!DOCTYPE html>...<h1>Título</h1>...</body></html>` |
| 702 | **250** | `seu_campo_css_adicional` | `body { font-size: 16px; } .hero { background: #000; }` |
| 703 | **250** | `seu_campo_js_adicional` | `console.log('Página de Marketing Carregada');` |
| 704 | **250** | `seu_campo_html_montado` | `<!DOCTYPE html>...<style>...</style></head>...<script>...</script></body></html>` |

### Detalhe do Conteúdo da Linha `meta_id = 704` (o resultado montado):

O campo `meta_value` para a `meta_key` **`seu_campo_html_montado`** conteria a string de texto completa e já processada, algo assim:

HTML

'''html
<!DOCTYPE html>
<html>
<head>
    <title>Título</title>

<style>
body { font-size: 16px; } .hero { background: #000; }
</style>
</head>
<body>
    <h1>Título</h1>

<script>
console.log('Página de Marketing Carregada');
</script>
</body>
</html>
'''

### Resumo do Fluxo nas Tabelas:

1. **Edição:** Quando você edita a página, você está alterando os valores das linhas `701`, `702` ou `703`.
2. **Salvamento (`save_post`):** Ao clicar em "Atualizar", o seu plugin lê o conteúdo das linhas `701`, `702` e `703`, monta o HTML final e **sobrescreve** o conteúdo da linha `704`.
3. **Visita do Usuário:** Quando um visitante acessa a URL, o seu template (`template-final.php`) ignora as linhas `700`, `701`, `702` e `703`. Ele faz uma única e rápida busca pelo conteúdo da linha `704` e o exibe.
