<?php
/**
 * Notification
 * Sistema de notificações multi-canal
 *
 * Canais suportados:
 * - database: Salva em tabela para exibição na UI
 * - mail: Envia por email
 * - sms: Envia por SMS (requer integração)
 * - slack: Envia para Slack webhook
 * - broadcast: WebSocket/SSE em tempo real
 *
 * @example
 * // Enviar notificação
 * Notification::send($user, new OrderShippedNotification($order));
 *
 * // Enviar para múltiplos usuários
 * Notification::send($users, new WelcomeNotification());
 *
 * // Canal específico
 * Notification::via('mail')->send($user, new PasswordResetNotification());
 *
 * // Obter notificações do usuário
 * $notifications = Notification::for($userId)->get();
 * $unread = Notification::for($userId)->unread()->get();
 */

class Notification {

    /**
     * Canais a usar
     */
    private static $channels = null;

    /**
     * Configurações
     */
    private static $config = [
        'table' => 'notifications',
        'mail_from' => null,
        'slack_webhook' => null,
        'sms_provider' => null
    ];

    /**
     * Usuário alvo (para queries)
     */
    private $userId = null;

    /**
     * Filtros de query
     */
    private $filters = [];

    // ===================
    // CONFIGURATION
    // ===================

    /**
     * Configurar sistema de notificações
     */
    public static function configure(array $config) {
        self::$config = array_merge(self::$config, $config);
    }

    /**
     * Especificar canais
     */
    public static function via($channels) {
        self::$channels = is_array($channels) ? $channels : func_get_args();
        return new static;
    }

    // ===================
    // SENDING
    // ===================

    /**
     * Enviar notificação
     *
     * @param mixed $notifiable Usuário ou array de usuários
     * @param Notifiable $notification Instância da notificação
     */
    public static function send($notifiable, Notifiable $notification) {
        $notifiables = is_array($notifiable) ? $notifiable : [$notifiable];

        foreach ($notifiables as $target) {
            self::sendToNotifiable($target, $notification);
        }

        // Reset channels
        self::$channels = null;
    }

    /**
     * Enviar para um notifiable
     */
    private static function sendToNotifiable($notifiable, Notifiable $notification) {
        // Determinar canais
        $channels = self::$channels ?? $notification->via($notifiable);

        foreach ($channels as $channel) {
            try {
                self::sendViaChannel($channel, $notifiable, $notification);
            } catch (Exception $e) {
                Logger::error("Failed to send notification via {$channel}", [
                    'notifiable' => self::getNotifiableId($notifiable),
                    'notification' => get_class($notification),
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    /**
     * Enviar via canal específico
     */
    private static function sendViaChannel($channel, $notifiable, Notifiable $notification) {
        switch ($channel) {
            case 'database':
                self::sendToDatabase($notifiable, $notification);
                break;
            case 'mail':
                self::sendToMail($notifiable, $notification);
                break;
            case 'sms':
                self::sendToSms($notifiable, $notification);
                break;
            case 'slack':
                self::sendToSlack($notifiable, $notification);
                break;
            case 'broadcast':
                self::sendToBroadcast($notifiable, $notification);
                break;
            default:
                throw new RuntimeException("Unknown notification channel: {$channel}");
        }
    }

    // ===================
    // DATABASE CHANNEL
    // ===================

    /**
     * Salvar notificação no banco
     */
    private static function sendToDatabase($notifiable, Notifiable $notification) {
        $data = $notification->toDatabase($notifiable);

        DB::table(self::$config['table'])->insert([
            'id' => self::generateUuid(),
            'type' => get_class($notification),
            'notifiable_type' => self::getNotifiableType($notifiable),
            'notifiable_id' => self::getNotifiableId($notifiable),
            'data' => json_encode($data),
            'read_at' => null,
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }

    // ===================
    // MAIL CHANNEL
    // ===================

    /**
     * Enviar por email
     */
    private static function sendToMail($notifiable, Notifiable $notification) {
        if (!method_exists($notification, 'toMail')) {
            return;
        }

        $mail = $notification->toMail($notifiable);

        if (!$mail instanceof NotificationMail) {
            return;
        }

        $to = self::getNotifiableEmail($notifiable);

        if (!$to) {
            return;
        }

        // Usar classe Mailer se existir
        if (class_exists('Mailer')) {
            Mailer::send($to, $mail->subject, $mail->render());
        } else {
            // Fallback para mail() nativo
            $headers = "MIME-Version: 1.0\r\n";
            $headers .= "Content-type: text/html; charset=UTF-8\r\n";

            if (self::$config['mail_from']) {
                $headers .= "From: " . self::$config['mail_from'] . "\r\n";
            }

            mail($to, $mail->subject, $mail->render(), $headers);
        }
    }

    // ===================
    // SMS CHANNEL
    // ===================

    /**
     * Enviar por SMS
     */
    private static function sendToSms($notifiable, Notifiable $notification) {
        if (!method_exists($notification, 'toSms')) {
            return;
        }

        $message = $notification->toSms($notifiable);
        $phone = self::getNotifiablePhone($notifiable);

        if (!$phone || !$message) {
            return;
        }

        // Implementar integração com provider de SMS
        // Ex: Twilio, AWS SNS, etc.
        Logger::info("SMS notification queued", [
            'phone' => $phone,
            'message' => substr($message, 0, 50)
        ]);
    }

    // ===================
    // SLACK CHANNEL
    // ===================

    /**
     * Enviar para Slack
     */
    private static function sendToSlack($notifiable, Notifiable $notification) {
        if (!method_exists($notification, 'toSlack')) {
            return;
        }

        $webhook = self::$config['slack_webhook'];

        if (!$webhook) {
            return;
        }

        $message = $notification->toSlack($notifiable);

        if (!$message instanceof NotificationSlack) {
            $message = new NotificationSlack($message);
        }

        $payload = $message->toArray();

        $ch = curl_init($webhook);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_exec($ch);
        curl_close($ch);
    }

    // ===================
    // BROADCAST CHANNEL
    // ===================

    /**
     * Broadcast em tempo real
     */
    private static function sendToBroadcast($notifiable, Notifiable $notification) {
        if (!method_exists($notification, 'toBroadcast')) {
            return;
        }

        $data = $notification->toBroadcast($notifiable);
        $channel = 'user.' . self::getNotifiableId($notifiable);

        // Salvar para polling ou usar com WebSocket server
        $broadcastData = [
            'channel' => $channel,
            'event' => get_class($notification),
            'data' => $data,
            'timestamp' => time()
        ];

        // Cache para polling
        $key = "broadcast:{$channel}:" . uniqid();
        Cache::set($key, $broadcastData, 300); // 5 minutos

        // Se tiver servidor WebSocket, enviar
        // Implementação depende do servidor usado
    }

    // ===================
    // QUERY BUILDER
    // ===================

    /**
     * Iniciar query para usuário
     */
    public static function for($userId) {
        $instance = new static;
        $instance->userId = $userId;
        return $instance;
    }

    /**
     * Filtrar não lidas
     */
    public function unread() {
        $this->filters['read_at'] = null;
        return $this;
    }

    /**
     * Filtrar lidas
     */
    public function read() {
        $this->filters['read_at'] = ['!=', null];
        return $this;
    }

    /**
     * Filtrar por tipo
     */
    public function type($type) {
        $this->filters['type'] = $type;
        return $this;
    }

    /**
     * Obter notificações
     */
    public function get($limit = 50) {
        $query = DB::table(self::$config['table'])
            ->where('notifiable_id', $this->userId)
            ->orderBy('created_at', 'DESC')
            ->limit($limit);

        foreach ($this->filters as $field => $value) {
            if (is_array($value)) {
                $query->where($field, $value[0], $value[1]);
            } elseif ($value === null) {
                $query->whereNull($field);
            } else {
                $query->where($field, $value);
            }
        }

        $results = $query->get();

        // Decodificar data
        return array_map(function($row) {
            $row['data'] = json_decode($row['data'], true);
            return $row;
        }, $results);
    }

    /**
     * Contar notificações
     */
    public function count() {
        $query = DB::table(self::$config['table'])
            ->where('notifiable_id', $this->userId);

        foreach ($this->filters as $field => $value) {
            if (is_array($value)) {
                $query->where($field, $value[0], $value[1]);
            } elseif ($value === null) {
                $query->whereNull($field);
            } else {
                $query->where($field, $value);
            }
        }

        return $query->count();
    }

    /**
     * Marcar como lida
     */
    public function markAsRead($id = null) {
        $query = DB::table(self::$config['table'])
            ->where('notifiable_id', $this->userId)
            ->whereNull('read_at');

        if ($id) {
            $query->where('id', $id);
        }

        return $query->update([
            'read_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Marcar todas como lidas
     */
    public function markAllAsRead() {
        return $this->markAsRead();
    }

    /**
     * Deletar notificação
     */
    public function delete($id = null) {
        $query = DB::table(self::$config['table'])
            ->where('notifiable_id', $this->userId);

        if ($id) {
            $query->where('id', $id);
        }

        return $query->delete();
    }

    /**
     * Deletar todas
     */
    public function deleteAll() {
        return $this->delete();
    }

    // ===================
    // HELPERS
    // ===================

    /**
     * Obter ID do notifiable
     */
    private static function getNotifiableId($notifiable) {
        if (is_array($notifiable)) {
            return $notifiable['id'] ?? $notifiable['user_id'] ?? null;
        }
        if (is_object($notifiable)) {
            return $notifiable->id ?? $notifiable->getId() ?? null;
        }
        return $notifiable;
    }

    /**
     * Obter tipo do notifiable
     */
    private static function getNotifiableType($notifiable) {
        if (is_object($notifiable)) {
            return get_class($notifiable);
        }
        return 'User';
    }

    /**
     * Obter email do notifiable
     */
    private static function getNotifiableEmail($notifiable) {
        if (is_array($notifiable)) {
            return $notifiable['email'] ?? null;
        }
        if (is_object($notifiable)) {
            return $notifiable->email ?? null;
        }
        return null;
    }

    /**
     * Obter telefone do notifiable
     */
    private static function getNotifiablePhone($notifiable) {
        if (is_array($notifiable)) {
            return $notifiable['phone'] ?? $notifiable['telefone'] ?? null;
        }
        if (is_object($notifiable)) {
            return $notifiable->phone ?? $notifiable->telefone ?? null;
        }
        return null;
    }

    /**
     * Gerar UUID
     */
    private static function generateUuid() {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }
}

/**
 * Interface para notificações
 */
abstract class Notifiable {

    /**
     * Canais de entrega
     */
    public function via($notifiable) {
        return ['database'];
    }

    /**
     * Representação para banco de dados
     */
    public function toDatabase($notifiable) {
        return $this->toArray($notifiable);
    }

    /**
     * Representação como array
     */
    abstract public function toArray($notifiable);
}

/**
 * Builder para email de notificação
 */
class NotificationMail {

    public $subject = '';
    public $greeting = 'Olá!';
    public $lines = [];
    public $action = null;
    public $salutation = 'Atenciosamente';
    public $view = null;
    public $viewData = [];

    /**
     * Definir assunto
     */
    public function subject($subject) {
        $this->subject = $subject;
        return $this;
    }

    /**
     * Definir saudação
     */
    public function greeting($greeting) {
        $this->greeting = $greeting;
        return $this;
    }

    /**
     * Adicionar linha de texto
     */
    public function line($line) {
        $this->lines[] = ['type' => 'text', 'content' => $line];
        return $this;
    }

    /**
     * Adicionar botão de ação
     */
    public function action($text, $url) {
        $this->action = ['text' => $text, 'url' => $url];
        return $this;
    }

    /**
     * Definir despedida
     */
    public function salutation($salutation) {
        $this->salutation = $salutation;
        return $this;
    }

    /**
     * Usar view customizada
     */
    public function view($view, array $data = []) {
        $this->view = $view;
        $this->viewData = $data;
        return $this;
    }

    /**
     * Renderizar email
     */
    public function render() {
        if ($this->view) {
            return View::render($this->view, $this->viewData);
        }

        // Template padrão
        $html = '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">';
        $html .= '<h2>' . htmlspecialchars($this->greeting) . '</h2>';

        foreach ($this->lines as $line) {
            $html .= '<p>' . htmlspecialchars($line['content']) . '</p>';
        }

        if ($this->action) {
            $html .= '<p style="text-align: center; margin: 30px 0;">';
            $html .= '<a href="' . htmlspecialchars($this->action['url']) . '" ';
            $html .= 'style="background: #3490dc; color: white; padding: 12px 24px; text-decoration: none; border-radius: 4px;">';
            $html .= htmlspecialchars($this->action['text']);
            $html .= '</a></p>';
        }

        $html .= '<p style="color: #666; margin-top: 30px;">' . htmlspecialchars($this->salutation) . '</p>';
        $html .= '</div>';

        return $html;
    }
}

/**
 * Builder para Slack
 */
class NotificationSlack {

    private $text;
    private $channel;
    private $username;
    private $icon;
    private $attachments = [];

    public function __construct($text = '') {
        $this->text = $text;
    }

    /**
     * Definir texto
     */
    public function content($text) {
        $this->text = $text;
        return $this;
    }

    /**
     * Definir canal
     */
    public function to($channel) {
        $this->channel = $channel;
        return $this;
    }

    /**
     * Definir username do bot
     */
    public function from($username, $icon = null) {
        $this->username = $username;
        $this->icon = $icon;
        return $this;
    }

    /**
     * Adicionar attachment
     */
    public function attachment(callable $callback) {
        $attachment = new NotificationSlackAttachment();
        $callback($attachment);
        $this->attachments[] = $attachment->toArray();
        return $this;
    }

    /**
     * Converter para array
     */
    public function toArray() {
        $payload = ['text' => $this->text];

        if ($this->channel) {
            $payload['channel'] = $this->channel;
        }
        if ($this->username) {
            $payload['username'] = $this->username;
        }
        if ($this->icon) {
            $payload['icon_emoji'] = $this->icon;
        }
        if (!empty($this->attachments)) {
            $payload['attachments'] = $this->attachments;
        }

        return $payload;
    }
}

/**
 * Attachment do Slack
 */
class NotificationSlackAttachment {

    private $data = [];

    public function title($title, $url = null) {
        $this->data['title'] = $title;
        if ($url) {
            $this->data['title_link'] = $url;
        }
        return $this;
    }

    public function content($text) {
        $this->data['text'] = $text;
        return $this;
    }

    public function color($color) {
        $this->data['color'] = $color;
        return $this;
    }

    public function field($title, $value, $short = true) {
        $this->data['fields'][] = [
            'title' => $title,
            'value' => $value,
            'short' => $short
        ];
        return $this;
    }

    public function footer($text, $icon = null) {
        $this->data['footer'] = $text;
        if ($icon) {
            $this->data['footer_icon'] = $icon;
        }
        return $this;
    }

    public function timestamp($timestamp) {
        $this->data['ts'] = $timestamp;
        return $this;
    }

    public function toArray() {
        return $this->data;
    }
}
