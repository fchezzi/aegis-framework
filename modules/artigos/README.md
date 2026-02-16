# Módulo Artigos - AEGIS Framework

**Versão:** 1.0.0
**AEGIS:** 14.0.7+
**Autor:** AEGIS Team

---

## 📖 Descrição

Sistema completo de artigos científicos com:
- Gerenciamento admin (CRUD)
- Listagem pública paginada
- Página individual com formulário de captura
- Busca AJAX (texto + filtro por ano)
- Upload de imagem + PDF
- Email automático com PDF anexo
- Integração RD Station Marketing
- Contador de visualizações

---

## 🗂️ Estrutura

```
modules/artigos/
├── README.md (este arquivo)
├── module.json (metadados v14)
├── routes.php (rotas públicas + admin)
├── controllers/
│   ├── AdminArtigosController.php (CRUD admin)
│   └── PublicArtigosController.php (front + captura)
├── views/
│   ├── admin/
│   │   ├── index.php (lista)
│   │   └── form.php (criar/editar)
│   └── public/
│       ├── index.php (listagem paginada)
│       └── artigo.php (individual + form)
└── database/
    ├── mysql-schema.sql (UUID VARCHAR)
    ├── supabase-schema.sql (UUID nativo)
    └── rollback.sql (desinstalação)
```

---

## 🗄️ Banco de Dados

### tbl_artigos
```sql
id VARCHAR(36) PRIMARY KEY
titulo VARCHAR(255) NOT NULL
slug VARCHAR(255) UNIQUE NOT NULL
introducao TEXT NOT NULL
autor VARCHAR(255) NOT NULL
data_artigo DATE NOT NULL
imagem VARCHAR(255)
link_externo VARCHAR(500)
arquivo_pdf VARCHAR(255)
views INT DEFAULT 0
created_at TIMESTAMP
updated_at TIMESTAMP
```

### tbl_artigos_leads
```sql
id VARCHAR(36) PRIMARY KEY
artigo_id VARCHAR(36) NOT NULL (FK → tbl_artigos.id)
nome VARCHAR(255) NOT NULL
email VARCHAR(255) NOT NULL
whatsapp VARCHAR(20) NOT NULL
created_at TIMESTAMP
```

**IMPORTANTE:** Usa UUID (VARCHAR 36 no MySQL, UUID nativo no Supabase)

---

## 🚀 Instalação

### Via Admin UI
1. Acessar `/admin/modules`
2. Clicar em "Instalar" no card do módulo Artigos
3. Banco criado automaticamente
4. Módulo disponível em:
   - Admin: `/admin/artigos`
   - Público: `/artigos`

### Via Código
```php
ModuleManager::install('artigos');
```

### Pré-requisitos
- AEGIS Framework 14.0.7+
- Email configurado (PHPMailer/SMTP)
- RD Station configurado (opcional)

---

## ⚙️ Configuração

### 1. Email (Obrigatório)
Acessar `/admin/settings` e configurar:
- Servidor SMTP (ex: smtp.gmail.com)
- Porta (587 para TLS, 465 para SSL)
- Usuário (email completo)
- Senha (App Password do Gmail)
- Email remetente
- Nome remetente
- Criptografia (TLS recomendado)

### 2. RD Station (Opcional)
Acessar `/admin/settings` e configurar:
- Marcar checkbox "Habilitar RD Station"
- Inserir API Key (token público)

### 3. Ajustes Finos
Editar `module.json` se necessário:
```json
{
  "configuration": {
    "per_page": 10,
    "max_file_size": 10485760,
    "allowed_extensions": ["pdf", "jpg", "png"]
  }
}
```

---

## 🎯 Uso

### Admin

#### Criar Artigo
1. `/admin/artigos` → Clicar "Novo Artigo"
2. Preencher formulário:
   - Título (obrigatório)
   - Slug (gerado automaticamente)
   - Introdução (obrigatório)
   - Autor (obrigatório)
   - Data do artigo (obrigatório)
   - Imagem destacada (opcional, JPG/PNG)
   - Link externo (opcional)
   - Arquivo PDF (opcional, para envio por email)
3. Salvar

#### Editar/Deletar
- Lista em `/admin/artigos`
- Ações: Editar | Deletar
- Busca por título/autor

### Público

#### Listar Artigos
- URL: `/artigos`
- Paginação automática (10 por página)
- Busca AJAX com filtro de ano
- Cards clicáveis

#### Ver Artigo
- URL: `/artigos/{slug}`
- Formulário de captura:
  - Nome (obrigatório)
  - Email (obrigatório)
  - WhatsApp (obrigatório)
- Ao submeter:
  1. Lead salvo no banco
  2. Email enviado com PDF anexo (se houver)
  3. Lead enviado para RD Station (se habilitado)
  4. Redirecionamento com mensagem de sucesso

---

## 🔌 Integrações

### Email (PHPMailer)

**Método usado:**
```php
Email::enviarArtigo($to, $nome, $tituloArtigo, $pdfPath);
```

**Fluxo:**
1. Verifica se artigo tem `arquivo_pdf`
2. Verifica se arquivo existe em `storage/uploads/`
3. Envia email com template HTML
4. Anexa PDF com nome original
5. Retorna true/false

**Requisitos:**
- SMTP configurado em `/admin/settings`
- PDF existente em `storage/uploads/`

### RD Station Marketing

**Método usado:**
```php
RDStation::enviarLead($email, $nome, $whatsapp, $tituloArtigo, $slug);
```

**Payload enviado:**
```json
{
  "event_type": "CONVERSION",
  "event_family": "CDP",
  "payload": {
    "conversion_identifier": "artigo-solicitado",
    "email": "email@exemplo.com",
    "name": "Nome Completo",
    "mobile_phone": "+5511999999999",
    "tags": ["artigo-slug", "contato_instituto"],
    "cf_titulo_artigo": "Título do Artigo",
    "cf_slug_artigo": "titulo-do-artigo",
    "traffic_source": "website-artigos"
  }
}
```

**Requisitos:**
- `RDSTATION_ENABLED = true` em _config.php
- `RDSTATION_API_KEY` configurada
- WhatsApp no formato brasileiro (formatação automática)

---

## 🛡️ Segurança

### Validações Implementadas
- ✅ CSRF token em todos os formulários
- ✅ Sanitização de inputs (Security::sanitize)
- ✅ Validação de email (FILTER_VALIDATE_EMAIL)
- ✅ Validação de tamanhos (título 255, email 255, whatsapp 20)
- ✅ Upload seguro (extensões permitidas, tamanho máximo)
- ✅ SQL injection prevenido (prepared statements)
- ✅ XSS prevenido (htmlspecialchars nas views)

### Rate Limiting
- Admin: via Auth::require() (rate limit do sistema)
- Público: sem rate limit (considerar adicionar no futuro)

---

## 🔧 Customização

### Template Email
Editar `core/Email.php` → método `enviarArtigo()`:
```php
$conteudo = '<p>Olá <strong>' . htmlspecialchars($nome) . '</strong>,</p>
<p>Obrigado pelo interesse no artigo <strong>' . htmlspecialchars($tituloArtigo) . '</strong>.</p>
<p>O PDF está anexado a este email.</p>
<p>Atenciosamente,<br>Equipe</p>';
```

### Template RD Station
Editar `core/RDStation.php` → método `enviarLead()`:
```php
$payload = [
    'event_type' => 'CONVERSION',
    'event_family' => 'CDP',
    'payload' => [
        'conversion_identifier' => 'seu-identificador',
        // ... outros campos
    ]
];
```

### Views Públicas
Editar arquivos em `views/public/`:
- `index.php` - listagem
- `artigo.php` - página individual

### Views Admin
Editar arquivos em `views/admin/`:
- `index.php` - lista
- `form.php` - formulário

---

## 🐛 Troubleshooting

### Email não enviado
1. Verificar logs: `storage/logs/error.log`
2. Testar SMTP: usar script de teste PHPMailer
3. Verificar configurações em `/admin/settings`
4. Gmail: verificar se App Password está correto

### RD Station não recebe leads
1. Verificar `RDSTATION_ENABLED = true`
2. Verificar `RDSTATION_API_KEY` correta
3. Verificar logs: `storage/logs/error.log`
4. Testar API manualmente com curl

### Upload não funciona
1. Verificar permissões: `storage/uploads/` deve ser gravável
2. Verificar tamanho: `upload_max_filesize` no php.ini
3. Verificar extensão: apenas PDF/JPG/PNG permitidos

### 500 Error
1. Verificar `storage/logs/error.log`
2. Verificar banco: tabelas `tbl_artigos` e `tbl_artigos_leads` existem?
3. Verificar _config.php: todas as constantes SMTP definidas?
4. Verificar composer: `vendor/autoload.php` existe?

---

## 📊 Métricas

### Leads Capturados
```sql
SELECT COUNT(*) FROM tbl_artigos_leads;
```

### Artigos Mais Visualizados
```sql
SELECT titulo, views FROM tbl_artigos ORDER BY views DESC LIMIT 10;
```

### Taxa de Conversão por Artigo
```sql
SELECT
    a.titulo,
    a.views,
    COUNT(l.id) as leads,
    ROUND((COUNT(l.id) / a.views) * 100, 2) as taxa_conversao
FROM tbl_artigos a
LEFT JOIN tbl_artigos_leads l ON l.artigo_id = a.id
WHERE a.views > 0
GROUP BY a.id
ORDER BY taxa_conversao DESC;
```

---

## 🔄 Migração de v9.0.2

Este módulo foi migrado de AEGIS v9.0.2 (bkp-instituto-atualli) para v14.0.7.

**Principais mudanças:**
- INT AUTO_INCREMENT → VARCHAR(36) UUID
- `Upload::pdf()` → `Upload::uploadFile()`
- module.json atualizado para formato v14
- Adicionado helper `checkModuleAccess()`
- Email e RDStation copiados do bkp (solução exata)

---

## 📚 Referências

- [AEGIS Modules Docs](.claude/modules.md)
- [AEGIS Quick Reference](.claude/QUICK_REFERENCE.md)
- [PHPMailer Docs](https://github.com/PHPMailer/PHPMailer)
- [RD Station API Docs](https://developers.rdstation.com/pt-BR/reference/conversions)

---

## 📝 Changelog

### v1.0.0 (27/01/2026)
- Migração completa de v9.0.2 para v14.0.7
- UUID implementado (MySQL + Supabase)
- Email PHPMailer integrado
- RD Station Marketing integrado
- Admin settings para configuração

---

## 🤝 Suporte

**Problemas?**
1. Verificar `storage/logs/error.log`
2. Consultar seção Troubleshooting
3. Verificar documentação AEGIS

**Feature requests?**
- Documentar em `docs/CHANGELOG-*.md`
- Adicionar ao roadmap se necessário

---

**Mantido por:** AEGIS Team
**Última atualização:** 27/01/2026
