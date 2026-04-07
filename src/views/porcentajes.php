<?php
/**
 * Copyright (c) 2026 CristianVGdev (github.com/CristianVGdev)
 * Proyecto: GitStatsVG (BSD-3-Clause)
 */
url_public('porcent');
if (vista_modo_registro()) {
    return;
}

$lista = json_carga('globalLanguagePercentage', []);
if (!is_array($lista)) {
    $lista = [];
}

$colores = [
    '#E0182D',
    '#E8B84B',
    '#4CAF50',
    '#2196F3',
    '#9C27B0',
    '#00BCD4',
    '#FF5722',
    '#8BC34A',
    '#FF9800',
    '#3F51B5',
];
?>
<!doctype html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Porcentajes</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            max-width: 500px;
            font-family: system-ui, -apple-system, sans-serif;
            padding: 1.25rem;
        }

        .total {
            font-size: 11px;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            color: #fff;
            margin-bottom: 1rem;
        }

        .total b {
            color: #FFFFFFB4;
            font-weight: 500;
        }

        .panel {
            background: #1a1a1a;
            border: 1px solid #333;
            border-radius: 10px;
            overflow: hidden;
        }

        .row {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 14px;
            border-bottom: 0.5px solid #2a2a2a;
        }

        .row:last-child {
            border-bottom: none;
        }

        .dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            flex-shrink: 0;
        }

        .name {
            flex: 1;
            font-size: 13px;
            color: #ccc;
        }

        .bar-wrap {
            width: 90px;
            height: 4px;
            background: #2a2a2a;
            border-radius: 2px;
            flex-shrink: 0;
        }

        .bar {
            height: 4px;
            border-radius: 2px;
        }

        .pct {
            font-size: 13px;
            font-weight: 700;
            color: #fff;
            min-width: 44px;
            text-align: right;
        }

        .bytes {
            font-size: 11px;
            color: #555;
            min-width: 72px;
            text-align: right;
        }

        .copy {
            display: block;
            text-align: center;
            font-size: 11px;
            color: #fff;
            margin-top: 0.75rem;
            letter-spacing: 0.5px;
        }
    </style>
</head>

<body>
    <div class="total">Total bytes: <b><?= number_format((int) json_carga('totalBytes', 0)) ?></b></div>
    <div class="panel">
        <?php foreach ($lista as $i => $item): ?>
            <?php if (!is_array($item)) {
                continue;
            } ?>
            <?php
            $color = $colores[$i % count($colores)];
            $lang  = htmlspecialchars((string) ($item['language'] ?? ''), ENT_QUOTES, 'UTF-8');
            $pct   = (float) ($item['percentage'] ?? 0);
            $bytes = (int) ($item['bytes'] ?? 0);
            ?>
            <div class="row" style="border-left: 2px solid <?= $color ?>">
                <div class="dot" style="background: <?= $color ?>"></div>
                <span class="name"><?= $lang ?></span>
                <div class="bar-wrap">
                    <div class="bar" style="width: <?= min(100, $pct) ?>%; background: <?= $color ?>"></div>
                </div>
                <span class="pct"><?= number_format($pct, 1) ?>%</span>
                <span class="bytes"><?= number_format($bytes) ?> b</span>
            </div>
        <?php endforeach; ?>
    </div>
    <span class="copy">Powered By: CristianVG 🦊</span>
</body>

</html>