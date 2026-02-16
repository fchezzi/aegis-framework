# Problemas Conhecidos

Soluções rápidas para erros comuns.

## UUID - NUNCA auto_increment

```sql
-- ERRADO
id INT AUTO_INCREMENT PRIMARY KEY

-- CERTO
id CHAR(36) PRIMARY KEY
```

```php
$id = Core::generateUUID();
```

## FK para admins - Tabela é `users`

```sql
-- ERRADO
REFERENCES admins(id)

-- CERTO
REFERENCES users(id)
```

## Módulos vs Páginas

| Entidade | Fonte de Verdade | Onde Configurar |
|----------|------------------|-----------------|
| Módulo | `module.json` | `/admin/modules` |
| Página | tabela `pages` | `/admin/pages` |

Módulos NÃO usam tabela `pages`.

## Menu não aparece sem login

Verificar:
- Página: `is_public = 1` na tabela `pages`
- Módulo: `"public": true` no `module.json`

## Sistema SEM members

Se `ENABLE_MEMBERS = false`, tudo é público automaticamente.

## Edit tool falha

Usar `Write` para reescrever arquivo completo em vez de `Edit`.

## Supabase - Placeholders SQL

O adapter substitui `?` automaticamente. Se der erro de sintaxe, verificar parâmetros.

## Deploy COM members

Pasta `/public` é obrigatória (contém `MemberAuth.php`).

## Schemas de instalação

- `*-schema.sql` → COM members
- `*-schema-minimal.sql` → SEM members (is_public DEFAULT 1)
