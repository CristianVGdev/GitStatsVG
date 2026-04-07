<!-- Copyright (c) 2026 CristianVGdev (github.com/CristianVGdev) | Proyecto: GitStatsVG (BSD-3-Clause) -->
# Politica de seguridad / Security Policy

## Español

### Alcance de reportes
Se aceptan reportes de seguridad relacionados con:
- Rutas HTTP publicas y manejo de solicitudes.
- Controles de autenticacion y autorizacion.
- Exposicion de datos, cache y serializacion de respuestas.
- Endurecimiento de headers, ruteo y control de errores.

### Como reportar una vulnerabilidad
No publiques detalles de explotacion en issues publicos.

Envia un reporte privado con:
1. Endpoint, archivo o componente afectado.
2. Pasos de reproduccion claros.
3. Impacto esperado y riesgo estimado.
4. Evidencia minima (capturas, payload, respuesta).
5. Propuesta de mitigacion (opcional).

Si no existe canal privado configurado, abre un issue minimo sin detalles sensibles solicitando un medio privado de contacto.

### Lo que no debes incluir en publico
- Tokens, API keys o secretos.
- Pruebas de concepto con datos reales sensibles.
- Instrucciones de explotacion completas.

### Expectativas de respuesta
Este proyecto se mantiene en modalidad best-effort.
No hay tiempos garantizados de respuesta o correccion.
Los hallazgos criticos se priorizan cuando la capacidad de mantenimiento lo permite.

### Politica de divulgacion
- Da tiempo razonable para triage y correccion antes de divulgar.
- Se recomienda divulgacion coordinada una vez publicado el fix.
- El credito tecnico al reportante es bienvenido cuando aplique.

## English

### Reporting scope
Security reports are accepted for:
- Public HTTP routes and request handling.
- Authentication and authorization controls.
- Data exposure, caching, and response serialization.
- Header hardening, routing hardening, and error handling.

### How to report a vulnerability
Do not publish exploit details in public issues.

Send a private report including:
1. Affected endpoint, file, or component.
2. Clear reproduction steps.
3. Expected impact and risk estimate.
4. Minimal evidence (screenshots, payload, response).
5. Suggested mitigation (optional).

If no private channel is configured, open a minimal public issue without sensitive details and request a private channel.

### What not to disclose publicly
- Tokens, API keys, or secrets.
- Proofs of concept using sensitive real data.
- Full exploitation instructions.

### Response expectations
This project is maintained on a best-effort basis.
No response or remediation SLA is guaranteed.
Critical findings are prioritized when maintainer capacity allows.

### Disclosure policy
- Allow reasonable time for triage and remediation before disclosure.
- Coordinated disclosure is recommended once a fix is available.
- Technical credit to reporters is welcome when applicable.
