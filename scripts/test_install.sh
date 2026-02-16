#!/bin/bash
# 🧪 Plano de Teste - AEGIS Framework
# Execute este script para testar instalação limpa

echo "🧪 AEGIS Framework - Teste de Instalação Limpa"
echo "================================================"
echo ""

# 1. Backup configs existentes
echo "📦 1. Criando backup das configurações atuais..."
if [ -f ".env" ]; then
    cp .env .env.backup.$(date +%Y%m%d_%H%M%S)
    echo "   ✅ .env → .env.backup"
fi

if [ -f "_config.php" ]; then
    cp _config.php _config.php.backup.$(date +%Y%m%d_%H%M%S)
    echo "   ✅ _config.php → _config.php.backup"
fi

# 2. Remover configs para simular instalação limpa
echo ""
echo "🧹 2. Removendo configurações para simular instalação limpa..."
rm -f .env _config.php
echo "   ✅ .env removido"
echo "   ✅ _config.php removido"

# 3. Limpar storage (opcional)
echo ""
echo "🗑️  3. Limpando storage..."
rm -rf storage/logs/*.log 2>/dev/null
rm -rf storage/cache/* 2>/dev/null
echo "   ✅ Logs e cache limpos"

echo ""
echo "✅ PREPARAÇÃO COMPLETA!"
echo ""
echo "📋 Próximos Passos MANUAIS:"
echo ""
echo "1. Abrir navegador em:"
echo "   http://localhost:8888/aegis/setup.php"
echo ""
echo "2. Testar instalação com MySQL + Members:"
echo "   - DB Type: MySQL"
echo "   - Host: localhost"
echo "   - Database: aegis_test"
echo "   - User: root"
echo "   - Pass: root"
echo "   - ☑️ Habilitar sistema de membros"
echo "   - Testar Conexão"
echo "   - Admin: Seu Nome / admin@test.com / SenhaForte123!"
echo "   - Instalar"
echo ""
echo "3. Validar após instalação:"
echo "   - Login em /admin/login funciona?"
echo "   - Dashboard carrega sem erros?"
echo "   - Criar página funciona?"
echo "   - Menu dinâmico funciona?"
echo ""
echo "4. Se TUDO OK, restaurar backup e commitar:"
echo "   ./restore_and_commit.sh"
echo ""

