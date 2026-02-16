#!/bin/bash
# Script para iniciar servidor de desenvolvimento AEGIS Framework

echo "🚀 Iniciando servidor AEGIS Framework..."
echo ""
echo "IMPORTANTE: Este é um servidor de DESENVOLVIMENTO apenas!"
echo "Para produção, use Apache, Nginx ou outro servidor web profissional."
echo ""
echo "Servidor iniciando em:"
echo "  http://localhost:5757/aegis"
echo ""
echo "Pressione Ctrl+C para parar o servidor"
echo ""
echo "---------------------------------------------------"

cd "$(dirname "$0")"

# Tentar encontrar PHP
if command -v php &> /dev/null; then
    PHP_BIN="php"
elif [ -f "/Applications/MAMP/bin/php/php8.2.0/bin/php" ]; then
    PHP_BIN="/Applications/MAMP/bin/php/php8.2.0/bin/php"
elif [ -f "/Applications/MAMP/bin/php/php8.1.0/bin/php" ]; then
    PHP_BIN="/Applications/MAMP/bin/php/php8.1.0/bin/php"
elif [ -f "/usr/bin/php" ]; then
    PHP_BIN="/usr/bin/php"
else
    echo "❌ ERRO: PHP não encontrado!"
    echo "Por favor, instale PHP ou MAMP"
    exit 1
fi

echo "✅ PHP encontrado: $PHP_BIN"
$PHP_BIN -v | head -1
echo ""

# Iniciar servidor
$PHP_BIN -S 0.0.0.0:5757 -t .
