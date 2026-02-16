<?php
/**
 * Auth Controller
 * Gerencia autenticação de administradores
 */

class AuthController extends BaseController {

    /**
     * Processar login de admin
     */
    public function login() {
        try {
            $this->validateCSRF();

            $email = $this->input('email');
            $password = $_POST['password'] ?? '';

            if (empty($email) || empty($password)) {
                throw new Exception('Email e senha são obrigatórios');
            }

            // Rate limiting - 5 tentativas em 5 minutos
            $rateLimit = RateLimiter::loginAttempt($email, 5, 300);

            if (!$rateLimit['allowed']) {
                $retryAfter = $rateLimit['retry_after'];
                Logger::getInstance()->security('Login bloqueado por rate limit', [
                    'email' => $email,
                    'attempts' => $rateLimit['ip_attempts']
                ]);
                throw new Exception("Muitas tentativas. Aguarde {$retryAfter} segundos.");
            }

            if (Auth::login($email, $password, $this->db())) {
                // Login bem sucedido - limpar rate limit
                RateLimiter::loginSuccess($email);

                Logger::getInstance()->audit('Admin login', Auth::id(), ['email' => $email]);

                $this->redirect('/admin/dashboard');
            } else {
                // Login falhou - registrar tentativa
                RateLimiter::loginFailed($email);

                Logger::getInstance()->security('Tentativa de login admin falhou', [
                    'email' => $email,
                    'remaining' => $rateLimit['remaining'] - 1
                ]);

                $_SESSION['login_error'] = 'Email ou senha incorretos';
                $this->redirect('/admin/login');
            }

        } catch (Exception $e) {
            $_SESSION['login_error'] = $e->getMessage();
            $this->redirect('/admin/login');
        }
    }

    /**
     * Processar logout
     */
    public function logout() {
        $userId = Auth::id();
        if ($userId) {
            Logger::getInstance()->audit('Admin logout', $userId);
        }

        Auth::logout();
        $this->redirect('/admin/login');
    }
}
