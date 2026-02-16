<?php
/**
 * API Routes
 * Definição de rotas da API REST
 *
 * Rotas são automaticamente prefixadas com /api/vX
 * Middlewares padrão: throttle (60 req/min)
 *
 * @example
 * // Acessar API v1
 * GET /api/v1/users
 * GET /api/v1/users/123
 * POST /api/v1/users (com Bearer token)
 *
 * // Acessar API v2
 * GET /api/v2/users
 */

// ===================
// API v1
// ===================

ApiRouter::version('v1', function() {

    // Endpoint público para listar versões
    ApiRouter::get('/versions', function() {
        header('Content-Type: application/json');
        echo json_encode(ApiRouter::versionsEndpoint());
        exit;
    });

    // Rotas públicas (sem autenticação)
    ApiRouter::get('/status', function() {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'data' => [
                'status' => 'online',
                'version' => 'v1',
                'timestamp' => date('c')
            ]
        ]);
        exit;
    });

    // ===================
    // AUTENTICAÇÃO
    // ===================

    // Login - retorna tokens JWT
    ApiRouter::post('/auth/login', 'AuthApiController@login');

    // Refresh token
    ApiRouter::post('/auth/refresh', 'AuthApiController@refresh');

    // Logout (invalidar token)
    ApiRouter::post('/auth/logout', 'AuthApiController@logout');

    // ===================
    // ROTAS AUTENTICADAS
    // ===================

    ApiRouter::auth(function() {

        // Usuário logado
        ApiRouter::get('/auth/me', 'AuthApiController@me');

        // Exemplo: Resource completo de usuários (requer auth)
        // Descomente para ativar:
        // ApiRouter::resource('/users', 'UsersApiController');

        // Exemplo: Resource apenas leitura
        // ApiRouter::apiResource('/posts', 'PostsApiController');

        // Exemplo: Rotas protegidas por role
        // ApiRouter::delete('/users/{id}', 'UsersApiController@destroy')
        //     ->middleware(Middleware::role('admin'));

    });

});

// ===================
// API v2 (Exemplo)
// ===================

// Descomente para criar v2:
/*
ApiRouter::version('v2', function() {

    // Herda mesma estrutura mas pode ter controllers diferentes
    ApiRouter::resource('/users', 'V2\UsersApiController');

    // Novos endpoints exclusivos da v2
    ApiRouter::get('/analytics', 'V2\AnalyticsApiController@index');

}, ['middleware' => ['api.auth']]); // v2 requer auth em tudo
*/

// ===================
// DEPRECATION (Exemplo)
// ===================

// Para deprecar uma versão:
/*
ApiRouter::version('v0', function() {
    ApiRouter::get('/legacy', 'LegacyController@index');
}, [
    'deprecated' => true,
    'sunset' => 'Sat, 31 Dec 2025 23:59:59 GMT'
]);
*/
