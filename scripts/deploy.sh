#!/bin/bash
# ========================================
# AEGIS Framework - Deploy Automatizado
# ========================================
# Uso: ./deploy.sh [ambiente]
# Ambientes: producao (padrão), homologacao
# ========================================

set -e # Parar em caso de erro

AMBIENTE="${1:-producao}"
VERSAO=$(date +%Y%m%d-%H%M%S)
ARQUIVO_DEPLOY="aegis-${AMBIENTE}-${VERSAO}.tar.gz"

echo "🚀 AEGIS Framework - Deploy Automatizado"
echo "=========================================="
echo "Ambiente: ${AMBIENTE}"
echo "Versão: ${VERSAO}"
echo ""

# ========================================
# 1. VALIDAÇÕES PRÉ-DEPLOY
# ========================================
echo "📋 Validando ambiente..."

if [ ! -f "_config.php" ]; then
    echo "❌ ERRO: _config.php não encontrado!"
    echo "   Execute: cp _config.PRODUCTION.php _config.php"
    exit 1
fi

if [ ! -f "routes.php" ]; then
    echo "❌ ERRO: routes.php não encontrado!"
    exit 1
fi

if [ ! -d "core" ]; then
    echo "❌ ERRO: Pasta core/ não encontrada!"
    exit 1
fi

echo "✅ Validações OK"
echo ""

# ========================================
# 2. CRIAR BACKUP DO DEPLOYMENT ANTERIOR
# ========================================
echo "💾 Criando backup..."

if [ -f "deploys/ultimo-deploy.tar.gz" ]; then
    mv deploys/ultimo-deploy.tar.gz "deploys/backup-${VERSAO}.tar.gz"
    echo "✅ Backup criado: deploys/backup-${VERSAO}.tar.gz"
else
    echo "⚠️  Primeiro deploy - sem backup anterior"
fi

mkdir -p deploys
echo ""

# ========================================
# 3. COMPACTAR ARQUIVOS
# ========================================
echo "📦 Compactando arquivos..."

tar -czf "deploys/${ARQUIVO_DEPLOY}" \
    --exclude='Documents' \
    --exclude='Documents/firstnode' \
    --exclude='node_modules' \
    --exclude='.git' \
    --exclude='.gitignore' \
    --exclude='.vscode' \
    --exclude='.claude' \
    --exclude='debug.log' \
    --exclude='*.md' \
    --exclude='COMECE_AQUI.md' \
    --exclude='DEBUG_*.md' \
    --exclude='DEPLOY_*.md' \
    --exclude='database/LIMPAR_BANCO.sql' \
    --exclude='database/GERAR_DROPS.sql' \
    --exclude='database/migrations/*.sql' \
    --exclude='_config.example.php' \
    --exclude='_config.PRODUCTION.php' \
    --exclude='show_error_log.php' \
    --exclude='test_*.php' \
    --exclude='.env' \
    --exclude='.DS_Store' \
    --exclude='storage/logs/*' \
    --exclude='storage/cache/*' \
    --exclude='deploys' \
    --exclude='deploy.sh' \
    admin/ \
    assets/ \
    core/ \
    database/ \
    frontend/ \
    modules/ \
    public/ \
    scripts/ \
    storage/ \
    index.php \
    .htaccess \
    _config.php \
    routes.php \
    setup.php

# Copiar como "último deploy" para facilitar
cp "deploys/${ARQUIVO_DEPLOY}" "deploys/ultimo-deploy.tar.gz"

TAMANHO=$(du -h "deploys/${ARQUIVO_DEPLOY}" | cut -f1)
echo "✅ Arquivo criado: ${ARQUIVO_DEPLOY} (${TAMANHO})"
echo ""

# ========================================
# 4. INFORMAÇÕES DE UPLOAD
# ========================================
echo "📤 PRÓXIMOS PASSOS:"
echo ""
echo "1. FAZER UPLOAD VIA FTP:"
echo "   - Arquivo: deploys/${ARQUIVO_DEPLOY}"
echo "   - Destino: public_html/ (raiz do site)"
echo ""
echo "2. NO SERVIDOR (via SSH ou File Manager):"
echo "   - Descompactar: tar -xzf ${ARQUIVO_DEPLOY}"
echo "   - Deletar arquivo: rm ${ARQUIVO_DEPLOY}"
echo ""
echo "3. CONFIGURAR PERMISSÕES:"
echo "   chmod 755 ."
echo "   chmod 777 storage/"
echo "   chmod 777 uploads/"
echo "   chmod 644 _config.php"
echo ""
echo "4. TESTAR:"
echo "   - Acessar: https://seusite.com"
echo "   - Admin: https://seusite.com/admin"
echo ""
echo "=========================================="
echo "✅ Deploy preparado com sucesso!"
echo "=========================================="
echo ""
echo "📁 Arquivo pronto em: deploys/${ARQUIVO_DEPLOY}"
echo ""

# ========================================
# 5. UPLOAD AUTOMÁTICO (se configurado)
# ========================================
if [ -f ".deploy-config" ]; then
    echo "🔧 Configuração de deploy encontrada"
    echo ""
    read -p "Deseja fazer upload automático? (s/N): " UPLOAD

    if [ "$UPLOAD" = "s" ] || [ "$UPLOAD" = "S" ]; then
        source .deploy-config

        if [ -z "$FTP_HOST" ] || [ -z "$FTP_USER" ] || [ -z "$FTP_PASS" ]; then
            echo "❌ Configuração incompleta em .deploy-config"
            exit 1
        fi

        echo "📤 Fazendo upload via FTP..."

        lftp -c "
            open ftp://${FTP_USER}:${FTP_PASS}@${FTP_HOST}
            cd ${FTP_DIR:-public_html}
            put deploys/${ARQUIVO_DEPLOY}
            bye
        "

        echo "✅ Upload concluído!"
        echo ""
        echo "⚠️  LEMBRE-SE DE DESCOMPACTAR NO SERVIDOR!"
    fi
else
    echo "💡 DICA: Crie um arquivo .deploy-config para upload automático:"
    echo ""
    echo "FTP_HOST=ftp.seuservidor.com"
    echo "FTP_USER=seu-usuario"
    echo "FTP_PASS=sua-senha"
    echo "FTP_DIR=public_html"
    echo ""
fi
