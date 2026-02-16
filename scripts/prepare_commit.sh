#!/bin/bash
# 🔄 Restaurar Backup e Commitar para Git
# Execute SOMENTE após teste de instalação bem-sucedido

echo "🔄 AEGIS Framework - Restaurar e Commitar"
echo "=========================================="
echo ""

# 1. Restaurar backup mais recente
echo "📦 1. Restaurando configuração original..."

# Restaurar .env mais recente
LATEST_ENV=$(ls -t .env.backup.* 2>/dev/null | head -1)
if [ -n "$LATEST_ENV" ]; then
    cp "$LATEST_ENV" .env
    echo "   ✅ Restaurado: $LATEST_ENV → .env"
else
    echo "   ⚠️  Nenhum backup .env encontrado"
fi

# Restaurar _config.php mais recente
LATEST_CONFIG=$(ls -t _config.php.backup.* 2>/dev/null | head -1)
if [ -n "$LATEST_CONFIG" ]; then
    cp "$LATEST_CONFIG" _config.php
    echo "   ✅ Restaurado: $LATEST_CONFIG → _config.php"
else
    echo "   ⚠️  Nenhum backup _config.php encontrado"
fi

# 2. Remover banco de teste
echo ""
echo "🗑️  2. Removendo banco de teste (aegis_test)..."
mysql -u root -proot -e "DROP DATABASE IF EXISTS aegis_test;" 2>/dev/null
if [ $? -eq 0 ]; then
    echo "   ✅ Banco aegis_test removido"
else
    echo "   ⚠️  Não foi possível remover banco (pode não existir)"
fi

# 3. Git status
echo ""
echo "📊 3. Status atual do Git:"
git status --short

# 4. Preparar commit
echo ""
echo "🎯 4. Preparando commit inicial..."
echo ""
read -p "   Deseja commitar agora? (s/n): " -n 1 -r
echo ""

if [[ $REPLY =~ ^[Ss]$ ]]; then
    # Add all files
    git add .
    
    # Show what will be committed
    echo ""
    echo "📋 Arquivos que serão commitados:"
    git status --short
    echo ""
    
    read -p "   Confirmar commit? (s/n): " -n 1 -r
    echo ""
    
    if [[ $REPLY =~ ^[Ss]$ ]]; then
        # Commit
        git commit -m "🎉 Initial release v1.5.0

AEGIS Framework - Framework PHP modular e seguro

Features:
- Sistema de autenticação dupla (Admin + Members)
- Multi-database (MySQL, Supabase, None)
- Page Builder visual com blocos e cards
- Menu Builder dinâmico com permissões
- Sistema de permissões granular
- Proteções de segurança (CSRF, XSS, SQL injection)
- Cache em memória e arquivo
- Documentação auto-gerada
- Instalador web completo

Documentação:
- README.md: Visão geral e quick start
- INSTALL.md: Guia de instalação detalhado
- CHANGELOG.md: Histórico de versões
"
        
        echo ""
        echo "✅ COMMIT CRIADO COM SUCESSO!"
        echo ""
        echo "📋 Próximos passos:"
        echo "1. Criar repositório no GitHub"
        echo "2. git remote add origin https://github.com/seu-usuario/aegis.git"
        echo "3. git branch -M main"
        echo "4. git push -u origin main"
        echo ""
    else
        echo "   ❌ Commit cancelado"
    fi
else
    echo "   ❌ Commit cancelado"
fi

