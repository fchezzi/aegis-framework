<?php
require_once __DIR__ . '/../_config.php';

// Conectar ao banco
$socket = '/Applications/MAMP/tmp/mysql/mysql.sock';
$pdo = new PDO("mysql:unix_socket=$socket;dbname=aegis", 'root', 'root');

// Pegar último relatório
$stmt = $pdo->query("SELECT id, third_party_summary FROM tbl_pagespeed_reports ORDER BY analyzed_at DESC LIMIT 1");
$report = $stmt->fetch(PDO::FETCH_ASSOC);

echo "ID: " . $report['id'] . "\n\n";

echo "Valor raw do banco:\n";
var_dump($report['third_party_summary']);

echo "\n\nApós json_decode:\n";
$decoded = json_decode($report['third_party_summary'], true);
var_dump($decoded);

echo "\n\nTipo após decode:\n";
echo "É array? " . (is_array($decoded) ? 'SIM' : 'NÃO') . "\n";
echo "Está vazio? " . (empty($decoded) ? 'SIM' : 'NÃO') . "\n";

// Testar decodificação dupla (caso esteja com escape duplo)
echo "\n\nTestando decode duplo:\n";
if (is_string($decoded)) {
    $decoded2 = json_decode($decoded, true);
    var_dump($decoded2);
}