<?php
/**
 * AEGIS Framework
 * Entry Point
 */

// CRITICAL: Configurar sessão ANTES de iniciar
ini_set('session.cookie_httponly', 1);
// ✅ Auto-detecta HTTPS: seguro em production, funciona em development
ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 1 : 0);
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.gc_maxlifetime', 7200); // 2 horas

// Iniciar sessão
session_start();

// Definir charset UTF-8 para toda a aplicação
header('Content-Type: text/html; charset=UTF-8');
mb_internal_encoding('UTF-8');

// Verificar se está instalado ANTES de carregar config
if (!file_exists(__DIR__ . '/_config.php')) {
    // Não instalado - redirecionar para setup
    header('Location: setup.php');
    exit;
}

// Carregar configuração (apenas se instalado)
require_once __DIR__ . '/_config.php';

// Composer Autoloader (PHPSpreadsheet e outras dependências)
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

// Autoloader
require_once __DIR__ . '/core/Autoloader.php';
Autoloader::register();

// Helper functions globais
require_once __DIR__ . '/core/helpers.php';

// Detectar e configurar ambiente (apenas se instalado)
Core::configure();

// Aplicar timezone configurado
date_default_timezone_set(Settings::get('timezone', 'America/Sao_Paulo'));

// Debug Bar (apenas em modo debug)
if (defined('DEBUG_MODE') && DEBUG_MODE === true) {
    DebugBar::register();
}

// Error Handler global (antes de qualquer código que pode falhar)
ErrorHandler::register(DEBUG_MODE ?? false);

// Registrar middlewares padrão
Middleware::register();

// Security headers
Security::setHeaders();

// Nota: função url() agora está em core/helpers.php

// Rotas
require_once __DIR__ . '/routes.php';

// Executar router
Router::run();
