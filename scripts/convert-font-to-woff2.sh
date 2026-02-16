#!/bin/bash
#
# Conversor de Fontes OTF/TTF → WOFF2
# Uso: ./convert-font-to-woff2.sh arquivo.otf [arquivo2.ttf ...]
#

if [ $# -eq 0 ]; then
    echo "Uso: $0 arquivo.otf [arquivo2.ttf ...]"
    echo "Exemplo: $0 Roboto-Regular.otf Roboto-Bold.ttf"
    exit 1
fi

for font in "$@"; do
    if [ ! -f "$font" ]; then
        echo "❌ Arquivo não encontrado: $font"
        continue
    fi

    # Gerar nome do arquivo de saída
    filename="${font%.*}"
    output="${filename}.woff2"

    echo "🔄 Convertendo: $font → $output"

    # Converter usando pyftsubset (mantém todos os glyphs)
    pyftsubset "$font" \
        --output-file="$output" \
        --flavor=woff2 \
        --layout-features='*' \
        --unicodes='*'

    if [ $? -eq 0 ]; then
        # Mostrar tamanhos
        original_size=$(stat -f%z "$font" 2>/dev/null || stat -c%s "$font" 2>/dev/null)
        new_size=$(stat -f%z "$output" 2>/dev/null || stat -c%s "$output" 2>/dev/null)

        original_kb=$((original_size / 1024))
        new_kb=$((new_size / 1024))
        reduction=$(( (original_size - new_size) * 100 / original_size ))

        echo "✅ Sucesso!"
        echo "   Original: ${original_kb}KB → WOFF2: ${new_kb}KB (redução de ${reduction}%)"
        echo ""
    else
        echo "❌ Erro ao converter $font"
        echo ""
    fi
done

echo "🎉 Conversão concluída!"
