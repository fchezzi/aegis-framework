<?php
/**
 * JWT
 * JSON Web Token implementation
 *
 * Funcionalidades:
 * - Encode/Decode tokens
 * - Validação de assinatura
 * - Verificação de expiração
 * - Refresh tokens
 * - Blacklist de tokens
 *
 * @example
 * // Gerar token
 * $token = JWT::encode([
 *     'sub' => $user['id'],
 *     'email' => $user['email'],
 *     'role' => 'admin'
 * ]);
 *
 * // Decodificar token
 * try {
 *     $payload = JWT::decode($token);
 *     echo $payload['sub']; // ID do usuário
 * } catch (Exception $e) {
 *     // Token inválido ou expirado
 * }
 *
 * // Refresh token
 * $newToken = JWT::refresh($oldToken);
 *
 * // Invalidar token
 * JWT::invalidate($token);
 */

class JWT {

    /**
     * Algoritmo de assinatura
     */
    const ALGO = 'HS256';

    /**
     * Mapeamento de algoritmos
     */
    private static $algos = [
        'HS256' => 'sha256',
        'HS384' => 'sha384',
        'HS512' => 'sha512'
    ];

    /**
     * TTL padrão em segundos (1 hora)
     */
    private static $ttl = 3600;

    /**
     * TTL do refresh token (7 dias)
     */
    private static $refreshTtl = 604800;

    /**
     * Secret key
     */
    private static $secret = null;

    /**
     * Configurar JWT
     *
     * @param array $config
     */
    public static function configure($config) {
        if (isset($config['secret'])) {
            self::$secret = $config['secret'];
        }
        if (isset($config['ttl'])) {
            self::$ttl = $config['ttl'];
        }
        if (isset($config['refresh_ttl'])) {
            self::$refreshTtl = $config['refresh_ttl'];
        }
    }

    /**
     * Obter secret key
     *
     * @return string
     * @throws Exception
     */
    private static function getSecret() {
        if (self::$secret === null) {
            self::$secret = defined('JWT_SECRET') ? JWT_SECRET : (defined('APP_KEY') ? APP_KEY : null);
        }

        if (empty(self::$secret)) {
            throw new Exception('JWT secret key não configurada. Defina JWT_SECRET ou APP_KEY.');
        }

        return self::$secret;
    }

    /**
     * Codificar payload em JWT
     *
     * @param array $payload Dados do usuário
     * @param int|null $ttl TTL em segundos (null = padrão)
     * @return string Token JWT
     */
    public static function encode($payload, $ttl = null) {
        $ttl = $ttl ?? self::$ttl;
        $now = time();

        // Claims padrão
        $payload = array_merge([
            'iat' => $now,                    // Issued at
            'exp' => $now + $ttl,             // Expiration
            'nbf' => $now,                    // Not before
            'jti' => self::generateJti()      // JWT ID único
        ], $payload);

        // Header
        $header = [
            'typ' => 'JWT',
            'alg' => self::ALGO
        ];

        // Encode
        $segments = [];
        $segments[] = self::base64UrlEncode(json_encode($header));
        $segments[] = self::base64UrlEncode(json_encode($payload));

        // Signature
        $signingInput = implode('.', $segments);
        $signature = self::sign($signingInput);
        $segments[] = self::base64UrlEncode($signature);

        return implode('.', $segments);
    }

    /**
     * Decodificar e validar JWT
     *
     * @param string $token
     * @return array Payload
     * @throws Exception Se token inválido
     */
    public static function decode($token) {
        $parts = explode('.', $token);

        if (count($parts) !== 3) {
            throw new Exception('Token JWT malformado');
        }

        list($headerB64, $payloadB64, $signatureB64) = $parts;

        // Verificar assinatura
        $signingInput = "{$headerB64}.{$payloadB64}";
        $signature = self::base64UrlDecode($signatureB64);

        if (!self::verify($signingInput, $signature)) {
            throw new Exception('Assinatura do token inválida');
        }

        // Decodificar payload
        $payload = json_decode(self::base64UrlDecode($payloadB64), true);

        if (!$payload) {
            throw new Exception('Payload do token inválido');
        }

        // Verificar expiração
        if (isset($payload['exp']) && $payload['exp'] < time()) {
            throw new Exception('Token expirado');
        }

        // Verificar not before
        if (isset($payload['nbf']) && $payload['nbf'] > time()) {
            throw new Exception('Token ainda não válido');
        }

        // Verificar blacklist
        if (isset($payload['jti']) && self::isBlacklisted($payload['jti'])) {
            throw new Exception('Token foi invalidado');
        }

        return $payload;
    }

    /**
     * Verificar se token é válido (sem lançar exceção)
     *
     * @param string $token
     * @return bool
     */
    public static function check($token) {
        try {
            self::decode($token);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Refresh token
     *
     * @param string $token Token atual
     * @param int|null $ttl Novo TTL
     * @return string Novo token
     * @throws Exception
     */
    public static function refresh($token, $ttl = null) {
        $payload = self::decode($token);

        // Verificar se pode fazer refresh
        $refreshLimit = $payload['iat'] + self::$refreshTtl;
        if (time() > $refreshLimit) {
            throw new Exception('Token não pode mais ser renovado');
        }

        // Invalidar token antigo
        if (isset($payload['jti'])) {
            self::blacklist($payload['jti'], $payload['exp'] ?? time() + 3600);
        }

        // Remover claims que serão regenerados
        unset($payload['iat'], $payload['exp'], $payload['nbf'], $payload['jti']);

        // Gerar novo token
        return self::encode($payload, $ttl);
    }

    /**
     * Invalidar token
     *
     * @param string $token
     */
    public static function invalidate($token) {
        try {
            $payload = self::decode($token);

            if (isset($payload['jti'])) {
                self::blacklist($payload['jti'], $payload['exp'] ?? time() + 3600);
            }
        } catch (Exception $e) {
            // Token já inválido, ignorar
        }
    }

    /**
     * Gerar par de tokens (access + refresh)
     *
     * @param array $payload
     * @return array ['access_token' => ..., 'refresh_token' => ..., 'expires_in' => ...]
     */
    public static function createTokenPair($payload) {
        // Access token
        $accessToken = self::encode($payload, self::$ttl);

        // Refresh token (mais longo, menos dados)
        $refreshPayload = [
            'sub' => $payload['sub'] ?? $payload['id'] ?? null,
            'type' => 'refresh'
        ];
        $refreshToken = self::encode($refreshPayload, self::$refreshTtl);

        return [
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'token_type' => 'Bearer',
            'expires_in' => self::$ttl
        ];
    }

    /**
     * Obter payload sem validar (útil para debug)
     *
     * @param string $token
     * @return array|null
     */
    public static function getPayload($token) {
        $parts = explode('.', $token);

        if (count($parts) !== 3) {
            return null;
        }

        return json_decode(self::base64UrlDecode($parts[1]), true);
    }

    /**
     * Obter tempo restante do token
     *
     * @param string $token
     * @return int Segundos restantes (0 se expirado)
     */
    public static function getTimeRemaining($token) {
        $payload = self::getPayload($token);

        if (!$payload || !isset($payload['exp'])) {
            return 0;
        }

        return max(0, $payload['exp'] - time());
    }

    // ===================
    // BLACKLIST
    // ===================

    /**
     * Adicionar JTI à blacklist
     *
     * @param string $jti
     * @param int $expiration Timestamp de expiração
     */
    private static function blacklist($jti, $expiration) {
        $ttl = max(0, $expiration - time());

        if ($ttl > 0) {
            Cache::set("jwt_blacklist:{$jti}", true, $ttl);
        }
    }

    /**
     * Verificar se JTI está na blacklist
     *
     * @param string $jti
     * @return bool
     */
    private static function isBlacklisted($jti) {
        return Cache::has("jwt_blacklist:{$jti}");
    }

    // ===================
    // HELPERS
    // ===================

    /**
     * Gerar JTI único
     *
     * @return string
     */
    private static function generateJti() {
        return bin2hex(random_bytes(16));
    }

    /**
     * Assinar dados
     *
     * @param string $data
     * @return string
     */
    private static function sign($data) {
        $algo = self::$algos[self::ALGO] ?? 'sha256';
        return hash_hmac($algo, $data, self::getSecret(), true);
    }

    /**
     * Verificar assinatura
     *
     * @param string $data
     * @param string $signature
     * @return bool
     */
    private static function verify($data, $signature) {
        $expected = self::sign($data);
        return hash_equals($expected, $signature);
    }

    /**
     * Base64 URL encode
     *
     * @param string $data
     * @return string
     */
    private static function base64UrlEncode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Base64 URL decode
     *
     * @param string $data
     * @return string
     */
    private static function base64UrlDecode($data) {
        $remainder = strlen($data) % 4;
        if ($remainder) {
            $data .= str_repeat('=', 4 - $remainder);
        }
        return base64_decode(strtr($data, '-_', '+/'));
    }
}
