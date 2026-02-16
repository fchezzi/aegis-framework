#!/bin/bash
# Script para preparar AEGIS Framework para distribuição
# Uso: ./prepare-release.sh

echo "╔═══════════════════════════════════════════════════════════╗"
echo "║  AEGIS Framework - Preparação para Release               ║"
echo "╚═══════════════════════════════════════════════════════════╝"
echo ""

# Verificar se está na pasta correta
if [ ! -f "prepare-release.sh" ]; then
    echo "❌ Execute este script da pasta raiz do AEGIS Framework"
    exit 1
fi

echo "⚠️  ATENÇÃO: Este script vai:"
echo "   1. Limpar cache, logs e uploads"
echo "   2. Criar backup do _config.php atual"
echo "   3. Substituir _config.php pelo _config.example.php (SEM credenciais)"
echo "   4. Criar ZIP para distribuição"
echo ""
read -p "Continuar? (s/N): " -n 1 -r
echo ""

if [[ ! $REPLY =~ ^[Ss]$ ]]; then
    echo "❌ Operação cancelada"
    exit 0
fi

echo ""
echo "🧹 Limpando arquivos temporários..."

# Limpar storage
rm -rf storage/cache/*
rm -rf storage/logs/*
rm -rf storage/uploads/*

# Criar .gitkeep
touch storage/cache/.gitkeep
touch storage/logs/.gitkeep
touch storage/uploads/.gitkeep

echo "✅ Storage limpo"

# Backup do _config.php atual
if [ -f "_config.php" ]; then
    BACKUP_FILE="_config.php.MY_BACKUP.$(date +%Y%m%d_%H%M%S)"
    cp _config.php "$BACKUP_FILE"
    echo "💾 Backup criado: $BACKUP_FILE"
    echo "   (GUARDE ESTE ARQUIVO - contém suas credenciais!)"
fi

# Substituir _config.php por versão genérica
if [ -f "_config.example.php" ]; then
    cp _config.example.php _config.php
    echo "✅ _config.php substituído por versão genérica (SEM credenciais)"
else
    echo "⚠️  _config.example.php não encontrado - pulando substituição"
fi

# Remover outros arquivos sensíveis
rm -f _config.php.backup.*
rm -f .env
rm -f .DS_Store

echo ""
echo "📦 Criando ZIP para distribuição..."

cd ..
ZIP_NAME="aegis-framework-v1.0-$(date +%Y%m%d).zip"

zip -r "$ZIP_NAME" aegis \
    -x "aegis/.git/*" \
    -x "aegis/.DS_Store" \
    -x "aegis/storage/cache/*" \
    -x "aegis/storage/logs/*" \
    -x "aegis/storage/uploads/*" \
    -x "aegis/_config.php.MY_BACKUP*" \
    -x "aegis/.env"

echo ""
echo "╔═══════════════════════════════════════════════════════════╗"
echo "║  ✅ Release Preparado com Sucesso!                       ║"
echo "╚═══════════════════════════════════════════════════════════╝"
echo ""
echo "📦 Arquivo criado:"
echo "   $(pwd)/$ZIP_NAME"
echo ""
echo "📋 Próximos passos:"
echo "   1. Teste a instalação usando INSTALACAO_RAPIDA.md"
echo "   2. Suba para GitHub ou distribua o ZIP"
echo ""
echo "⚠️  IMPORTANTE:"
echo "   - Seu backup está em: aegis/$BACKUP_FILE"
echo "   - Para voltar a trabalhar, restaure: cp $BACKUP_FILE _config.php"
echo ""
