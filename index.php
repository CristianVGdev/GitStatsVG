<?php
/**
 * Copyright (c) 2026 CristianVGdev (github.com/CristianVGdev)
 * Proyecto: GitStatsVG (BSD-3-Clause)
 */
declare(strict_types=1);

if (!function_exists('endurecer_entorno_ejecucion')) {
    /**
     * Endurece ajustes de runtime para produccion.
     *
     * @return void
     */
    function endurecer_entorno_ejecucion(): void {
        if (function_exists('header_remove')) {
            header_remove('X-Powered-By');
        }
    }
}

endurecer_entorno_ejecucion();

require_once __DIR__ . '/logger.php';
require_once __DIR__ . '/src/api/configLoader.php';
require_once __DIR__ . '/src/api/respuestaApi.php';
require_once __DIR__ . '/src/api/servicioApi.php';
require_once __DIR__ . '/src/router/routerVistas.php';

inicializar_logger_app();

if (!function_exists('obtener_ruta_entrada')) {
    /**
     * Obtiene la ruta solicitada desde el index raiz.
     *
     * @return string
     */
    function obtener_ruta_entrada(): string {
        $resultado = '';

        $uri = filter_input(INPUT_SERVER, 'REQUEST_URI', FILTER_UNSAFE_RAW);
        if (!is_string($uri)) {
            $uri = '';
        }

        $path = parse_url($uri, PHP_URL_PATH);
        if (!is_string($path)) {
            $path = '';
        }

        $script = filter_input(INPUT_SERVER, 'SCRIPT_NAME', FILTER_UNSAFE_RAW);
        if (!is_string($script)) {
            $script = '';
        }

        $directorioBase = str_replace('\\', '/', dirname($script));
        if ($directorioBase !== '' && $directorioBase !== '/' && str_starts_with($path, $directorioBase)) {
            $path = substr($path, strlen($directorioBase));
        }

        $ruta = trim($path, '/');
        if ($ruta !== '' && $ruta !== 'index.php') {
            if (str_starts_with($ruta, 'index.php/')) {
                $ruta = substr($ruta, 10);
            }

            $ruta = strtolower(trim($ruta, '/'));
            if ($ruta !== '' && !str_contains($ruta, '..') && preg_match('/^[\p{L}\p{N}\/-]+$/u', $ruta) === 1) {
                $resultado = $ruta;
            }
        }

        return $resultado;
    }
}

if (!function_exists('resolver_destino_entrada')) {
    /**
     * Genera candidatos removiendo prefijos para entornos bajo subruta.
     *
     * @param string $ruta Ruta normalizada de entrada.
     * @return array<int,string>
     */
    function generar_candidatos_ruta_entrada(string $ruta): array {
        $ruta = normalizar_ruta_publica_vista($ruta);
        if ($ruta === '') {
            return [''];
        }

        $candidatos = [$ruta];
        $segmentos = explode('/', $ruta);
        $total = count($segmentos);

        for ($i = 1; $i < $total; $i++) {
            $candidato = implode('/', array_slice($segmentos, $i));
            $candidato = normalizar_ruta_publica_vista($candidato);
            if ($candidato !== '' && !in_array($candidato, $candidatos, true)) {
                $candidatos[] = $candidato;
            }
        }

        return $candidatos;
    }

    /**
     * Resuelve si la ruta pertenece a API o a vista.
     *
     * @param string $ruta Ruta solicitada.
     * @return array<string,string>|null
     */
    function resolver_destino_entrada(string $ruta): ?array {
        $apiPermitidos = ['api/end-porcent', 'api/end-stats'];

        $solicitudImg = false;
        $rutaVistaOriginal = normalizar_ruta_publica_vista($ruta);
        $rutaVistaNormalizada = '';
        $candidatos = generar_candidatos_ruta_entrada($ruta);

        foreach ($candidatos as $candidato) {
            if (in_array($candidato, $apiPermitidos, true)) {
                return [
                    'tipo' => 'api',
                    'endpoint' => substr($candidato, 4),
                ];
            }

            $rutaVista = normalizar_ruta_publica_vista($candidato);
            $formatoVista = 'html';

            if (str_ends_with($rutaVista, '/img')) {
                $rutaVista = normalizar_ruta_publica_vista(substr($rutaVista, 0, -4));
                $formatoVista = 'svg';
                $solicitudImg = true;
            }

            if ($rutaVista !== '' && resolver_vista_publica($rutaVista) !== null) {
                return [
                    'tipo' => 'vista',
                    'endpoint' => $rutaVista,
                    'formato' => $formatoVista,
                ];
            }

            $rutaVistaNormalizada = $rutaVista;
        }

        if ($solicitudImg) {
            registrar_evento_http_img(404, 'img_route_not_found', [
                'rutaEntrada' => $ruta,
                'rutaVistaOriginal' => $rutaVistaOriginal,
                'rutaVistaNormalizada' => $rutaVistaNormalizada,
                'motivo' => 'No se encontro una vista registrada para sufijo /img.',
            ]);
        }

        return null;
    }
}

if (!function_exists('obtener_metodo_http')) {
    /**
     * Devuelve el metodo HTTP actual.
     *
     * @return string
     */
    function obtener_metodo_http(): string {
        $metodo = filter_input(INPUT_SERVER, 'REQUEST_METHOD', FILTER_UNSAFE_RAW);
        if (!is_string($metodo) || $metodo === '') {
            return 'GET';
        }

        return strtoupper(trim($metodo));
    }
}

if (!function_exists('ejecutar_endpoint_api')) {
    /**
     * Ejecuta endpoint API protegido con token de cliente.
     *
     * @param string $endpoint Nombre del endpoint.
     * @return void
     */
    function ejecutar_endpoint_api(string $endpoint): void {
        aplicar_encabezados_seguridad_json();
        aplicar_politica_cors();

        $metodo = obtener_metodo_http();
        if ($metodo === 'OPTIONS') {
            responder_vacio(204);
        }

        if ($metodo !== 'GET') {
            header('Allow: GET, OPTIONS');
            responder_error_json(405, 'Metodo no permitido.');
        }

        if (!function_exists('curl_init')) {
            responder_error_json(500, 'La extension cURL de PHP es obligatoria.');
        }

        $limite = validar_limite_solicitud();
        if (!$limite['allow']) {
            header('Retry-After: ' . (int) $limite['retryAfter']);
            responder_error_json(429, 'Demasiadas solicitudes. Intenta mas tarde.');
        }

        if (!token_github_esta_configurado()) {
            responder_error_json(403, 'Acceso denegado. Falta token de GitHub.');
        }

        if (!clave_api_esta_configurada()) {
            responder_error_json(403, 'Acceso denegado. Falta configurar clave de acceso.');
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

if (!function_exists('ejecutar_endpoint_vista')) {
    /**
     * Valida precondiciones de seguridad para vistas (HTML/SVG).
     *
     * @return void
     */
    function validar_precondiciones_vista(): void {
        $metodo = obtener_metodo_http();
        if ($metodo !== 'GET' && $metodo !== 'HEAD') {
            header('Allow: GET, HEAD');
            responder_error_html(405, 'Metodo no permitido.');
        }

        if (!function_exists('curl_init')) {
            responder_error_html(500, 'La extension cURL de PHP es obligatoria.');
        }

        $limite = validar_limite_solicitud();
        if (!$limite['allow']) {
            header('Retry-After: ' . (int) $limite['retryAfter']);
            responder_error_html(429, 'Demasiadas solicitudes. Intenta mas tarde.');
        }

        if (!token_github_esta_configurado()) {
            responder_error_html(403, 'Acceso denegado. Falta token de GitHub.');
        }
    }

    /**
     * Ejecuta vista grafica protegida solo por token GitHub de servidor.
     *
     * @param string $endpoint Nombre del endpoint.
     * @param string $formato Formato de salida (html|svg).
     * @return void
     */
    function ejecutar_endpoint_vista(string $endpoint, string $formato = 'html'): void {
        if ($formato === 'svg') {
            aplicar_encabezados_seguridad_svg();
        } else {
            aplicar_encabezados_seguridad_html();
        }

        $claveVista = $endpoint;
        validar_precondiciones_vista();

        if (obtener_metodo_http() === 'HEAD') {
            http_response_code(200);
            exit;
        }

        if ($claveVista === 'porcent') {
            $ttlCache = obtener_cache_ttl_segundos();
            $rutaCache = obtener_ruta_archivo_cache('end-porcent.json');
            if ($rutaCache === null) {
                responder_error_html(500, 'No se pudo preparar el cache local.');
            }

            $payload = cargar_respuesta_cache($rutaCache, $ttlCache);
            if (is_array($payload)) {
                renderizar_vista_github($payload, 'porcent', $formato);
            }

            $repositorios = obtener_repositorios_propios();
            if (!$repositorios['ok']) {
                responder_error_html((int) $repositorios['status'], (string) $repositorios['error']);
            }

            $global = recolectar_lenguajes_globales($repositorios['data']);
            $estadisticas = construir_estadisticas_lenguajes($global['languages']);

            $payload = [
                'meta' => construir_meta_respuesta(false, 0, (int) $global['languageRequestErrors']),
                'globalLanguagePercentage' => $estadisticas['usage'],
                'totalBytes' => $estadisticas['totalBytes'],
            ];

            guardar_respuesta_cache($rutaCache, $payload);

            renderizar_vista_github($payload, 'porcent', $formato);
            return;
        }

        if ($claveVista !== 'stats') {
            responder_error_html(404, 'Vista no disponible.');
        }

        $ttlCache = obtener_cache_ttl_segundos();
        $rutaCache = obtener_ruta_archivo_cache('end-stats.json');
        if ($rutaCache === null) {
            responder_error_html(500, 'No se pudo preparar el cache local.');
        }

        $payload = cargar_respuesta_cache($rutaCache, $ttlCache);
        if (is_array($payload)) {
            renderizar_vista_github($payload, 'stats', $formato);
        }

        $repositorios = obtener_repositorios_propios();
        if (!$repositorios['ok']) {
            responder_error_html((int) $repositorios['status'], (string) $repositorios['error']);
        }

        $payload = [
            'meta' => construir_meta_respuesta(false, 0, 0),
            'githubStats' => construir_estadisticas_github($repositorios['data']),
        ];

        guardar_respuesta_cache($rutaCache, $payload);

        renderizar_vista_github($payload, 'stats', $formato);
    }
}

if (!function_exists('ejecutar_front_controller')) {
    /**
     * Ejecuta el front controller unico del proyecto.
     *
     * @return void
     */
    function ejecutar_front_controller(): void {
        $ruta = obtener_ruta_entrada();
        $destino = resolver_destino_entrada($ruta);

        if ($destino === null) {
            aplicar_encabezados_seguridad_json();
            responder_error_json(404, 'Ruta no disponible.');
        }

        if ($destino['tipo'] === 'api') {
            ejecutar_endpoint_api($destino['endpoint']);
            return;
        }

        $formato = isset($destino['formato']) ? (string) $destino['formato'] : 'html';
        ejecutar_endpoint_vista($destino['endpoint'], $formato);
    }
}

ejecutar_front_controller();
