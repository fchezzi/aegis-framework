<?php
/**
 * Componente: Última Atualização
 * Exibe a data/hora da última atualização de uma tabela
 */

// Configurações do card
$table = $config['table'] ?? '';
$dateField = $config['date_field'] ?? '';
$prefixText = $config['prefix_text'] ?? 'Última atualização realizada em:';
$dateFormat = $config['date_format'] ?? 'datetime';
$style = $config['style'] ?? 'default';
$icon = $config['icon'] ?? 'clock';
$showEmpty = $config['show_empty'] ?? 'no';
$emptyText = $config['empty_text'] ?? 'Nenhum dado disponível';

// Validar configurações
if (empty($table) || empty($dateField)) {
    if ($showEmpty === 'yes') {
        echo '<p class="ultima-atualizacao ultima-atualizacao--empty">' . htmlspecialchars($emptyText) . '</p>';
    }
    return;
}

// Buscar última data da tabela
$db = DB::connect();
$latestDate = null;
$latestTimestamp = 0;

try {
    // Sanitizar nomes (mesma forma que MySQLAdapter faz internamente)
    $tableSafe = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
    $fieldSafe = preg_replace('/[^a-zA-Z0-9_]/', '', $dateField);

    // Buscar registro mais recente
    $query = "SELECT MAX({$fieldSafe}) as latest FROM {$tableSafe}";
    $result = $db->query($query);

    if ($result && !empty($result[0]['latest'])) {
        $latestDate = $result[0]['latest'];
        $latestTimestamp = strtotime($latestDate);
    }
} catch (Exception $e) {
    // Erro ao buscar dados
    if ($showEmpty === 'yes') {
        echo '<p class="ultima-atualizacao ultima-atualizacao--error">Erro ao buscar dados</p>';
    }
    return;
}

// Se não encontrou nenhuma data
if (!$latestDate) {
    if ($showEmpty === 'yes') {
        echo '<p class="ultima-atualizacao ultima-atualizacao--empty">' . htmlspecialchars($emptyText) . '</p>';
    }
    return;
}

// Formatar data
$formattedDate = '';
switch ($dateFormat) {
    case 'datetime':
        $formattedDate = date('d/m/Y \à\s H:i', $latestTimestamp);
        break;
    case 'date':
        $formattedDate = date('d/m/Y', $latestTimestamp);
        break;
    case 'relative':
        $diff = time() - $latestTimestamp;
        if ($diff < 60) {
            $formattedDate = 'há poucos segundos';
        } elseif ($diff < 3600) {
            $minutes = floor($diff / 60);
            $formattedDate = "há $minutes " . ($minutes == 1 ? 'minuto' : 'minutos');
        } elseif ($diff < 86400) {
            $hours = floor($diff / 3600);
            $formattedDate = "há $hours " . ($hours == 1 ? 'hora' : 'horas');
        } elseif ($diff < 604800) {
            $days = floor($diff / 86400);
            $formattedDate = "há $days " . ($days == 1 ? 'dia' : 'dias');
        } else {
            $formattedDate = date('d/m/Y', $latestTimestamp);
        }
        break;
}

// Renderizar baseado no estilo
$iconHtml = '';
if (!empty($icon)) {
    $iconHtml = '<i data-lucide="' . htmlspecialchars($icon) . '"></i> ';
}

$styleClass = 'ultima-atualizacao ultima-atualizacao--' . $style;
?>

<?php if ($style === 'default'): ?>
    <p class="<?= $styleClass ?>">
        <?= $iconHtml ?>
        <span class="ultima-atualizacao__text"><?= htmlspecialchars($prefixText) ?></span>
        <strong class="ultima-atualizacao__date"><?= htmlspecialchars($formattedDate) ?></strong>
    </p>

<?php elseif ($style === 'badge'): ?>
    <div class="<?= $styleClass ?>">
        <?= $iconHtml ?>
        <span class="ultima-atualizacao__text"><?= htmlspecialchars($prefixText) ?></span>
        <span class="ultima-atualizacao__badge"><?= htmlspecialchars($formattedDate) ?></span>
    </div>

<?php elseif ($style === 'card'): ?>
    <div class="<?= $styleClass ?>">
        <div class="ultima-atualizacao__icon-wrapper">
            <?= $iconHtml ?>
        </div>
        <div class="ultima-atualizacao__content">
            <span class="ultima-atualizacao__text"><?= htmlspecialchars($prefixText) ?></span>
            <strong class="ultima-atualizacao__date"><?= htmlspecialchars($formattedDate) ?></strong>
        </div>
    </div>

<?php elseif ($style === 'inline'): ?>
    <span class="<?= $styleClass ?>">
        <?= $iconHtml ?>
        <?= htmlspecialchars($prefixText) ?>
        <strong><?= htmlspecialchars($formattedDate) ?></strong>
    </span>

<?php endif; ?>
