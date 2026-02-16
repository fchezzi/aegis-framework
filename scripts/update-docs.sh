#!/bin/bash
# AEGIS - Atualizar Documentação
# USO: ./update-docs.sh

echo "🔄 Atualizando documentação AEGIS..."
/Applications/MAMP/bin/php/php7.4.33/bin/php scripts/generate-docs.php
