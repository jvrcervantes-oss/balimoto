# Auditoría Automática — Bali Moto Adventures
**Fecha:** 2026-06-09  
**Backup:** `Backup_20260609_auditoria/` (8 archivos)

---

## Correcciones aplicadas automáticamente

| # | Severidad | Archivo | Fix aplicado |
|---|---|---|---|
| 1 | CRÍTICO | `robots.txt` | Sitemap URL corregida: `b2kepicadv.com` → `balimotoadventures.com` |
| 2 | CRÍTICO | `robots.txt` | Añadidos `Disallow` para `Backup_20260608/`, `Backup_20260608_1812/`, `Backup_20260609_1044/`, `Backup_20260609_auditoria/`, `Deprecated/` |
| 3 | CRÍTICO | `index.html` | Encoding UTF-8 corregido en tour "Bali to Jakarta": `Javaâ€™s` → `Java's`, `â€"` → `—` |
| 4 | CRÍTICO | `b2k-tour-bali-komodo.html` | Añadida `<link rel="canonical" href="https://balimotoadventures.com/b2k-tour-bali-komodo.html">` |
| 5 | ALTO | `index.html` | Añadida `<link rel="canonical">`, `og:image`, `og:image:width/height`, `twitter:image` |
| 6 | ALTO | `b2k-tour-bali-komodo.html` | Añadida `og:image`, `og:image:width/height`, `twitter:image` |
| 7 | ALTO | `b2k-tour-7-islands.html` | Añadida `<link rel="canonical">`, `og:image`, `og:image:width/height`, `twitter:image` |
| 8 | ALTO | `7-islands-bali-motorcycle-tour.html` | Añadida `og:image`, `og:image:width/height`, `twitter:card`, `twitter:image` |
| 9 | ALTO | `bali-to-komodo-motorcycle-tour.html` | Añadida `og:image`, `og:image:width/height`, `twitter:card`, `twitter:image` |
| 10 | ALTO | `best-motorcycle-tours-indonesia.html` | Añadida `og:image`, `og:image:width/height`, `twitter:card`, `twitter:image` |
| 11 | ALTO | `sitemap.xml` | Añadidas páginas legales: `privacy-policy.html`, `terms.html`, `data-deletion.html` |

**Imágenes OG usadas:**
- Páginas Bali to Komodo / Home / SEO general: `images/HERO_INDEX.JPG`
- Páginas 7 Islands: `images/HERO_7ISL.jpg`

---

## Issues NO tocados — requieren permiso o decisión del owner

| # | Severidad | Archivo | Issue | Acción requerida |
|---|---|---|---|---|
| 1 | MEDIO | `b2k-tour-bali-komodo.html` | Meta Pixel falta evento `Contact` en botones de WhatsApp | Confirmar qué botones deben trackearlo |
| 2 | BAJO | `index.html` | Lightbox `<img alt="">` no se actualiza dinámicamente | Mejora de accesibilidad, bajo impacto |
| 3 | BAJO | `index.html` | Google Apps Script URL de newsletter pública en cliente | Requiere migración a proxy backend o servicio externo |
| 4 | BAJO | `b2k-tour-bali-komodo.html` | `innerHTML` con strings internos (bajo riesgo, datos no son user-input) | Mejora de calidad, no urgente |

---

## Pendientes del checklist de producción (sin cambios)

Estos ya estaban documentados en `contexto/proyecto_b2k.md`:
- [ ] Sustituir imágenes base64 por URLs hosteadas en Hostinger (reduce tamaño ~4MB → <200KB)
- [ ] Cambiar password admin `b2k2026` antes de ir live
- [ ] GA4 (owner necesita crear cuenta)
- [ ] Página Sumba Challenge (waitlist)
- [ ] Fotos reales para 7 Islands
- [ ] Reseñas reales de clientes
- [ ] Confirmar email `info@b2kepicadv.com`
- [ ] Subir sitemap a Google Search Console
- [ ] Verificar webhook deploy balimoto → Hostinger

---

## Verificaciones OK — sin cambios necesarios

- Meta Pixel ID `1916298258994371` correctamente instalado en las 3 páginas principales ✓
- Footer links legales presentes en todas las páginas ✓
- Admin password protegida con hash SHA-256 (no plaintext) ✓
- Sin `console.log` en producción ✓
- Marca "Bali Moto Adventures" consistente en todos los archivos ✓
- Schema.org JSON-LD presente en páginas SEO ✓
- `preconnect` Google Fonts presente en todas las páginas ✓
- `async` en Google Tag Manager ✓
- Sin React dev builds (no usa React) ✓
