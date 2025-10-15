# Como Usar o Sistema Block HTML

Para criar um Block HTML via código, o usuário deve seguir estes passos:

1. No menu do WordPress, acesse **Block HTML > Adicionar Novo**.
2. Ele será levado para a tela de criação de block HTML, que carregará o Editor Clássico (mesmo que o Gutenberg esteja ativo). O editor principal (o `textarea` grande) ainda estará visível, mas o ambiente é mais simples e focado.
3. Abaixo do editor, o usuário encontrará os três campos principais do sistema:
   * **HTML do Block:** Para inserir a estrutura HTML do componente.
   * **CSS do Block:** Para estilizações específicas do block.
   * **JS do Block:** Para scripts específicos do block.
4. Na sidebar direita, há dois metaboxes importantes:
   * **"Shortcode"** que exibe o código para inserir o block em qualquer lugar.
   * **"Status do Block"** que permite ativar/desativar a renderização do block.

---

# Sistema de Ativação/Desativação

O sistema Block HTML possui controle de ativação que permite desabilitar temporariamente um block sem precisar deletá-lo:

## Metabox "Status do Block":
* **Checkbox "Block Ativo":** Controla se o block será renderizado quando o shortcode for chamado
* **Padrão:** Novos blocks são criados como **ativos** por padrão
* **Comportamento:** Quando desativado, o shortcode retorna vazio (não renderiza nada)

## Vantagens:
* **Manutenção:** Desative blocks temporariamente durante manutenções
* **Testes:** Desative blocks em produção para testar alterações
* **Controle:** Mantenha o block salvo mas impeça sua exibição
* **Limpeza:** O shortcode desaparece completamente da página quando desativado

---

# Lógica de Controle com a Flag `_wcpc_is_block_html`

Para diferenciar um block HTML de código dos posts normais do WordPress, o plugin utiliza uma "flag" (uma etiqueta) nos metadados.

1. **Criação e Flag:** Ao clicar em **Block HTML > Adicionar Novo**, um novo post do tipo `wcpc_block_html` é iniciado e a flag `_wcpc_is_block_html` com o valor `true` é adicionada à tabela `wp_postmeta` para esse post.

2. **Primeiro Salvamento:** No primeiro salvamento do block HTML, o plugin realiza uma ação importante:
   * Executa a lógica de "compilação", combinando HTML, CSS e JS no campo `_wcpc_block_compiled`.

3. **Edição Futura:** Ao abrir o editor para qualquer block HTML, o plugin verifica a existência da flag:
   * Se `_wcpc_is_block_html` for `true`, ele força o carregamento do Editor Clássico. Isso garante que o usuário sempre tenha a experiência de edição correta para os blocks HTML de código, independentemente do editor padrão do site (Gutenberg).

---

# Sistema de Shortcode

O sistema de Block HTML funciona através de shortcodes que podem ser inseridos em qualquer lugar do WordPress:

## Shortcode Gerado:

Cada block HTML possui um shortcode único no formato:
```
[wcpc_block id="123"]
```

Onde `123` é o ID do block HTML criado.

## Como Usar o Shortcode:

* **Em Posts/Páginas:** Cole o shortcode diretamente no editor de conteúdo
* **Em Widgets:** Use em widgets de texto que suportam shortcodes
* **Em Templates:** Use `do_shortcode('[wcpc_block id="123"]')` em arquivos PHP
* **Em Páginas WCPC:** Cole o shortcode diretamente no campo HTML

---

# Estrutura de Renderização

Quando um shortcode `[wcpc_block id="123"]` é processado, o HTML renderizado terá esta estrutura:

```html
<style>
/* CSS minificado do block */
</style>

<div>
<!-- HTML do block -->
</div>

<script>
/* JS minificado do block */
</script>
```

## Exemplo Prático:

Se você criar um block com:
- **HTML:** `<h2>Meu Título</h2><p>Meu conteúdo</p>`
- **CSS:** `h2 { color: red; } p { font-size: 16px; }`
- **JS:** `console.log('Block carregado');`

O resultado renderizado será:
```html
<style>h2{color:red;}p{font-size:16px;}</style>

<div><h2>Meu Título</h2><p>Meu conteúdo</p></div>

<script>console.log('Block carregado');</script>
```

---

# Armazenamento wcpc_block_compiled

Considerando que você criou um novo block HTML chamado **"Botão de Call-to-Action"**, que no banco de dados recebeu o `ID = 450`, a estrutura nas tabelas do WordPress ficaria exatamente assim:

---

### Tabela: `wp_posts`

Primeiro, uma única linha é criada nesta tabela para representar o block HTML em si.

| ID | post_author | post_title | post_name | post_status | post_type |
| --- | --- | --- | --- | --- | --- |
| **450** | 1 | Botão de Call-to-Action | botao-de-call-to-action | publish | wcpc_block_html |

- O `ID = 450` é a chave que conecta este block HTML a todos os seus metadados.
- O `post_type = wcpc_block_html` identifica que é um Custom Post Type do sistema.

---

### Tabela: `wp_postmeta`

Em seguida, para este único block HTML, **cinco linhas** seriam criadas nesta tabela. Uma para a flag, três para a sua "fonte da verdade" (os campos que você edita) e uma para o resultado "compilado" (o cache).

| meta_id | post_id | meta_key | meta_value (Conteúdo Abreviado) |
| --- | --- | --- | --- |
| 900 | **450** | `_wcpc_is_block_html` | `true` |
| 901 | **450** | `_wcpc_block_html` | `<button class="cta-btn">Clique Aqui!</button>` |
| 902 | **450** | `_wcpc_block_css` | `.cta-btn { background: #ff6b35; color: white; padding: 15px 30px; border: none; border-radius: 5px; cursor: pointer; }` |
| 903 | **450** | `_wcpc_block_js` | `document.querySelector('.cta-btn').addEventListener('click', function(){ alert('Botão clicado!'); });` |
| 904 | **450** | `_wcpc_block_compiled` | `<style>.cta-btn{background:#ff6b35;color:white;padding:15px 30px;border:none;border-radius:5px;cursor:pointer;}</style><div><button class="cta-btn">Clique Aqui!</button></div><script>document.querySelector('.cta-btn').addEventListener('click',function(){alert('Botão clicado!');});</script>` |

### Detalhe do Conteúdo da Linha `meta_id = 904` (o resultado montado):

O campo `meta_value` para a `meta_key` **`_wcpc_block_compiled`** conteria a string de texto completa e já processada, algo assim:

```html
<style>
.cta-btn{background:#ff6b35;color:white;padding:15px 30px;border:none;border-radius:5px;cursor:pointer;}
</style>

<div>
<button class="cta-btn">Clique Aqui!</button>
</div>

<script>
document.querySelector('.cta-btn').addEventListener('click',function(){alert('Botão clicado!');});
</script>
```

### Resumo do Fluxo nas Tabelas:

1. **Edição:** Quando você edita o block HTML, você está alterando os valores das linhas `901`, `902` ou `903`.
2. **Salvamento (`save_post`):** Ao clicar em "Publicar/Atualizar", o plugin lê o conteúdo das linhas `901`, `902` e `903`, monta o HTML final compilado e **sobrescreve** o conteúdo da linha `904`.
3. **Renderização via Shortcode:** Quando um shortcode `[wcpc_block id="450"]` é processado, o sistema busca o conteúdo compilado da linha `904` e o renderiza na página.

---

# Fluxo de Funcionamento

## 1. Criação de Block HTML:
1. Acesse **WP Admin → Block HTML → Adicionar Novo**
2. Defina o **Título** do block HTML
3. Adicione o código **HTML**, **CSS** e **JS** nos respectivos campos
4. **Publique** o block HTML
5. Copie o **shortcode** gerado no metabox da sidebar

## 2. Uso do Shortcode:
1. Cole o shortcode em qualquer post, página ou widget
2. O shortcode será processado automaticamente e renderizará o block compilado
3. O block aparecerá com todos os estilos e funcionalidades JavaScript

## 3. Compilação e Renderização:
1. Quando o block é salvo, o sistema:
   - Minifica o CSS removendo espaços e comentários
   - Minifica o JavaScript removendo espaços e comentários
   - Combina tudo no formato: `<style>CSS</style><div>HTML</div><script>JS</script>`
   - Salva o resultado compilado em `_wcpc_block_compiled`

2. Quando o shortcode é processado:
   - Busca o conteúdo compilado do block
   - Renderiza diretamente na página sem processamento adicional

---

# Casos de Uso Práticos

## 1. Botões de Call-to-Action:
```html
<!-- HTML -->
<button class="cta-button">Baixar Agora</button>

<!-- CSS -->
.cta-button {
    background: linear-gradient(45deg, #ff6b35, #f7931e);
    color: white;
    padding: 15px 30px;
    border: none;
    border-radius: 25px;
    font-weight: bold;
    cursor: pointer;
    transition: transform 0.3s ease;
}
.cta-button:hover {
    transform: scale(1.05);
}

<!-- JS -->
document.querySelector('.cta-button').addEventListener('click', function() {
    gtag('event', 'click', {
        'event_category': 'CTA',
        'event_label': 'Download Button'
    });
});
```

## 2. Cards de Produto:
```html
<!-- HTML -->
<div class="product-card">
    <img src="/path/to/image.jpg" alt="Produto">
    <h3>Nome do Produto</h3>
    <p class="price">R$ 99,90</p>
    <button class="buy-btn">Comprar</button>
</div>

<!-- CSS -->
.product-card {
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    text-align: center;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}
.product-card img {
    width: 100%;
    height: 200px;
    object-fit: cover;
}
.price {
    font-size: 24px;
    color: #ff6b35;
    font-weight: bold;
}

<!-- JS -->
document.querySelector('.buy-btn').addEventListener('click', function() {
    window.location.href = '/checkout?product=123';
});
```

## 3. Formulários Customizados:
```html
<!-- HTML -->
<form class="custom-form">
    <input type="email" placeholder="Seu e-mail" required>
    <button type="submit">Inscrever-se</button>
</form>

<!-- CSS -->
.custom-form {
    display: flex;
    gap: 10px;
    max-width: 400px;
}
.custom-form input {
    flex: 1;
    padding: 12px;
    border: 2px solid #ddd;
    border-radius: 4px;
}
.custom-form button {
    background: #007cba;
    color: white;
    border: none;
    padding: 12px 20px;
    border-radius: 4px;
    cursor: pointer;
}

<!-- JS -->
document.querySelector('.custom-form').addEventListener('submit', function(e) {
    e.preventDefault();
    const email = this.querySelector('input').value;
    // Enviar para API de newsletter
    fetch('/api/newsletter', {
        method: 'POST',
        body: JSON.stringify({email: email})
    });
});
```

---

# Integração com Sistema de Prioridades

O Block HTML está integrado ao sistema de prioridades do plugin com a chave `'block-html'` que possui prioridade `10`, garantindo que os blocks sejam carregados antes de outros componentes quando usados em páginas WCPC.

---

# Estrutura de Arquivos

```
wp-code-page-creator/
├── includes/
│   ├── class-admin.php (lógica principal das páginas)
│   ├── class-header-footer.php (headers/footers)
│   └── class-block-html.php (nova classe para blocks HTML)
├── templates/
│   └── template-final.php (template das páginas)
└── docs/
    ├── wp-code-page-creator.md
    ├── wcpc-header-footer.md
    └── wcpc-block-html.md (este arquivo)
```

A nova classe `class-block-html.php` é responsável por:
- Registrar o Custom Post Type `wcpc_block_html`
- Criar os metaboxes para HTML, CSS, JS e Shortcode
- Implementar a lógica de compilação específica para blocks HTML
- Processar o shortcode `[wcpc_block]` para renderização
- Fornecer interface administrativa completa para gerenciamento dos blocks