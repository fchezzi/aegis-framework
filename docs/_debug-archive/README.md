# 🔍 DEBUG - Scripts de Diagnóstico

> **Pasta para scripts de debug e diagnóstico do sistema**
>
> Scripts aqui NÃO são para produção. Use apenas em desenvolvimento.

---

## 📂 Estrutura

```
debug/
├── README.md                  → Este arquivo
├── debug-php-binary.php       → Diagnosticar caminho do PHP
└── [outros scripts de debug]
```

---

## 🎯 Scripts Disponíveis

### 1. debug-php-binary.php
**O que faz:** Verifica qual caminho do PHP está disponível no servidor web

**Quando usar:**
- Erro "php: command not found"
- Erro "sh: -l: command not found"
- Validação de sintaxe não funciona

**Como usar:**
```
http://localhost:5757/futebol-energia/debug/debug-php-binary.php
```

**Output esperado:**
- Mostra valor de `PHP_BINARY`
- Testa `exec()` com diferentes caminhos
- Detecta se MAMP está instalado

---

## 🛡️ Segurança

**IMPORTANTE:** Scripts de debug podem expor informações sensíveis do servidor.

### Proteção Recomendada

Adicione no início de cada script de debug:

```php
<?php
// 🛡️ SEGURANÇA: Só permitir em desenvolvimento
if ($_SERVER['SERVER_NAME'] !== 'localhost' &&
    !str_starts_with($_SERVER['SERVER_NAME'], '127.0.0.1')) {
    http_response_code(403);
    die('Acesso negado');
}
```

Ou adicione no `.htaccess`:

```apache
# Bloquear acesso à pasta debug em produção
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteCond %{HTTP_HOST} !^localhost$ [NC]
    RewriteCond %{HTTP_HOST} !^127\.0\.0\.1$ [NC]
    RewriteRule ^debug/ - [F,L]
</IfModule>
```

---

## 📝 Convenções

### Nomenclatura
- **Formato:** `debug-{funcionalidade}.php`
- **Exemplos:**
  - `debug-php-binary.php` → Diagnosticar PHP
  - `debug-database.php` → Testar conexão DB
  - `debug-permissions.php` → Verificar permissões de arquivos
  - `debug-modules.php` → Listar módulos instalados

### Template de Script

```php
<?php
/**
 * DEBUG: [Descrição]
 *
 * O que testa: [explicação]
 * Quando usar: [cenário]
 */

// 🛡️ Segurança
if ($_SERVER['SERVER_NAME'] !== 'localhost' &&
    !str_starts_with($_SERVER['SERVER_NAME'], '127.0.0.1')) {
    http_response_code(403);
    die('Acesso negado');
}

echo "<h1>DEBUG: [Título]</h1>";
echo "<pre>";

// Testes aqui...

echo "</pre>";
```

---

## 🚨 Regras

1. **NUNCA commitar em produção** - Adicionar `/debug/` no `.gitignore`
2. **Scripts temporários** - Delete após resolver o problema
3. **Nomenclatura clara** - Nome deve explicar o que testa
4. **Documentar uso** - Atualizar este README ao criar novo script
5. **Proteção obrigatória** - Sempre bloquear acesso em produção

---

## 📋 Checklist ao Criar Script de Debug

- [ ] Nome segue padrão `debug-*.php`
- [ ] Tem proteção contra acesso em produção
- [ ] Documentado neste README
- [ ] Output formatado em `<pre>` ou HTML legível
- [ ] Comentário explica o que testa
- [ ] Deletar após resolver o problema (opcional)

---

**Última atualização:** 2026-01-16
