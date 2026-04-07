<?php
/**
 * Copyright (c) 2026 CristianVGdev (github.com/CristianVGdev)
 * Proyecto: GitStatsVG (BSD-3-Clause)
 */
declare(strict_types=1);

if (!function_exists('resolver_endpoint_api')) {
    /**
     * Resuelve el endpoint permitido a partir de la ruta solicitada.
     *
     * @param string $ruta Ruta relativa solicitada.
     * @return string|null
     */
    function resolver_endpoint_api(string $ruta): ?string {
        $permitidos = ['end-porcent', 'end-stats'];
        if (!in_array($ruta, $permitidos, true)) {
            return null;
        }

        return $ruta;
    }
}

if (!function_exists('obtener_ruta_api_actual')) {
    /**
     * Obtiene la ruta relativa solicitada dentro de /api.
     *
     * @return string
     */
    function obtener_ruta_api_actual(): string {
        $uri = obtener_valor_servidor('REQUEST_URI');
        $ruta = parse_url($uri, PHP_URL_PATH);
        if (!is_string($ruta)) {
            $ruta = '';
        }

        $scriptName = obtener_valor_servidor('SCRIPT_NAME');
        $directorioApi = str_replace('\\', '/', dirname($scriptName));
        if ($directorioApi !== '' && $directorioApi !== '/' && str_starts_with($ruta, $directorioApi)) {
            $ruta = substr($ruta, strlen($directorioApi));
        }

        $ruta = trim($ruta, '/');
        if ($ruta === '' || $ruta === 'index.php') {
            return '';
        }

        if (str_contains($ruta, '/')) {
            return '';
        }

        return strtolower($ruta);
    }
}

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

if (!function_exists('obtener_metodo_solicitud')) {
    /**
     * Obtiene el metodo HTTP actual.
     *
     * @return string
     */
    function obtener_metodo_solicitud(): string {
        $metodo = strtoupper(trim(obtener_valor_servidor('REQUEST_METHOD')));
        if ($metodo === '') {
            return 'GET';
        }

        return $metodo;
    }
}
