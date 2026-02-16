<?php
/**
 * Version Manager
 * Gerencia versionamento do AEGIS Framework
 *
 * Single source of truth: .aegis-version
 * Histórico estruturado: storage/versions.json
 * Sincroniza automaticamente: CHANGELOG.md, docs/_state.md
 */

class Version {

    private static $versionFile = ROOT_PATH . '.aegis-version';
    private static $historyFile = STORAGE_PATH . 'versions.json';

    /**
     * Retorna versão atual do framework
     * @return string
     */
    public static function current() {
        if (!file_exists(self::$versionFile)) {
            self::createVersionFile('1.5.0');
        }

        return trim(file_get_contents(self::$versionFile));
    }

    /**
     * Bump version (patch, minor, major)
     * @param string $type - patch|minor|major
     * @param string $description - Descrição das mudanças
     * @param array $changes - Array de mudanças detalhadas
     * @return array
     */
    public static function bump($type, $description = '', $changes = []) {
        $current = self::current();
        list($major, $minor, $patch) = explode('.', $current);

        switch ($type) {
            case 'major':
                $major++;
                $minor = 0;
                $patch = 0;
                break;
            case 'minor':
                $minor++;
                $patch = 0;
                break;
            case 'patch':
                $patch++;
                break;
            default:
                throw new Exception("Tipo inválido: $type. Use: patch, minor ou major");
        }

        $newVersion = "$major.$minor.$patch";

        // Salvar nova versão
        file_put_contents(self::$versionFile, $newVersion);

        // Adicionar ao histórico
        self::addToHistory($current, $newVersion, $type, $description, $changes);

        // Sincronizar CHANGELOG.md
        self::syncChangelog();

        // Sincronizar docs/_state.md
        self::updateStateDoc($newVersion, $description);

        return [
            'success' => true,
            'old_version' => $current,
            'new_version' => $newVersion,
            'type' => $type
        ];
    }

    /**
     * Adiciona entrada ao histórico de versões
     */
    private static function addToHistory($oldVersion, $newVersion, $type, $description, $changes) {
        $history = self::getHistory();

        $entry = [
            'version' => $newVersion,
            'previous_version' => $oldVersion,
            'type' => $type,
            'description' => $description,
            'changes' => $changes,
            'date' => date('Y-m-d'),
            'timestamp' => time()
        ];

        array_unshift($history, $entry); // Adiciona no início

        file_put_contents(
            self::$historyFile,
            json_encode($history, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );
    }

    /**
     * Retorna histórico de versões
     * @return array
     */
    public static function getHistory() {
        if (!file_exists(self::$historyFile)) {
            return [];
        }

        $content = file_get_contents(self::$historyFile);
        return json_decode($content, true) ?: [];
    }

    /**
     * Sincroniza CHANGELOG.md com histórico estruturado
     */
    private static function syncChangelog() {
        $changelogPath = ROOT_PATH . 'CHANGELOG.md';
        $history = self::getHistory();

        if (empty($history)) {
            return;
        }

        // Ler conteúdo existente
        $existingContent = file_exists($changelogPath) ? file_get_contents($changelogPath) : '';

        // Extrair header (tudo antes do primeiro ## [)
        preg_match('/^(.*?)(?=## \[)/s', $existingContent, $matches);
        $header = $matches[1] ?? self::getChangelogHeader();

        // Extrair seção "Unreleased" se existir
        preg_match('/## \[Unreleased\](.*?)(?=## \[|$)/s', $existingContent, $matches);
        $unreleasedSection = $matches[0] ?? '';

        // Gerar novas entradas
        $entries = '';
        foreach ($history as $entry) {
            $entries .= self::formatChangelogEntry($entry);
        }

        // Montar CHANGELOG.md completo
        $newContent = $header;
        if ($unreleasedSection) {
            $newContent .= $unreleasedSection . "\n";
        }
        $newContent .= $entries;

        file_put_contents($changelogPath, $newContent);
    }

    /**
     * Sincroniza docs/_state.md com versão atual
     */
    private static function updateStateDoc($newVersion, $description) {
        $stateDocPath = ROOT_PATH . 'docs/_state.md';

        if (!file_exists($stateDocPath)) {
            return; // Se não existe, não atualiza
        }

        $content = file_get_contents($stateDocPath);

        // Atualizar linha de versão
        $content = preg_replace(
            '/\*\*Versão:\*\* \d+\.\d+\.\d+/',
            "**Versão:** $newVersion",
            $content
        );

        // Atualizar última sessão (data atual)
        $content = preg_replace(
            '/\*\*Última sessão:\*\* \d{4}-\d{2}-\d{2}/',
            "**Última sessão:** " . date('Y-m-d'),
            $content
        );

        // Atualizar última ação
        $content = preg_replace(
            '/\*\*Última ação:\*\* .+/',
            "**Última ação:** $description",
            $content
        );

        file_put_contents($stateDocPath, $content);
    }

    /**
     * Formata entrada do CHANGELOG.md
     */
    private static function formatChangelogEntry($entry) {
        $version = $entry['version'];
        $date = $entry['date'];
        $type = $entry['type'];
        $description = $entry['description'];
        $changes = $entry['changes'] ?? [];

        $typeLabel = [
            'major' => '🚨 Breaking Changes',
            'minor' => '✨ New Features',
            'patch' => '🐛 Bug Fixes'
        ][$type] ?? 'Changes';

        $md = "\n## [$version] - $date\n\n";
        $md .= "### $typeLabel\n\n";

        if ($description) {
            $md .= "$description\n\n";
        }

        if (!empty($changes)) {
            foreach ($changes as $change) {
                $md .= "- $change\n";
            }
            $md .= "\n";
        }

        return $md;
    }

    /**
     * Header padrão do CHANGELOG.md
     */
    private static function getChangelogHeader() {
        return <<<'MD'
# 📋 Changelog - AEGIS Framework

Todas as mudanças notáveis neste projeto serão documentadas neste arquivo.

O formato é baseado em [Keep a Changelog](https://keepachangelog.com/pt-BR/1.0.0/),
e este projeto adere ao [Semantic Versioning](https://semver.org/lang/pt-BR/).

---


MD;
    }

    /**
     * Cria arquivo .aegis-version se não existir
     */
    private static function createVersionFile($version) {
        file_put_contents(self::$versionFile, $version);
    }

    /**
     * Retorna informações completas da versão atual
     * @return array
     */
    public static function info() {
        $current = self::current();
        $history = self::getHistory();
        $latestEntry = $history[0] ?? null;

        list($major, $minor, $patch) = explode('.', $current);

        return [
            'version' => $current,
            'major' => (int)$major,
            'minor' => (int)$minor,
            'patch' => (int)$patch,
            'latest_change' => $latestEntry,
            'total_releases' => count($history)
        ];
    }

    /**
     * Compara duas versões
     * @return int - 1 se v1 > v2, -1 se v1 < v2, 0 se iguais
     */
    public static function compare($v1, $v2) {
        return version_compare($v1, $v2);
    }

    /**
     * Analisa mudanças e sugere tipo de bump
     * @param array $files - Lista de arquivos modificados
     * @param string $context - Contexto adicional (opcional)
     * @return array
     */
    public static function suggestBump($files = [], $context = '') {
        return VersionAnalyzer::suggest($files, $context);
    }

    /**
     * Auto-bump: Detecta mudanças e faz bump automaticamente se necessário
     * @return array|null
     */
    public static function autoBump() {
        // Verificar se já fez bump hoje
        $lastBumpFile = STORAGE_PATH . 'last-bump.txt';
        $today = date('Y-m-d');

        if (file_exists($lastBumpFile)) {
            $lastBump = trim(file_get_contents($lastBumpFile));
            if ($lastBump === $today) {
                // Já fez bump hoje, não fazer novamente
                return null;
            }
        }

        // Detectar mudanças nas últimas 24h
        $changes = self::detectChanges(1440); // 24h

        if (empty($changes)) {
            return null; // Sem mudanças
        }

        // Sugerir bump baseado nas mudanças
        $suggestion = self::suggestBump($changes);

        // Se confiança for alta ou média, fazer bump automaticamente
        if (in_array($suggestion['confidence'], ['alta', 'média'])) {
            $type = $suggestion['suggested_type'];

            // Gerar descrição automática
            $description = "Auto-bump: " . implode(', ', $suggestion['reasons']);

            // Listar arquivos modificados (máximo 10)
            $filesList = array_slice(
                array_map(fn($f) => $f['path'], $changes),
                0,
                10
            );

            // Fazer bump
            $result = self::bump($type, $description, $filesList);

            // Registrar que fez bump hoje
            file_put_contents($lastBumpFile, $today);

            // Log
            error_log("AUTO-BUMP: {$result['old_version']} → {$result['new_version']} ({$type})");

            return [
                'success' => true,
                'action' => 'auto_bumped',
                'result' => $result,
                'suggestion' => $suggestion
            ];
        }

        // Confiança baixa: não fazer bump automático
        return [
            'success' => false,
            'action' => 'suggestion_only',
            'suggestion' => $suggestion,
            'message' => 'Confiança baixa, requer aprovação manual'
        ];
    }

    /**
     * Detecta mudanças comparando timestamps de arquivos
     * @return array
     */
    public static function detectChanges($sinceMinutes = 60) {
        $since = time() - ($sinceMinutes * 60);
        $changes = [];

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(ROOT_PATH),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getMTime() > $since) {
                $path = str_replace(ROOT_PATH, '', $file->getPathname());

                // Ignorar arquivos irrelevantes
                if (preg_match('#^(\.git|node_modules|vendor|storage/cache|storage/logs|Documents)#', $path)) {
                    continue;
                }

                $changes[] = [
                    'path' => $path,
                    'status' => 'modified', // Simplificado (não detecta new vs modified sem git)
                    'modified' => $file->getMTime()
                ];
            }
        }

        return $changes;
    }
}
