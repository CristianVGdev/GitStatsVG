<?php
/**
 * Copyright (c) 2026 CristianVGdev (github.com/CristianVGdev)
 * Proyecto: GitStatsVG (BSD-3-Clause)
 */
declare(strict_types=1);

if (!function_exists('aplicar_encabezados_seguridad_json')) {
    /**
     * Aplica encabezados de seguridad para respuestas JSON.
     *
     * @return void
     */
    function aplicar_encabezados_seguridad_json(): void {
        if (function_exists('header_remove')) {
            header_remove('X-Powered-By');
        }

        header('Content-Type: application/json; charset=utf-8');
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('Referrer-Policy: no-referrer');
        header('Permissions-Policy: geolocation=(), camera=(), microphone=()');
        header('X-Permitted-Cross-Domain-Policies: none');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
    }
}

if (!function_exists('aplicar_politica_cors')) {
    /**
     * Aplica CORS solo para origen permitido.
     *
     * @return void
     */
    function aplicar_politica_cors(): void {
        $origen = trim(GITSTATS_ALLOWED_ORIGIN);
        if ($origen !== '') {
            header('Access-Control-Allow-Origin: ' . $origen);
            header('Vary: Origin');
        }

        header('Access-Control-Allow-Methods: GET, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-API-Key');
    }
}

if (!function_exists('responder_error_json')) {
    /**
     * Devuelve un error JSON estandarizado.
     *
     * @param int $codigo Codigo HTTP.
     * @param string $mensaje Mensaje de error.
     * @return void
     */
    function responder_error_json(int $codigo, string $mensaje): void {
        responder_json($codigo, [
            'error' => $mensaje,
        ]);
    }
}

if (!function_exists('responder_vacio')) {
    /**
     * Devuelve respuesta vacia para preflight.
     *
     * @param int $codigo Codigo HTTP.
     * @return void
     */
    function responder_vacio(int $codigo): void {
        http_response_code($codigo);
        exit;
    }
}

if (!function_exists('responder_json')) {
    /**
     * Emite respuesta JSON y finaliza ejecucion.
     *
     * @param int $codigo Codigo HTTP.
     * @param array<string,mixed> $cuerpo Cuerpo JSON.
     * @return void
     */
    function responder_json(int $codigo, array $cuerpo): void {
        http_response_code($codigo);
        echo json_encode($cuerpo, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}
