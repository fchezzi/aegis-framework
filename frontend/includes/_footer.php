<?php
/**
 * Include: footer
 * @critical: true
 */
?>
    <footer style="background: #2c3e50; color: white; text-align: center; padding: 30px 0; margin-top: 60px;">
        <div class="container">
            <p style="margin: 0;">© <?= date('Y') ?> AEGIS Framework - Criado com Claude Code</p>
            <p style="margin: 10px 0 0 0; font-size: 14px; opacity: 0.7;">
                Framework PHP minimalista com admin inteligente
            </p>
        </div>
    </footer>

    <!-- DEBUG SIMPLES -->
    <script src="<?= url('/assets/js/debug-simples.js') ?>"></script>

    <!-- Fix para filtros (preservar data ao trocar canal) -->
    <script src="<?= url('/assets/js/filtros-fix.js') ?>"></script>

    <!-- Auto-aplicação de filtros padrão (Todos + Últimos 30 dias) -->
    <script src="<?= url('/assets/js/filtros-autoload.js') ?>"></script>
</body>
</html>
