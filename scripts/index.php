<?php
/**
 * AEGIS Framework - Scripts Protection
 * Scripts devem ser executados via CLI, não via HTTP
 */

http_response_code(403);
header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Acesso Negado</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: #1a1a1a;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
            text-align: center;
        }
        h1 { font-size: 48px; margin-bottom: 20px; color: #ff4444; }
        p { font-size: 18px; opacity: 0.8; margin: 10px 0; }
        code {
            background: #2a2a2a;
            padding: 4px 8px;
            border-radius: 4px;
            color: #4CAF50;
        }
    </style>
</head>
<body>
    <div>
        <h1>⛔ Acesso Negado</h1>
        <p>Scripts administrativos devem ser executados via CLI.</p>
        <p>Exemplo: <code>php scripts/nome-script.php</code></p>
    </div>
</body>
</html>
