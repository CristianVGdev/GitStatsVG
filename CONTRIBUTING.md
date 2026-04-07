<!-- Copyright (c) 2026 CristianVGdev (github.com/CristianVGdev) | Proyecto: GitStatsVG (BSD-3-Clause) -->
# Contribuir a GitStatsVG / Contributing to GitStatsVG

## Español

Gracias por tu interes en contribuir.

### Alcance del proyecto
GitStatsVG es un proyecto PHP orientado a:
- Exponer estadisticas agregadas de GitHub de forma anonima.
- Mantener una base simple y sin dependencias externas.
- Priorizar seguridad en rutas publicas y flujo de datos.

### Licencia y terminos de contribucion
Este repositorio se distribuye bajo licencia BSD 3-Clause.
Al enviar una contribucion, aceptas que tu aporte se publica bajo los mismos terminos de la licencia BSD 3-Clause del proyecto.

### Disponibilidad de mantenimiento
Este repositorio se mantiene en modalidad best-effort.
No existe SLA para revisiones, respuestas o merges.
Las PR e issues pueden tardar en atenderse y algunas propuestas pueden no priorizarse.

### Antes de empezar
1. Para cambios grandes, abre un issue primero y alinea alcance.
2. Manten cambios pequenos, enfocados y faciles de revisar.
3. Nunca subas secretos, tokens ni archivos locales de configuracion.
4. Respeta rutas, contratos JSON y comportamiento documentado.

### Reglas de desarrollo
- Conserva estilo procedural donde el codigo ya sigue ese enfoque.
- Prefiere funciones pequenas y complejidad baja.
- No debilites validaciones, headers ni controles de acceso.
- Manten consistencia con cache, ruteo y estructura existente.
- Evita cambios cosmeticos masivos sin valor funcional.

### Flujo recomendado para PR
1. Crea una rama con alcance claro.
2. Implementa una sola mejora o correccion por PR.
3. Verifica manualmente rutas afectadas.
4. Actualiza documentacion cuando haya cambios de comportamiento.
5. Abre PR con contexto tecnico y riesgos identificados.

### Checklist previo al Pull Request
- [ ] El cambio resuelve un problema concreto.
- [ ] Evalue impacto en seguridad y no introduje bypass.
- [ ] No subi credenciales, tokens ni datos privados.
- [ ] Mantuve compatibilidad con rutas y respuesta esperada.
- [ ] Actualice documentacion necesaria.
- [ ] Probe escenarios de error y entradas invalidas.

### Criterios de revision
- Las revisiones son asincronas.
- Cambios fuera de alcance, riesgosos o demasiado amplios pueden cerrarse sin merge.
- Puede solicitarse division de PR en partes mas pequenas.

### Convencion de commits
Usa mensajes claros y accionables. Ejemplos:
- `fix(api): harden authorization bearer validation`
- `docs: clarify cache policy by days`
- `security: reinforce route normalization`

### Reporte de seguridad
No publiques vulnerabilidades sensibles en issues publicos.
Sigue el proceso definido en `.github/SECURITY.md`.

## English

Thanks for your interest in contributing.

### Project scope
GitStatsVG is a PHP project focused on:
- Exposing anonymous aggregated GitHub statistics.
- Keeping a simple codebase with zero external dependencies.
- Prioritizing security on public routes and data flow.

### License and contribution terms
This repository is distributed under the BSD 3-Clause license.
By submitting a contribution, you agree your contribution is provided under the same BSD 3-Clause terms.

### Maintainer availability
This repository is maintained on a best-effort basis.
There is no SLA for responses, reviews, or merges.
PRs and issues may take time to be handled, and some proposals may not be prioritized.

### Before you start
1. For major changes, open an issue first and align scope.
2. Keep changes small, focused, and easy to review.
3. Never commit secrets, tokens, or local config files.
4. Respect documented routes, JSON contracts, and behavior.

### Development rules
- Keep procedural style where the codebase already follows it.
- Prefer small functions and low complexity.
- Do not weaken validation, headers, or access controls.
- Keep cache, routing, and project conventions consistent.
- Avoid large cosmetic-only changes.

### Recommended PR flow
1. Create a branch with a clear scope.
2. Implement one fix or improvement per PR.
3. Manually verify affected routes.
4. Update documentation when behavior changes.
5. Open a PR with technical context and identified risks.

### Pull request checklist
- [ ] The change solves one concrete problem.
- [ ] I evaluated security impact and introduced no bypass.
- [ ] I did not commit credentials, tokens, or private data.
- [ ] I kept compatibility with existing routes and response contracts.
- [ ] I updated required documentation.
- [ ] I tested error scenarios and invalid input paths.

### Review criteria
- Reviews are asynchronous.
- Out-of-scope, risky, or overly broad changes may be closed without merge.
- You may be asked to split the PR into smaller units.

### Security reporting
Do not disclose sensitive vulnerabilities in public issues.
Follow the process in `.github/SECURITY.md`.
