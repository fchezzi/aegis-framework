# 🧪 TESTES v1.0 - Checklist de Validação

> **Data:** 2026-01-16
> **Objetivo:** Validar correções aplicadas antes do deploy

---

## ✅ TESTES MANUAIS (Execute você mesmo)

### 1. Segurança - APIs protegidas

```bash
# Teste 1: API sem autenticação deve retornar 401
curl -I http://localhost/api/chart-data.php
# Esperado: HTTP/1.1 401 Unauthorized

# Teste 2: Upload sem autenticação deve retornar 401
curl -I http://localhost/api/upload-image.php
# Esperado: HTTP/1.1 401 Unauthorized

# Teste 3: Downloads sem autenticação deve redirecionar
curl -I http://localhost/downloads
# Esperado: HTTP/1.1 302 Found (redirect para /login)

# Teste 4: Scripts bloqueados
curl -I http://localhost/scripts/sync-menu-permissions.php
# Esperado: HTTP/1.1 403 Forbidden

# Teste 5: Uploads bloqueados
curl -I http://localhost/uploads/reports/arquivo.xlsx
# Esperado: HTTP/1.1 403 Forbidden
```

---

### 2. Funcionalidade - Admin

```bash
# Teste 6: Login admin funciona
# Acessar: http://localhost/admin/login
# Fazer login com credenciais válidas
# Esperado: Redirecionar para /admin/dashboard

# Teste 7: CRUD de páginas funciona
# Acessar: http://localhost/admin/content/pages
# Criar nova página
# Editar página
# Deletar página
# Esperado: Todas operações funcionam sem erro

# Teste 8: PageBuilder renderiza componentes
# Acessar: http://localhost/admin/content/pages/edit/{id}
# Adicionar componente Tabelas
# Salvar e visualizar
# Esperado: Componente renderiza corretamente
```

---

### 3. Performance - Cache

```bash
# Teste 9: API de palpites usa cache
# Acessar: http://localhost/modules/palpites/api/updates.php
# Verificar header X-Cache ou tempo de resposta
# Esperado: Segunda requisição mais rápida (cache hit)

# Teste 10: Queries não fazem N+1
# Acessar: http://localhost/admin/members
# Verificar logs de query
# Esperado: 2-3 queries no máximo (com eager loading)
```

---

## 🤖 TESTES AUTOMATIZADOS (v2)

Para v2, implementar:
- PHPUnit para unit tests
- Pest para feature tests
- Laravel Dusk para browser tests

---

## 📋 CHECKLIST DE PRÉ-DEPLOY

Antes de fazer deploy para produção:

### Segurança
- [ ] Mudar `DEBUG_MODE` para `false` em `_config.php`
- [ ] Mudar `session.cookie_secure` para `1` (HTTPS)
- [ ] Verificar `.htaccess` na raiz (se Apache)
- [ ] Verificar permissões de pasta (755 para pastas, 644 para arquivos)
- [ ] Revisar credenciais do banco (não usar root/root)
- [ ] Ativar HSTS headers (production only)

### Performance
- [ ] Ativar OPcache no PHP
- [ ] Configurar Redis/Memcached para cache
- [ ] Minificar CSS/JS
- [ ] Otimizar imagens
- [ ] Configurar Gzip no servidor

### Backup
- [ ] Configurar backup automático diário
- [ ] Testar restore de backup
- [ ] Documentar processo de rollback

### Monitoramento
- [ ] Configurar logs de erro
- [ ] Configurar alertas (Sentry/New Relic)
- [ ] Configurar uptime monitoring

---

## 🔍 COMO EXECUTAR OS TESTES

### Opção 1: Manual via Browser
1. Abra cada URL listada acima
2. Verifique o resultado esperado
3. Marque como ✅ ou ❌

### Opção 2: Script de Teste (v2)
```bash
# Criar em v2:
php aegis test:security
php aegis test:performance
php aegis test:all
```

---

## 📊 CRITÉRIOS DE APROVAÇÃO

**v1.0 está pronto para produção se:**
- ✅ 10/10 testes manuais passam
- ✅ Zero erros de sintaxe PHP
- ✅ Zero warnings no log
- ✅ Todas páginas carregam < 2s
- ✅ Backup funciona e pode ser restaurado

---

## 🚨 SE ALGO FALHAR

1. **API retorna 500 ao invés de 401:**
   - Verificar se `Auth::check()` foi adicionado
   - Verificar logs em `/storage/logs/`

2. **Scripts ainda acessíveis:**
   - Verificar se `.htaccess` foi criado em `/scripts/`
   - Testar se Apache está lendo `.htaccess` (`AllowOverride All`)

3. **Upload direto funciona:**
   - Verificar se `.htaccess` foi criado em `/uploads/`
   - Verificar permissões do arquivo

4. **Login não funciona:**
   - Verificar tabela `users` existe
   - Verificar se há admin cadastrado
   - Rodar: `php scripts/create-admin.php` (se existir)

---

## 📝 REGISTRO DE TESTES

Quando executar, preencha:

| # | Teste | Status | Observação |
|---|-------|--------|------------|
| 1 | API sem auth → 401 | ⏳ | |
| 2 | Upload sem auth → 401 | ⏳ | |
| 3 | Downloads → redirect | ⏳ | |
| 4 | Scripts bloqueados | ⏳ | |
| 5 | Uploads bloqueados | ⏳ | |
| 6 | Login admin | ⏳ | |
| 7 | CRUD páginas | ⏳ | |
| 8 | PageBuilder | ⏳ | |
| 9 | Cache funciona | ⏳ | |
| 10 | Sem N+1 queries | ⏳ | |

**Legenda:**
- ⏳ Pendente
- ✅ Passou
- ❌ Falhou
- ⚠️ Passou com ressalvas

---

**Última atualização:** 2026-01-16
