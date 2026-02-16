#!/bin/bash
#
# Script de teste do módulo PageSpeed Insights
# Uso: ./scripts/test-pagespeed.sh [comando]
#

set -e

AEGIS_URL="http://localhost:5757/aegis"
N8N_URL="http://localhost:5678"
WEBHOOK_SECRET="bfe48065-3ab7-442c-b6c6-a9ac467a3c19"
MYSQL_USER="root"
MYSQL_PASS="root"
MYSQL_DB="aegis"

# Cores
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Funções auxiliares
echo_success() {
    echo -e "${GREEN}✓${NC} $1"
}

echo_error() {
    echo -e "${RED}✗${NC} $1"
}

echo_info() {
    echo -e "${BLUE}ℹ${NC} $1"
}

echo_warning() {
    echo -e "${YELLOW}⚠${NC} $1"
}

# Testes
test_csrf() {
    echo_info "Testando endpoint CSRF..."
    RESPONSE=$(curl -s "$AEGIS_URL/admin/api/get-csrf.php")
    TOKEN=$(echo "$RESPONSE" | jq -r '.csrf_token // empty')

    if [ -n "$TOKEN" ]; then
        echo_success "CSRF token obtido: ${TOKEN:0:20}..."
    else
        echo_error "Falha ao obter CSRF token"
        echo "$RESPONSE"
        exit 1
    fi
}

test_get_urls() {
    echo_info "Testando endpoint Get URLs..."
    RESPONSE=$(curl -s -X POST "$AEGIS_URL/admin/api/pagespeed-get-urls.php" \
        -H "Content-Type: application/x-www-form-urlencoded" \
        -d "webhook_secret=$WEBHOOK_SECRET")

    SUCCESS=$(echo "$RESPONSE" | jq -r '.success // false')

    if [ "$SUCCESS" = "true" ]; then
        TOTAL=$(echo "$RESPONSE" | jq -r '.total_analyses')
        echo_success "Config obtida: $TOTAL análises configuradas"
        echo "$RESPONSE" | jq .
    else
        echo_error "Falha ao obter URLs"
        echo "$RESPONSE"
        exit 1
    fi
}

test_save_mock() {
    echo_info "Testando salvamento com dados mock..."
    RESPONSE=$(curl -s -X POST "$AEGIS_URL/admin/api/pagespeed-save.php" \
        -H "Content-Type: application/json" \
        -d @storage/mock-pagespeed-data.json)

    SUCCESS=$(echo "$RESPONSE" | jq -r '.success // false')

    if [ "$SUCCESS" = "true" ]; then
        REPORT_ID=$(echo "$RESPONSE" | jq -r '.report_id')
        echo_success "Dados salvos! Report ID: $REPORT_ID"
    else
        echo_error "Falha ao salvar dados"
        echo "$RESPONSE"
        exit 1
    fi
}

test_database() {
    echo_info "Verificando banco de dados..."
    COUNT=$(/Applications/MAMP/Library/bin/mysql -h localhost -u $MYSQL_USER -p$MYSQL_PASS $MYSQL_DB -sN -e \
        "SELECT COUNT(*) FROM tbl_pagespeed_reports;" 2>/dev/null || echo "0")

    if [ "$COUNT" -gt 0 ]; then
        echo_success "Banco OK: $COUNT relatórios encontrados"
        echo ""
        echo "Últimos 3 relatórios:"
        /Applications/MAMP/Library/bin/mysql -h localhost -u $MYSQL_USER -p$MYSQL_PASS $MYSQL_DB -e \
            "SELECT id, url, strategy, performance_score, analyzed_at
             FROM tbl_pagespeed_reports
             ORDER BY analyzed_at DESC
             LIMIT 3;" 2>/dev/null
    else
        echo_warning "Nenhum relatório no banco"
    fi
}

test_n8n_workflow() {
    echo_info "Testando workflow n8n..."
    RESPONSE=$(curl -s -X POST "$N8N_URL/webhook/aegis-pagespeed-manual" \
        -H "Content-Type: application/json" \
        -d "{\"webhook_secret\": \"$WEBHOOK_SECRET\"}")

    if [ -z "$RESPONSE" ]; then
        echo_success "Webhook chamado (resposta vazia esperada)"
    else
        echo_warning "Resposta do webhook: $RESPONSE"
    fi

    sleep 2
    echo_info "Aguardando processamento..."
}

test_google_api() {
    echo_info "Testando Google PageSpeed API..."

    # Ler API key do settings.json
    API_KEY=$(cat storage/settings.json | jq -r '.pagespeed_api_key')

    RESPONSE=$(curl -s "https://www.googleapis.com/pagespeedonline/v5/runPagespeed?url=https://google.com&strategy=mobile&key=$API_KEY")

    ERROR=$(echo "$RESPONSE" | jq -r '.error.message // empty')

    if [ -n "$ERROR" ]; then
        echo_error "Google API falhou: $ERROR"
        if [[ "$ERROR" == *"Quota exceeded"* ]]; then
            echo_warning "Quota excedida. Aguarde renovação ou crie nova API key."
        fi
    else
        SCORE=$(echo "$RESPONSE" | jq -r '.lighthouseResult.categories.performance.score')
        SCORE_PCT=$(echo "scale=0; $SCORE * 100 / 1" | bc)
        echo_success "Google API OK! Score: $SCORE_PCT/100"
    fi
}

show_dashboard() {
    echo_info "Abrindo dashboard..."
    open "$AEGIS_URL/admin/pagespeed"
}

show_n8n() {
    echo_info "Abrindo n8n..."
    open "$N8N_URL"
}

run_all_tests() {
    echo ""
    echo "========================================="
    echo "  PageSpeed Insights - Test Suite"
    echo "========================================="
    echo ""

    test_csrf
    echo ""
    test_get_urls
    echo ""
    test_save_mock
    echo ""
    test_database
    echo ""
    test_google_api
    echo ""

    echo "========================================="
    echo_success "Testes concluídos!"
    echo "========================================="
    echo ""
}

show_help() {
    cat << EOF
Uso: ./scripts/test-pagespeed.sh [comando]

Comandos:
  all             Executar todos os testes
  csrf            Testar endpoint CSRF
  urls            Testar endpoint Get URLs
  save            Testar salvamento com dados mock
  db              Verificar banco de dados
  api             Testar Google PageSpeed API
  workflow        Testar workflow n8n completo
  dashboard       Abrir dashboard no navegador
  n8n             Abrir n8n no navegador
  help            Mostrar esta ajuda

Exemplos:
  ./scripts/test-pagespeed.sh all
  ./scripts/test-pagespeed.sh db
  ./scripts/test-pagespeed.sh api

EOF
}

# Main
case "${1:-all}" in
    all)
        run_all_tests
        ;;
    csrf)
        test_csrf
        ;;
    urls)
        test_get_urls
        ;;
    save)
        test_save_mock
        ;;
    db)
        test_database
        ;;
    api)
        test_google_api
        ;;
    workflow)
        test_n8n_workflow
        ;;
    dashboard)
        show_dashboard
        ;;
    n8n)
        show_n8n
        ;;
    help|--help|-h)
        show_help
        ;;
    *)
        echo_error "Comando desconhecido: $1"
        echo ""
        show_help
        exit 1
        ;;
esac
