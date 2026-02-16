#!/bin/bash
# ========================================
# AEGIS Framework - Suite de Testes
# ========================================

set -e
FAILED=0

echo "🧪 AEGIS Framework - Suite de Testes"
echo "======================================"
echo ""

# Verificar se está no diretório correto
if [ ! -f "_config.php" ]; then
    echo "❌ ERRO: Execute este script na raiz do projeto AEGIS"
    exit 1
fi

# Detectar PHP
PHP_BIN=$(command -v php 2>/dev/null || echo "")
if [ -z "$PHP_BIN" ]; then
    # Tentar caminhos comuns no Mac
    if [ -f "/usr/local/bin/php" ]; then
        PHP_BIN="/usr/local/bin/php"
    elif [ -f "/opt/homebrew/bin/php" ]; then
        PHP_BIN="/opt/homebrew/bin/php"
    else
        echo "❌ PHP não encontrado!"
        echo "   Instale PHP ou configure o PATH"
        exit 1
    fi
fi

echo "📍 Usando PHP: $PHP_BIN"
echo ""

# ========================================
# TESTE 1: Sistema de Cache
# ========================================
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "📦 TESTE 1/7: Sistema de Cache"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
if $PHP_BIN test_cache.php 2>&1; then
    echo ""
else
    FAILED=$((FAILED+1))
    echo "❌ Teste de cache FALHOU"
fi
sleep 1

# ========================================
# TESTE 2: API de Updates
# ========================================
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "🌐 TESTE 2/7: API de Updates"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
if $PHP_BIN test_api.php 2>&1; then
    echo ""
else
    FAILED=$((FAILED+1))
    echo "❌ Teste de API FALHOU"
fi
sleep 1

# ========================================
# TESTE 3: Sistema de Migrations
# ========================================
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "📦 TESTE 3/7: Sistema de Migrations"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
if $PHP_BIN test_migrations.php 2>&1; then
    echo ""
else
    FAILED=$((FAILED+1))
    echo "❌ Teste de migrations FALHOU"
fi
sleep 1

# ========================================
# TESTE 4: Arquivos Criados
# ========================================
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "📂 TESTE 4/7: Verificação de Arquivos"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

FILES=(
    "core/Cache.php"
    "core/ModuleMigration.php"
    "modules/palpites/api/updates.php"
    "modules/palpites/database/performance-indexes.sql"
    "modules/palpites/database/rollback.sql"
    "modules/palpites/module.json"
    "database/migrations/003_module_migrations.sql"
    "deploy.sh"
    ".deploy-config.example"
)

ALL_EXIST=true
for file in "${FILES[@]}"; do
    if [ -f "$file" ]; then
        echo "✅ $file"
    else
        echo "❌ $file NÃO ENCONTRADO"
        ALL_EXIST=false
    fi
done

if [ "$ALL_EXIST" = false ]; then
    FAILED=$((FAILED+1))
fi
echo ""
sleep 1

# ========================================
# TESTE 5: Deploy Script
# ========================================
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "🚀 TESTE 5/7: Script de Deploy"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
if [ -x "deploy.sh" ]; then
    echo "✅ deploy.sh é executável"
else
    echo "❌ deploy.sh não é executável"
    echo "   Execute: chmod +x deploy.sh"
    FAILED=$((FAILED+1))
fi
echo ""
sleep 1

# ========================================
# TESTE 6: Pasta de Cache
# ========================================
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "💾 TESTE 6/7: Diretório de Cache"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
if [ -d "storage/cache" ]; then
    echo "✅ storage/cache/ existe"
    CACHE_FILES=$(find storage/cache -name "*.cache" 2>/dev/null | wc -l | tr -d ' ')
    echo "   Arquivos de cache: $CACHE_FILES"
else
    echo "⚠️  storage/cache/ não existe (será criado automaticamente)"
    mkdir -p storage/cache
    echo "   ✅ Criado agora"
fi
echo ""
sleep 1

# ========================================
# TESTE 7: Configuração
# ========================================
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "⚙️  TESTE 7/7: Configuração"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
if grep -q "SUPABASE_URL" _config.php 2>/dev/null; then
    echo "✅ _config.php configurado com Supabase"
else
    echo "❌ _config.php sem configuração Supabase"
    FAILED=$((FAILED+1))
fi

if grep -q "APP_URL" _config.php 2>/dev/null; then
    APP_URL=$(grep "define('APP_URL'" _config.php | cut -d "'" -f4)
    echo "✅ APP_URL definida: $APP_URL"
else
    echo "❌ APP_URL não definida"
    FAILED=$((FAILED+1))
fi
echo ""

# ========================================
# RESUMO
# ========================================
echo "======================================"
echo "📊 RESUMO DOS TESTES"
echo "======================================"

if [ $FAILED -eq 0 ]; then
    echo "✅ TODOS OS TESTES PASSARAM!"
    echo ""
    echo "🚀 PRÓXIMOS PASSOS:"
    echo "1. Executar SQL de performance no Supabase"
    echo "2. Testar tela de exibição no navegador"
    echo "3. Preparar deploy: ./deploy.sh producao"
else
    echo "❌ $FAILED TESTE(S) FALHARAM"
    echo ""
    echo "⚠️  Corrija os erros acima antes de continuar"
fi
echo "======================================"

exit $FAILED
