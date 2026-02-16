<?php
/**
 * Palpiteiros - Listagem
 */

Auth::require();
$user = Auth::user();

$db = DB::connect();

// Cache: Listagem de palpiteiros (5 minutos)
$palpiteiros = SimpleCache::remember('palpites_palpiteiros_list', function() use ($db) {
    return $db->select('tbl_palpiteiros', [], ['order' => 'ordem ASC']);
}, 300);

$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Palpiteiros - <?= ADMIN_NAME ?></title>
    <link rel="stylesheet" type="text/css" href="<?= url('/assets/css/admin.css') ?>">
    <script src="<?= url('/assets/js/admin.js') ?>"></script>
    <style>
        .foto-thumb {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 50%;
            border: 3px solid #e9ecef;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .btn-icon {
            background: none;
            border: none;
            cursor: pointer;
            padding: 8px;
            font-size: 18px;
            transition: transform 0.2s ease;
            opacity: 0.6;
        }
        .btn-icon:hover {
            transform: scale(1.2);
            opacity: 1;
        }
        .btn-icon:disabled {
            opacity: 0.3;
            cursor: not-allowed;
        }
    </style>
</head>
<body class="m-pagebasebody">

    <?php require_once __DIR__ . '/../../../../../admin/includes/header.php'; ?>

    <main class="m-pagebase">

        <div class="m-pagebase__header">
            <h1>Palpiteiros</h1>
            <div style="display: flex; gap: 10px;">
                <a href="<?= url('/admin/palpites') ?>" class="m-pagebase__btn m-pagebase__btn--widthauto m-pagebase__btn--secondary">
                    <i data-lucide="arrow-left"></i> Voltar
                </a>
                <a href="<?= url('/admin/palpites/palpiteiros/create') ?>" class="m-pagebase__btn m-pagebase__btn--widthauto">
                    <i data-lucide="plus"></i> Novo Palpiteiro
                </a>
            </div>
        </div>

        <?php if ($success): ?>
            <div class="alert alert--success">
                <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert--error">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <table class="m-pagebase__table">
            <thead>
                <tr>
                    <th style="text-align: center; width: 80px;">Ordem</th>
                    <th>Nome</th>
                    <th style="width: 100px;">Foto</th>
                    <th style="width: 100px;">Status</th>
                    <th style="text-align: center; width: 100px;">Mover</th>
                    <th style="text-align: center; width: 200px;">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $total = count($palpiteiros);
                foreach ($palpiteiros as $index => $p):
                ?>
                <tr>
                    <td style="text-align: center;">
                        <span class="m-pagebase__badge m-pagebase__badge--core">
                            <?= $p['ordem'] ?>
                        </span>
                    </td>
                    <td style="font-weight: 600;"><?= htmlspecialchars($p['nome']) ?></td>
                    <td style="text-align: center;">
                        <?php if (!empty($p['foto_url'])): ?>
                            <img src="<?= Upload::url($p['foto_url']) ?>" alt="Foto" class="foto-thumb">
                        <?php else: ?>
                            <div style="width: 50px; height: 50px; border-radius: 50%; background: #e9ecef; display: flex; align-items: center; justify-content: center; font-size: 20px; margin: 0 auto;">
                                <i data-lucide="user"></i>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($p['ativo']): ?>
                            <span class="m-pagebase__badge m-pagebase__badge--success">Ativo</span>
                        <?php else: ?>
                            <span class="m-pagebase__badge m-pagebase__badge--inactive">Inativo</span>
                        <?php endif; ?>
                    </td>
                    <td style="text-align: center;">
                        <div style="display: flex; gap: 4px; justify-content: center; align-items: center;">
                            <?php if ($index > 0): ?>
                                <form method="POST" action="<?= url('/admin/palpites/palpiteiros/move-up') ?>" style="display: inline;">
                                    <input type="hidden" name="csrf_token" value="<?= Security::generateCSRF() ?>">
                                    <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                    <button type="submit" class="btn-icon" title="Mover para cima">▲</button>
                                </form>
                            <?php else: ?>
                                <button class="btn-icon" disabled title="Já está no topo">▲</button>
                            <?php endif; ?>

                            <?php if ($index < $total - 1): ?>
                                <form method="POST" action="<?= url('/admin/palpites/palpiteiros/move-down') ?>" style="display: inline;">
                                    <input type="hidden" name="csrf_token" value="<?= Security::generateCSRF() ?>">
                                    <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                    <button type="submit" class="btn-icon" title="Mover para baixo">▼</button>
                                </form>
                            <?php else: ?>
                                <button class="btn-icon" disabled title="Já está no final">▼</button>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td style="text-align: center;">
                        <div class="m-pagebase__actions">
                            <a href="<?= url('/admin/palpites/palpiteiros/' . $p['id'] . '/edit') ?>" class="m-pagebase__btn m-pagebase__btn--sm m-pagebase__btn--edit m-pagebase__btn--widthauto">
                                <i data-lucide="pencil"></i> Editar
                            </a>
                            <form method="POST" action="<?= url('/admin/palpites/palpiteiros/' . $p['id'] . '/delete') ?>" style="display: inline;" onsubmit="return confirm('Tem certeza que deseja deletar este palpiteiro?')">
                                <input type="hidden" name="csrf_token" value="<?= Security::generateCSRF() ?>">
                                <button type="submit" class="m-pagebase__btn m-pagebase__btn--sm m-pagebase__btn--danger m-pagebase__btn--widthauto">
                                    <i data-lucide="trash-2"></i> Deletar
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

    </main>

    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        lucide.createIcons();
    </script>
</body>
</html>
