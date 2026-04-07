<!-- Copyright (c) 2026 CristianVGdev (github.com/CristianVGdev) | Proyecto: GitStatsVG (BSD-3-Clause) -->
<!-- p align="center">
  <img src="./docs/assets/gitstats-vg-banner.png" alt="GitStatsVG banner" width="100%">
</!-->

<h1 align="center">GitStatsVG 🦊</h1>

<p align="center">
  API PHP protegida para exponer estadísticas anónimas de GitHub, incluyendo repos privados, sin revelar usuario ni nombres de repositorio.
</p>

<p align="center">
  <img src="https://img.shields.io/github/license/CristianVGdev/gitstats" alt="Licencia">
  <img src="https://img.shields.io/github/last-commit/CristianVGdev/gitstats" alt="Último commit">
  <img src="https://img.shields.io/github/repo-size/CristianVGdev/gitstats" alt="Tamaño del repo">
  <img src="https://img.shields.io/github/languages/top/CristianVGdev/gitstats" alt="Lenguaje principal">
  <img src="https://img.shields.io/badge/dependencies-0-2ea44f" alt="Sin dependencias">
  <img src="https://img.shields.io/badge/API-an%C3%B3nima-0969da" alt="API tipo anónima">
  <a href="https://github.com/CristianVGdev" target="_blank" rel="noopener noreferrer">
  <img src="https://img.shields.io/badge/copy-github.com%2FCristianVGdev-0969da?logo=github" alt="copy github.com/CristianVGdev">
</a>
</p>

<p align="center">
  <a href="#resumen">Resumen</a> •
  <a href="#qué-entrega">Qué entrega</a> •
  <a href="#rutas-disponibles">Rutas</a> •
  <a href="#uso-rápido">Uso rápido</a> •
  <a href="#configuración">Configuración</a> •
  <a href="#seguridad">Seguridad</a> •
  <a href="#comunidad-y-colaboracion-esen">Comunidad</a> •
  <a href="#estructura-del-proyecto">Estructura</a>
</p>

---

## Resumen

**GitStatsVG** consulta GitHub desde servidor y publica únicamente métricas agregadas y anónimas.

Está pensado para estos escenarios:

- mostrar estadísticas sin exponer identidad de cuenta
- trabajar con repos privados desde backend
- limitar la superficie pública a rutas controladas
- servir JSON y vistas gráficas desde una misma entrada

> [!IMPORTANT]
> La aplicación usa una sola entrada pública (`index.php`) y solo habilita 4 rutas.  
> Las rutas de API requieren token de GitHub configurado en servidor y una clave de cliente válida.  
> Las vistas gráficas requieren únicamente el token del servidor.

## Qué entrega

<table>
  <tr>
    <td width="33%" valign="top">
      <strong>Privacidad primero</strong><br>
      La respuesta JSON omite cualquier dato que identifique al usuario o a los repositorios.
    </td>
    <td width="33%" valign="top">
      <strong>Backend simple</strong><br>
      PHP puro, extensión cURL y cero dependencias de terceros.
    </td>
    <td width="33%" valign="top">
      <strong>Repos privados</strong><br>
      Consume datos desde GitHub con token del servidor y devuelve solo métricas agregadas.
    </td>
  </tr>
</table>

## Qué expone

### 1) Porcentaje global de lenguajes

Entrega el peso acumulado de lenguajes detectados por GitHub en todos los repositorios considerados.

### 2) Estadísticas generales de la cuenta

Entrega métricas globales agregadas de la cuenta, sin exponer nombres de repos ni usuario.

## Rutas disponibles

| Ruta | Tipo | Descripción | Protección |
| --- | --- | --- | --- |
| `/api/end-porcent` | JSON | Porcentaje global de lenguajes | Token GitHub + API key |
| `/api/end-stats` | JSON | Estadísticas generales de la cuenta | Token GitHub + API key |
| `/porcent` | Vista | Visualización gráfica de lenguajes | Token GitHub |
| `/stats` | Vista | Visualización gráfica de estadísticas | Token GitHub |

Cualquier otra ruta responde `404`.

## Flujo de la app

```txt
Cliente
  -> index.php
    -> routerApi.php o routerVistas.php
      -> servicioApi.php
        -> GitHub API
        -> cache local
      -> respuestaApi.php o vistas
```

## Requisitos

- PHP con extensión `cURL` habilitada
- Sin dependencias de terceros
- Token de GitHub con permisos suficientes para consultar repos privados
- Configuración runtime vía `.user.ini`

## Uso rápido

### Consultar porcentaje global de lenguajes

```bash
curl -H "X-API-Key: tu_clave" https://tu-dominio.com/api/end-porcent
```

### Consultar estadísticas generales

```bash
curl -H "Authorization: Bearer tu_clave" https://tu-dominio.com/api/end-stats
```

### Abrir vistas gráficas

```txt
https://tu-dominio.com/porcent
https://tu-dominio.com/stats
```

## Seguridad

### Entrada única

La aplicación centraliza todo en [`index.php`](./index.php) como front controller.

### Acceso a la API

Las rutas:

- `/api/end-porcent`
- `/api/end-stats`

requieren:

- token de GitHub configurado en servidor
- clave de cliente enviada por alguno de estos headers

```http
X-API-Key: tu_clave
```

```http
Authorization: Bearer tu_clave
```

Si falta el token de GitHub o la clave del cliente no es válida, la API responde `403`.

### Acceso a vistas

Las rutas:

- `/porcent`
- `/stats`

requieren:

- token de GitHub configurado en servidor

No requieren `X-API-Key` ni `Authorization: Bearer` del cliente.

### Protección adicional

- caché configurable
- rate limit configurable por IP
- validación de credenciales antes de responder
- verificación anti fugas con workflow y hook local

## Configuración

1. Toma como base [`src/api/config.example`](./src/api/config.example)
2. Crea tu archivo local `src/api/config.php` (ignorado por Git)
3. Define ahí tus constantes reales

### Constantes requeridas

| Constante | Propósito |
| --- | --- |
| `GITSTATS_API_ACCESS_KEY` | Clave esperada para consumir la API |
| `GITSTATS_GITHUB_TOKEN` | Token de GitHub para consultar datos |
| `GITSTATS_ALLOWED_ORIGIN` | Origen permitido para CORS |
| `GITSTATS_GITHUB_PER_PAGE` | Tamaño de paginación hacia GitHub |
| `GITSTATS_GITHUB_MAX_PAGES` | Límite de páginas a consultar |
| `GITSTATS_REQUEST_TIMEOUT_SECONDS` | Timeout de peticiones |
| `GITSTATS_INCLUDE_FORKS` | Incluye o excluye forks |
| `GITSTATS_CACHE_DAYS` | Días de caché |
| `GITSTATS_RATE_LIMIT_PER_MINUTE` | Límite por IP por minuto |

### Notas de runtime

`GITSTATS_CACHE_DAYS = 0` activa modo realtime (sin caché)

`GITSTATS_RATE_LIMIT_PER_MINUTE = 0` desactiva el límite

Los archivos cacheados se guardan en:

```txt
src/api/cache
```

## Respuestas de la API

<details>
  <summary><code>GET /api/end-porcent</code></summary>

```json
{
  "meta": {},
  "globalLanguagePercentage": {
    "PHP": 61.32,
    "JavaScript": 25.11,
    "CSS": 8.74,
    "HTML": 4.83
  },
  "totalBytes": 1234567
}
```

</details>

<details>
  <summary><code>GET /api/end-stats</code></summary>

```json
{
  "meta": {},
  "githubStats": {
    "totalRepos": 0,
    "totalStars": 0,
    "totalForks": 0,
    "totalWatchers": 0
  }
}
```

</details>

## Qué protege esta API

- no expone usuario de GitHub
- no expone nombres de repositorios
- mantiene la lógica de consulta del lado del servidor
- permite consumir métricas agregadas incluso cuando el origen incluye repos privados

## Verificación anti credenciales

- Verificación en GitHub: [`.github/workflows/verificador-seguridad.yml`](./.github/workflows/verificador-seguridad.yml)
- Hook local opcional: [`.githooks/pre-commit`](./.githooks/pre-commit)

La combinación de validación remota y hook local reduce fugas accidentales antes y después del commit.

## Comunidad y colaboracion (ES/EN)

Este repositorio incluye archivos de comunidad en formato bilingue (espanol/ingles) para facilitar colaboracion internacional.

- Guia de contribucion: [`CONTRIBUTING.md`](./CONTRIBUTING.md)
- Codigo de conducta: [`.github/CODE_OF_CONDUCT.md`](./.github/CODE_OF_CONDUCT.md)
- Politica de seguridad: [`.github/SECURITY.md`](./.github/SECURITY.md)
- Politica de soporte: [`.github/SUPPORT.md`](./.github/SUPPORT.md)
- Plantilla de pull request: [`.github/PULL_REQUEST_TEMPLATE.md`](./.github/PULL_REQUEST_TEMPLATE.md)
- Plantillas de issues: [`.github/ISSUE_TEMPLATE`](./.github/ISSUE_TEMPLATE)

Notas:

- Mantenimiento best-effort: la atencion de issues y PR puede demorar.
- No se garantiza SLA de respuesta ni de merge.
- Para vulnerabilidades, usa el proceso privado indicado en la politica de seguridad.

## Notas técnicas

- para repos privados, el token de GitHub debe tener permisos suficientes sobre repos
- el cálculo de lenguajes se basa en bytes detectados por GitHub
- solo están habilitadas las 4 rutas documentadas en este README
- si se expone un token, debes rotarlo inmediatamente en GitHub y actualizar `src/api/config.php`

## Estructura del proyecto

```txt
.
├── index.php
├── .user.ini
├── src/
│   ├── api/
│   │   ├── config.example
│   │   ├── respuestaApi.php
│   │   └── servicioApi.php
│   ├── router/
│   │   ├── routerApi.php
│   │   └── routerVistas.php
│   └── views/
│       ├── estadisticas.php
│       └── porcentajes.php
├── .github/
│   └── workflows/
│       └── verificador-seguridad.yml
└── .githooks/
    └── pre-commit
```

## Archivos clave

- [`index.php`](./index.php) (front controller único para API y vistas)
- [`src/api/servicioApi.php`](./src/api/servicioApi.php) (lógica de negocio y consumo de GitHub)
- [`src/api/respuestaApi.php`](./src/api/respuestaApi.php) (respuestas JSON y headers de seguridad)
- [`src/router/routerApi.php`](./src/router/routerApi.php) (resolución de rutas de API)
- [`src/router/routerVistas.php`](./src/router/routerVistas.php) (ruteo, render y helpers de vistas)

## Branding sugerido del repo

### Descripción corta del repositorio

```txt
Protected PHP API for anonymous GitHub stats, including private repositories.
```

### Topics sugeridos

```txt
php, github-api, github-stats, private-repositories, analytics, json-api, privacy, security, php-api, github-languages
```

### Nombre visible del producto

Usa **GitStatsVG** como branding visible y conserva **gitstats** como slug técnico del repositorio.

## Licencia

Distribuido bajo licencia BSD 3-Clause.  
Consulta [`LICENSE`](./LICENSE).