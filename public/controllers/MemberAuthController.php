<?php
/**
 * MemberAuthController
 * Autenticação pública de membros (usuários do site)
 */

class MemberAuthController extends BaseController {

    /**
     * Exibir formulário de login
     */
    public function login() {
        // Verificar se sistema de membros está habilitado
        if (!$this->membersEnabled()) {
            $this->redirect('/');
            return;
        }

        // Se já está logado, redirecionar para home
        if ($this->isMemberAuthenticated()) {
            $this->redirect('/home');
            return;
        }

        $this->renderPublic('views/login');
    }

    /**
     * Processar login
     */
    public function doLogin() {
        if (!$this->membersEnabled()) {
            $this->redirect('/');
            return;
        }

        try {
            $this->validateCSRF();

            $email = $this->input('email');
            $password = $_POST['password'] ?? '';

            if (empty($email) || empty($password)) {
                throw new Exception("Email e senha são obrigatórios");
            }

            // Rate limiting - 5 tentativas em 5 minutos
            $rateLimit = RateLimiter::loginAttempt($email, 5, 300);

            if (!$rateLimit['allowed']) {
                $retryAfter = $rateLimit['retry_after'];
                Logger::getInstance()->security('Member login bloqueado por rate limit', [
                    'email' => $email,
                    'attempts' => $rateLimit['ip_attempts']
                ]);
                throw new Exception("Muitas tentativas. Aguarde {$retryAfter} segundos.");
            }

            if (MemberAuth::login($email, $password)) {
                // Login bem sucedido - limpar rate limit
                RateLimiter::loginSuccess($email);

                $member = MemberAuth::member();
                Logger::getInstance()->audit('Member login', $member['id'], ['email' => $email]);

                // Verificar redirect após login
                $redirectUrl = $_SESSION['redirect_after_login'] ?? '/home';
                unset($_SESSION['redirect_after_login']);
                $this->redirect($redirectUrl);
            } else {
                // Login falhou - registrar tentativa
                RateLimiter::loginFailed($email);

                Logger::getInstance()->security('Tentativa de login member falhou', [
                    'email' => $email,
                    'remaining' => $rateLimit['remaining'] - 1
                ]);

                throw new Exception("Email ou senha inválidos");
            }

        } catch (Exception $e) {
            $this->error($e->getMessage());
            $this->redirect('/login');
        }
    }

    /**
     * Logout
     */
    public function logout() {
        if (!$this->membersEnabled()) {
            $this->redirect('/');
            return;
        }

        $member = MemberAuth::member();
        if ($member) {
            Logger::getInstance()->audit('Member logout', $member['id']);
        }

        MemberAuth::logout();
        $this->success("Logout realizado com sucesso");
        $this->redirect('/login');
    }
}
