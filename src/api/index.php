<?php
/**
 * Copyright (c) 2026 CristianVGdev (github.com/CristianVGdev)
 * Proyecto: GitStatsVG (BSD-3-Clause)
 */
declare(strict_types=1);

require_once __DIR__ . '/configLoader.php';
require_once __DIR__ . '/../router/routerApi.php';
require_once __DIR__ . '/respuestaApi.php';
require_once __DIR__ . '/servicioApi.php';

if (!function_exists('ejecutar_api_segura')) {
    /**
     * Inicializa y ejecuta la API con controles estrictos de seguridad.
     *
     * @return void
     */
    function ejecutar_api_segura(): void {
        aplicar_encabezados_seguridad_json();
        aplicar_politica_cors();

        $ruta = obtener_ruta_api_actual();
        $endpoint = resolver_endpoint_api($ruta);
        $metodo = obtener_metodo_solicitud();

        if ($metodo === 'OPTIONS') {
            if ($endpoint !== null) {
                responder_vacio(204);
            }

            responder_error_json(404, 'Endpoint no disponible.');
        }

        if ($metodo !== 'GET') {
            header('Allow: GET, OPTIONS');
            responder_error_json(405, 'Metodo no permitido.');
        }

        if ($endpoint === null) {
            responder_error_json(404, 'Endpoint no disponible.');
        }

        if (!function_exists('curl_init')) {
            responder_error_json(500, 'La extension cURL de PHP es obligatoria.');
        }

        if (!clave_api_esta_configurada()) {
            responder_error_json(403, 'Acceso denegado. Falta configurar clave de acceso.');
        }

        if (!token_github_esta_configurado()) {
            responder_error_json(403, 'Acceso denegado. Falta token de GitHub.');
        }

        if (!solicitud_esta_autorizada()) {
            responder_error_json(403, 'Acceso denegado. Token requerido.');
        }

        if ($endpoint === 'end-porcent') {
            atender_fin_porcentaje();
            return;
        }

        atender_fin_estadisticas();
    }
}

ejecutar_api_segura();
