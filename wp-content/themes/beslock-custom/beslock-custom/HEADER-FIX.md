Resumen de cambio: "Shim" del header

Motivación
- Se detectó un pequeño hueco entre el `header` fijo y el `hero` cuando el usuario hace scroll hacia arriba en resoluciones de escritorio. El hueco era visualmente molesto.

Qué se cambió
1) JavaScript
- Archivo modificado: `assets/js/header-state.js`
- Cambio: la clase BEM usada para compactar el header pasó de `header--compact` a `header--scrolled`.
- Racional: unificar la clase que usan `main.js`, los estilos y otros scripts para evitar estados desincronizados.

2) CSS
- Archivo modificado: `assets/css/header-state.css`
- Cambio: añadido un pseudo-elemento de seguridad `.header::after` (solo en pantallas >= 1024px) que extiende unos pocos píxeles por debajo del header y cubre el hueco visual.
- Racional: solución conservadora y no disruptiva que no cambia el flujo del documento ni añade márgenes/padding; es temporal y reversible.

BEM y convenciones
- Las modificaciones respetan la convención BEM:
  - El pseudo-elemento `::after` pertenece al bloque `.header` — no se introdujeron nuevas clases de bloque ni elementos.
  - La clase modificadora que usa JS y CSS es `header--scrolled` (modificador del bloque `header`). Esto es consistente con BEM: `header` (bloque) + `--scrolled` (modificador).
  - No se han añadido clases con nombres no BEM ni utilidades globales.

Cómo revertir / mejorar (pasos siguientes recomendados)
- Para remover el shim temporal basta eliminar el bloque `@media (min-width: 1024px) { .header::after { ... } }` en `assets/css/header-state.css`.
- Corrección de raíz (opcional): investigar transformaciones/animaciones del hero o el cálculo de `--header-height`. Si se detecta que el `hero` aplica transforms que dejan subpixel gaps, ajustar hero o medir y forzar una compensación dinámica en JS (actualizar `--header-height`).

Archivos tocados
- wp-content/themes/beslock-custom/assets/js/header-state.js
- wp-content/themes/beslock-custom/assets/css/header-state.css

Autor: Cambios aplicados por el equipo de desarrollo (registro automático)
Fecha: 2026-04-21

Notas adicionales
- Este archivo se colocó en el tema hijo para que los cambios sean locales y reversibles sin tocar la plantilla padre.
