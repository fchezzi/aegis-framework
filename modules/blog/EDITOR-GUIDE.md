# 📝 Guia do Editor TinyMCE - Blog AEGIS

## ✨ Recursos Disponíveis

### 1. **Formatação de Texto**
- **Negrito, Itálico, Sublinhado, Tachado**
- Títulos (H1, H2, H3, etc.)
- Alinhamento (esquerda, centro, direita, justificado)
- Listas numeradas e com marcadores
- Cores de texto e fundo

### 2. **Imagens** 🖼️

**Método 1: Upload via Botão**
1. Clique no botão "Imagem" (ícone de foto)
2. Escolha "Upload" na aba
3. Selecione a imagem do seu computador
4. Aguarde o upload automático
5. Ajuste tamanho/alinhamento se necessário

**Método 2: Arrastar e Soltar**
1. Arraste uma imagem do seu computador
2. Solte diretamente no editor
3. Upload automático!

**Método 3: Colar da Área de Transferência**
1. Copie uma imagem (Ctrl+C / Cmd+C)
2. Cole no editor (Ctrl+V / Cmd+V)
3. Upload automático!

**Formatos suportados:**
- JPG/JPEG
- PNG
- WEBP
- Limite: 5MB por imagem

### 3. **Vídeos do YouTube** 🎥

**Como Adicionar:**
1. Copie a URL do vídeo no YouTube
   - Exemplo: `https://www.youtube.com/watch?v=VIDEO_ID`
   - Ou: `https://youtu.be/VIDEO_ID`
2. Clique no botão "Mídia" (ícone de câmera/play)
3. Cole a URL na aba "Embed"
4. Clique em OK
5. O vídeo será incorporado automaticamente!

**Dica:** O vídeo aparecerá como iframe responsivo no post público.

### 4. **Links**
1. Selecione o texto
2. Clique no botão "Link" (ícone de corrente)
3. Digite a URL
4. Escolha se abre em nova aba
5. Salvar

### 5. **Tabelas**
- Criar tabelas com linhas/colunas
- Mesclar células
- Adicionar/remover linhas

### 6. **Código Fonte**
- Botão "Code" para ver/editar HTML diretamente
- Útil para ajustes finos

### 7. **Tela Cheia**
- Botão "Fullscreen" para editor em tela cheia
- Facilita edição de textos longos

---

## 🎯 Workflow Recomendado

### Criar Post Completo

1. **Título e Slug**
   - Digite o título → slug é gerado automaticamente
   - Ajuste o slug se necessário (SEO!)

2. **Introdução**
   - Resumo curto e direto (máx 350 caracteres)
   - Aparece nos cards de listagem

3. **Conteúdo Principal** (Editor TinyMCE)
   - Escreva o texto base
   - Adicione formatação (títulos, listas, etc.)
   - Insira imagens onde fizer sentido
   - Adicione vídeos do YouTube se relevante
   - Use links para referenciar fontes

4. **Imagem Destacada**
   - Upload da imagem principal (aparece no card)
   - Diferente das imagens inline do conteúdo

5. **Categoria**
   - Escolha categoria adequada
   - Define a URL SEO do post

6. **Preview**
   - Clique em "Ver Post" após salvar
   - Verifique como ficou no frontend

---

## 💡 Dicas Profissionais

### Imagens
- ✅ Use imagens otimizadas (WEBP quando possível)
- ✅ Redimensione antes do upload se muito grandes
- ✅ Use Alt Text para acessibilidade (TinyMCE permite adicionar)
- ❌ Evite imagens > 2MB

### Vídeos
- ✅ Incorporar do YouTube (não fazer upload!)
- ✅ Use URLs curtas (youtu.be) ou completas (youtube.com/watch)
- ✅ Vídeos não contam no limite de caracteres
- ❌ Não abuse - máximo 2-3 vídeos por post

### SEO
- ✅ Use títulos H2/H3 para estruturar conteúdo
- ✅ Primeiro parágrafo deve ter palavras-chave
- ✅ Links internos para outros posts
- ✅ Slug descritivo e curto

### Performance
- ✅ Introdução concisa (carrega rápido na lista)
- ✅ Imagens inline comprimidas
- ✅ Evite muitas imagens (3-5 é ideal)

---

## 🚨 Limites Técnicos

| Item | Limite |
|------|--------|
| Título | 255 caracteres |
| Slug | 255 caracteres |
| Introdução | 350 caracteres |
| Conteúdo | 100.000 caracteres (~16MB) |
| Imagem destacada | 5MB |
| Imagens inline | 5MB cada |

---

## 🐛 Problemas Comuns

**"Erro ao fazer upload da imagem"**
- Verifique se a imagem é JPG/PNG/WEBP
- Verifique se é menor que 5MB
- Tente comprimir a imagem

**"Vídeo não aparece"**
- Use apenas URLs do YouTube
- Formato aceito: `youtube.com/watch?v=` ou `youtu.be/`
- Não use URLs de outras plataformas (Vimeo, etc.)

**"Conteúdo muito longo"**
- Limite: 100.000 caracteres
- Divida em múltiplos posts se necessário
- Imagens em base64 contam no limite (use upload!)

---

## 📚 Atalhos de Teclado

| Ação | Atalho |
|------|--------|
| Negrito | Ctrl/Cmd + B |
| Itálico | Ctrl/Cmd + I |
| Sublinhado | Ctrl/Cmd + U |
| Desfazer | Ctrl/Cmd + Z |
| Refazer | Ctrl/Cmd + Y |
| Colar sem formatação | Ctrl/Cmd + Shift + V |
| Visualizar código | Botão "Code" |

---

**Versão:** 1.1.0
**Última atualização:** 2025-11-23
