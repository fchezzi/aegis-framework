<?php
/**
 * AEGIS Framework - Component System
 *
 * Sistema de componentes reutilizáveis para Page Builder
 * Suporta validação via metadata (component.json) e rendering customizado
 *
 * @package AEGIS
 * @version 9.1.0
 * @since 9.1.0
 */

class Component {
    /**
     * Cache de metadata dos componentes
     */
    private static array $metadataCache = [];

    /**
     * Diretório base dos componentes
     */
    private static string $componentsPath = __DIR__ . '/../components';

    /**
     * Renderizar um componente
     *
     * @param string $type Tipo do componente (hero, tabelas, etc)
     * @param array $data Dados de configuração do componente
     * @return string HTML renderizado
     * @throws Exception Se componente não existe ou dados inválidos
     */
    public static function render(string $type, array $data): string {
        // Validar tipo
        $type = self::sanitizeType($type);
        if (!self::exists($type)) {
            throw new Exception("Componente '{$type}' não encontrado.");
        }

        // Validar dados
        if (!self::validate($type, $data)) {
            throw new Exception("Dados inválidos para componente '{$type}'.");
        }

        // Carregar classe do componente
        $componentClass = self::loadComponentClass($type);

        // Renderizar
        return $componentClass::render($data);
    }

    /**
     * Validar dados de configuração contra metadata
     *
     * @param string $type Tipo do componente
     * @param array $data Dados a validar
     * @return bool True se válido, false caso contrário
     */
    public static function validate(string $type, array $data): bool {
        $metadata = self::getMetadata($type);

        if (!isset($metadata['fields'])) {
            return true; // Sem fields definidos = qualquer dado é válido
        }

        foreach ($metadata['fields'] as $fieldName => $fieldConfig) {
            // Verificar se campo deve ser validado (respeitar show_if)
            if (!self::shouldValidateField($fieldConfig, $data)) {
                continue; // Pular validação deste campo
            }

            // Validar campos obrigatórios
            if (($fieldConfig['required'] ?? false) && empty($data[$fieldName])) {
                return false;
            }

            // Validar tipo de campo se presente
            if (!empty($data[$fieldName])) {
                if (!self::validateFieldType($data[$fieldName], $fieldConfig)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Verificar se campo deve ser validado baseado em show_if
     *
     * @param array $fieldConfig Configuração do campo
     * @param array $data Dados atuais
     * @return bool True se deve validar, false se deve pular
     */
    private static function shouldValidateField(array $fieldConfig, array $data): bool {
        // Se não tem show_if, sempre validar
        if (!isset($fieldConfig['show_if'])) {
            return true;
        }

        $showIf = $fieldConfig['show_if'];

        // Verificar cada condição do show_if
        foreach ($showIf as $dependentField => $allowedValues) {
            // Se campo dependente não existe nos dados, não mostrar
            if (!isset($data[$dependentField])) {
                return false;
            }

            $currentValue = $data[$dependentField];

            // Se allowedValues é array, verificar se valor está no array
            if (is_array($allowedValues)) {
                if (!in_array($currentValue, $allowedValues)) {
                    return false;
                }
            } else {
                // Se é string, verificar igualdade exata
                if ($currentValue !== $allowedValues) {
                    return false;
                }
            }
        }

        // Todas as condições passaram, validar campo
        return true;
    }

    /**
     * Validar tipo de campo específico
     */
    private static function validateFieldType($value, array $fieldConfig): bool {
        $type = $fieldConfig['type'] ?? 'text';

        switch ($type) {
            case 'number':
                if (!is_numeric($value)) return false;
                if (isset($fieldConfig['min']) && $value < $fieldConfig['min']) return false;
                if (isset($fieldConfig['max']) && $value > $fieldConfig['max']) return false;
                break;

            case 'url':
                if (!filter_var($value, FILTER_VALIDATE_URL)) return false;
                break;

            case 'email':
                if (!filter_var($value, FILTER_VALIDATE_EMAIL)) return false;
                break;

            case 'text':
            case 'textarea':
                if (isset($fieldConfig['max_length']) && strlen($value) > $fieldConfig['max_length']) {
                    return false;
                }
                break;

            case 'select':
                if (isset($fieldConfig['options']) && !in_array($value, $fieldConfig['options'])) {
                    return false;
                }
                break;
        }

        return true;
    }

    /**
     * Obter metadata de um componente
     *
     * @param string $type Tipo do componente
     * @return array Metadata do component.json
     * @throws Exception Se metadata não encontrado
     */
    public static function getMetadata(string $type): array {
        $type = self::sanitizeType($type);

        // Verificar cache
        if (isset(self::$metadataCache[$type])) {
            return self::$metadataCache[$type];
        }

        // Carregar metadata
        $metadataPath = self::$componentsPath . '/' . $type . '/component.json';

        if (!file_exists($metadataPath)) {
            throw new Exception("Metadata não encontrado para componente '{$type}'.");
        }

        $metadata = json_decode(file_get_contents($metadataPath), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Erro ao decodificar metadata do componente '{$type}': " . json_last_error_msg());
        }

        // Cachear
        self::$metadataCache[$type] = $metadata;

        return $metadata;
    }

    /**
     * Listar todos os componentes disponíveis
     *
     * @return array Array com informações básicas de cada componente
     */
    public static function listAvailable(): array {
        $components = [];

        if (!is_dir(self::$componentsPath)) {
            return [];
        }

        $dirs = scandir(self::$componentsPath);

        foreach ($dirs as $dir) {
            if ($dir === '.' || $dir === '..') continue;

            $componentPath = self::$componentsPath . '/' . $dir;

            if (is_dir($componentPath) && file_exists($componentPath . '/component.json')) {
                try {
                    $metadata = self::getMetadata($dir);
                    $components[] = [
                        'type' => $dir,
                        'title' => $metadata['title'] ?? ucfirst($dir),
                        'description' => $metadata['description'] ?? '',
                        'min_size' => $metadata['min_size'] ?? 1,
                        'max_size' => $metadata['max_size'] ?? 6,
                    ];
                } catch (Exception $e) {
                    // Ignorar componentes com metadata inválido
                    continue;
                }
            }
        }

        return $components;
    }

    /**
     * Verificar se componente existe
     *
     * @param string $type Tipo do componente
     * @return bool True se existe, false caso contrário
     */
    public static function exists(string $type): bool {
        $type = self::sanitizeType($type);
        $componentPath = self::$componentsPath . '/' . $type;

        return is_dir($componentPath)
            && file_exists($componentPath . '/component.json')
            && file_exists($componentPath . '/' . ucfirst($type) . '.php');
    }

    /**
     * Carregar classe PHP do componente
     *
     * @param string $type Tipo do componente
     * @return object Instância da classe
     * @throws Exception Se classe não encontrada
     */
    private static function loadComponentClass(string $type): object {
        $type = self::sanitizeType($type);
        $className = ucfirst($type);
        $classFile = self::$componentsPath . '/' . $type . '/' . $className . '.php';

        if (!file_exists($classFile)) {
            throw new Exception("Arquivo de classe não encontrado para componente '{$type}'.");
        }

        require_once $classFile;

        if (!class_exists($className)) {
            throw new Exception("Classe '{$className}' não encontrada.");
        }

        return new $className();
    }

    /**
     * Sanitizar nome do tipo de componente
     *
     * @param string $type Tipo a sanitizar
     * @return string Tipo sanitizado
     */
    private static function sanitizeType(string $type): string {
        // Permitir apenas letras, números, hífen e underscore
        return preg_replace('/[^a-z0-9_-]/i', '', $type);
    }

    /**
     * Limpar cache de metadata (útil para testes)
     */
    public static function clearCache(): void {
        self::$metadataCache = [];
    }
}
