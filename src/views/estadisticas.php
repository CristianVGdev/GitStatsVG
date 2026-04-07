<?php
/**
 * Copyright (c) 2026 CristianVGdev (github.com/CristianVGdev)
 * Proyecto: GitStatsVG (BSD-3-Clause)
 */
url_public('stats');
if (vista_modo_registro()) {
    return;
}

$stats = json_carga('githubStats', []);
if (!is_array($stats)) {
    $stats = [];
}
?>
<!doctype html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Stats</title>
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
            padding: 11px 14px;
            border-bottom: 0.5px solid #2a2a2a;
        }

        .row:last-child {
            border-bottom: none;
        }

        .row.accent {
            border-left: 2px solid #e0182d;
        }

        .row svg {
            width: 18px;
            height: 18px;
            flex-shrink: 0;
        }

        .row .name {
            flex: 1;
            font-size: 13px;
            color: #aaa;
        }

        .row .val {
            font-size: 18px;
            font-weight: 700;
            color: #fff;
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
    <div class="panel">

        <div class="row accent">
            <svg viewBox="0 0 18 18" fill="none">
                <rect x="2" y="3" width="14" height="12" rx="2" stroke="#e0182d" stroke-width="1.5" />
                <path d="M2 7h14" stroke="#e0182d" stroke-width="1" />
            </svg>
            <span class="name">Repositorios</span>
            <span class="val"><?= number_format((int) ($stats['totalRepositories'] ?? 0)) ?></span>
        </div>

        <div class="row">
            <svg viewBox="0 0 18 18" fill="none">
                <rect x="2" y="3" width="14" height="12" rx="2" stroke="#555" stroke-width="1.5" />
                <path d="M2 7h14" stroke="#555" stroke-width="1" />
            </svg>
            <span class="name">Públicos</span>
            <span class="val"><?= number_format((int) ($stats['publicRepositories'] ?? 0)) ?></span>
        </div>

        <div class="row">
            <svg viewBox="0 0 18 18" fill="none">
                <rect x="2" y="3" width="14" height="12" rx="2" stroke="#555" stroke-width="1.5" />
                <path d="M2 7h14" stroke="#555" stroke-width="1" />
                <path d="M9 10v-2m0 4v.5" stroke="#555" stroke-width="1.2" stroke-linecap="round" />
            </svg>
            <span class="name">Privados</span>
            <span class="val"><?= number_format((int) ($stats['privateRepositories'] ?? 0)) ?></span>
        </div>

        <div class="row">
            <svg viewBox="0 0 18 18" fill="none">
                <path d="M3 3h12v9a2 2 0 01-2 2H5a2 2 0 01-2-2V3z" stroke="#555" stroke-width="1.5" />
                <path d="M1 3h16" stroke="#555" stroke-width="1" />
            </svg>
            <span class="name">Archivados</span>
            <span class="val"><?= number_format((int) ($stats['archivedRepositories'] ?? 0)) ?></span>
        </div>

        <div class="row">
            <svg viewBox="0 0 18 18" fill="none">
                <circle cx="5" cy="4" r="2" stroke="#aaa" stroke-width="1.4" />
                <circle cx="13" cy="4" r="2" stroke="#aaa" stroke-width="1.4" />
                <circle cx="9" cy="14" r="2" stroke="#aaa" stroke-width="1.4" />
                <path d="M5,6 C5,10 9,12 9,12 C9,12 13,10 13,6" stroke="#aaa" stroke-width="1.4" fill="none" />
            </svg>
            <span class="name">Forks</span>
            <span class="val"><?= number_format((int) ($stats['forkRepositories'] ?? 0)) ?></span>
        </div>

        <div class="row">
            <svg viewBox="0 0 18 18" fill="none">
                <polygon points="9,2 11,7 17,7 12,11 14,16 9,13 4,16 6,11 1,7 7,7" stroke="#e8b84b" stroke-width="1.5" stroke-linejoin="round" />
            </svg>
            <span class="name">Stars</span>
            <span class="val"><?= number_format((int) ($stats['totalStars'] ?? 0)) ?></span>
        </div>

        <div class="row">
            <svg viewBox="0 0 18 18" fill="none">
                <ellipse cx="9" cy="9" rx="7" ry="4.5" stroke="#aaa" stroke-width="1.4" />
                <circle cx="9" cy="9" r="2" stroke="#aaa" stroke-width="1.4" />
            </svg>
            <span class="name">Watchers</span>
            <span class="val"><?= number_format((int) ($stats['totalWatchers'] ?? 0)) ?></span>
        </div>

        <div class="row">
            <svg viewBox="0 0 18 18" fill="none">
                <circle cx="9" cy="7" r="3.5" stroke="#e0182d" stroke-width="1.4" />
                <path d="M9,10.5 L9,16" stroke="#e0182d" stroke-width="1.4" stroke-linecap="round" />
            </svg>
            <span class="name">Issues abiertas</span>
            <span class="val"><?= number_format((int) ($stats['totalOpenIssues'] ?? 0)) ?></span>
        </div>

    </div>
    <span class="copy">Powered By: CristianVG 🦊</span>
</body>

</html>