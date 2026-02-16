# 🐛 FIX PREVENTIVO - Upload de Imagens (31/01/2026)

## ⚠️ SE UPLOAD PARAR DE FUNCIONAR EM PRODUÇÃO

**Sintoma:** Erro ao fazer upload via API, mensagem vazia ou HTTP 406

**Causa provável:** ModSecurity bloqueando POST com FormData na pasta `/api/`

---

## ✅ VERIFICAÇÃO RÁPIDA

1. Verificar se arquivo existe em produção: `api/.htaccess`
2. Se NÃO existir: **RECRIAR** com conteúdo abaixo
3. Se existir mas uploads não funcionam: verificar logs de erro

---

## 📄 CONTEÚDO CRÍTICO: `api/.htaccess`

```apache
# Desabilitar ModSecurity para permitir uploads
<IfModule mod_security.c>
    SecFilterEngine Off
    SecFilterScanPOST Off
</IfModule>

<IfModule mod_security2.c>
    SecRuleEngine Off
</IfModule>
```

**⚠️ NUNCA DELETAR** este arquivo em produção!

---

## 🔍 DIAGNÓSTICO

### Teste 1: API responde?
```bash
curl https://SEU-DOMINIO/api/upload-image.php
```

**Esperado:** JSON com erro de autenticação
**Se retornar HTML/vazio:** Problema no PHP

### Teste 2: ModSecurity bloqueando?
```bash
curl -X POST -F "image=@test.jpg" https://SEU-DOMINIO/api/upload-image.php
```

**HTTP 406:** ModSecurity bloqueando → precisa do `.htaccess`
**JSON:** ModSecurity OK

---

## 🛠️ CORREÇÕES APLICADAS

### 1. `api/.htaccess` (NOVO)
Desabilita ModSecurity apenas em `/api/` para permitir uploads.

### 2. `api/upload-image.php` (HARDENING)
- Output buffering (`ob_start()` + `ob_end_clean()`)
- Try-catch no Auth para capturar erros
- Path relativo (funciona em qualquer ambiente)

---

## 📊 ORIGEM DO FIX

**Projeto:** Futebol Energia
**Data:** 31/01/2026
**Tempo investigação:** ~3 horas
**Causa raiz:** ModSecurity + output buffering

**Sintoma real em produção:**
- Erro: "Unexpected end of JSON input"
- GET funcionava, POST com arquivo retornava HTTP 406
- Após desabilitar ModSecurity: POST crashava silenciosamente

**Solução testada e validada em produção.**

---

## 📝 HISTÓRICO

**v15.2.2** - 31/01/2026
- Fix preventivo aplicado
- Baseado em issue real de Futebol Energia
- Sem produção AEGIS ainda, mas preparado

---

**Mantido por:** Fábio Chezzi
**Referência:** docs/CHANGELOG.md v15.2.2
