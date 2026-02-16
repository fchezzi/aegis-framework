<?php
/**
 * Desinstalação de Módulo - Etapa 1
 * Mostra SQL para executar manualmente no Supabase
 */

Auth::require();

// Validar parâmetros
if (!isset($_GET['module'])) {
    Core::redirect('/admin/modules?error=' . urlencode('Módulo não especificado'));
}

$moduleName = Security::sanitize($_GET['module']);

// Mensagens
$error = $_GET['error'] ?? '';
$success = $_GET['success'] ?? '';

// Verificar se módulo existe e está instalado
$isInstalled = ModuleManager::isInstalled($moduleName);
if (!$isInstalled) {
    Core::redirect('/admin/modules?error=' . urlencode('Módulo não está instalado'));
}

// Buscar info do módulo
$modulePath = ROOT_PATH . 'modules/' . $moduleName;
$moduleConfig = json_decode(file_get_contents($modulePath . '/module.json'), true);

// Buscar SQL de rollback
$rollbackFile = $modulePath . '/database/rollback.sql';
$rollbackSQL = file_exists($rollbackFile) ? file_get_contents($rollbackFile) : null;

// Gerar SQL de limpeza (como fallback)
$cleanupSQL = generateCleanupSQL($moduleName);

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
	<?php require_once __DIR__ . '../../../includes/_admin-head.php'; ?>
	<title>Desinstalar <?= htmlspecialchars($moduleConfig['title']) ?> - AEGIS Admin</title>
</head>
<body>
    <div class="uninstall-container">
        <h1>🗑️ Desinstalar Módulo: <?= htmlspecialchars($moduleConfig['title']) ?></h1>

        <?php if ($error): ?>
            <div class="alert alert-error" style="margin-bottom: 20px;">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success" style="margin-bottom: 20px;">
                ✅ <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>

        <div class="warning-box">
            <h3>⚠️ ATENÇÃO - Processo Manual de Desinstalação</h3>
            <p><strong>Por problemas conhecidos com a desinstalação automática do Supabase</strong>, este processo requer intervenção manual.</p>
            <p>Siga os passos abaixo cuidadosamente para garantir que todas as tabelas sejam removidas corretamente.</p>
        </div>

        <!-- PASSO 1: Copiar SQL -->
        <div class="step-box">
            <div class="step-header">
                <div class="step-number">1</div>
                <h2 class="step-title">Copie o SQL de Limpeza</h2>
            </div>

            <p class="instruction">
                Copie o código SQL abaixo. Ele vai remover todas as tabelas, views e funções do módulo <strong><?= htmlspecialchars($moduleConfig['title']) ?></strong>.
            </p>

            <div class="sql-box">
                <button class="copy-btn" onclick="copySQL()">📋 Copiar</button>
                <pre id="sql-code"><?= htmlspecialchars($rollbackSQL ?: $cleanupSQL) ?></pre>
            </div>
        </div>

        <!-- PASSO 2: Executar no Supabase -->
        <div class="step-box">
            <div class="step-header">
                <div class="step-number">2</div>
                <h2 class="step-title">Execute no Supabase</h2>
            </div>

            <p class="instruction">
                <strong>1.</strong> Acesse o dashboard do Supabase<br>
                <strong>2.</strong> Vá em <strong>SQL Editor</strong><br>
                <strong>3.</strong> Cole o código SQL copiado<br>
                <strong>4.</strong> Clique em <strong>Run</strong><br>
                <strong>5.</strong> Aguarde a confirmação de sucesso
            </p>

            <div class="verification-info">
                <h4>ℹ️ O que será verificado:</h4>
                <p>Quando clicar em "Já executei, verificar agora", o sistema vai:</p>
                <ul>
                    <li>Conectar no Supabase via service_role key</li>
                    <li>Verificar se TODAS as tabelas do módulo foram deletadas</li>
                    <li>Confirmar que views e funções foram removidas</li>
                    <li>Só permitir finalizar se tudo estiver limpo</li>
                </ul>
                <p><strong>Se alguma tabela ainda existir, você será notificado e poderá tentar novamente.</strong></p>
            </div>
        </div>

        <!-- PASSO 3: Verificar e Finalizar -->
        <div class="step-box">
            <div class="step-header">
                <div class="step-number">3</div>
                <h2 class="step-title">Verificar e Finalizar</h2>
            </div>

            <p class="instruction">
                Depois de executar o SQL no Supabase, clique no botão abaixo para verificar se todas as tabelas foram removidas corretamente.
            </p>

            <form method="POST" action="<?= url('/admin/modules/verify-uninstall') ?>" id="verify-form">
                <input type="hidden" name="csrf_token" value="<?= Security::generateCSRF() ?>">
                <input type="hidden" name="module_name" value="<?= htmlspecialchars($moduleName) ?>">

                <div class="actions">
                    <button type="submit" class="btn btn-primary">
                        ✅ Já executei, verificar agora
                    </button>
                    <a href="<?= url('/admin/modules') ?>" class="btn btn-secondary">
                        ← Cancelar
                    </a>
                </div>
            </form>
        </div>
    </div>

    <script>
        function copySQL() {
            const sqlCode = document.getElementById('sql-code').textContent;
            const btn = document.querySelector('.copy-btn');

            navigator.clipboard.writeText(sqlCode).then(() => {
                btn.textContent = '✅ Copiado!';
                btn.classList.add('copied');

                setTimeout(() => {
                    btn.textContent = '📋 Copiar';
                    btn.classList.remove('copied');
                }, 2000);
            }).catch(err => {
                alert('Erro ao copiar. Por favor, copie manualmente.');
                console.error('Erro ao copiar:', err);
            });
        }

        // Prevenir submit múltiplo
        document.getElementById('verify-form').addEventListener('submit', function(e) {
            const btn = this.querySelector('button[type="submit"]');
            btn.disabled = true;
            btn.textContent = '⏳ Verificando...';
        });
    </script>
</body>
</html>

<?php
/**
 * Função auxiliar para gerar SQL de limpeza (fallback)
 */
function generateCleanupSQL($moduleName) {
    // Para o módulo palpites, retornar SQL específico
    if ($moduleName === 'palpites') {
        return <<<SQL
-- ========================================
-- LIMPEZA COMPLETA - MÓDULO PALPITES
-- ORDEM CORRETA: Triggers → Functions → Views → Tables
-- ========================================

-- 1. Remover triggers PRIMEIRO (antes de deletar as tabelas)
DROP TRIGGER IF EXISTS trigger_resultado_atualiza_ranking ON tbl_resultados_reais CASCADE;

-- 2. Remover funções
DROP FUNCTION IF EXISTS swap_ordem_palpiteiros(BIGINT, BIGINT) CASCADE;
DROP FUNCTION IF EXISTS atualizar_cache_ranking() CASCADE;
DROP FUNCTION IF EXISTS trigger_atualizar_ranking() CASCADE;

-- 3. Remover views
DROP VIEW IF EXISTS vw_pontuacao_palpites CASCADE;
DROP VIEW IF EXISTS vw_ranking_palpiteiros CASCADE;

-- 4. Remover tabelas POR ÚLTIMO (CASCADE remove dependências)
DROP TABLE IF EXISTS tbl_resultados_reais CASCADE;
DROP TABLE IF EXISTS tbl_palpites CASCADE;
DROP TABLE IF EXISTS tbl_jogos_palpites CASCADE;
DROP TABLE IF EXISTS tbl_times CASCADE;
DROP TABLE IF EXISTS tbl_palpiteiros CASCADE;

-- 5. Remover cache de ranking
DROP TABLE IF EXISTS cache_ranking_palpiteiros CASCADE;

-- ========================================
-- VERIFICAÇÃO
-- ========================================
SELECT 'Limpeza concluída!' as status;
SQL;
    }

    return "-- Nenhum SQL de limpeza disponível para o módulo '{$moduleName}'";
}
