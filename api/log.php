<?php
// Script simples para log via JavaScript
$msg = $_GET['msg'] ?? 'sem mensagem';
error_log("[JS LOG] " . $msg);
header('Content-Type: text/plain');
echo "OK";
