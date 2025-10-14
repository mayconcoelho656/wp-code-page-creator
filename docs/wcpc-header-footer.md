# Como Usar o Sistema Header/Footer

Para criar um Header ou Footer via código, o usuário deve seguir estes passos:

1. No menu do WordPress, acesse **Headers/Footers > Adicionar Novo**.
2. Ele será levado para a tela de criação de header/footer, que carregará o Editor Clássico (mesmo que o Gutenberg esteja ativo). O editor principal (o `textarea` grande) ainda estará visível, mas o ambiente é mais simples e focado.
3. Abaixo do editor, o usuário encontrará os quatro campos principais do sistema:
   * **HTML Completo:** Para inserir a estrutura HTML do header/footer.
   * **CSS Adicional:** Para estilizações específicas.
   * **JS Adicional:** Para scripts específicos.
   * **Type:** Radio buttons para selecionar se é "Header" ou "Footer".

---

# Lógica de Controle com a Flag `_wcpc_is_header_footer`

Para diferenciar um header/footer de código dos posts normais do WordPress, o plugin utiliza uma "flag" (uma etiqueta) nos metadados.

1. **Criação e Flag:** Ao clicar em **Headers/Footers > Adicionar Novo**, um novo post do tipo `wcpc_header_footer` é iniciado e a flag `_wcpc_is_header_footer` com o valor `true` é adicionada à tabela `wp_postmeta` para esse post.

2. **Primeiro Salvamento:** No primeiro salvamento do header/footer, o plugin realiza uma ação importante:
   * Executa a lógica de "compilação", combinando HTML, CSS e JS no campo `wcpc_hf_compiled`.

3. **Edição Futura:** Ao abrir o editor para qualquer header/footer, o plugin verifica a existência da flag:
   * Se `_wcpc_is_header_footer` for `true`, ele força o carregamento do Editor Clássico. Isso garante que o usuário sempre tenha a experiência de edição correta para os headers/footers de código, independentemente do editor padrão do site (Gutenberg).

---

# Sistema de Renderização nas Páginas

O sistema de Header/Footer funciona através de um metabox específico nas páginas WCPC que permite selecionar individualmente quais headers e footers serão utilizados:

## Metabox Header-Footer nas Páginas:

Cada página WCPC possui um metabox **"Header-Footer"** na sidebar direita com:

* **Checkbox "Ativar Header"** + **Seletor de Header:** Permite escolher qual header será injetado
* **Checkbox "Ativar Footer"** + **Seletor de Footer:** Permite escolher qual footer será injetado

## Campos de Controle por Página:

* **`_wcpc_enable_header`:** Checkbox para ativar/desativar header (1/0)
* **`_wcpc_selected_header`:** ID do header selecionado
* **`_wcpc_enable_footer`:** Checkbox para ativar/desativar footer (1/0)
* **`_wcpc_selected_footer`:** ID do footer selecionado

## Estrutura de Renderização:

Quando uma página WCPC é compilada, o HTML final terá esta estrutura:

```html
<!DOCTYPE html>
<html>
<head>
    <!-- Conteúdo original do head -->
    
    {{wcpc_head}}
    
    <style>
    /* CSS minificado da página */
    </style>

</head>
<body>
    [CONTEÚDO COMPILADO DO HEADER SELECIONADO]
    
    <!-- Conteúdo HTML da página -->
    
    [CONTEÚDO COMPILADO DO FOOTER SELECIONADO]
    
    <script>
    /* JS minificado da página */
    </script>
    
    {{wcpc_footer}}
</body>
</html>
```

## Conteúdo Renderizado pelo Header/Footer:

Cada header/footer compilado será renderizado no seguinte formato:

```html
<style>
/* CSS minificado do header/footer */
</style>

<section>
<!-- HTML do header/footer -->
</section>

<script>
/* JS minificado do header/footer */
</script>
```

---

# Armazenamento wcpc_hf_compiled

Considerando que você criou um novo header chamado **"Header Principal"**, que no banco de dados recebeu o `ID = 350`, a estrutura nas tabelas do WordPress ficaria exatamente assim:

---

### Tabela: `wp_posts`

Primeiro, uma única linha é criada nesta tabela para representar o header/footer em si.

| ID | post_author | post_title | post_name | post_status | post_type |
| --- | --- | --- | --- | --- | --- |
| **350** | 1 | Header Principal | header-principal | publish | wcpc_header_footer |

- O `ID = 350` é a chave que conecta este header/footer a todos os seus metadados.
- O `post_type = wcpc_header_footer` identifica que é um Custom Post Type do sistema.

---

### Tabela: `wp_postmeta`

Em seguida, para este único header/footer, **seis linhas** seriam criadas nesta tabela. Uma para a flag, quatro para a sua "fonte da verdade" (os campos que você edita) e uma para o resultado "compilado" (o cache).

| meta_id | post_id | meta_key | meta_value (Conteúdo Abreviado) |
| --- | --- | --- | --- |
| 800 | **350** | `_wcpc_is_header_footer` | `true` |
| 801 | **350** | `wcpc_hf_html` | `<nav class="main-nav"><ul><li>Home</li><li>Sobre</li></ul></nav>` |
| 802 | **350** | `wcpc_hf_css` | `.main-nav { background: #333; } .main-nav ul { list-style: none; }` |
| 803 | **350** | `wcpc_hf_js` | `document.querySelector('.main-nav').addEventListener('click', function(){});` |
| 804 | **350** | `wcpc_hf_type` | `header` |
| 805 | **350** | `wcpc_hf_compiled` | `<style>.main-nav{background:#333;}.main-nav ul{list-style:none;}</style><section><nav class="main-nav">...</nav></section><script>document.querySelector('.main-nav').addEventListener('click',function(){});</script>` |

### Detalhe do Conteúdo da Linha `meta_id = 805` (o resultado montado):

O campo `meta_value` para a `meta_key` **`wcpc_hf_compiled`** conteria a string de texto completa e já processada, algo assim:

```html
<style>
.main-nav{background:#333;}.main-nav ul{list-style:none;}
</style>

<section>
<nav class="main-nav">
    <ul>
        <li>Home</li>
        <li>Sobre</li>
    </ul>
</nav>
</section>

<script>
document.querySelector('.main-nav').addEventListener('click',function(){});
</script>
```

### Resumo do Fluxo nas Tabelas:

1. **Edição:** Quando você edita o header/footer, você está alterando os valores das linhas `801`, `802`, `803` ou `804`.
2. **Salvamento (`save_post`):** Ao clicar em "Publicar/Atualizar", o plugin lê o conteúdo das linhas `801`, `802` e `803`, monta o HTML final compilado e **sobrescreve** o conteúdo da linha `805`.
3. **Renderização na Página:** Quando uma página WCPC é compilada, o sistema busca todos os headers/footers ativos, lê o conteúdo compilado da linha `805` e injeta nas variáveis `{{header}}` e `{{footer}}` conforme o tipo definido na linha `804`.

---

# Fluxo de Funcionamento

## 1. Criação de Header/Footer:
1. Acesse **WP Admin → WCPC Header/Footer → Adicionar Novo**
2. Defina o **Título** do header/footer
3. Selecione o **Tipo** (Header ou Footer)
4. Adicione o código **HTML**, **CSS** e **JS**
5. **Publique** o header/footer

## 2. Configuração nas Páginas:
1. Edite uma página WCPC existente
2. No metabox **"Header-Footer"** (sidebar direita):
   - Marque **"Ativar Header"** e selecione um header da lista
   - Marque **"Ativar Footer"** e selecione um footer da lista
3. **Salve** a página

## 3. Compilação e Renderização:
1. Quando a página é salva, o sistema:
   - Verifica se header/footer estão habilitados
   - Busca o conteúdo compilado dos headers/footers selecionados
   - Injeta o conteúdo nos locais corretos da página
   - Compila e minifica todo o código final

---

# Fluxo de Compilação e Minificação

## Headers/Footers (CPT):
1. **Salvamento:** Quando um header/footer é salvo, o código HTML, CSS e JS são armazenados separadamente
2. **Compilação:** O sistema compila automaticamente o código final juntando HTML + CSS minificado + JS minificado
3. **Armazenamento:** O código compilado é salvo em `_wcpc_hf_compiled`

## Páginas WCPC:
1. **Verificação:** Durante o salvamento da página, verifica se headers/footers estão habilitados
2. **Busca:** Obtém o conteúdo compilado dos headers/footers selecionados
3. **Injeção:** Substitui as posições de header e footer pelo conteúdo compilado
4. **Compilação Final:** Compila a página completa com headers/footers integrados
5. **Minificação:** CSS e JS da página são minificados
6. **Armazenamento:** Página final compilada é salva em `_wcpc_compiled_html`

---

# Estrutura de Arquivos

```
wp-code-page-creator/
├── includes/
│   ├── class-admin.php (lógica principal das páginas)
│   └── class-header-footer.php (nova classe para headers/footers)
├── templates/
│   └── template-final.php (template das páginas)
└── docs/
    ├── wp-code-page-creator.md
    └── wcpc-header-footer.md (este arquivo)
```

A nova classe `class-header-footer.php` será responsável por:
- Registrar o Custom Post Type `wcpc_header_footer`
- Criar os metaboxes para HTML, CSS, JS e Type
- Implementar a lógica de compilação específica para headers/footers
- Fornecer métodos para buscar e renderizar headers/footers nas páginas