<?php
/**
 * AEGIS Routes
 * Ponto de entrada das rotas - carrega arquivos de rotas organizados
 *
 * Estrutura:
 * - routes/api.php     → Rotas da API REST (/api/v1, /api/v2)
 * - routes/public.php  → Rotas públicas (home, login, logout)
 * - routes/admin.php   → Rotas do painel admin
 * - routes/catchall.php → Rotas genéricas (/:slug) - DEVE ser última
 *
 * Ordem de carregamento é IMPORTANTE:
 * 1. Rotas de API (prefixo /api)
 * 2. Rotas públicas (mais específicas)
 * 3. Rotas admin (prefixo /admin)
 * 4. Rotas de módulos (dinâmicas)
 * 5. Rotas catch-all (genéricas - última prioridade)
 */

// ================================================
// 1. ROTAS DE API
// ================================================
// API REST com versionamento (/api/v1, /api/v2)
// Carregada primeiro para evitar conflitos
if (file_exists(__DIR__ . '/routes/api.php')) {
    require_once __DIR__ . '/routes/api.php';
}

// ================================================
// 2. ROTAS PÚBLICAS
// ================================================
require_once __DIR__ . '/routes/public.php';

// ================================================
// 3. ROTAS ADMIN
// ================================================
require_once __DIR__ . '/routes/admin.php';

// ================================================
// 4. MÓDULOS OPCIONAIS
// ================================================
// Carregar rotas de módulos instalados
// Cada módulo tem seu próprio routes.php
// Só carrega se módulo estiver instalado
// IMPORTANTE: Deve vir ANTES das rotas catch-all
ModuleManager::loadAllRoutes();

// ================================================
// 5. ROTAS CATCH-ALL (ÚLTIMA PRIORIDADE)
// ================================================
// Rotas genéricas como /:slug devem vir por último
// para não interceptar rotas específicas
require_once __DIR__ . '/routes/catchall.php';
