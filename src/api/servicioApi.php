<?php
/**
 * Copyright (c) 2026 CristianVGdev (github.com/CristianVGdev)
 * Proyecto: GitStatsVG (BSD-3-Clause)
 */
declare(strict_types=1);

if (!function_exists('obtener_valor_servidor')) {
    /**
     * Lee una variable del servidor de forma segura.
     *
     * @param string $nombre Nombre de la variable.
     * @return string
     */
    function obtener_valor_servidor(string $nombre): string {
        $valor = filter_input(INPUT_SERVER, $nombre, FILTER_UNSAFE_RAW);
        if (!is_string($valor)) {
            return '';
        }

        return $valor;
    }
}

if (!function_exists('atender_fin_porcentaje')) {
    /**
     * Atiende /api/end-porcent con porcentaje global de lenguajes.
     *
     * @return void
     */
    function atender_fin_porcentaje(): void {
        $ttlCache = obtener_cache_ttl_segundos();
        $rutaCache = obtener_ruta_archivo_cache('end-porcent.json');
        if ($rutaCache === null) {
            responder_error_json(500, 'No se pudo preparar el cache local.');
        }

        $enCache = cargar_respuesta_cache($rutaCache, $ttlCache);
        if ($enCache !== null) {
            responder_json(200, $enCache);
        }

        $repositorios = obtener_repositorios_propios();
        if (!$repositorios['ok']) {
            responder_error_json((int) $repositorios['status'], (string) $repositorios['error']);
        }

        $global = recolectar_lenguajes_globales($repositorios['data']);
        $estadisticas = construir_estadisticas_lenguajes($global['languages']);

        $respuesta = [
            'meta' => construir_meta_respuesta(false, 0, (int) $global['languageRequestErrors']),
            'globalLanguagePercentage' => $estadisticas['usage'],
            'totalBytes' => $estadisticas['totalBytes'],
        ];

        guardar_respuesta_cache($rutaCache, $respuesta);
        responder_json(200, $respuesta);
    }
}

if (!function_exists('atender_fin_estadisticas')) {
    /**
     * Atiende /api/end-stats con estadisticas globales.
     *
     * @return void
     */
    function atender_fin_estadisticas(): void {
        $ttlCache = obtener_cache_ttl_segundos();
        $rutaCache = obtener_ruta_archivo_cache('end-stats.json');
        if ($rutaCache === null) {
            responder_error_json(500, 'No se pudo preparar el cache local.');
        }

        $enCache = cargar_respuesta_cache($rutaCache, $ttlCache);
        if ($enCache !== null) {
            responder_json(200, $enCache);
        }

        $repositorios = obtener_repositorios_propios();
        if (!$repositorios['ok']) {
            responder_error_json((int) $repositorios['status'], (string) $repositorios['error']);
        }

        $respuesta = [
            'meta' => construir_meta_respuesta(false, 0, 0),
            'githubStats' => construir_estadisticas_github($repositorios['data']),
        ];

        guardar_respuesta_cache($rutaCache, $respuesta);
        responder_json(200, $respuesta);
    }
}

if (!function_exists('obtener_repositorios_propios')) {
    /**
     * Recupera repositorios del propietario autenticado.
     *
     * @return array<string,mixed>
     */
    function obtener_repositorios_propios(): array {
        $repositorios = [];
        $salida = [
            'ok' => true,
            'status' => 200,
            'data' => [],
            'error' => '',
        ];

        for ($pagina = 1; $pagina <= GITSTATS_GITHUB_MAX_PAGES; $pagina++) {
            $lote = obtener_pagina_repositorios_propios($pagina);
            if (!$lote['ok']) {
                $salida = $lote;
                break;
            }

            foreach ($lote['data'] as $repo) {
                $repositorios[] = $repo;
            }

            if ($lote['isLastPage']) {
                break;
            }
        }

        if ($salida['ok']) {
            $salida['data'] = $repositorios;
        }

        return $salida;
    }
}

if (!function_exists('obtener_pagina_repositorios_propios')) {
    /**
     * Recupera una pagina de repositorios.
     *
     * @param int $pagina Numero de pagina.
     * @return array<string,mixed>
     */
    function obtener_pagina_repositorios_propios(int $pagina): array {
        $url = sprintf(
            'https://api.github.com/user/repos?visibility=all&affiliation=owner,collaborator,organization_member&sort=updated&per_page=%d&page=%d',
            GITSTATS_GITHUB_PER_PAGE,
            $pagina
        );

        $resultado = solicitar_json_github($url);
        if (!$resultado['ok']) {
            return [
                'ok' => false,
                'status' => (int) ($resultado['status'] ?? 502),
                'error' => (string) ($resultado['error'] ?? 'No se pudo consultar GitHub.'),
                'data' => [],
                'isLastPage' => true,
            ];
        }

        if (!is_array($resultado['data'])) {
            return [
                'ok' => false,
                'status' => 502,
                'error' => 'Respuesta inesperada de GitHub.',
                'data' => [],
                'isLastPage' => true,
            ];
        }

        $cantidad = 0;
        $filtrados = [];

        foreach ($resultado['data'] as $repo) {
            if (!is_array($repo)) {
                continue;
            }

            $cantidad++;
            if (!GITSTATS_INCLUDE_FORKS && !empty($repo['fork'])) {
                continue;
            }

            $filtrados[] = $repo;
        }

        return [
            'ok' => true,
            'status' => 200,
            'error' => '',
            'data' => $filtrados,
            'isLastPage' => $cantidad < GITSTATS_GITHUB_PER_PAGE,
        ];
    }
}

if (!function_exists('recolectar_lenguajes_globales')) {
    /**
     * Acumula bytes por lenguaje de todos los repositorios.
     *
     * @param array<int,mixed> $repos Repositorios.
     * @return array<string,mixed>
     */
    function recolectar_lenguajes_globales(array $repos): array {
        $globales = [];
        $errores = 0;

        foreach ($repos as $repo) {
            if (!is_array($repo)) {
                continue;
            }

            $url = isset($repo['languages_url']) ? (string) $repo['languages_url'] : '';
            if ($url === '') {
                continue;
            }

            $resultado = solicitar_json_github($url);
            if (!$resultado['ok'] || !is_array($resultado['data'])) {
                $errores++;
                continue;
            }

            $normalizados = normalizar_bytes_lenguajes($resultado['data']);
            foreach ($normalizados as $lenguaje => $bytes) {
                if (!isset($globales[$lenguaje])) {
                    $globales[$lenguaje] = 0;
                }

                $globales[$lenguaje] += $bytes;
            }
        }

        return [
            'languages' => $globales,
            'languageRequestErrors' => $errores,
        ];
    }
}

if (!function_exists('construir_estadisticas_github')) {
    /**
     * Construye estadisticas agregadas de repositorios.
     *
     * @param array<int,mixed> $repos Repositorios.
     * @return array<string,int>
     */
    function construir_estadisticas_github(array $repos): array {
        $stats = [
            'totalRepositories' => 0,
            'publicRepositories' => 0,
            'privateRepositories' => 0,
            'archivedRepositories' => 0,
            'forkRepositories' => 0,
            'totalStars' => 0,
            'totalForks' => 0,
            'totalWatchers' => 0,
            'totalOpenIssues' => 0,
        ];

        foreach ($repos as $repo) {
            if (!is_array($repo)) {
                continue;
            }

            $stats['totalRepositories']++;
            $stats['publicRepositories'] += !empty($repo['private']) ? 0 : 1;
            $stats['privateRepositories'] += !empty($repo['private']) ? 1 : 0;
            $stats['archivedRepositories'] += !empty($repo['archived']) ? 1 : 0;
            $stats['forkRepositories'] += !empty($repo['fork']) ? 1 : 0;
            $stats['totalStars'] += (int) ($repo['stargazers_count'] ?? 0);
            $stats['totalForks'] += (int) ($repo['forks_count'] ?? 0);
            $stats['totalWatchers'] += (int) ($repo['watchers_count'] ?? 0);
            $stats['totalOpenIssues'] += (int) ($repo['open_issues_count'] ?? 0);
        }

        return $stats;
    }
}

if (!function_exists('solicitar_json_github')) {
    /**
     * Solicita JSON a GitHub con validacion de respuesta.
     *
     * @param string $url URL objetivo.
     * @return array<string,mixed>
     */
    function solicitar_json_github(string $url): array {
        $resultado = [
            'ok' => false,
            'status' => 502,
            'error' => 'No se pudo consultar GitHub.',
        ];

        $http = ejecutar_solicitud_http_github($url, construir_encabezados_github(), GITSTATS_REQUEST_TIMEOUT_SECONDS);
        if ($http['ok']) {
            $estado = (int) ($http['status'] ?? 200);
            $cuerpo = (string) ($http['body'] ?? '');
            $json = json_decode($cuerpo, true);

            if ($estado >= 400) {
                $resultado['status'] = $estado;
                $resultado['error'] = 'GitHub rechazo la solicitud.';
            } elseif (json_last_error() !== JSON_ERROR_NONE) {
                $resultado['status'] = 502;
                $resultado['error'] = 'GitHub devolvio una respuesta invalida.';
            } else {
                $resultado = [
                    'ok' => true,
                    'status' => $estado > 0 ? $estado : 200,
                    'data' => $json,
                ];
            }
        } else {
            $resultado['status'] = (int) ($http['status'] ?? 502);
        }

        return $resultado;
    }
}

if (!function_exists('construir_encabezados_github')) {
    /**
     * Construye encabezados HTTP para GitHub.
     *
     * @return array<int,string>
     */
    function construir_encabezados_github(): array {
        return [
            'Accept: application/vnd.github+json',
            'X-GitHub-Api-Version: 2022-11-28',
            'User-Agent: gitstats-api-protegida',
            'Authorization: Bearer ' . GITSTATS_GITHUB_TOKEN,
        ];
    }
}

if (!function_exists('ejecutar_solicitud_http_github')) {
    /**
     * Ejecuta una solicitud HTTP usando cURL.
     *
     * @param string $url URL objetivo.
     * @param array<int,string> $encabezados Encabezados HTTP.
     * @param int $tiempoEspera Segundos de timeout.
     * @return array<string,mixed>
     */
    function ejecutar_solicitud_http_github(string $url, array $encabezados, int $tiempoEspera): array {
        $resultado = [
            'ok' => false,
            'status' => 500,
            'body' => '',
        ];

        $ch = curl_init($url);
        if ($ch !== false) {
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => $tiempoEspera,
                CURLOPT_CONNECTTIMEOUT => 8,
                CURLOPT_HTTPHEADER => $encabezados,
            ]);

            $body = curl_exec($ch);
            $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);

            if (PHP_VERSION_ID >= 80000) {
                unset($ch);
            } else {
                curl_close($ch);
            }

            if ($body !== false) {
                $resultado = [
                    'ok' => true,
                    'status' => $status > 0 ? $status : 200,
                    'body' => (string) $body,
                ];
            }
        }

        return $resultado;
    }
}

if (!function_exists('normalizar_bytes_lenguajes')) {
    /**
     * Normaliza un arreglo de bytes por lenguaje.
     *
     * @param array<mixed,mixed> $lenguajes Lenguajes crudos.
     * @return array<string,int>
     */
    function normalizar_bytes_lenguajes(array $lenguajes): array {
        $salida = [];

        foreach ($lenguajes as $lenguaje => $bytes) {
            if (!is_string($lenguaje)) {
                continue;
            }

            $salida[$lenguaje] = max(0, (int) $bytes);
        }

        return $salida;
    }
}

if (!function_exists('construir_estadisticas_lenguajes')) {
    /**
     * Calcula porcentaje global por lenguaje.
     *
     * @param array<string,int> $lenguajes Lenguaje=>bytes.
     * @return array<string,mixed>
     */
    function construir_estadisticas_lenguajes(array $lenguajes): array {
        if (empty($lenguajes)) {
            return [
                'totalBytes' => 0,
                'usage' => [],
            ];
        }

        arsort($lenguajes);
        $total = array_sum($lenguajes);
        if ($total <= 0) {
            return [
                'totalBytes' => 0,
                'usage' => [],
            ];
        }

        $uso = [];
        foreach ($lenguajes as $lenguaje => $bytes) {
            $uso[] = [
                'language' => (string) $lenguaje,
                'bytes' => (int) $bytes,
                'percentage' => round(((int) $bytes / $total) * 100, 2),
            ];
        }

        return [
            'totalBytes' => $total,
            'usage' => $uso,
        ];
    }
}

if (!function_exists('construir_meta_respuesta')) {
    /**
     * Construye metadatos de respuesta estandar.
     *
     * @param bool $cacheHit Si viene de cache.
     * @param int $edadCache Edad del cache.
     * @param int $erroresLenguajes Cantidad de errores en lenguajes.
     * @return array<string,mixed>
     */
    function construir_meta_respuesta(bool $cacheHit, int $edadCache, int $erroresLenguajes): array {
        return [
            'generatedAt' => gmdate('c'),
            'cache' => [
                'hit' => $cacheHit,
                'ttlDays' => obtener_cache_dias(),
                'ttlSeconds' => obtener_cache_ttl_segundos(),
                'ageSeconds' => $edadCache,
            ],
            'languageRequestsFailed' => $erroresLenguajes,
        ];
    }
}

if (!function_exists('obtener_cache_dias')) {
    /**
     * Obtiene dias configurados de cache.
     *
     * Si vale 0, se considera modo realtime.
     *
     * @return int
     */
    function obtener_cache_dias(): int {
        $dias = 0;

        if (defined('GITSTATS_CACHE_DAYS')) {
            $dias = max(0, (int) constant('GITSTATS_CACHE_DAYS'));
        } elseif (defined('GITSTATS_CACHE_TTL_SECONDS')) {
            $ttl = max(0, (int) constant('GITSTATS_CACHE_TTL_SECONDS'));
            if ($ttl > 0) {
                $dias = (int) ceil($ttl / 86400);
            }
        } else {
            $dias = 0;
        }

        return $dias;
    }
}

if (!function_exists('obtener_cache_ttl_segundos')) {
    /**
     * Convierte dias de cache a segundos.
     *
     * @return int
     */
    function obtener_cache_ttl_segundos(): int {
        return obtener_cache_dias() * 86400;
    }
}

if (!function_exists('obtener_directorio_cache_api')) {
    /**
     * Devuelve directorio de cache en src/api/cache.
     *
     * @return string
     */
    function obtener_directorio_cache_api(): string {
        return __DIR__ . '/cache';
    }
}

if (!function_exists('obtener_ruta_archivo_cache')) {
    /**
     * Obtiene ruta de archivo cache y crea directorio si falta.
     *
     * @param string $nombreArchivo Nombre del archivo cache.
     * @return string|null
     */
    function obtener_ruta_archivo_cache(string $nombreArchivo): ?string {
        $directorio = obtener_directorio_cache_api();
        if (!is_dir($directorio) && !mkdir($directorio, 0775, true) && !is_dir($directorio)) {
            return null;
        }

        return $directorio . '/' . $nombreArchivo;
    }
}

if (!function_exists('cargar_respuesta_cache')) {
    /**
     * Carga una respuesta cacheada si sigue vigente.
     *
     * @param string $rutaCache Ruta del archivo cache.
     * @param int $ttl Tiempo maximo de vida.
     * @return array<string,mixed>|null
     */
    function cargar_respuesta_cache(string $rutaCache, int $ttl): ?array {
        $payload = null;
        $estado = leer_estado_cache($rutaCache, $ttl);

        if ($estado['ok'] && is_array($estado['payload'])) {
            $payload = $estado['payload'];

            if (!isset($payload['meta']) || !is_array($payload['meta'])) {
                $payload['meta'] = [];
            }

            if (!isset($payload['meta']['cache']) || !is_array($payload['meta']['cache'])) {
                $payload['meta']['cache'] = [];
            }

            $payload['meta']['cache']['hit'] = true;
            $payload['meta']['cache']['ttlSeconds'] = $ttl;
            $payload['meta']['cache']['ageSeconds'] = (int) ($estado['ageSeconds'] ?? 0);
        }

        return $payload;
    }
}

if (!function_exists('leer_estado_cache')) {
    /**
     * Lee estado de cache y su payload si es utilizable.
     *
     * @param string $rutaCache Ruta del archivo cache.
     * @param int $ttl Tiempo maximo de vida.
     * @return array<string,mixed>
     */
    function leer_estado_cache(string $rutaCache, int $ttl): array {
        $estado = [
            'ok' => false,
            'payload' => null,
            'ageSeconds' => 0,
        ];

        $cacheVigente = obtener_payload_cache_vigente($rutaCache, $ttl);
        if ($cacheVigente['ok']) {
            $estado = [
                'ok' => true,
                'payload' => $cacheVigente['payload'],
                'ageSeconds' => $cacheVigente['ageSeconds'],
            ];
        }

        return $estado;
    }
}

if (!function_exists('obtener_payload_cache_vigente')) {
    /**
     * Obtiene payload de cache solo si esta vigente y parseable.
     *
     * @param string $rutaCache Ruta del archivo cache.
     * @param int $ttl Tiempo maximo de vida.
     * @return array<string,mixed>
     */
    function obtener_payload_cache_vigente(string $rutaCache, int $ttl): array {
        $salida = [
            'ok' => false,
            'payload' => null,
            'ageSeconds' => 0,
        ];

        $puedeLeer = $ttl > 0 && is_file($rutaCache);
        $modificado = $puedeLeer ? filemtime($rutaCache) : false;
        $valido = is_int($modificado);
        $edad = $valido ? max(0, time() - $modificado) : 0;
        $vigente = $puedeLeer && $valido && $edad <= $ttl;

        if ($vigente) {
            $payload = parsear_carga_cache($rutaCache);
            if ($payload !== null) {
                $salida = [
                    'ok' => true,
                    'payload' => $payload,
                    'ageSeconds' => $edad,
                ];
            }
        }

        return $salida;
    }
}

if (!function_exists('parsear_carga_cache')) {
    /**
     * Parsea contenido JSON de cache.
     *
     * @param string $rutaCache Ruta del archivo cache.
     * @return array<string,mixed>|null
     */
    function parsear_carga_cache(string $rutaCache): ?array {
        $salida = null;
        $raw = file_get_contents($rutaCache);

        if (is_string($raw) && $raw !== '') {
            $json = json_decode($raw, true);
            if (is_array($json)) {
                $salida = $json;
            }
        }

        return $salida;
    }
}

if (!function_exists('guardar_respuesta_cache')) {
    /**
     * Guarda payload JSON en cache local.
     *
     * @param string $rutaCache Ruta de cache.
     * @param array<string,mixed> $payload Contenido a guardar.
     * @return void
     */
    function guardar_respuesta_cache(string $rutaCache, array $payload): void {
        if (obtener_cache_ttl_segundos() <= 0) {
            return;
        }

        $json = json_encode(
            $payload,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE
        );
        if (is_string($json)) {
            file_put_contents($rutaCache, $json, LOCK_EX);
        }
    }
}

if (!function_exists('obtener_limite_solicitudes_por_minuto')) {
    /**
     * Obtiene limite de solicitudes por minuto.
     *
     * 0 desactiva la limitacion.
     *
     * @return int
     */
    function obtener_limite_solicitudes_por_minuto(): int {
        if (!defined('GITSTATS_RATE_LIMIT_PER_MINUTE')) {
            return 120;
        }

        return max(0, (int) constant('GITSTATS_RATE_LIMIT_PER_MINUTE'));
    }
}

if (!function_exists('obtener_ip_cliente')) {
    /**
     * Obtiene la IP de cliente para control de abuso.
     *
     * @return string
     */
    function obtener_ip_cliente(): string {
        $ip = trim(obtener_valor_servidor('REMOTE_ADDR'));
        if ($ip === '') {
            return 'unknown';
        }

        return $ip;
    }
}

if (!function_exists('obtener_ruta_rate_limit')) {
    /**
     * Obtiene ruta de archivo de rate limit por IP.
     *
     * @param string $ip IP cliente.
     * @return string
     */
    function obtener_ruta_rate_limit(string $ip): string {
        return obtener_directorio_cache_api() . '/ratelimit-' . hash('sha256', $ip) . '.json';
    }
}

if (!function_exists('leer_eventos_rate_limit')) {
    /**
     * Lee eventos previos de rate limit.
     *
     * @param string $ruta Archivo de control.
     * @return array<int,int>
     */
    function leer_eventos_rate_limit(string $ruta): array {
        $raw = is_file($ruta) ? file_get_contents($ruta) : '';
        if (!is_string($raw) || $raw === '') {
            return [];
        }

        $json = json_decode($raw, true);
        if (!is_array($json)) {
            return [];
        }

        $eventos = [];
        foreach ($json as $evento) {
            if (!is_numeric($evento)) {
                continue;
            }

            $eventos[] = (int) $evento;
        }

        return $eventos;
    }
}

if (!function_exists('filtrar_eventos_recientes')) {
    /**
     * Filtra eventos dentro de la ventana de 60 segundos.
     *
     * @param array<int,int> $eventos Timestamps previos.
     * @param int $ahora Timestamp actual.
     * @return array<int,int>
     */
    function filtrar_eventos_recientes(array $eventos, int $ahora): array {
        $vigentes = [];
        $minimo = $ahora - 59;

        foreach ($eventos as $evento) {
            if ($evento >= $minimo) {
                $vigentes[] = $evento;
            }
        }

        return $vigentes;
    }
}

if (!function_exists('guardar_eventos_rate_limit')) {
    /**
     * Persiste eventos de rate limit.
     *
     * @param string $ruta Archivo de control.
     * @param array<int,int> $eventos Eventos vigentes.
     * @return void
     */
    function guardar_eventos_rate_limit(string $ruta, array $eventos): void {
        $json = json_encode($eventos, JSON_UNESCAPED_SLASHES);
        if (is_string($json)) {
            file_put_contents($ruta, $json, LOCK_EX);
        }
    }
}

if (!function_exists('validar_limite_solicitud')) {
    /**
     * Valida limite de solicitudes por minuto por IP.
     *
     * @return array{allow:bool,retryAfter:int}
     */
    function validar_limite_solicitud(): array {
        $limite = obtener_limite_solicitudes_por_minuto();
        if ($limite <= 0) {
            return [
                'allow' => true,
                'retryAfter' => 0,
            ];
        }

        $ip = obtener_ip_cliente();
        $ruta = obtener_ruta_rate_limit($ip);
        $ahora = time();

        $eventos = leer_eventos_rate_limit($ruta);
        $eventos = filtrar_eventos_recientes($eventos, $ahora);

        if (count($eventos) >= $limite) {
            $primero = $eventos[0] ?? $ahora;
            $retry = max(1, 60 - ($ahora - $primero));

            return [
                'allow' => false,
                'retryAfter' => $retry,
            ];
        }

        $eventos[] = $ahora;
        guardar_eventos_rate_limit($ruta, $eventos);

        return [
            'allow' => true,
            'retryAfter' => 0,
        ];
    }
}

if (!function_exists('clave_api_esta_configurada')) {
    /**
     * Verifica que exista clave API valida.
     *
     * @return bool
     */
    function clave_api_esta_configurada(): bool {
        $clave = trim(GITSTATS_API_ACCESS_KEY);

        return $clave !== '' && $clave !== 'cambia-por-una-clave-segura';
    }
}

if (!function_exists('token_github_esta_configurado')) {
    /**
     * Verifica que exista token GitHub valido.
     *
     * @return bool
     */
    function token_github_esta_configurado(): bool {
        $token = trim(GITSTATS_GITHUB_TOKEN);

        return $token !== '' && $token !== 'cambia-por-tu-token-github';
    }
}

if (!function_exists('solicitud_esta_autorizada')) {
    /**
     * Valida autenticacion por X-API-Key o Bearer.
     *
     * @return bool
     */
    function solicitud_esta_autorizada(): bool {
        $clave = trim(obtener_valor_servidor('HTTP_X_API_KEY'));

        if ($clave === '') {
            $autorizacion = trim(obtener_valor_servidor('HTTP_AUTHORIZATION'));
            if (str_starts_with($autorizacion, 'Bearer ')) {
                $clave = trim(substr($autorizacion, 7));
            }
        }

        if ($clave === '') {
            return false;
        }

        return hash_equals(GITSTATS_API_ACCESS_KEY, $clave);
    }
}
