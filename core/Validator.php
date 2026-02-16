<?php
/**
 * Validator
 * Validação robusta de dados de entrada
 *
 * Regras disponíveis:
 * - required: Campo obrigatório
 * - email: Email válido
 * - min:n: Mínimo n caracteres
 * - max:n: Máximo n caracteres
 * - numeric: Apenas números
 * - alpha: Apenas letras
 * - alphanumeric: Letras e números
 * - url: URL válida
 * - date: Data válida
 * - in:a,b,c: Valor em lista
 * - regex:pattern: Regex customizado
 * - confirmed: Campo_confirmation deve existir e ser igual
 * - unique:table,column: Único no banco
 * - exists:table,column: Deve existir no banco
 * - slug: Formato slug (a-z, 0-9, -)
 * - uuid: UUID válido
 * - json: JSON válido
 * - file: É arquivo uploadado
 * - image: É imagem (jpg, png, gif, webp)
 * - mimes:jpg,png: Tipos MIME específicos
 * - max_size:1024: Tamanho máximo em KB
 *
 * @example
 * $validator = new Validator($_POST, [
 *     'name' => 'required|min:3|max:100',
 *     'email' => 'required|email|unique:users,email',
 *     'password' => 'required|min:8|confirmed',
 *     'role' => 'required|in:admin,editor,user'
 * ]);
 *
 * if ($validator->fails()) {
 *     $errors = $validator->errors();
 * }
 *
 * $validated = $validator->validated(); // Apenas dados validados
 */

class Validator {

    /**
     * Dados a validar
     */
    private $data = [];

    /**
     * Regras de validação
     */
    private $rules = [];

    /**
     * Erros encontrados
     */
    private $errors = [];

    /**
     * Dados validados
     */
    private $validated = [];

    /**
     * Mensagens customizadas
     */
    private $customMessages = [];

    /**
     * Nomes amigáveis dos campos
     */
    private $attributes = [];

    /**
     * Conexão com banco (para unique/exists)
     */
    private $db = null;

    /**
     * Mensagens padrão
     */
    private static $defaultMessages = [
        'required' => 'O campo :attribute é obrigatório',
        'email' => 'O campo :attribute deve ser um email válido',
        'min' => 'O campo :attribute deve ter no mínimo :min caracteres',
        'max' => 'O campo :attribute deve ter no máximo :max caracteres',
        'numeric' => 'O campo :attribute deve ser numérico',
        'alpha' => 'O campo :attribute deve conter apenas letras',
        'alphanumeric' => 'O campo :attribute deve conter apenas letras e números',
        'url' => 'O campo :attribute deve ser uma URL válida',
        'date' => 'O campo :attribute deve ser uma data válida',
        'in' => 'O valor selecionado para :attribute é inválido',
        'regex' => 'O formato do campo :attribute é inválido',
        'confirmed' => 'A confirmação de :attribute não confere',
        'unique' => 'Este :attribute já está em uso',
        'exists' => 'O :attribute selecionado não existe',
        'slug' => 'O campo :attribute deve ser um slug válido (a-z, 0-9, -)',
        'uuid' => 'O campo :attribute deve ser um UUID válido',
        'json' => 'O campo :attribute deve ser um JSON válido',
        'file' => 'O campo :attribute deve ser um arquivo',
        'image' => 'O campo :attribute deve ser uma imagem',
        'mimes' => 'O campo :attribute deve ser do tipo: :values',
        'max_size' => 'O campo :attribute não pode ser maior que :max KB',
        'integer' => 'O campo :attribute deve ser um número inteiro',
        'boolean' => 'O campo :attribute deve ser verdadeiro ou falso',
        'array' => 'O campo :attribute deve ser um array',
        'between' => 'O campo :attribute deve estar entre :min e :max'
    ];

    /**
     * Construtor
     *
     * @param array $data Dados a validar
     * @param array $rules Regras de validação
     * @param array $messages Mensagens customizadas (opcional)
     * @param array $attributes Nomes amigáveis (opcional)
     */
    public function __construct($data, $rules, $messages = [], $attributes = []) {
        $this->data = $data;
        $this->rules = $rules;
        $this->customMessages = $messages;
        $this->attributes = $attributes;

        $this->validate();
    }

    /**
     * Factory estático
     *
     * @param array $data
     * @param array $rules
     * @return self
     */
    public static function make($data, $rules, $messages = [], $attributes = []) {
        return new self($data, $rules, $messages, $attributes);
    }

    /**
     * Executar validação
     */
    private function validate() {
        foreach ($this->rules as $field => $ruleString) {
            $rules = is_array($ruleString) ? $ruleString : explode('|', $ruleString);
            $value = $this->getValue($field);

            foreach ($rules as $rule) {
                // Parse rule:params
                $params = [];
                if (strpos($rule, ':') !== false) {
                    list($rule, $paramString) = explode(':', $rule, 2);
                    $params = explode(',', $paramString);
                }

                // Se não é required e está vazio, pular outras validações
                if ($rule !== 'required' && $this->isEmpty($value)) {
                    continue;
                }

                // Executar regra
                $method = 'validate' . ucfirst($rule);
                if (method_exists($this, $method)) {
                    $result = $this->$method($field, $value, $params);
                    if ($result !== true) {
                        $this->addError($field, $rule, $params);
                    }
                }
            }

            // Se passou, adicionar aos validados
            if (!isset($this->errors[$field])) {
                $this->validated[$field] = $value;
            }
        }
    }

    /**
     * Obter valor (suporta notação dot: user.name)
     */
    private function getValue($field) {
        $keys = explode('.', $field);
        $value = $this->data;

        foreach ($keys as $key) {
            if (!is_array($value) || !array_key_exists($key, $value)) {
                return null;
            }
            $value = $value[$key];
        }

        return $value;
    }

    /**
     * Verificar se valor está vazio
     */
    private function isEmpty($value) {
        return $value === null || $value === '' || $value === [];
    }

    /**
     * Adicionar erro
     */
    private function addError($field, $rule, $params = []) {
        $message = $this->getMessage($field, $rule, $params);

        if (!isset($this->errors[$field])) {
            $this->errors[$field] = [];
        }

        $this->errors[$field][] = $message;
    }

    /**
     * Obter mensagem de erro
     */
    private function getMessage($field, $rule, $params = []) {
        // Mensagem customizada específica (field.rule)
        $customKey = "{$field}.{$rule}";
        if (isset($this->customMessages[$customKey])) {
            $message = $this->customMessages[$customKey];
        }
        // Mensagem customizada geral (rule)
        elseif (isset($this->customMessages[$rule])) {
            $message = $this->customMessages[$rule];
        }
        // Mensagem padrão
        else {
            $message = self::$defaultMessages[$rule] ?? "Validação {$rule} falhou para :attribute";
        }

        // Substituir placeholders
        $attribute = $this->attributes[$field] ?? str_replace('_', ' ', $field);
        $message = str_replace(':attribute', $attribute, $message);

        // Substituir parâmetros
        if (isset($params[0])) {
            $message = str_replace(':min', $params[0], $message);
            $message = str_replace(':max', $params[0], $message);
            $message = str_replace(':values', implode(', ', $params), $message);
        }
        if (isset($params[1])) {
            $message = str_replace(':max', $params[1], $message);
        }

        return $message;
    }

    // ===================
    // REGRAS DE VALIDAÇÃO
    // ===================

    private function validateRequired($field, $value, $params) {
        return !$this->isEmpty($value);
    }

    private function validateEmail($field, $value, $params) {
        return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    }

    private function validateMin($field, $value, $params) {
        $min = (int) $params[0];
        if (is_string($value)) {
            return mb_strlen($value) >= $min;
        }
        if (is_numeric($value)) {
            return $value >= $min;
        }
        if (is_array($value)) {
            return count($value) >= $min;
        }
        return false;
    }

    private function validateMax($field, $value, $params) {
        $max = (int) $params[0];
        if (is_string($value)) {
            return mb_strlen($value) <= $max;
        }
        if (is_numeric($value)) {
            return $value <= $max;
        }
        if (is_array($value)) {
            return count($value) <= $max;
        }
        return false;
    }

    private function validateBetween($field, $value, $params) {
        $min = (int) $params[0];
        $max = (int) $params[1];
        if (is_string($value)) {
            $len = mb_strlen($value);
            return $len >= $min && $len <= $max;
        }
        if (is_numeric($value)) {
            return $value >= $min && $value <= $max;
        }
        return false;
    }

    private function validateNumeric($field, $value, $params) {
        return is_numeric($value);
    }

    private function validateInteger($field, $value, $params) {
        return filter_var($value, FILTER_VALIDATE_INT) !== false;
    }

    private function validateAlpha($field, $value, $params) {
        return preg_match('/^[\pL\pM]+$/u', $value);
    }

    private function validateAlphanumeric($field, $value, $params) {
        return preg_match('/^[\pL\pM\pN]+$/u', $value);
    }

    private function validateUrl($field, $value, $params) {
        return filter_var($value, FILTER_VALIDATE_URL) !== false;
    }

    private function validateDate($field, $value, $params) {
        $format = $params[0] ?? 'Y-m-d';
        $d = DateTime::createFromFormat($format, $value);
        return $d && $d->format($format) === $value;
    }

    private function validateIn($field, $value, $params) {
        return in_array($value, $params);
    }

    private function validateRegex($field, $value, $params) {
        return preg_match($params[0], $value);
    }

    private function validateConfirmed($field, $value, $params) {
        $confirmField = $field . '_confirmation';
        return isset($this->data[$confirmField]) && $value === $this->data[$confirmField];
    }

    private function validateSlug($field, $value, $params) {
        return preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $value);
    }

    private function validateUuid($field, $value, $params) {
        return preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $value);
    }

    private function validateJson($field, $value, $params) {
        json_decode($value);
        return json_last_error() === JSON_ERROR_NONE;
    }

    private function validateBoolean($field, $value, $params) {
        return in_array($value, [true, false, 0, 1, '0', '1', 'true', 'false'], true);
    }

    private function validateArray($field, $value, $params) {
        return is_array($value);
    }

    private function validateUnique($field, $value, $params) {
        if ($this->db === null) {
            $this->db = DB::connect();
        }

        $table = $params[0];
        $column = $params[1] ?? $field;
        $exceptId = $params[2] ?? null;

        $sql = "SELECT COUNT(*) as count FROM {$table} WHERE {$column} = ?";
        $bindings = [$value];

        if ($exceptId) {
            $sql .= " AND id != ?";
            $bindings[] = $exceptId;
        }

        $result = $this->db->query($sql, $bindings);
        return ($result[0]['count'] ?? 0) == 0;
    }

    private function validateExists($field, $value, $params) {
        if ($this->db === null) {
            $this->db = DB::connect();
        }

        $table = $params[0];
        $column = $params[1] ?? $field;

        $result = $this->db->query(
            "SELECT COUNT(*) as count FROM {$table} WHERE {$column} = ?",
            [$value]
        );

        return ($result[0]['count'] ?? 0) > 0;
    }

    private function validateFile($field, $value, $params) {
        return isset($_FILES[$field]) && $_FILES[$field]['error'] === UPLOAD_ERR_OK;
    }

    private function validateImage($field, $value, $params) {
        if (!$this->validateFile($field, $value, $params)) {
            return false;
        }

        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        return in_array($_FILES[$field]['type'], $allowedTypes);
    }

    private function validateMimes($field, $value, $params) {
        if (!$this->validateFile($field, $value, $params)) {
            return false;
        }

        $mimeMap = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls' => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'zip' => 'application/zip',
            'txt' => 'text/plain',
            'csv' => 'text/csv'
        ];

        $allowedMimes = array_map(function($ext) use ($mimeMap) {
            return $mimeMap[$ext] ?? $ext;
        }, $params);

        return in_array($_FILES[$field]['type'], $allowedMimes);
    }

    private function validateMax_size($field, $value, $params) {
        if (!isset($_FILES[$field])) {
            return true;
        }

        $maxKb = (int) $params[0];
        $fileSizeKb = $_FILES[$field]['size'] / 1024;

        return $fileSizeKb <= $maxKb;
    }

    // ===================
    // MÉTODOS PÚBLICOS
    // ===================

    /**
     * Verificar se validação falhou
     *
     * @return bool
     */
    public function fails() {
        return !empty($this->errors);
    }

    /**
     * Verificar se validação passou
     *
     * @return bool
     */
    public function passes() {
        return empty($this->errors);
    }

    /**
     * Obter todos os erros
     *
     * @return array
     */
    public function errors() {
        return $this->errors;
    }

    /**
     * Obter primeiro erro de um campo
     *
     * @param string $field
     * @return string|null
     */
    public function first($field) {
        return $this->errors[$field][0] ?? null;
    }

    /**
     * Obter todos os erros como array flat
     *
     * @return array
     */
    public function all() {
        $all = [];
        foreach ($this->errors as $field => $errors) {
            foreach ($errors as $error) {
                $all[] = $error;
            }
        }
        return $all;
    }

    /**
     * Obter dados validados
     *
     * @return array
     */
    public function validated() {
        return $this->validated;
    }

    /**
     * Obter valor validado específico
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get($key, $default = null) {
        return $this->validated[$key] ?? $default;
    }

    /**
     * Obter apenas campos específicos dos validados
     *
     * @param array $keys
     * @return array
     */
    public function only($keys) {
        return array_intersect_key($this->validated, array_flip($keys));
    }

    /**
     * Obter validados exceto campos específicos
     *
     * @param array $keys
     * @return array
     */
    public function except($keys) {
        return array_diff_key($this->validated, array_flip($keys));
    }
}
