<?php
/**
 * Copyright (c) 2026 CristianVGdev (github.com/CristianVGdev)
 * Proyecto: GitStatsVG (BSD-3-Clause)
 */
declare(strict_types=1);

if (!function_exists('estado_vistas')) {
    /**
     * Devuelve el estado interno del motor de vistas.
     *
     * @return array<string,mixed>
     */
    function &estado_vistas(): array {
        static $estado = [
            'modoRegistro' => false,
            'registro' => [],
            'archivoActual' => '',
            'payload' => [],
        ];

        return $estado;
    }
}

if (!function_exists('aplicar_encabezados_seguridad_html')) {
    /**
     * Aplica encabezados de seguridad para vistas HTML.
     *
     * @return void
     */
    function aplicar_encabezados_seguridad_html(): void {
        if (function_exists('header_remove')) {
            header_remove('X-Powered-By');
        }

        header('Content-Type: text/html; charset=utf-8');
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('Referrer-Policy: no-referrer');
        header('Permissions-Policy: geolocation=(), camera=(), microphone=()');
        header('X-Permitted-Cross-Domain-Policies: none');
        header("Content-Security-Policy: default-src 'self'; style-src 'self' 'unsafe-inline'; frame-ancestors 'self'; base-uri 'none'; form-action 'none';");
    }
}

if (!function_exists('aplicar_encabezados_seguridad_svg')) {
    /**
     * Aplica encabezados de seguridad para respuestas SVG.
     *
     * @return void
     */
    function aplicar_encabezados_seguridad_svg(): void {
        if (function_exists('header_remove')) {
            header_remove('X-Powered-By');
        }

        header('Content-Type: image/svg+xml; charset=utf-8');
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('Referrer-Policy: no-referrer');
        header('Permissions-Policy: geolocation=(), camera=(), microphone=()');
        header('X-Permitted-Cross-Domain-Policies: none');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
    }
}

if (!function_exists('responder_error_html')) {
    /**
     * Responde error HTML simple y legible.
     *
     * @param int $codigo Codigo HTTP.
     * @param string $mensaje Mensaje a mostrar.
     * @return void
     */
    function responder_error_html(int $codigo, string $mensaje): void {
        http_response_code($codigo);
        echo '<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Error</title></head><body>';
        echo '<h1>Error ' . $codigo . '</h1>';
        echo '<p>' . htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8') . '</p>';
        echo '</body></html>';
        exit;
    }
}

if (!function_exists('vista_modo_registro')) {
    /**
     * Indica si el sistema de vistas esta en modo registro.
     *
     * @return bool
     */
    function vista_modo_registro(): bool {
        $estado = estado_vistas();

        return !empty($estado['modoRegistro']);
    }
}

if (!function_exists('iniciar_registro_vistas')) {
    /**
     * Inicializa modo de registro de vistas.
     *
     * @return void
     */
    function iniciar_registro_vistas(): void {
        $estado = &estado_vistas();
        $estado['modoRegistro'] = true;
        $estado['registro'] = [];
        $estado['archivoActual'] = '';
    }
}

if (!function_exists('definir_archivo_vista_en_registro')) {
    /**
     * Define archivo actual durante registro de vistas.
     *
     * @param string $rutaArchivo Ruta del archivo de vista.
     * @return void
     */
    function definir_archivo_vista_en_registro(string $rutaArchivo): void {
        $estado = &estado_vistas();
        $estado['archivoActual'] = $rutaArchivo;
    }
}

if (!function_exists('finalizar_registro_vistas')) {
    /**
     * Finaliza registro y devuelve mapa de rutas publicas.
     *
     * @return array<string,string>
     */
    function finalizar_registro_vistas(): array {
        $estado = &estado_vistas();
        $registro = isset($estado['registro']) && is_array($estado['registro']) ? $estado['registro'] : [];

        $estado['modoRegistro'] = false;
        $estado['registro'] = [];
        $estado['archivoActual'] = '';

        return $registro;
    }
}

if (!function_exists('normalizar_ruta_publica_vista')) {
    /**
     * Normaliza clave de ruta publica de vista.
     *
     * @param string $ruta Ruta solicitada.
     * @return string
     */
    function normalizar_ruta_publica_vista(string $ruta): string {
        return strtolower(trim($ruta, '/'));
    }
}

if (!function_exists('url_public')) {
    /**
     * Registra/retorna la URL publica de una vista.
     *
     * @param string $clave Clave publica.
     * @return string
     */
    function url_public(string $clave): string {
        $ruta = strtolower(trim($clave, '/'));
        if ($ruta === '') {
            $ruta = 'home';
        }

        if (vista_modo_registro()) {
            $estado = &estado_vistas();
            $archivoActual = isset($estado['archivoActual']) && is_string($estado['archivoActual'])
                ? $estado['archivoActual']
                : '';

            if ($archivoActual !== '') {
                if (!isset($estado['registro']) || !is_array($estado['registro'])) {
                    $estado['registro'] = [];
                }

                $estado['registro'][$ruta] = $archivoActual;
            }
        }

        return '/' . $ruta;
    }
}

if (!function_exists('json_carga')) {
    /**
     * Lee una clave del payload JSON actual usando notacion con punto.
     *
     * @param string $ruta Ruta de clave, por ejemplo: githubStats.totalRepositories
     * @param mixed $valorDefecto Valor por defecto si no existe.
     * @return mixed
     */
    function json_carga(string $ruta = '', $valorDefecto = null) {
        $estado = estado_vistas();
        $payload = isset($estado['payload']) && is_array($estado['payload']) ? $estado['payload'] : [];

        if ($ruta === '') {
            return $payload;
        }

        $actual = $payload;
        foreach (explode('.', $ruta) as $segmento) {
            if (!is_array($actual) || !array_key_exists($segmento, $actual)) {
                return $valorDefecto;
            }

            $actual = $actual[$segmento];
        }

        return $actual;
    }
}

if (!function_exists('obtener_archivos_vista')) {
    /**
     * Obtiene archivos de vista disponibles.
     *
     * @return array<int,string>
     */
    function obtener_archivos_vista(): array {
        $archivos = glob(__DIR__ . '/../views/*.php');
        if ($archivos === false) {
            return [];
        }

        $salida = [];
        foreach ($archivos as $archivo) {
            if (!is_string($archivo)) {
                continue;
            }

            $salida[] = $archivo;
        }

        return $salida;
    }
}

if (!function_exists('construir_registro_vistas')) {
    /**
     * Construye mapa ruta publica => archivo fisico de vista.
     *
     * @return array<string,string>
     */
    function construir_registro_vistas(): array {
        iniciar_registro_vistas();

        foreach (obtener_archivos_vista() as $archivoVista) {
            definir_archivo_vista_en_registro($archivoVista);

            ob_start();
            include $archivoVista;
            ob_end_clean();
        }

        return finalizar_registro_vistas();
    }
}

if (!function_exists('resolver_vista_publica')) {
    /**
     * Resuelve archivo de vista por ruta publica.
     *
     * @param string $ruta Ruta publica sin slash inicial.
     * @return string|null
     */
    function resolver_vista_publica(string $ruta): ?string {
        static $registro = null;

        if (!is_array($registro)) {
            $registro = construir_registro_vistas();
        }

        $clave = normalizar_ruta_publica_vista($ruta);
        if ($clave === '' || !isset($registro[$clave])) {
            return null;
        }

        $archivo = $registro[$clave];
        if (!is_string($archivo) || !is_file($archivo)) {
            return null;
        }

        return $archivo;
    }
}

if (!function_exists('renderizar_vista_github')) {
    /**
     * Renderiza el HTML de una vista registrada por url_public().
     *
     * @param array<string,mixed> $payload Datos JSON procesados.
     * @param string $endpoint Endpoint o clave publica.
     * @param string $formato Formato de salida (html|svg).
     * @return void
     */
    function renderizar_vista_github(array $payload, string $endpoint, string $formato = 'html'): void {
        if ($formato === 'svg') {
            renderizar_vista_github_svg($payload, $endpoint);
            return;
        }

        $contenido = obtener_html_vista_github($payload, $endpoint);
        if (!is_string($contenido)) {
            responder_error_html(404, 'Vista no disponible.');
        }

        echo $contenido;
        exit;
    }
}

if (!function_exists('obtener_html_vista_github')) {
    /**
     * Renderiza una vista y devuelve su HTML como string.
     *
     * @param array<string,mixed> $payload Datos JSON procesados.
     * @param string $endpoint Endpoint o clave publica.
     * @return string|null
     */
    function obtener_html_vista_github(array $payload, string $endpoint): ?string {
        $archivoVista = resolver_vista_publica($endpoint);
        if ($archivoVista === null) {
            return null;
        }

        $estado = &estado_vistas();
        $estado['modoRegistro'] = false;
        $estado['payload'] = $payload;

        ob_start();
        include $archivoVista;
        $contenido = ob_get_clean();

        unset($estado['payload']);

        return is_string($contenido) ? $contenido : null;
    }
}

if (!function_exists('extraer_contenido_tag_html')) {
    /**
     * Extrae el contenido interno de una etiqueta HTML.
     *
     * @param string $html Documento HTML.
     * @param string $tag Nombre de la etiqueta.
     * @return string
     */
    function extraer_contenido_tag_html(string $html, string $tag): string {
        $regex = '~<' . preg_quote($tag, '~') . '\b[^>]*>(.*?)</' . preg_quote($tag, '~') . '>~is';
        $coincidencias = [];
        if (preg_match($regex, $html, $coincidencias) === 1 && isset($coincidencias[1])) {
            return trim((string) $coincidencias[1]);
        }

        return '';
    }
}

if (!function_exists('obtener_dimensiones_svg_vista')) {
    /**
     * Obtiene dimensiones del SVG segun endpoint y cantidad de datos.
     *
     * @param string $endpoint Endpoint de vista.
     * @param array<string,mixed> $payload Datos de la vista.
     * @return array{width:int,height:int}
     */
    function obtener_dimensiones_svg_vista(string $endpoint, array $payload): array {
        if ($endpoint === 'porcent') {
            $lista = isset($payload['globalLanguagePercentage']) && is_array($payload['globalLanguagePercentage'])
                ? $payload['globalLanguagePercentage']
                : [];

            $filas = max(1, min(12, count($lista)));

            return [
                'width' => 540,
                'height' => 120 + ($filas * 42),
            ];
        }

        return [
            'width' => 540,
            'height' => 460,
        ];
    }
}

if (!function_exists('construir_svg_desde_html')) {
    /**
     * Asegura namespaces SVG en etiquetas <svg> embebidas dentro de XHTML.
     *
     * @param string $htmlFragment Fragmento HTML/XHTML.
     * @return string
     */
    function normalizar_svg_embebido_en_xhtml(string $htmlFragment): string {
        return (string) preg_replace_callback(
            '/<svg\b[^>]*>/iu',
            static function (array $coincidencia): string {
                $tag = $coincidencia[0] ?? '<svg>';
                if (!is_string($tag)) {
                    return '<svg>';
                }

                if (preg_match('/\sxmlns\s*=\s*["\']/iu', $tag) === 1) {
                    return $tag;
                }

                $tagSinCierre = rtrim($tag, '>');
                return $tagSinCierre . ' xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">';
            },
            $htmlFragment
        );
    }

    /**
     * Convierte HTML de vista en un SVG usando foreignObject.
     *
     * @param string $html Documento HTML.
     * @param int $ancho Ancho del SVG.
     * @param int $alto Alto del SVG.
     * @return string
     */
    function construir_svg_desde_html(string $html, int $ancho, int $alto): string {
        $estilos = extraer_contenido_tag_html($html, 'style');
        $body = extraer_contenido_tag_html($html, 'body');
        if ($body === '') {
            $body = $html;
        }

        $body = normalizar_svg_embebido_en_xhtml($body);

        $xhtml = '<div xmlns="http://www.w3.org/1999/xhtml" style="width:' . $ancho . 'px;height:' . $alto . 'px;overflow:hidden;">';
        if ($estilos !== '') {
            $xhtml .= '<style>' . $estilos . '</style>';
        }
        $xhtml .= '<style>*{font-family:Consolas,"Courier New",monospace !important;}</style>';
        $xhtml .= $body;
        $xhtml .= '</div>';

        return sprintf(
            '<?xml version="1.0" encoding="UTF-8"?><svg xmlns="http://www.w3.org/2000/svg" width="%d" height="%d" viewBox="0 0 %d %d" role="img" aria-label="GitStatsVG Image"><foreignObject x="0" y="0" width="100%%" height="100%%">%s</foreignObject></svg>',
            $ancho,
            $alto,
            $ancho,
            $alto,
            $xhtml
        );
    }
}

if (!function_exists('renderizar_vista_github_svg')) {
    /**
     * Renderiza una vista como SVG convirtiendo su HTML.
     *
     * @param array<string,mixed> $payload Datos JSON procesados.
     * @param string $endpoint Endpoint o clave publica.
     * @return void
     */
    function renderizar_vista_github_svg(array $payload, string $endpoint): void {
        $html = obtener_html_vista_github($payload, $endpoint);
        if (!is_string($html)) {
            responder_error_html(404, 'Vista no disponible.');
        }

        $dimensiones = obtener_dimensiones_svg_vista($endpoint, $payload);
        echo construir_svg_desde_html($html, $dimensiones['width'], $dimensiones['height']);
        exit;
    }
}

if (!function_exists('obtener_ruta_vista_actual')) {
    /**
     * Obtiene la ruta actual para resolver vistas.
     *
     * @return string
     */
    function obtener_ruta_vista_actual(): string {
        $uri = filter_input(INPUT_SERVER, 'REQUEST_URI', FILTER_UNSAFE_RAW);
        if (!is_string($uri)) {
            $uri = '';
        }

        $ruta = parse_url($uri, PHP_URL_PATH);
        if (!is_string($ruta)) {
            $ruta = '';
        }

        $script = filter_input(INPUT_SERVER, 'SCRIPT_NAME', FILTER_UNSAFE_RAW);
        if (!is_string($script)) {
            $script = '';
        }

        $directorio = str_replace('\\', '/', dirname($script));
        if ($directorio !== '' && $directorio !== '/' && str_starts_with($ruta, $directorio)) {
            $ruta = substr($ruta, strlen($directorio));
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
