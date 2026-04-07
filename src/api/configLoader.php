<?php
/**
 * Copyright (c) 2026 CristianVGdev (github.com/CristianVGdev)
 * Proyecto: GitStatsVG (BSD-3-Clause)
 */
declare(strict_types=1);

if (!function_exists('cargar_configuracion_api')) {
    /**
    * Carga configuracion local obligatoria.
     *
     * @return void
     */
    function cargar_configuracion_api(): void {
        $rutaConfigLocal = __DIR__ . '/config.php';

        if (is_file($rutaConfigLocal)) {
            require_once $rutaConfigLocal;
            return;
        }

        http_response_code(500);
        echo 'Falta configuracion: crea config con base en config.example';
        exit;
    }
}

cargar_configuracion_api();
