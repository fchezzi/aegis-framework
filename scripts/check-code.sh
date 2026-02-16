#!/bin/bash

cd "$(dirname "$0")/.."

echo "🔍 AEGIS - Code Quality Check"
echo "=============================="
echo ""

# PHPStan
echo "📊 Rodando PHPStan (análise estática)..."
vendor/bin/phpstan analyse --error-format=table --no-progress

PHPSTAN_EXIT=$?

echo ""
echo "=============================="
echo ""

# PHP_CodeSniffer
echo "📋 Rodando PHP_CodeSniffer (padrão de código PSR-12)..."
vendor/bin/phpcs

PHPCS_EXIT=$?

echo ""
echo "=============================="
echo ""

# Resultado final
if [ $PHPSTAN_EXIT -eq 0 ] && [ $PHPCS_EXIT -eq 0 ]; then
    echo "✅ Tudo OK! Código está limpo."
    exit 0
else
    echo "❌ Foram encontrados problemas. Revise acima."
    echo ""
    echo "💡 Para corrigir automaticamente problemas de estilo:"
    echo "   vendor/bin/phpcbf"
    exit 1
fi
