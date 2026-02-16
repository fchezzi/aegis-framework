<?php
/**
 * AEGIS - Importar CSV para banco de dados
 */

require_once __DIR__ . '/../../_config.php';
require_once __DIR__ . '/../../core/Autoloader.php';
Autoloader::register();

header('Content-Type: application/json');

try {
    // Ler JSON do body
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (!$data || !isset($data['table']) || !isset($data['rows'])) {
        throw new Exception('Dados inválidos');
    }

    // Validar CSRF token
    if (!isset($data['csrf_token'])) {
        throw new Exception('Token CSRF não fornecido');
    }

    Security::validateCSRF($data['csrf_token']);

    $table = $data['table'];
    $rows = $data['rows'];

    // Validar tabela
    $allowedTables = ['youtube_extra', 'tbl_youtube', 'tbl_website', 'tbl_facebook', 'tbl_instagram', 'tbl_tiktok', 'tbl_x', 'tbl_x_inscritos', 'tbl_app', 'tbl_twitch'];
    if (!in_array($table, $allowedTables)) {
        throw new Exception('Tabela não permitida');
    }

    // Conectar banco
    $db = DB::connect();

    // Contadores
    $imported = 0;
    $errors = 0;
    $errorDetails = [];

    // Importar cada linha
    foreach ($rows as $index => $row) {
        try {
            $lineNumber = $index + 1;

            if ($table === 'youtube_extra') {
                importYoutubeExtra($db, $row);
            } elseif ($table === 'tbl_youtube') {
                importTblYoutube($db, $row);
            } elseif ($table === 'tbl_website') {
                importTblWebsite($db, $row);
            } elseif ($table === 'tbl_facebook') {
                importTblFacebook($db, $row);
            } elseif ($table === 'tbl_instagram') {
                importTblInstagram($db, $row);
            } elseif ($table === 'tbl_tiktok') {
                importTblTiktok($db, $row);
            } elseif ($table === 'tbl_x') {
                importTblX($db, $row);
            } elseif ($table === 'tbl_x_inscritos') {
                importTblXInscritos($db, $row);
            } elseif ($table === 'tbl_app') {
                importTblApp($db, $row);
            } elseif ($table === 'tbl_twitch') {
                importTblTwitch($db, $row);
            }

            $imported++;

        } catch (Exception $e) {
            $errors++;
            $errorDetails[] = [
                'line' => $lineNumber,
                'error' => $e->getMessage(),
                'data' => $row
            ];

            // Limitar log de erros a 50
            if (count($errorDetails) > 50) {
                array_shift($errorDetails);
            }
        }
    }

    // Retornar resultado
    echo json_encode([
        'success' => true,
        'imported' => $imported,
        'errors' => $errors,
        'total' => count($rows),
        'error_details' => $errorDetails
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * Importar linha para youtube_extra
 */
function importYoutubeExtra($db, $row) {
    // Validar campos obrigatórios
    if (empty($row['canal_id'])) {
        throw new Exception('canal_id vazio');
    }

    if (empty($row['data'])) {
        throw new Exception('data vazio');
    }

    // Validar se canal existe
    $canal = $db->select('canais', ['id' => $row['canal_id']]);
    if (empty($canal)) {
        throw new Exception('Canal não encontrado: ' . $row['canal_id']);
    }

    // Converter data para MySQL (aceita múltiplos formatos)
    $dataFormatted = null;
    if (is_numeric($row['data'])) {
        // Serial do Excel
        $days = intval($row['data']);
        $excelEpoch = new DateTime('1899-12-30');
        $excelEpoch->modify("+{$days} days");
        $dataFormatted = $excelEpoch->format('Y-m-d');
    } else {
        // Tentar múltiplos formatos
        $formats = ['Y-m-d', 'd/m/Y', 'd-m-Y'];
        foreach ($formats as $format) {
            $date = DateTime::createFromFormat($format, $row['data']);
            if ($date) {
                $dataFormatted = $date->format('Y-m-d');
                break;
            }
        }
    }

    if (!$dataFormatted) {
        throw new Exception('Data inválida: ' . $row['data']);
    }

    // Preparar dados
    $insertData = [
        'id' => Core::generateUUID(),
        'canal_id' => $row['canal_id'],
        'data' => $dataFormatted,
        'inscritos' => (int)($row['inscritos'] ?? 0),
        'espectadores_unicos' => (int)($row['espectadores_unicos'] ?? 0)
    ];

    // Verificar se já existe (mesmo canal + mesma data)
    $existing = $db->select('youtube_extra', [
        'canal_id' => $insertData['canal_id'],
        'data' => $insertData['data']
    ]);

    if (!empty($existing)) {
        // Atualizar ao invés de inserir
        $db->update('youtube_extra', $insertData, [
            'canal_id' => $insertData['canal_id'],
            'data' => $insertData['data']
        ]);
    } else {
        // Inserir novo
        $db->insert('youtube_extra', $insertData);
    }
}

/**
 * Importar linha para tbl_youtube
 */
function importTblYoutube($db, $row) {
    // Validar campos obrigatórios
    if (empty($row['video_id'])) {
        throw new Exception('video_id vazio');
    }

    // Converter data para MySQL (aceita múltiplos formatos)
    $videoPublished = null;
    if (!empty($row['video_published'])) {
        // Se for número (serial do Excel: dias desde 1900-01-01)
        if (is_numeric($row['video_published'])) {
            $days = intval($row['video_published']);
            $excelEpoch = new DateTime('1899-12-30'); // Excel epoch (ajuste Windows)
            $excelEpoch->modify("+{$days} days");
            $videoPublished = $excelEpoch->format('Y-m-d H:i:s');
        } else {
            // Tentar múltiplos formatos de texto
            $formats = [
                'd/m/Y H:i',      // 10/12/2025 00:00
                'Y-m-d H:i:s',    // 2025-12-10 00:00:00
                'Y-m-d',          // 2025-12-10
                'd/m/Y',          // 10/12/2025
                'd-m-Y H:i',      // 10-12-2025 00:00
                'd-m-Y'           // 10-12-2025
            ];

            foreach ($formats as $format) {
                $date = DateTime::createFromFormat($format, $row['video_published']);
                if ($date && $date->format($format) === $row['video_published']) {
                    $videoPublished = $date->format('Y-m-d H:i:s');
                    break;
                }
            }
        }
    }

    // Preparar dados completos (30 campos)
    $insertData = [
        'id' => $row['id'] ?? Core::generateUUID(),
        'video_id' => $row['video_id'],
        'video_title' => $row['video_title'] ?? '',
        'video_published' => $videoPublished,
        'video_kind' => $row['video_kind'] ?? '',
        'video_show' => $row['video_show'] ?? '',
        'video_duration' => (int)($row['video_duration'] ?? 0),
        'link' => $row['link'] ?? '',
        'video_watchtime' => (int)($row['video_watchtime'] ?? 0),
        'video_views' => (int)($row['video_views'] ?? 0),
        'video_subscriptions' => (int)($row['video_subscriptions'] ?? 0),
        'video_purchase' => (float)($row['video_purchase'] ?? 0),
        'video_impressions' => (int)($row['video_impressions'] ?? 0),
        'video_clickrate' => (float)($row['video_clickrate'] ?? 0),
        'video_returnviewers' => (int)($row['video_returnviewers'] ?? 0),
        'video_casualviewers' => (int)($row['video_casualviewers'] ?? 0),
        'video_uniqueviewers' => (int)($row['video_uniqueviewers'] ?? 0),
        'video_newviewers' => (int)($row['video_newviewers'] ?? 0),
        'simultaneos' => (int)($row['simultaneos'] ?? 0),
        'video_links' => $row['video_links'] ?? '',
        'video_likes' => (int)($row['video_likes'] ?? 0),
        'video_deslikes' => (int)($row['video_deslikes'] ?? 0),
        'video_comments' => (int)($row['video_comments'] ?? 0),
        'video_favorites' => (int)($row['video_favorites'] ?? 0),
        'video_thumbnail' => $row['video_thumbnail'] ?? '',
        'cadastro' => (int)($row['cadastro'] ?? 0),
        'encerrado' => (int)($row['encerrado'] ?? 0),
        'canal_id' => $row['canal_id'] ?? null
    ];

    // Verificar se já existe (por ID ou video_id)
    $existing = $db->select('tbl_youtube', ['id' => $insertData['id']]);

    if (empty($existing)) {
        $existing = $db->select('tbl_youtube', ['video_id' => $insertData['video_id']]);
    }

    if (!empty($existing)) {
        // Atualizar (usar ID existente)
        $insertData['id'] = $existing[0]['id'];
        $db->update('tbl_youtube', $insertData, ['id' => $insertData['id']]);
    } else {
        // Inserir novo
        $db->insert('tbl_youtube', $insertData);
    }
}

/**
 * Importar linha para tbl_website
 */
function importTblWebsite($db, $row) {
    // Validar campos obrigatórios
    if (empty($row['website_id'])) {
        throw new Exception('website_id vazio');
    }

    if (empty($row['data'])) {
        throw new Exception('data vazio');
    }

    // Validar se canal existe
    $canal = $db->select('canais', ['id' => $row['website_id']]);
    if (empty($canal)) {
        throw new Exception('Canal/Website não encontrado: ' . $row['website_id']);
    }

    // Converter data para MySQL (aceita múltiplos formatos)
    $dataFormatted = null;
    if (is_numeric($row['data'])) {
        // Serial do Excel
        $days = intval($row['data']);
        $excelEpoch = new DateTime('1899-12-30');
        $excelEpoch->modify("+{$days} days");
        $dataFormatted = $excelEpoch->format('Y-m-d');
    } else {
        // Tentar múltiplos formatos
        $formats = ['Y-m-d', 'd/m/Y', 'd-m-Y'];
        foreach ($formats as $format) {
            $date = DateTime::createFromFormat($format, $row['data']);
            if ($date) {
                $dataFormatted = $date->format('Y-m-d');
                break;
            }
        }
    }

    if (!$dataFormatted) {
        throw new Exception('Data inválida: ' . $row['data']);
    }

    // Preparar dados
    $insertData = [
        'data' => $dataFormatted,
        'website_id' => $row['website_id'],
        'visitantes' => (int)($row['visitantes'] ?? 0)
    ];

    // Adicionar ID apenas para INSERT (não para UPDATE)
    $insertDataWithId = array_merge(['id' => null], $insertData);

    // Verificar se já existe (mesma data + mesmo website)
    $existing = $db->select('tbl_website', [
        'data' => $insertData['data'],
        'website_id' => $insertData['website_id']
    ]);

    if (!empty($existing)) {
        // Atualizar ao invés de inserir
        $db->update('tbl_website', $insertData, [
            'data' => $insertData['data'],
            'website_id' => $insertData['website_id']
        ]);
    } else {
        // Inserir novo (com ID auto-increment NULL)
        $db->insert('tbl_website', $insertDataWithId);
    }
}

/**
 * Importar linha para tbl_facebook
 */
function importTblFacebook($db, $row) {
    // Validar campos obrigatórios
    if (empty($row['canal_id'])) {
        throw new Exception('canal_id vazio');
    }

    if (empty($row['data'])) {
        throw new Exception('data vazio');
    }

    // Validar se canal existe
    $canal = $db->select('canais', ['id' => $row['canal_id']]);
    if (empty($canal)) {
        throw new Exception('Canal não encontrado: ' . $row['canal_id']);
    }

    // Converter data para MySQL (aceita múltiplos formatos)
    $dataFormatted = null;
    if (is_numeric($row['data'])) {
        // Serial do Excel
        $days = intval($row['data']);
        $excelEpoch = new DateTime('1899-12-30');
        $excelEpoch->modify("+{$days} days");
        $dataFormatted = $excelEpoch->format('Y-m-d');
    } else {
        // Tentar múltiplos formatos
        $formats = ['Y-m-d', 'd/m/Y', 'd-m-Y'];
        foreach ($formats as $format) {
            $date = DateTime::createFromFormat($format, $row['data']);
            if ($date) {
                $dataFormatted = $date->format('Y-m-d');
                break;
            }
        }
    }

    if (!$dataFormatted) {
        throw new Exception('Data inválida: ' . $row['data']);
    }

    // Preparar dados
    $insertData = [
        'canal_id' => $row['canal_id'],
        'data' => $dataFormatted,
        'visualizacoes' => (int)($row['visualizacoes'] ?? 0),
        'ganhos' => (float)($row['ganhos'] ?? 0),
        'interacoes' => (int)($row['interacoes'] ?? 0),
        'seguidores_liquidos' => (int)($row['seguidores_liquidos'] ?? 0),
        'deixaram_seguir' => (int)($row['deixaram_seguir'] ?? 0),
        'total_seguidores' => (int)($row['total_seguidores'] ?? 0),
        'video_view_3s' => (int)($row['video_view_3s'] ?? 0),
        'video_view_1min' => (int)($row['video_view_1min'] ?? 0),
        'reacoes' => (int)($row['reacoes'] ?? 0),
        'comentarios' => (int)($row['comentarios'] ?? 0),
        'compartilhamentos' => (int)($row['compartilhamentos'] ?? 0)
    ];

    // Adicionar ID apenas para INSERT
    $insertDataWithId = array_merge(['id' => null], $insertData);

    // Verificar se já existe (mesmo canal + mesma data)
    $existing = $db->select('tbl_facebook', [
        'canal_id' => $insertData['canal_id'],
        'data' => $insertData['data']
    ]);

    if (!empty($existing)) {
        // Atualizar ao invés de inserir
        $db->update('tbl_facebook', $insertData, [
            'canal_id' => $insertData['canal_id'],
            'data' => $insertData['data']
        ]);
    } else {
        // Inserir novo (com ID auto-increment NULL)
        $db->insert('tbl_facebook', $insertDataWithId);
    }
}

/**
 * Importar linha para tbl_instagram
 */
function importTblInstagram($db, $row) {
    // Validar campos obrigatórios
    if (empty($row['canal_id'])) {
        throw new Exception('canal_id vazio');
    }

    if (empty($row['data'])) {
        throw new Exception('data vazio');
    }

    // Validar se canal existe
    $canal = $db->select('canais', ['id' => $row['canal_id']]);
    if (empty($canal)) {
        throw new Exception('Canal não encontrado: ' . $row['canal_id']);
    }

    // Converter data para MySQL (aceita múltiplos formatos)
    $dataFormatted = null;
    if (is_numeric($row['data'])) {
        // Serial do Excel
        $days = intval($row['data']);
        $excelEpoch = new DateTime('1899-12-30');
        $excelEpoch->modify("+{$days} days");
        $dataFormatted = $excelEpoch->format('Y-m-d');
    } else {
        // Tentar múltiplos formatos
        $formats = ['Y-m-d', 'd/m/Y', 'd-m-Y'];
        foreach ($formats as $format) {
            $date = DateTime::createFromFormat($format, $row['data']);
            if ($date) {
                $dataFormatted = $date->format('Y-m-d');
                break;
            }
        }
    }

    if (!$dataFormatted) {
        throw new Exception('Data inválida: ' . $row['data']);
    }

    // Preparar dados (25 campos)
    $insertData = [
        'canal_id' => $row['canal_id'],
        'data' => $dataFormatted,
        'visualizacoes_total' => (int)($row['visualizacoes_total'] ?? 0),
        'visualizacoes_seguidores' => (float)($row['visualizacoes_seguidores'] ?? 0),
        'visualizacoes_naoseguidores' => (float)($row['visualizacoes_naoseguidores'] ?? 0),
        'contas_alcancadas' => (int)($row['contas_alcancadas'] ?? 0),
        'tipo_post' => (int)($row['tipo_post'] ?? 0),
        'tipo_reels' => (int)($row['tipo_reels'] ?? 0),
        'tipo_stories' => (int)($row['tipo_stories'] ?? 0),
        'tipo_live' => (int)($row['tipo_live'] ?? 0),
        'tipo_videos' => (int)($row['tipo_videos'] ?? 0),
        'visitas_ao_perfil' => (int)($row['visitas_ao_perfil'] ?? 0),
        'toques_links_externos' => (int)($row['toques_links_externos'] ?? 0),
        'interacoes_total' => (int)($row['interacoes_total'] ?? 0),
        'interacoes_curtir' => (int)($row['interacoes_curtir'] ?? 0),
        'interacoes_comentarios' => (int)($row['interacoes_comentarios'] ?? 0),
        'interacoes_salvar' => (int)($row['interacoes_salvar'] ?? 0),
        'interacoes_compartilhar' => (int)($row['interacoes_compartilhar'] ?? 0),
        'interacoes_repost' => (int)($row['interacoes_repost'] ?? 0),
        'contas_com_engajamento' => (int)($row['contas_com_engajamento'] ?? 0),
        'seguidores_total' => (int)($row['seguidores_total'] ?? 0),
        'seguidores_novos' => (int)($row['seguidores_novos'] ?? 0),
        'seguidores_deixaram' => (int)($row['seguidores_deixaram'] ?? 0),
        'conteudo_compartilhado' => (int)($row['conteudo_compartilhado'] ?? 0)
    ];

    // Adicionar ID apenas para INSERT
    $insertDataWithId = array_merge(['id' => null], $insertData);

    // Verificar se já existe (mesmo canal + mesma data)
    $existing = $db->select('tbl_instagram', [
        'canal_id' => $insertData['canal_id'],
        'data' => $insertData['data']
    ]);

    if (!empty($existing)) {
        // Atualizar ao invés de inserir
        $db->update('tbl_instagram', $insertData, [
            'canal_id' => $insertData['canal_id'],
            'data' => $insertData['data']
        ]);
    } else {
        // Inserir novo (com ID auto-increment NULL)
        $db->insert('tbl_instagram', $insertDataWithId);
    }
}

/**
 * Importar linha para tbl_tiktok
 */
function importTblTiktok($db, $row) {
    // Validar campos obrigatórios
    if (empty($row['canal_id'])) {
        throw new Exception('canal_id vazio');
    }

    if (empty($row['data'])) {
        throw new Exception('data vazio');
    }

    // Validar se canal existe
    $canal = $db->select('canais', ['id' => $row['canal_id']]);
    if (empty($canal)) {
        throw new Exception('Canal não encontrado: ' . $row['canal_id']);
    }

    // Converter data para MySQL (aceita múltiplos formatos)
    $dataFormatted = null;
    if (is_numeric($row['data'])) {
        // Serial do Excel
        $days = intval($row['data']);
        $excelEpoch = new DateTime('1899-12-30');
        $excelEpoch->modify("+{$days} days");
        $dataFormatted = $excelEpoch->format('Y-m-d');
    } else {
        // Tentar múltiplos formatos
        $formats = ['Y-m-d', 'd/m/Y', 'd-m-Y'];
        foreach ($formats as $format) {
            $date = DateTime::createFromFormat($format, $row['data']);
            if ($date) {
                $dataFormatted = $date->format('Y-m-d');
                break;
            }
        }
    }

    if (!$dataFormatted) {
        throw new Exception('Data inválida: ' . $row['data']);
    }

    // Preparar dados (8 campos)
    $insertData = [
        'canal_id' => $row['canal_id'],
        'data' => $dataFormatted,
        'visualizacoes_publicacoes' => (int)($row['visualizacoes_publicacoes'] ?? 0),
        'visualizacoes_perfil' => (int)($row['visualizacoes_perfil'] ?? 0),
        'curtidas' => (int)($row['curtidas'] ?? 0),
        'comentarios' => (int)($row['comentarios'] ?? 0),
        'compartilhamentos' => (int)($row['compartilhamentos'] ?? 0),
        'receita' => (float)($row['receita'] ?? 0),
        'seguidores' => (int)($row['seguidores'] ?? 0)
    ];

    // Adicionar ID apenas para INSERT
    $insertDataWithId = array_merge(['id' => null], $insertData);

    // Verificar se já existe (mesmo canal + mesma data)
    $existing = $db->select('tbl_tiktok', [
        'canal_id' => $insertData['canal_id'],
        'data' => $insertData['data']
    ]);

    if (!empty($existing)) {
        // Atualizar ao invés de inserir
        $db->update('tbl_tiktok', $insertData, [
            'canal_id' => $insertData['canal_id'],
            'data' => $insertData['data']
        ]);
    } else {
        // Inserir novo (com ID auto-increment NULL)
        $db->insert('tbl_tiktok', $insertDataWithId);
    }
}

/**
 * Importar linha para tbl_x
 */
function importTblX($db, $row) {
    // Validar campos obrigatórios
    if (empty($row['canal_id'])) {
        throw new Exception('canal_id vazio');
    }

    if (empty($row['data'])) {
        throw new Exception('data vazio');
    }

    // Validar se canal existe
    $canal = $db->select('canais', ['id' => $row['canal_id']]);
    if (empty($canal)) {
        throw new Exception('Canal não encontrado: ' . $row['canal_id']);
    }

    // Converter data para MySQL (aceita múltiplos formatos)
    $dataFormatted = null;
    if (is_numeric($row['data'])) {
        // Serial do Excel
        $days = intval($row['data']);
        $excelEpoch = new DateTime('1899-12-30');
        $excelEpoch->modify("+{$days} days");
        $dataFormatted = $excelEpoch->format('Y-m-d');
    } else {
        // Tentar múltiplos formatos
        $formats = ['Y-m-d', 'd/m/Y', 'd-m-Y'];
        foreach ($formats as $format) {
            $date = DateTime::createFromFormat($format, $row['data']);
            if ($date) {
                $dataFormatted = $date->format('Y-m-d');
                break;
            }
        }
    }

    if (!$dataFormatted) {
        throw new Exception('Data inválida: ' . $row['data']);
    }

    // Preparar dados (11 campos)
    $insertData = [
        'canal_id' => $row['canal_id'],
        'data' => $dataFormatted,
        'impressoes' => (int)($row['impressoes'] ?? 0),
        'engajamento' => (int)($row['engajamento'] ?? 0),
        'curtidas' => (int)($row['curtidas'] ?? 0),
        'itens_salvos' => (int)($row['itens_salvos'] ?? 0),
        'compartilhamentos' => (int)($row['compartilhamentos'] ?? 0),
        'novos_seguidores' => (int)($row['novos_seguidores'] ?? 0),
        'deixaram_seguir' => (int)($row['deixaram_seguir'] ?? 0),
        'respostas' => (int)($row['respostas'] ?? 0),
        'reposts' => (int)($row['reposts'] ?? 0),
        'visitas_perfil' => (int)($row['visitas_perfil'] ?? 0),
        'posts_criados' => (int)($row['posts_criados'] ?? 0)
    ];

    // Adicionar ID apenas para INSERT
    $insertDataWithId = array_merge(['id' => null], $insertData);

    // Verificar se já existe (mesmo canal + mesma data)
    $existing = $db->select('tbl_x', [
        'canal_id' => $insertData['canal_id'],
        'data' => $insertData['data']
    ]);

    if (!empty($existing)) {
        // Atualizar ao invés de inserir
        $db->update('tbl_x', $insertData, [
            'canal_id' => $insertData['canal_id'],
            'data' => $insertData['data']
        ]);
    } else {
        // Inserir novo (com ID auto-increment NULL)
        $db->insert('tbl_x', $insertDataWithId);
    }
}

/**
 * Importar linha para tbl_x_inscritos
 */
function importTblXInscritos($db, $row) {
    // Validar campos obrigatórios
    if (empty($row['canal_id'])) {
        throw new Exception('canal_id vazio');
    }

    if (empty($row['data'])) {
        throw new Exception('data vazio');
    }

    if (!isset($row['inscritos'])) {
        throw new Exception('inscritos vazio');
    }

    // Validar se canal existe
    $canal = $db->select('canais', ['id' => $row['canal_id']]);
    if (empty($canal)) {
        throw new Exception('Canal não encontrado: ' . $row['canal_id']);
    }

    // Converter data para MySQL (aceita múltiplos formatos)
    $dataFormatted = null;
    if (is_numeric($row['data'])) {
        // Serial do Excel
        $days = intval($row['data']);
        $excelEpoch = new DateTime('1899-12-30');
        $excelEpoch->modify("+{$days} days");
        $dataFormatted = $excelEpoch->format('Y-m-d');
    } else {
        // Tentar múltiplos formatos
        $formats = ['Y-m-d', 'd/m/Y', 'd-m-Y'];
        foreach ($formats as $format) {
            $date = DateTime::createFromFormat($format, $row['data']);
            if ($date) {
                $dataFormatted = $date->format('Y-m-d');
                break;
            }
        }
    }

    if (!$dataFormatted) {
        throw new Exception('Data inválida: ' . $row['data']);
    }

    // Preparar dados
    $insertData = [
        'canal_id' => $row['canal_id'],
        'data' => $dataFormatted,
        'inscritos' => (int)($row['inscritos'] ?? 0)
    ];

    // Adicionar ID apenas para INSERT
    $insertDataWithId = array_merge(['id' => null], $insertData);

    // Verificar se já existe (mesmo canal + mesma data)
    $existing = $db->select('tbl_x_inscritos', [
        'canal_id' => $insertData['canal_id'],
        'data' => $insertData['data']
    ]);

    if (!empty($existing)) {
        // Atualizar ao invés de inserir
        $db->update('tbl_x_inscritos', $insertData, [
            'canal_id' => $insertData['canal_id'],
            'data' => $insertData['data']
        ]);
    } else {
        // Inserir novo (com ID auto-increment NULL)
        $db->insert('tbl_x_inscritos', $insertDataWithId);
    }
}

/**
 * Importar linha para tbl_app
 */
function importTblApp($db, $row) {
    // Validar campos obrigatórios
    if (empty($row['canal_id'])) {
        throw new Exception('canal_id vazio');
    }

    if (empty($row['data'])) {
        throw new Exception('data vazio');
    }

    // Validar se canal existe
    $canal = $db->select('canais', ['id' => $row['canal_id']]);
    if (empty($canal)) {
        throw new Exception('Canal não encontrado: ' . $row['canal_id']);
    }

    // Converter data para MySQL (aceita múltiplos formatos)
    $dataFormatted = null;
    if (is_numeric($row['data'])) {
        // Serial do Excel
        $days = intval($row['data']);
        $excelEpoch = new DateTime('1899-12-30');
        $excelEpoch->modify("+{$days} days");
        $dataFormatted = $excelEpoch->format('Y-m-d');
    } else {
        // Tentar múltiplos formatos
        $formats = ['Y-m-d', 'd/m/Y', 'd-m-Y'];
        foreach ($formats as $format) {
            $date = DateTime::createFromFormat($format, $row['data']);
            if ($date) {
                $dataFormatted = $date->format('Y-m-d');
                break;
            }
        }
    }

    if (!$dataFormatted) {
        throw new Exception('Data inválida: ' . $row['data']);
    }

    // Preparar dados (4 campos)
    $insertData = [
        'canal_id' => $row['canal_id'],
        'data' => $dataFormatted,
        'total_instalacoes' => (int)($row['total_instalacoes'] ?? 0),
        'usuarios_mes' => (int)($row['usuarios_mes'] ?? 0),
        'visualizacoes' => (int)($row['visualizacoes'] ?? 0),
        'visualizacoes_aovivo' => (int)($row['visualizacoes_aovivo'] ?? 0)
    ];

    // Adicionar ID apenas para INSERT
    $insertDataWithId = array_merge(['id' => null], $insertData);

    // Verificar se já existe (mesmo canal + mesma data)
    $existing = $db->select('tbl_app', [
        'canal_id' => $insertData['canal_id'],
        'data' => $insertData['data']
    ]);

    if (!empty($existing)) {
        // Atualizar ao invés de inserir
        $db->update('tbl_app', $insertData, [
            'canal_id' => $insertData['canal_id'],
            'data' => $insertData['data']
        ]);
    } else {
        // Inserir novo (com ID auto-increment NULL)
        $db->insert('tbl_app', $insertDataWithId);
    }
}

/**
 * Importar linha para tbl_twitch
 */
function importTblTwitch($db, $row) {
    // Validar campos obrigatórios
    if (empty($row['canal_id'])) {
        throw new Exception('canal_id vazio');
    }

    if (empty($row['data'])) {
        throw new Exception('data vazio');
    }

    // Validar se canal existe
    $canal = $db->select('canais', ['id' => $row['canal_id']]);
    if (empty($canal)) {
        throw new Exception('Canal não encontrado: ' . $row['canal_id']);
    }

    // Converter data para MySQL (aceita múltiplos formatos)
    $dataFormatted = null;
    if (is_numeric($row['data'])) {
        // Serial do Excel
        $days = intval($row['data']);
        $excelEpoch = new DateTime('1899-12-30');
        $excelEpoch->modify("+{$days} days");
        $dataFormatted = $excelEpoch->format('Y-m-d');
    } else {
        // Tentar múltiplos formatos
        $formats = ['Y-m-d', 'd/m/Y', 'd-m-Y'];
        foreach ($formats as $format) {
            $date = DateTime::createFromFormat($format, $row['data']);
            if ($date) {
                $dataFormatted = $date->format('Y-m-d');
                break;
            }
        }
    }

    if (!$dataFormatted) {
        throw new Exception('Data inválida: ' . $row['data']);
    }

    // Preparar dados (3 campos de métricas)
    $insertData = [
        'canal_id' => $row['canal_id'],
        'data' => $dataFormatted,
        'novos_seguidores' => (int)($row['novos_seguidores'] ?? 0),
        'espectadores_engajados' => (int)($row['espectadores_engajados'] ?? 0),
        'espectadores_unicos' => (int)($row['espectadores_unicos'] ?? 0)
    ];

    // Adicionar ID apenas para INSERT
    $insertDataWithId = array_merge(['id' => null], $insertData);

    // Verificar se já existe (mesmo canal + mesma data)
    $existing = $db->select('tbl_twitch', [
        'canal_id' => $insertData['canal_id'],
        'data' => $insertData['data']
    ]);

    if (!empty($existing)) {
        // Atualizar ao invés de inserir
        $db->update('tbl_twitch', $insertData, [
            'canal_id' => $insertData['canal_id'],
            'data' => $insertData['data']
        ]);
    } else {
        // Inserir novo (com ID auto-increment NULL)
        $db->insert('tbl_twitch', $insertDataWithId);
    }
}
