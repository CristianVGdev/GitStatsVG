<?php
/**
 * Copyright (c) 2026 CristianVGdev (github.com/CristianVGdev)
 * Proyecto: GitStatsVG (BSD-3-Clause)
 */
declare(strict_types=1);

if (!function_exists('obtener_directorio_logs_app')) {
    /**
     * Devuelve la ruta absoluta al directorio de logs.
     *
     * @return string
     */
    function obtener_directorio_logs_app(): string {
        return __DIR__ . '/logs';
    }
}

if (!function_exists('obtener_request_id_app')) {
    /**
     * Genera/retorna un ID estable por request.
     *
     * @return string
     */
    function obtener_request_id_app(): string {
        return uniqid('req_', true);
    }
}

if (!function_exists('normalizar_array_contexto_log')) {
    /**
     * Normaliza arrays para el contexto de log.
     *
     * @param array<mixed> $valor Valor a normalizar.
     * @param int $nivel Nivel de recursion.
     * @return array<string,mixed>
     */
    function normalizar_array_contexto_log(array $valor, int $nivel): array {
        $normalizado = [];
        $contador = 0;

        foreach ($valor as $clave => $item) {
            if ($contador >= 40) {
                $normalizado['...'] = '[truncated]';
                break;
            }

            $claveTexto = is_int($clave) ? (string) $clave : $clave;
            $normalizado[$claveTexto] = normalizar_valor_contexto_log($item, $nivel + 1);
            $contador++;
        }

        return $normalizado;
    }
}

if (!function_exists('normalizar_throwable_contexto_log')) {
    /**
     * Normaliza throwables para contexto de log.
     *
     * @param Throwable $error Error a normalizar.
     * @return array<string,mixed>
     */
    function normalizar_throwable_contexto_log(Throwable $error): array {
        return [
            'type' => get_class($error),
            'message' => $error->getMessage(),
            'file' => $error->getFile(),
            'line' => $error->getLine(),
        ];
    }
}

if (!function_exists('obtener_valor_servidor_logger')) {
    /**
     * Lee una variable de servidor de forma segura.
     *
     * @param string $nombre Nombre de la variable.
     * @return string
     */
    function obtener_valor_servidor_logger(string $nombre): string {
        $valor = filter_input(INPUT_SERVER, $nombre, FILTER_UNSAFE_RAW);
        if (!is_string($valor)) {
            return '';
        }

        return $valor;
    }
}

if (!function_exists('valor_contexto_escalar')) {
    /**
     * Indica si el valor es escalar serializable directo.
     *
     * @param mixed $valor Valor a validar.
     * @return bool
     */
    function valor_contexto_escalar($valor): bool {
        if ($valor === null) {
            return true;
        }

        if (is_bool($valor) || is_int($valor) || is_float($valor)) {
            return true;
        }

        return is_string($valor);
    }
}

if (!function_exists('solicitud_actual_es_img')) {
    /**
     * Determina si la solicitud actual corresponde a una ruta /img.
     *
     * @return bool
     */
    function solicitud_actual_es_img(): bool {
        $uri = obtener_valor_servidor_logger('REQUEST_URI');
        $path = parse_url($uri, PHP_URL_PATH);
        if (!is_string($path) || $path === '') {
            return false;
        }

        $ruta = strtolower(rtrim($path, '/'));

        return str_ends_with($ruta, '/img');
    }
}

if (!function_exists('normalizar_valor_contexto_log')) {
    /**
     * Convierte valores complejos a representaciones seguras para log.
     *
     * @param mixed $valor Valor original.
     * @param int $nivel Nivel de recursion.
     * @return mixed
     */
    function normalizar_valor_contexto_log($valor, int $nivel = 0) {
        if ($nivel > 4) {
            $resultado = '[max-depth]';
        } elseif (valor_contexto_escalar($valor)) {
            $resultado = $valor;
        } elseif ($valor instanceof Throwable) {
            $resultado = normalizar_throwable_contexto_log($valor);
        } elseif (is_array($valor)) {
            $resultado = normalizar_array_contexto_log($valor, $nivel);
        } elseif (is_object($valor)) {
            $resultado = [
                'object' => get_class($valor),
            ];
        } else {
            $resultado = '[' . gettype($valor) . ']';
        }

        return $resultado;
    }
}

if (!function_exists('escribir_log_app')) {
    /**
     * Escribe un registro JSON por linea en logs/app.log.
     *
     * @param string $nivel Nivel del evento.
     * @param string $evento Nombre del evento.
     * @param array<string,mixed> $contexto Contexto opcional.
     * @return void
     */
    function escribir_log_app(string $nivel, string $evento, array $contexto = []): void {
        $directorio = obtener_directorio_logs_app();
        if (!is_dir($directorio)) {
            $creado = mkdir($directorio, 0775, true);
            if (!$creado && !is_dir($directorio)) {
                return;
            }
        }

        if (!is_dir($directorio) || !is_writable($directorio)) {
            return;
        }

        $archivo = $directorio . '/app.log';

        $registro = [
            'timestamp' => gmdate('c'),
            'level' => strtoupper(trim($nivel)),
            'event' => trim($evento),
            'requestId' => obtener_request_id_app(),
            'method' => obtener_valor_servidor_logger('REQUEST_METHOD'),
            'uri' => obtener_valor_servidor_logger('REQUEST_URI'),
            'scriptName' => obtener_valor_servidor_logger('SCRIPT_NAME'),
            'remoteAddr' => obtener_valor_servidor_logger('REMOTE_ADDR'),
            'userAgent' => obtener_valor_servidor_logger('HTTP_USER_AGENT'),
            'context' => normalizar_valor_contexto_log($contexto),
        ];

        $linea = json_encode($registro, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if (!is_string($linea)) {
            return;
        }

        file_put_contents($archivo, $linea . PHP_EOL, FILE_APPEND | LOCK_EX);
    }
}

if (!function_exists('registrar_evento_http_img')) {
    /**
     * Registra solo errores 404/500 para solicitudes de imagen /img.
     *
     * @param int $codigoHttp Codigo HTTP.
     * @param string $evento Nombre del evento.
     * @param array<string,mixed> $contexto Contexto adicional.
     * @return void
     */
    function registrar_evento_http_img(int $codigoHttp, string $evento, array $contexto = []): void {
        if (!solicitud_actual_es_img()) {
            return;
        }

        if ($codigoHttp !== 404 && $codigoHttp !== 500) {
            return;
        }

        $nivel = $codigoHttp === 500 ? 'error' : 'warning';
        $contexto['httpStatus'] = $codigoHttp;

        escribir_log_app($nivel, $evento, $contexto);
    }
}

if (!function_exists('inicializar_logger_app')) {
    /**
     * Registra manejadores de errores/excepciones para diagnostico.
     *
     * @return void
     */
    function inicializar_logger_app(): void {
        set_exception_handler(
            static function (Throwable $exception): void {
                registrar_evento_http_img(500, 'img_uncaught_exception', [
                    'exception' => $exception,
                ]);

                if (!headers_sent()) {
                    http_response_code(500);
                    header('Content-Type: application/json; charset=utf-8');
                }

                echo '{"error":"Error interno del servidor."}';
                exit;
            }
        );

        register_shutdown_function(
            static function (): void {
                $error = error_get_last();
                if (!is_array($error)) {
                    return;
                }

                $fatales = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR];
                if (!in_array((int) $error['type'], $fatales, true)) {
                    return;
                }

                registrar_evento_http_img(500, 'img_fatal_error', [
                    'type' => $error['type'],
                    'message' => isset($error['message']) ? (string) $error['message'] : '',
                    'file' => isset($error['file']) ? (string) $error['file'] : '',
                    'line' => isset($error['line']) ? (int) $error['line'] : 0,
                ]);
            }
        );
    }
}
