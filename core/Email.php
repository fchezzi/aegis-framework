<?php
/**
 * AEGIS Framework - Email Helper
 * PHPMailer wrapper for sending emails
 * Version: 1.0.0
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class Email {

    /**
     * Envia email com anexo opcional
     *
     * @param string $to Email destinatário
     * @param string $subject Assunto
     * @param string $htmlBody Corpo HTML do email
     * @param string|null $attachment Caminho completo do arquivo para anexar
     * @param string|null $attachmentName Nome customizado do arquivo anexo
     * @return bool True se enviado com sucesso
     * @throws Exception Se houver erro no envio
     */
    public static function send($to, $subject, $htmlBody, $attachment = null, $attachmentName = null) {

        // Validações básicas
        if (empty($to) || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
            error_log("ERRO Email::send() - Email destinatário inválido: {$to}");
            return false;
        }

        if (empty($subject) || empty($htmlBody)) {
            error_log("ERRO Email::send() - Assunto ou corpo vazio");
            return false;
        }

        // Verificar configurações SMTP
        if (empty(SMTP_HOST) || empty(SMTP_USERNAME) || empty(SMTP_PASSWORD)) {
            error_log("ERRO Email::send() - Configurações SMTP não definidas em _config.php");
            return false;
        }

        try {
            $mail = new PHPMailer(true);

            // Configuração SMTP
            $mail->isSMTP();
            $mail->Host = SMTP_HOST;
            $mail->SMTPAuth = true;
            $mail->Username = SMTP_USERNAME;
            $mail->Password = SMTP_PASSWORD;
            $mail->SMTPSecure = SMTP_ENCRYPTION;
            $mail->Port = SMTP_PORT;
            $mail->CharSet = 'UTF-8';

            // Debug (apenas se DEBUG_MODE estiver ativo)
            if (defined('DEBUG_MODE') && DEBUG_MODE) {
                $mail->SMTPDebug = 2;
            } else {
                $mail->SMTPDebug = 0;
            }

            // Remetente
            $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);

            // Destinatário
            $mail->addAddress($to);

            // Anexo (se fornecido)
            if ($attachment !== null && file_exists($attachment)) {
                $filename = $attachmentName ?? basename($attachment);
                $mail->addAttachment($attachment, $filename);
                error_log("Email::send() - Anexando arquivo: {$attachment} como {$filename}");
            } elseif ($attachment !== null) {
                error_log("AVISO Email::send() - Arquivo não encontrado: {$attachment}");
            }

            // Conteúdo
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $htmlBody;
            $mail->AltBody = strip_tags($htmlBody); // Versão texto puro

            // Enviar
            $result = $mail->send();

            if ($result) {
                error_log("Email::send() - Email enviado com sucesso para: {$to}");
            }

            return $result;

        } catch (Exception $e) {
            error_log("ERRO Email::send() - Falha ao enviar email: {$mail->ErrorInfo}");
            error_log("ERRO Email::send() - Exception: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Envia alerta técnico usando SMTP de alertas
     *
     * @param string $to Email destinatário
     * @param string $subject Assunto
     * @param string $htmlBody Corpo HTML do email
     * @return bool True se enviado com sucesso
     * @throws Exception Se houver erro no envio
     */
    public static function sendAlert($to, $subject, $htmlBody) {

        // Validações básicas
        if (empty($to) || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
            error_log("ERRO Email::sendAlert() - Email destinatário inválido: {$to}");
            return false;
        }

        if (empty($subject) || empty($htmlBody)) {
            error_log("ERRO Email::sendAlert() - Assunto ou corpo vazio");
            return false;
        }

        // Buscar configurações de SMTP de alertas do Settings
        if (!class_exists('Settings')) {
            error_log("ERRO Email::sendAlert() - Classe Settings não disponível");
            return false;
        }

        $alertSmtpHost = Settings::get('alert_smtp_host');
        $alertSmtpPort = Settings::get('alert_smtp_port', 587);
        $alertSmtpUsername = Settings::get('alert_smtp_username');
        $alertSmtpPassword = Settings::get('alert_smtp_password');
        $alertSmtpFromEmail = Settings::get('alert_smtp_from_email');
        $alertSmtpFromName = Settings::get('alert_smtp_from_name', 'AEGIS Alertas');
        $alertSmtpEncryption = Settings::get('alert_smtp_encryption', 'tls');

        // Verificar se SMTP de alertas está configurado
        if (empty($alertSmtpHost) || empty($alertSmtpUsername) || empty($alertSmtpPassword)) {
            error_log("ERRO Email::sendAlert() - SMTP de alertas não configurado");
            return false;
        }

        try {
            $mail = new PHPMailer(true);

            // Configuração SMTP de alertas
            $mail->isSMTP();
            $mail->Host = $alertSmtpHost;
            $mail->SMTPAuth = true;
            $mail->Username = $alertSmtpUsername;
            $mail->Password = $alertSmtpPassword;
            $mail->SMTPSecure = $alertSmtpEncryption;
            $mail->Port = $alertSmtpPort;
            $mail->CharSet = 'UTF-8';

            // Debug (apenas se DEBUG_MODE estiver ativo)
            if (defined('DEBUG_MODE') && DEBUG_MODE) {
                $mail->SMTPDebug = 2;
            } else {
                $mail->SMTPDebug = 0;
            }

            // Remetente
            $mail->setFrom($alertSmtpFromEmail, $alertSmtpFromName);

            // Destinatário
            $mail->addAddress($to);

            // Conteúdo
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $htmlBody;
            $mail->AltBody = strip_tags($htmlBody); // Versão texto puro

            // Enviar
            $result = $mail->send();

            if ($result) {
                error_log("Email::sendAlert() - Alerta enviado com sucesso para: {$to}");
            }

            return $result;

        } catch (Exception $e) {
            error_log("ERRO Email::sendAlert() - Falha ao enviar alerta: {$mail->ErrorInfo}");
            error_log("ERRO Email::sendAlert() - Exception: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Template HTML para emails do Instituto Atualli
     *
     * @param string $titulo Título principal do email
     * @param string $conteudo Conteúdo principal (HTML permitido)
     * @param string|null $btnTexto Texto do botão (opcional)
     * @param string|null $btnLink Link do botão (opcional)
     * @return string HTML completo do email
     */
    public static function template($titulo, $conteudo, $btnTexto = null, $btnLink = null) {
        $btn = '';
        if ($btnTexto && $btnLink) {
            $btn = '<table width="100%" cellpadding="0" cellspacing="0" style="margin: 30px 0;">
                <tr>
                    <td align="center">
                        <a href="' . htmlspecialchars($btnLink, ENT_QUOTES) . '"
                           style="background: #2d808c; color: white; padding: 14px 30px;
                                  text-decoration: none; border-radius: 4px; font-weight: bold;
                                  display: inline-block;">
                            ' . htmlspecialchars($btnTexto, ENT_QUOTES) . '
                        </a>
                    </td>
                </tr>
            </table>';
        }

        return '<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . htmlspecialchars($titulo, ENT_QUOTES) . '</title>
</head>
<body style="margin: 0; padding: 0; font-family: Arial, sans-serif; background: #f4f4f4;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background: #f4f4f4; padding: 20px 0;">
        <tr>
            <td align="center">
                <!-- Container principal -->
                <table width="600" cellpadding="0" cellspacing="0" style="background: white; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">

                    <!-- Header -->
                    <tr>
                        <td style="background: #2d808c; padding: 30px; text-align: center; border-radius: 8px 8px 0 0;">
                            <h1 style="margin: 0; color: white; font-size: 28px; font-weight: 600;">
                                Instituto Atualli
                            </h1>
                        </td>
                    </tr>

                    <!-- Conteúdo -->
                    <tr>
                        <td style="padding: 40px 30px;">
                            <h2 style="margin: 0 0 20px 0; color: #2d808c; font-size: 22px;">
                                ' . htmlspecialchars($titulo, ENT_QUOTES) . '
                            </h2>
                            <div style="color: #333; font-size: 16px; line-height: 1.6;">
                                ' . $conteudo . '
                            </div>
                            ' . $btn . '
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background: #f9f9f9; padding: 20px 30px; text-align: center; border-radius: 0 0 8px 8px; border-top: 1px solid #eee;">
                            <p style="margin: 0; color: #666; font-size: 14px;">
                                © ' . date('Y') . ' Instituto Atualli - Todos os direitos reservados
                            </p>
                            <p style="margin: 10px 0 0 0; color: #999; font-size: 12px;">
                                Este email foi enviado porque você solicitou um artigo científico em nosso site.
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>';
    }

    /**
     * Envia email de artigo com PDF anexo
     *
     * @param string $to Email destinatário
     * @param string $nome Nome do destinatário
     * @param string $tituloArtigo Título do artigo
     * @param string $pdfPath Caminho completo do PDF
     * @return bool True se enviado com sucesso
     */
    public static function enviarArtigo($to, $nome, $tituloArtigo, $pdfPath) {

        $subject = "Seu artigo científico: {$tituloArtigo}";

        $conteudo = '<p>Olá <strong>' . htmlspecialchars($nome, ENT_QUOTES) . '</strong>,</p>
            <p>Obrigado pelo seu interesse em nosso conteúdo científico!</p>
            <p>Conforme solicitado, segue em anexo o artigo científico:</p>
            <p style="font-size: 18px; color: #2d808c; font-weight: 600; margin: 20px 0;">
                "' . htmlspecialchars($tituloArtigo, ENT_QUOTES) . '"
            </p>
            <p>O arquivo está em formato PDF e pode ser visualizado em qualquer dispositivo.</p>
            <p>Se tiver dúvidas ou quiser saber mais sobre nossos estudos, não hesite em entrar em contato.</p>
            <p style="margin-top: 30px;">Boa leitura! 📖</p>';

        $htmlBody = self::template(
            'Seu artigo científico está aqui!',
            $conteudo,
            'Acessar nosso site',
            APP_URL . '/artigos'
        );

        return self::send($to, $subject, $htmlBody, $pdfPath, basename($pdfPath));
    }
}
