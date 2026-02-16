<?php
/**
 * VersionAnalyzer
 * Analisa mudanças em arquivos e suggest tipo de version bump
 */

class VersionAnalyzer {

    /**
     * Analisar arquivos e sugerir tipo de bump
     *
     * @param array $files Lista de arquivos modificados
     * @param string $context Contexto adicional (commit message, etc)
     * @return array Análise completa com sugestão
     */
    public static function suggest($files = [], $context = '') {
        $analysis = self::analyze($files, $context);

        $current = Version::current();
        $newVersion = self::calculateNewVersion($current, $analysis['type']);

        return [
            'current_version' => $current,
            'suggested_type' => $analysis['type'],
            'new_version' => $newVersion,
            'confidence' => $analysis['confidence'],
            'reasons' => $analysis['reasons'],
            'files' => $analysis['files']
        ];
    }

    /**
     * Analisar arquivos e determinar tipo de mudança
     */
    private static function analyze($files, $context) {
        $classified = self::classifyFiles($files);
        $signals = self::detectSignals($classified, $context);

        return [
            'type' => $signals['type'],
            'confidence' => $signals['confidence'],
            'reasons' => $signals['reasons'],
            'files' => $classified
        ];
    }

    /**
     * Classificar arquivos por status
     */
    private static function classifyFiles($files) {
        $created = [];
        $modified = [];
        $deleted = [];

        foreach ($files as $file) {
            $status = $file['status'] ?? 'modified';
            $path = $file['path'] ?? $file;

            switch ($status) {
                case 'created':
                case 'new':
                    $created[] = $path;
                    break;

                case 'deleted':
                    $deleted[] = $path;
                    break;

                default:
                    $modified[] = $path;
                    break;
            }
        }

        return [
            'created' => $created,
            'modified' => $modified,
            'deleted' => $deleted,
            'total' => count($created) + count($modified) + count($deleted)
        ];
    }

    /**
     * Detectar sinais para determinar tipo de bump
     */
    private static function detectSignals($files, $context) {
        // Analisar arquivos criados
        $coreFiles = array_filter($files['created'], fn($f) => strpos($f, 'core/') !== false);
        $newModules = array_filter($files['created'], fn($f) => strpos($f, 'modules/') !== false);
        $newAdminPages = array_filter($files['created'], fn($f) => strpos($f, 'admin/') !== false);

        // Analisar arquivos modificados
        $configChanges = array_filter($files['modified'], function($f) {
            return strpos($f, '_config.php') !== false || strpos($f, 'config.php') !== false;
        });
        $schemaChanges = array_filter($files['modified'], fn($f) => strpos($f, 'schema.sql') !== false);

        // Palavras-chave de breaking changes
        $breakingKeywords = ['BREAKING', 'breaking change', 'removed', 'deprecated'];
        $hasBreakingContext = false;

        foreach ($breakingKeywords as $keyword) {
            if (stripos($context, $keyword) !== false) {
                $hasBreakingContext = true;
                break;
            }
        }

        // Determinar tipo
        if ($hasBreakingContext || !empty($schemaChanges) || !empty($files['deleted'])) {
            return self::signalMajor($hasBreakingContext, $schemaChanges, $files['deleted']);
        }

        if (!empty($files['created']) || !empty($newModules) || !empty($coreFiles) || !empty($newAdminPages)) {
            return self::signalMinor($coreFiles, $newModules, $newAdminPages, $files['created']);
        }

        return self::signalPatch();
    }

    /**
     * Sinal de MAJOR (breaking changes)
     */
    private static function signalMajor($hasBreaking, $schemaChanges, $deleted) {
        $reasons = [];

        if ($hasBreaking) {
            $reasons[] = 'Contexto indica breaking changes';
        }
        if (!empty($schemaChanges)) {
            $reasons[] = 'Mudanças no schema do banco';
        }
        if (!empty($deleted)) {
            $reasons[] = count($deleted) . ' arquivo(s) deletado(s)';
        }

        return [
            'type' => 'major',
            'confidence' => 'alta',
            'reasons' => $reasons
        ];
    }

    /**
     * Sinal de MINOR (novas funcionalidades)
     */
    private static function signalMinor($coreFiles, $newModules, $newAdminPages, $created) {
        $reasons = [];

        if (!empty($coreFiles)) {
            $reasons[] = count($coreFiles) . ' nova(s) classe(s) core';
        }
        if (!empty($newModules)) {
            $reasons[] = 'Novo(s) módulo(s) adicionado(s)';
        }
        if (!empty($newAdminPages)) {
            $reasons[] = count($newAdminPages) . ' nova(s) página(s) admin';
        }
        if (count($created) > 0) {
            $reasons[] = count($created) . ' arquivo(s) criado(s)';
        }

        return [
            'type' => 'minor',
            'confidence' => 'alta',
            'reasons' => $reasons
        ];
    }

    /**
     * Sinal de PATCH (bug fixes)
     */
    private static function signalPatch() {
        return [
            'type' => 'patch',
            'confidence' => 'média',
            'reasons' => ['Apenas modificações em arquivos existentes']
        ];
    }

    /**
     * Calcular nova versão
     */
    private static function calculateNewVersion($current, $type) {
        list($major, $minor, $patch) = explode('.', $current);

        switch ($type) {
            case 'major':
                return ($major + 1) . '.0.0';

            case 'minor':
                return $major . '.' . ($minor + 1) . '.0';

            case 'patch':
                return $major . '.' . $minor . '.' . ($patch + 1);

            default:
                return $current;
        }
    }
}
